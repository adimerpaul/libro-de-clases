<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactivos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->string('unidad', 30);
            $table->decimal('stock_actual', 12, 3)->default(0);
            $table->decimal('stock_minimo', 12, 3)->default(0);
            $table->string('estado', 20)->default('ACTIVO');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('servicio_laboratorio_reactivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('reactivo_id')->constrained('reactivos');
            $table->decimal('cantidad', 12, 4);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_laboratorio_reactivos');
        Schema::dropIfExists('reactivos');
    }
};
