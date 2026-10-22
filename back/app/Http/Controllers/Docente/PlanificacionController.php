<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Planificacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Planificaciones de clase del docente que consulta (solo las propias). */
class PlanificacionController extends Controller
{
    use ValidaCargaDocente;

    private const RELACIONES = [
        'asignacion.curso', 'asignacion.asignatura:id,nombre,codigo,color',
        'objetivo:id,codigo,unidad,texto,nivel,es_demo',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);

        return Planificacion::with(self::RELACIONES)
            ->where('docente_id', $request->user()->id)
            ->whereBetween('fecha', [$request->desde, $request->hasta])
            ->when($request->asignacion_id, fn ($q, $id) => $q->where('asignacion_id', $id))
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->get();
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request) + ['docente_id' => $request->user()->id];

        return Planificacion::create($datos)->load(self::RELACIONES);
    }

    public function update(Request $request, Planificacion $planificacion)
    {
        $this->esPropia($request, $planificacion);
        $planificacion->update($this->validar($request));

        return $planificacion->load(self::RELACIONES);
    }

    public function destroy(Request $request, Planificacion $planificacion)
    {
        $this->esPropia($request, $planificacion);
        $planificacion->delete();

        return response()->json(['message' => 'Planificación eliminada']);
    }

    private function esPropia(Request $request, Planificacion $planificacion): void
    {
        abort_unless($planificacion->docente_id === $request->user()->id, 403, 'Esta planificación es de otro docente.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'asignacion_id' => ['required', 'integer', 'exists:asignaciones,id,deleted_at,NULL'],
            'objetivo_aprendizaje_id' => ['nullable', 'integer', 'exists:objetivos_aprendizaje,id,deleted_at,NULL'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'duracion_minutos' => ['nullable', 'integer', 'between:15,360'],
            'titulo' => ['required', 'string', 'max:200'],
            'inicio' => ['nullable', 'string', 'max:5000'],
            'desarrollo' => ['nullable', 'string', 'max:5000'],
            'cierre' => ['nullable', 'string', 'max:5000'],
            'evidencia' => ['nullable', 'string', 'max:200'],
            'recursos' => ['nullable', 'string', 'max:5000'],
            'adecuaciones' => ['nullable', 'string', 'max:5000'],
            'estado' => ['nullable', Rule::in(Planificacion::ESTADOS)],
        ], [], [
            'asignacion_id' => 'curso y asignatura', 'objetivo_aprendizaje_id' => 'OA', 'titulo' => 'título',
            'hora_inicio' => 'hora', 'duracion_minutos' => 'duración',
        ]);

        $this->validarAsignacionYOa($request, $datos);

        $datos['duracion_minutos'] ??= 90;
        $datos['estado'] ??= 'planificada';

        return $datos;
    }
}
