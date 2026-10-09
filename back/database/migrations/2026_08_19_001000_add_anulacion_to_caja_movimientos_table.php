<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->enum('estado', ['ACTIVO', 'ANULADO'])->default('ACTIVO')->after('importe');
            $table->foreignId('anulado_por_id')->nullable()->after('estado')->constrained('users');
            $table->dateTime('anulado_en')->nullable()->after('anulado_por_id');
            $table->string('motivo_anulacion', 500)->nullable()->after('anulado_en');
        });
    }

    public function down(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulado_por_id');
            $table->dropColumn(['estado', 'anulado_en', 'motivo_anulacion']);
        });
    }
};
