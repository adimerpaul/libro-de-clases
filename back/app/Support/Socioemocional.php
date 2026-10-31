<?php

namespace App\Support;

/**
 * Catálogos del módulo Educación Emocional / Socioemocional (ui3/educacion-emocional.html).
 * Las 5 competencias, los tramos del plan, las dimensiones tipo DIA y los estados de
 * alertas, protocolo DEC y sesiones viven solo aquí.
 */
class Socioemocional
{
    /** clave => [nombre, color] */
    public const COMPETENCIAS = [
        'autoconciencia' => ['Autoconciencia', '#7c3aed'],
        'autorregulacion' => ['Autorregulación', '#0891b2'],
        'conciencia_social' => ['Conciencia social', '#db2777'],
        'relaciones' => ['Habilidades de relación', '#2563eb'],
        'decisiones' => ['Decisiones responsables', '#059669'],
    ];

    /** Tramos del plan (por nivel). clave => [nombre, orden mínimo, orden máximo] de App\Support\Niveles::orden */
    public const TRAMOS = [
        'basica_inicial' => ['Parvularia – 4° Básico', 0, 5],
        'basica_superior' => ['5° – 8° Básico', 6, 9],
        'media' => ['1° – 4° Medio', 10, 13],
    ];

    /** Plan socioemocional: competencia => tramo => foco (ui3 "Estructura por competencia y nivel"). */
    public const PLAN = [
        'autoconciencia' => [
            'basica_inicial' => 'Identificar emociones básicas (alegría, pena, rabia, miedo)',
            'basica_superior' => 'Reconocer emociones complejas y sus causas',
            'media' => 'Reflexión sobre identidad emocional y valores',
        ],
        'autorregulacion' => [
            'basica_inicial' => 'Estrategias simples (respirar, pedir ayuda)',
            'basica_superior' => 'Técnicas de manejo emocional y pausa',
            'media' => 'Autogestión en contextos de estrés y presión',
        ],
        'conciencia_social' => [
            'basica_inicial' => 'Empatía básica, reconocer emociones ajenas',
            'basica_superior' => 'Perspectiva del otro, inclusión',
            'media' => 'Empatía compleja, diversidad y cuidado colectivo',
        ],
        'relaciones' => [
            'basica_inicial' => 'Buen trato, turnos, compartir',
            'basica_superior' => 'Resolución de conflictos, colaboración',
            'media' => 'Comunicación asertiva, liderazgo positivo',
        ],
        'decisiones' => [
            'basica_inicial' => 'Autocuidado básico (higiene, descanso)',
            'basica_superior' => 'Hábitos saludables, prevención de riesgos',
            'media' => 'Proyecto de vida, bienestar y salud mental',
        ],
    ];

    /** Dimensiones del diagnóstico tipo DIA: clave => [nombre, competencias que promedia, color] */
    public const DIMENSIONES = [
        'personal' => ['Aprendizaje socioemocional personal', ['autoconciencia', 'autorregulacion'], '#7c3aed'],
        'comunitario' => ['Aprendizaje socioemocional comunitario', ['relaciones', 'conciencia_social'], '#0284c7'],
        'ciudadano' => ['Aprendizaje socioemocional ciudadano', ['decisiones'], '#059669'],
    ];

    public const PERIODOS = ['inicial' => 'Diagnóstico', 'intermedio' => 'Monitoreo intermedio', 'final' => 'Cierre'];

    public const ESTADOS_SESION = ['planificada' => 'Planificada', 'en_curso' => 'En curso', 'realizada' => 'Realizada', 'permanente' => 'Permanente'];

    public const TIPOS_SESION = ['Taller', 'Círculo de diálogo', 'Bitácora emocional', 'Proyecto de aula', 'Charla', 'Ruta Sana Mente', 'Otro'];

    /** Seguimiento individual (alertas de bienestar) */
    public const TIPOS_ALERTA = ['desregulacion' => 'Desregulación emocional', 'bienestar' => 'Bienestar', 'riesgo' => 'Riesgo'];

    public const ESTADOS_ALERTA = [
        'activa' => 'Activa', 'acompanamiento' => 'En acompañamiento', 'seguimiento' => 'Seguimiento',
        'derivacion' => 'Derivación a red', 'estable' => 'Estable', 'cerrada' => 'Cerrada',
    ];

    /** Estados en que la alerta sigue abierta */
    public const ABIERTAS = ['activa', 'acompanamiento', 'seguimiento', 'derivacion', 'estable'];

    public const INTENSIDADES = [
        'baja' => 'Baja (contención en aula)',
        'media' => 'Media (requiere apoyo especializado)',
        'alta' => 'Alta (riesgo, derivación inmediata)',
    ];

    /** Fases del protocolo DEC */
    public const FASES = [
        'prevencion' => ['Prevención', 'Clima de aula, rutinas predecibles, estrategias preventivas socioemocionales, PAEC cuando corresponda.'],
        'contencion' => ['Contención inicial', 'Adulto de referencia, espacio seguro, lenguaje calmado, no escalar. Registro inmediato.'],
        'intervencion' => ['Intervención', 'Equipo de apoyo (psicólogo/a, orientador, convivencia). Técnicas de regulación. Contacto a familia.'],
        'seguimiento' => ['Seguimiento y cierre', 'Plan de acompañamiento, evaluación de evolución, posible derivación a red de salud, aprendizaje para el equipo.'],
    ];

    public const TIPOS_INTERVENCION = [
        'contencion' => 'Contención en aula', 'sesion_individual' => 'Sesión individual', 'contacto_familia' => 'Contacto con la familia',
        'entrevista' => 'Entrevista', 'derivacion' => 'Derivación a red de salud', 'evaluacion' => 'Evaluación de evolución', 'otro' => 'Otro',
    ];

    /** Tramo del plan para un nivel ("7° Básico" → basica_superior). */
    public static function tramo(string $nivel): string
    {
        $orden = Niveles::orden($nivel);
        foreach (self::TRAMOS as $clave => [, $min, $max]) {
            if ($orden >= $min && $orden <= $max) {
                return $clave;
            }
        }

        return 'media';
    }

    /**
     * Logro por dimensión DIA (0–100, entero) desde los puntajes por competencia;
     * null si no hay datos de esa dimensión.
     */
    public static function dimensiones(array $puntajes): array
    {
        $out = [];
        foreach (self::DIMENSIONES as $clave => [, $competencias]) {
            $valores = array_filter(array_map(fn ($c) => $puntajes[$c] ?? null, $competencias), fn ($v) => $v !== null);
            $out[$clave] = $valores ? (int) round(array_sum($valores) / count($valores)) : null;
        }

        return $out;
    }

    /** Promedio entero de valores no nulos (0–100), o null. */
    public static function promedio(iterable $valores): ?int
    {
        $v = collect($valores)->filter(fn ($x) => $x !== null);

        return $v->isEmpty() ? null : (int) round($v->avg());
    }

    public static function catalogos(): array
    {
        $lista = fn (array $a) => collect($a)->map(fn ($label, $value) => ['value' => $value, 'label' => is_array($label) ? $label[0] : $label])->values();

        return [
            'competencias' => collect(self::COMPETENCIAS)->map(fn ($c, $k) => ['value' => $k, 'label' => $c[0], 'color' => $c[1]])->values(),
            'tramos' => $lista(self::TRAMOS),
            'plan' => self::PLAN,
            'dimensiones' => collect(self::DIMENSIONES)->map(fn ($d, $k) => ['value' => $k, 'label' => $d[0], 'competencias' => $d[1], 'color' => $d[2]])->values(),
            'periodos' => $lista(self::PERIODOS),
            'estados_sesion' => $lista(self::ESTADOS_SESION),
            'tipos_sesion' => self::TIPOS_SESION,
            'tipos_alerta' => $lista(self::TIPOS_ALERTA),
            'estados_alerta' => $lista(self::ESTADOS_ALERTA),
            'intensidades' => $lista(self::INTENSIDADES),
            'fases' => collect(self::FASES)->map(fn ($f, $k) => ['value' => $k, 'label' => $f[0], 'desc' => $f[1]])->values(),
            'tipos_intervencion' => $lista(self::TIPOS_INTERVENCION),
        ];
    }
}
