<?php

namespace App\Support;

class DescripcionHtml
{
    public static function limpiar(?string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p[style],div[style],span[style],br,b,strong,i,em,u,s,strike,ul,ol,li,blockquote,sub,sup');
        $config->set('CSS.AllowedProperties', ['text-align', 'font-weight', 'font-style', 'text-decoration']);
        $config->set('Cache.DefinitionImpl', null);

        return (new \HTMLPurifier($config))->purify($html ?? '');
    }

    public static function guardar(string $valor): string
    {
        return strip_tags($valor) === $valor ? mb_strtoupper($valor) : self::limpiar($valor);
    }

    public static function mostrar(?string $valor): string
    {
        $valor = $valor ?? '';

        return strip_tags($valor) === $valor ? nl2br(e($valor)) : self::limpiar($valor);
    }
}
