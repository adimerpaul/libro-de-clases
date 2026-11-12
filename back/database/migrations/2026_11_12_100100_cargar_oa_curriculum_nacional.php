<?php

use App\Support\CurriculoNacional;
use Illuminate\Database\Migrations\Migration;

/**
 * Llena /curriculum con todos los OA publicados en curriculumnacional.cl (Parvularia, 1° Básico
 * a 4° Medio, diferenciados HC y especialidades TP) desde database/data/curriculo_nacional_oa.json,
 * creando las asignaturas que falten. En las pruebas no se ejecuta: cada prueba arma sus OA.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningUnitTests()) {
            CurriculoNacional::cargar();
        }
    }

    public function down(): void
    {
        // Los OA quedan: pueden estar usados en planificaciones o instrumentos
    }
};
