<?php

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Instrumento;
use App\Models\LibretaEntrada;
use App\Models\Matricula;
use App\Models\ObjetivoAprendizaje;
use App\Models\User;
use App\Models\VinculoApoderado;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->docente = User::create(['name' => 'Marcela Soto', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->ajeno = User::create(['name' => 'Otro docente', 'email' => 'otro@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->madre = User::create(['name' => 'María Rojas', 'email' => 'apoderado@demo.cl', 'password' => 'demo123'])->assignRole('Apoderado');

    $this->curso = Curso::create(['anio' => (int) now()->year, 'nivel' => '2° Medio', 'letra' => 'B', 'profesor_jefe_id' => $this->docente->id]);
    $mat = Asignatura::create(['nombre' => 'Matemática', 'codigo' => 'MAT']);
    $asig = Asignacion::create(['curso_id' => $this->curso->id, 'asignatura_id' => $mat->id, 'docente_id' => $this->docente->id]);

    $this->hija = Estudiante::create(['run' => '11111111-1', 'nombres' => 'Antonia', 'apellido_paterno' => 'Rojas', 'apoderado_telefono' => '+56 9 1111 2222']);
    $this->otro = Estudiante::create(['run' => '22222222-2', 'nombres' => 'Luis', 'apellido_paterno' => 'Pérez']);
    foreach ([$this->hija, $this->otro] as $i => $e) {
        Matricula::create(['estudiante_id' => $e->id, 'curso_id' => $this->curso->id, 'numero_lista' => $i + 1, 'fecha_matricula' => now(), 'estado' => 'activa']);
    }
    VinculoApoderado::create(['user_id' => $this->madre->id, 'estudiante_id' => $this->hija->id, 'parentesco' => 'Madre']);

    $oa = ObjetivoAprendizaje::create([
        'codigo' => 'MAT-2M-01', 'nivel' => '2° Medio', 'asignatura_id' => $mat->id, 'texto' => 'Funciones', 'version_curricular' => 'demo',
        'indicadores' => ['Modela con funciones lineales'],
    ]);
    Instrumento::create([
        'docente_id' => $this->docente->id, 'asignacion_id' => $asig->id, 'objetivo_aprendizaje_id' => $oa->id, 'nombre' => 'Prueba funciones',
        'tipo' => 'Prueba', 'exigencia' => 60, 'estado' => 'listo', 'fecha_aplicacion' => now()->addDays(5)->toDateString(),
    ]);
});

function enLibreta(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

test('el apoderado abre la libreta de su hijo con identificación y calendario con criterios', function () {
    enLibreta($this->madre)->getJson('/api/libreta/estudiantes')->assertOk()->assertJsonCount(1)->assertJsonPath('0.hijo', true);

    enLibreta($this->madre)->getJson("/api/libreta/{$this->hija->id}")->assertOk()
        ->assertJsonPath('rol', 'familia')
        ->assertJsonPath('estudiante.contacto_emergencia.telefono', '+56 9 1111 2222')
        ->assertJsonPath('curso.profesor_jefe', 'Marcela Soto')
        ->assertJsonPath('establecimiento.nombre', config('establecimiento.nombre'))
        ->assertJsonPath('calendario.0.estado', 'proxima')
        ->assertJsonPath('calendario.0.oa.indicadores.0', 'Modela con funciones lineales');

    // Otro estudiante: no existe para ella
    enLibreta($this->madre)->getJson("/api/libreta/{$this->otro->id}")->assertNotFound();
    enLibreta($this->madre)->postJson("/api/libreta/{$this->otro->id}/entradas", ['tipo' => 'comunicacion', 'asunto' => 'x', 'mensaje' => 'y'])->assertNotFound();
});

test('comunicación bidireccional con lectura, citación confirmada y justificación', function () {
    // El profesor jefe cita a la familia
    $citacion = enLibreta($this->docente)->postJson("/api/libreta/{$this->hija->id}/entradas", [
        'tipo' => 'citacion', 'asunto' => 'Entrevista profesor jefe', 'fecha_evento' => now()->addDays(3)->toDateString(), 'hora_evento' => '16:30',
    ])->assertCreated()->assertJsonPath('requiere_respuesta', true)->json();

    // El docente no puede emitir tipos de la familia ni la familia los del establecimiento
    enLibreta($this->docente)->postJson("/api/libreta/{$this->hija->id}/entradas", ['tipo' => 'justificacion', 'asunto' => 'x', 'fecha_evento' => now()->toDateString()])->assertUnprocessable();
    enLibreta($this->madre)->postJson("/api/libreta/{$this->hija->id}/entradas", ['tipo' => 'citacion', 'asunto' => 'x', 'fecha_evento' => now()->toDateString(), 'hora_evento' => '10:00'])->assertUnprocessable();

    // Al abrir la libreta, la familia deja leída la citación y la confirma
    enLibreta($this->madre)->getJson("/api/libreta/{$this->hija->id}")->assertJsonPath('entradas.0.leido_por', 'María Rojas');
    enLibreta($this->docente)->postJson("/api/libreta/entradas/{$citacion['id']}/responder", ['respuesta' => 'confirmada'])->assertForbidden();
    enLibreta($this->madre)->postJson("/api/libreta/entradas/{$citacion['id']}/responder", ['respuesta' => 'autoriza'])->assertUnprocessable();
    enLibreta($this->madre)->postJson("/api/libreta/entradas/{$citacion['id']}/responder", ['respuesta' => 'confirmada', 'comentario' => 'Llego 16:30'])
        ->assertOk()->assertJsonPath('respondido_por', 'María Rojas');
    enLibreta($this->madre)->postJson("/api/libreta/entradas/{$citacion['id']}/responder", ['respuesta' => 'no_asistira'])->assertUnprocessable();

    // La familia justifica una inasistencia (fecha obligatoria)
    enLibreta($this->madre)->postJson("/api/libreta/{$this->hija->id}/entradas", ['tipo' => 'justificacion', 'asunto' => 'Control médico'])->assertUnprocessable();
    enLibreta($this->madre)->postJson("/api/libreta/{$this->hija->id}/entradas", [
        'tipo' => 'justificacion', 'asunto' => 'Control médico', 'fecha_evento' => now()->subDay()->toDateString(),
    ])->assertCreated()->assertJsonPath('origen', 'familia');

    // El docente la ve y queda leída por él
    enLibreta($this->docente)->getJson("/api/libreta/{$this->hija->id}")->assertJsonPath('rol', 'establecimiento')
        ->assertJsonPath('entradas.0.tipo', 'justificacion')->assertJsonPath('entradas.0.leido_por', 'Marcela Soto');
    expect(LibretaEntrada::count())->toBe(2);
});

test('un docente sin carga en el curso no abre la libreta; con libreta.ver sí', function () {
    enLibreta($this->ajeno)->getJson("/api/libreta/{$this->hija->id}")->assertNotFound();
    enLibreta($this->ajeno)->getJson('/api/libreta/estudiantes')->assertOk()->assertJsonCount(0);

    $this->ajeno->givePermissionTo(Permisos::VER_LIBRETAS);
    enLibreta($this->ajeno)->getJson("/api/libreta/{$this->hija->id}")->assertOk();
    enLibreta($this->ajeno)->getJson('/api/libreta/estudiantes?q=Luis')->assertOk()->assertJsonCount(1);
});
