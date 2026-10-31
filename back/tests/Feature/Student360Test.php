<?php

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\MarcaRfid;
use App\Models\Matricula;
use App\Models\PieCaso;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();
    config(['services.rfid.hora_inicio' => '08:00', 'services.rfid.tolerancia' => 10]);

    $this->docente = User::create(['name' => 'Marcela', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->utp = User::create(['name' => 'UTP', 'email' => 'utp@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::VER_STUDENT360);
    $this->pie = User::create(['name' => 'PIE', 'email' => 'pie@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo([Permisos::VER_STUDENT360, 'pie.ver']);

    $anio = $this->anio = (int) now()->year;
    $this->cursoA = Curso::create(['anio' => $anio, 'nivel' => '2° Medio', 'letra' => 'A']);
    $this->cursoB = Curso::create(['anio' => $anio, 'nivel' => '3° Medio', 'letra' => 'A']);
    $this->mat = Asignatura::create(['nombre' => 'Matemática', 'codigo' => 'MAT']);
    $this->len = Asignatura::create(['nombre' => 'Lengua', 'codigo' => 'LEN']);
    Asignacion::create(['curso_id' => $this->cursoA->id, 'asignatura_id' => $this->mat->id, 'docente_id' => $this->docente->id]);
    $otroDocente = User::create(['name' => 'Jorge', 'email' => 'jorge@demo.cl', 'password' => 'demo123'])->givePermissionTo(Permisos::DICTAR_CLASES);
    Asignacion::create(['curso_id' => $this->cursoB->id, 'asignatura_id' => $this->len->id, 'docente_id' => $otroDocente->id]);

    $nuevo = function ($curso, $n, $ap, $pie = false) use ($anio) {
        $e = Estudiante::create(['run' => sprintf('%08d-%d', 10000000 + $n, $n % 10), 'nombres' => 'Ana', 'apellido_paterno' => $ap,
            'apellido_materno' => 'Rojas', 'fecha_nacimiento' => '2010-03-01', 'sexo' => 'F', 'pie' => $pie]);
        Matricula::create(['estudiante_id' => $e->id, 'curso_id' => $curso->id, 'numero_lista' => $n, 'fecha_matricula' => "{$anio}-03-01", 'estado' => 'activa']);

        return $e;
    };
    $this->ana = $nuevo($this->cursoA, 1, 'Araya', true);
    $this->bea = $nuevo($this->cursoA, 2, 'Soto');
    $this->carla = $nuevo($this->cursoB, 1, 'Muñoz');

    // 10 días hábiles de marzo: Ana viene todos (2 atrasos), Bea 8 → 80 %
    $dias = collect(range(2, 13))->map(fn ($d) => now()->setDate($anio, 3, $d))->reject->isWeekend()->take(10)->values();
    foreach ($dias as $i => $dia) {
        $marcar = fn ($e, $h) => MarcaRfid::create(['codigo' => 'X', 'estudiante_id' => $e->id, 'curso_id' => $this->cursoA->id,
            'lector' => 'Test', 'tipo' => 'entrada', 'marcado_en' => $dia->copy()->setTimeFromTimeString($h)]);
        $marcar($this->ana, $i < 2 ? '08:30' : '07:50');
        if ($i < 8) {
            $marcar($this->bea, '07:45');
        }
    }

    PieCaso::create(['estudiante_id' => $this->ana->id, 'anio' => $anio, 'estado' => 'activo',
        'diagnostico' => 'Trastorno específico del lenguaje (TEL)', 'tipo_nee' => 'transitoria', 'fecha_ingreso' => "{$anio}-03-05"]);
});

it('calcula asistencia, atrasos y alertas por estudiante y filtra por curso', function () {
    $res = $this->actingAs($this->utp, 'sanctum')->getJson("/api/student360?curso_id={$this->cursoA->id}")
        ->assertOk()
        ->assertJsonCount(2, 'estudiantes')
        ->assertJsonPath('estudiantes.0.asistencia.porcentaje', 100)
        ->assertJsonPath('estudiantes.0.asistencia.atrasos', 2)
        ->assertJsonPath('estudiantes.1.asistencia.porcentaje', 80)
        ->assertJsonPath('estudiantes.1.alertas.0.nivel', 'critica')
        ->assertJsonPath('resumen.asistencia', 90)
        ->assertJsonPath('resumen.criticos', 1)
        ->json();

    expect($res['resumen']['tramos']['bajo_85'])->toBe(1);
});

it('filtra por asignatura: solo los cursos que la tienen, con su docente', function () {
    $this->actingAs($this->utp, 'sanctum')->getJson("/api/student360?asignatura_id={$this->len->id}")
        ->assertOk()
        ->assertJsonCount(1, 'estudiantes')
        ->assertJsonPath('estudiantes.0.apellido_paterno', 'Muñoz');

    $this->actingAs($this->utp, 'sanctum')->getJson('/api/student360/filtros')
        ->assertOk()->assertJsonCount(2, 'cursos')->assertJsonCount(2, 'asignaturas');
});

it('al docente solo le muestra estudiantes de su carga', function () {
    $this->actingAs($this->docente, 'sanctum')->getJson('/api/student360')
        ->assertOk()->assertJsonCount(2, 'estudiantes');

    $this->actingAs($this->docente, 'sanctum')->getJson("/api/student360/{$this->carla->id}")->assertNotFound();
});

it('el detalle PIE solo se muestra con pie.ver', function () {
    $this->actingAs($this->utp, 'sanctum')->getJson("/api/student360/{$this->ana->id}")
        ->assertOk()
        ->assertJsonPath('pie.restringido', true)
        ->assertJsonPath('pie.tiene', true)
        ->assertJsonMissingPath('pie.caso')
        ->assertJsonPath('asistencia.porcentaje', 100)
        ->assertJsonPath('mensual.0.mes', 3)
        ->assertJsonPath('asignaturas.0.asignatura.nombre', 'Matemática');

    $this->actingAs($this->pie, 'sanctum')->getJson("/api/student360/{$this->ana->id}")
        ->assertOk()->assertJsonPath('pie.caso.tipo_nee', 'transitoria');
});

it('sin permiso no entra', function () {
    $otro = User::create(['name' => 'X', 'email' => 'x@demo.cl', 'password' => 'demo123']);
    $this->actingAs($otro, 'sanctum')->getJson('/api/student360')->assertForbidden();
});
