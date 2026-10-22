<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Support\Permisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Libro de clases de cada curso. Con "libros.ver" se ven todos; un docente
 * solo los cursos donde dicta alguna asignatura o es profesor(a) jefe.
 */
class LibroController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $anio = (int) ($request->anio ?: now()->year);

        $cursos = $this->visibles($request)
            ->where('anio', $anio)
            ->with(['profesorJefe:id,name', 'asignaciones' => fn ($q) => $q->with('asignatura:id,nombre,codigo,color')->select('id', 'curso_id', 'asignatura_id', 'docente_id')])
            ->withCount(['asignaciones', 'matriculas as estudiantes_count' => fn ($q) => $q->activas()])
            ->get()
            ->each(function (Curso $curso) use ($user) {
                $curso->es_jefatura = $curso->profesor_jefe_id === $user->id;
                // Asignaturas que dicta quien consulta (vacío para UTP / Dirección)
                $curso->mis_asignaturas = $curso->asignaciones->where('docente_id', $user->id)->pluck('asignatura')->values();
                $curso->unsetRelation('asignaciones');
            });

        return Curso::ordenar($cursos);
    }

    public function show(Request $request, int $curso)
    {
        $curso = $this->visibles($request)
            ->with([
                'profesorJefe:id,name,email',
                'asignaciones' => fn ($q) => $q->with(['asignatura:id,nombre,codigo,color', 'docente:id,name']),
                'matriculas' => fn ($q) => $q->with('estudiante')->orderBy('numero_lista'),
            ])
            ->findOrFail($curso);

        $curso->setRelation('asignaciones', $curso->asignaciones->sortBy('asignatura.nombre')->values());

        return $curso;
    }

    /** Cursos que puede abrir quien consulta. */
    private function visibles(Request $request): Builder
    {
        $user = $request->user();

        return Curso::query()->when(! $user->tienePermiso(Permisos::VER_LIBROS), fn ($q) => $q->where(
            fn ($q) => $q->where('profesor_jefe_id', $user->id)
                ->orWhereHas('asignaciones', fn ($q) => $q->where('docente_id', $user->id))
        ));
    }
}
