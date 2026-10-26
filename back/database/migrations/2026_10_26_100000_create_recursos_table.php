<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Biblioteca pedagógica: actividades, guías, presentaciones, videos, material didáctico y apoyos DUA/PIE
        Schema::create('recursos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->string('tipo', 40);
            $table->text('descripcion')->nullable();
            $table->string('nivel', 30)->nullable(); // null = transversal
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas')->nullOnDelete();
            $table->foreignId('objetivo_aprendizaje_id')->nullable()->constrained('objetivos_aprendizaje')->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->string('archivo_path')->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->unsignedBigInteger('archivo_tamano')->nullable();
            $table->boolean('publicado')->default(true);
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publicado', 'nivel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos');
    }
};
