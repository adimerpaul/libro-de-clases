<?php

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo([...Permisos::crud('cursos'), ...Permisos::crud('estudiantes'), ...Permisos::crud('matriculas')]);

    $this->primeroA = Curso::create(['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'A']);
    $this->primeroB = Curso::create(['anio' => 2026, 'nivel' => '1° Medio', 'letra' => 'B']);
    $this->segundoA27 = Curso::create(['anio' => 2027, 'nivel' => '2° Medio', 'letra' => 'A']);
});

function comoAdminEst()
{
    $token = test()->postJson('/api/login', ['email' => 'admin@demo.cl', 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

function estudiante(array $cambios = []): array
{
    return $cambios + [
        'run' => '11.111.111-1', 'nombres' => 'Sofía', 'apellido_paterno' => 'Rojas', 'apellido_materno' => 'Díaz',
        'fecha_nacimiento' => '2011-05-04', 'sexo' => 'F', 'apoderado_nombre' => 'Patricia Rojas',
    ];
}

test('crea un estudiante y lo matricula en un curso', function () {
    comoAdminEst()->postJson('/api/admin/estudiantes', estudiante(['curso_id' => $this->primeroA->id]))
        ->assertCreated()
        ->assertJsonPath('run', '11111111-1')
        ->assertJsonPath('nombre_completo', 'Rojas Díaz, Sofía')
        ->assertJsonPath('matricula.curso.nombre', '1° Medio A')
        ->assertJsonPath('matricula.numero_lista', 1);

    // RUN repetido o inválido
    comoAdminEst()->postJson('/api/admin/estudiantes', estudiante())->assertUnprocessable()->assertJsonValidationErrors('run');
    comoAdminEst()->postJson('/api/admin/estudiantes', estudiante(['run' => '12345678-K']))->assertUnprocessable()->assertJsonValidationErrors('run');
});

test('una sola matrícula activa por año y cambio de curso dentro del año', function () {
    $e = Estudiante::create(estudiante(['run' => '11111111-1']));

    $m = comoAdminEst()->postJson('/api/admin/matriculas', ['estudiante_id' => $e->id, 'curso_id' => $this->primeroA->id])
        ->assertCreated()->json();

    // Otro curso del mismo año: rechazado
    comoAdminEst()->postJson('/api/admin/matriculas', ['estudiante_id' => $e->id, 'curso_id' => $this->primeroB->id])
        ->assertUnprocessable();

    // Otro año: permitido (es la matrícula de ese año)
    comoAdminEst()->postJson('/api/admin/matriculas', ['estudiante_id' => $e->id, 'curso_id' => $this->segundoA27->id])
        ->assertCreated();

    // Cambio de curso en el mismo año: queda al final de la nueva lista
    comoAdminEst()->putJson("/api/admin/matriculas/{$m['id']}", [
        'curso_id' => $this->primeroB->id, 'fecha_matricula' => '2026-03-01', 'estado' => 'activa',
    ])->assertOk()->assertJsonPath('curso_id', $this->primeroB->id)->assertJsonPath('numero_lista', 1);

    // Cambio a un curso de otro año: rechazado
    comoAdminEst()->putJson("/api/admin/matriculas/{$m['id']}", [
        'curso_id' => $this->segundoA27->id, 'fecha_matricula' => '2026-03-01', 'estado' => 'activa',
    ])->assertUnprocessable();

    // Retiro: guarda fecha y deja de contar como activa
    comoAdminEst()->putJson("/api/admin/matriculas/{$m['id']}", [
        'curso_id' => $this->primeroB->id, 'fecha_matricula' => '2026-03-01', 'estado' => 'retirada', 'motivo_retiro' => 'Cambio de ciudad',
    ])->assertOk()->assertJsonPath('estado', 'retirada')->assertJsonPath('fecha_retiro', now()->toDateString());

    comoAdminEst()->postJson('/api/admin/matriculas', ['estudiante_id' => $e->id, 'curso_id' => $this->primeroA->id])
        ->assertCreated();
});

test('copia la nómina a otro año y renumera alfabéticamente', function () {
    foreach ([['Zúñiga', '11111111-1'], ['Abarca', '12345678-5'], ['Muñoz', '7654321-6']] as $i => [$apellido, $run]) {
        $e = Estudiante::create(estudiante(['run' => $run, 'apellido_paterno' => $apellido]));
        Matricula::create(['estudiante_id' => $e->id, 'curso_id' => $this->primeroA->id, 'numero_lista' => $i + 1, 'fecha_matricula' => '2026-03-01']);
    }

    comoAdminEst()->postJson('/api/admin/matriculas/copiar', [
        'desde_curso_id' => $this->primeroA->id, 'hacia_curso_id' => $this->segundoA27->id,
    ])->assertOk()->assertJsonPath('copiados', 3);

    // Copiar de nuevo no duplica
    comoAdminEst()->postJson('/api/admin/matriculas/copiar', [
        'desde_curso_id' => $this->primeroA->id, 'hacia_curso_id' => $this->segundoA27->id,
    ])->assertOk()->assertJsonPath('copiados', 0)->assertJsonCount(3, 'omitidos');

    // La copia ya numera en orden alfabético
    $nomina = comoAdminEst()->getJson("/api/admin/matriculas?curso_id={$this->segundoA27->id}")->assertOk()->json();
    expect(collect($nomina)->pluck('estudiante.apellido_paterno')->all())->toBe(['Abarca', 'Muñoz', 'Zúñiga']);

    comoAdminEst()->postJson('/api/admin/matriculas/renumerar', ['curso_id' => $this->primeroA->id])->assertOk();
    $nomina = comoAdminEst()->getJson("/api/admin/matriculas?curso_id={$this->primeroA->id}")->json();
    expect(collect($nomina)->map(fn ($m) => $m['numero_lista'].' '.$m['estudiante']['apellido_paterno'])->all())
        ->toBe(['1 Abarca', '2 Muñoz', '3 Zúñiga']);
});

test('copia los cursos de un año al siguiente sin duplicar', function () {
    comoAdminEst()->postJson('/api/admin/cursos/copiar', ['desde_anio' => 2026, 'hacia_anio' => 2027])
        ->assertOk()->assertJsonPath('creados', 2);
    comoAdminEst()->postJson('/api/admin/cursos/copiar', ['desde_anio' => 2026, 'hacia_anio' => 2027])
        ->assertOk()->assertJsonPath('creados', 0);

    expect(Curso::where('anio', 2027)->count())->toBe(3);
});

test('los cursos informan sus estudiantes activos y se exige el permiso', function () {
    $e = Estudiante::create(estudiante());
    Matricula::create(['estudiante_id' => $e->id, 'curso_id' => $this->primeroA->id, 'numero_lista' => 1, 'fecha_matricula' => '2026-03-01']);

    comoAdminEst()->getJson('/api/admin/cursos?anio=2026')->assertOk()
        ->assertJsonFragment(['nombre' => '1° Medio A', 'estudiantes_count' => 1]);

    $docente = User::create(['name' => 'Doc', 'email' => 'doc@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $token = test()->postJson('/api/login', ['email' => 'doc@demo.cl', 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/api/admin/estudiantes')->assertForbidden();
});
