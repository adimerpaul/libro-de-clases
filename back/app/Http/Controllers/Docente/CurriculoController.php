<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\ObjetivoAprendizaje;
use Illuminate\Http\Request;

/** Consulta del catálogo curricular (OA vigentes). */
class CurriculoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'nivel' => ['nullable', 'string'],
            'asignatura_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return ObjetivoAprendizaje::vigentes()
            ->with('asignatura:id,nombre,codigo,color')
            ->when($request->nivel, fn ($q, $nivel) => $q->where('nivel', $nivel))
            ->when($request->asignatura_id, fn ($q, $id) => $q->where('asignatura_id', $id))
            ->when($request->q, fn ($q, $texto) => $q->where(fn ($w) => $w
                ->where('codigo', 'like', "%{$texto}%")
                ->orWhere('texto', 'like', "%{$texto}%")
                ->orWhere('unidad', 'like', "%{$texto}%")))
            ->orderBy('codigo')
            ->get();
    }
}
