<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // `nombre` guarda el nombre genérico; el comercial es la marca con la
            // que se vende el mismo principio activo y puede repetirse o faltar.
            $table->string('nombre_comercial')->nullable()->after('nombre');
            $table->index('nombre_comercial');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['nombre_comercial']);
            $table->dropColumn('nombre_comercial');
        });
    }
};
