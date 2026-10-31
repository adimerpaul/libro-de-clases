<?php

namespace App\Support;

use App\Models\Calificacion;
use App\Models\MarcaRfid;

/**
 * Resumen de un estudiante para la familia (portal y libreta): notas del año y
 * asistencia del mes según RFID (propuesta, no la asistencia oficial).
 */
class ResumenEstudiante
{
    /** Notas del año por asignatura; el promedio general es el de los promedios por asignatura. */
    public static function notas(int $estudianteId, int $anio): array
    {
        $asignaturas = Calificacion::where('estudiante_id', $estudianteId)
            ->whereHas('instrumento.asignacion.curso', fn ($q) => $q->where('anio', $anio))
            ->with(['instrumento:id,asignacion_id,objetivo_aprendizaje_id,nombre,tipo,fecha_aplicacion',
                'instrumento.asignacion:id,asignatura_id,docente_id', 'instrumento.asignacion.asignatura:id,nombre,color',
                'instrumento.asignacion.docente:id,name', 'instrumento.objetivo:id,codigo'])
            ->get()
            ->filter(fn (Calificacion $c) => $c->instrumento?->asignacion?->asignatura)
            ->groupBy(fn (Calificacion $c) => $c->instrumento->asignacion->asignatura_id)
            ->map(function ($notas) {
                $asignacion = $notas->first()->instrumento->asignacion;

                return [
                    'asignatura' => $asignacion->asignatura->nombre,
                    'color' => $asignacion->asignatura->color,
                    'docente' => $asignacion->docente?->name,
                    'promedio' => EscalaNotas::promedio($notas->pluck('nota')),
                    'notas' => $notas->sortBy(fn ($c) => $c->instrumento->fecha_aplicacion?->format('Y-m-d') ?? '9999')
                        ->map(fn (Calificacion $c) => [
                            'id' => $c->id,
                            'instrumento' => $c->instrumento->nombre,
                            'tipo' => $c->instrumento->tipo,
                            'fecha' => $c->instrumento->fecha_aplicacion?->format('Y-m-d'),
                            'oa' => $c->instrumento->objetivo?->codigo,
                            'nota' => $c->nota,
                            'observacion' => $c->observacion,
                        ])->values(),
                ];
            })
            ->sortBy('asignatura')->values();

        return [
            'promedio' => EscalaNotas::promedio($asignaturas->pluck('promedio')),
            'total' => $asignaturas->sum(fn ($a) => count($a['notas'])),
            'bajo_4' => $asignaturas->sum(fn ($a) => collect($a['notas'])->where('nota', '<', EscalaNotas::NOTA_APROBACION)->count()),
            'asignaturas' => $asignaturas,
        ];
    }

    /**
     * Asistencia del mes según las marcas RFID (propuesta del motor, no la asistencia oficial):
     * días hábiles hasta hoy con entrada, atrasos y la última marca.
     */
    public static function asistencia(int $estudianteId): array
    {
        $desde = today()->startOfMonth();
        $marcas = MarcaRfid::where('estudiante_id', $estudianteId)->whereBetween('marcado_en', [$desde, now()])
            ->orderBy('marcado_en')->orderBy('id')->get();

        $entradas = $marcas->where('tipo', 'entrada')->groupBy(fn ($m) => $m->marcado_en->toDateString())->map->first();
        // Días con clases = días en que los lectores registraron a alguien (excluye feriados y vacaciones)
        $habiles = MarcaRfid::whereBetween('marcado_en', [$desde, now()])->whereNotNull('estudiante_id')
            ->selectRaw('DATE(marcado_en) as dia')->distinct()->get()->count();
        $atrasos = $entradas->filter(fn ($m) => AsistenciaRfid::propuesta($m->marcado_en) === 'atrasado');
        $hoy = $marcas->filter(fn ($m) => $m->marcado_en->isToday());
        $entradaHoy = $hoy->firstWhere('tipo', 'entrada');

        return [
            'desde' => $desde->toDateString(),
            'dias_habiles' => $habiles,
            'dias_con_entrada' => $entradas->count(),
            'porcentaje' => $habiles ? (int) round(100 * min($entradas->count(), $habiles) / $habiles) : null,
            'atrasos' => $atrasos->count(),
            'limite' => AsistenciaRfid::limite(today())->format('H:i'),
            'hoy' => [
                'entrada' => $entradaHoy?->marcado_en->format('H:i'),
                'propuesta' => $entradaHoy ? AsistenciaRfid::propuesta($entradaHoy->marcado_en) : null,
                'dentro' => $hoy->last()?->tipo === 'entrada',
            ],
            'ultimas' => $marcas->reverse()->take(8)->map(fn (MarcaRfid $m) => [
                'marcado_en' => $m->marcado_en->format('Y-m-d H:i'),
                'tipo' => $m->tipo,
                'lector' => $m->lector,
                'propuesta' => $m->tipo === 'entrada' ? AsistenciaRfid::propuesta($m->marcado_en) : null,
            ])->values(),
        ];
    }
}
