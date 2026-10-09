<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_laboratorio_datos', function (Blueprint $table) {
            // Valor con el que se precarga el resultado al registrarlo.
            $table->string('valor_defecto')->nullable()->after('rango_referencia');
        });

        // Lista cerrada de valores posibles para un dato (COLOR: AMARILLO,
        // BLANCO, …). Si un dato no tiene opciones, se escribe libremente.
        Schema::create('producto_laboratorio_dato_opciones', function (Blueprint $table) {
            $table->id();
            // El nombre del índice va explícito: el que genera Laravel a partir
            // de tabla + columna pasa los 64 caracteres que admite MySQL.
            $table->foreignId('producto_laboratorio_dato_id')
                ->constrained('producto_laboratorio_datos', indexName: 'lab_dato_opciones_dato_id_fk')
                ->cascadeOnDelete();
            $table->string('valor');
            $table->unsignedInteger('orden')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_laboratorio_dato_opciones');

        Schema::table('producto_laboratorio_datos', function (Blueprint $table) {
            $table->dropColumn('valor_defecto');
        });
    }
};
