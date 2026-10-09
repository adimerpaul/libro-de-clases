<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $formulasPorProducto = [
            'LEUCOGRAMA' => [
                ['NEUTROFILOS ABSOLUTOS', 'neutrofilos_absolutos', '(neutrofilos * globulos_blancos) / 100', '10^3/µL'],
                ['LINFOCITOS ABSOLUTOS', 'linfocitos_absolutos', '(linfocitos * globulos_blancos) / 100', '10^3/µL'],
                ['MONOCITOS ABSOLUTOS', 'monocitos_absolutos', '(monocitos * globulos_blancos) / 100', '10^3/µL'],
                ['EOSINOFILOS ABSOLUTOS', 'eosinofilos_absolutos', '(eosinofilos * globulos_blancos) / 100', '10^3/µL'],
                ['BASOFILOS ABSOLUTOS', 'basofilos_absolutos', '(basofilos * globulos_blancos) / 100', '10^3/µL'],
            ],
            'HB. GLICOSILADA' => [
                ['GLUCOSA PROMEDIO ESTIMADA', 'glucosa_promedio_estimada', '(28.7 * hba1c) - 46.7', 'mg/dL'],
            ],
            'HEMOGLOBINA GLICOSILADA' => [
                ['GLUCOSA PROMEDIO ESTIMADA', 'glucosa_promedio_estimada', '(28.7 * hba1c) - 46.7', 'mg/dL'],
            ],
        ];

        foreach ($formulasPorProducto as $productoNombre => $formulas) {
            $productoId = DB::table('productos')
                ->where('nombre', $productoNombre)
                ->value('id');

            if (! $productoId) {
                continue;
            }

            foreach ($formulas as $index => [$nombre, $variable, $formula, $unidad]) {
                DB::table('producto_laboratorio_formulas')->updateOrInsert(
                    [
                        'producto_id' => $productoId,
                        'nombre_variable' => $variable,
                    ],
                    [
                        'nombre' => $nombre,
                        'formula' => $formula,
                        'unidad' => $unidad,
                        'orden' => ($index + 1) * 10,
                        'visible' => true,
                        'deleted_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Se conservan las fórmulas porque pueden haber sido ajustadas clínicamente.
    }
};
