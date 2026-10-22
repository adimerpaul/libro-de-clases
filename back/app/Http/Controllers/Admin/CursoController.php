<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\User;
use App\Support\Niveles;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CursoController extends Controller
{
    public function index(Request $request)
    {
        $cursos = Curso::with('profesorJefe:id,name')
            ->withCount(['asignaciones', 'matriculas as estudiantes_count' => fn ($q) => $q->activas()])
            ->when($request->anio, fn ($q, $anio) => $q->where('anio', $anio))
            ->get();

        return Curso::ordenar($cursos);
    }

    public function store(Request $request)
    {
        return Curso::create($this->validar($request))->load('profesorJefe:id,name');
    }

    public function update(Request $request, Curso $curso)
    {
        $curso->update($this->validar($request, $curso));

        return $curso->load('profesorJefe:id,name');
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();

        return response()->json(['message' => 'Curso eliminado']);
    }

    private function validar(Request $request, ?Curso $curso = null): array
    {
        $request->merge(['letra' => mb_strtoupper(trim((string) $request->letra))]);

        $datos = $request->validate([
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'nivel' => ['required', Rule::in(Niveles::TODOS)],
            'letra' => [
                'required', 'string', 'max:2',
                Rule::unique('cursos')->ignore($curso)->withoutTrashed()
                    ->where('anio', $request->anio)->where('nivel', $request->nivel),
            ],
            'profesor_jefe_id' => ['nullable', 'integer', 'exists:users,id,deleted_at,NULL'],
        ], ['letra.unique' => 'Ya existe ese curso en el año indicado.'], ['anio' => 'año']);

        if (! empty($datos['profesor_jefe_id']) && ! User::find($datos['profesor_jefe_id'])->tienePermiso(Permisos::DICTAR_CLASES)) {
            abort(422, 'El profesor jefe debe tener el permiso Dictar clases.');
        }

        return $datos;
    }
}
