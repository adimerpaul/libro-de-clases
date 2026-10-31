<?php

namespace App\Http\Controllers\Emocional;

use App\Http\Controllers\Controller;
use App\Models\AlertaBienestar;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\DiagnosticoSocioemocional;
use App\Models\Matricula;
use App\Models\ObjetivoAprendizaje;
use App\Models\SesionSocioemocional;
use App\Models\User;
use App\Support\Niveles;
use App\Support\Permisos;
use App\Support\Socioemocional;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Dashboard, plan socioemocional, catálogos y vínculo con Orientación (Educación Emocional). */
class ResumenController extends Controller
{
    /** Catálogos de los formularios, cursos del año y profesionales responsables. */
    public function catalogos(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $user = $request->user();

        // Quien solo reporta (docente) ve los cursos de su carga o jefatura
        $cursos = Curso::visiblesPara($user, Permisos::de('emocional', Permisos::VER))->where('anio', $anio)->get(['id', 'nivel', 'letra']);
        $responsables = $user->tienePermiso(Permisos::de('emocional', Permisos::VER))
            ? User::orderBy('name')->get(['id', 'name'])->filter(fn (User $u) => $u->tienePermiso(Permisos::de('emocional', Permisos::EDITAR)))->values()
            : [];

        return Socioemocional::catalogos() + [
            'anio' => $anio,
            'cursos' => Curso::ordenar($cursos)->map(fn (Curso $c) => ['id' => $c->id, 'nombre' => $c->nombre, 'nivel' => $c->nivel, 'tramo' => Socioemocional::tramo($c->nivel)]),
            'responsables' => $responsables,
        ];
    }

    public function resumen(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $sesiones = SesionSocioemocional::whereHas('curso', fn ($q) => $q->where('anio', $anio))->with('curso:id,nivel,letra')->get();
        $hechas = $sesiones->whereIn('estado', ['realizada', 'en_curso', 'permanente']);

        $matriculados = Matricula::activas()->whereHas('curso', fn ($q) => $q->where('anio', $anio));
        $total = (clone $matriculados)->count();
        $alcanzados = (clone $matriculados)->whereIn('curso_id', $hechas->pluck('curso_id')->unique())->count();

        // Avance por competencia = promedio del último diagnóstico de cada estudiante en el año
        $ultimos = DiagnosticoSocioemocional::where('anio', $anio)->orderBy('fecha')->orderBy('id')->get()->groupBy('estudiante_id')->map->last();
        $competencias = collect(Socioemocional::COMPETENCIAS)->map(fn ($c, $k) => [
            'value' => $k, 'label' => $c[0], 'color' => $c[1],
            'logro' => Socioemocional::promedio($ultimos->pluck($k)),
            'sesiones' => $hechas->where('competencia', $k)->count(),
        ])->values();

        $alertas = AlertaBienestar::where('anio', $anio);

        return [
            'anio' => $anio,
            'sesiones_realizadas' => $sesiones->where('estado', 'realizada')->count(),
            'sesiones_total' => $sesiones->count(),
            'estudiantes_alcanzados' => $alcanzados,
            'estudiantes_total' => $total,
            'alertas_abiertas' => (clone $alertas)->abiertas()->count(),
            'alertas_alta' => (clone $alertas)->abiertas()->where('intensidad', 'alta')->count(),
            'diagnosticados' => $ultimos->count(),
            'cobertura' => $this->matriz($hechas)['cobertura'],
            'competencias' => $competencias,
            'recientes' => (clone $alertas)->with('estudiante:id,nombres,apellido_paterno,apellido_materno')
                ->latest('fecha')->latest('id')->limit(5)->get()
                ->map(fn (AlertaBienestar $a) => [
                    'id' => $a->id, 'tipo' => $a->tipo, 'estado' => $a->estado, 'intensidad' => $a->intensidad, 'fecha' => $a->fecha->format('Y-m-d'),
                    'estudiante_id' => $a->estudiante_id, 'estudiante' => $a->estudiante?->nombre_completo,
                    'curso' => Matricula::delAnio($a->estudiante_id, $anio)?->curso?->nombre,
                ]),
        ];
    }

    /** Matriz competencia × tramo del plan, con las sesiones del año que cubren cada celda. */
    public function plan(Request $request)
    {
        return $this->matriz(
            SesionSocioemocional::whereHas('curso', fn ($q) => $q->where('anio', (int) ($request->anio ?: now()->year)))
                ->whereIn('estado', ['realizada', 'en_curso', 'permanente'])->with('curso:id,nivel,letra')->get()
        );
    }

    private function matriz(Collection $hechas): array
    {
        $celdas = [];
        foreach (Socioemocional::PLAN as $competencia => $tramos) {
            foreach ($tramos as $tramo => $foco) {
                $celdas[$competencia][$tramo] = [
                    'foco' => $foco,
                    'sesiones' => $hechas->filter(fn ($s) => $s->competencia === $competencia && Socioemocional::tramo($s->curso->nivel) === $tramo)->count(),
                ];
            }
        }
        $cubiertas = collect($celdas)->flatten(1)->where('sesiones', '>', 0)->count();

        return ['celdas' => $celdas, 'cobertura' => (int) round(100 * $cubiertas / (count(Socioemocional::COMPETENCIAS) * count(Socioemocional::TRAMOS)))];
    }

    /**
     * OA de Orientación del catálogo curricular y su estado en el plan: cubierto si hay
     * una sesión realizada vinculada, parcial si solo planificada o en curso.
     */
    public function orientacion(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $asignatura = Asignatura::where('codigo', 'ORI')->first();
        if (! $asignatura) {
            return ['asignatura' => null, 'oa' => []];
        }

        $sesiones = SesionSocioemocional::whereNotNull('objetivo_aprendizaje_id')
            ->whereHas('curso', fn ($q) => $q->where('anio', $anio))->get()->groupBy('objetivo_aprendizaje_id');

        $oa = ObjetivoAprendizaje::where('asignatura_id', $asignatura->id)->vigentes()->get(['id', 'codigo', 'nivel', 'unidad', 'texto', 'es_demo'])
            ->sortBy([fn ($a, $b) => Niveles::orden($a->nivel) <=> Niveles::orden($b->nivel), fn ($a, $b) => strnatcmp($a->codigo, $b->codigo)])
            ->map(function (ObjetivoAprendizaje $o) use ($sesiones) {
                $s = $sesiones->get($o->id, collect());

                return $o->toArray() + [
                    'sesiones' => $s->count(),
                    'competencias' => $s->pluck('competencia')->unique()->values(),
                    'estado' => $s->where('estado', 'realizada')->isNotEmpty() ? 'cubierto' : ($s->isNotEmpty() ? 'parcial' : 'pendiente'),
                ];
            })->values();

        return ['asignatura' => $asignatura->only('id', 'nombre'), 'oa' => $oa];
    }
}
