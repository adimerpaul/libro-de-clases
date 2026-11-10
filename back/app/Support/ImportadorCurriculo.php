<?php

namespace App\Support;

use App\Models\Asignatura;
use App\Models\ObjetivoAprendizaje;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

/**
 * Módulo Curricular 3.1 en dos pasos:
 *  1. cargarReferencia(): copia una base SQLite "curriculum_core" (paquete Modulo_Curricular_3_1)
 *     a las tablas curriculum_* / learning_objectives, conservando los id del paquete.
 *  2. sincronizar(): lleva los OA oficiales vigentes al catálogo de trabajo (objetivos_aprendizaje)
 *     que usan Currículum, Planificador y Evaluaciones; crea las asignaturas que falten.
 * Ambos son idempotentes y todo-o-nada.
 */
class ImportadorCurriculo
{
    /**
     * Tabla SQLite => [tabla destino, renombres de columnas, columnas JSON, clave].
     * En orden de dependencias (las FK apuntan a tablas ya cargadas).
     */
    private const TABLAS = [
        'source_registry' => ['curriculum_sources', [], [], ['id']],
        'curriculum_versions' => ['curriculum_versions', [], [], ['id']],
        'education_levels' => ['curriculum_levels', [], [], ['id']],
        'grades' => ['curriculum_grades', [], [], ['id']],
        'subjects' => ['curriculum_subjects', [], [], ['id']],
        'grade_subjects' => ['curriculum_offerings', [], [], ['id']],
        'curriculum_axes' => ['curriculum_axes', ['grade_subject_id' => 'offering_id'], [], ['id']],
        'curriculum_units' => ['curriculum_units', ['grade_subject_id' => 'offering_id'], [], ['id']],
        'learning_objectives' => ['learning_objectives', ['grade_subject_id' => 'offering_id'], [], ['id']],
        'unit_objectives' => ['curriculum_unit_objective', [], [], ['unit_id', 'objective_id']],
        'indicators' => ['curriculum_indicators', [], [], ['id']],
        'skills_competencies' => ['curriculum_skills', [], [], ['id']],
        'objective_skills' => ['curriculum_objective_skill', [], [], ['objective_id', 'skill_id']],
        'resource_catalog' => ['curriculum_resources', ['metadata_json' => 'metadata'], ['metadata'], ['id']],
        'resource_objectives' => ['curriculum_resource_objective', [], [], ['resource_id', 'objective_id']],
        'planning_templates' => ['curriculum_planning_templates', ['grade_subject_id' => 'offering_id', 'structure_json' => 'structure'], ['structure'], ['id']],
        'tp_specialties' => ['curriculum_tp_specialties', [], [], ['id']],
        'tp_modules' => ['curriculum_tp_modules', [], [], ['id']],
        'tp_module_objectives' => ['curriculum_tp_module_objective', [], [], ['module_id', 'objective_id']],
    ];

    /** Tablas puente: sin timestamps ni soft deletes. */
    private const PUENTES = ['curriculum_unit_objective', 'curriculum_objective_skill', 'curriculum_resource_objective', 'curriculum_tp_module_objective'];

    /** Código de asignatura del paquete => código en el libro (DatosAcademicosSeeder). */
    private const ASIGNATURAS = [
        'ART_VIS' => 'ART', 'LENG' => 'LEN', 'MAT' => 'MAT', 'CN' => 'CIE', 'HIST' => 'HIS', 'ING' => 'ING',
        'PARV_COM_INT' => 'PCI', 'PARV_DPS' => 'PDPS', 'PARV_ICE' => 'PICE',
    ];

    private const COLORES = ['#0f4f85', '#bd1f2d', '#1f8a5b', '#9a6b1f', '#6b4fbb', '#c2407a', '#2b8fa8', '#e07a1f'];

    /** Paso 1: SQLite del paquete → tablas de referencia. Devuelve filas por tabla destino. */
    public static function cargarReferencia(string $ruta): array
    {
        if (! is_file($ruta)) {
            throw new RuntimeException("No existe el archivo {$ruta}");
        }
        $sqlite = new PDO('sqlite:'.$ruta, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $ahora = now();

        return DB::transaction(function () use ($sqlite, $ahora) {
            $conteo = [];
            foreach (self::TABLAS as $origen => [$destino, $renombres, $json, $clave]) {
                $filas = array_map(function ($fila) use ($renombres, $json, $destino, $ahora) {
                    foreach ($renombres as $de => $a) {
                        $fila[$a] = $fila[$de];
                        unset($fila[$de]);
                    }
                    foreach ($json as $col) {
                        // Se re-codifica para validar el JSON y dejarlo en UTF-8 legible
                        $fila[$col] = isset($fila[$col]) ? json_encode(json_decode($fila[$col], true), JSON_UNESCAPED_UNICODE) : null;
                    }
                    if (! in_array($destino, self::PUENTES, true)) {
                        $fila += ['created_at' => $ahora, 'updated_at' => $ahora, 'deleted_at' => null];
                    }

                    return $fila;
                }, $sqlite->query("SELECT * FROM \"{$origen}\"")->fetchAll(PDO::FETCH_ASSOC));

                foreach (array_chunk($filas, 500) as $lote) {
                    $columnas = array_values(array_diff(array_keys($lote[0]), [...$clave, 'created_at']));
                    DB::table($destino)->upsert($lote, $clave, $columnas ?: $clave);
                }
                $conteo[$destino] = count($filas);
            }

            return $conteo;
        });
    }

    /** Paso 2: OA oficiales vigentes → objetivos_aprendizaje (uno por nivel del libro). */
    public static function sincronizar(): array
    {
        return DB::transaction(function () {
            $resumen = ['asignaturas_creadas' => 0, 'oa_creados' => 0, 'oa_actualizados' => 0, 'omitidos' => []];
            $asignaturas = self::asignaturas($resumen);

            $oas = DB::table('learning_objectives as lo')
                ->join('curriculum_offerings as o', 'o.id', '=', 'lo.offering_id')
                ->join('curriculum_grades as g', 'g.id', '=', 'o.grade_id')
                ->join('curriculum_subjects as s', 's.id', '=', 'o.subject_id')
                ->join('curriculum_versions as v', 'v.id', '=', 'o.curriculum_version_id')
                ->leftJoin('curriculum_axes as ax', 'ax.id', '=', 'lo.axis_id')
                ->leftJoin('curriculum_sources as src', 'src.id', '=', 'lo.source_id')
                ->where('lo.active', true)->whereNull('lo.deleted_at')
                ->orderBy('lo.code')
                ->get(['lo.id', 'lo.code', 'lo.official_text', 'lo.official_record', 'g.code as grado', 's.code as asignatura',
                    'ax.name as eje', 'v.code as version', 'v.snapshot_date', 'src.name as fuente', 'src.url as fuente_url']);

            $indicadores = self::agrupar(DB::table('curriculum_indicators')->whereNull('deleted_at')->orderBy('id')->get(['objective_id as oa', 'text as valor']));
            $habilidades = self::agrupar(DB::table('curriculum_objective_skill as os')->join('curriculum_skills as sk', 'sk.id', '=', 'os.skill_id')
                ->orderBy('sk.id')->get(['os.objective_id as oa', 'sk.name as valor']));
            $recursos = self::agrupar(DB::table('curriculum_resource_objective as ro')->join('curriculum_resources as r', 'r.id', '=', 'ro.resource_id')
                ->whereNull('r.deleted_at')->orderBy('r.id')->get(['ro.objective_id as oa', 'r.title as valor']));

            foreach ($oas as $oa) {
                $niveles = Niveles::CODIGOS[$oa->grado] ?? null;
                if (! $niveles) {
                    $resumen['omitidos'][] = "{$oa->code}: curso {$oa->grado} sin equivalente";

                    continue;
                }

                foreach ($niveles as $i => $nivel) {
                    // NT cubre Pre-kínder y Kínder: un OA por nivel (código + versión es único)
                    $codigo = count($niveles) > 1 ? "{$oa->code}-".($i + 1) : $oa->code;
                    $registro = ObjetivoAprendizaje::withTrashed()->firstOrNew(['codigo' => $codigo, 'version_curricular' => $oa->version]);
                    $nuevo = ! $registro->exists;

                    $registro->fill([
                        'nivel' => $nivel,
                        'asignatura_id' => $asignaturas[$oa->asignatura],
                        'curriculum_objective_id' => $oa->id,
                        'unidad' => $oa->eje ? mb_substr($oa->eje, 0, 150) : null,
                        'texto' => $oa->official_text,
                        'habilidades' => $habilidades[$oa->id] ?? null,
                        'indicadores' => $indicadores[$oa->id] ?? null,
                        'recursos' => $recursos[$oa->id] ?? null,
                        'fuente' => $oa->fuente ? mb_substr($oa->fuente, 0, 255) : null,
                        'fuente_url' => $oa->fuente_url,
                        'vigente_desde' => $oa->snapshot_date,
                        'fuente_hash' => hash('sha256', $oa->official_text),
                        'es_demo' => ! $oa->official_record,
                    ]);
                    $registro->deleted_at = null;
                    $registro->save();

                    $resumen[$nuevo ? 'oa_creados' : 'oa_actualizados']++;
                }
            }

            return $resumen;
        });
    }

    /** Asignaturas (y ámbitos de parvularia) del catálogo: usa la existente o la crea. */
    private static function asignaturas(array &$resumen): array
    {
        $mapa = [];
        foreach (DB::table('curriculum_subjects')->whereNull('deleted_at')->orderBy('id')->get() as $i => $s) {
            $codigo = self::ASIGNATURAS[$s->code] ?? mb_substr($s->code, 0, 10);
            $asignatura = Asignatura::withTrashed()->where('codigo', $codigo)->first()
                ?? Asignatura::where('nombre', $s->name)->first();

            if (! $asignatura) {
                $asignatura = Asignatura::create(['codigo' => $codigo, 'nombre' => $s->name, 'color' => self::COLORES[$i % count(self::COLORES)]]);
                $resumen['asignaturas_creadas']++;
            } elseif ($asignatura->trashed()) {
                $asignatura->restore();
            }

            $mapa[$s->code] = $asignatura->id;
        }

        return $mapa;
    }

    /** [id OA => [valor, …]] sin repetidos, recortados al largo de las listas del catálogo. */
    private static function agrupar($filas): array
    {
        $grupos = [];
        foreach ($filas as $f) {
            $grupos[$f->oa][] = mb_substr($f->valor, 0, 300);
        }

        return array_map(fn ($v) => array_values(array_unique($v)), $grupos);
    }
}
