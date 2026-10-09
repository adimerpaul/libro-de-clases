<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->enum('sexo', ['M', 'F'])->nullable();
            $table->string('ci', 30)->nullable();
            $table->string('estado', 50)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
