<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('doctores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ci')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('registro')->nullable();
            $table->string('estado', 30)->default('ACTIVO');
            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
        });

        Schema::create('doctor_especialidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctores')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['doctor_id', 'especialidad_id']);
        });

        $especialidades = [
            'MEDICINA GENERAL', 'PEDIATRÍA', 'GINECOLOGÍA Y OBSTETRICIA', 'CIRUGÍA GENERAL',
            'TRAUMATOLOGÍA Y ORTOPEDIA', 'MEDICINA INTERNA', 'CARDIOLOGÍA', 'NEUROLOGÍA',
            'UROLOGÍA', 'OTORRINOLARINGOLOGÍA', 'OFTALMOLOGÍA', 'DERMATOLOGÍA',
            'GASTROENTEROLOGÍA', 'NEUMOLOGÍA', 'ANESTESIOLOGÍA', 'PSIQUIATRÍA',
            'ENDOCRINOLOGÍA', 'NEFROLOGÍA', 'ONCOLOGÍA', 'RADIOLOGÍA',
        ];
        $now = now();
        DB::table('especialidades')->insert(array_map(
            fn ($nombre) => ['nombre' => $nombre, 'created_at' => $now, 'updated_at' => $now],
            $especialidades
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_especialidad');
        Schema::dropIfExists('doctores');
        Schema::dropIfExists('especialidades');
    }
};
