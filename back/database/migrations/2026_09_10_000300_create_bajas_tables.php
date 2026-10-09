<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bajas de farmacia: salidas de inventario que no son ventas (vencimiento,
 * cruce, bonificación, deterioro, etc.). Cada detalle apunta al lote exacto
 * (`compra_detalles`) para que el descuento de stock sea por lote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('fecha_hora');
            $table->string('motivo', 40);
            $table->text('observacion')->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('anulado_por')->nullable()->constrained('users');
            $table->dateTime('anulado_en')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('baja_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baja_id')->constrained('bajas');
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('compra_detalle_id')->nullable()->constrained('compra_detalles');
            $table->string('nombre');
            $table->string('lote')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('cantidad', 12, 4);
            $table->decimal('precio', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('motivo', 40)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('compra_detalle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baja_detalles');
        Schema::dropIfExists('bajas');
    }
};
