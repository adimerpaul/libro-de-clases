<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->foreignId('compra_detalle_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('compra_detalles')
                ->nullOnDelete();
            $table->string('lote')->nullable()->after('nombre');
            $table->date('fecha_vencimiento')->nullable()->after('lote');
            $table->index(['compra_detalle_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropIndex(['compra_detalle_id', 'producto_id']);
            $table->dropConstrainedForeignId('compra_detalle_id');
            $table->dropColumn(['lote', 'fecha_vencimiento']);
        });
    }
};
