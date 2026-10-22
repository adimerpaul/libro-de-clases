<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Asignación docente: qué docente dicta qué asignatura en qué curso.
 */
class AsignacionController extends Controller
{
    private const RELACIONES = ['curso', 'asignatura:id,nombre,codigo,color', 'docente:id,name,email'];

    public function index(Request $request)
    {
        return Asignacion::with(self::RELACIONES)
            ->when($request->curso_id, fn ($q, $id) => $q->where('curso_id', $id))
            ->when($request->docente_id, fn ($q, $id) => $q->where('docente_id', $id))
            ->when($request->anio, fn ($q, $anio) => $q->whereHas('curso', fn ($c) => $c->where('anio', $anio)))
            ->get()
            ->sortBy(fn ($a) => $a->asignatura->nombre)
            ->values();
    }

    public function store(Request $request)
    {
        return Asignacion::create($this->validar($request))->load(self::RELACIONES);
    }

    public function update(Request $request, Asignacion $asignacion)
    {
        $asignacion->update($this->validar($request, $asignacion));

        return $asignacion->load(self::RELACIONES);
    }

    public function destroy(Asignacion $asignacion)
    {
        $asignacion->delete();

        return response()->json(['message' => 'Asignación eliminada']);
    }

    private function validar(Request $request, ?Asignacion $asignacion = null): array
    {
        $datos = $request->validate([
            'curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL'],
            'asignatura_id' => [
                'required', 'integer', 'exists:asignaturas,id,deleted_at,NULL',
                Rule::unique('asignaciones')->ignore($asignacion)->withoutTrashed()->where('curso_id', $request->curso_id),
            ],
            'docente_id' => ['required', 'integer', 'exists:users,id,deleted_at,NULL'],
            'horas_semanales' => ['nullable', 'integer', 'between:1,40'],
        ], ['asignatura_id.unique' => 'Esa asignatura ya tiene docente en este curso.'], [
            'curso_id' => 'curso', 'asignatura_id' => 'asignatura', 'docente_id' => 'docente',
        ]);

        if (! User::find($datos['docente_id'])->tienePermiso(Permisos::DICTAR_CLASES)) {
            abort(422, 'El usuario seleccionado no tiene el permiso Dictar clases.');
        }

        return $datos;
    }
}
