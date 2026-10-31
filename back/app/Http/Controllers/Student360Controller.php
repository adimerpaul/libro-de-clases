<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Instrumento;
use App\Models\Matricula;
use App\Models\PieCaso;
use App\Models\Planificacion;
use App\Support\Permisos;
use App\Support\Pie;
use App\Support\Student360;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Student 360: vista longitudinal del estudiante. Con "student360.ver" se ven todos
 * los cursos; un docente, los de su carga o jefatura. El dominio PIE (sensible) solo
 * se detalla con "pie.ver"; los demás ven únicamente si el estudiante tiene apoyo.
 */
class Student360Controller extends Controller
{
    /** Años, cursos visibles y asignaturas de cada uno (para los filtros). */
    public function filtros(Request $request)
    {
        $anio = $this->anio($request);
        $cursos = Curso::ordenar($this->cursos($request)->where('anio', $anio)
            ->with(['asignaciones' => fn ($q) => $q->with('asignatura:id,nombre,codigo,color')])
            ->withCount(['matriculas as estudiantes_count' => fn ($q) => $q->activas()])
            ->get());

        $asignaturas = $cursos->flatMap(fn ($c) => $c->asignaciones->pluck('asignatura'))
            ->filter()->unique('id')->sortBy('nombre')->values();

        return [
            'anio' => $anio,
            'anios' => $this->cursos($request)->distinct()->orderByDesc('anio')->pluck('anio'),
            'cursos' => $cursos->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'estudiantes_count' => $c->estudiantes_count,
                'asignaturas' => $c->asignaciones->pluck('asignatura_id')->values(),
            ]),
            'asignaturas' => $asignaturas->map->only('id', 'nombre', 'codigo', 'color')->values(),
        ];
    }

    /** Nómina filtrada (curso, asignatura, texto) con asistencia RFID, alertas y resumen. */
    public function index(Request $request)
    {
        $request->validate([
            'anio' => ['nullable', 'integer'],
            'curso_id' => ['nullable', 'integer'],
            'asignatura_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:80'],
            'alerta' => ['nullable', 'in:con,sin'],
        ]);
        $anio = $this->anio($request);

        $cursos = $this->cursos($request)->where('anio', $anio)
            ->when($request->curso_id, fn ($q, $id) => $q->whereKey($id))
            ->when($request->asignatura_id, fn ($q, $id) => $q->whereHas('asignaciones', fn ($q) => $q->where('asignatura_id', $id)))
            ->with(['profesorJefe:id,name', 'asignaciones' => fn ($q) => $q
                ->when($request->asignatura_id, fn ($q, $id) => $q->where('asignatura_id', $id))
                ->with(['asignatura:id,nombre,codigo,color', 'docente:id,name'])])
            ->get()->keyBy('id');

        $matriculas = Matricula::activas()
            ->whereIn('curso_id', $cursos->keys())
            ->with('estudiante:id,run,nombres,apellido_paterno,apellido_materno,sexo,fecha_nacimiento,pie')
            ->when($request->q, fn ($q, $t) => $q->whereHas('estudiante', fn ($e) => $e
                ->where('nombres', 'like', "%{$t}%")
                ->orWhere('apellido_paterno', 'like', "%{$t}%")
                ->orWhere('apellido_materno', 'like', "%{$t}%")
                ->orWhere('run', 'like', '%'.preg_replace('/[^0-9kK]/', '', $t).'%')))
            ->get();

        $asistencia = Student360::asistencia($matriculas, $anio);
        $posicion = Curso::ordenar($cursos->values())->pluck('id')->flip();
        $instrumentos = $this->instrumentosPorCurso($cursos);

        $filas = $matriculas->map(function (Matricula $m) use ($cursos, $asistencia, $instrumentos) {
            $curso = $cursos[$m->curso_id];
            $a = $asistencia[$m->estudiante_id];
            $asig = $curso->asignaciones->first();

            return [
                'id' => $m->estudiante_id,
                'matricula_id' => $m->id,
                'numero_lista' => $m->numero_lista,
                'nombre_completo' => $m->estudiante->nombre_completo,
                'nombres' => $m->estudiante->nombres,
                'apellido_paterno' => $m->estudiante->apellido_paterno,
                'run' => $m->estudiante->run,
                'sexo' => $m->estudiante->sexo,
                'pie' => (bool) $m->estudiante->pie,
                'curso' => ['id' => $curso->id, 'nombre' => $curso->nombre, 'nivel' => $curso->nivel],
                'asistencia' => $a,
                'alertas' => Student360::alertas($a),
                // Con filtro de asignatura: su docente y sus instrumentos en ese curso
                'asignatura' => request('asignatura_id') && $asig ? [
                    'docente' => $asig->docente?->name,
                    'instrumentos' => $instrumentos[$curso->id] ?? 0,
                ] : null,
            ];
        })
            ->when($request->alerta === 'con', fn ($c) => $c->filter(fn ($f) => $f['alertas']))
            ->when($request->alerta === 'sin', fn ($c) => $c->reject(fn ($f) => $f['alertas']))
            ->sortBy([
                fn ($a, $b) => $posicion[$a['curso']['id']] <=> $posicion[$b['curso']['id']],
                fn ($a, $b) => $a['numero_lista'] <=> $b['numero_lista'],
            ])
            ->values();

        $conDatos = $filas->pluck('asistencia.porcentaje')->filter(fn ($p) => $p !== null);

        return [
            'anio' => $anio,
            'resumen' => [
                'estudiantes' => $filas->count(),
                'mujeres' => $filas->where('sexo', 'F')->count(),
                'hombres' => $filas->where('sexo', 'M')->count(),
                'pie' => $filas->where('pie', true)->count(),
                'asistencia' => $conDatos->count() ? round($conDatos->avg(), 1) : null,
                'atrasos' => $filas->sum('asistencia.atrasos'),
                'criticos' => $filas->filter(fn ($f) => collect($f['alertas'])->contains('nivel', 'critica'))->count(),
                'en_riesgo' => $filas->filter(fn ($f) => $f['alertas'])->count(),
                'tramos' => [
                    'sobre_95' => $conDatos->filter(fn ($p) => $p >= 95)->count(),
                    'de_90_a_95' => $conDatos->filter(fn ($p) => $p >= 90 && $p < 95)->count(),
                    'de_85_a_90' => $conDatos->filter(fn ($p) => $p >= 85 && $p < 90)->count(),
                    'bajo_85' => $conDatos->filter(fn ($p) => $p < 85)->count(),
                    'sin_datos' => $filas->count() - $conDatos->count(),
                ],
            ],
            'cursos' => Curso::ordenar($cursos->values())->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'profesor_jefe' => $c->profesorJefe?->name,
                'docente' => $request->asignatura_id ? $c->asignaciones->first()?->docente?->name : null,
            ])->values(),
            'estudiantes' => $filas,
        ];
    }

    /** Ficha 360 de un estudiante: identificación, trayectoria, asistencia, asignaturas y PIE. */
    public function show(Request $request, int $estudiante)
    {
        $anio = $this->anio($request);
        $visibles = $this->cursos($request)->pluck('id');

        $est = Estudiante::whereHas('matriculas', fn ($q) => $q->whereIn('curso_id', $visibles))
            ->with(['matriculas' => fn ($q) => $q->with('curso.profesorJefe:id,name')])
            ->findOrFail($estudiante);

        $trayectoria = $est->matriculas
            ->sortByDesc(fn ($m) => [$m->curso?->anio, $m->estado === 'activa'])
            ->values();
        $actual = $trayectoria->first(fn ($m) => $m->curso?->anio === $anio && $m->estado === 'activa')
            ?? $trayectoria->first(fn ($m) => $m->curso?->anio === $anio)
            ?? $trayectoria->first();
        $anioVista = $actual?->curso?->anio ?? $anio;
        $curso = $actual?->curso;

        $asistencia = $curso ? Student360::asistencia(collect([$actual]), $anioVista)[$est->id] : null;

        return [
            'anio' => $anioVista,
            'estudiante' => array_merge($est->only([
                'id', 'run', 'nombres', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'fecha_nacimiento',
                'sexo', 'direccion', 'apoderado_nombre', 'apoderado_run', 'apoderado_telefono', 'apoderado_email',
                'pie', 'observaciones',
            ]), ['fecha_nacimiento' => $est->fecha_nacimiento?->format('Y-m-d')]),
            'matricula' => $actual ? [
                'id' => $actual->id,
                'numero_lista' => $actual->numero_lista,
                'estado' => $actual->estado,
                'fecha_matricula' => $actual->fecha_matricula?->format('Y-m-d'),
                'fecha_retiro' => $actual->fecha_retiro?->format('Y-m-d'),
                'motivo_retiro' => $actual->motivo_retiro,
                'curso' => $curso ? [
                    'id' => $curso->id, 'nombre' => $curso->nombre, 'nivel' => $curso->nivel, 'anio' => $curso->anio,
                    'profesor_jefe' => $curso->profesorJefe?->name,
                ] : null,
            ] : null,
            'trayectoria' => $trayectoria->map(fn ($m) => [
                'anio' => $m->curso?->anio,
                'curso' => $m->curso?->nombre,
                'curso_id' => $m->curso_id,
                'numero_lista' => $m->numero_lista,
                'estado' => $m->estado,
                'fecha_matricula' => $m->fecha_matricula?->format('Y-m-d'),
                'fecha_retiro' => $m->fecha_retiro?->format('Y-m-d'),
                'motivo_retiro' => $m->motivo_retiro,
            ]),
            'asistencia' => $asistencia,
            'alertas' => $asistencia ? Student360::alertas($asistencia) : [],
            'mensual' => $curso ? Student360::mensual($est->id, $curso->id, $anioVista) : [],
            'ausencias' => $curso ? Student360::ausencias($est->id, $curso->id, $anioVista) : [],
            'asignaturas' => $curso ? $this->asignaturas($curso, $request->integer('asignatura_id') ?: null) : [],
            'pie' => $this->pie($request, $est, $anioVista),
            'umbrales' => [
                'promocion' => Student360::ASISTENCIA_PROMOCION,
                'riesgo' => Student360::ASISTENCIA_RIESGO,
            ],
        ];
    }

    /** Asignaturas del curso con docente, instrumentos y avance de planificación (OA trabajados). */
    private function asignaturas(Curso $curso, ?int $soloAsignatura): array
    {
        $asignaciones = Asignacion::where('curso_id', $curso->id)
            ->when($soloAsignatura, fn ($q, $id) => $q->where('asignatura_id', $id))
            ->with(['asignatura:id,nombre,codigo,color', 'docente:id,name,email'])
            ->get()
            ->sortBy('asignatura.nombre');
        $ids = $asignaciones->pluck('id');

        $instrumentos = Instrumento::whereIn('asignacion_id', $ids)
            ->with('objetivo:id,codigo')
            ->withCount('preguntas')
            ->withSum('preguntas', 'puntaje')
            ->orderByRaw('fecha_aplicacion is null, fecha_aplicacion')
            ->get()
            ->groupBy('asignacion_id');
        $planificaciones = Planificacion::whereIn('asignacion_id', $ids)
            ->with('objetivo:id,codigo,texto')
            ->get()
            ->groupBy('asignacion_id');

        return $asignaciones->map(function (Asignacion $a) use ($instrumentos, $planificaciones) {
            $plan = $planificaciones->get($a->id, collect());

            return [
                'id' => $a->id,
                'asignatura' => $a->asignatura?->only('id', 'nombre', 'codigo', 'color'),
                'docente' => $a->docente?->only('id', 'name', 'email'),
                'horas_semanales' => $a->horas_semanales,
                'instrumentos' => $instrumentos->get($a->id, collect())->map(fn ($i) => [
                    'id' => $i->id,
                    'nombre' => $i->nombre,
                    'tipo' => $i->tipo,
                    'estado' => $i->estado,
                    'fecha_aplicacion' => $i->fecha_aplicacion?->format('Y-m-d'),
                    'preguntas' => $i->preguntas_count,
                    'puntaje' => (float) $i->preguntas_sum_puntaje,
                    'oa' => $i->objetivo?->codigo,
                ])->values(),
                'planificaciones' => [
                    'total' => $plan->count(),
                    'realizadas' => $plan->where('estado', 'realizada')->count(),
                    'ultima' => $plan->max('fecha')?->format('Y-m-d'),
                ],
                'oa_trabajados' => $plan->pluck('objetivo')->filter()->unique('id')
                    ->map(fn ($o) => ['codigo' => $o->codigo, 'texto' => $o->texto])->values(),
            ];
        })->values()->all();
    }

    /** Dominio PIE: con "pie.ver" el caso del año; sin él, solo si tiene apoyo. */
    private function pie(Request $request, Estudiante $est, int $anio): array
    {
        if (! $request->user()->tienePermiso('pie.ver')) {
            return ['restringido' => true, 'tiene' => (bool) $est->pie];
        }

        $caso = PieCaso::where('estudiante_id', $est->id)
            ->where('anio', '<=', $anio)
            ->with(['responsable:id,name', 'seguimientos' => fn ($q) => $q->latest('fecha')->limit(4)])
            ->withCount(['seguimientos', 'documentos'])
            ->orderByDesc('anio')
            ->first();

        return [
            'restringido' => false,
            'tiene' => (bool) $est->pie,
            'caso' => $caso ? [
                'id' => $caso->id,
                'anio' => $caso->anio,
                'estado' => $caso->estado,
                'estado_txt' => Pie::ESTADOS[$caso->estado] ?? $caso->estado,
                'diagnostico' => $caso->diagnostico,
                'tipo_nee' => $caso->tipo_nee,
                'apoyos' => $caso->apoyos ?? [],
                'horas_semanales' => $caso->horas_semanales,
                'fecha_reevaluacion' => $caso->fecha_reevaluacion?->format('Y-m-d'),
                'responsable' => $caso->responsable?->name,
                'fortalezas' => $caso->fortalezas,
                'barreras' => $caso->barreras,
                'seguimientos_count' => $caso->seguimientos_count,
                'documentos_count' => $caso->documentos_count,
                'seguimientos' => $caso->seguimientos->map(fn ($s) => ['fecha' => $s->fecha?->format('Y-m-d'), 'tipo' => $s->tipo, 'area' => $s->area, 'descripcion' => $s->descripcion])->values(),
            ] : null,
        ];
    }

    /** Instrumentos (de la asignatura filtrada) por curso. */
    private function instrumentosPorCurso(Collection $cursos): array
    {
        $ids = $cursos->flatMap(fn ($c) => $c->asignaciones->pluck('id'));

        return Instrumento::whereIn('asignacion_id', $ids)
            ->with('asignacion:id,curso_id')
            ->get()
            ->groupBy('asignacion.curso_id')
            ->map->count()
            ->all();
    }

    private function cursos(Request $request): Builder
    {
        return Curso::query()->visiblesPara($request->user(), Permisos::VER_STUDENT360);
    }

    private function anio(Request $request): int
    {
        return (int) ($request->anio ?: now()->year);
    }
}
