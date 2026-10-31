<?php

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Instrumento;
use App\Models\MarcaRfid;
use App\Models\Matricula;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();
    config(['services.rfid.hora_inicio' => '08:00', 'services.rfid.tolerancia' => 10]);

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(['estudiantes.ver', 'estudiantes.editar', 'usuarios.crear']);
    $this->docente = User::create(['name' => 'Marcela', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->madre = User::create(['name' => 'María Rojas', 'email' => 'apoderado@demo.cl', 'password' => 'demo123'])->assignRole('Apoderado');

    $this->curso = Curso::create(['anio' => (int) now()->year, 'nivel' => '2° Medio', 'letra' => 'B']);
    $mat = Asignatura::create(['nombre' => 'Matemática', 'codigo' => 'MAT']);
    $this->asignacion = Asignacion::create(['curso_id' => $this->curso->id, 'asignatura_id' => $mat->id, 'docente_id' => $this->docente->id]);

    $this->hija = Estudiante::create(['run' => '11111111-1', 'nombres' => 'Antonia', 'apellido_paterno' => 'Rojas', 'apellido_materno' => 'Soto']);
    $this->otro = Estudiante::create(['run' => '22222222-2', 'nombres' => 'Luis', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'Mora']);
    foreach ([$this->hija, $this->otro] as $i => $e) {
        Matricula::create(['estudiante_id' => $e->id, 'curso_id' => $this->curso->id, 'numero_lista' => $i + 1, 'fecha_matricula' => now(), 'estado' => 'activa']);
    }

    // Instrumento de 10 puntos, exigencia 60 %
    $this->instrumento = Instrumento::create([
        'docente_id' => $this->docente->id, 'asignacion_id' => $this->asignacion->id, 'nombre' => 'Control funciones',
        'tipo' => 'Control', 'exigencia' => 60, 'estado' => 'listo', 'fecha_aplicacion' => now()->toDateString(),
    ]);
    $this->instrumento->preguntas()->create(['orden' => 1, 'tipo' => 'desarrollo', 'enunciado' => 'Explique', 'puntaje' => 10]);
});

function comoFamilia(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

test('el rol Apoderado existe con familia.ver y aparece en el login', function () {
    expect($this->madre->tienePermiso(Permisos::VER_FAMILIA))->toBeTrue();
    $this->getJson('/api/login/roles')->assertOk()->assertSee('Apoderado');
});

test('el administrador vincula una cuenta de apoderado o la crea desde la ficha', function () {
    $api = comoFamilia($this->admin);

    $api->getJson('/api/admin/apoderados/cuentas')->assertOk()->assertJsonCount(1)->assertJsonPath('0.email', 'apoderado@demo.cl');
    $api->postJson("/api/admin/estudiantes/{$this->hija->id}/apoderados", ['user_id' => $this->madre->id, 'parentesco' => 'Madre'])->assertCreated();
    $api->postJson("/api/admin/estudiantes/{$this->hija->id}/apoderados", ['user_id' => $this->madre->id, 'parentesco' => 'Madre'])->assertStatus(422);
    // Un docente no es cuenta de apoderado
    $api->postJson("/api/admin/estudiantes/{$this->hija->id}/apoderados", ['user_id' => $this->docente->id, 'parentesco' => 'Padre'])->assertStatus(422);

    $api->postJson("/api/admin/estudiantes/{$this->otro->id}/apoderados", [
        'parentesco' => 'Padre', 'cuenta' => ['name' => 'Rodrigo Pérez', 'email' => 'rperez@demo.cl', 'password' => 'demo123'],
    ])->assertCreated()->assertJsonPath('user.email', 'rperez@demo.cl');
    expect(User::where('email', 'rperez@demo.cl')->first()->hasRole('Apoderado'))->toBeTrue();

    $vinculos = $api->getJson("/api/admin/estudiantes/{$this->hija->id}/apoderados")->assertJsonCount(1, 'vinculos')->json('vinculos');
    $api->deleteJson("/api/admin/apoderados/{$vinculos[0]['id']}")->assertOk();
    expect($this->hija->vinculos()->count())->toBe(0);
});

test('el docente registra notas de su instrumento y se calculan con la escala', function () {
    $api = comoFamilia($this->docente);
    $url = "/api/docente/instrumentos/{$this->instrumento->id}/notas";

    $api->getJson($url)->assertOk()->assertJsonCount(2, 'estudiantes')->assertJsonPath('puntaje_total', 10);
    $api->putJson($url, ['notas' => [
        ['estudiante_id' => $this->hija->id, 'puntaje' => 6],   // 60 % → 4,0
        ['estudiante_id' => $this->otro->id, 'puntaje' => 10],  // 7,0
    ]])->assertOk()->assertJsonPath('estudiantes.0.nota', 4)->assertJsonPath('estudiantes.1.nota', 7)->assertJsonPath('promedio', 5.5);

    $api->putJson($url, ['notas' => [['estudiante_id' => $this->hija->id, 'puntaje' => 11]]])->assertStatus(422);
    $api->putJson($url, ['notas' => [['estudiante_id' => $this->otro->id, 'puntaje' => null]]])->assertJsonPath('estudiantes.1.nota', null);

    $ajeno = User::create(['name' => 'Otro', 'email' => 'otro@demo.cl', 'password' => 'demo123'])->givePermissionTo(Permisos::DICTAR_CLASES);
    comoFamilia($ajeno)->getJson($url)->assertForbidden();
});

test('el apoderado ve las notas y la asistencia solo de sus hijos', function () {
    $this->travelTo(today()->setTime(10, 0));
    $this->hija->vinculos()->create(['user_id' => $this->madre->id, 'parentesco' => 'Madre']);
    $this->instrumento->calificaciones()->create(['estudiante_id' => $this->hija->id, 'puntaje' => 9, 'nota' => 6.1]);
    $this->instrumento->calificaciones()->create(['estudiante_id' => $this->otro->id, 'puntaje' => 3, 'nota' => 2.5]);
    MarcaRfid::create(['codigo' => 'X', 'estudiante_id' => $this->hija->id, 'curso_id' => $this->curso->id, 'lector' => 'Portal', 'tipo' => 'entrada', 'marcado_en' => today()->setTime(8, 20)]);

    $api = comoFamilia($this->madre);
    $api->getJson('/api/familia/hijos')->assertOk()->assertJsonCount(1)->assertJsonPath('0.curso', '2° Medio B');
    $api->getJson("/api/familia/hijos/{$this->hija->id}")->assertOk()
        ->assertJsonPath('notas.promedio', 6.1)
        ->assertJsonPath('notas.asignaturas.0.asignatura', 'Matemática')
        ->assertJsonPath('notas.asignaturas.0.notas.0.nota', 6.1)
        ->assertJsonPath('asistencia.hoy.propuesta', 'atrasado')
        ->assertJsonPath('asistencia.atrasos', 1);
    $api->getJson("/api/familia/hijos/{$this->otro->id}")->assertNotFound();

    // Sin familia.ver no se entra al portal
    comoFamilia($this->docente)->getJson('/api/familia/hijos')->assertForbidden();
});

test('solo usuarios con el rol Apoderado aparecen como cuenta, buscables por nombre, correo o RUN', function () {
    // Tiene familia.ver directo pero no el rol: no es apoderado
    User::create(['name' => 'Otra Persona', 'email' => 'otra@demo.cl', 'password' => 'demo123'])->givePermissionTo(Permisos::VER_FAMILIA);
    $this->madre->update(['run' => '15123456-7']);
    $this->hija->vinculos()->create(['user_id' => $this->madre->id, 'parentesco' => 'Madre']);
    $api = comoFamilia($this->admin);

    $api->getJson('/api/admin/apoderados/cuentas')->assertOk()->assertJsonCount(1)
        ->assertJsonPath('0.email', 'apoderado@demo.cl')->assertJsonPath('0.estudiantes_count', 1);
    $api->getJson('/api/admin/apoderados/cuentas?q=15.123')->assertJsonCount(1);
    $api->getJson('/api/admin/apoderados/cuentas?q=otra')->assertJsonCount(0);
});

test('el listado de estudiantes muestra la cuenta vinculada y filtra por ella', function () {
    $this->hija->vinculos()->create(['user_id' => $this->madre->id, 'parentesco' => 'Madre']);
    $api = comoFamilia($this->admin);

    $api->getJson('/api/admin/estudiantes')->assertOk()
        ->assertJsonPath('0.apellido_paterno', 'Pérez')->assertJsonCount(0, '0.cuentas')
        ->assertJsonPath('1.cuentas.0.user.name', 'María Rojas')->assertJsonPath('1.cuentas.0.parentesco', 'Madre');

    $api->getJson('/api/admin/estudiantes?cuenta=con')->assertJsonCount(1)->assertJsonPath('0.nombres', 'Antonia');
    $api->getJson('/api/admin/estudiantes?cuenta=sin')->assertJsonCount(1)->assertJsonPath('0.nombres', 'Luis');
    $api->getJson("/api/admin/estudiantes?apoderado_id={$this->madre->id}")->assertJsonCount(1)->assertJsonPath('0.nombres', 'Antonia');
    // Buscar por nombre ya no trae a todos
    $api->getJson('/api/admin/estudiantes?q=Luis')->assertJsonCount(1);
});
