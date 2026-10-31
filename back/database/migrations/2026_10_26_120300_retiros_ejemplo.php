<?php

use Database\Seeders\RetirosSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retiros y traslados de ejemplo (RetirosSeeder) también al migrar, para que /retiros
 * no quede vacío. El seeder se omite si ya hay matrículas dadas de baja.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RetirosSeeder)->run();
    }

    public function down(): void
    {
        DB::table('matriculas')->where('estado', '!=', 'activa')
            ->whereIn('motivo_retiro', array_column(RetirosSeeder::BAJAS, 1))
            ->update(['estado' => 'activa', 'fecha_retiro' => null, 'motivo_retiro' => null]);
    }
};
