<?php

namespace App\Support;

use App\Models\AlertaBienestar;
use App\Models\Calificacion;
use App\Models\Curso;
use App\Models\DiagnosticoSocioemocional;
use App\Models\Instrumento;
use App\Models\LibretaEntrada;
use App\Models\Matricula;
use App\Models\PieCaso;
use App\Models\Planificacion;
use App\Models\SesionSocioemocional;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Compila TODO el libro de clases de un curso (asistencia, leccionario, calificaciones,
 * PIE, formación socioemocional, entrevistas y comunicaciones) en secciones con un
 * formato común. Lo usan la vista hojeable y el PDF, que así muestran exactamente lo
 * mismo; el PDF es el respaldo que se firma.
 *
 * Sección: ['clave', 'titulo', 'pestana', 'resumen' => [[k, v]], 'nota', 'modo' => pares|continuo,
 *           'tablas' => [['titulo', 'columnas' => [[label, align]], 'filas' => [[celda…]]]]]
 * Celda: texto, o ['t' => texto, 'c' => rojo|verde|ambar|gris] para destacar.
 * "pares": tabla[0] en la hoja izquierda y tabla[1] en la derecha, con las mismas filas.
 * "continuo": una tabla que sigue de hoja en hoja.
 */
class LibroCurso
{
    private const MESES = [3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic', 1 => 'Ene', 2 => 'Feb'];

    private Collection $nomina;

    public function __construct(private Curso $curso, private User $user)
    {
        // Nómina completa (retirados incluidos: el libro conserva su registro)
        $this->nomina = $curso->matriculas->sortBy('numero_lista')->values();
    }

    /** @return array<int, array<string, mixed>> */
    public function secciones(): array
    {
        return array_values(array_filter([
            $this->asistencia(),
            $this->leccionario(),
            $this->resumenNotas(),
            ...$this->notasPorAsignatura(),
            $this->pie(),
            $this->sesionesSocioemocionales(),
            $this->bienestar(),
            $this->entrevistas(),
        ]));
    }

    /** Firmas del cierre y huella (SHA-256) del contenido, para verificar el respaldo. */
    public function firmas(array $secciones): array
    {
        $jefe = $this->curso->profesorJefe?->name;

        return [
            'firmantes' => [
                ['cargo' => 'Profesor(a) jefe', 'nombre' => $jefe],
                ['cargo' => 'Jefe(a) de UTP', 'nombre' => $this->primeroConRol('UTP')],
                ['cargo' => 'Director(a)', 'nombre' => $this->primeroConRol('Dirección')],
            ],
            'hash' => hash('sha256', json_encode([
                'curso' => [$this->curso->id, $this->curso->nombre, $this->curso->anio],
                'nomina' => $this->nomina->map(fn ($m) => [$m->numero_lista, $m->estudiante?->run, $m->estado])->all(),
                'secciones' => $secciones,
            ], JSON_UNESCAPED_UNICODE)),
        ];
    }

    // ── Asistencia ─────────────────────────────────────────────

    private function asistencia(): array
    {
        $anio = $this->curso->anio;
        $anual = Student360::asistencia($this->nomina, $anio);
        $porEstudiante = $this->nomina->mapWithKeys(fn ($m) => [$m->estudiante_id => collect(Student360::mensual($m->estudiante_id, $this->curso->id, $anio))->keyBy('mes')]);
        $meses = collect(self::MESES)->keys()->filter(fn ($mes) => $porEstudiante->contains(fn ($c) => $c->has($mes)))->values();
        $lectivos = collect($anual)->max('lectivos') ?? 0;

        $izq = $this->nomina->map(function ($m) use ($anual) {
            $a = $anual[$m->estudiante_id] ?? null;
            $p = $a['porcentaje'] ?? null;

            return [
                $m->numero_lista,
                $this->nombre($m),
                $a ? "{$a['dias']}/{$a['lectivos']}" : '—',
                $p === null ? '—' : ['t' => $this->pct($p), 'c' => $p < Student360::ASISTENCIA_PROMOCION ? 'rojo' : ($p < Student360::ASISTENCIA_RIESGO ? 'ambar' : 'verde')],
                $a['atrasos'] ?? 0,
                $m->estado !== 'activa' ? ['t' => ucfirst($m->estado), 'c' => 'gris'] : ($p !== null && $p < Student360::ASISTENCIA_PROMOCION ? ['t' => 'Bajo 85 %', 'c' => 'rojo'] : 'Regular'),
            ];
        });
        $der = $this->nomina->map(fn ($m) => [$m->numero_lista, ...$meses->map(function ($mes) use ($porEstudiante, $m) {
            $fila = $porEstudiante[$m->estudiante_id]->get($mes);

            return $fila && $fila['porcentaje'] !== null ? $this->pct($fila['porcentaje']) : '—';
        })->all()]);

        $activos = collect($anual)->only($this->nomina->where('estado', 'activa')->pluck('estudiante_id')->all());
        $conDato = $activos->whereNotNull('porcentaje');

        return [
            'clave' => 'asistencia',
            'titulo' => 'Registro de asistencia',
            'pestana' => 'ASIST.',
            'resumen' => [
                ['Días lectivos registrados', $lectivos],
                ['Asistencia promedio del curso', $conDato->count() ? $this->pct($conDato->avg('porcentaje')) : '—'],
                ['Estudiantes bajo 85 %', $conDato->where('porcentaje', '<', Student360::ASISTENCIA_PROMOCION)->count()],
                ['Atrasos del año', $activos->sum('atrasos')],
            ],
            'nota' => 'Asistencia según las marcas de los lectores RFID (primera entrada de cada día hábil). Es la propuesta del motor; la asistencia oficial la valida el establecimiento.',
            'modo' => 'pares',
            'tablas' => [
                ['titulo' => 'Asistencia anual', 'columnas' => [['N°', 'c'], ['Estudiante', 'l'], ['Días', 'c'], ['%', 'c'], ['Atrasos', 'c'], ['Situación', 'l']], 'filas' => $izq->all()],
                ['titulo' => 'Asistencia mensual (%)', 'columnas' => [['N°', 'c'], ...$meses->map(fn ($mes) => [self::MESES[$mes], 'c'])->all()], 'filas' => $der->all()],
            ],
        ];
    }

    // ── Leccionario (registro de clases) ───────────────────────

    private function leccionario(): array
    {
        $clases = Planificacion::whereHas('asignacion', fn ($q) => $q->where('curso_id', $this->curso->id))
            ->whereYear('fecha', $this->curso->anio)
            ->where('estado', '!=', 'borrador')
            ->with(['asignacion.asignatura:id,nombre,codigo', 'docente:id,name', 'objetivo:id,codigo'])
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->get();

        return [
            'clave' => 'leccionario',
            'titulo' => 'Leccionario · registro de clases',
            'pestana' => 'LECCION.',
            'resumen' => [
                ['Clases registradas', $clases->count()],
                ['Realizadas', $clases->where('estado', 'realizada')->count()],
                ['Planificadas (por realizar)', $clases->where('estado', 'planificada')->count()],
                ['Con OA declarado', $clases->whereNotNull('objetivo_aprendizaje_id')->count()],
            ],
            'nota' => 'Contenidos y actividades de cada clase según la planificación docente, con el OA trabajado.',
            'modo' => 'continuo',
            'tablas' => [[
                'titulo' => 'Clases del año',
                'columnas' => [['Fecha', 'c'], ['Hora', 'c'], ['Asignatura', 'l'], ['OA', 'c'], ['Contenido / actividad', 'l'], ['Docente', 'l'], ['Estado', 'c']],
                'filas' => $clases->map(fn (Planificacion $p) => [
                    $p->fecha?->format('d-m'),
                    $p->hora_inicio ? substr($p->hora_inicio, 0, 5) : '—',
                    $p->asignacion?->asignatura?->nombre ?? '—',
                    $p->objetivo?->codigo ?? '—',
                    $p->titulo,
                    $p->docente?->name ?? '—',
                    $p->estado === 'realizada' ? ['t' => 'Realizada', 'c' => 'verde'] : ['t' => 'Planificada', 'c' => 'ambar'],
                ])->all(),
            ]],
        ];
    }

    // ── Calificaciones ─────────────────────────────────────────

    /** Calificaciones del curso agrupadas: [asignatura_id => ['asignatura', 'instrumentos', 'notas' => [estudiante_id => [instrumento_id => nota]]]] */
    private function calificaciones(): Collection
    {
        return once(function () {
            $instrumentos = Instrumento::whereHas('asignacion', fn ($q) => $q->where('curso_id', $this->curso->id))
                ->where(fn ($q) => $q->where('estado', 'listo')->orWhereHas('calificaciones'))
                ->with(['asignacion.asignatura:id,nombre,codigo', 'objetivo:id,codigo'])
                ->orderByRaw('fecha_aplicacion IS NULL')->orderBy('fecha_aplicacion')->orderBy('id')
                ->get();
            $notas = Calificacion::whereIn('instrumento_id', $instrumentos->pluck('id'))->get()->groupBy('estudiante_id');

            return $instrumentos->groupBy(fn ($i) => $i->asignacion->asignatura_id)
                ->map(fn ($grupo) => [
                    'asignatura' => $grupo->first()->asignacion->asignatura,
                    'instrumentos' => $grupo->values(),
                    'notas' => $notas->map(fn ($n) => $n->whereIn('instrumento_id', $grupo->pluck('id'))->pluck('nota', 'instrumento_id')),
                ])
                ->sortBy(fn ($g) => $g['asignatura']->nombre);
        });
    }

    private function resumenNotas(): ?array
    {
        $grupos = $this->calificaciones();
        if ($grupos->isEmpty()) {
            return null;
        }

        $promedios = $this->nomina->mapWithKeys(fn ($m) => [$m->estudiante_id => $grupos->map(
            fn ($g) => EscalaNotas::promedio(collect($g['notas'][$m->estudiante_id] ?? []))
        )]);

        return [
            'clave' => 'notas',
            'titulo' => 'Calificaciones · resumen del año',
            'pestana' => 'NOTAS',
            'resumen' => [
                ['Asignaturas con notas', $grupos->count()],
                ['Instrumentos registrados', $grupos->sum(fn ($g) => $g['instrumentos']->count())],
                ['Promedio del curso', $this->nota(EscalaNotas::promedio($promedios->map(fn ($p) => EscalaNotas::promedio($p->filter()))->filter()))],
            ],
            'nota' => 'Escala 1,0 a 7,0; la nota de cada instrumento la calcula el sistema desde el puntaje (App\Support\EscalaNotas). Promedio general = promedio de los promedios por asignatura.',
            'modo' => 'pares',
            'tablas' => [
                [
                    'titulo' => 'Promedio por asignatura',
                    'columnas' => [['N°', 'c'], ['Estudiante', 'l'], ...$grupos->map(fn ($g) => [$g['asignatura']->codigo, 'c'])->values()->all()],
                    'filas' => $this->nomina->map(fn ($m) => [$m->numero_lista, $this->nombre($m), ...$promedios[$m->estudiante_id]->map(fn ($p) => $this->celdaNota($p))->values()->all()])->all(),
                ],
                [
                    'titulo' => 'Promedio general',
                    'columnas' => [['N°', 'c'], ['Promedio', 'c'], ['Bajo 4,0', 'c'], ['Situación', 'l']],
                    'filas' => $this->nomina->map(function ($m) use ($promedios) {
                        $p = $promedios[$m->estudiante_id]->filter();
                        $general = EscalaNotas::promedio($p);
                        $bajo = $p->filter(fn ($n) => $n < EscalaNotas::NOTA_APROBACION)->count();

                        return [$m->numero_lista, $this->celdaNota($general), $bajo ?: '—',
                            $m->estado !== 'activa' ? ['t' => ucfirst($m->estado), 'c' => 'gris'] : ($general === null ? '—' : ($bajo ? ['t' => "{$bajo} asignatura(s) bajo 4,0", 'c' => 'rojo'] : ['t' => 'Al día', 'c' => 'verde']))];
                    })->all(),
                ],
            ],
        ];
    }

    private function notasPorAsignatura(): array
    {
        return $this->calificaciones()->map(function ($g) {
            $instr = $g['instrumentos'];

            return [
                'clave' => 'notas-'.$g['asignatura']->codigo,
                'titulo' => "Calificaciones · {$g['asignatura']->nombre}",
                'pestana' => null,
                'resumen' => $instr->map(fn ($i, $k) => ['N'.($k + 1), $i->nombre.($i->fecha_aplicacion ? ' · '.$i->fecha_aplicacion->format('d-m') : '').($i->objetivo ? " · {$i->objetivo->codigo}" : '')])->values()->all(),
                'nota' => null,
                'modo' => 'pares',
                'tablas' => [
                    [
                        'titulo' => 'Notas por instrumento',
                        'columnas' => [['N°', 'c'], ['Estudiante', 'l'], ...$instr->map(fn ($i, $k) => ['N'.($k + 1), 'c'])->values()->all()],
                        'filas' => $this->nomina->map(fn ($m) => [$m->numero_lista, $this->nombre($m),
                            ...$instr->map(fn ($i) => $this->celdaNota($g['notas'][$m->estudiante_id][$i->id] ?? null))->values()->all()])->all(),
                    ],
                    [
                        'titulo' => 'Promedio de la asignatura',
                        'columnas' => [['N°', 'c'], ['Promedio', 'c'], ['Notas', 'c']],
                        'filas' => $this->nomina->map(function ($m) use ($g) {
                            $n = collect($g['notas'][$m->estudiante_id] ?? []);

                            return [$m->numero_lista, $this->celdaNota(EscalaNotas::promedio($n)), $n->count()];
                        })->all(),
                    ],
                ],
            ];
        })->values()->all();
    }

    // ── PIE ────────────────────────────────────────────────────

    private function pie(): array
    {
        $casos = PieCaso::where('anio', $this->curso->anio)
            ->whereIn('estudiante_id', $this->nomina->pluck('estudiante_id'))
            ->with('responsable:id,name')->get()->keyBy('estudiante_id');
        $puedeVer = $this->user->tienePermiso(Permisos::de('pie', Permisos::VER));

        $filas = $puedeVer
            ? $this->nomina->filter(fn ($m) => $casos->has($m->estudiante_id))->map(function ($m) use ($casos) {
                $c = $casos[$m->estudiante_id];

                return [$m->numero_lista, $this->nombre($m), $c->diagnostico, ucfirst($c->tipo_nee ?? '—'),
                    implode(', ', $c->apoyos ?? []) ?: '—', $c->horas_semanales ?: '—', $c->responsable?->name ?? '—',
                    Pie::ESTADOS[$c->estado] ?? $c->estado];
            })->values()->all()
            : [];

        return [
            'clave' => 'pie',
            'titulo' => 'Programa de Integración Escolar (PIE)',
            'pestana' => 'PIE',
            'resumen' => [
                ['Estudiantes en PIE', $casos->count()],
                ['NEE transitorias', $casos->where('tipo_nee', Pie::TRANSITORIA)->count()],
                ['NEE permanentes', $casos->where('tipo_nee', Pie::PERMANENTE)->count()],
            ],
            'nota' => $puedeVer
                ? 'Registro de estudiantes con NEE, apoyos y responsables (Decreto 170). Información sensible: solo para el equipo de aula y PIE.'
                : 'Detalle reservado al equipo PIE (permiso pie.ver). Se informa solo la cantidad de estudiantes.',
            'modo' => 'continuo',
            'tablas' => [[
                'titulo' => 'Estudiantes del programa',
                'columnas' => [['N°', 'c'], ['Estudiante', 'l'], ['Diagnóstico', 'l'], ['NEE', 'c'], ['Apoyos', 'l'], ['Hrs.', 'c'], ['Responsable', 'l'], ['Estado', 'c']],
                'filas' => $filas,
            ]],
        ];
    }

    // ── Formación socioemocional ──────────────────────────────

    private function sesionesSocioemocionales(): array
    {
        $sesiones = SesionSocioemocional::where('curso_id', $this->curso->id)->with('responsable:id,name')->orderBy('fecha')->get();
        $diagnosticos = DiagnosticoSocioemocional::where('curso_id', $this->curso->id)->where('anio', $this->curso->anio)->get();
        $competencias = array_keys(Socioemocional::COMPETENCIAS);

        $resumen = [['Sesiones del año', $sesiones->count()], ['Realizadas', $sesiones->where('estado', 'realizada')->count()]];
        foreach (Socioemocional::PERIODOS as $periodo => $nombre) {
            $del = $diagnosticos->where('periodo', $periodo);
            if ($del->isNotEmpty()) {
                $resumen[] = ["{$nombre} ({$del->count()} est.)", collect($competencias)
                    ->map(fn ($c) => Socioemocional::COMPETENCIAS[$c][0].' '.(Socioemocional::promedio($del->pluck($c)->filter(fn ($v) => $v !== null)) ?? '—').'%')
                    ->implode(' · ')];
            }
        }

        return [
            'clave' => 'socioemocional',
            'titulo' => 'Formación socioemocional',
            'pestana' => 'SOCIOEMOC.',
            'resumen' => $resumen,
            'nota' => 'Sesiones del plan socioemocional del curso y logro promedio del diagnóstico por competencia (CASEL).',
            'modo' => 'continuo',
            'tablas' => [[
                'titulo' => 'Sesiones y talleres',
                'columnas' => [['Fecha', 'c'], ['Sesión', 'l'], ['Tipo', 'l'], ['Competencia', 'l'], ['Responsable', 'l'], ['Estado', 'c']],
                'filas' => $sesiones->map(fn (SesionSocioemocional $s) => [
                    $s->fecha?->format('d-m'), $s->titulo, $s->tipo, Socioemocional::COMPETENCIAS[$s->competencia][0] ?? $s->competencia,
                    $s->responsable?->name ?? '—', Socioemocional::ESTADOS_SESION[$s->estado] ?? $s->estado,
                ])->all(),
            ]],
        ];
    }

    /** Alertas de bienestar / protocolo DEC: detalle solo con "emocional.ver" (sin la descripción del episodio). */
    private function bienestar(): array
    {
        $alertas = AlertaBienestar::where('anio', $this->curso->anio)
            ->whereIn('estudiante_id', $this->nomina->pluck('estudiante_id'))
            ->with('responsable:id,name')->orderBy('fecha')->get();
        $puedeVer = $this->user->tienePermiso(Permisos::de('emocional', Permisos::VER));
        $lista = $this->nomina->keyBy('estudiante_id');

        return [
            'clave' => 'bienestar',
            'titulo' => 'Bienestar y protocolo DEC',
            'pestana' => null,
            'resumen' => [
                ['Alertas del año', $alertas->count()],
                ['Abiertas', $alertas->whereIn('estado', Socioemocional::ABIERTAS)->count()],
                ['Con familia contactada', $alertas->where('familia_contactada', true)->count()],
            ],
            'nota' => $puedeVer
                ? 'Seguimiento individual de bienestar. No se incluye la descripción de los episodios (dato sensible); el detalle está en el módulo de Educación emocional.'
                : 'Detalle reservado al equipo de convivencia y bienestar (permiso emocional.ver). Se informan solo los totales.',
            'modo' => 'continuo',
            'tablas' => [[
                'titulo' => 'Alertas de bienestar',
                'columnas' => [['Fecha', 'c'], ['N°', 'c'], ['Estudiante', 'l'], ['Tipo', 'l'], ['Intensidad', 'c'], ['Responsable', 'l'], ['Estado', 'c']],
                'filas' => $puedeVer ? $alertas->map(fn (AlertaBienestar $a) => [
                    $a->fecha?->format('d-m'), $lista[$a->estudiante_id]->numero_lista ?? '—',
                    $lista->has($a->estudiante_id) ? $this->nombre($lista[$a->estudiante_id]) : '—',
                    Socioemocional::TIPOS_ALERTA[$a->tipo] ?? $a->tipo, ucfirst($a->intensidad), $a->responsable?->name ?? '—',
                    in_array($a->estado, Socioemocional::ABIERTAS, true) ? ['t' => Socioemocional::ESTADOS_ALERTA[$a->estado] ?? $a->estado, 'c' => 'ambar'] : ['t' => 'Cerrada', 'c' => 'gris'],
                ])->all() : [],
            ]],
        ];
    }

    // ── Entrevistas y comunicaciones (libreta) ────────────────

    private function entrevistas(): array
    {
        $entradas = LibretaEntrada::whereIn('estudiante_id', $this->nomina->pluck('estudiante_id'))
            ->whereYear('created_at', $this->curso->anio)
            ->with(['autor:id,name', 'respondidoPor:id,name'])->orderBy('created_at')->get();
        $lista = $this->nomina->keyBy('estudiante_id');

        return [
            'clave' => 'entrevistas',
            'titulo' => 'Entrevistas, citaciones y comunicaciones',
            'pestana' => 'ENTREV.',
            'resumen' => [
                ['Citaciones / entrevistas', $entradas->where('tipo', 'citacion')->count()],
                ['Justificaciones de la familia', $entradas->where('tipo', 'justificacion')->count()],
                ['Retiros anticipados avisados', $entradas->where('tipo', 'retiro')->count()],
                ['Acuerdos firmados', $entradas->where('tipo', 'acuerdo')->whereNotNull('respuesta')->count()],
            ],
            'nota' => 'Registro de la Libreta del Estudiante: emisor, fecha y respuesta o firma de la familia.',
            'modo' => 'continuo',
            'tablas' => [[
                'titulo' => 'Registro del año',
                'columnas' => [['Fecha', 'c'], ['N°', 'c'], ['Estudiante', 'l'], ['Tipo', 'l'], ['Asunto', 'l'], ['Emisor', 'l'], ['Respuesta / firma', 'l']],
                'filas' => $entradas->map(fn (LibretaEntrada $e) => [
                    $e->created_at?->format('d-m'), $lista[$e->estudiante_id]->numero_lista ?? '—',
                    $lista->has($e->estudiante_id) ? $this->nombre($lista[$e->estudiante_id]) : '—',
                    Libreta::TIPOS[$e->tipo][0] ?? $e->tipo, $e->asunto, $e->autor?->name ?? '—',
                    $e->respuesta
                        ? ['t' => (Libreta::respuestas($e->tipo)[$e->respuesta] ?? 'Lectura confirmada').' · '.($e->respondidoPor?->name ?? ''), 'c' => in_array($e->respuesta, ['no_asistira', 'no_autoriza'], true) ? 'rojo' : 'verde']
                        : ($e->requiere_respuesta ? ['t' => 'Pendiente', 'c' => 'ambar'] : ($e->leido_en ? 'Leída' : '—')),
                ])->all(),
            ]],
        ];
    }

    // ── Utilidades ─────────────────────────────────────────────

    private function nombre(Matricula $m): string
    {
        $e = $m->estudiante;

        return $e ? trim("{$e->apellido_paterno} {$e->apellido_materno}, {$e->nombres}") : '—';
    }

    private function pct(float $p): string
    {
        return str_replace('.', ',', (string) round($p, 1)).' %';
    }

    private function nota($n): string
    {
        return $n === null ? '—' : number_format((float) $n, 1, ',', '');
    }

    private function celdaNota($n): string|array
    {
        return $n === null ? '—' : ['t' => $this->nota($n), 'c' => $n < EscalaNotas::NOTA_APROBACION ? 'rojo' : ''];
    }

    private function primeroConRol(string $rol): ?string
    {
        return User::whereIn('id', DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', $rol)->where('model_type', User::class)->pluck('model_id'))
            ->orderBy('id')->value('name');
    }
}
