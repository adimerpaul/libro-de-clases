<?php

namespace App\Support;

/**
 * RUN chileno: normaliza a "12345678-9" y valida el dígito verificador (módulo 11).
 */
class Run
{
    public static function normalizar(?string $run): ?string
    {
        if ($run === null) {
            return null;
        }

        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', $run));

        if (strlen($limpio) < 2) {
            return $limpio === '' ? null : $limpio;
        }

        return substr($limpio, 0, -1).'-'.substr($limpio, -1);
    }

    public static function digitoVerificador(string $cuerpo): string
    {
        $suma = 0;
        $factor = 2;

        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $dv = 11 - ($suma % 11);

        return match ($dv) {
            11 => '0',
            10 => 'K',
            default => (string) $dv,
        };
    }

    public static function esValido(?string $run): bool
    {
        $run = self::normalizar($run);

        if (! $run || ! preg_match('/^(\d{7,8})-([\dK])$/', $run, $m)) {
            return false;
        }

        return self::digitoVerificador($m[1]) === $m[2];
    }
}
