<?php

namespace App\Support;

/**
 * Escala de notas chilena 1,0 – 7,0 con exigencia (por defecto 60 %: ese % del
 * puntaje máximo equivale a la nota 4,0). Cálculo determinístico y auditable.
 */
class EscalaNotas
{
    public const NOTA_MIN = 1.0;

    public const NOTA_APROBACION = 4.0;

    public const NOTA_MAX = 7.0;

    public static function nota(float $puntaje, float $maximo, int $exigencia = 60): float
    {
        if ($maximo <= 0) {
            return self::NOTA_MIN;
        }

        $puntaje = max(0, min($puntaje, $maximo));
        $corte = $maximo * $exigencia / 100;

        if ($puntaje < $corte) {
            $nota = self::NOTA_MIN + (self::NOTA_APROBACION - self::NOTA_MIN) * $puntaje / $corte;
        } elseif ($maximo == $corte) {
            $nota = self::NOTA_MAX;
        } else {
            $nota = self::NOTA_APROBACION + (self::NOTA_MAX - self::NOTA_APROBACION) * ($puntaje - $corte) / ($maximo - $corte);
        }

        // Redondeo a un decimal, mitad hacia arriba (3,95 → 4,0)
        return round($nota + 1e-9, 1);
    }

    /**
     * Tabla puntaje → nota. Paso de 0,5 si hay puntajes con medio punto; si no, de 1.
     *
     * @return array<int, array{puntaje: float, nota: float}>
     */
    public static function tabla(float $maximo, int $exigencia = 60): array
    {
        $paso = fmod($maximo, 1.0) != 0.0 ? 0.5 : 1.0;
        $filas = [];

        for ($p = 0.0; $p <= $maximo + 1e-9; $p += $paso) {
            $filas[] = ['puntaje' => round($p, 1), 'nota' => self::nota($p, $maximo, $exigencia)];
        }

        return $filas;
    }

    /** Puntaje mínimo para obtener la nota 4,0. */
    public static function puntajeAprobacion(float $maximo, int $exigencia = 60): float
    {
        return round($maximo * $exigencia / 100, 1);
    }
}
