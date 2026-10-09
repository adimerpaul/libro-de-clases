<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nit', 50)->nullable();
            $table->string('razon_social')->nullable();
            $table->string('contacto')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('estado', 30)->default('ACTIVO');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
