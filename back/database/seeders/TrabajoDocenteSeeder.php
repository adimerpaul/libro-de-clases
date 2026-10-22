<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Instrumento;
use App\Models\ObjetivoAprendizaje;
use App\Models\Planificacion;
use App\Models\User;
use App\Support\GeneradorInstrumento;
use Illuminate\Database\Seeder;

/**
 * Planificaciones de la semana actual e instrumento de ejemplo para docente@demo.cl
 * (Marcela, Matemática 1° y 2° Medio). Datos ficticios. Se omite si ya tiene planificaciones.
 */
class TrabajoDocenteSeeder extends Seeder
{
    public function run(): void
    {
        $marcela = User::where('email', 'docente@demo.cl')->first();
        if (! $marcela || Planificacion::where('docente_id', $marcela->id)->exists()) {
            return;
        }

        $carga = Asignacion::with(['curso', 'asignatura'])->where('docente_id', $marcela->id)->get()
            ->filter(fn ($a) => $a->curso->anio === (int) now()->year);
        $oa = fn (string $codigo) => ObjetivoAprendizaje::where('codigo', $codigo)->first();
        $lunes = now()->startOfWeek();

        $clases = [
            // [curso, día (0 = lunes), hora, OA, título, evidencia]
            ['2° Medio B', 0, '08:30', 'MAT-2M-02', 'Funciones lineales: tablas y gráficos', 'Ticket de salida'],
            ['2° Medio A', 1, '10:15', 'MAT-2M-02', 'Pendiente como razón de cambio', 'Guía resuelta en parejas'],
            ['1° Medio A', 2, '11:45', 'MAT-1M-02', 'Productos notables con material concreto', 'Organizador gráfico'],
            ['2° Medio B', 3, '08:30', 'MAT-2M-01', 'Potencias y raíces en contexto', 'Resolución de problemas'],
            ['1° Medio B', 4, '09:50', 'MAT-1M-03', 'Gráficos y medidas de tendencia central', 'Informe breve de datos'],
        ];

        foreach ($clases as [$curso, $dia, $hora, $codigo, $titulo, $evidencia]) {
            $asignacion = $carga->first(fn ($a) => $a->curso->nombre === $curso && $a->asignatura->codigo === 'MAT');
            if (! $asignacion) {
                continue;
            }

            Planificacion::create([
                'docente_id' => $marcela->id,
                'asignacion_id' => $asignacion->id,
                'objetivo_aprendizaje_id' => $oa($codigo)?->id,
                'fecha' => $lunes->copy()->addDays($dia)->toDateString(),
                'hora_inicio' => $hora,
                'duracion_minutos' => 90,
                'titulo' => $titulo,
                'inicio' => 'Activación de conocimientos previos con una pregunta problematizadora (10 min).',
                'desarrollo' => 'Modelado docente, trabajo colaborativo en parejas y andamiaje según avance (60 min).',
                'cierre' => 'Ticket de salida y metacognición breve: ¿qué aprendí y qué me costó? (15 min).',
                'evidencia' => $evidencia,
                'adecuaciones' => 'Múltiples formas de representación (tabla, gráfico, explicación oral).',
                'estado' => 'planificada',
            ]);
        }

        // Instrumento de ejemplo generado desde el OA y guardado como borrador
        $asignacion = $carga->first(fn ($a) => $a->curso->nombre === '2° Medio B');
        $objetivo = $oa('MAT-2M-02');
        if ($asignacion && $objetivo) {
            $instrumento = Instrumento::create([
                'docente_id' => $marcela->id,
                'asignacion_id' => $asignacion->id,
                'objetivo_aprendizaje_id' => $objetivo->id,
                'nombre' => 'Control funciones lineales',
                'tipo' => 'Control',
                'fecha_aplicacion' => $lunes->copy()->addDays(4)->toDateString(),
                'exigencia' => 60,
                'instrucciones' => 'Lee cada pregunta con atención. Justifica tus respuestas de desarrollo.',
                'estado' => 'borrador',
            ]);

            foreach (GeneradorInstrumento::generar($objetivo, 8) as $i => $p) {
                $instrumento->preguntas()->create($p + ['orden' => $i + 1]);
            }
        }
    }
}
