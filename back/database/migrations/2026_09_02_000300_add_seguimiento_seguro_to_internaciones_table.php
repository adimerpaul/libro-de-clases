<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seguimiento de facturación al seguro para cada internación.
 * Reproduce la planilla que lleva la clínica: entrega de informe,
 * respuesta de auditoría, facturación, cancelación y tipo de pago.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internaciones', function (Blueprint $table) {
            $table->date('entrega_informe')->nullable()->after('sala');
            $table->date('respuesta_auditoria')->nullable()->after('entrega_informe');
            $table->date('fecha_facturacion')->nullable()->after('respuesta_auditoria');
            $table->decimal('monto_facturado', 12, 2)->nullable()->after('fecha_facturacion');
            $table->date('fecha_cancelacion')->nullable()->after('monto_facturado');
            $table->string('tipo_pago', 30)->nullable()->after('fecha_cancelacion');
            $table->text('observacion_seguro')->nullable()->after('tipo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('internaciones', function (Blueprint $table) {
            $table->dropColumn([
                'entrega_informe',
                'respuesta_auditoria',
                'fecha_facturacion',
                'monto_facturado',
                'fecha_cancelacion',
                'tipo_pago',
                'observacion_seguro',
            ]);
        });
    }
};
