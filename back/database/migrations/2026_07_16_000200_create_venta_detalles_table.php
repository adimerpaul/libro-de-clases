<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('nombre');
            $table->decimal('precio', 14, 2)->default(0);
            $table->decimal('cantidad', 10, 4)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['venta_id', 'producto_id']);
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};
