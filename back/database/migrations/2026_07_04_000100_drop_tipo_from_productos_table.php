<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'nombre']);
            $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('tipo', 80)->default('FARMACIA')->after('unidad_id');
            $table->index(['tipo', 'nombre']);
        });
    }
};
