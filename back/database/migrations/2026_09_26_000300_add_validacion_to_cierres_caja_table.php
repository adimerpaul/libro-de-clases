<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            // Quién revisó y dio por bueno el cierre, y cuándo. Validado, el cierre queda bloqueado.
            $table->foreignId('validado_por_id')->nullable()->after('modificado_en')->constrained('users');
            $table->dateTime('validado_en')->nullable()->after('validado_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validado_por_id');
            $table->dropColumn('validado_en');
        });
    }
};
