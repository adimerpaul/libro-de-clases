<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AsignaturaController extends Controller
{
    public function index()
    {
        return Asignatura::withCount('asignaciones')->orderBy('nombre')->get();
    }

    public function store(Request $request)
    {
        return Asignatura::create($this->validar($request));
    }

    public function update(Request $request, Asignatura $asignatura)
    {
        $asignatura->update($this->validar($request, $asignatura));

        return $asignatura;
    }

    public function destroy(Asignatura $asignatura)
    {
        if ($asignatura->asignaciones()->exists()) {
            return response()->json([
                'message' => 'La asignatura tiene docentes asignados en cursos. Quite esas asignaciones o desactívela.',
            ], 409);
        }

        $asignatura->delete();

        return response()->json(['message' => 'Asignatura eliminada']);
    }

    private function validar(Request $request, ?Asignatura $asignatura = null): array
    {
        $request->merge(['codigo' => mb_strtoupper(trim((string) $request->codigo))]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('asignaturas')->ignore($asignatura)->withoutTrashed()],
            'codigo' => ['required', 'string', 'max:10', Rule::unique('asignaturas')->ignore($asignatura)->withoutTrashed()],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'activo' => ['boolean'],
        ], [], ['codigo' => 'código']);
    }
}
