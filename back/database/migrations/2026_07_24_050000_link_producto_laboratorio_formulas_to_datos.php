<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('producto_laboratorio_formulas', 'producto_laboratorio_dato_id')) {
            Schema::table('producto_laboratorio_formulas', function (Blueprint $table) {
                $table->unsignedBigInteger('producto_laboratorio_dato_id')
                    ->nullable()
                    ->after('producto_id');
            });
        }

        Schema::table('producto_laboratorio_formulas', function (Blueprint $table) {
            $table->foreign('producto_laboratorio_dato_id', 'pl_formula_dato_fk')
                ->references('id')
                ->on('producto_laboratorio_datos')
                ->cascadeOnDelete();
        });

        DB::table('producto_laboratorio_formulas')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->each(function ($formula) {
                $datoId = DB::table('producto_laboratorio_datos')
                    ->where('producto_id', $formula->producto_id)
                    ->where('nombre_variable', $formula->nombre_variable)
                    ->whereNull('deleted_at')
                    ->value('id');

                if (! $datoId) {
                    $ultimoOrden = (int) DB::table('producto_laboratorio_datos')
                        ->where('producto_id', $formula->producto_id)
                        ->whereNull('deleted_at')
                        ->max('orden');

                    $datoId = DB::table('producto_laboratorio_datos')->insertGetId([
                        'producto_id' => $formula->producto_id,
                        'nombre' => $formula->nombre ?: mb_strtoupper($formula->nombre_variable),
                        'nombre_variable' => $formula->nombre_variable,
                        'unidad' => $formula->unidad,
                        'rango_referencia' => null,
                        'orden' => $ultimoOrden + 10,
                        'visible' => $formula->visible,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('producto_laboratorio_formulas')
                    ->where('id', $formula->id)
                    ->update([
                        'producto_laboratorio_dato_id' => $datoId,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('producto_laboratorio_formulas', function (Blueprint $table) {
            $table->dropForeign('pl_formula_dato_fk');
            $table->dropColumn('producto_laboratorio_dato_id');
        });
    }
};
