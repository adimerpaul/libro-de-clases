<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instrumento de evaluación (prueba, control, ticket…) de un docente para una asignación
        Schema::create('instrumentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('objetivo_aprendizaje_id')->nullable()->constrained('objetivos_aprendizaje')->nullOnDelete();
            $table->string('nombre', 200);
            $table->string('tipo', 40);
            $table->date('fecha_aplicacion')->nullable();
            // % de logro que corresponde a la nota 4,0 (escala 1,0 – 7,0)
            $table->unsignedTinyInteger('exigencia')->default(60);
            $table->text('instrucciones')->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->timestamps();
        });

        Schema::create('instrumento_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->string('tipo', 20); // seleccion | verdadero_falso | desarrollo
            $table->text('enunciado');
            $table->json('alternativas')->nullable();
            // Índice de la alternativa correcta (selección) o "V"/"F"
            $table->string('respuesta_correcta', 5)->nullable();
            // Pauta de corrección para preguntas de desarrollo
            $table->text('criterios')->nullable();
            $table->decimal('puntaje', 5, 1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrumento_preguntas');
        Schema::dropIfExists('instrumentos');
    }
};
