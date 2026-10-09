<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una "venta" también puede ser un gasto de caja (un refresco, el periódico):
 * tipo_movimiento distingue el dinero que entra del que sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('tipo_movimiento', 20)->default('INGRESO')->after('user_id');
            $table->index(['tipo_movimiento', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['tipo_movimiento', 'fecha_hora']);
            $table->dropColumn('tipo_movimiento');
        });
    }
};
