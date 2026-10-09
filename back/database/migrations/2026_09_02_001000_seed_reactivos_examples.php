<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ejemplos = [
            ['R-001', 'REACTIVO DE GLUCOSA', 'ML', 500, 100, 'GLUCOSA', 0.50],
            ['R-002', 'REACTIVO DE COLESTEROL', 'ML', 500, 100, 'COLESTEROL', 0.50],
            ['R-003', 'REACTIVO DE TRIGLICÉRIDOS', 'ML', 500, 100, 'TRIGLIC', 0.50],
            ['R-004', 'REACTIVO DE CREATININA', 'ML', 500, 100, 'CREATININA', 0.50],
            ['R-005', 'REACTIVO DE UREA', 'ML', 500, 100, 'UREA', 0.50],
            ['R-006', 'REACTIVO GOT / AST', 'ML', 250, 50, 'GOT', 0.40],
            ['R-007', 'REACTIVO GPT / ALT', 'ML', 250, 50, 'GPT', 0.40],
            ['R-008', 'REACTIVO DE HEMOGLOBINA', 'ML', 500, 100, 'HEMOGLOBINA', 0.25],
            ['R-009', 'TIRAS REACTIVAS DE ORINA', 'TIRA', 100, 20, 'ORINA', 1.00],
            ['R-010', 'REACTIVO PCR LÁTEX', 'ML', 100, 20, 'PROTEÍNA C REACTIVA', 0.10],
        ];

        foreach ($ejemplos as [$codigo, $nombre, $unidad, $stock, $minimo, $servicio, $cantidad]) {
            $reactivoId = DB::table('reactivos')->insertGetId([
                'codigo' => $codigo, 'nombre' => $nombre, 'unidad' => $unidad,
                'stock_actual' => $stock, 'stock_minimo' => $minimo, 'estado' => 'ACTIVO',
                'descripcion' => 'EJEMPLO INICIAL', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $productoId = DB::table('productos')->where('nombre', 'like', "%{$servicio}%")
                ->whereNull('deleted_at')->value('id');
            if ($productoId) {
                DB::table('servicio_laboratorio_reactivos')->insert([
                    'producto_id' => $productoId, 'reactivo_id' => $reactivoId,
                    'cantidad' => $cantidad, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('reactivos')->whereBetween('codigo', ['R-001', 'R-010'])->pluck('id');
        DB::table('servicio_laboratorio_reactivos')->whereIn('reactivo_id', $ids)->delete();
        DB::table('reactivos')->whereIn('id', $ids)->delete();
    }
};
