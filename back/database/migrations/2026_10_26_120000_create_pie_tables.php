<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programa de Integración Escolar: un caso por estudiante y año escolar (validado en
 * código ignorando eliminados), con su expediente de documentos e imágenes y la
 * bitácora de seguimiento (sesiones, co-docencia, entrevistas, reuniones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pie_casos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->string('estado', 20)->default('en_evaluacion'); // en_evaluacion | activo | egresado
            $table->string('diagnostico', 120);
            $table->string('tipo_nee', 20); // transitoria | permanente (sale del diagnóstico)
            $table->string('diagnostico_detalle', 500)->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_evaluacion')->nullable();
            $table->date('fecha_reevaluacion')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('equipo')->nullable();  // [{especialidad, nombre}]
            $table->json('apoyos')->nullable();  // ["Aula de recursos", …]
            $table->unsignedTinyInteger('horas_semanales')->nullable();
            $table->text('fortalezas')->nullable();
            $table->text('barreras')->nullable();
            $table->text('necesidades_apoyo')->nullable();
            $table->text('acuerdos_familia')->nullable();
            $table->text('observaciones')->nullable();
            $table->date('fecha_egreso')->nullable();
            $table->string('motivo_egreso', 300)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['anio', 'estado']);
            $table->index(['estudiante_id', 'anio']);
        });

        Schema::create('pie_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pie_caso_id')->constrained('pie_casos')->cascadeOnDelete();
            $table->string('tipo', 60);
            $table->string('titulo', 200);
            $table->date('fecha')->nullable();
            $table->string('descripcion', 1000)->nullable();
            $table->string('archivo_path');
            $table->string('archivo_nombre');
            $table->string('archivo_mime', 100)->nullable();
            $table->unsignedBigInteger('archivo_tamano')->nullable();
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pie_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pie_caso_id')->constrained('pie_casos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tipo', 60);
            $table->string('area', 60)->nullable();
            $table->unsignedSmallInteger('duracion_minutos')->nullable();
            $table->text('descripcion');
            $table->text('acuerdos')->nullable();
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pie_caso_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pie_seguimientos');
        Schema::dropIfExists('pie_documentos');
        Schema::dropIfExists('pie_casos');
    }
};
