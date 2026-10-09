<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->after('paciente_id')->constrained('doctores')->nullOnDelete();
            $table->foreignId('seguro_id')->nullable()->after('doctor_id')->constrained('seguros')->nullOnDelete();
            $table->dropColumn('doctor');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('doctor_id');
            $table->dropConstrainedForeignId('seguro_id');
            $table->string('doctor')->nullable();
        });
    }
};
