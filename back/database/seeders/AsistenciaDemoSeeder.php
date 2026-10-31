<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Historial FICTICIO de marcas RFID (entrada y salida) del año escolar en curso, desde
 * el primer día hábil de marzo hasta ayer, para que Student 360 y los reportes tengan
 * datos. Cada estudiante tiene un perfil fijo de asistencia y puntualidad (semilla =
 * su id), así que el resultado es siempre el mismo. Se omiten fines de semana,
 * vacaciones de invierno y feriados. Reemplaza el historial demo anterior.
 *
 *   php artisan db:seed --class=AsistenciaDemoSeeder
 */
class AsistenciaDemoSeeder extends Seeder
{
    public const LECTOR = 'Portal principal (histórico demo)';

    public function run(): void
    {
        $anio = (int) now()->year;
        DB::table('marcas_rfid')->where('lector', self::LECTOR)->delete();

        $estudiantes = DB::table('matriculas')
            ->join('cursos', 'cursos.id', '=', 'matriculas.curso_id')
            ->join('tarjetas_rfid', fn ($j) => $j->on('tarjetas_rfid.estudiante_id', '=', 'matriculas.estudiante_id')->where('tarjetas_rfid.activa', true))
            ->where('cursos.anio', $anio)->where('matriculas.estado', 'activa')
            ->whereNull('matriculas.deleted_at')->whereNull('cursos.deleted_at')->whereNull('tarjetas_rfid.deleted_at')
            ->get(['matriculas.estudiante_id', 'matriculas.curso_id', 'tarjetas_rfid.id as tarjeta_id', 'tarjetas_rfid.codigo']);

        $dias = $this->diasLectivos($anio);
        $now = now();
        $filas = [];

        foreach ($estudiantes as $e) {
            mt_srand($e->estudiante_id * 7919);
            // Perfil: la mayoría sobre 90 %, algunos en riesgo y unos pocos bajo 85 %
            $perfil = mt_rand(1, 100);
            $pAsiste = match (true) {
                $perfil <= 8 => mt_rand(70, 84) / 100,
                $perfil <= 22 => mt_rand(85, 90) / 100,
                default => mt_rand(91, 99) / 100,
            };
            $pAtraso = mt_rand(1, 100) <= 15 ? mt_rand(8, 18) / 100 : mt_rand(0, 5) / 100;

            foreach ($dias as $dia) {
                if (mt_rand(1, 1000) > $pAsiste * 1000) {
                    continue;
                }
                $entrada = mt_rand(1, 1000) <= $pAtraso * 1000
                    ? $dia->copy()->setTime(8, mt_rand(11, 50), mt_rand(0, 59))
                    : $dia->copy()->setTime(7, mt_rand(25, 59), mt_rand(0, 59))->addMinutes(mt_rand(0, 9));
                $salida = $dia->copy()->setTime(15, mt_rand(30, 59), mt_rand(0, 59))->addMinutes(mt_rand(0, 40));

                foreach (['entrada' => $entrada, 'salida' => $salida] as $tipo => $hora) {
                    $filas[] = [
                        'codigo' => $e->codigo, 'tarjeta_id' => $e->tarjeta_id, 'estudiante_id' => $e->estudiante_id,
                        'curso_id' => $e->curso_id, 'lector' => self::LECTOR, 'tipo' => $tipo,
                        'marcado_en' => $hora->format('Y-m-d H:i:s'), 'created_at' => $now, 'updated_at' => $now,
                    ];
                }

                if (count($filas) >= 1000) {
                    DB::table('marcas_rfid')->insert($filas);
                    $filas = [];
                }
            }
        }

        if ($filas) {
            DB::table('marcas_rfid')->insert($filas);
        }
    }

    /** Días hábiles desde marzo hasta ayer, sin vacaciones de invierno ni feriados. */
    private function diasLectivos(int $anio): array
    {
        $feriados = ['05-01', '05-21', '06-20', '06-29', '07-16', '08-15', '09-18', '09-19', '10-12', '10-31', '11-01'];
        $inviernoDesde = Carbon::create($anio, 6, 22);
        $inviernoHasta = Carbon::create($anio, 7, 3);
        $hasta = now()->subDay()->startOfDay()->min(Carbon::create($anio, 12, 15));

        $dias = [];
        for ($d = Carbon::create($anio, 3, 4); $d->lte($hasta); $d->addDay()) {
            if ($d->isWeekend() || in_array($d->format('m-d'), $feriados) || $d->between($inviernoDesde, $inviernoHasta)) {
                continue;
            }
            $dias[] = $d->copy();
        }

        return $dias;
    }
}
