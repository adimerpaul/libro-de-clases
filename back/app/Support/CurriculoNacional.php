<?php

namespace App\Support;

use App\Models\Asignatura;
use App\Models\ObjetivoAprendizaje;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * OA oficiales publicados en curriculumnacional.cl (Unidad de Currículum y Evaluación, Mineduc),
 * extraídos página por página (curso × asignatura) a database/data/curriculo_nacional_oa.json
 * con database/data/curriculo_nacional_extraer.py. Cada OA trae su eje, texto oficial,
 * marca de priorización y la URL de su ficha; habilidades y actitudes son las del curso.
 *
 * cargar() lo lleva a objetivos_aprendizaje: idempotente y todo-o-nada. Un OA que ya existe
 * con el mismo código (no demo) se actualiza en su versión; los demás se crean en la del archivo.
 */
class CurriculoNacional
{
    private const COLORES = ['#0f4f85', '#bd1f2d', '#1f8a5b', '#9a6b1f', '#6b4fbb', '#c2407a', '#2b8fa8', '#e07a1f', '#5f7d8c', '#4a5a9a'];

    public static function ruta(): string
    {
        return database_path('data/curriculo_nacional_oa.json');
    }

    public static function cargar(?string $ruta = null): array
    {
        $ruta ??= self::ruta();
        $datos = is_file($ruta) ? json_decode(file_get_contents($ruta), true) : null;
        if (! is_array($datos) || empty($datos['ofertas'])) {
            throw new RuntimeException("Archivo de currículum inválido: {$ruta}");
        }

        return DB::transaction(function () use ($datos) {
            $resumen = ['asignaturas_creadas' => 0, 'oa_creados' => 0, 'oa_actualizados' => 0];
            $asignaturas = [];

            foreach ($datos['ofertas'] as $oferta) {
                $asignaturaId = $asignaturas[$oferta['asignatura_codigo']] ??= self::asignatura($oferta, $resumen);

                foreach ($oferta['oa'] as $oa) {
                    // El código ya viene único en el archivo (el extractor agrega el nivel si se repite)
                    $registro = ObjetivoAprendizaje::withTrashed()->where('codigo', $oa['codigo'])->where('es_demo', false)->first()
                        ?? new ObjetivoAprendizaje(['codigo' => $oa['codigo'], 'version_curricular' => $datos['version']]);
                    $nuevo = ! $registro->exists;

                    $registro->fill([
                        'nivel' => $oferta['nivel'],
                        'asignatura_id' => $asignaturaId,
                        'unidad' => $oa['eje'] ? mb_substr($oa['eje'], 0, 150) : null,
                        'texto' => $oa['texto'],
                        'habilidades' => $oferta['habilidades'] ?: null,
                        'actitudes' => $oferta['actitudes'] ?: null,
                        'priorizado' => $oa['priorizado'],
                        'fuente' => mb_substr($datos['fuente'], 0, 255),
                        'fuente_url' => mb_substr($oa['url'] ?? $oferta['url'], 0, 255),
                        'fuente_hash' => hash('sha256', $oa['texto']),
                        'es_demo' => false,
                    ]);
                    $registro->deleted_at = null;
                    $registro->save();

                    $resumen[$nuevo ? 'oa_creados' : 'oa_actualizados']++;
                }
            }

            return $resumen;
        });
    }

    /** La asignatura por código, luego por nombre; si no existe se crea. */
    private static function asignatura(array $oferta, array &$resumen): int
    {
        $asignatura = Asignatura::withTrashed()->where('codigo', $oferta['asignatura_codigo'])->first()
            ?? Asignatura::withTrashed()->where('nombre', $oferta['asignatura'])->first();

        if (! $asignatura) {
            $color = self::COLORES[crc32($oferta['asignatura']) % count(self::COLORES)];
            $asignatura = Asignatura::create(['codigo' => $oferta['asignatura_codigo'], 'nombre' => $oferta['asignatura'], 'color' => $color]);
            $resumen['asignaturas_creadas']++;
        } elseif ($asignatura->trashed()) {
            $asignatura->restore();
        }

        return $asignatura->id;
    }
}
