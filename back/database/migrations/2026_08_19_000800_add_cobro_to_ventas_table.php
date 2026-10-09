<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('cobrado_por_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_hora_cobro')->nullable()->after('fecha_hora');
            $table->index(['cobrado_por_id', 'fecha_hora_cobro']);
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cobrado_por_id']);
            $table->dropIndex(['cobrado_por_id', 'fecha_hora_cobro']);
            $table->dropColumn(['cobrado_por_id', 'fecha_hora_cobro']);
        });
    }
};
