<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libro de Clases PIE de cada curso (formato del libro impreso, ui/Libro de Clases PIE.html).
 * Cada hoja del libro es un documento JSON identificado por su clave: "general", "plan",
 * "matricula", "familia", "entrevistas", "hojas", "asistencia/2026-11",
 * "actividades/2026-11-10" o "evaluaciones/1S". La firma del registro diario la pone el
 * servidor con el usuario autenticado (firmado_por / firmado_en).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pie_libro_hojas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('clave', 40);
            $table->json('datos');
            $table->foreignId('firmado_por')->nullable()->constrained('users');
            $table->timestamp('firmado_en')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['curso_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pie_libro_hojas');
    }
};
