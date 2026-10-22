<?php

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\Planificacion;
use App\Models\Role;
use App\Models\User;
use App\Support\Permisos;
use App\Support\Run;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(array_values(array_diff(Permisos::todos(), [Permisos::DICTAR_CLASES])));
    $this->docente = User::create(['name' => 'Docente', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
});

function tokenDe(User $user): string
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');

    // El guard de Sanctum recuerda al usuario entre requests del mismo test
    app('auth')->forgetGuards();

    return $token;
}

function comoAdmin()
{
    return test()->withToken(tokenDe(test()->admin));
}

function usuarioCon(array $permisos, string $email = 'otro@demo.cl'): User
{
    return User::create(['name' => 'Otro', 'email' => $email, 'password' => 'demo123'])->givePermissionTo($permisos);
}

test('valida el RUN chileno con dígito verificador', function () {
    expect(Run::esValido('11.111.111-1'))->toBeTrue()
        ->and(Run::esValido('12.345.678-5'))->toBeTrue()
        ->and(Run::esValido('12345678-K'))->toBeFalse()
        ->and(Run::normalizar('12.345.678-k'))->toBe('12345678-K');
});

test('cada acción del CRUD exige su propio permiso', function () {
    $this->withToken(tokenDe($this->docente))->getJson('/api/admin/cursos')->assertForbidden();

    $token = tokenDe(usuarioCon(['cursos.ver', 'cursos.crear']));
    $this->withToken($token)->getJson('/api/admin/cursos')->assertOk();
    $curso = $this->withToken($token)->postJson('/api/admin/cursos', ['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'A'])
        ->assertCreated()->json();
    $this->withToken($token)->putJson("/api/admin/cursos/{$curso['id']}", ['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'B'])
        ->assertForbidden();
    $this->withToken($token)->deleteJson("/api/admin/cursos/{$curso['id']}")->assertForbidden();
    $this->withToken($token)->getJson('/api/admin/asignaturas')->assertForbidden();

    // Asignación docente lee cursos y asignaturas para su formulario, pero no los modifica
    $token = tokenDe(usuarioCon(['asignaciones.ver'], 'asig@demo.cl'));
    $this->withToken($token)->getJson('/api/admin/cursos')->assertOk();
    $this->withToken($token)->getJson('/api/admin/asignaturas')->assertOk();
    $this->withToken($token)->postJson('/api/admin/asignaturas', ['nombre' => 'Arte', 'codigo' => 'ART'])->assertForbidden();
});

test('crea asignatura, curso y asigna un docente', function () {
    $asignatura = comoAdmin()->postJson('/api/admin/asignaturas', ['nombre' => 'Matemática', 'codigo' => 'mat'])
        ->assertCreated()->assertJsonPath('codigo', 'MAT')->json();

    $curso = comoAdmin()->postJson('/api/admin/cursos', [
        'anio' => 2026, 'nivel' => '2° Medio', 'letra' => 'b', 'profesor_jefe_id' => $this->docente->id,
    ])->assertCreated()->assertJsonPath('nombre', '2° Medio B')->json();

    comoAdmin()->postJson('/api/admin/asignaciones', [
        'curso_id' => $curso['id'], 'asignatura_id' => $asignatura['id'], 'docente_id' => $this->docente->id, 'horas_semanales' => 7,
    ])->assertCreated()->assertJsonPath('docente.name', 'Docente');

    // La misma asignatura no puede tener dos docentes en el mismo curso
    comoAdmin()->postJson('/api/admin/asignaciones', [
        'curso_id' => $curso['id'], 'asignatura_id' => $asignatura['id'], 'docente_id' => $this->docente->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('asignatura_id');
});

test('no repite cursos ni asigna a quien no dicta clases', function () {
    Curso::create(['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'A']);
    comoAdmin()->postJson('/api/admin/cursos', ['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'A'])
        ->assertUnprocessable()->assertJsonValidationErrors('letra');

    $asignatura = Asignatura::create(['nombre' => 'Inglés', 'codigo' => 'ING']);

    comoAdmin()->postJson('/api/admin/asignaciones', [
        'curso_id' => Curso::first()->id, 'asignatura_id' => $asignatura->id, 'docente_id' => $this->admin->id,
    ])->assertUnprocessable();
});

test('eliminar es lógico, en cascada y permite volver a crear', function () {
    $curso = Curso::create(['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'A']);
    $asignatura = Asignatura::create(['nombre' => 'Inglés', 'codigo' => 'ING']);
    $asignacion = Asignacion::create(['curso_id' => $curso->id, 'asignatura_id' => $asignatura->id, 'docente_id' => $this->docente->id]);
    $plan = Planificacion::create([
        'docente_id' => $this->docente->id, 'asignacion_id' => $asignacion->id, 'fecha' => '2026-03-02', 'titulo' => 'Clase 1',
    ]);

    comoAdmin()->deleteJson("/api/admin/cursos/{$curso->id}")->assertOk();

    expect(Curso::find($curso->id))->toBeNull()
        ->and(Curso::withTrashed()->find($curso->id)->trashed())->toBeTrue()
        ->and(Asignacion::withTrashed()->find($asignacion->id)->trashed())->toBeTrue()
        ->and(Planificacion::withTrashed()->find($plan->id)->trashed())->toBeTrue();

    // El curso y la asignatura eliminados no impiden crearlos de nuevo
    comoAdmin()->postJson('/api/admin/cursos', ['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'A'])->assertCreated();
    comoAdmin()->deleteJson("/api/admin/asignaturas/{$asignatura->id}")->assertOk();
    comoAdmin()->postJson('/api/admin/asignaturas', ['nombre' => 'Inglés', 'codigo' => 'ING'])->assertCreated();

    // Un usuario eliminado no inicia sesión y su correo queda libre
    comoAdmin()->deleteJson("/api/admin/usuarios/{$this->docente->id}")->assertOk();
    expect(User::withTrashed()->find($this->docente->id)->trashed())->toBeTrue();
    $this->postJson('/api/login', ['email' => 'docente@demo.cl', 'password' => 'demo123'])->assertUnauthorized();
    comoAdmin()->postJson('/api/admin/usuarios', ['name' => 'Nueva', 'email' => 'docente@demo.cl', 'password' => 'clave123'])
        ->assertCreated();

    // Un rol eliminado se recupera al crearlo con el mismo nombre
    $rol = comoAdmin()->postJson('/api/admin/roles', ['name' => 'Convivencia', 'permisos' => ['cursos.ver']])->json();
    comoAdmin()->deleteJson("/api/admin/roles/{$rol['id']}")->assertOk();
    expect(Role::withTrashed()->find($rol['id'])->trashed())->toBeTrue();
    comoAdmin()->postJson('/api/admin/roles', ['name' => 'Convivencia', 'permisos' => []])
        ->assertCreated()->assertJsonPath('id', $rol['id']);
});

test('crea usuarios con permisos y RUN normalizado', function () {
    comoAdmin()->postJson('/api/admin/usuarios', [
        'name' => 'Nuevo Docente', 'email' => 'NUEVO@demo.cl', 'run' => '11.111.111-1',
        'password' => 'clave123', 'permisos' => [Permisos::DICTAR_CLASES, 'cursos.ver'],
    ])->assertCreated()
        ->assertJsonPath('email', 'nuevo@demo.cl')
        ->assertJsonPath('run', '11111111-1')
        ->assertJsonPath('permisos', [Permisos::DICTAR_CLASES, 'cursos.ver']);

    comoAdmin()->postJson('/api/admin/usuarios', [
        'name' => 'Otro', 'email' => 'otro@demo.cl', 'run' => '11111111-2', 'password' => 'clave123',
    ])->assertUnprocessable()->assertJsonValidationErrors('run');

    comoAdmin()->getJson('/api/admin/usuarios?permiso='.Permisos::DICTAR_CLASES)->assertOk()->assertJsonCount(2);
});

test('con los permisos de Docentes gestiona docentes pero no da permisos', function () {
    $token = tokenDe(usuarioCon(Permisos::crud('docentes'), 'dir@demo.cl'));

    // Solo ve docentes, aunque pida todos los usuarios
    $this->withToken($token)->getJson('/api/admin/usuarios')->assertOk()->assertJsonCount(1);

    $this->withToken($token)->postJson('/api/admin/usuarios', [
        'name' => 'Profe', 'email' => 'profe@demo.cl', 'password' => 'clave123', 'permisos' => ['usuarios.editar'],
    ])->assertCreated()->assertJsonPath('permisos', [Permisos::DICTAR_CLASES]);

    $this->withToken($token)->putJson("/api/admin/usuarios/{$this->admin->id}", [
        'name' => 'Admin', 'email' => 'admin@demo.cl', 'bloqueado' => true,
    ])->assertForbidden();
});

test('protege al propio administrador y a docentes con asignaciones', function () {
    comoAdmin()->putJson("/api/admin/usuarios/{$this->admin->id}", [
        'name' => 'Admin', 'email' => 'admin@demo.cl', 'permisos' => ['cursos.ver'],
    ])->assertUnprocessable();

    comoAdmin()->deleteJson("/api/admin/usuarios/{$this->admin->id}")->assertUnprocessable();

    $curso = Curso::create(['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'A']);
    $asignatura = Asignatura::create(['nombre' => 'Inglés', 'codigo' => 'ING']);
    Asignacion::create(['curso_id' => $curso->id, 'asignatura_id' => $asignatura->id, 'docente_id' => $this->docente->id]);

    comoAdmin()->deleteJson("/api/admin/usuarios/{$this->docente->id}")->assertStatus(409);
    comoAdmin()->deleteJson("/api/admin/asignaturas/{$asignatura->id}")->assertStatus(409);
    comoAdmin()->putJson("/api/admin/usuarios/{$this->docente->id}", [
        'name' => 'Docente', 'email' => 'docente@demo.cl', 'permisos' => [],
    ])->assertUnprocessable();
});

test('bloquear un usuario revoca sus sesiones', function () {
    $token = tokenDe($this->docente);

    comoAdmin()->putJson("/api/admin/usuarios/{$this->docente->id}", [
        'name' => 'Docente', 'email' => 'docente@demo.cl', 'bloqueado' => true, 'permisos' => [Permisos::DICTAR_CLASES],
    ])->assertOk()->assertJsonPath('bloqueado', true);

    expect($this->docente->tokens()->count())->toBe(0);
    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
});

test('los roles base vienen de la migración con permisos CRUD', function () {
    $roles = collect(comoAdmin()->getJson('/api/admin/roles')->assertOk()->json())->keyBy('name');

    expect($roles['Docente']['permisos'])->toEqual([Permisos::DICTAR_CLASES])
        ->and($roles['Dirección']['permisos'])->toContain('cursos.eliminar', 'asignaciones.crear')
        ->and($roles['Dirección']['permisos'])->not->toContain('usuarios.ver')
        ->and($roles['Administrador']['permisos'])->toContain('usuarios.editar', 'roles.eliminar');
});

test('un usuario recibe los permisos de sus roles', function () {
    $rol = comoAdmin()->postJson('/api/admin/roles', ['name' => 'Jefe UTP', 'permisos' => Permisos::crud('cursos')])
        ->assertCreated()->json();

    $nuevo = comoAdmin()->postJson('/api/admin/usuarios', [
        'name' => 'Jefa', 'email' => 'jefa@demo.cl', 'password' => 'demo123',
        'roles' => ['Jefe UTP', 'Docente'], 'permisos' => [],
    ])->assertCreated()
        ->assertJsonPath('roles', ['Jefe UTP', 'Docente'])
        ->assertJsonPath('permisos', [])
        ->json();

    expect($nuevo['permisos_efectivos'])->toEqualCanonicalizing([...Permisos::crud('cursos'), Permisos::DICTAR_CLASES]);

    // Entra a cursos gracias al rol y figura como docente por el rol Docente
    $this->withToken(tokenDe(User::find($nuevo['id'])))->getJson('/api/admin/cursos')->assertOk();
    comoAdmin()->getJson('/api/admin/usuarios?permiso='.Permisos::DICTAR_CLASES)
        ->assertJsonFragment(['email' => 'jefa@demo.cl']);

    // Un rol en uso no se elimina
    comoAdmin()->deleteJson("/api/admin/roles/{$rol['id']}")->assertStatus(409);
});

test('no se pierde la edición de usuarios por cambiar roles', function () {
    $this->admin->syncPermissions([]);
    $this->admin->assignRole('Administrador');
    $rol = Role::findByName('Administrador', 'web');

    comoAdmin()->putJson("/api/admin/roles/{$rol->id}", ['name' => 'Administrador', 'permisos' => ['usuarios.ver']])
        ->assertUnprocessable();

    comoAdmin()->putJson("/api/admin/usuarios/{$this->admin->id}", [
        'name' => 'Admin', 'email' => 'admin@demo.cl', 'roles' => [], 'permisos' => [],
    ])->assertUnprocessable();
});
