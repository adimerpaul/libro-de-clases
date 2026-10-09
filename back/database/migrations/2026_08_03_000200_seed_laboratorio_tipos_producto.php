<?php

use Database\Seeders\LaboratorioTipoSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Aplica LaboratorioTipoSeeder sobre las bases ya migradas: crea las áreas
     * de laboratorio y reubica los exámenes que colgaban de LABORATORIOS.
     * El seeder es idempotente, así que volver a correrlo no duplica nada.
     */
    public function up(): void
    {
        (new LaboratorioTipoSeeder)->run();
    }

    public function down(): void
    {
        // Las áreas quedan: borrarlas dejaría productos sin tipo.
    }
};
