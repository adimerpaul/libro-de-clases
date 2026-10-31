<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Instrumento;
use App\Models\LibretaEntrada;
use App\Models\Matricula;
use App\Models\User;
use App\Models\VinculoApoderado;
use App\Support\Libreta;
use App\Support\Permisos;
use App\Support\ResumenEstudiante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Libreta del Estudiante (ui3 libretaPage): identificación, comunicación bidireccional
 * establecimiento ↔ familia y organización (calendario de evaluaciones con sus criterios).
 * Acceso: el apoderado, a sus hijos vinculados (escribe como "familia"); el personal con
 * "libreta.ver", a todos; el docente, a los estudiantes de sus cursos (ambos como "establecimiento").
 */
class LibretaController extends Controller
{
    /** Estudiantes cuya libreta puede abrir el usuario (selector de la página). */
    public function estudiantes(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $user = $request->user();
        $anio = (int) now()->year;

        $hijos = VinculoApoderado::where('user_id', $user->id)->pluck('parentesco', 'estudiante_id');
        $esPersonal = $user->tienePermiso(Permisos::VER_LIBRETAS) || $user->tienePermiso(Permisos::DICTAR_CLASES);

        $matriculas = Matricula::activas()
            ->whereHas('curso', fn ($q) => $q->where('anio', $anio))
            ->with(['estudiante', 'curso'])
            ->where(fn ($q) => $q
                ->whereIn('estudiante_id', $hijos->keys())
                ->when($esPersonal, fn ($w) => $w->orWhereIn('curso_id', $this->cursosVisibles($user, $anio))))
            ->when($request->q, fn ($q, $texto) => $q->whereHas('estudiante', fn ($e) => $e
                ->where('nombres', 'like', "%{$texto}%")->orWhere('apellido_paterno', 'like', "%{$texto}%")
                ->orWhere('apellido_materno', 'like', "%{$texto}%")->orWhere('run', 'like', "%{$texto}%")))
            ->get()
            ->filter->estudiante;

        // Hijos vinculados sin matrícula este año también se listan (para ver su libreta)
        $sinMatricula = Estudiante::whereIn('id', $hijos->keys())->whereNotIn('id', $matriculas->pluck('estudiante_id'))->get();

        return $matriculas->map(fn (Matricula $m) => [
            'id' => $m->estudiante_id,
            'nombre' => $m->estudiante->nombre_completo,
            'curso' => $m->curso->nombre,
            'hijo' => $hijos->has($m->estudiante_id),
        ])->concat($sinMatricula->map(fn (Estudiante $e) => ['id' => $e->id, 'nombre' => $e->nombre_completo, 'curso' => null, 'hijo' => true]))
            ->sortBy([['hijo', 'desc'], ['curso', 'asc'], ['nombre', 'asc']])
            ->take(60)->values();
    }

    public function show(Request $request, Estudiante $estudiante)
    {
        $user = $request->user();
        $origen = $this->acceso($user, $estudiante);
        $anio = (int) now()->year;
        $matricula = Matricula::delAnio($estudiante->id, $anio)?->load('curso.profesorJefe:id,name,email');

        // Abrir la libreta deja leídas las entradas del otro lado (trazabilidad de lectura)
        LibretaEntrada::where('estudiante_id', $estudiante->id)
            ->where('origen', '!=', $origen)->whereNull('leido_en')
            ->update(['leido_en' => now(), 'leido_por' => $user->id]);

        $notas = ResumenEstudiante::notas($estudiante->id, $anio);
        $asistencia = ResumenEstudiante::asistencia($estudiante->id);
        $vinculo = VinculoApoderado::where('estudiante_id', $estudiante->id)->with('user:id,name')->first();

        return [
            'rol' => $origen,
            'estudiante' => [
                'id' => $estudiante->id,
                'nombre' => $estudiante->nombre_completo,
                'nombres' => $estudiante->nombres,
                'apellido' => $estudiante->apellido_paterno,
                'run' => $estudiante->run,
                'fecha_nacimiento' => $estudiante->fecha_nacimiento?->format('Y-m-d'),
                'direccion' => $estudiante->direccion,
                'pie' => $origen === Libreta::FAMILIA || $user->tienePermiso(Permisos::de('pie', Permisos::VER)) ? (bool) $estudiante->pie : null,
                'contacto_emergencia' => [
                    'nombre' => $estudiante->apoderado_nombre ?: $vinculo?->user?->name,
                    'telefono' => $estudiante->apoderado_telefono,
                    'email' => $estudiante->apoderado_email,
                ],
            ],
            'curso' => $matricula ? [
                'id' => $matricula->curso->id,
                'nombre' => $matricula->curso->nombre,
                'anio' => $matricula->curso->anio,
                'numero_lista' => $matricula->numero_lista,
                'profesor_jefe' => $matricula->curso->profesorJefe?->name,
                'profesor_jefe_email' => $matricula->curso->profesorJefe?->email,
            ] : null,
            'establecimiento' => config('establecimiento'),
            'resumen' => [
                'asistencia' => $asistencia['porcentaje'],
                'atrasos' => $asistencia['atrasos'],
                'dias_habiles' => $asistencia['dias_habiles'],
                'dias_con_entrada' => $asistencia['dias_con_entrada'],
                'promedio' => $notas['promedio'],
            ],
            'entradas' => LibretaEntrada::where('estudiante_id', $estudiante->id)
                ->with(['autor:id,name', 'respondidoPor:id,name', 'leidoPor:id,name'])
                ->latest()->latest('id')->get()
                ->map(fn (LibretaEntrada $e) => $this->datos($e)),
            'calendario' => $matricula ? $this->calendario($matricula->curso, $estudiante->id) : [],
            'tipos' => Libreta::catalogo(),
        ];
    }

    public function store(Request $request, Estudiante $estudiante)
    {
        $user = $request->user();
        $origen = $this->acceso($user, $estudiante);
        $tipo = $request->input('tipo');
        $conFecha = in_array($tipo, ['citacion', 'justificacion', 'retiro', 'autorizacion'], true);
        $conHora = in_array($tipo, ['citacion', 'retiro'], true);

        $datos = $request->validate([
            'tipo' => ['required', Rule::in(Libreta::tiposDe($origen))],
            'asunto' => ['required', 'string', 'max:150'],
            'mensaje' => [$tipo === 'comunicacion' ? 'required' : 'nullable', 'string', 'max:3000'],
            'fecha_evento' => [$conFecha ? 'required' : 'nullable', 'date'],
            'hora_evento' => [$conHora ? 'required' : 'nullable', 'date_format:H:i'],
            'requiere_confirmacion' => ['boolean'],
        ], [], ['fecha_evento' => 'fecha', 'hora_evento' => 'hora', 'tipo' => 'tipo de entrada']);

        // Citaciones, autorizaciones y acuerdos esperan respuesta de la familia; una comunicación
        // del establecimiento puede pedir además confirmación de lectura
        $requiere = $origen === Libreta::ESTABLECIMIENTO
            && (Libreta::respuestas($datos['tipo']) || ! empty($datos['requiere_confirmacion']));

        $entrada = LibretaEntrada::create([
            'estudiante_id' => $estudiante->id,
            'autor_id' => $user->id,
            'origen' => $origen,
            'tipo' => $datos['tipo'],
            'asunto' => $datos['asunto'],
            'mensaje' => $datos['mensaje'] ?? null,
            'fecha_evento' => $datos['fecha_evento'] ?? null,
            'hora_evento' => $datos['hora_evento'] ?? null,
            'requiere_respuesta' => $requiere,
        ]);

        return response()->json($this->datos($entrada->load('autor:id,name')), 201);
    }

    /** La familia responde (confirma, autoriza, firma o confirma lectura) una entrada del establecimiento. */
    public function responder(Request $request, LibretaEntrada $entrada)
    {
        $user = $request->user();
        abort_unless($this->acceso($user, $entrada->estudiante) === Libreta::FAMILIA, 403, 'Solo la familia del estudiante puede responder.');
        abort_unless($entrada->origen === Libreta::ESTABLECIMIENTO && $entrada->requiere_respuesta, 422, 'Esta entrada no requiere respuesta.');
        abort_if($entrada->respuesta !== null, 422, 'Esta entrada ya fue respondida.');

        $validas = array_keys(Libreta::respuestas($entrada->tipo)) ?: ['enterado'];
        $datos = $request->validate([
            'respuesta' => ['required', Rule::in($validas)],
            'comentario' => ['nullable', 'string', 'max:300'],
        ]);

        $entrada->update([
            'respuesta' => $datos['respuesta'],
            'comentario_respuesta' => $datos['comentario'] ?? null,
            'respondido_por' => $user->id,
            'respondido_en' => now(),
            'leido_en' => $entrada->leido_en ?? now(),
            'leido_por' => $entrada->leido_por ?? $user->id,
        ]);

        return $this->datos($entrada->load(['autor:id,name', 'respondidoPor:id,name', 'leidoPor:id,name']));
    }

    /**
     * "familia" si es apoderado vinculado; "establecimiento" si es personal autorizado
     * (libreta.ver, o docente/profesor jefe de su curso este año). Si no, 404.
     */
    private function acceso(User $user, Estudiante $estudiante): string
    {
        if ($user->tienePermiso(Permisos::VER_FAMILIA)
            && VinculoApoderado::where(['user_id' => $user->id, 'estudiante_id' => $estudiante->id])->exists()) {
            return Libreta::FAMILIA;
        }

        if ($user->tienePermiso(Permisos::VER_LIBRETAS)) {
            return Libreta::ESTABLECIMIENTO;
        }

        $anio = (int) now()->year;
        $cursoId = Matricula::delAnio($estudiante->id, $anio)?->curso_id;
        abort_unless($cursoId && $user->tienePermiso(Permisos::DICTAR_CLASES)
            && in_array($cursoId, $this->cursosVisibles($user, $anio), true), 404, 'No tiene acceso a la libreta de este estudiante.');

        return Libreta::ESTABLECIMIENTO;
    }

    /** Cursos del año: todos con "libreta.ver"; el docente, los de su carga o jefatura. */
    private function cursosVisibles(User $user, int $anio): array
    {
        return Curso::where('anio', $anio)->visiblesPara($user, Permisos::VER_LIBRETAS)->pluck('id')->all();
    }

    /**
     * Evaluaciones listas del curso (un mes atrás y dos meses adelante) con los indicadores
     * del OA: la familia conoce de antemano cómo se evaluará. Los borradores no se muestran.
     */
    private function calendario(Curso $curso, int $estudianteId): array
    {
        $instrumentos = Instrumento::whereHas('asignacion', fn ($q) => $q->where('curso_id', $curso->id))
            ->where('estado', 'listo')
            ->whereNotNull('fecha_aplicacion')
            ->whereBetween('fecha_aplicacion', [today()->subDays(30), today()->addDays(60)])
            ->with(['asignacion.asignatura:id,nombre,color', 'objetivo:id,codigo,texto,indicadores'])
            ->orderBy('fecha_aplicacion')
            ->get();

        $conNota = Calificacion::where('estudiante_id', $estudianteId)->whereIn('instrumento_id', $instrumentos->pluck('id'))->pluck('instrumento_id')->all();

        return $instrumentos->map(fn (Instrumento $i) => [
            'id' => $i->id,
            'nombre' => $i->nombre,
            'tipo' => $i->tipo,
            'fecha' => $i->fecha_aplicacion->format('Y-m-d'),
            'asignatura' => $i->asignacion?->asignatura?->nombre,
            'color' => $i->asignacion?->asignatura?->color,
            'estado' => in_array($i->id, $conNota, true) ? 'evaluada' : ($i->fecha_aplicacion->isBefore(today()) ? 'por_calificar' : 'proxima'),
            'oa' => $i->objetivo ? [
                'codigo' => $i->objetivo->codigo,
                'texto' => $i->objetivo->texto,
                'indicadores' => $i->objetivo->indicadores ?? [],
            ] : null,
        ])->values()->all();
    }

    private function datos(LibretaEntrada $e): array
    {
        return [
            'id' => $e->id,
            'origen' => $e->origen,
            'tipo' => $e->tipo,
            'asunto' => $e->asunto,
            'mensaje' => $e->mensaje,
            'fecha_evento' => $e->fecha_evento?->format('Y-m-d'),
            'hora_evento' => $e->hora_evento ? substr($e->hora_evento, 0, 5) : null,
            'autor' => $e->autor?->name,
            'creado_en' => $e->created_at?->format('Y-m-d H:i'),
            'requiere_respuesta' => $e->requiere_respuesta,
            'respuesta' => $e->respuesta,
            'comentario_respuesta' => $e->comentario_respuesta,
            'respondido_por' => $e->respondidoPor?->name,
            'respondido_en' => $e->respondido_en?->format('Y-m-d H:i'),
            'leido_por' => $e->leidoPor?->name,
            'leido_en' => $e->leido_en?->format('Y-m-d H:i'),
        ];
    }
}
