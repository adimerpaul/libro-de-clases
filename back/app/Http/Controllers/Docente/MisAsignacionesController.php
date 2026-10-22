<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Curso;
use Illuminate\Http\Request;

/** Carga del docente que consulta: sus asignaturas por curso en el año indicado. */
class MisAsignacionesController extends Controller
{
    public function __invoke(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);

        $asignaciones = Asignacion::with(['curso', 'asignatura:id,nombre,codigo,color'])
            ->where('docente_id', $request->user()->id)
            ->whereHas('curso', fn ($q) => $q->where('anio', $anio))
            ->get();

        // Mismo orden que la lista de cursos (Pre-kínder → 4° Medio), luego asignatura
        $orden = Curso::ordenar($asignaciones->pluck('curso')->unique('id'))->pluck('id')->flip();

        return $asignaciones
            ->sortBy([
                fn ($a, $b) => $orden[$a->curso_id] <=> $orden[$b->curso_id],
                fn ($a, $b) => strcmp($a->asignatura->nombre, $b->asignatura->nombre),
            ])
            ->values();
    }
}
