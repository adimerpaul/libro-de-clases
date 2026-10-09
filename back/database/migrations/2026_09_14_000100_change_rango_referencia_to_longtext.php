<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // El rango de referencia ahora se edita con un editor HTML (WYSIWYG): el
    // marcado ocupa bastante más que el texto plano, así que se amplía a LONGTEXT.
    public function up(): void
    {
        foreach (['producto_laboratorio_datos', 'solicitud_laboratorio_resultados'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->longText('rango_referencia')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['producto_laboratorio_datos', 'solicitud_laboratorio_resultados'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->text('rango_referencia')->nullable()->change();
            });
        }
    }
};
