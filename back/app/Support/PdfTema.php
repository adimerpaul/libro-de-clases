<?php

namespace App\Support;

/**
 * Iconos y colores de los reportes en PDF.
 *
 * dompdf no entiende fuentes de iconos ni emojis, pero sí dibuja SVG embebido
 * como data-uri, así que cada icono es un trazo de 24x24 que sale vectorial —
 * nítido a cualquier tamaño y sin sumar peso de imagen al archivo.
 */
class PdfTema
{
    /** Trazos de 24x24 (Material Symbols). */
    private const ICONOS = [
        'persona' => 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
        'documento' => 'M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z',
        'calendario' => 'M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z',
        'escudo' => 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z',
        'cama' => 'M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z',
        'puerta' => 'M19 19V4h-4V3H5v16H3v2h12V6h2v15h4v-2h-2zm-6-7h-2v-2h2v2z',
        'codigo' => 'M3 11h8V3H3v8zm2-6h4v4H5V5zM3 21h8v-8H3v8zm2-6h4v4H5v-4zM13 3v8h8V3h-8zm6 6h-4V5h4v4zm-6 4h2v2h-2zm2 2h2v2h-2zm-2 2h2v2h-2zm4 0h2v2h-2zm2-2h2v2h-2zm-4 4h2v2h-2zm2-4h2v2h-2zm2 4h2v2h-2z',
        'cruz' => 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z',
        'matraz' => 'M19.8 18.4L14 10.67V6.5l1.35-1.69c.26-.33.03-.81-.39-.81H9.04c-.42 0-.65.48-.39.81L10 6.5v4.17L4.2 18.4c-.49.66-.02 1.6.8 1.6h14c.82 0 1.29-.94.8-1.6z',
        'escaner' => 'M3 5v4h2V5h4V3H5c-1.1 0-2 .9-2 2zm2 10H3v4c0 1.1.9 2 2 2h4v-2H5v-4zm14 4h-4v2h4c1.1 0 2-.9 2-2v-4h-2v4zm0-16h-4v2h4v4h2V5c0-1.1-.9-2-2-2zM7 11h10v2H7z',
        'ambulancia' => 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM8 11H6V9H4v2H2V7h2v2h2V7h2v4z',
        'bisturi' => 'M14.5 5.5l4 4L9 19H5v-4l9.5-9.5zm5.7-1.2l-2-2c-.4-.4-1-.4-1.4 0l-1.6 1.6 3.4 3.4 1.6-1.6c.4-.4.4-1 0-1.4zM3 21h18v2H3z',
        'maletin' => 'M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4zm7 11h-3v3h-2v-3H9v-2h3v-3h2v3h3v2z',
        'aire' => 'M14.5 17c0 1.65-1.35 3-3 3s-3-1.35-3-3h2c0 .55.45 1 1 1s1-.45 1-1-.45-1-1-1H2v-2h9.5c1.65 0 3 1.35 3 3zM19 6.5C19 4.57 17.43 3 15.5 3S12 4.57 12 6.5h2c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5S16.33 8 15.5 8H2v2h13.5c1.93 0 3.5-1.57 3.5-3.5zm-.5 4.5H2v2h16.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5v2c1.93 0 3.5-1.57 3.5-3.5S20.43 11 18.5 11z',
        'pastilla' => 'M4.22 11.29l7.07-7.07a5 5 0 017.07 7.07l-7.07 7.07a5 5 0 01-7.07-7.07zm1.41 1.42a3 3 0 004.24 4.24l2.83-2.83-4.24-4.24-2.83 2.83z',
        'bebe' => 'M12 2a10 10 0 100 20 10 10 0 000-20zM8.5 10a1.25 1.25 0 112.5 0 1.25 1.25 0 01-2.5 0zm4.5 0a1.25 1.25 0 112.5 0 1.25 1.25 0 01-2.5 0zm-1 8c-2.03 0-3.8-1.11-4.75-2.75l1.3-.75C8.75 15.6 10.26 16.5 12 16.5s3.25-.9 3.95-2l1.3.75A5.49 5.49 0 0112 18z',
        'etiqueta' => 'M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z',
        'pago' => 'M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z',
        'reloj' => 'M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z',
        'firma' => 'M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z',
    ];

    /**
     * Icono de cada área. Se busca por coincidencia en el nombre del tipo de
     * producto, de modo que un tipo nuevo cae solo en el grupo que le toca.
     */
    private const ICONOS_TIPO = [
        'INTERNACION' => 'cama',
        'U.T.I' => 'cama',
        'UTI' => 'cama',
        'NEONATOLOGIA' => 'bebe',
        'PESQUIZA NEONATAL' => 'bebe',
        'QUIROFANO' => 'bisturi',
        'PROCEDIMIENTO' => 'bisturi',
        'CIRUGIA' => 'bisturi',
        'RAYOS X' => 'escaner',
        'TOMOGRAFIA' => 'escaner',
        'ECOGRAFIA' => 'escaner',
        'RESONANCIA' => 'escaner',
        'MAMOGRAFIA' => 'escaner',
        'AMBULANCIA' => 'ambulancia',
        'OXIGENO' => 'aire',
        'FARMACIA' => 'pastilla',
        'MEDICAMENTO' => 'pastilla',
        'ENFERMERIA' => 'maletin',
        'SERVICIO MEDICO' => 'maletin',
        'CONSULTA' => 'maletin',
    ];

    /** Colores de Quasar que guardan los tipos de producto, en hexadecimal. */
    private const COLORES = [
        'primary' => '#1976D2', 'secondary' => '#26A69A', 'accent' => '#9C27B0',
        'positive' => '#21BA45', 'negative' => '#C10015', 'info' => '#31CCEC', 'warning' => '#F2C037',
        'red' => '#F44336', 'pink' => '#E91E63', 'purple' => '#9C27B0', 'deep-purple' => '#673AB7',
        'indigo' => '#3F51B5', 'blue' => '#2196F3', 'light-blue' => '#03A9F4', 'cyan' => '#00BCD4',
        'teal' => '#009688', 'green' => '#4CAF50', 'light-green' => '#8BC34A', 'lime' => '#CDDC39',
        'yellow' => '#FFEB3B', 'amber' => '#FFC107', 'orange' => '#FF9800', 'deep-orange' => '#FF5722',
        'brown' => '#795548', 'grey' => '#9E9E9E', 'blue-grey' => '#607D8B',
    ];

    public const AZUL = '#0D47A1';

    /** `<img>` con el icono ya embebido, listo para imprimirse con `{!! !!}`. */
    public static function icono(string $nombre, string $color = self::AZUL, float $tam = 8.5): string
    {
        $path = self::ICONOS[$nombre] ?? self::ICONOS['etiqueta'];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="'.$color.'">'
            .'<path d="'.$path.'"/></svg>';

        return '<img class="ico" src="data:image/svg+xml;base64,'.base64_encode($svg).'"'
            .' style="width:'.$tam.'px;height:'.$tam.'px">';
    }

    /** Nombre del icono que le corresponde a un tipo de producto. */
    public static function iconoDeTipo(?string $tipo, bool $esLaboratorio = false): string
    {
        $tipo = mb_strtoupper((string) $tipo);

        foreach (self::ICONOS_TIPO as $palabra => $icono) {
            if (str_contains($tipo, $palabra)) {
                return $icono;
            }
        }

        // Los tipos de laboratorio son muchos y muy específicos (hematología,
        // serologías, perfil tiroideo…): todos comparten el matraz.
        return $esLaboratorio ? 'matraz' : 'etiqueta';
    }

    /**
     * Icono del PDF para un tipo de producto padre. El padre guarda un ícono de
     * Material (el que se ve en pantalla); si no tiene equivalente aquí, se
     * busca por el nombre como con los tipos.
     */
    public static function iconoDePadre(?string $icono, ?string $nombre, bool $esLaboratorio = false): string
    {
        $equivalentes = [
            'science' => 'matraz', 'biotech' => 'matraz', 'bloodtype' => 'matraz',
            'medication' => 'pastilla', 'local_pharmacy' => 'pastilla', 'vaccines' => 'pastilla',
            'medical_services' => 'maletin', 'health_and_safety' => 'maletin', 'healing' => 'maletin',
            'local_hospital' => 'cama', 'emergency' => 'cama',
            'monitor_heart' => 'escaner', 'visibility' => 'escaner',
            'airport_shuttle' => 'ambulancia', 'child_care' => 'bebe',
        ];

        return $equivalentes[(string) $icono] ?? self::iconoDeTipo($nombre, $esLaboratorio);
    }

    /** Convierte el color guardado (nombre de Quasar o hex) en hexadecimal. */
    public static function color(?string $color, string $defecto = self::AZUL): string
    {
        $color = trim((string) $color);

        if (preg_match('/^#[0-9a-f]{3,6}$/i', $color)) {
            return $color;
        }

        return self::COLORES[mb_strtolower($color)] ?? $defecto;
    }

    /** Importe en letras para el pie de la proforma. */
    public static function enLetras(float $monto): string
    {
        $entero = (int) floor(abs($monto));
        $centavos = (int) round((abs($monto) - $entero) * 100);

        if (! class_exists(\NumberFormatter::class)) {
            return number_format($monto, 2, ',', '.').' Bs.';
        }

        $letras = (new \NumberFormatter('es', \NumberFormatter::SPELLOUT))->format($entero);

        return mb_strtoupper($letras).' '.str_pad((string) $centavos, 2, '0', STR_PAD_LEFT).'/100 BOLIVIANOS';
    }
}
