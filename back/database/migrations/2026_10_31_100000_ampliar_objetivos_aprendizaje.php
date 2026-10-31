<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo curricular de ui3 (oaDetail): OA → contenidos → habilidades y actitudes →
 * indicadores → instrumentos sugeridos → estrategias → inicio/desarrollo/cierre →
 * recursos → tiempo y espacio. Los indicadores alimentan el creador de evaluaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objetivos_aprendizaje', function (Blueprint $table) {
            $table->json('actitudes')->nullable()->after('habilidades');
            $table->text('contenidos')->nullable()->after('texto');
            $table->json('instrumentos')->nullable()->after('indicadores');
            $table->text('estrategias')->nullable()->after('instrumentos');
            $table->text('inicio')->nullable()->after('estrategias');
            $table->text('desarrollo')->nullable()->after('inicio');
            $table->text('cierre')->nullable()->after('desarrollo');
            $table->json('recursos')->nullable()->after('cierre');
            $table->string('tiempo_espacio', 200)->nullable()->after('recursos');
        });
    }

    public function down(): void
    {
        Schema::table('objetivos_aprendizaje', function (Blueprint $table) {
            $table->dropColumn(['actitudes', 'contenidos', 'instrumentos', 'estrategias', 'inicio', 'desarrollo', 'cierre', 'recursos', 'tiempo_espacio']);
        });
    }
};
