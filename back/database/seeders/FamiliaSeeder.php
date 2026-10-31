<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Instrumento;
use App\Models\Matricula;
use App\Models\User;
use App\Models\VinculoApoderado;
use App\Support\EscalaNotas;
use Illuminate\Database\Seeder;

/**
 * Portal de familia DEMO (datos ficticios): apoderado@demo.cl (María Rojas, rol Apoderado)
 * vinculada a dos estudiantes, y notas de dos instrumentos por asignatura en los cursos
 * de sus hijos. Determinístico; se omite si ya hay calificaciones.
 */
class FamiliaSeeder extends Seeder
{
    /** [nombre, tipo, semanas atrás] */
    private const INSTRUMENTOS = [
        ['Prueba de unidad 1', 'Prueba', 6],
        ['Control de avance', 'Control', 2],
    ];

    public function run(): void
    {
        if (Calificacion::exists()) {
            return;
        }

        $anio = (int) now()->year;
        $maria = User::firstOrCreate(['email' => 'apoderado@demo.cl'], ['name' => 'María Rojas', 'password' => 'demo123']);
        $maria->syncRoles(['Apoderado']);

        // Un hijo en 2° Medio B (curso de docente@demo.cl) y otro en 7° Básico A; si no existen, otros cursos con nómina
        $cursos = Curso::ordenar(Curso::where('anio', $anio)->whereHas('matriculas', fn ($q) => $q->activas())->get());
        $elegidos = collect(['2° Medio B', '7° Básico A'])
            ->map(fn ($nombre) => $cursos->first(fn ($c) => $c->nombre === $nombre))->filter()
            ->merge($cursos)->unique('id')->take(2);

        $nombres = [];
        foreach ($elegidos as $curso) {
            // El primero de la lista con un nombre distinto al del otro hijo (los nombres demo se repiten)
            $matricula = Matricula::activas()->where('curso_id', $curso->id)->with('estudiante')->orderBy('numero_lista')->get()
                ->first(fn ($m) => $m->estudiante && ! in_array($m->estudiante->nombre_completo, $nombres, true));
            if ($matricula) {
                $nombres[] = $matricula->estudiante->nombre_completo;
                VinculoApoderado::firstOrCreate(
                    ['user_id' => $maria->id, 'estudiante_id' => $matricula->estudiante_id],
                    ['parentesco' => 'Madre'],
                );
            }
            $this->notasDelCurso($curso);
        }
    }

    /** Dos instrumentos "listos" por asignatura del curso, con nota para toda la nómina. */
    private function notasDelCurso(Curso $curso): void
    {
        $nomina = Matricula::activas()->where('curso_id', $curso->id)->pluck('estudiante_id');
        $asignaciones = Asignacion::with('asignatura')->where('curso_id', $curso->id)->whereNotNull('docente_id')->get();

        foreach ($asignaciones as $a => $asignacion) {
            foreach (self::INSTRUMENTOS as $k => [$nombre, $tipo, $semanas]) {
                $instrumento = Instrumento::create([
                    'docente_id' => $asignacion->docente_id,
                    'asignacion_id' => $asignacion->id,
                    'nombre' => "{$nombre} · {$asignacion->asignatura->nombre}",
                    'tipo' => $tipo,
                    'fecha_aplicacion' => now()->subWeeks($semanas)->startOfWeek()->addDays($a % 5)->toDateString(),
                    'exigencia' => 60,
                    'instrucciones' => 'Instrumento DEMO con notas ficticias.',
                    'estado' => 'listo',
                ]);
                foreach (range(1, 4) as $orden) {
                    $instrumento->preguntas()->create([
                        'orden' => $orden, 'tipo' => 'desarrollo', 'puntaje' => 5,
                        'enunciado' => "Pregunta {$orden} de desarrollo (demo).", 'criterios' => 'Pauta demo.',
                    ]);
                }

                foreach ($nomina as $estudianteId) {
                    // Puntaje 8–20 de 20, fijo por estudiante, asignatura e instrumento
                    $puntaje = 8 + ($estudianteId * 7 + $a * 5 + $k * 3) % 13;
                    $instrumento->calificaciones()->create([
                        'estudiante_id' => $estudianteId,
                        'puntaje' => $puntaje,
                        'nota' => EscalaNotas::nota($puntaje, 20, 60),
                    ]);
                }
            }
        }
    }
}
