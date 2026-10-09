<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('caja', ['ADMINISTRATIVA', 'GENERAL']);
            $table->enum('tipo', ['INGRESO', 'GASTO']);
            $table->dateTime('fecha_hora');
            $table->string('categoria', 100)->nullable();
            $table->string('concepto', 255);
            $table->text('descripcion')->nullable();
            $table->string('beneficiario', 255)->nullable();
            $table->string('documento', 100)->nullable();
            $table->decimal('importe', 14, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['caja', 'tipo', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
