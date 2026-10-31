<?php

namespace App\Support;

use App\Models\InstrumentoPregunta;
use App\Models\ObjetivoAprendizaje;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Genera un BORRADOR de preguntas desde un OA con Google Gemini. Solo se envía
 * información curricular (OA, nivel, asignatura, parámetros del docente), nunca
 * datos de estudiantes. La respuesta se valida y normaliza aquí; el docente
 * revisa y edita antes de aplicar. Puntajes y notas no los decide la IA.
 */
class GeneradorIa
{
    public const DIFICULTADES = ['inicial', 'intermedio', 'desafiante'];

    public const PROPOSITOS = ['diagnostica', 'formativa', 'sumativa'];

    /** API Interactions de Gemini: un solo endpoint; el modelo va en el cuerpo. */
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    public static function disponible(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  array{cantidad:int, mezcla:string, dificultad?:string, proposito?:string, criterios?:?string, flexibilizada?:bool, tipo_instrumento?:?string, nivel?:?string}  $op
     * @return array<int, array<string, mixed>>
     */
    public static function generar(ObjetivoAprendizaje $oa, array $op): array
    {
        if (! self::disponible()) {
            throw new RuntimeException('La generación con IA no está configurada (falta GEMINI_API_KEY en el servidor).');
        }

        $payload = [
            'system_instruction' => self::instruccionSistema(),
            'input' => self::prompt($oa, $op),
            'response_mime_type' => 'application/json',
            'response_format' => self::esquema($op['mezcla']),
            'generation_config' => ['temperature' => 0.7],
        ];

        $datos = self::llamar($payload);
        $preguntas = self::normalizar($datos['preguntas'] ?? [], $op['mezcla'], $op['cantidad']);

        if (! $preguntas) {
            throw new RuntimeException('La IA no devolvió preguntas válidas. Intenta nuevamente.');
        }

        return $preguntas;
    }

    /** Prueba el modelo configurado y, si está saturado o no existe, los de respaldo. */
    private static function llamar(array $payload): array
    {
        $modelos = array_values(array_unique(array_filter([
            config('services.gemini.model'),
            ...explode(',', (string) config('services.gemini.respaldo')),
        ])));

        $ultimo = null;
        foreach ($modelos as $modelo) {
            try {
                $res = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                    ->timeout((int) config('services.gemini.timeout', 90))
                    ->post(self::URL, ['model' => trim($modelo)] + $payload);
            } catch (ConnectionException $e) {
                $ultimo = 'sin conexión con el servicio de IA';

                continue;
            }

            if ($res->successful()) {
                $json = self::extraerJson($res->json('steps', []));
                if (is_array($json)) {
                    return $json;
                }
                $ultimo = 'respuesta con formato inválido';

                continue;
            }

            $ultimo = $res->json('error.message') ?: "HTTP {$res->status()}";
            Log::warning('Gemini falló', ['modelo' => $modelo, 'status' => $res->status(), 'error' => $ultimo]);

            // Saturación, cuota o modelo retirado: probar el siguiente. Clave inválida u otro error: cortar.
            if (! in_array($res->status(), [404, 429, 500, 503, 504])) {
                break;
            }
        }

        throw new RuntimeException('No se pudo generar con IA en este momento ('.$ultimo.'). Intenta en unos minutos o usa las plantillas sin IA.');
    }

    /**
     * El texto viene en los pasos "model_output" de la interacción (los "thought" se ignoran).
     * Tolera el JSON envuelto en un bloque de código ```json por si el modelo lo agrega.
     */
    private static function extraerJson(array $pasos): ?array
    {
        $texto = collect($pasos)
            ->where('type', 'model_output')
            ->flatMap(fn ($paso) => $paso['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $texto = trim(preg_replace('/^\s*```(?:json)?\s*|\s*```\s*$/u', '', $texto));
        $json = json_decode($texto, true);

        return is_array($json) ? $json : null;
    }

    private static function instruccionSistema(): string
    {
        return <<<'TXT'
        Eres un docente experto en evaluación educativa del sistema escolar de Chile (Mineduc, Bases Curriculares).
        Redactas ítems de evaluación en español de Chile, claros, sin ambigüedades y adecuados a la edad del nivel.
        Reglas:
        - Cada ítem evalúa el Objetivo de Aprendizaje (OA) entregado y sus indicadores; nada fuera de él.
        - Selección múltiple: 4 alternativas (a lo más 5), una sola correcta, distractores plausibles basados en errores frecuentes, sin "todas las anteriores" ni "ninguna de las anteriores".
        - Verdadero o falso: afirmaciones completamente verdaderas o completamente falsas; equilibra V y F.
        - Desarrollo: consigna concreta y una pauta de corrección con criterios observables y su distribución de puntos.
        - Usa contextos cercanos a estudiantes chilenos. No incluyas nombres de personas reales ni datos personales.
        - No numeres los enunciados ni pongas letras a las alternativas.
        TXT;
    }

    private static function prompt(ObjetivoAprendizaje $oa, array $op): string
    {
        $lista = fn ($v) => is_array($v) ? implode('; ', array_filter($v)) : (string) $v;
        $tipos = [
            'mixto' => 'mezcla equilibrada: aproximadamente la mitad de selección múltiple, un cuarto verdadero o falso y un cuarto de desarrollo',
            'seleccion' => 'solo selección múltiple',
            'verdadero_falso' => 'solo verdadero o falso',
            'desarrollo' => 'solo preguntas de desarrollo',
        ];
        $dificultad = [
            'inicial' => 'inicial: con apoyo, contexto familiar y demanda cognitiva baja (recordar, comprender)',
            'intermedio' => 'intermedia: situaciones contextualizadas que exigen aplicar y analizar',
            'desafiante' => 'desafiante: situaciones nuevas que exigen justificar, evaluar y transferir',
        ][$op['dificultad'] ?? 'intermedio'];
        $proposito = ['diagnostica' => 'diagnóstica', 'formativa' => 'formativa', 'sumativa' => 'sumativa'][$op['proposito'] ?? 'formativa'];

        $partes = [
            "Genera exactamente {$op['cantidad']} ítems para un instrumento de evaluación.",
            '',
            'Contexto curricular:',
            '- Nivel / curso: '.($op['nivel'] ?? $oa->nivel),
            '- Asignatura: '.($oa->asignatura?->nombre ?? '—'),
            "- OA {$oa->codigo}: {$oa->texto}",
        ];
        if ($oa->unidad) {
            $partes[] = "- Unidad: {$oa->unidad}";
        }
        if ($oa->contenidos) {
            $partes[] = '- Contenidos: '.$lista($oa->contenidos);
        }
        if ($oa->habilidades) {
            $partes[] = '- Habilidades: '.$lista($oa->habilidades);
        }
        $criterios = trim((string) ($op['criterios'] ?? '')) ?: $lista($oa->indicadores);
        if ($criterios) {
            $partes[] = "- Indicadores / criterios a evaluar: {$criterios}";
        }

        array_push($partes,
            '',
            'Parámetros:',
            '- Tipo de instrumento: '.(($op['tipo_instrumento'] ?? null) ?: 'Prueba'),
            "- Propósito: evaluación {$proposito}",
            "- Dificultad {$dificultad}",
            "- Tipos de ítem: {$tipos[$op['mezcla']]}",
        );

        if (! empty($op['flexibilizada'])) {
            $partes[] = '- VERSIÓN FLEXIBILIZADA (adecuación evaluativa, Decreto 83): instrucciones segmentadas y breves, '
                .'lenguaje simple, una idea por enunciado, apoyos de contexto, 3 alternativas en selección múltiple, '
                .'mismo OA pero menor carga de lectura.';
        }

        $partes[] = '';
        $partes[] = 'Para "seleccion" indica en indice_correcta la posición (desde 0) de la alternativa correcta. '
            .'Para "verdadero_falso" usa respuesta_vf "V" o "F". Para "desarrollo" entrega criterios (pauta de corrección).';

        return implode("\n", $partes);
    }

    private static function esquema(string $mezcla): array
    {
        $tipos = $mezcla === 'mixto' ? InstrumentoPregunta::TIPOS : [$mezcla];

        return [
            'type' => 'object',
            'properties' => [
                'preguntas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'tipo' => ['type' => 'string', 'enum' => $tipos],
                            'enunciado' => ['type' => 'string'],
                            'alternativas' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'indice_correcta' => ['type' => 'integer'],
                            'respuesta_vf' => ['type' => 'string', 'enum' => ['V', 'F']],
                            'criterios' => ['type' => 'string'],
                        ],
                        'required' => ['tipo', 'enunciado'],
                    ],
                ],
            ],
            'required' => ['preguntas'],
        ];
    }

    /** Valida cada ítem, descarta los incompletos y deja el formato del editor. */
    public static function normalizar(array $items, string $mezcla, int $cantidad): array
    {
        $limpiar = fn ($t) => trim(preg_replace('/^\s*(\d+[.)]|[a-eA-E][.)])\s*/u', '', (string) $t));
        $preguntas = [];

        foreach ($items as $item) {
            $tipo = $item['tipo'] ?? null;
            $enunciado = $limpiar($item['enunciado'] ?? '');
            if (! in_array($tipo, InstrumentoPregunta::TIPOS, true) || $enunciado === ''
                || ($mezcla !== 'mixto' && $tipo !== $mezcla)) {
                continue;
            }

            $p = [
                'tipo' => $tipo,
                'puntaje' => GeneradorInstrumento::PUNTAJES[$tipo],
                'enunciado' => mb_substr($enunciado, 0, 2000),
                'alternativas' => null,
                'respuesta_correcta' => null,
                'criterios' => null,
            ];

            if ($tipo === 'seleccion') {
                $alts = array_values(array_filter(array_map($limpiar, $item['alternativas'] ?? []), 'strlen'));
                $correcta = $item['indice_correcta'] ?? null;
                if (count($alts) < 2 || ! is_int($correcta) || ! isset($alts[$correcta])) {
                    continue;
                }
                $alts = array_slice($alts, 0, 6);
                if ($correcta > 5) {
                    continue;
                }
                // Se baraja para que la correcta no quede siempre en la misma letra
                $texto = $alts[$correcta];
                shuffle($alts);
                $p['alternativas'] = array_map(fn ($a) => mb_substr($a, 0, 500), $alts);
                $p['respuesta_correcta'] = (string) array_search($texto, $alts, true);
            } elseif ($tipo === 'verdadero_falso') {
                $vf = strtoupper((string) ($item['respuesta_vf'] ?? ''));
                if (! in_array($vf, ['V', 'F'], true)) {
                    continue;
                }
                $p['respuesta_correcta'] = $vf;
            } else {
                $p['criterios'] = mb_substr(trim((string) ($item['criterios'] ?? '')), 0, 2000) ?: null;
            }

            $preguntas[] = $p;
            if (count($preguntas) >= $cantidad) {
                break;
            }
        }

        return $preguntas;
    }
}
