<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            // Cantidad contada por corte: {"200": 3, "100": 5, ..., "0.1": 4}.
            $table->json('detalle_efectivo')->nullable()->after('monto');
        });
    }

    public function down(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropColumn('detalle_efectivo');
        });
    }
};
