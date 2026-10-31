<?php

namespace App\Http\Controllers\Emocional;

use App\Http\Controllers\Controller;
use App\Models\AlertaBienestar;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\IntervencionBienestar;
use App\Models\Matricula;
use App\Support\Permisos;
use App\Support\Socioemocional;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Seguimiento individual de bienestar (confidencial, "emocional.ver") y protocolo DEC.
 * El docente con "emocional.reportar" solo activa el protocolo para estudiantes de sus
 * cursos y ve el estado de lo que reportó, sin el detalle del seguimiento.
 */
class AlertaController extends Controller
{
    private const ESTUDIANTE = 'estudiante:id,run,nombres,apellido_paterno,apellido_materno';

    public function index(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);

        $alertas = AlertaBienestar::where('anio', $anio)
            ->with([self::ESTUDIANTE, 'responsable:id,name', 'reportadoPor:id,name'])
            ->withCount('intervenciones')
            ->withMax('intervenciones', 'fecha')
            ->when($request->estado === 'abiertas', fn ($q) => $q->abiertas())
            ->when($request->estado && $request->estado !== 'abiertas', fn ($q) => $q->where('estado', $request->estado))
            ->when($request->tipo, fn ($q, $t) => $q->where('tipo', $t))
            ->when($request->curso_id, fn ($q, $id) => $q->whereHas('estudiante.matriculas', fn ($m) => $m->where('curso_id', $id)->activas()))
            ->when($request->estudiante_id, fn ($q, $id) => $q->where('estudiante_id', $id))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->get();

        $cursos = self::cursosDelAnio($alertas->pluck('estudiante_id'), $anio);

        return $alertas->map(fn (AlertaBienestar $a) => $a->toArray() + ['curso' => $cursos[$a->estudiante_id] ?? null])->values();
    }

    public function show(AlertaBienestar $alerta)
    {
        $alerta->load([self::ESTUDIANTE, 'responsable:id,name', 'reportadoPor:id,name',
            'intervenciones' => fn ($q) => $q->with('autor:id,name')->orderByDesc('fecha')->orderByDesc('id')]);

        return $alerta->toArray() + ['curso' => self::cursosDelAnio([$alerta->estudiante_id], $alerta->anio)[$alerta->estudiante_id] ?? null];
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $alerta = AlertaBienestar::create($datos + ['reportado_por_id' => $request->user()->id]);

        return response()->json($this->show($alerta), 201);
    }

    public function update(Request $request, AlertaBienestar $alerta)
    {
        $alerta->update($this->validar($request, $alerta));

        return $this->show($alerta);
    }

    public function destroy(AlertaBienestar $alerta)
    {
        $alerta->delete();

        return response()->json(['message' => 'Seguimiento eliminado']);
    }

    // ── Bitácora de intervenciones ──

    public function storeIntervencion(Request $request, AlertaBienestar $alerta)
    {
        $alerta->intervenciones()->create($this->validarIntervencion($request) + ['autor_id' => $request->user()->id]);

        return response()->json($this->show($alerta), 201);
    }

    public function updateIntervencion(Request $request, IntervencionBienestar $intervencion)
    {
        $intervencion->update($this->validarIntervencion($request));

        return $this->show($intervencion->alerta);
    }

    public function destroyIntervencion(IntervencionBienestar $intervencion)
    {
        $alerta = $intervencion->alerta;
        $intervencion->delete();

        return $this->show($alerta);
    }

    // ── Protocolo DEC desde el aula ("emocional.reportar") ──

    /** Estudiantes que puede reportar: los de sus cursos (o todos, con "emocional.ver"). */
    public function estudiantes(Request $request)
    {
        $anio = (int) now()->year;
        $texto = trim((string) $request->q);
        $cursos = Curso::visiblesPara($request->user(), Permisos::de('emocional', Permisos::VER))->where('anio', $anio)->pluck('id');

        return Matricula::activas()->whereIn('curso_id', $cursos)
            ->when($request->curso_id, fn ($q, $id) => $q->where('curso_id', $id))
            ->whereHas('estudiante', fn ($q) => $q->when($texto !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombres', 'like', "%{$texto}%")->orWhere('apellido_paterno', 'like', "%{$texto}%")
                ->orWhere('apellido_materno', 'like', "%{$texto}%"))))
            ->with('estudiante:id,run,nombres,apellido_paterno,apellido_materno', 'curso:id,nivel,letra')
            ->orderBy('curso_id')->orderBy('numero_lista')->limit(60)->get()
            ->map(fn (Matricula $m) => ['id' => $m->estudiante_id, 'nombre' => $m->estudiante->nombre_completo, 'curso' => $m->curso->nombre, 'curso_id' => $m->curso_id]);
    }

    /** Activa el protocolo: alerta de desregulación en fase de contención, a cargo del equipo. */
    public function protocolo(Request $request)
    {
        $datos = $request->validate([
            'estudiante_id' => ['required', 'integer', 'exists:estudiantes,id,deleted_at,NULL'],
            'intensidad' => ['required', Rule::in(array_keys(Socioemocional::INTENSIDADES))],
            'descripcion' => ['required', 'string', 'max:3000'],
            'lugar' => ['nullable', 'string', 'max:150'],
            'familia_contactada' => ['nullable', 'boolean'],
        ], [], ['descripcion' => 'descripción de la situación']);

        $this->puedeReportar($request, $datos['estudiante_id']);

        $alerta = AlertaBienestar::create($datos + [
            'anio' => (int) now()->year,
            'tipo' => 'desregulacion',
            'estado' => 'activa',
            'fase' => 'contencion',
            'fecha' => today(),
            'reportado_por_id' => $request->user()->id,
            'familia_contactada' => (bool) ($datos['familia_contactada'] ?? false),
        ]);

        return response()->json(['id' => $alerta->id, 'message' => 'Protocolo DEC activado: el equipo de convivencia y orientación lo verá en Seguimiento.'], 201);
    }

    /** Lo que reportó quien consulta: solo estado y fase, sin la bitácora confidencial. */
    public function misReportes(Request $request)
    {
        $alertas = AlertaBienestar::where('reportado_por_id', $request->user()->id)->where('anio', (int) now()->year)
            ->with(self::ESTUDIANTE)->orderByDesc('fecha')->orderByDesc('id')->limit(50)->get();
        $cursos = self::cursosDelAnio($alertas->pluck('estudiante_id'), (int) now()->year);

        return $alertas->map(fn (AlertaBienestar $a) => [
            'id' => $a->id, 'fecha' => $a->fecha->format('Y-m-d'), 'intensidad' => $a->intensidad,
            'estado' => $a->estado, 'fase' => $a->fase, 'estudiante' => $a->estudiante?->nombre_completo,
            'curso' => $cursos[$a->estudiante_id]['nombre'] ?? null,
        ]);
    }

    /** estudiante_id => ['id', 'nombre'] del curso de su matrícula activa en el año. */
    public static function cursosDelAnio($estudianteIds, int $anio): array
    {
        return Matricula::whereIn('estudiante_id', $estudianteIds)->activas()
            ->whereHas('curso', fn ($c) => $c->where('anio', $anio))->with('curso')->get()
            ->mapWithKeys(fn (Matricula $m) => [$m->estudiante_id => ['id' => $m->curso->id, 'nombre' => $m->curso->nombre]])
            ->all();
    }

    private function puedeReportar(Request $request, int $estudianteId): void
    {
        $matricula = Matricula::delAnio($estudianteId, (int) now()->year);
        abort_unless($matricula, 422, 'El estudiante no tiene matrícula activa este año.');
        abort_unless(
            Curso::visiblesPara($request->user(), Permisos::de('emocional', Permisos::VER))->whereKey($matricula->curso_id)->exists(),
            403, 'El estudiante no es de un curso de su carga o jefatura.'
        );
    }

    private function validar(Request $request, ?AlertaBienestar $alerta = null): array
    {
        $datos = $request->validate([
            'estudiante_id' => [$alerta ? 'exclude' : 'required', 'integer', 'exists:estudiantes,id,deleted_at,NULL'],
            'tipo' => ['required', Rule::in(array_keys(Socioemocional::TIPOS_ALERTA))],
            'intensidad' => ['required', Rule::in(array_keys(Socioemocional::INTENSIDADES))],
            'estado' => ['required', Rule::in(array_keys(Socioemocional::ESTADOS_ALERTA))],
            'fase' => ['nullable', 'required_if:tipo,desregulacion', Rule::in(array_keys(Socioemocional::FASES))],
            'fecha' => ['required', 'date'],
            'descripcion' => ['required', 'string', 'max:3000'],
            'lugar' => ['nullable', 'string', 'max:150'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id,deleted_at,NULL'],
            'equipo' => ['nullable', 'string', 'max:200'],
            'familia_contactada' => ['nullable', 'boolean'],
            'proxima_revision' => ['nullable', 'date', 'after_or_equal:fecha'],
            'derivacion' => ['nullable', 'required_if:estado,derivacion', 'string', 'max:300'],
            'fecha_cierre' => ['nullable', 'required_if:estado,cerrada', 'date', 'after_or_equal:fecha'],
            'motivo_cierre' => ['nullable', 'required_if:estado,cerrada', 'string', 'max:300'],
        ], [
            'fase.required_if' => 'Indique la fase del protocolo DEC.',
            'derivacion.required_if' => 'Indique a qué red o profesional se deriva.',
            'fecha_cierre.required_if' => 'Indique la fecha de cierre.',
            'motivo_cierre.required_if' => 'Indique el motivo de cierre.',
        ], ['descripcion' => 'descripción', 'proxima_revision' => 'próxima revisión', 'responsable_id' => 'responsable']);

        $datos['familia_contactada'] = (bool) ($datos['familia_contactada'] ?? false);
        if ($datos['tipo'] !== 'desregulacion') {
            $datos['fase'] = null;
        }
        if ($datos['estado'] !== 'cerrada') {
            $datos['fecha_cierre'] = $datos['motivo_cierre'] = null;
        }

        // El año escolar lo da la fecha; el estudiante debe estar matriculado ese año
        $datos['anio'] = Carbon::parse($datos['fecha'])->year;
        $estudianteId = $alerta?->estudiante_id ?? $datos['estudiante_id'];
        if (! Matricula::delAnio($estudianteId, $datos['anio'])) {
            throw ValidationException::withMessages(['estudiante_id' => "El estudiante no tiene matrícula activa en {$datos['anio']}."]);
        }

        return $datos;
    }

    private function validarIntervencion(Request $request): array
    {
        return $request->validate([
            'fecha' => ['required', 'date'],
            'tipo' => ['required', Rule::in(array_keys(Socioemocional::TIPOS_INTERVENCION))],
            'descripcion' => ['required', 'string', 'max:3000'],
            'acuerdos' => ['nullable', 'string', 'max:2000'],
        ], [], ['descripcion' => 'descripción']);
    }
}
