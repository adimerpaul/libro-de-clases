<?php

namespace App\Http\Controllers\Pie;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\PieCaso;
use App\Models\User;
use App\Support\Permisos;
use App\Support\Pie;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Casos del Programa de Integración Escolar: uno por estudiante y año escolar. */
class CasoController extends Controller
{
    private const ESTUDIANTE = 'estudiante:id,run,nombres,apellido_paterno,apellido_materno,fecha_nacimiento,sexo';

    /** Catálogos del formulario y profesionales que pueden ser responsables del caso. */
    public function catalogos()
    {
        $responsables = User::orderBy('name')->get(['id', 'name', 'email'])
            ->filter(fn (User $u) => $u->tienePermiso(Permisos::de('pie', Permisos::EDITAR)))
            ->values();

        return Pie::catalogos() + ['responsables' => $responsables, 'anio' => (int) now()->year];
    }

    public function index(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);

        $casos = PieCaso::where('anio', $anio)
            ->with([self::ESTUDIANTE, 'responsable:id,name'])
            ->withCount(['documentos', 'seguimientos'])
            ->withMax('seguimientos', 'fecha')
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->tipo_nee, fn ($q, $tipo) => $q->where('tipo_nee', $tipo))
            ->when($request->curso_id, fn ($q, $id) => $q->whereHas('estudiante.matriculas', fn ($m) => $m->where('curso_id', $id)->activas()))
            ->get();

        $cursos = $this->cursosDelAnio($casos->pluck('estudiante_id'), $anio);

        return $casos
            ->map(fn (PieCaso $c) => $c->toArray() + ['curso' => $cursos[$c->estudiante_id] ?? null])
            ->sortBy(fn ($c) => $c['estudiante']['nombre_completo'] ?? '', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /** Expediente completo: ficha del estudiante, documentos y bitácora. */
    public function show(PieCaso $caso)
    {
        $caso->load([
            'estudiante', 'responsable:id,name,email',
            'documentos' => fn ($q) => $q->with('autor:id,name')->orderByDesc('fecha')->latest(),
            'seguimientos' => fn ($q) => $q->with('autor:id,name')->orderByDesc('fecha')->latest(),
        ]);

        $historial = PieCaso::where('estudiante_id', $caso->estudiante_id)->where('id', '!=', $caso->id)
            ->orderByDesc('anio')->get(['id', 'anio', 'estado', 'diagnostico', 'tipo_nee']);

        return $caso->toArray() + [
            'curso' => $this->cursosDelAnio([$caso->estudiante_id], $caso->anio)[$caso->estudiante_id] ?? null,
            'historial' => $historial,
        ];
    }

    public function store(Request $request)
    {
        $caso = PieCaso::create($this->validar($request));

        return response()->json($this->show($caso), 201);
    }

    public function update(Request $request, PieCaso $caso)
    {
        $caso->update($this->validar($request, $caso));

        return $this->show($caso);
    }

    public function destroy(PieCaso $caso)
    {
        $caso->delete();

        return response()->json(['message' => 'Caso PIE eliminado']);
    }

    /** Estudiantes con matrícula activa en el año y sin caso PIE ese año (candidatos a ingresar). */
    public function estudiantes(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $texto = trim((string) $request->q);

        $estudiantes = Estudiante::whereHas('matriculas', fn ($m) => $m->activas()->whereHas('curso', fn ($c) => $c->where('anio', $anio)))
            ->whereDoesntHave('pieCasos', fn ($q) => $q->where('anio', $anio))
            ->when($texto !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombres', 'like', "%{$texto}%")
                ->orWhere('apellido_paterno', 'like', "%{$texto}%")
                ->orWhere('apellido_materno', 'like', "%{$texto}%")
                ->orWhere('run', 'like', '%'.preg_replace('/[^0-9kK]/', '', $texto).'%')))
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombres')
            ->limit(30)
            ->get(['id', 'run', 'nombres', 'apellido_paterno', 'apellido_materno']);

        $cursos = $this->cursosDelAnio($estudiantes->pluck('id'), $anio);

        return $estudiantes->map(fn (Estudiante $e) => $e->toArray() + ['curso' => $cursos[$e->id] ?? null]);
    }

    /** estudiante_id => ['id', 'nombre'] del curso de su matrícula activa en el año. */
    private function cursosDelAnio($estudianteIds, int $anio): array
    {
        return Matricula::whereIn('estudiante_id', $estudianteIds)->activas()
            ->whereHas('curso', fn ($c) => $c->where('anio', $anio))
            ->with('curso')
            ->get()
            ->mapWithKeys(fn (Matricula $m) => [$m->estudiante_id => ['id' => $m->curso->id, 'nombre' => $m->curso->nombre]])
            ->all();
    }

    private function validar(Request $request, ?PieCaso $caso = null): array
    {
        $datos = $request->validate([
            'estudiante_id' => [$caso ? 'exclude' : 'required', 'integer', 'exists:estudiantes,id,deleted_at,NULL'],
            'anio' => [$caso ? 'exclude' : 'required', 'integer', 'between:2000,2100'],
            'estado' => ['required', Rule::in(array_keys(Pie::ESTADOS))],
            'diagnostico' => ['required', Rule::in(array_keys(Pie::DIAGNOSTICOS))],
            'diagnostico_detalle' => ['nullable', 'string', 'max:500'],
            'fecha_ingreso' => ['required', 'date'],
            'fecha_evaluacion' => ['nullable', 'date'],
            'fecha_reevaluacion' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id,deleted_at,NULL'],
            'equipo' => ['nullable', 'array', 'max:15'],
            'equipo.*.especialidad' => ['required', Rule::in(Pie::ESPECIALIDADES)],
            'equipo.*.nombre' => ['required', 'string', 'max:120'],
            'apoyos' => ['nullable', 'array'],
            'apoyos.*' => ['distinct', Rule::in(Pie::APOYOS)],
            'horas_semanales' => ['nullable', 'integer', 'between:0,44'],
            'fortalezas' => ['nullable', 'string', 'max:3000'],
            'barreras' => ['nullable', 'string', 'max:3000'],
            'necesidades_apoyo' => ['nullable', 'string', 'max:3000'],
            'acuerdos_familia' => ['nullable', 'string', 'max:3000'],
            'observaciones' => ['nullable', 'string', 'max:3000'],
            'fecha_egreso' => ['nullable', 'required_if:estado,egresado', 'date', 'after_or_equal:fecha_ingreso'],
            'motivo_egreso' => ['nullable', 'required_if:estado,egresado', 'string', 'max:300'],
        ], [
            'fecha_egreso.required_if' => 'Indique la fecha de egreso.',
            'motivo_egreso.required_if' => 'Indique el motivo del egreso o alta.',
        ], [
            'estudiante_id' => 'estudiante', 'diagnostico' => 'diagnóstico', 'fecha_ingreso' => 'fecha de ingreso',
            'fecha_evaluacion' => 'fecha de evaluación', 'fecha_reevaluacion' => 'fecha de reevaluación',
            'responsable_id' => 'responsable', 'horas_semanales' => 'horas semanales de apoyo',
            'equipo.*.especialidad' => 'especialidad', 'equipo.*.nombre' => 'nombre del profesional',
        ]);

        if (! $caso) {
            if (PieCaso::where('estudiante_id', $datos['estudiante_id'])->where('anio', $datos['anio'])->exists()) {
                throw ValidationException::withMessages(['estudiante_id' => "El estudiante ya tiene un caso PIE en {$datos['anio']}."]);
            }
            if (! Matricula::delAnio($datos['estudiante_id'], $datos['anio'])) {
                throw ValidationException::withMessages(['estudiante_id' => "El estudiante no tiene matrícula activa en {$datos['anio']}."]);
            }
        }

        if ($datos['responsable_id'] ?? null) {
            if (! User::find($datos['responsable_id'])->tienePermiso(Permisos::de('pie', Permisos::EDITAR))) {
                throw ValidationException::withMessages(['responsable_id' => 'El responsable debe tener permiso para editar casos PIE.']);
            }
        }

        $datos['tipo_nee'] = Pie::tipoNee($datos['diagnostico']);
        $datos['equipo'] = array_values($datos['equipo'] ?? []);
        $datos['apoyos'] = array_values($datos['apoyos'] ?? []);
        if ($datos['estado'] !== 'egresado') {
            $datos['fecha_egreso'] = $datos['motivo_egreso'] = null;
        }

        return $datos;
    }
}
