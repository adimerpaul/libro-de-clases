<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cuánto de cada venta entró en efectivo y cuánto por QR. En pago MIXTO se
 * reparte; monto_efectivo es lo que queda en caja (sin el cambio devuelto),
 * así monto_efectivo + monto_qr = total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('monto_efectivo', 10, 2)->default(0)->after('cambio');
            $table->decimal('monto_qr', 10, 2)->default(0)->after('monto_efectivo');
        });

        // Ventas ya cobradas: todo el total va al medio con que se pagaron.
        // Los gastos salen de caja en efectivo.
        $cobradas = fn () => DB::table('ventas')
            ->where(fn ($q) => $q->where('estado', 'ACTIVO')->orWhereNotNull('fecha_hora_cobro'));

        $cobradas()->where(fn ($q) => $q->where('tipo_pago', 'EFECTIVO')->orWhereNull('tipo_pago'))
            ->update(['monto_efectivo' => DB::raw('total')]);
        $cobradas()->where('tipo_pago', 'QR')
            ->update(['monto_qr' => DB::raw('total')]);
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['monto_efectivo', 'monto_qr']);
        });
    }
};
