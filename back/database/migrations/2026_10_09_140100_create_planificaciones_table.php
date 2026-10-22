<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Planificación de una clase: asignación (curso + asignatura) del docente, fecha y OA
        Schema::create('planificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('objetivo_aprendizaje_id')->nullable()->constrained('objetivos_aprendizaje')->nullOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->unsignedSmallInteger('duracion_minutos')->default(90);
            $table->string('titulo', 200);
            $table->text('inicio')->nullable();
            $table->text('desarrollo')->nullable();
            $table->text('cierre')->nullable();
            $table->string('evidencia', 200)->nullable();
            $table->text('recursos')->nullable();
            $table->text('adecuaciones')->nullable();
            $table->string('estado', 20)->default('planificada');
            $table->timestamps();

            $table->index(['docente_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planificaciones');
    }
};
