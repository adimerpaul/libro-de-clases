<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use App\Models\PieCaso;
use App\Models\User;
use App\Support\Pie;
use Illuminate\Database\Seeder;

/**
 * Casos PIE FICTICIOS del año actual para los estudiantes marcados "pie" por EstudiantesSeeder,
 * con equipo, apoyos y algunos registros de bitácora. Determinístico; se omite si ya hay casos.
 */
class PieSeeder extends Seeder
{
    private const BARRERAS = [
        'Comprensión de consignas extensas y organización de la tarea.',
        'Acceso a información escrita y velocidad lectora.',
        'Planificación, memoria de trabajo y autorregulación en tareas largas.',
        'Comunicación oral y participación en trabajos grupales.',
    ];

    public function run(): void
    {
        if (PieCaso::exists()) {
            return;
        }

        $anio = (int) now()->year;
        $responsable = User::where('email', 'pie@demo.cl')->first();
        $diagnosticos = array_keys(Pie::DIAGNOSTICOS);

        $estudiantes = Estudiante::where('pie', true)
            ->whereHas('matriculas', fn ($m) => $m->activas()->whereHas('curso', fn ($c) => $c->where('anio', $anio)))
            ->orderBy('id')->get();

        foreach ($estudiantes as $i => $estudiante) {
            $diagnostico = $diagnosticos[($i * 5) % count($diagnosticos)];
            $caso = PieCaso::create([
                'estudiante_id' => $estudiante->id,
                'anio' => $anio,
                'estado' => $i % 6 === 5 ? 'en_evaluacion' : 'activo',
                'diagnostico' => $diagnostico,
                'tipo_nee' => Pie::tipoNee($diagnostico),
                'fecha_ingreso' => "{$anio}-03-".sprintf('%02d', 4 + $i % 20),
                'fecha_evaluacion' => "{$anio}-03-".sprintf('%02d', 1 + $i % 20),
                'fecha_reevaluacion' => ($anio + 1).'-03-15',
                'responsable_id' => $responsable?->id,
                'equipo' => [
                    ['especialidad' => 'Educador(a) diferencial', 'nombre' => 'Valentina Torres'],
                    ['especialidad' => $i % 2 ? 'Fonoaudiólogo(a)' : 'Psicólogo(a)', 'nombre' => $i % 2 ? 'Rodrigo Pino (DEMO)' : 'Camila Vera (DEMO)'],
                ],
                'apoyos' => array_slice(Pie::APOYOS, $i % 3, 3),
                'horas_semanales' => 3 + $i % 5,
                'fortalezas' => 'Buena disposición al trabajo con apoyo visual y en pequeño grupo.',
                'barreras' => self::BARRERAS[$i % count(self::BARRERAS)],
                'necesidades_apoyo' => 'Instrucciones segmentadas, apoyos visuales y tiempo adicional en evaluaciones.',
                'observaciones' => 'Caso de demostración con datos ficticios.',
            ]);

            $caso->seguimientos()->createMany([
                ['fecha' => "{$anio}-04-10", 'tipo' => 'Entrevista familiar', 'descripcion' => 'Entrevista de ingreso y antecedentes con el apoderado.', 'acuerdos' => 'Rutina de lectura diaria de 15 minutos en el hogar.', 'autor_id' => $responsable?->id],
                ['fecha' => "{$anio}-05-08", 'tipo' => 'Sesión en aula de recursos', 'area' => Pie::AREAS[$i % count(Pie::AREAS)], 'duracion_minutos' => 45, 'descripcion' => 'Sesión focalizada en pequeño grupo.', 'autor_id' => $responsable?->id],
            ]);
        }
    }
}
