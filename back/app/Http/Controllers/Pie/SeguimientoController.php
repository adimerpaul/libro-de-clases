<?php

namespace App\Http\Controllers\Pie;

use App\Http\Controllers\Controller;
use App\Models\PieCaso;
use App\Models\PieSeguimiento;
use App\Support\Pie;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Bitácora del caso PIE: intervenciones, co-docencia, familia y equipo. */
class SeguimientoController extends Controller
{
    public function store(Request $request, PieCaso $caso)
    {
        $seguimiento = $caso->seguimientos()->create($this->validar($request) + ['autor_id' => $request->user()->id]);

        return response()->json($seguimiento->load('autor:id,name'), 201);
    }

    public function update(Request $request, PieSeguimiento $seguimiento)
    {
        $seguimiento->update($this->validar($request));

        return $seguimiento->load('autor:id,name');
    }

    public function destroy(PieSeguimiento $seguimiento)
    {
        $seguimiento->delete();

        return response()->json(['message' => 'Registro eliminado de la bitácora']);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'tipo' => ['required', Rule::in(Pie::TIPOS_SEGUIMIENTO)],
            'area' => ['nullable', Rule::in(Pie::AREAS)],
            'duracion_minutos' => ['nullable', 'integer', 'between:1,600'],
            'descripcion' => ['required', 'string', 'max:3000'],
            'acuerdos' => ['nullable', 'string', 'max:2000'],
        ], [], ['duracion_minutos' => 'duración', 'descripcion' => 'descripción', 'area' => 'área']);
    }
}
