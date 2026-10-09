<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->date('fecha_ingreso')->nullable();
            $table->string('tipo_paciente', 50)->nullable();
            $table->date('fecha_alta')->nullable();
            $table->string('codigo_hc', 30)->nullable();
            $table->string('sala', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internaciones');
    }
};
