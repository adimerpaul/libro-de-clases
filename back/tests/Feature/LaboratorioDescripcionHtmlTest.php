<?php

use App\Models\Paciente;
use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('conserva formato seguro al guardar y lo imprime en la solicitud', function () {
    $user = User::create(['name' => 'RESPONSABLE PRUEBA', 'username' => 'html-prueba', 'password' => bcrypt('prueba')]);
    foreach (['Crear Productos', 'Editar Productos', 'Crear Solicitudes Laboratorio'] as $permiso) {
        $user->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
    }
    Sanctum::actingAs($user);
    $tipo = TipoProducto::create(['nombre' => 'LABORATORIO PRUEBA', 'es_laboratorio' => true]);
    $html = '<p style="text-align:center"><strong>Descripción clínica</strong></p><ul><li>Primera indicación</li></ul><script>alert(1)</script><img src="x" onerror="alert(2)">';
    $producto = $this->postJson('/api/productos', [
        'nombre' => 'PRUEBA HTML', 'tipo_producto_id' => $tipo->id, 'descripcion' => $html,
    ])->assertCreated()->json();
    expect($producto['descripcion'])->toContain('<strong>Descripción clínica</strong>', '<ul><li>')
        ->not->toContain('<script', 'onerror', '<img');
    $this->putJson('/api/productos/'.$producto['id'], [
        'nombre' => 'PRUEBA HTML', 'tipo_producto_id' => $tipo->id, 'descripcion' => $producto['descripcion'],
    ])->assertOk()->assertJsonPath('descripcion', $producto['descripcion']);
    $paciente = Paciente::create(['nombre_completo' => 'PACIENTE DE PRUEBA', 'ci' => 'PRUEBA', 'sexo' => 'F']);
    $solicitud = $this->postJson('/api/solicitudes-laboratorio', [
        'paciente_id' => $paciente->id, 'fecha_solicitud' => '2026-09-10', 'hora_solicitud' => '10:00',
        'producto_ids' => [$producto['id']],
    ])->assertCreated()->json();
    $this->get('/api/solicitudes-laboratorio/'.$solicitud['id'].'/impresion')
        ->assertOk()->assertSee('<strong>Descripción clínica</strong>', false)->assertDontSee('<script', false);
    $pdf = $this->get('/api/solicitudes-laboratorio/'.$solicitud['id'].'/pdf')->assertOk();
    expect($pdf->headers->get('Content-Type'))->toBe('application/pdf');
    if (getenv('LAB_PDF_QA')) {
        file_put_contents(storage_path('app/laboratorio-html-qa.pdf'), $pdf->getContent());
    }
});

it('presenta las descripciones antiguas como texto y conserva sus saltos', function () {
    $producto = new Producto(['descripcion' => "PRIMERA LÍNEA\nSEGUNDA & TERCERA"]);
    expect($producto->descripcion_html)->toContain('<br', '&amp;');
});
