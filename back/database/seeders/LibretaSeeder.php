<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Instrumento;
use App\Models\LibretaEntrada;
use App\Models\Matricula;
use App\Models\ObjetivoAprendizaje;
use App\Models\User;
use App\Models\VinculoApoderado;
use App\Support\Libreta;
use Illuminate\Database\Seeder;

/**
 * Libreta DEMO (datos ficticios) para los hijos de apoderado@demo.cl: comunicaciones del
 * profesor jefe (citación, autorización, acuerdo, felicitación) y una justificación de la
 * familia. Se omite si ya hay entradas.
 */
class LibretaSeeder extends Seeder
{
    public function run(): void
    {
        $anio = (int) now()->year;
        $vinculos = VinculoApoderado::whereHas('user', fn ($q) => $q->where('email', 'apoderado@demo.cl'))->get();

        $this->evaluacionesProximas($vinculos, $anio);

        if (LibretaEntrada::exists()) {
            return;
        }

        $respaldo = User::where('email', 'docente@demo.cl')->first();

        foreach ($vinculos as $i => $v) {
            $curso = Matricula::delAnio($v->estudiante_id, $anio)?->curso;
            $jefe = $curso?->profesorJefe ?? $respaldo;
            if (! $jefe) {
                continue;
            }

            $entrada = fn (array $datos, int $diasAtras) => LibretaEntrada::create($datos + [
                'estudiante_id' => $v->estudiante_id,
                'autor_id' => $jefe->id,
                'origen' => Libreta::ESTABLECIMIENTO,
            ])->forceFill(['created_at' => now()->subDays($diasAtras), 'updated_at' => now()->subDays($diasAtras)])->saveQuietly();

            $entrada(['tipo' => 'comunicacion', 'asunto' => 'Reunión de apoderados',
                'mensaje' => 'Les recordamos la reunión de apoderados del curso. Se revisarán resultados del primer semestre y la salida pedagógica.',
                'fecha_evento' => now()->addDays(4)->toDateString(), 'hora_evento' => '19:00', 'requiere_respuesta' => true], 6);
            $entrada(['tipo' => 'citacion', 'asunto' => 'Entrevista con profesor(a) jefe',
                'mensaje' => 'Solicitamos una entrevista para conversar sobre el avance académico y acordar apoyos.',
                'fecha_evento' => now()->addDays(7)->toDateString(), 'hora_evento' => '16:30', 'requiere_respuesta' => true], 3);
            $entrada(['tipo' => 'autorizacion', 'asunto' => 'Salida pedagógica · Museo Regional',
                'mensaje' => 'Salida de 09:00 a 13:30 con traslado en bus contratado por el establecimiento. Requiere autorización del apoderado.',
                'fecha_evento' => now()->addDays(12)->toDateString(), 'requiere_respuesta' => true], 2);
            if ($i === 0) {
                $entrada(['tipo' => 'acuerdo', 'asunto' => 'Compromiso de puntualidad',
                    'mensaje' => 'Estudiante y familia se comprometen a llegar antes de las 08:00. Seguimiento en 14 días con Inspectoría.',
                    'requiere_respuesta' => true], 8);
            }
            $entrada(['tipo' => 'felicitacion', 'asunto' => 'Participación destacada',
                'mensaje' => 'Felicitamos su participación y liderazgo en el proyecto interdisciplinario de Ciencias y Tecnología.'], 10);

            LibretaEntrada::create([
                'estudiante_id' => $v->estudiante_id,
                'autor_id' => $v->user_id,
                'origen' => Libreta::FAMILIA,
                'tipo' => 'justificacion',
                'asunto' => 'Control médico',
                'mensaje' => 'Mi pupilo(a) no asistió por control médico programado. Adjunto certificado en secretaría.',
                'fecha_evento' => now()->subDays(9)->toDateString(),
                'leido_por' => $jefe->id,
                'leido_en' => now()->subDays(8),
            ])->forceFill(['created_at' => now()->subDays(9)])->saveQuietly();
        }
    }

    /**
     * Calendario de la libreta: hasta tres evaluaciones próximas (listas, con fecha y OA del
     * nivel y asignatura) en los cursos de los hijos. Idempotente: no agrega si el curso ya
     * tiene evaluaciones futuras.
     */
    private function evaluacionesProximas($vinculos, int $anio): void
    {
        $cursos = $vinculos->map(fn ($v) => Matricula::delAnio($v->estudiante_id, $anio)?->curso)->filter()->unique('id');

        foreach ($cursos as $curso) {
            $asignaciones = Asignacion::where('curso_id', $curso->id)->with('asignatura')->get();
            if (Instrumento::whereIn('asignacion_id', $asignaciones->pluck('id'))->where('fecha_aplicacion', '>', today())->exists()) {
                continue;
            }

            // Primero las asignaturas con OA en el catálogo para ese nivel
            $conOa = $asignaciones
                ->map(fn ($a) => [$a, ObjetivoAprendizaje::vigentes()->where(['nivel' => $curso->nivel, 'asignatura_id' => $a->asignatura_id])->orderBy('codigo')->first()])
                ->sortByDesc(fn ($par) => (bool) $par[1])
                ->take(3)->values();

            foreach ($conOa as $i => [$asignacion, $oa]) {
                [$nombre, $tipo] = [['Prueba de unidad 2', 'Prueba'], ['Control formativo', 'Control'], ['Guía evaluada', 'Guía evaluada']][$i];
                Instrumento::create([
                    'docente_id' => $asignacion->docente_id,
                    'asignacion_id' => $asignacion->id,
                    'objetivo_aprendizaje_id' => $oa?->id,
                    'nombre' => "{$nombre} · {$asignacion->asignatura->nombre}",
                    'tipo' => $tipo,
                    'fecha_aplicacion' => now()->addDays([5, 12, 20][$i])->toDateString(),
                    'exigencia' => 60,
                    'instrucciones' => 'Evaluación DEMO programada para el calendario de la libreta.',
                    'estado' => 'listo',
                ]);
            }
        }
    }
}
