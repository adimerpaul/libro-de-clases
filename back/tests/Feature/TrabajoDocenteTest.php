<?php

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\ObjetivoAprendizaje;
use App\Models\User;
use App\Support\EscalaNotas;
use App\Support\GeneradorInstrumento;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->docente = User::create(['name' => 'Marcela', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->otro = User::create(['name' => 'Otro', 'email' => 'otro@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);

    $mat = Asignatura::create(['nombre' => 'Matemática', 'codigo' => 'MAT']);
    $len = Asignatura::create(['nombre' => 'Lengua y Literatura', 'codigo' => 'LEN']);
    $curso = Curso::create(['anio' => (int) now()->year, 'nivel' => '2° Medio', 'letra' => 'B']);

    $this->asignacion = Asignacion::create(['curso_id' => $curso->id, 'asignatura_id' => $mat->id, 'docente_id' => $this->docente->id]);
    $this->ajena = Asignacion::create(['curso_id' => $curso->id, 'asignatura_id' => $len->id, 'docente_id' => $this->otro->id]);

    $base = ['version_curricular' => 'demo', 'es_demo' => true];
    $this->oa = ObjetivoAprendizaje::create($base + [
        'codigo' => 'MAT-2M-02', 'nivel' => '2° Medio', 'asignatura_id' => $mat->id,
        'unidad' => 'Álgebra y funciones', 'texto' => 'Modelar situaciones con funciones.', 'habilidades' => ['Modelar'],
    ]);
    $this->oaOtroNivel = ObjetivoAprendizaje::create($base + [
        'codigo' => 'MAT-1M-01', 'nivel' => '1° Medio', 'asignatura_id' => $mat->id, 'texto' => 'Racionales.',
    ]);
});

function comoDocente(?User $user = null)
{
    $user ??= test()->docente;
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

function planificacion(array $cambios = []): array
{
    return $cambios + [
        'asignacion_id' => test()->asignacion->id,
        'objetivo_aprendizaje_id' => test()->oa->id,
        'fecha' => now()->startOfWeek()->toDateString(),
        'hora_inicio' => '08:30',
        'titulo' => 'Funciones lineales',
        'inicio' => 'Pregunta inicial',
        'evidencia' => 'Ticket de salida',
    ];
}

test('la escala chilena da 4,0 en el 60 % y 7,0 en el máximo', function () {
    expect(EscalaNotas::nota(0, 20))->toBe(1.0)
        ->and(EscalaNotas::nota(12, 20))->toBe(4.0)
        ->and(EscalaNotas::nota(20, 20))->toBe(7.0)
        ->and(EscalaNotas::nota(6, 20))->toBe(2.5)
        ->and(EscalaNotas::nota(16, 20))->toBe(5.5)
        ->and(EscalaNotas::nota(10, 20, 50))->toBe(4.0)
        ->and(EscalaNotas::puntajeAprobacion(20))->toBe(12.0)
        ->and(EscalaNotas::tabla(3.5))->toHaveCount(8);
});

test('el generador es determinístico y deja la correcta en distintas posiciones', function () {
    $a = GeneradorInstrumento::generar($this->oa, 8);
    $b = GeneradorInstrumento::generar($this->oa, 8);

    expect($a)->toBe($b)->toHaveCount(8)
        ->and(collect($a)->pluck('tipo')->unique()->sort()->values()->all())->toBe(['desarrollo', 'seleccion', 'verdadero_falso'])
        ->and(collect($a)->where('tipo', 'seleccion')->pluck('respuesta_correcta')->unique()->count())->toBeGreaterThan(1);
});

test('el docente ve solo su carga y los OA de su nivel', function () {
    comoDocente()->getJson('/api/docente/asignaciones')->assertOk()->assertJsonCount(1)
        ->assertJsonPath('0.asignatura.codigo', 'MAT');

    comoDocente()->getJson('/api/docente/oa?nivel=2°+Medio&asignatura_id='.$this->oa->asignatura_id)
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.codigo', 'MAT-2M-02');
});

test('planifica una clase y solo la ve su docente', function () {
    $id = comoDocente()->postJson('/api/docente/planificaciones', planificacion())
        ->assertCreated()->assertJsonPath('objetivo.codigo', 'MAT-2M-02')->json('id');

    $semana = ['desde' => now()->startOfWeek()->toDateString(), 'hasta' => now()->endOfWeek()->toDateString()];
    comoDocente()->getJson('/api/docente/planificaciones?'.http_build_query($semana))->assertOk()->assertJsonCount(1);
    comoDocente($this->otro)->getJson('/api/docente/planificaciones?'.http_build_query($semana))->assertOk()->assertJsonCount(0);

    comoDocente($this->otro)->putJson("/api/docente/planificaciones/{$id}", planificacion())->assertForbidden();
    comoDocente($this->otro)->deleteJson("/api/docente/planificaciones/{$id}")->assertForbidden();
});

test('no planifica en carga ajena ni con OA de otro nivel', function () {
    comoDocente()->postJson('/api/docente/planificaciones', planificacion(['asignacion_id' => $this->ajena->id, 'objetivo_aprendizaje_id' => null]))
        ->assertForbidden();

    comoDocente()->postJson('/api/docente/planificaciones', planificacion(['objetivo_aprendizaje_id' => $this->oaOtroNivel->id]))
        ->assertUnprocessable();
});

test('quien no dicta clases no entra al trabajo docente', function () {
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::crud('cursos'));

    comoDocente($admin)->getJson('/api/docente/asignaciones')->assertForbidden();
});

test('crea un instrumento con preguntas validadas y su escala', function () {
    $preguntas = comoDocente()->postJson('/api/docente/instrumentos/generar', [
        'objetivo_aprendizaje_id' => $this->oa->id, 'cantidad' => 4, 'mezcla' => 'mixto',
    ])->assertOk()->json();

    $instrumento = [
        'asignacion_id' => $this->asignacion->id, 'objetivo_aprendizaje_id' => $this->oa->id,
        'nombre' => 'Control funciones', 'tipo' => 'Control', 'exigencia' => 60, 'estado' => 'listo',
        'preguntas' => $preguntas,
    ];

    $creado = comoDocente()->postJson('/api/docente/instrumentos', $instrumento)->assertCreated()
        ->assertJsonCount(4, 'preguntas')
        ->json();

    // 2 selección (2 pts) + 1 V/F (1 pt) + 1 desarrollo (4 pts) = 9
    expect($creado['puntaje_total'])->toEqual(9)->and($creado['puntaje_aprobacion'])->toEqual(5.4);

    // Selección sin alternativa correcta marcada → error en esa pregunta
    $instrumento['preguntas'][0]['respuesta_correcta'] = null;
    comoDocente()->putJson("/api/docente/instrumentos/{$creado['id']}", $instrumento)
        ->assertUnprocessable()->assertJsonValidationErrors('preguntas.0.respuesta_correcta');

    // Un instrumento listo necesita preguntas
    comoDocente()->postJson('/api/docente/instrumentos', ['preguntas' => []] + $instrumento)
        ->assertUnprocessable()->assertJsonValidationErrors('preguntas');

    comoDocente($this->otro)->getJson("/api/docente/instrumentos/{$creado['id']}")->assertForbidden();

    comoDocente()->getJson('/api/docente/instrumentos/escala?maximo=9&exigencia=60')->assertOk()
        ->assertJsonPath('puntaje_aprobacion', 5.4)
        ->assertJsonCount(10, 'tabla');
});
