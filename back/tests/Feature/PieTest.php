<?php

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\PieDocumento;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();
    Storage::fake(PieDocumento::DISCO);

    $this->pie = User::create(['name' => 'Valentina Torres', 'email' => 'pie@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::crud('pie'));
    $this->utp = User::create(['name' => 'UTP', 'email' => 'utp@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::de('pie', Permisos::VER));

    $this->curso = Curso::create(['anio' => 2026, 'nivel' => '3° Básico', 'letra' => 'A']);
    $this->est = Estudiante::create(['run' => '11111111-1', 'nombres' => 'Catalina', 'apellido_paterno' => 'Silva']);
    Matricula::create(['estudiante_id' => $this->est->id, 'curso_id' => $this->curso->id, 'numero_lista' => 1, 'fecha_matricula' => '2026-03-01']);
});

function comoEnPie(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

function casoPie(array $cambios = []): array
{
    return $cambios + [
        'anio' => 2026, 'estado' => 'activo', 'diagnostico' => 'Trastorno específico del lenguaje (TEL)',
        'fecha_ingreso' => '2026-03-10', 'apoyos' => ['Aula de recursos', 'PACI'],
        'equipo' => [['especialidad' => 'Fonoaudiólogo(a)', 'nombre' => 'Rodrigo Pino']],
    ];
}

test('ingresa un estudiante al PIE: el tipo de NEE sale del diagnóstico y marca la ficha', function () {
    $caso = comoEnPie($this->pie)->postJson('/api/pie/casos', casoPie(['estudiante_id' => $this->est->id, 'responsable_id' => $this->pie->id]))
        ->assertCreated()->json();

    expect($caso['tipo_nee'])->toBe('transitoria')
        ->and($caso['curso']['nombre'])->toBe($this->curso->nombre)
        ->and($caso['equipo'][0]['especialidad'])->toBe('Fonoaudiólogo(a)')
        ->and($this->est->fresh()->pie)->toBeTrue();

    // Un caso por estudiante y año
    comoEnPie($this->pie)->postJson('/api/pie/casos', casoPie(['estudiante_id' => $this->est->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('estudiante_id');

    // Al egresar deja de figurar como PIE; exige fecha y motivo
    comoEnPie($this->pie)->putJson("/api/pie/casos/{$caso['id']}", casoPie(['estado' => 'egresado']))
        ->assertUnprocessable()->assertJsonValidationErrors(['fecha_egreso', 'motivo_egreso']);
    comoEnPie($this->pie)->putJson("/api/pie/casos/{$caso['id']}", casoPie(['estado' => 'egresado', 'fecha_egreso' => '2026-11-30', 'motivo_egreso' => 'Alta por reevaluación']))
        ->assertOk();
    expect($this->est->fresh()->pie)->toBeFalse();
});

test('solo ingresa estudiantes con matrícula activa en el año', function () {
    comoEnPie($this->pie)->postJson('/api/pie/casos', casoPie(['estudiante_id' => $this->est->id, 'anio' => 2027]))
        ->assertUnprocessable()->assertJsonValidationErrors('estudiante_id');
});

test('el expediente guarda imágenes en disco privado y las sirve con permiso', function () {
    $caso = comoEnPie($this->pie)->postJson('/api/pie/casos', casoPie(['estudiante_id' => $this->est->id]))->json();

    $doc = comoEnPie($this->pie)->post("/api/pie/casos/{$caso['id']}/documentos", [
        'tipo' => 'Evidencia / fotografía',
        'archivo' => UploadedFile::fake()->image('trabajo.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated()->json();

    expect($doc['es_imagen'])->toBeTrue()
        ->and($doc['titulo'])->toBe('trabajo')
        ->and($doc)->not->toHaveKey('archivo_path');
    Storage::disk(PieDocumento::DISCO)->assertExists(PieDocumento::find($doc['id'])->archivo_path);

    comoEnPie($this->utp)->get("/api/pie/documentos/{$doc['id']}/archivo")->assertOk();
    comoEnPie($this->utp)->deleteJson("/api/pie/documentos/{$doc['id']}")->assertForbidden();

    // Bitácora
    comoEnPie($this->pie)->postJson("/api/pie/casos/{$caso['id']}/seguimientos", [
        'fecha' => '2026-04-10', 'tipo' => 'Sesión en aula de recursos', 'area' => 'Lectoescritura', 'descripcion' => 'Conciencia fonológica',
    ])->assertCreated();

    $detalle = comoEnPie($this->utp)->getJson("/api/pie/casos/{$caso['id']}")->assertOk()->json();
    expect($detalle['documentos'])->toHaveCount(1)->and($detalle['seguimientos'])->toHaveCount(1);
});

test('quien solo ve PIE no crea casos, y sin permiso no hay acceso', function () {
    comoEnPie($this->utp)->getJson('/api/pie/casos?anio=2026')->assertOk();
    comoEnPie($this->utp)->postJson('/api/pie/casos', casoPie(['estudiante_id' => $this->est->id]))->assertForbidden();

    $otro = User::create(['name' => 'Otro', 'email' => 'otro@demo.cl', 'password' => 'demo123']);
    comoEnPie($otro)->getJson('/api/pie/casos')->assertForbidden();
});

test('eliminar el estudiante elimina sus casos PIE', function () {
    $caso = comoEnPie($this->pie)->postJson('/api/pie/casos', casoPie(['estudiante_id' => $this->est->id]))->json();

    $this->est->delete();

    comoEnPie($this->pie)->getJson("/api/pie/casos/{$caso['id']}")->assertNotFound();
});
