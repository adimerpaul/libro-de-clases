<?php

namespace App\Support;

/**
 * Catálogos del Programa de Integración Escolar (Decreto 170/2009 y Decreto 83/2015).
 * El tipo de NEE (transitoria o permanente) sale del diagnóstico; no se elige aparte.
 */
class Pie
{
    public const TRANSITORIA = 'transitoria';

    public const PERMANENTE = 'permanente';

    /** Diagnósticos del Decreto 170 => tipo de NEE. */
    public const DIAGNOSTICOS = [
        'Dificultades específicas del aprendizaje (DEA)' => self::TRANSITORIA,
        'Trastorno específico del lenguaje (TEL)' => self::TRANSITORIA,
        'Trastorno por déficit atencional (TDA/TDAH)' => self::TRANSITORIA,
        'Funcionamiento intelectual limítrofe (FIL)' => self::TRANSITORIA,
        'Discapacidad intelectual' => self::PERMANENTE,
        'Trastorno del espectro autista (TEA)' => self::PERMANENTE,
        'Discapacidad visual' => self::PERMANENTE,
        'Discapacidad auditiva' => self::PERMANENTE,
        'Disfasia severa' => self::PERMANENTE,
        'Discapacidad múltiple' => self::PERMANENTE,
        'Sordoceguera' => self::PERMANENTE,
    ];

    /** Estados del caso: los dos primeros cuentan como "en el programa". */
    public const ESTADOS = [
        'en_evaluacion' => 'En evaluación',
        'activo' => 'Activo',
        'egresado' => 'Egresado / alta',
    ];

    public const VIGENTES = ['en_evaluacion', 'activo'];

    /** Apoyos que recibe el estudiante (plan de apoyo). */
    public const APOYOS = [
        'Co-docencia en aula común',
        'Aula de recursos',
        'DUA en aula',
        'PACI',
        'Adecuaciones de acceso',
        'Evaluación diversificada',
        'Apoyo fonoaudiológico',
        'Apoyo psicológico',
        'Terapia ocupacional',
        'Apoyo kinesiológico',
    ];

    /** Profesionales del equipo de aula y de apoyo (evaluación multidimensional). */
    public const ESPECIALIDADES = [
        'Educador(a) diferencial',
        'Psicopedagogo(a)',
        'Psicólogo(a)',
        'Fonoaudiólogo(a)',
        'Terapeuta ocupacional',
        'Kinesiólogo(a)',
        'Médico(a)',
        'Neurólogo(a)',
        'Trabajador(a) social',
        'Intérprete de lengua de señas',
    ];

    public const TIPOS_DOCUMENTO = [
        'Formulario de evaluación integral (FUDEI)',
        'Informe de evaluación diagnóstica',
        'Certificado o informe médico',
        'Autorización del apoderado',
        'PACI',
        'Informe de estado de avance',
        'Evidencia / fotografía',
        'Otro',
    ];

    /** Registros de la bitácora de seguimiento (intervención, co-docencia, familia y equipo). */
    public const TIPOS_SEGUIMIENTO = [
        'Sesión en aula de recursos',
        'Co-docencia',
        'Observación de aula',
        'Entrevista familiar',
        'Reunión de equipo',
        'Evaluación / reevaluación',
        'Derivación externa',
    ];

    /** Áreas de intervención del aula de recursos. */
    public const AREAS = [
        'Lectoescritura',
        'Cálculo',
        'Funciones ejecutivas',
        'Autorregulación',
        'Comunicación y lenguaje',
        'Habilidades adaptativas',
    ];

    /** Archivos del expediente: documentos e imágenes (máx. 15 MB). */
    public const EXTENSIONES = 'pdf,doc,docx,odt,xls,xlsx,jpg,jpeg,png,webp,heic';

    public static function tipoNee(string $diagnostico): ?string
    {
        return self::DIAGNOSTICOS[$diagnostico] ?? null;
    }

    public static function catalogos(): array
    {
        return [
            'diagnosticos' => array_map(fn ($d, $t) => ['label' => $d, 'tipo' => $t], array_keys(self::DIAGNOSTICOS), self::DIAGNOSTICOS),
            'estados' => array_map(fn ($v, $l) => ['value' => $v, 'label' => $l], array_keys(self::ESTADOS), self::ESTADOS),
            'apoyos' => self::APOYOS,
            'especialidades' => self::ESPECIALIDADES,
            'tipos_documento' => self::TIPOS_DOCUMENTO,
            'tipos_seguimiento' => self::TIPOS_SEGUIMIENTO,
            'areas' => self::AREAS,
        ];
    }
}
