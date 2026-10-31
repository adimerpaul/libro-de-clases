<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Una tarjeta RFID FICTICIA ("RFID-000001", …) para cada estudiante que no tenga una,
 * para que el simulador de /rfid tenga a quién marcar. Mismo formato que
 * TarjetasRfidSeeder; down() solo borra estas tarjetas ficticias.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $filas = DB::table('estudiantes')->whereNull('deleted_at')
            ->whereNotIn('id', DB::table('tarjetas_rfid')->whereNull('deleted_at')->select('estudiante_id'))
            ->orderBy('id')->pluck('id')
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

    public function down(): void
    {
        DB::table('tarjetas_rfid')->where('codigo', 'like', 'RFID-%')->delete();
    }
};
