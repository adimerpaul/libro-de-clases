<?php

use App\Models\Baja;
use App\Models\BajaDetalle;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Ver Productos', 'Editar Productos', 'Editar Lotes Farmacia'] as $nombre) {
        Permission::findOrCreate($nombre, 'web');
    }
    $this->usuario = User::create(['name' => 'PRUEBA', 'username' => 'prueba-lotes', 'password' => bcrypt('prueba')]);
    $this->usuario->givePermissionTo('Ver Productos');
    Sanctum::actingAs($this->usuario);
    $tipo = TipoProducto::firstOrCreate(['nombre' => 'FARMACIA']);
    $this->producto = Producto::create(['nombre' => 'PRUEBA LOTE', 'tipo_producto_id' => $tipo->id]);
    $compra = Compra::create(['user_id' => $this->usuario->id, 'fecha_hora' => now()]);
    $this->lote = CompraDetalle::create([
        'compra_id' => $compra->id, 'producto_id' => $this->producto->id,
        'nombre' => 'PRUEBA LOTE', 'lote' => 'ANTERIOR', 'fecha_vencimiento' => '2027-01-01',
        'cantidad' => 10, 'precio' => 2, 'total' => 20,
    ]);
    $this->url = '/api/productos/'.$this->producto->id.'/historial/COMPRA/'.$this->lote->id;
});

it('exige el permiso específico aunque pueda editar productos', function () {
    $this->usuario->givePermissionTo('Editar Productos');
    $this->putJson($this->url, ['lote' => 'NUEVO', 'fecha_vencimiento' => null])->assertForbidden();
    expect($this->lote->fresh()->lote)->toBe('ANTERIOR');
});

it('corrige desde compra o venta y sincroniza solo el lote vinculado', function (string $origen) {
    $this->usuario->givePermissionTo('Editar Lotes Farmacia');
    $venta = Venta::create(['user_id' => $this->usuario->id, 'fecha_hora' => now()]);
    $vendido = VentaDetalle::create([
        'venta_id' => $venta->id, 'producto_id' => $this->producto->id,
        'compra_detalle_id' => $this->lote->id, 'nombre' => 'PRUEBA LOTE',
        'lote' => 'ANTERIOR', 'cantidad' => 2, 'precio' => 3, 'total' => 6,
    ]);
    $baja = Baja::create(['user_id' => $this->usuario->id, 'fecha_hora' => now(), 'motivo' => 'DETERIORO']);
    $bajado = BajaDetalle::create([
        'baja_id' => $baja->id, 'producto_id' => $this->producto->id,
        'compra_detalle_id' => $this->lote->id, 'nombre' => 'PRUEBA LOTE', 'lote' => 'ANTERIOR', 'cantidad' => 1,
    ]);
    $otroLote = $this->lote->replicate();
    $otroLote->save();
    $url = $origen === 'COMPRA' ? $this->url : '/api/productos/'.$this->producto->id.'/historial/VENTA/'.$vendido->id;
    $this->putJson($url, [
        'lote' => 'corregido', 'fecha_vencimiento' => '2028-02-29',
        'cantidad' => 999, 'precio' => 999, 'fecha_hora' => '2000-01-01', 'compra_detalle_id' => $otroLote->id,
    ])->assertOk();
    foreach ([$this->lote, $vendido, $bajado] as $detalle) {
        expect($detalle->fresh()->lote)->toBe('CORREGIDO');
        expect($detalle->fresh()->fecha_vencimiento->format('Y-m-d'))->toBe('2028-02-29');
    }
    expect($this->lote->fresh()->cantidad)->toBe('10.0000');
    expect($vendido->fresh()->precio)->toBe('3.00');
    expect($vendido->fresh()->compra_detalle_id)->toBe($this->lote->id);
    expect($otroLote->fresh()->lote)->toBe('ANTERIOR');
})->with(['COMPRA', 'VENTA']);

it('valida fechas y pertenencia al producto', function () {
    $this->usuario->givePermissionTo('Editar Lotes Farmacia');
    $this->putJson($this->url, ['lote' => 'X', 'fecha_vencimiento' => '2027-02-30'])
        ->assertUnprocessable()->assertJsonValidationErrors('fecha_vencimiento');
    $otro = $this->producto->replicate();
    $otro->save();
    $this->putJson('/api/productos/'.$otro->id.'/historial/COMPRA/'.$this->lote->id,
        ['lote' => 'X', 'fecha_vencimiento' => null])->assertNotFound();
    expect($this->lote->fresh()->lote)->toBe('ANTERIOR');
});
