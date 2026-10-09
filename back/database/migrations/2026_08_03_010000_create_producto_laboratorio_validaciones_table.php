<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_laboratorio_validaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->text('expresion');
            $table->string('operador', 10);
            $table->decimal('valor', 14, 4);
            $table->decimal('valor_hasta', 14, 4)->nullable();
            $table->string('mensaje');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_laboratorio_validaciones');
    }
};
