<?php

namespace App\Support;

use App\Models\ObjetivoAprendizaje;

/**
 * Genera un BORRADOR de preguntas a partir de un OA usando plantillas fijas
 * (sin IA, mismo resultado para la misma entrada). El docente siempre revisa y
 * edita antes de aplicar el instrumento.
 */
class GeneradorInstrumento
{
    public const PUNTAJES = ['seleccion' => 2, 'verdadero_falso' => 1, 'desarrollo' => 4];

    /**
     * @param  string  $mezcla  mixto | seleccion | verdadero_falso | desarrollo
     * @return array<int, array<string, mixed>>
     */
    public static function generar(ObjetivoAprendizaje $oa, int $cantidad, string $mezcla = 'mixto'): array
    {
        $banco = [
            'seleccion' => self::seleccion($oa),
            'verdadero_falso' => self::verdaderoFalso($oa),
            'desarrollo' => self::desarrollo($oa),
        ];

        // Mixto: dos de selección por cada una de V/F y de desarrollo
        $secuencia = $mezcla === 'mixto'
            ? ['seleccion', 'seleccion', 'verdadero_falso', 'desarrollo']
            : [$mezcla];

        $usados = array_fill_keys(array_keys($banco), 0);
        $preguntas = [];

        for ($i = 0; $i < $cantidad; $i++) {
            $tipo = $secuencia[$i % count($secuencia)];
            $plantillas = $banco[$tipo];
            $p = $plantillas[$usados[$tipo] % count($plantillas)];
            $usados[$tipo]++;

            $preguntas[] = ['tipo' => $tipo, 'puntaje' => self::PUNTAJES[$tipo]] + $p
                + ['alternativas' => null, 'respuesta_correcta' => null, 'criterios' => null];
        }

        // La alternativa correcta rota de posición para no quedar siempre en la A
        foreach ($preguntas as $i => &$p) {
            if ($p['tipo'] === 'seleccion') {
                $correcta = $p['alternativas'][0];
                $resto = array_slice($p['alternativas'], 1);
                $pos = $i % 4;
                array_splice($resto, $pos, 0, [$correcta]);
                $p['alternativas'] = $resto;
                $p['respuesta_correcta'] = (string) $pos;
            }
        }

        return $preguntas;
    }

    /** Selección múltiple: la primera alternativa es la correcta (luego se rota). */
    private static function seleccion(ObjetivoAprendizaje $oa): array
    {
        $unidad = $oa->unidad ?: 'la unidad';
        $habilidad = mb_strtolower($oa->habilidades[0] ?? 'aplicar');

        return [
            [
                'enunciado' => "¿Cuál de las siguientes situaciones representa mejor el aprendizaje trabajado en {$unidad}?",
                'alternativas' => [
                    'Aplicar el concepto en un caso contextualizado y justificarlo',
                    'Memorizar una definición sin usarla',
                    'Copiar un ejemplo sin analizarlo',
                    'Responder sin explicar el procedimiento',
                ],
            ],
            [
                'enunciado' => "¿Qué evidencia mostraría un logro adecuado del objetivo {$oa->codigo}?",
                'alternativas' => [
                    'Explicar y aplicar lo aprendido apoyándose en evidencia',
                    'Repetir palabras clave sin relación entre ellas',
                    'Entregar solo el resultado final',
                    'Omitir los pasos del razonamiento',
                ],
            ],
            [
                'enunciado' => '¿Qué estrategia ayuda más a verificar una respuesta?',
                'alternativas' => [
                    'Revisar el procedimiento y contrastarlo con la evidencia',
                    'Entregar sin revisar',
                    'Eliminar los datos que no calzan',
                    'Cambiar la respuesta al azar',
                ],
            ],
            [
                'enunciado' => "Para {$habilidad} correctamente en {$unidad}, lo más importante es:",
                'alternativas' => [
                    'Usar los conceptos de la unidad y justificar cada paso',
                    'Responder lo más rápido posible',
                    'Usar palabras difíciles aunque no correspondan',
                    'Copiar la respuesta de un compañero',
                ],
            ],
            [
                'enunciado' => '¿Qué criterio distingue una respuesta de mayor calidad?',
                'alternativas' => [
                    'Coherencia, uso de evidencia y procedimiento claro',
                    'Mayor extensión aunque no se relacione con la pregunta',
                    'Uso de colores y dibujos',
                    'Rapidez en la entrega',
                ],
            ],
        ];
    }

    private static function verdaderoFalso(ObjetivoAprendizaje $oa): array
    {
        $unidad = $oa->unidad ?: 'la unidad';

        return [
            ['enunciado' => "Los aprendizajes de {$unidad} se pueden aplicar a situaciones de la vida cotidiana.", 'respuesta_correcta' => 'V'],
            ['enunciado' => 'Para justificar una respuesta basta con escribir el resultado final.', 'respuesta_correcta' => 'F'],
            ['enunciado' => 'Revisar el procedimiento permite detectar y corregir errores.', 'respuesta_correcta' => 'V'],
            ['enunciado' => "Lo trabajado en {$unidad} no tiene relación con otras asignaturas.", 'respuesta_correcta' => 'F'],
        ];
    }

    private static function desarrollo(ObjetivoAprendizaje $oa): array
    {
        $unidad = $oa->unidad ?: 'la unidad';

        return [
            [
                'enunciado' => "Explica con tus palabras el aprendizaje central de {$unidad} y entrega un ejemplo.",
                'criterios' => 'Explica el concepto con precisión (2 pts) · Entrega un ejemplo pertinente (2 pts).',
            ],
            [
                'enunciado' => "Resuelve o analiza una situación nueva vinculada al siguiente objetivo: {$oa->texto}",
                'criterios' => 'Plantea un procedimiento adecuado (2 pts) · Justifica el resultado con evidencia (2 pts).',
            ],
            [
                'enunciado' => '¿Qué error frecuente podría ocurrir en este tema y cómo lo corregirías?',
                'criterios' => 'Identifica un error real del tema (2 pts) · Propone una corrección justificada (2 pts).',
            ],
        ];
    }
}
