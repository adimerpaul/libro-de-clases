<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estudiantes (ficha) y matrículas: en qué curso está cada estudiante en cada año
 * escolar. El RUN y "una matrícula activa por estudiante y año" se validan en
 * código ignorando los eliminados (soft delete), como el resto de los únicos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('run', 12)->index();
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 60);
            $table->string('apellido_materno', 60)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo', 1)->nullable(); // F | M | X
            $table->string('direccion', 200)->nullable();
            $table->string('apoderado_nombre', 150)->nullable();
            $table->string('apoderado_run', 12)->nullable();
            $table->string('apoderado_telefono', 50)->nullable();
            $table->string('apoderado_email', 150)->nullable();
            $table->boolean('pie')->default(false); // Programa de Integración Escolar
            $table->text('observaciones')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['apellido_paterno', 'apellido_materno', 'nombres']);
        });

        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_lista')->nullable();
            $table->date('fecha_matricula');
            $table->string('estado', 20)->default('activa'); // activa | retirada | trasladada
            $table->date('fecha_retiro')->nullable();
            $table->string('motivo_retiro', 200)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['curso_id', 'numero_lista']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
        Schema::dropIfExists('estudiantes');
    }
};
