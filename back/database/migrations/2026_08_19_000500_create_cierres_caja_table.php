<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('fecha');
            // Total que el sistema calculó al momento de cerrar (ventas ACTIVO del día).
            $table->decimal('monto_sistema', 14, 2)->default(0);
            // Efectivo que contó y digitó el cajero.
            $table->decimal('monto', 14, 2)->default(0);
            $table->decimal('diferencia', 14, 2)->default(0);
            $table->unsignedInteger('cantidad_ventas')->default(0);
            $table->dateTime('fecha_hora');
            $table->string('comentario', 500)->nullable();
            // El cierre admite una sola corrección: al usarla queda bloqueado.
            $table->dateTime('modificado_en')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Una caja por usuario y por día: es la regla del negocio.
            $table->unique(['user_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
