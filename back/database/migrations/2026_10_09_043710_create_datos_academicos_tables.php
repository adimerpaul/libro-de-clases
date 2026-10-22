<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaturas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('codigo', 10)->unique();
            $table->string('color', 7)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->string('nivel', 20);
            $table->string('letra', 2);
            $table->foreignId('profesor_jefe_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['anio', 'nivel', 'letra']);
        });

        // Qué docente dicta qué asignatura en qué curso (una asignatura tiene un solo docente por curso)
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->restrictOnDelete();
            $table->foreignId('docente_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('horas_semanales')->nullable();
            $table->timestamps();

            $table->unique(['curso_id', 'asignatura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('asignaturas');
    }
};
