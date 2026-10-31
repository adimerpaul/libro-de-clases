<?php

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\Instrumento;
use App\Models\ObjetivoAprendizaje;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->utp = User::create(['name' => 'UTP', 'email' => 'utp@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::crud('curriculum'));
    $this->docente = User::create(['name' => 'Docente', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo([Permisos::DICTAR_CLASES, Permisos::de('curriculum', Permisos::VER)]);

    $this->mat = Asignatura::create(['nombre' => 'Matemática', 'codigo' => 'MAT', 'activo' => true]);
});

function comoEnCurriculum(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

function oaForm(array $cambios = []): array
{
    return $cambios + [
        'codigo' => 'mat-2m-01', 'nivel' => '2° Medio', 'asignatura_id' => test()->mat->id, 'unidad' => 'Números',
        'texto' => 'Aplicar números reales para resolver problemas.', 'version_curricular' => '2026',
        'habilidades' => ['Resolver problemas', ''], 'indicadores' => ['Explica con evidencia', 'Justifica procedimientos'],
        'instrumentos' => ['Rúbrica'], 'inicio' => 'Activación', 'vigente_desde' => '2026-01-01',
    ];
}

test('UTP crea, edita y lista OA del catálogo con su estructura pedagógica', function () {
    $oa = comoEnCurriculum($this->utp)->postJson('/api/curriculum/oa', oaForm())->assertCreated()->json();

    expect($oa['codigo'])->toBe('MAT-2M-01')
        ->and($oa['habilidades'])->toBe(['Resolver problemas'])
        ->and($oa['indicadores'])->toHaveCount(2)
        ->and($oa['vigente_desde'])->toBe('2026-01-01');

    // Mismo código en la misma versión: rechazado; en otra versión: permitido
    comoEnCurriculum($this->utp)->postJson('/api/curriculum/oa', oaForm())->assertUnprocessable();
    comoEnCurriculum($this->utp)->postJson('/api/curriculum/oa', oaForm(['version_curricular' => '2027']))->assertCreated();

    comoEnCurriculum($this->utp)->putJson("/api/curriculum/oa/{$oa['id']}", oaForm(['unidad' => 'Álgebra']))
        ->assertOk()->assertJsonPath('unidad', 'Álgebra');

    comoEnCurriculum($this->docente)->getJson('/api/curriculum/oa?nivel=2° Medio&asignatura_id='.$this->mat->id)
        ->assertOk()->assertJsonCount(2);
    comoEnCurriculum($this->docente)->getJson('/api/curriculum/catalogos')
        ->assertOk()->assertJsonPath('resumen.vigentes', 2)->assertJsonPath('niveles.11.total', 2);
});

test('el docente solo consulta el catálogo', function () {
    comoEnCurriculum($this->docente)->postJson('/api/curriculum/oa', oaForm())->assertForbidden();
    comoEnCurriculum($this->docente)->postJson('/api/curriculum/oa/importar', ['oas' => [oaForm()]])->assertForbidden();
});

test('un OA en uso no se elimina: se retira con su vigencia', function () {
    $oa = ObjetivoAprendizaje::create(oaForm(['codigo' => 'MAT-2M-01']));
    $curso = Curso::create(['anio' => 2026, 'nivel' => '2° Medio', 'letra' => 'A']);
    $asig = Asignacion::create(['curso_id' => $curso->id, 'asignatura_id' => $this->mat->id, 'docente_id' => $this->docente->id]);
    Instrumento::create([
        'docente_id' => $this->docente->id, 'asignacion_id' => $asig->id, 'objetivo_aprendizaje_id' => $oa->id,
        'nombre' => 'Prueba', 'tipo' => 'Prueba', 'exigencia' => 60, 'estado' => 'borrador',
    ]);

    comoEnCurriculum($this->utp)->deleteJson("/api/curriculum/oa/{$oa->id}")->assertStatus(409);

    // Retirado (vigente hasta ayer): sale del listado vigente y del selector docente
    comoEnCurriculum($this->utp)->putJson("/api/curriculum/oa/{$oa->id}", oaForm(['vigente_hasta' => now()->subDay()->toDateString(), 'vigente_desde' => '2025-01-01']))->assertOk();
    comoEnCurriculum($this->utp)->getJson('/api/curriculum/oa')->assertJsonCount(0);
    comoEnCurriculum($this->utp)->getJson('/api/curriculum/oa?todos=1')->assertJsonCount(1);
    comoEnCurriculum($this->docente)->getJson('/api/docente/oa')->assertJsonCount(0);

    $libre = ObjetivoAprendizaje::create(oaForm(['codigo' => 'MAT-2M-02']));
    comoEnCurriculum($this->utp)->deleteJson("/api/curriculum/oa/{$libre->id}")->assertOk();
    // Se puede volver a crear (recupera el eliminado)
    comoEnCurriculum($this->utp)->postJson('/api/curriculum/oa', oaForm(['codigo' => 'MAT-2M-02']))
        ->assertCreated()->assertJsonPath('id', $libre->id);
});

test('importa el catálogo con el esquema de ui3, todo o nada', function () {
    $fila = [
        'level' => '1° Medio', 'subject' => 'Matemática', 'oa_code' => 'MA1M-OA01',
        'oa_statement' => 'Calcular operaciones con números racionales.', 'unit_or_axis' => 'Números',
        'skills' => ['Resolver problemas'], 'indicators' => ['Calcula con racionales'],
        'curriculum_version' => 'Bases 2019', 'effective_from' => '2020-01-01',
        'source_url' => 'https://www.curriculumnacional.cl/',
    ];

    // Con una fila mala no se guarda ninguna
    comoEnCurriculum($this->utp)->postJson('/api/curriculum/oa/importar', ['oas' => [$fila, ['oa_code' => 'X', 'subject' => 'Química']]])
        ->assertUnprocessable()->assertJsonCount(1, 'errores');
    expect(ObjetivoAprendizaje::count())->toBe(0);

    comoEnCurriculum($this->utp)->postJson('/api/curriculum/oa/importar', ['oas' => [$fila]])
        ->assertOk()->assertJsonPath('creados', 1);
    comoEnCurriculum($this->utp)->postJson('/api/curriculum/oa/importar', ['oas' => [['oa_statement' => 'Texto nuevo'] + $fila]])
        ->assertOk()->assertJsonPath('actualizados', 1);

    $oa = ObjetivoAprendizaje::first();
    expect($oa->texto)->toBe('Texto nuevo')->and($oa->asignatura_id)->toBe($this->mat->id)->and($oa->indicadores)->toBe(['Calcula con racionales']);
});

test('un OA en uso se retira y se reactiva; el docente no puede', function () {
    $oa = ObjetivoAprendizaje::create(oaForm(['codigo' => 'MAT-2M-09']));
    $curso = Curso::create(['anio' => 2026, 'nivel' => '2° Medio', 'letra' => 'A']);
    $asig = Asignacion::create(['curso_id' => $curso->id, 'asignatura_id' => $this->mat->id, 'docente_id' => $this->docente->id]);
    Instrumento::create([
        'docente_id' => $this->docente->id, 'asignacion_id' => $asig->id, 'objetivo_aprendizaje_id' => $oa->id,
        'nombre' => 'Prueba', 'tipo' => 'Prueba', 'exigencia' => 60, 'estado' => 'borrador',
    ]);

    comoEnCurriculum($this->utp)->getJson('/api/curriculum/oa')->assertJsonPath('0.instrumentos_count', 1)->assertJsonPath('0.recursos_count', 0);
    comoEnCurriculum($this->utp)->deleteJson("/api/curriculum/oa/{$oa->id}")
        ->assertStatus(409)->assertJsonPath('message', fn ($m) => str_contains($m, '1 instrumento(s)'));

    comoEnCurriculum($this->docente)->postJson("/api/curriculum/oa/{$oa->id}/retirar")->assertForbidden();
    comoEnCurriculum($this->utp)->postJson("/api/curriculum/oa/{$oa->id}/retirar")->assertOk();
    comoEnCurriculum($this->utp)->getJson('/api/curriculum/oa')->assertJsonCount(0);

    comoEnCurriculum($this->utp)->postJson("/api/curriculum/oa/{$oa->id}/retirar", ['reactivar' => true])->assertOk();
    comoEnCurriculum($this->utp)->getJson('/api/curriculum/oa')->assertJsonCount(1);
});
