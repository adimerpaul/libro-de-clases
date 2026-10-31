<?php

namespace App\Http\Controllers\Emocional;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\DiagnosticoSocioemocional;
use App\Models\Matricula;
use App\Support\Socioemocional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Diagnóstico socioemocional tipo DIA: un registro por estudiante, año y periodo, con el
 * logro (0–100) de cada competencia. Dimensiones y promedios en App\Support\Socioemocional.
 */
class DiagnosticoController extends Controller
{
    /** Planilla del curso en un periodo: nómina activa con su diagnóstico (si lo tiene). */
    public function planilla(Request $request)
    {
        $request->validate([
            'curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL'],
            'periodo' => ['required', Rule::in(array_keys(Socioemocional::PERIODOS))],
        ]);

        return $this->datosPlanilla(Curso::findOrFail($request->curso_id), $request->periodo);
    }

    /** filas: [{ estudiante_id, <competencia>: 0–100|null, observacion }]; una fila vacía borra el diagnóstico. */
    public function guardar(Request $request)
    {
        $competencias = array_keys(Socioemocional::COMPETENCIAS);
        $reglas = [
            'curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL'],
            'periodo' => ['required', Rule::in(array_keys(Socioemocional::PERIODOS))],
            'fecha' => ['required', 'date'],
            'filas' => ['present', 'array'],
            'filas.*.estudiante_id' => ['required', 'integer', 'distinct'],
            'filas.*.observacion' => ['nullable', 'string', 'max:500'],
        ];
        foreach ($competencias as $c) {
            $reglas["filas.*.{$c}"] = ['nullable', 'integer', 'between:0,100'];
        }
        $datos = $request->validate($reglas, [], ['filas.*.observacion' => 'observación']);

        $curso = Curso::findOrFail($datos['curso_id']);
        $nomina = Matricula::activas()->where('curso_id', $curso->id)->pluck('estudiante_id')->all();
        foreach ($datos['filas'] as $f) {
            abort_unless(in_array($f['estudiante_id'], $nomina, true), 422, 'Hay estudiantes que no están matriculados en este curso.');
        }

        DB::transaction(function () use ($datos, $curso, $competencias, $request) {
            foreach ($datos['filas'] as $f) {
                $actual = DiagnosticoSocioemocional::where(['estudiante_id' => $f['estudiante_id'], 'anio' => $curso->anio, 'periodo' => $datos['periodo']])->first();
                $puntajes = collect($competencias)->mapWithKeys(fn ($c) => [$c => $f[$c] ?? null])->all();

                if (collect($puntajes)->filter(fn ($v) => $v !== null)->isEmpty()) {
                    $actual?->delete();

                    continue;
                }
                $valores = $puntajes + [
                    'curso_id' => $curso->id, 'fecha' => $datos['fecha'],
                    'observacion' => $f['observacion'] ?? null, 'aplicado_por_id' => $request->user()->id,
                ];
                $actual
                    ? $actual->update($valores)
                    : DiagnosticoSocioemocional::create($valores + ['estudiante_id' => $f['estudiante_id'], 'anio' => $curso->anio, 'periodo' => $datos['periodo']]);
            }
        });

        return $this->datosPlanilla($curso, $datos['periodo']);
    }

    /**
     * Resultados por curso del año: dimensiones DIA del último periodo aplicado y tendencia
     * respecto del periodo anterior (±3 puntos).
     */
    public function resultados(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $orden = array_flip(array_keys(Socioemocional::PERIODOS));
        $todos = DiagnosticoSocioemocional::where('anio', $anio)->get();

        $cursos = Curso::ordenar(Curso::whereIn('id', $todos->pluck('curso_id')->unique())->get())
            ->map(function (Curso $curso) use ($todos, $orden) {
                $porPeriodo = $todos->where('curso_id', $curso->id)->groupBy('periodo')->sortBy(fn ($v, $p) => $orden[$p]);
                $ultimo = $porPeriodo->keys()->last();
                $anterior = $porPeriodo->keys()->count() > 1 ? $porPeriodo->keys()->slice(-2, 1)->first() : null;
                $dims = $this->dimensionesDe($porPeriodo[$ultimo]);
                $promedio = Socioemocional::promedio($dims);
                $previo = $anterior ? Socioemocional::promedio($this->dimensionesDe($porPeriodo[$anterior])) : null;

                return [
                    'curso_id' => $curso->id, 'curso' => $curso->nombre, 'periodo' => $ultimo,
                    'estudiantes' => $porPeriodo[$ultimo]->count(),
                    'dimensiones' => $dims, 'promedio' => $promedio,
                    'tendencia' => $previo === null || $promedio === null ? null : ($promedio - $previo >= 3 ? 'sube' : ($promedio - $previo <= -3 ? 'baja' : 'estable')),
                ];
            })->values();

        // Global: último diagnóstico de cada estudiante
        $ultimos = $todos->sortBy(fn ($d) => [$orden[$d->periodo], $d->fecha])->groupBy('estudiante_id')->map->last();

        return ['anio' => $anio, 'global' => $this->dimensionesDe($ultimos), 'estudiantes' => $ultimos->count(), 'cursos' => $cursos];
    }

    /** Promedio de cada dimensión en un grupo de diagnósticos. */
    private function dimensionesDe($diagnosticos): array
    {
        return collect(array_keys(Socioemocional::DIMENSIONES))
            ->mapWithKeys(fn ($d) => [$d => Socioemocional::promedio($diagnosticos->map(fn ($x) => $x->dimensiones[$d]))])->all();
    }

    private function datosPlanilla(Curso $curso, string $periodo): array
    {
        $diagnosticos = DiagnosticoSocioemocional::where(['anio' => $curso->anio, 'periodo' => $periodo])
            ->whereIn('estudiante_id', Matricula::activas()->where('curso_id', $curso->id)->select('estudiante_id'))
            ->get()->keyBy('estudiante_id');

        $filas = Matricula::activas()->where('curso_id', $curso->id)->with('estudiante')->orderBy('numero_lista')->get()
            ->filter->estudiante
            ->map(fn (Matricula $m) => [
                'id' => $m->estudiante_id, 'numero_lista' => $m->numero_lista, 'nombre' => $m->estudiante->nombre_completo,
                'diagnostico' => $diagnosticos->get($m->estudiante_id),
            ])->values();

        $competencias = collect(Socioemocional::COMPETENCIAS)
            ->map(fn ($c, $k) => Socioemocional::promedio($diagnosticos->pluck($k)))->all();

        return [
            'curso' => ['id' => $curso->id, 'nombre' => $curso->nombre, 'anio' => $curso->anio, 'tramo' => Socioemocional::tramo($curso->nivel)],
            'periodo' => $periodo,
            'fecha' => $diagnosticos->max(fn ($d) => $d->fecha->format('Y-m-d')),
            'promedios' => $competencias,
            'dimensiones' => $this->dimensionesDe($diagnosticos),
            'filas' => $filas,
        ];
    }
}
