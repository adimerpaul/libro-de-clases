<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internacion_id')->constrained('internaciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['internacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internacion_items');
    }
};
