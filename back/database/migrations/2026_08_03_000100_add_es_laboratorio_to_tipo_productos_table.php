<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_productos', function (Blueprint $table) {
            // Marca las áreas de laboratorio (HEMATOLOGIA, UROANALISIS, …) para
            // distinguirlas de los demás tipos (FARMACIA, ECOGRAFIA, …) sin
            // depender del nombre.
            $table->boolean('es_laboratorio')->default(false)->after('color');
            $table->unsignedInteger('orden')->default(0)->after('es_laboratorio');
        });
    }

    public function down(): void
    {
        Schema::table('tipo_productos', function (Blueprint $table) {
            $table->dropColumn(['es_laboratorio', 'orden']);
        });
    }
};
