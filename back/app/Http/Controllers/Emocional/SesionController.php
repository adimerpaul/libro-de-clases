<?php

namespace App\Http\Controllers\Emocional;

use App\Http\Controllers\Controller;
use App\Models\Matricula;
use App\Models\SesionSocioemocional;
use App\Support\Niveles;
use App\Support\Socioemocional;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Sesiones y actividades socioemocionales por curso. */
class SesionController extends Controller
{
    private const RELACIONES = ['curso:id,anio,nivel,letra', 'responsable:id,name', 'objetivo:id,codigo,texto,nivel'];

    public function index(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $texto = trim((string) $request->q);

        return SesionSocioemocional::with(self::RELACIONES)
            ->whereHas('curso', fn ($q) => $q->where('anio', $anio))
            ->when($request->competencia, fn ($q, $c) => $q->where('competencia', $c))
            ->when($request->curso_id, fn ($q, $id) => $q->where('curso_id', $id))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when($request->tramo, fn ($q, $t) => $q->whereHas('curso', fn ($c) => $c->whereIn('nivel', $this->nivelesDelTramo($t))))
            ->when($texto !== '', fn ($q) => $q->where(fn ($w) => $w->where('titulo', 'like', "%{$texto}%")->orWhere('descripcion', 'like', "%{$texto}%")))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $sesion = SesionSocioemocional::create($this->validar($request));

        return response()->json($sesion->load(self::RELACIONES), 201);
    }

    public function update(Request $request, SesionSocioemocional $sesion)
    {
        $sesion->update($this->validar($request));

        return $sesion->load(self::RELACIONES);
    }

    public function destroy(SesionSocioemocional $sesion)
    {
        $sesion->delete();

        return response()->json(['message' => 'Sesión eliminada']);
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL'],
            'titulo' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(Socioemocional::TIPOS_SESION)],
            'competencia' => ['required', Rule::in(array_keys(Socioemocional::COMPETENCIAS))],
            'fecha' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha'],
            'estado' => ['required', Rule::in(array_keys(Socioemocional::ESTADOS_SESION))],
            'participantes' => ['nullable', 'integer', 'between:0,2000'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id,deleted_at,NULL'],
            'objetivo_aprendizaje_id' => ['nullable', 'integer', 'exists:objetivos_aprendizaje,id,deleted_at,NULL'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'evidencias' => ['nullable', 'string', 'max:2000'],
        ], [], ['curso_id' => 'curso', 'fecha_fin' => 'fecha de término', 'objetivo_aprendizaje_id' => 'OA']);

        // Sin participantes informados: la nómina activa del curso
        $datos['participantes'] ??= Matricula::activas()->where('curso_id', $datos['curso_id'])->count();

        return $datos;
    }

    private function nivelesDelTramo(string $tramo): array
    {
        return array_values(array_filter(Niveles::TODOS, fn ($n) => Socioemocional::tramo($n) === $tramo));
    }
}
