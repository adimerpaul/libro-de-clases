<?php

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\PieCaso;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->pie = User::create(['name' => 'Valentina Torres', 'email' => 'pie@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::crud('pie'));
    $this->utp = User::create(['name' => 'UTP', 'email' => 'utp@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::de('pie', Permisos::VER));

    $this->curso = Curso::create(['anio' => 2026, 'nivel' => '3° Básico', 'letra' => 'A']);
    $this->conPie = Estudiante::create(['run' => '11111111-1', 'nombres' => 'Catalina', 'apellido_paterno' => 'Silva']);
    $this->sinPie = Estudiante::create(['run' => '22222222-2', 'nombres' => 'Pedro', 'apellido_paterno' => 'Rojas']);
    foreach ([$this->conPie, $this->sinPie] as $i => $e) {
        Matricula::create(['estudiante_id' => $e->id, 'curso_id' => $this->curso->id, 'numero_lista' => $i + 1, 'fecha_matricula' => '2026-03-01']);
    }
    PieCaso::create(['estudiante_id' => $this->conPie->id, 'anio' => 2026, 'estado' => 'activo', 'diagnostico' => 'Discapacidad intelectual', 'tipo_nee' => 'permanente', 'fecha_ingreso' => '2026-03-10']);
});

function comoEnLibroPie(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

test('el libro PIE del curso lista solo a los estudiantes con caso PIE del año', function () {
    $cursos = comoEnLibroPie($this->utp)->getJson('/api/pie/libro/cursos?anio=2026')->assertOk()->json();
    expect($cursos[0]['pie_count'])->toBe(1);

    $libro = comoEnLibroPie($this->utp)->getJson("/api/pie/libro/{$this->curso->id}")->assertOk()->json();
    expect($libro['alumnos'])->toHaveCount(1)
        ->and($libro['alumnos'][0]['nombres'])->toBe('Catalina')
        ->and($libro['alumnos'][0]['nee'])->toBe('Permanente');
});

test('guarda hojas, valida claves y respeta permisos', function () {
    $url = "/api/pie/libro/{$this->curso->id}/hojas/asistencia/2026-11";
    comoEnLibroPie($this->pie)->putJson($url, ['datos' => ['m' => [$this->conPie->id => ['3' => 'P']]]])->assertOk();
    comoEnLibroPie($this->utp)->getJson($url)->assertOk()->assertJsonPath("datos.m.{$this->conPie->id}.3", 'P');
    comoEnLibroPie($this->utp)->putJson($url, ['datos' => []])->assertForbidden();
    comoEnLibroPie($this->pie)->putJson("/api/pie/libro/{$this->curso->id}/hojas/otra-cosa", ['datos' => []])->assertNotFound();

    $serie = comoEnLibroPie($this->utp)->getJson("/api/pie/libro/{$this->curso->id}/serie/asistencia")->assertOk()->json();
    expect($serie)->toHaveCount(1);
});

test('el registro diario firmado no se edita hasta quitar la firma', function () {
    $base = "/api/pie/libro/{$this->curso->id}";
    comoEnLibroPie($this->pie)->putJson("$base/hojas/actividades/2026-11-10", ['datos' => ['obs' => 'Co-docencia']])->assertOk();
    comoEnLibroPie($this->pie)->postJson("$base/actividades/2026-11-10/firma")->assertOk()->assertJsonPath('firma.nombre', 'Valentina Torres');
    comoEnLibroPie($this->pie)->putJson("$base/hojas/actividades/2026-11-10", ['datos' => ['obs' => 'Cambio']])->assertStatus(423);
    comoEnLibroPie($this->pie)->deleteJson("$base/actividades/2026-11-10/firma")->assertOk()->assertJsonPath('firma', null);
    comoEnLibroPie($this->pie)->putJson("$base/hojas/actividades/2026-11-10", ['datos' => ['obs' => 'Cambio']])->assertOk();
});

test('conserva los objetos vacíos del JSON (no los convierte en listas)', function () {
    $url = "/api/pie/libro/{$this->curso->id}/hojas/hojas";
    comoEnLibroPie($this->pie)->putJson($url, ['datos' => ['by' => (object) []]])->assertOk();
    expect(comoEnLibroPie($this->utp)->getJson($url)->getContent())->toContain('"by":{}');
});
