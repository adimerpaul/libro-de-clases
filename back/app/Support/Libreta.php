<?php

namespace App\Support;

/**
 * Catálogo de la Libreta del Estudiante: qué tipos de entrada emite cada lado y
 * qué respuestas admite cada tipo que requiere respuesta de la familia.
 */
class Libreta
{
    public const ESTABLECIMIENTO = 'establecimiento';

    public const FAMILIA = 'familia';

    /** tipo => [etiqueta, origen, respuestas posibles de la familia (vacío = solo confirmar lectura)] */
    public const TIPOS = [
        'comunicacion' => ['Comunicación', null, []],
        'citacion' => ['Citación / entrevista', self::ESTABLECIMIENTO, ['confirmada' => 'Asistiré', 'no_asistira' => 'No podré asistir']],
        'autorizacion' => ['Autorización', self::ESTABLECIMIENTO, ['autoriza' => 'Autorizo', 'no_autoriza' => 'No autorizo']],
        'acuerdo' => ['Acuerdo / compromiso', self::ESTABLECIMIENTO, ['firmada' => 'Firmo el acuerdo']],
        'felicitacion' => ['Felicitación', self::ESTABLECIMIENTO, []],
        'justificacion' => ['Justificación de inasistencia', self::FAMILIA, []],
        'retiro' => ['Aviso de retiro anticipado', self::FAMILIA, []],
    ];

    /** Tipos que puede emitir un lado (la comunicación general la usan ambos). */
    public static function tiposDe(string $origen): array
    {
        return array_keys(array_filter(self::TIPOS, fn ($t) => $t[1] === null || $t[1] === $origen));
    }

    /** Respuestas válidas de la familia para un tipo ([] = no requiere respuesta). */
    public static function respuestas(string $tipo): array
    {
        return self::TIPOS[$tipo][2] ?? [];
    }

    /** Para el front: [{value, label, origen, respuestas: [{value, label}]}] */
    public static function catalogo(): array
    {
        return array_map(fn ($clave, $t) => [
            'value' => $clave,
            'label' => $t[0],
            'origen' => $t[1],
            'respuestas' => array_map(fn ($v, $l) => ['value' => $v, 'label' => $l], array_keys($t[2]), $t[2]),
        ], array_keys(self::TIPOS), self::TIPOS);
    }
}
