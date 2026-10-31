<?php

namespace Database\Seeders;

use App\Models\AlertaBienestar;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\DiagnosticoSocioemocional;
use App\Models\Matricula;
use App\Models\ObjetivoAprendizaje;
use App\Models\SesionSocioemocional;
use App\Models\User;
use App\Support\Socioemocional;
use Illuminate\Database\Seeder;

/**
 * Educación emocional DEMO (datos ficticios): convivencia@demo.cl (rol Convivencia),
 * sesiones por curso, alertas de bienestar con su bitácora y diagnóstico tipo DIA
 * (inicial en todos los cursos, intermedio en la mitad). Determinístico; se omite si ya hay sesiones.
 */
class EmocionalSeeder extends Seeder
{
    /** [título, tipo, competencia, estado, semanas atrás] — se reparten entre los cursos */
    private const SESIONES = [
        ['¿Cómo me siento hoy? (bitácora)', 'Bitácora emocional', 'autoconciencia', 'permanente', 30],
        ['Técnicas de respiración y pausa', 'Taller', 'autorregulacion', 'realizada', 5],
        ['Círculo de diálogo emocional', 'Círculo de diálogo', 'relaciones', 'realizada', 4],
        ['Ruta Sana Mente: Emociones', 'Ruta Sana Mente', 'autoconciencia', 'en_curso', 1],
        ['Proyecto: Cuidado colectivo del aula', 'Proyecto de aula', 'conciencia_social', 'en_curso', 6],
        ['Hábitos saludables y descanso', 'Charla', 'decisiones', 'realizada', 8],
        ['Resolución pacífica de conflictos', 'Taller', 'relaciones', 'planificada', -1],
        ['Proyecto de vida y bienestar', 'Taller', 'decisiones', 'planificada', -2],
    ];

    /** [tipo, intensidad, estado, fase, descripción, intervenciones [[días atrás, tipo, texto]]] */
    private const ALERTAS = [
        ['desregulacion', 'media', 'acompanamiento', 'intervencion', 'Crisis de llanto y rabia en clase tras un conflicto con compañeros.', [
            [6, 'contencion', 'Contención en aula · Protocolo DEC activado · Apoderada contactada'],
            [5, 'sesion_individual', 'Sesión individual con psicóloga · Técnicas de regulación · Acuerdo de seguimiento semanal'],
        ]],
        ['bienestar', 'baja', 'seguimiento', null, 'Ánimo bajo y aislamiento en recreos reportado por el profesor jefe.', [
            [12, 'entrevista', 'Entrevista con el estudiante y profesor jefe'],
            [7, 'contacto_familia', 'Llamado a la familia: se acuerdan rutinas de descanso'],
            [2, 'sesion_individual', 'Tercera sesión de acompañamiento'],
        ]],
        ['riesgo', 'alta', 'derivacion', null, 'Expresiones de desesperanza en una actividad escrita.', [
            [3, 'entrevista', 'Entrevista de primera acogida con psicóloga'],
            [2, 'derivacion', 'Derivación a CESFAM con consentimiento de la familia'],
        ]],
        ['bienestar', 'baja', 'estable', null, 'Dificultades de adaptación tras cambio de curso.', [
            [20, 'entrevista', 'Entrevista inicial'], [9, 'evaluacion', 'Evolución favorable: participa en trabajos grupales'],
        ]],
        ['desregulacion', 'baja', 'cerrada', 'seguimiento', 'Episodio de desregulación al inicio de una evaluación.', [
            [30, 'contencion', 'Contención inicial y espacio seguro'], [15, 'evaluacion', 'Evaluación de evolución: cierre de alerta'],
        ]],
    ];

    /** OA DEMO de Orientación (ui3 "Vinculación con Orientación"); no son el texto oficial del Mineduc. */
    private const OA_ORIENTACION = [
        ['ORI-1B-01', '1° Básico', 'Identificar emociones propias y ajenas y formas de expresarlas.', 'Crecimiento personal'],
        ['ORI-3B-01', '3° Básico', 'Aceptar emociones y practicar estrategias de manejo emocional.', 'Crecimiento personal'],
        ['ORI-5B-01', '5° Básico', 'Manifestar empatía y promover relaciones positivas.', 'Relaciones interpersonales'],
        ['ORI-7B-01', '7° Básico', 'Desarrollar habilidades de resolución de conflictos.', 'Relaciones interpersonales'],
        ['ORI-8B-01', '8° Básico', 'Reconocer y valorar la diversidad y el cuidado colectivo.', 'Participación y pertenencia'],
        ['ORI-1M-01', '1° Medio', 'Autocuidado, bienestar y toma de decisiones responsables.', 'Bienestar y autocuidado'],
        ['ORI-2M-01', '2° Medio', 'Gestionar emociones en contextos de estrés y presión académica.', 'Bienestar y autocuidado'],
    ];

    public function run(): void
    {
        if (SesionSocioemocional::exists()) {
            return;
        }

        $anio = (int) now()->year;
        $fernanda = User::firstOrCreate(['email' => 'convivencia@demo.cl'], ['name' => 'Fernanda Riquelme', 'password' => 'demo123']);
        $fernanda->syncRoles(['Convivencia']);

        $cursos = Curso::ordenar(Curso::where('anio', $anio)->whereHas('matriculas', fn ($q) => $q->activas())->get())->values();
        if ($cursos->isEmpty()) {
            return;
        }
        $oaOrientacion = $this->oaOrientacion();

        // Sesiones: cada curso recibe 3 actividades del catálogo, rotando
        foreach ($cursos as $c => $curso) {
            foreach ([0, 1, 2] as $k) {
                [$titulo, $tipo, $competencia, $estado, $semanas] = self::SESIONES[($c * 3 + $k) % count(self::SESIONES)];
                $fecha = now()->subWeeks($semanas)->startOfWeek()->addDays(($c + $k) % 5);
                SesionSocioemocional::create([
                    'curso_id' => $curso->id,
                    'responsable_id' => $fernanda->id,
                    'objetivo_aprendizaje_id' => $oaOrientacion->firstWhere('nivel', $curso->nivel)?->id,
                    'titulo' => $titulo,
                    'tipo' => $tipo,
                    'competencia' => $competencia,
                    'fecha' => $fecha->toDateString(),
                    'fecha_fin' => $estado === 'permanente' ? null : ($estado === 'en_curso' ? $fecha->copy()->addWeeks(3)->toDateString() : null),
                    'estado' => $estado,
                    'participantes' => Matricula::activas()->where('curso_id', $curso->id)->count(),
                    'descripcion' => 'Actividad DEMO del Plan Socioemocional: '.Socioemocional::PLAN[$competencia][Socioemocional::tramo($curso->nivel)].'.',
                    'evidencias' => $estado === 'realizada' ? 'Registro fotográfico y acta de la sesión (demo).' : null,
                ]);
            }
        }

        // Alertas: un estudiante de cada uno de los primeros cursos
        foreach (self::ALERTAS as $i => [$tipo, $intensidad, $estado, $fase, $descripcion, $intervenciones]) {
            $curso = $cursos[$i % $cursos->count()];
            $matricula = Matricula::activas()->where('curso_id', $curso->id)->orderBy('numero_lista')->skip(2 + $i)->first();
            if (! $matricula) {
                continue;
            }
            $inicio = today()->subDays($intervenciones[0][0] + 1);
            $alerta = AlertaBienestar::create([
                'estudiante_id' => $matricula->estudiante_id,
                'anio' => $anio,
                'tipo' => $tipo, 'intensidad' => $intensidad, 'estado' => $estado, 'fase' => $fase,
                'fecha' => $inicio->toDateString(),
                'descripcion' => $descripcion,
                'lugar' => $tipo === 'desregulacion' ? "Sala {$curso->nombre}" : null,
                'reportado_por_id' => $curso->profesor_jefe_id ?? $fernanda->id,
                'responsable_id' => $fernanda->id,
                'equipo' => 'Psicóloga + Profesor(a) jefe',
                'familia_contactada' => true,
                'proxima_revision' => $estado === 'cerrada' ? null : today()->addDays(7 - $i)->toDateString(),
                'derivacion' => $estado === 'derivacion' ? 'CESFAM (Estrategia de Salud Mental MINEDUC-MINSAL)' : null,
                'fecha_cierre' => $estado === 'cerrada' ? today()->subDays($intervenciones[1][0])->toDateString() : null,
                'motivo_cierre' => $estado === 'cerrada' ? 'Evolución favorable' : null,
            ]);
            foreach ($intervenciones as [$dias, $tipoInt, $texto]) {
                $alerta->intervenciones()->create(['fecha' => today()->subDays($dias)->toDateString(), 'tipo' => $tipoInt, 'descripcion' => $texto, 'autor_id' => $fernanda->id]);
            }
        }

        $this->diagnosticos($cursos, $fernanda);
    }

    private function oaOrientacion()
    {
        $asignaturaId = Asignatura::where('codigo', 'ORI')->value('id');
        if (! $asignaturaId) {
            return collect();
        }
        foreach (self::OA_ORIENTACION as [$codigo, $nivel, $texto, $unidad]) {
            ObjetivoAprendizaje::firstOrCreate(['codigo' => $codigo], [
                'nivel' => $nivel, 'asignatura_id' => $asignaturaId, 'unidad' => $unidad, 'texto' => $texto,
                'habilidades' => ['Autoconocimiento', 'Empatía'], 'version_curricular' => 'demo', 'es_demo' => true,
            ]);
        }

        return ObjetivoAprendizaje::where('asignatura_id', $asignaturaId)->get();
    }

    private function diagnosticos($cursos, User $fernanda): void
    {
        $anio = (int) now()->year;
        // Diagnóstico DIA: inicial en marzo para todos; intermedio en julio para la mitad de los cursos (con mejora)
        $competencias = array_keys(Socioemocional::COMPETENCIAS);
        foreach ($cursos as $c => $curso) {
            $nomina = Matricula::activas()->where('curso_id', $curso->id)->pluck('estudiante_id');
            foreach (['inicial' => [3, 0], 'intermedio' => [7, 6]] as $periodo => [$mes, $mejora]) {
                if ($periodo === 'intermedio' && ($c % 2 || now()->month < 7)) {
                    continue;
                }
                foreach ($nomina as $estudianteId) {
                    $puntajes = [];
                    foreach ($competencias as $k => $comp) {
                        $puntajes[$comp] = min(100, 55 + ($estudianteId * 13 + $k * 7 + $c * 5) % 36 + $mejora + ($k === 4 ? -6 : 0));
                    }
                    DiagnosticoSocioemocional::create($puntajes + [
                        'estudiante_id' => $estudianteId, 'curso_id' => $curso->id, 'anio' => $anio, 'periodo' => $periodo,
                        'fecha' => sprintf('%d-%02d-15', $anio, $mes), 'aplicado_por_id' => $fernanda->id,
                    ]);
                }
            }
        }
    }
}
