<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Una tarjeta RFID FICTICIA por estudiante (código "RFID-000001", …), para probar el
 * servidor sockets/. Determinístico; se omite si ya hay tarjetas.
 */
class TarjetasRfidSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('tarjetas_rfid')->exists()) {
            return;
        }

        $now = now();
        $filas = DB::table('estudiantes')->whereNull('deleted_at')->orderBy('id')->pluck('id')
            ->map(fn ($id) => [
                'estudiante_id' => $id,
                'codigo' => sprintf('RFID-%06d', $id),
                'activa' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        foreach ($filas->chunk(500) as $chunk) {
            DB::table('tarjetas_rfid')->insert($chunk->all());
        }
    }
}
