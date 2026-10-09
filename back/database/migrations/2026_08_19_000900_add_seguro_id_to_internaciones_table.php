<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('internaciones', 'seguro_id')) {
            Schema::table('internaciones', function (Blueprint $table) {
                $table->foreignId('seguro_id')->nullable()->after('paciente_id')
                    ->constrained('seguros')->nullOnDelete();
            });
        }

        DB::table('internaciones')
            ->whereNull('seguro_id')
            ->orderBy('id')
            ->eachById(function ($internacion) {
                $seguroId = DB::table('pacientes')->where('id', $internacion->paciente_id)->value('seguro_id');
                if ($seguroId) {
                    DB::table('internaciones')->where('id', $internacion->id)->update(['seguro_id' => $seguroId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('internaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seguro_id');
        });
    }
};
