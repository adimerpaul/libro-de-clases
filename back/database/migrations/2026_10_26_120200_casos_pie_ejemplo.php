<?php

use Database\Seeders\PieSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Casos PIE de ejemplo (PieSeeder) también al migrar, para que el módulo no quede vacío.
 * El seeder se omite si ya hay casos.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new PieSeeder)->run();
    }

    public function down(): void
    {
        $ids = DB::table('pie_casos')->where('observaciones', 'Caso de demostración con datos ficticios.')->pluck('id');
        DB::table('pie_seguimientos')->whereIn('pie_caso_id', $ids)->delete();
        DB::table('pie_casos')->whereIn('id', $ids)->delete();
    }
};
