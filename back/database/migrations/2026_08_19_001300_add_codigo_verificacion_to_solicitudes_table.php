<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->string('codigo_verificacion', 32)->nullable()->after('codigo_solicitud');
        });

        DB::table('solicitudes')->select('id')->orderBy('id')->each(function ($solicitude) {
            do {
                $codigo = Str::random(32);
            } while (DB::table('solicitudes')->where('codigo_verificacion', $codigo)->exists());

            DB::table('solicitudes')->where('id', $solicitude->id)->update([
                'codigo_verificacion' => $codigo,
            ]);
        });

        Schema::table('solicitudes', function (Blueprint $table) {
            $table->unique('codigo_verificacion');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropUnique(['codigo_verificacion']);
            $table->dropColumn('codigo_verificacion');
        });
    }
};
