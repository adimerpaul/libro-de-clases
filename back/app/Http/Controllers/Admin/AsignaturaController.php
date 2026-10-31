<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AsignaturaController extends Controller
{
    /** Catálogo con los cursos del año que llevan cada asignatura (y quién la dicta). */
    public function index(Request $request)
    {
        $request->validate(['anio' => ['nullable', 'integer', 'min:2000', 'max:2100']]);
        $anio = $request->integer('anio') ?: (int) now()->year;

        $asignaturas = Asignatura::withCount('asignaciones')
            ->with(['asignaciones' => fn ($q) => $q->whereHas('curso', fn ($c) => $c->where('anio', $anio))
                ->with(['curso:id,anio,nivel,letra', 'docente:id,name'])])
            ->orderBy('nombre')
            ->get();

        return $asignaturas->map(function (Asignatura $a) {
            $cursos = Curso::ordenar($a->asignaciones->pluck('curso')->filter())
                ->map(function (Curso $c) use ($a) {
                    $asig = $a->asignaciones->firstWhere('curso_id', $c->id);

                    return [
                        'id' => $c->id,
                        'nombre' => $c->nombre,
                        'docente' => $asig->docente?->name,
                        'horas_semanales' => $asig->horas_semanales,
                    ];
                });

            return collect($a->toArray())->except('asignaciones')->put('cursos', $cursos)->all();
        });
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
