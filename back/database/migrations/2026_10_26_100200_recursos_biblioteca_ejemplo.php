<?php

use Database\Seeders\BibliotecaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recursos de ejemplo de la biblioteca (BibliotecaSeeder) también al migrar, para que
 * la biblioteca no quede vacía. El seeder se omite si ya hay recursos.
 */
return new class extends Migration
{
    private const TITULOS = [
        'Estaciones de aprendizaje', 'Funciones en contexto', 'Lectura con evidencia',
        'Homeostasis visual', 'Kit de fracciones', 'Banco de apoyos visuales',
    ];

    public function up(): void
    {
        (new BibliotecaSeeder)->run();
    }

    public function down(): void
    {
        DB::table('recursos')->whereNull('archivo_path')->whereIn('titulo', self::TITULOS)->delete();
    }
};
