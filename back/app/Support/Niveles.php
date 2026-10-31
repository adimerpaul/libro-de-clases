<?php

namespace App\Support;

/**
 * Niveles de enseñanza en Chile, en orden. El orden se usa para listar cursos.
 */
class Niveles
{
    public const TODOS = [
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
    ];

    public const PARVULARIA = 'Educación Parvularia';

    public const BASICA = 'Educación Básica';

    public const MEDIA = 'Enseñanza Media';

    public static function orden(string $nivel): int
    {
        $i = array_search($nivel, self::TODOS, true);

        return $i === false ? 99 : $i;
    }

    /** Etapa del sistema escolar a la que pertenece el nivel (mapa curricular de ui3). */
    public static function etapa(string $nivel): string
    {
        return match (true) {
            in_array($nivel, ['Pre-kínder', 'Kínder'], true) => self::PARVULARIA,
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
