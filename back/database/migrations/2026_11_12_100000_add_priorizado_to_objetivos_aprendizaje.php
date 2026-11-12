<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** OA marcado como priorizado en curriculumnacional.cl (Priorización Curricular Mineduc). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objetivos_aprendizaje', function (Blueprint $table) {
            $table->boolean('priorizado')->default(false)->after('es_demo');
        });
    }

    public function down(): void
    {
        Schema::table('objetivos_aprendizaje', function (Blueprint $table) {
            $table->dropColumn('priorizado');
        });
    }
};
