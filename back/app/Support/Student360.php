<?php

namespace App\Support;

use App\Models\MarcaRfid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Indicadores de Student 360, calculados en código determinístico a partir de
 * las marcas RFID (propuesta, no asistencia oficial: esa la valida una persona).
 *
 * Día lectivo de un curso = día hábil en que al menos un estudiante del curso
 * marcó entrada. Asistencia = días con entrada / días lectivos de su curso.
 * Atraso = la primera entrada del día pasó el límite de AsistenciaRfid.
 */
class Student360
{
    /** Bajo este porcentaje no se cumple el requisito de asistencia para la promoción (Decreto 67). */
    public const ASISTENCIA_PROMOCION = 85;

    /** Bajo este porcentaje hay ausentismo crónico (criterio Mineduc). */
    public const ASISTENCIA_RIESGO = 90;

    /** Desde esta cantidad de atrasos en el año se levanta alerta de puntualidad. */
    public const ATRASOS_ALERTA = 10;

    /**
     * Primera entrada de cada estudiante por día, en los cursos dados.
     *
     * @return Collection<int, object{estudiante_id:int, curso_id:int, dia:string, primera:string}>
     */
    private static function entradas(array $cursoIds, int $anio, ?int $estudianteId = null): Collection
    {
        return MarcaRfid::query()
            ->selectRaw('estudiante_id, curso_id, DATE(marcado_en) as dia, MIN(marcado_en) as primera')
            ->where('tipo', 'entrada')
            ->whereNotNull('estudiante_id')
            ->whereIn('curso_id', $cursoIds)
            ->when($estudianteId, fn ($q, $id) => $q->where('estudiante_id', $id))
            ->whereBetween('marcado_en', ["{$anio}-01-01 00:00:00", "{$anio}-12-31 23:59:59"])
            ->groupBy('estudiante_id', 'curso_id', 'dia')
            ->toBase()
            ->get()
            ->filter(fn ($f) => ! Carbon::parse($f->dia)->isWeekend())
            ->values();
    }

    /** Días lectivos (fechas) de cada curso: [curso_id => [Y-m-d, …]]. */
    public static function diasLectivos(array $cursoIds, int $anio): array
    {
        return self::entradas($cursoIds, $anio)
            ->groupBy('curso_id')
            ->map(fn ($filas) => $filas->pluck('dia')->unique()->sort()->values()->all())
            ->all();
    }

    /**
     * Asistencia de cada estudiante en su curso del año.
     *
     * @return array<int, array{porcentaje:?float, dias:int, lectivos:int, atrasos:int}> por estudiante_id
     */
    public static function asistencia(Collection $matriculas, int $anio): array
    {
        $cursoIds = $matriculas->pluck('curso_id')->unique()->values()->all();
        $entradas = self::entradas($cursoIds, $anio);
        $lectivos = $entradas->groupBy('curso_id')->map(fn ($f) => $f->pluck('dia')->unique()->count());
        $porEstudiante = $entradas->groupBy(fn ($f) => "{$f->estudiante_id}-{$f->curso_id}");

        $res = [];
        foreach ($matriculas as $m) {
            $propias = $porEstudiante->get("{$m->estudiante_id}-{$m->curso_id}", collect());
            $total = (int) ($lectivos[$m->curso_id] ?? 0);
            $res[$m->estudiante_id] = [
                'porcentaje' => $total ? round($propias->count() * 100 / $total, 1) : null,
                'dias' => $propias->count(),
                'lectivos' => $total,
                'atrasos' => $propias->filter(fn ($f) => self::esAtraso($f->primera))->count(),
            ];
        }

        return $res;
    }

    /** Asistencia mes a mes de un estudiante en su curso: [['mes' => 3, 'porcentaje' => 95.0, …], …]. */
    public static function mensual(int $estudianteId, int $cursoId, int $anio): array
    {
        $curso = self::entradas([$cursoId], $anio);
        $propias = $curso->where('estudiante_id', $estudianteId);

        return $curso->groupBy(fn ($f) => (int) substr($f->dia, 5, 2))
            ->sortKeys()
            ->map(function ($filas, $mes) use ($propias) {
                $lectivos = $filas->pluck('dia')->unique()->count();
                $mias = $propias->filter(fn ($f) => (int) substr($f->dia, 5, 2) === $mes);

                return [
                    'mes' => $mes,
                    'lectivos' => $lectivos,
                    'dias' => $mias->count(),
                    'atrasos' => $mias->filter(fn ($f) => self::esAtraso($f->primera))->count(),
                    'porcentaje' => $lectivos ? round($mias->count() * 100 / $lectivos, 1) : null,
                ];
            })
            ->values()
            ->all();
    }

    /** Días lectivos del curso en que el estudiante no marcó entrada (los más recientes primero). */
    public static function ausencias(int $estudianteId, int $cursoId, int $anio, int $limite = 8): array
    {
        $curso = self::entradas([$cursoId], $anio);
        $presentes = $curso->where('estudiante_id', $estudianteId)->pluck('dia')->all();

        return $curso->pluck('dia')->unique()->diff($presentes)->sortDesc()->take($limite)->values()->all();
    }

    public static function esAtraso(string $primera): bool
    {
        return AsistenciaRfid::propuesta(Carbon::parse($primera)) === 'atrasado';
    }

    /**
     * Alertas determinísticas del estudiante.
     *
     * @return array<int, array{nivel:string, texto:string}>
     */
    public static function alertas(array $asistencia): array
    {
        $alertas = [];
        $p = $asistencia['porcentaje'] ?? null;

        if ($p !== null && $p < self::ASISTENCIA_PROMOCION) {
            $alertas[] = ['nivel' => 'critica', 'texto' => "Asistencia {$p} %: bajo el 85 % exigido para la promoción"];
        } elseif ($p !== null && $p < self::ASISTENCIA_RIESGO) {
            $alertas[] = ['nivel' => 'riesgo', 'texto' => "Asistencia {$p} %: ausentismo crónico (bajo 90 %)"];
        }
        if (($asistencia['atrasos'] ?? 0) >= self::ATRASOS_ALERTA) {
            $alertas[] = ['nivel' => 'riesgo', 'texto' => "{$asistencia['atrasos']} atrasos en el año"];
        }

        return $alertas;
    }
}
