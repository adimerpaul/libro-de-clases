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

    public static function orden(string $nivel): int
    {
        $i = array_search($nivel, self::TODOS, true);

        return $i === false ? 99 : $i;
    }
}
