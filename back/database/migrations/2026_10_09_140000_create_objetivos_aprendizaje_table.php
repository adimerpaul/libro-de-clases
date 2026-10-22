<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo curricular versionado (ui2/curriculum_import_schema.json): los OA viven
 * aquí con su fuente y vigencia, nunca en el código del front.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objetivos_aprendizaje', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40);
            $table->string('nivel', 20);
            $table->foreignId('asignatura_id')->constrained('asignaturas')->restrictOnDelete();
            $table->string('unidad', 150)->nullable();
            $table->text('texto');
            $table->json('habilidades')->nullable();
            $table->json('indicadores')->nullable();
            $table->string('fuente', 255)->nullable();
            $table->string('fuente_url', 255)->nullable();
            $table->string('version_curricular', 40);
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->string('fuente_hash', 64)->nullable();
            // true = resumen de demostración, no texto oficial del Mineduc
            $table->boolean('es_demo')->default(false);
            $table->timestamps();

            $table->unique(['codigo', 'version_curricular']);
            $table->index(['nivel', 'asignatura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objetivos_aprendizaje');
    }
};
