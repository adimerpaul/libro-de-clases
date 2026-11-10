<?php

use App\Support\ImportadorCurriculo;
use Illuminate\Database\Migrations\Migration;

/**
 * Lleva los OA oficiales del Módulo Curricular (tablas de create_curriculo_nacional_tables)
 * al catálogo de /curriculum (objetivos_aprendizaje), con sus asignaturas. Así basta
 * `php artisan migrate` para tenerlos. En las pruebas no se ejecuta: cada prueba arma
 * sus propias asignaturas y OA.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningUnitTests()) {
            ImportadorCurriculo::sincronizar();
        }
    }

    public function down(): void
    {
        // Los OA quedan: pueden estar usados en planificaciones o instrumentos
    }
};
