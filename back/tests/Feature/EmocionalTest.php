<?php

use App\Models\AlertaBienestar;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\DiagnosticoSocioemocional;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\User;
use App\Support\Permisos;
use App\Support\Socioemocional;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->convivencia = User::create(['name' => 'Fernanda', 'email' => 'convivencia@demo.cl', 'password' => 'demo123'])->assignRole('Convivencia');
    $this->docente = User::create(['name' => 'Marcela', 'email' => 'docente@demo.cl', 'password' => 'demo123'])->assignRole('Docente');

    $this->curso = Curso::create(['anio' => (int) now()->year, 'nivel' => '7° Básico', 'letra' => 'A']);
    $this->ajeno = Curso::create(['anio' => (int) now()->year, 'nivel' => '2° Medio', 'letra' => 'B']);
    $asignatura = Asignatura::create(['nombre' => 'Orientación', 'codigo' => 'ORI']);
    Asignacion::create(['curso_id' => $this->curso->id, 'asignatura_id' => $asignatura->id, 'docente_id' => $this->docente->id]);

    $this->est = Estudiante::create(['run' => '11111111-1', 'nombres' => 'Martina', 'apellido_paterno' => 'Soto', 'apellido_materno' => 'Rojas']);
    $this->otro = Estudiante::create(['run' => '22222222-2', 'nombres' => 'Javier', 'apellido_paterno' => 'Vargas', 'apellido_materno' => 'Mora']);
    Matricula::create(['estudiante_id' => $this->est->id, 'curso_id' => $this->curso->id, 'numero_lista' => 1, 'fecha_matricula' => now(), 'estado' => 'activa']);
    Matricula::create(['estudiante_id' => $this->otro->id, 'curso_id' => $this->ajeno->id, 'numero_lista' => 1, 'fecha_matricula' => now(), 'estado' => 'activa']);
});

function comoEmocional(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

test('los tramos y las dimensiones DIA se calculan en el catálogo', function () {
    expect(Socioemocional::tramo('Kínder'))->toBe('basica_inicial')
        ->and(Socioemocional::tramo('4° Básico'))->toBe('basica_inicial')
        ->and(Socioemocional::tramo('5° Básico'))->toBe('basica_superior')
        ->and(Socioemocional::tramo('1° Medio'))->toBe('media')
        ->and(Socioemocional::dimensiones(['autoconciencia' => 80, 'autorregulacion' => 70, 'relaciones' => 90, 'conciencia_social' => null, 'decisiones' => null]))
        ->toBe(['personal' => 75, 'comunitario' => 90, 'ciudadano' => null]);
});

test('convivencia registra sesiones; los participantes salen de la nómina', function () {
    $api = comoEmocional($this->convivencia);
    $id = $api->postJson('/api/emocional/sesiones', [
        'curso_id' => $this->curso->id, 'titulo' => 'Círculo de diálogo', 'tipo' => 'Círculo de diálogo',
        'competencia' => 'relaciones', 'fecha' => now()->toDateString(), 'estado' => 'realizada',
    ])->assertCreated()->assertJsonPath('participantes', 1)->json('id');

    $api->getJson('/api/emocional/sesiones?competencia=relaciones')->assertJsonCount(1);
    $api->getJson('/api/emocional/sesiones?tramo=media')->assertJsonCount(0);
    $api->getJson('/api/emocional/plan')->assertJsonPath('celdas.relaciones.basica_superior.sesiones', 1)->assertJsonPath('cobertura', 7);
    $api->deleteJson("/api/emocional/sesiones/{$id}")->assertOk();
});

test('el seguimiento exige matrícula y lleva bitácora de intervenciones', function () {
    $api = comoEmocional($this->convivencia);
    $base = ['tipo' => 'bienestar', 'intensidad' => 'media', 'estado' => 'seguimiento', 'fecha' => now()->toDateString(), 'descripcion' => 'Ánimo bajo sostenido'];

    $sinMatricula = Estudiante::create(['run' => '33333333-3', 'nombres' => 'Ana', 'apellido_paterno' => 'Díaz']);
    $api->postJson('/api/emocional/alertas', $base + ['estudiante_id' => $sinMatricula->id])->assertStatus(422);
    $api->postJson('/api/emocional/alertas', ['tipo' => 'desregulacion'] + $base + ['estudiante_id' => $this->est->id])->assertStatus(422); // falta la fase

    $id = $api->postJson('/api/emocional/alertas', $base + ['estudiante_id' => $this->est->id])->assertCreated()
        ->assertJsonPath('curso.nombre', '7° Básico A')->json('id');
    $api->postJson("/api/emocional/alertas/{$id}/intervenciones", ['fecha' => now()->toDateString(), 'tipo' => 'sesion_individual', 'descripcion' => 'Técnicas de regulación'])
        ->assertCreated()->assertJsonCount(1, 'intervenciones');
    $api->putJson("/api/emocional/alertas/{$id}", ['estado' => 'cerrada'] + $base)->assertStatus(422); // falta fecha y motivo de cierre

    $api->getJson('/api/emocional/alertas?estado=abiertas')->assertJsonCount(1);
    $api->getJson("/api/emocional/estudiantes/{$this->est->id}")->assertOk()->assertJsonCount(1, 'alertas')->assertJsonPath('curso.tramo', 'basica_superior');
});

test('el docente activa el protocolo DEC solo en sus cursos y no ve los seguimientos', function () {
    $api = comoEmocional($this->docente);

    $api->getJson('/api/emocional/protocolo/estudiantes')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $this->est->id);
    $api->postJson('/api/emocional/protocolo', ['estudiante_id' => $this->est->id, 'intensidad' => 'baja', 'descripcion' => 'Crisis en clase'])->assertCreated();
    $api->postJson('/api/emocional/protocolo', ['estudiante_id' => $this->otro->id, 'intensidad' => 'baja', 'descripcion' => 'X'])->assertForbidden();
    $api->getJson('/api/emocional/protocolo/mis-reportes')->assertJsonCount(1)->assertJsonPath('0.fase', 'contencion')->assertJsonMissingPath('0.descripcion');

    $api->getJson('/api/emocional/alertas')->assertForbidden();
    $api->getJson('/api/emocional/resumen')->assertForbidden();

    $alerta = AlertaBienestar::first();
    expect($alerta->tipo)->toBe('desregulacion')->and($alerta->reportado_por_id)->toBe($this->docente->id);
});

test('el diagnóstico DIA se guarda por periodo y resume por curso', function () {
    $api = comoEmocional($this->convivencia);
    $fila = ['estudiante_id' => $this->est->id, 'autoconciencia' => 80, 'autorregulacion' => 70, 'conciencia_social' => 90, 'relaciones' => 70, 'decisiones' => 60];

    $api->putJson('/api/emocional/diagnosticos', ['curso_id' => $this->curso->id, 'periodo' => 'inicial', 'fecha' => now()->toDateString(), 'filas' => [$fila]])
        ->assertOk()->assertJsonPath('filas.0.diagnostico.dimensiones.personal', 75)->assertJsonPath('filas.0.diagnostico.promedio', 74);
    $final = ['estudiante_id' => $this->est->id, 'autoconciencia' => 90, 'autorregulacion' => 80, 'conciencia_social' => 100, 'relaciones' => 80, 'decisiones' => 70];
    $api->putJson('/api/emocional/diagnosticos', ['curso_id' => $this->curso->id, 'periodo' => 'final', 'fecha' => now()->toDateString(), 'filas' => [$final]])->assertOk();
    $api->putJson('/api/emocional/diagnosticos', ['curso_id' => $this->curso->id, 'periodo' => 'inicial', 'fecha' => now()->toDateString(), 'filas' => [['estudiante_id' => $this->otro->id, 'autoconciencia' => 50]]])->assertStatus(422);

    $api->getJson('/api/emocional/diagnosticos/resultados')->assertOk()
        ->assertJsonPath('cursos.0.periodo', 'final')->assertJsonPath('cursos.0.tendencia', 'sube')->assertJsonPath('global.ciudadano', 70);

    // Fila vacía = borrar
    $api->putJson('/api/emocional/diagnosticos', ['curso_id' => $this->curso->id, 'periodo' => 'inicial', 'fecha' => now()->toDateString(), 'filas' => [['estudiante_id' => $this->est->id]]])->assertOk();
    expect(DiagnosticoSocioemocional::count())->toBe(1);

    $api->getJson('/api/emocional/resumen')->assertOk()->assertJsonPath('diagnosticados', 1)->assertJsonPath('competencias.0.logro', 90);
});

test('un docente con clases.dictar directo (sin rol) también activa el protocolo en sus cursos', function () {
    $docente = User::create(['name' => 'Valentina', 'email' => 'vrojas@demo.cl', 'password' => 'demo123'])->givePermissionTo(Permisos::DICTAR_CLASES);
    Asignacion::create(['curso_id' => $this->ajeno->id, 'asignatura_id' => Asignatura::first()->id, 'docente_id' => $docente->id]);

    $api = comoEmocional($docente);
    $api->getJson('/api/emocional/catalogos')->assertOk()->assertJsonCount(1, 'cursos')->assertJsonPath('responsables', []);
    $api->postJson('/api/emocional/protocolo', ['estudiante_id' => $this->otro->id, 'intensidad' => 'media', 'descripcion' => 'Desregulación en recreo'])->assertCreated();
    $api->postJson('/api/emocional/protocolo', ['estudiante_id' => $this->est->id, 'intensidad' => 'media', 'descripcion' => 'X'])->assertForbidden();
    $api->getJson('/api/emocional/estudiantes')->assertForbidden();
});
