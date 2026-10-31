<?php

namespace App\Http\Controllers\Emocional;

use App\Http\Controllers\Controller;
use App\Models\AlertaBienestar;
use App\Models\DiagnosticoSocioemocional;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\SesionSocioemocional;
use App\Support\Socioemocional;
use Illuminate\Http\Request;

/** Ficha socioemocional de cada estudiante: diagnósticos, seguimientos y sesiones de su curso. */
class EstudianteController extends Controller
{
    /** Estudiantes del año con su último diagnóstico y alertas abiertas. */
    public function index(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $texto = trim((string) $request->q);

        $matriculas = Matricula::activas()->whereHas('curso', fn ($q) => $q->where('anio', $anio))
            ->when($request->curso_id, fn ($q, $id) => $q->where('curso_id', $id))
            ->when($texto !== '', fn ($q) => $q->whereHas('estudiante', fn ($e) => $e->where(fn ($w) => $w
                ->where('nombres', 'like', "%{$texto}%")->orWhere('apellido_paterno', 'like', "%{$texto}%")
                ->orWhere('apellido_materno', 'like', "%{$texto}%")
                ->orWhere('run', 'like', '%'.preg_replace('/[^0-9kK]/', '', $texto).'%'))))
            ->with('estudiante:id,run,nombres,apellido_paterno,apellido_materno', 'curso:id,nivel,letra')
            ->get()->filter->estudiante;

        $ids = $matriculas->pluck('estudiante_id');
        $orden = array_flip(array_keys(Socioemocional::PERIODOS));
        $diagnosticos = DiagnosticoSocioemocional::where('anio', $anio)->whereIn('estudiante_id', $ids)->get()
            ->sortBy(fn ($d) => $orden[$d->periodo])->groupBy('estudiante_id')->map->last();
        $alertas = AlertaBienestar::where('anio', $anio)->abiertas()->whereIn('estudiante_id', $ids)->get()->groupBy('estudiante_id');

        return $matriculas->map(function (Matricula $m) use ($diagnosticos, $alertas) {
            $d = $diagnosticos->get($m->estudiante_id);
            $a = $alertas->get($m->estudiante_id, collect());

            return [
                'id' => $m->estudiante_id,
                'nombre' => $m->estudiante->nombre_completo,
                'run' => $m->estudiante->run,
                'curso' => $m->curso->nombre,
                'curso_id' => $m->curso_id,
                'numero_lista' => $m->numero_lista,
                'promedio' => $d?->promedio,
                'periodo' => $d?->periodo,
                'dimensiones' => $d?->dimensiones,
                'alertas_abiertas' => $a->count(),
                'alerta_alta' => $a->contains('intensidad', 'alta'),
            ];
        })
            ->when($request->con_alerta, fn ($c) => $c->filter(fn ($e) => $e['alertas_abiertas'] > 0))
            ->sortBy([['curso_id', 'asc'], ['numero_lista', 'asc']])->values();
    }

    public function show(Request $request, Estudiante $estudiante)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $matricula = Matricula::delAnio($estudiante->id, $anio)?->load('curso.profesorJefe:id,name');
        $orden = array_flip(array_keys(Socioemocional::PERIODOS));

        $diagnosticos = $estudiante->diagnosticosSocioemocionales()->with('curso:id,nivel,letra', 'aplicadoPor:id,name')->get()
            ->sortBy([['anio', 'desc'], fn ($a, $b) => $orden[$b->periodo] <=> $orden[$a->periodo]])->values();

        $alertas = $estudiante->alertasBienestar()
            ->with(['responsable:id,name', 'reportadoPor:id,name', 'intervenciones' => fn ($q) => $q->with('autor:id,name')->orderByDesc('fecha')->orderByDesc('id')])
            ->orderByDesc('fecha')->orderByDesc('id')->get();

        $sesiones = $matricula
            ? SesionSocioemocional::where('curso_id', $matricula->curso_id)->with('responsable:id,name', 'objetivo:id,codigo')
                ->orderByDesc('fecha')->get()
            : collect();

        $ultimo = $diagnosticos->firstWhere('anio', $anio);

        return [
            'estudiante' => $estudiante->only('id', 'run', 'nombres', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'fecha_nacimiento', 'pie'),
            'curso' => $matricula ? [
                'id' => $matricula->curso_id, 'nombre' => $matricula->curso->nombre, 'nivel' => $matricula->curso->nivel,
                'tramo' => Socioemocional::tramo($matricula->curso->nivel), 'profesor_jefe' => $matricula->curso->profesorJefe?->name,
            ] : null,
            'anio' => $anio,
            'ultimo' => $ultimo,
            'diagnosticos' => $diagnosticos,
            'alertas' => $alertas,
            'sesiones' => $sesiones,
            // Sesiones del curso por competencia (lo trabajado en el año)
            'trabajado' => collect(Socioemocional::COMPETENCIAS)->map(fn ($c, $k) => $sesiones->where('competencia', $k)->whereIn('estado', ['realizada', 'en_curso', 'permanente'])->count()),
        ];
    }
}
