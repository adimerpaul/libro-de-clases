<?php

use App\Http\Controllers\PacienteController;
use App\Models\Internacion;
use App\Models\InternacionItem;
use App\Models\Paciente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    // Esquema aislado en SQLite: no ejecuta las migraciones de inventario ni toca datos reales.
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    app('db')->purge('sqlite');
    $tables = [
        'pacientes' => ['nombre_completo'],
        'cierres_caja' => ['user_id', 'fecha'],
        'internaciones' => ['paciente_id', 'seguro_id', 'fecha_ingreso', 'pagado_en', 'pagado_por_id', 'venta_id', 'monto_pagado', 'pago_tipo', 'pago_observacion'],
        'internacion_items' => ['internacion_id', 'nombre', 'precio', 'cantidad', 'total'],
        'ventas' => ['user_id', 'paciente_id', 'seguro_id', 'fecha_hora', 'tipo_pago', 'comentario', 'estado', 'total', 'pago', 'cambio', 'tipo_movimiento', 'fecha_hora_cobro', 'cobrado_por_id'],
        'venta_detalles' => ['venta_id', 'producto_id', 'nombre', 'precio', 'cantidad', 'total'],
    ];
    foreach ($tables as $name => $fields) {
        Schema::create($name, function (Blueprint $table) use ($fields) {
            $table->id();
            foreach ($fields as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
    }
});

it('cobra internaciones con o sin ventas y evita repetir el cobro', function (bool $conVenta) {
    $paciente = Paciente::create(['nombre_completo' => 'PRUEBA']);
    $usuario = Mockery::mock(User::class)->makePartial();
    $usuario->shouldReceive('hasPermissionTo')->with('Crear Ventas')->andReturnTrue();
    $usuario->id = 1;
    $request = Request::create('/', 'POST', ['tipo_pago' => 'QR']);
    $request->setUserResolver(fn () => $usuario);
    $crearInternacion = function (float $total) use ($paciente) {
        $internacion = Internacion::create(['paciente_id' => $paciente->id, 'fecha_ingreso' => '2026-09-01']);
        InternacionItem::create(['internacion_id' => $internacion->id, 'nombre' => 'CARGO', 'precio' => $total, 'cantidad' => 1, 'total' => $total]);

        return $internacion;
    };
    $primera = $crearInternacion(100);
    $segunda = $crearInternacion(50);
    $pagada = $crearInternacion(70);
    $pagada->update(['pagado_en' => now()]);
    $vacia = Internacion::create(['paciente_id' => $paciente->id]);
    if ($conVenta) {
        Venta::create(['paciente_id' => $paciente->id, 'estado' => 'PENDIENTE', 'tipo_movimiento' => 'INGRESO', 'total' => 25]);
    }
    $controller = new PacienteController;
    $data = $controller->cobrarTodo($request, $paciente->id)->getData(true);
    expect($data['internaciones_cobradas'])->toBe(2)
        ->and($data['ventas_cobradas'])->toBe($conVenta ? 1 : 0)
        ->and((float) $data['total'])->toBe($conVenta ? 175.0 : 150.0)
        ->and($primera->fresh()->pagada)->toBeTrue()
        ->and($segunda->fresh()->pagada)->toBeTrue()
        ->and($vacia->fresh()->pagada)->toBeFalse()
        ->and(Venta::where('estado', 'ACTIVO')->count())->toBe(2);
    expect(fn () => $controller->cobrarTodo($request, $paciente->id))
        ->toThrow(HttpException::class);
    expect(Venta::count())->toBe($conVenta ? 3 : 2);
})->with([false, true]);
