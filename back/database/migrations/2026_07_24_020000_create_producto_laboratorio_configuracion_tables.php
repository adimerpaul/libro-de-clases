<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_laboratorio_datos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('nombre_variable', 100);
            $table->string('unidad', 100)->nullable();
            $table->text('rango_referencia')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('visible')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('producto_laboratorio_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre')->nullable();
            $table->string('nombre_variable', 100);
            $table->text('formula');
            $table->string('unidad', 100)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('visible')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_laboratorio_formulas');
        Schema::dropIfExists('producto_laboratorio_datos');
    }
};
