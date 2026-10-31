<?php

namespace Database\Seeders;

use App\Models\Matricula;
use Illuminate\Database\Seeder;

/**
 * Retiros y traslados FICTICIOS del año actual: da de baja algunas matrículas activas
 * (de estudiantes sin PIE). Determinístico; se omite si ya hay matrículas dadas de baja.
 */
class RetirosSeeder extends Seeder
{
    public const BAJAS = [
        // [estado, motivo]
        ['trasladada', 'Cambio de domicilio a otra comuna.'],
        ['retirada', 'Retiro voluntario solicitado por el apoderado.'],
        ['trasladada', 'Traslado a establecimiento con jornada vespertina.'],
        ['retirada', 'Familia se traslada al extranjero.'],
        ['trasladada', 'Traslado por cambio de trabajo del apoderado.'],
        ['retirada', 'Retiro por motivos de salud.'],
    ];

    public function run(): void
    {
        if (Matricula::where('estado', '!=', 'activa')->exists()) {
            return;
        }

        $anio = (int) now()->year;
        $matriculas = Matricula::activas()
            ->whereHas('curso', fn ($c) => $c->where('anio', $anio))
            ->whereHas('estudiante', fn ($e) => $e->where('pie', false))
            ->orderBy('id')->get();

        // Repartidas en la nómina, no las primeras del primer curso
        $paso = max(1, intdiv($matriculas->count(), count(self::BAJAS)));

        foreach (self::BAJAS as $i => [$estado, $motivo]) {
            $matricula = $matriculas->get($i * $paso);
            if (! $matricula) {
                break;
            }

            $fecha = $matricula->fecha_matricula->copy()->addDays(30 + $i * 21);
            $matricula->update([
                'estado' => $estado,
                'fecha_retiro' => $fecha->isFuture() ? now()->toDateString() : $fecha->toDateString(),
                'motivo_retiro' => $motivo,
            ]);
        }
    }
}
