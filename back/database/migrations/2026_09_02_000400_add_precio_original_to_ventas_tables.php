<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Precio de lista al momento de la venta: deja registrado cuánto debería haber
 * costado cada línea cuando el cajero cambia el precio a mano.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->decimal('precio_original', 14, 2)->nullable()->after('precio');
            $table->decimal('total_original', 14, 2)->nullable()->after('total');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('total_original', 14, 2)->nullable()->after('total');
        });

        // Las ventas ya registradas no tuvieron cambio de precio conocido: se
        // asume que se cobró el precio de lista.
        DB::statement('UPDATE venta_detalles SET precio_original = precio, total_original = total WHERE precio_original IS NULL');
        DB::statement('UPDATE ventas SET total_original = total WHERE total_original IS NULL');
    }

    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropColumn(['precio_original', 'total_original']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('total_original');
        });
    }
};
