<?php

namespace App\Support;

/**
 * Niveles de enseñanza en Chile, en orden. El orden se usa para listar cursos.
 */
class Niveles
{
    public const TODOS = [
        'Sala Cuna',
        'Nivel Medio',
        'Pre-kínder',
        'Kínder',
        '1° Básico',
        '2° Básico',
        '3° Básico',
        '4° Básico',
        '5° Básico',
        '6° Básico',
        '7° Básico',
        '8° Básico',
        '1° Medio',
        '2° Medio',
        '3° Medio',
        '4° Medio',
        '3° Medio TP',
        '4° Medio TP',
    ];

    /** Posición de Pre-kínder en TODOS: orden() la toma como 0 (tramos socioemocionales, edad demo). */
    private const ORIGEN = 2;

    public const PARVULARIA = 'Educación Parvularia';

    public const BASICA = 'Educación Básica';

    public const MEDIA = 'Enseñanza Media';

    /** Bases curriculares Mineduc (agrupación de curriculumnacional.cl) => niveles. */
    public const BASES = [
        'Bases Curriculares de Educación Parvularia' => ['Sala Cuna', 'Nivel Medio', 'Pre-kínder', 'Kínder'],
        'Bases Curriculares de 1° a 6° Básico' => ['1° Básico', '2° Básico', '3° Básico', '4° Básico', '5° Básico', '6° Básico'],
        'Bases Curriculares de 7° Básico a 2° Medio' => ['7° Básico', '8° Básico', '1° Medio', '2° Medio'],
        'Bases Curriculares de 3° y 4° Medio' => ['3° Medio', '4° Medio'],
        'Bases Curriculares de 3° y 4° Medio Técnico Profesional' => ['3° Medio TP', '4° Medio TP'],
    ];

    /**
     * Códigos de nivel del catálogo curricular (tabla grades de curriculum_core) => nivel.
     * FG y HC son planes del mismo curso; NT (Nivel Transición) cubre Pre-kínder y Kínder.
     */
    public const CODIGOS = [
        'SC' => ['Sala Cuna'], 'NM' => ['Nivel Medio'], 'NT' => ['Pre-kínder', 'Kínder'],
        '1B' => ['1° Básico'], '2B' => ['2° Básico'], '3B' => ['3° Básico'], '4B' => ['4° Básico'],
        '5B' => ['5° Básico'], '6B' => ['6° Básico'], '7B' => ['7° Básico'], '8B' => ['8° Básico'],
        '1M' => ['1° Medio'], '2M' => ['2° Medio'],
        '3M_FG' => ['3° Medio'], '3M_HC' => ['3° Medio'], '4M_FG' => ['4° Medio'], '4M_HC' => ['4° Medio'],
        '3M_TP' => ['3° Medio TP'], '4M_TP' => ['4° Medio TP'],
    ];

    /** [['base' => 'Bases Curriculares de 1° a 6° Básico', 'niveles' => [...]], …] */
    public static function porBase(): array
    {
        return array_map(fn ($base, $niveles) => compact('base', 'niveles'), array_keys(self::BASES), self::BASES);
    }

    public static function orden(string $nivel): int
    {
        $i = array_search($nivel, self::TODOS, true);

        return $i === false ? 99 : $i - self::ORIGEN;
    }

    /** Etapa del sistema escolar a la que pertenece el nivel (mapa curricular de ui3). */
    public static function etapa(string $nivel): string
    {
        return match (true) {
            in_array($nivel, self::BASES['Bases Curriculares de Educación Parvularia'], true) => self::PARVULARIA,
            str_ends_with($nivel, 'Básico') => self::BASICA,
            default => self::MEDIA,
        };
    }

    /** [['nivel' => '1° Básico', 'etapa' => 'Educación Básica'], …] */
    public static function conEtapa(): array
    {
        return array_map(fn ($n) => ['nivel' => $n, 'etapa' => self::etapa($n)], self::TODOS);
    }
}
