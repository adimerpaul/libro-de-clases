<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internaciones', function (Blueprint $table) {
            // Pago total del paciente: con esto la internación queda cerrada y bloqueada.
            $table->timestamp('pagado_en')->nullable()->after('sala');
            $table->foreignId('pagado_por_id')->nullable()->after('pagado_en')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('venta_id')->nullable()->after('pagado_por_id')
                ->constrained('ventas')->nullOnDelete();
            $table->decimal('monto_pagado', 14, 2)->nullable()->after('venta_id');
            $table->string('pago_tipo', 30)->nullable()->after('monto_pagado');
            $table->string('pago_observacion', 255)->nullable()->after('pago_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('internaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pagado_por_id');
            $table->dropConstrainedForeignId('venta_id');
            $table->dropColumn(['pagado_en', 'monto_pagado', 'pago_tipo', 'pago_observacion']);
        });
    }
};
