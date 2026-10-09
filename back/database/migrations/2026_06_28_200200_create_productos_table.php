<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('marca', 150)->nullable();
            $table->foreignId('fabricante_id')->nullable()->constrained('fabricantes')->nullOnDelete();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->string('tipo', 80)->default('FARMACIA');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'nombre']);
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
