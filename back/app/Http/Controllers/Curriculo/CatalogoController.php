<?php

namespace App\Http\Controllers\Curriculo;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\ObjetivoAprendizaje;
use App\Support\Niveles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Catálogo curricular versionado (módulo Currículum y OA, ui3 curriculumPage).
 * Cada OA conserva fuente, versión y vigencia; un OA en uso no se borra, se retira
 * fijando su "vigente hasta". Importación según ui3/curriculum_import_schema.json.
 */
class CatalogoController extends Controller
{
    /** Lo que usa un OA: mientras tenga alguno no se elimina (se retira con su vigencia). */
    private const USOS = ['planificaciones', 'instrumentosEvaluacion as instrumentos_count', 'recursosBiblioteca as recursos_count'];

    /** Campos de lista (habilidades, indicadores…): arreglos de textos. */
    private const LISTAS = ['habilidades', 'actitudes', 'indicadores', 'instrumentos', 'recursos'];

    /** Claves del esquema de importación (inglés) → columnas del catálogo. */
    private const ESQUEMA = [
        'level' => 'nivel',
        'oa_code' => 'codigo',
        'oa_statement' => 'texto',
        'unit_or_axis' => 'unidad',
        'skills' => 'habilidades',
        'attitudes' => 'actitudes',
        'indicators' => 'indicadores',
        'source_url' => 'fuente_url',
        'curriculum_version' => 'version_curricular',
        'effective_from' => 'vigente_desde',
        'effective_to' => 'vigente_hasta',
        'source_hash' => 'fuente_hash',
    ];

    public function catalogos()
    {
        $hoy = now()->toDateString();
        $vigentes = fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy);

        $conteo = ObjetivoAprendizaje::where($vigentes)
            ->selectRaw('nivel, asignatura_id, count(*) as total')
            ->groupBy('nivel', 'asignatura_id')
            ->get();

        return [
            'niveles' => array_map(fn ($n) => $n + [
                'total' => (int) $conteo->where('nivel', $n['nivel'])->sum('total'),
            ], Niveles::conEtapa()),
            'asignaturas' => Asignatura::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'color']),
            'conteo' => $conteo->map(fn ($c) => ['nivel' => $c->nivel, 'asignatura_id' => $c->asignatura_id, 'total' => (int) $c->total])->values(),
            'versiones' => ObjetivoAprendizaje::distinct()->orderBy('version_curricular')->pluck('version_curricular'),
            'resumen' => [
                'vigentes' => (int) $conteo->sum('total'),
                'demo' => ObjetivoAprendizaje::where($vigentes)->where('es_demo', true)->count(),
                'con_indicadores' => ObjetivoAprendizaje::where($vigentes)->whereNotNull('indicadores')->where('indicadores', '!=', '[]')->count(),
            ],
        ];
    }

    public function index(Request $request)
    {
        $request->validate([
            'nivel' => ['nullable', 'string'],
            'asignatura_id' => ['nullable', 'integer'],
            'version' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:100'],
            'todos' => ['nullable', 'boolean'],
        ]);

        return ObjetivoAprendizaje::query()
            ->with('asignatura:id,nombre,codigo,color')
            ->withCount(self::USOS)
            ->when(! $request->boolean('todos'), fn ($q) => $q->vigentes())
            ->when($request->nivel, fn ($q, $nivel) => $q->where('nivel', $nivel))
            ->when($request->asignatura_id, fn ($q, $id) => $q->where('asignatura_id', $id))
            ->when($request->version, fn ($q, $v) => $q->where('version_curricular', $v))
            ->when($request->q, fn ($q, $texto) => $q->where(fn ($w) => $w
                ->where('codigo', 'like', "%{$texto}%")
                ->orWhere('texto', 'like', "%{$texto}%")
                ->orWhere('unidad', 'like', "%{$texto}%")))
            ->orderBy('codigo')
            ->get();
    }

    public function store(Request $request)
    {
        $this->normalizarCodigo($request);
        $datos = $this->limpiar($request->validate($this->reglas($request)));

        // El índice único (código, versión) incluye los eliminados: se recupera el mismo OA
        $oa = ObjetivoAprendizaje::onlyTrashed()
            ->where(['codigo' => $datos['codigo'], 'version_curricular' => $datos['version_curricular']])
            ->first();

        if ($oa) {
            $oa->restore();
            $oa->update($datos);
        } else {
            $oa = ObjetivoAprendizaje::create($datos);
        }

        return response()->json($this->conDatos($oa), 201);
    }

    public function update(Request $request, ObjetivoAprendizaje $oa)
    {
        $this->normalizarCodigo($request);
        $oa->update($this->limpiar($request->validate($this->reglas($request, $oa))));

        return $this->conDatos($oa);
    }

    public function destroy(ObjetivoAprendizaje $oa)
    {
        $oa->loadCount(self::USOS);
        $usos = array_filter([
            'planificación(es)' => $oa->planificaciones_count,
            'instrumento(s) de evaluación' => $oa->instrumentos_count,
            'recurso(s) de la biblioteca' => $oa->recursos_count,
        ]);

        if ($usos) {
            $detalle = implode(', ', array_map(fn ($n, $que) => "{$n} {$que}", $usos, array_keys($usos)));

            return response()->json([
                'message' => "El OA {$oa->codigo} se usa en {$detalle}: no se puede eliminar. Retíralo del catálogo en su lugar.",
            ], 409);
        }

        $oa->delete();

        return response()->json(['message' => "OA {$oa->codigo} eliminado"]);
    }

    /**
     * Retira un OA en uso (vigente hasta ayer: deja de ofrecerse en planificación y
     * evaluaciones, pero lo ya hecho lo conserva) o lo reactiva.
     */
    public function retirar(Request $request, ObjetivoAprendizaje $oa)
    {
        $reactivar = $request->validate(['reactivar' => ['boolean']])['reactivar'] ?? false;

        $oa->update(['vigente_hasta' => $reactivar ? null : now()->subDay()->toDateString()]);

        return response()->json([
            'message' => $reactivar ? "OA {$oa->codigo} reactivado" : "OA {$oa->codigo} retirado del catálogo",
            'oa' => $this->conDatos($oa),
        ]);
    }

    /**
     * Carga masiva desde un JSON (arreglo de OA con las claves de ui3/curriculum_import_schema.json
     * o las del catálogo). Todo o nada: con un error en una fila no se guarda ninguna.
     * Un OA con el mismo código y versión se actualiza.
     */
    public function importar(Request $request)
    {
        $request->validate([
            'oas' => ['required', 'array', 'min:1', 'max:2000'],
            'oas.*' => ['array'],
        ], [], ['oas' => 'archivo']);

        $asignaturas = Asignatura::get(['id', 'nombre', 'codigo']);
        $buscar = function ($valor) use ($asignaturas) {
            $v = mb_strtolower(trim((string) $valor));

            return $asignaturas->first(fn ($a) => mb_strtolower($a->codigo) === $v || mb_strtolower($a->nombre) === $v)?->id;
        };

        $filas = [];
        $errores = [];
        $claves = [];

        foreach ($request->oas as $i => $crudo) {
            $fila = [];
            foreach ($crudo as $clave => $valor) {
                $fila[self::ESQUEMA[$clave] ?? $clave] = $valor;
            }
            if (! isset($fila['asignatura_id'])) {
                $fila['asignatura_id'] = $buscar($fila['subject'] ?? $fila['asignatura'] ?? null);
            }
            $fila['version_curricular'] ??= 'sin-version';
            $fila['es_demo'] = (bool) ($fila['es_demo'] ?? false);

            $v = Validator::make($fila, $this->reglas(null), [
                'asignatura_id.required' => 'La asignatura «'.($fila['subject'] ?? $fila['asignatura'] ?? '').'» no existe en el establecimiento.',
            ]);

            $clave = mb_strtoupper(trim((string) ($fila['codigo'] ?? ''))).'|'.$fila['version_curricular'];
            if ($v->fails()) {
                $errores[] = 'Fila '.($i + 1).' ('.($fila['codigo'] ?? 'sin código').'): '.$v->errors()->first();
            } elseif (isset($claves[$clave])) {
                $errores[] = 'Fila '.($i + 1)." ({$fila['codigo']}): código repetido en el archivo para la versión {$fila['version_curricular']}.";
            } else {
                $claves[$clave] = true;
                $filas[] = $this->limpiar($v->validated());
            }

            if (count($errores) >= 20) {
                break;
            }
        }

        if ($errores) {
            return response()->json(['message' => 'El archivo tiene errores; no se importó ningún OA.', 'errores' => $errores], 422);
        }

        $creados = 0;
        $actualizados = 0;
        DB::transaction(function () use ($filas, &$creados, &$actualizados) {
            foreach ($filas as $datos) {
                $oa = ObjetivoAprendizaje::withTrashed()
                    ->where(['codigo' => $datos['codigo'], 'version_curricular' => $datos['version_curricular']])
                    ->first();

                if ($oa) {
                    $oa->trashed() && $oa->restore();
                    $oa->update($datos);
                    $actualizados++;
                } else {
                    ObjetivoAprendizaje::create($datos);
                    $creados++;
                }
            }
        });

        return response()->json([
            'message' => "Importación lista: {$creados} OA nuevos y {$actualizados} actualizados.",
            'creados' => $creados,
            'actualizados' => $actualizados,
        ]);
    }

    private function reglas(?Request $request, ?ObjetivoAprendizaje $oa = null): array
    {
        $version = $request?->input('version_curricular');

        $reglas = [
            'codigo' => ['required', 'string', 'max:40'],
            'nivel' => ['required', Rule::in(Niveles::TODOS)],
            'asignatura_id' => ['required', 'integer', 'exists:asignaturas,id,deleted_at,NULL'],
            'unidad' => ['nullable', 'string', 'max:150'],
            'texto' => ['required', 'string', 'max:3000'],
            'contenidos' => ['nullable', 'string', 'max:3000'],
            'estrategias' => ['nullable', 'string', 'max:2000'],
            'inicio' => ['nullable', 'string', 'max:2000'],
            'desarrollo' => ['nullable', 'string', 'max:2000'],
            'cierre' => ['nullable', 'string', 'max:2000'],
            'tiempo_espacio' => ['nullable', 'string', 'max:200'],
            'fuente' => ['nullable', 'string', 'max:255'],
            'fuente_url' => ['nullable', 'url', 'max:255'],
            'version_curricular' => ['required', 'string', 'max:40'],
            'vigente_desde' => ['nullable', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],
            'fuente_hash' => ['nullable', 'string', 'max:64'],
            'es_demo' => ['boolean'],
        ];

        // En el formulario, el código no se repite dentro de la misma versión
        if ($request) {
            $reglas['codigo'][] = Rule::unique('objetivos_aprendizaje', 'codigo')
                ->where('version_curricular', $version)
                ->withoutTrashed()
                ->ignore($oa?->id);
        }

        foreach (self::LISTAS as $lista) {
            $reglas[$lista] = ['nullable', 'array', 'max:30'];
            $reglas["{$lista}.*"] = ['nullable', 'string', 'max:300'];
        }

        return $reglas;
    }

    /** El código se guarda en mayúsculas: se normaliza antes de validar su unicidad. */
    private function normalizarCodigo(Request $request): void
    {
        if (is_string($request->codigo)) {
            $request->merge(['codigo' => mb_strtoupper(trim($request->codigo))]);
        }
    }

    /** Quita textos vacíos de las listas (null si queda vacía) y normaliza el código. */
    private function limpiar(array $datos): array
    {
        $datos['codigo'] = mb_strtoupper(trim($datos['codigo']));

        foreach (self::LISTAS as $lista) {
            if (array_key_exists($lista, $datos)) {
                $items = array_values(array_filter(array_map(fn ($x) => trim((string) $x), $datos[$lista] ?? []), 'strlen'));
                $datos[$lista] = $items ?: null;
            }
        }

        return $datos;
    }

    private function conDatos(ObjetivoAprendizaje $oa): ObjetivoAprendizaje
    {
        return $oa->load('asignatura:id,nombre,codigo,color')
            ->loadCount(self::USOS);
    }
}
