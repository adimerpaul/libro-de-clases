<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\TipoProducto;
use Illuminate\Database\Seeder;

class LaboratorioTipoSeeder extends Seeder
{
    /**
     * Áreas de laboratorio. Se guardan como tipos de producto marcados con
     * es_laboratorio, junto a FARMACIA, ECOGRAFIA y demás.
     */
    public const AREAS = [
        ['nombre' => 'HEMATOLOGIA', 'color' => 'red-8'],
        ['nombre' => 'QUIMICA SANGUINEA', 'color' => 'blue-8'],
        ['nombre' => 'UROANALISIS', 'color' => 'amber-8'],
        ['nombre' => 'PARASITOLOGIA', 'color' => 'green-8'],
        ['nombre' => 'MICROBIOLOGIA', 'color' => 'purple-8'],
        ['nombre' => 'INMUNOLOGIA', 'color' => 'teal-8'],
        ['nombre' => 'COAGULACION', 'color' => 'deep-orange-8'],
        ['nombre' => 'HORMONAS', 'color' => 'pink-8'],
        ['nombre' => 'SEROLOGIA', 'color' => 'indigo-8'],
        ['nombre' => 'MARCADORES TUMORALES', 'color' => 'brown-8'],
    ];

    /**
     * Reglas para reubicar los exámenes que cuelgan de LABORATORIOS. Se evalúan
     * en orden y gana la primera que coincida. Los patrones se buscan como
     * palabra completa: si no, "GRAM" se comería "hemoGRAMa" y "leucoGRAMa".
     * Por eso las áreas específicas (tinciones, cultivos) van antes que las
     * genéricas de PARASITOLOGIA ("FRESCO", "HECES").
     */
    private const REGLAS = [
        ['area' => 'MICROBIOLOGIA', 'patrones' => ['GRAM', 'CULTIVO', 'ANTIBIOGRAMA', 'BACTERIOLOGIA', 'BAAR', 'ZIEHL']],
        ['area' => 'UROANALISIS', 'patrones' => ['ORINA', 'UROANALISIS', 'UROCULTIVO']],
        ['area' => 'COAGULACION', 'patrones' => ['PROTROMBINA', 'COAGULOGRAMA', 'COAGULACION', 'TROMBOPLASTINA', 'INR']],
        // QUIMICA va antes que HEMATOLOGIA: la hemoglobina glicosilada lleva
        // "HEMOGLOBINA" en el nombre pero es química sanguínea.
        ['area' => 'QUIMICA SANGUINEA', 'patrones' => ['GLUCOSA', 'GLICOSILADA', 'COLESTEROL', 'TRIGLICERIDOS', 'CREATININA', 'UREA', 'URICO', 'TRANSAMINASAS', 'BILIRRUBINA', 'LIPIDICO', 'AMILASA', 'FOSFATASA']],
        ['area' => 'HEMATOLOGIA', 'patrones' => ['HEMOGRAMA', 'HEMOGLOBINA', 'HEMATOCRITO', 'LEUCOGRAMA', 'PLAQUETAS', 'RETICULOCITOS', 'SANGUINEO', 'GASOMETRIA', 'SANGRIA', 'ERITROSEDIMENTACION', 'VES']],
        ['area' => 'PARASITOLOGIA', 'patrones' => ['COPROPARASITOLOGICO', 'PARASITOLOGICO', 'PARASITOS', 'FRESCO', 'FECAL', 'HECES', 'COPROLOGICO']],
        ['area' => 'SEROLOGIA', 'patrones' => ['SEROLOGIA', 'VDRL', 'RPR', 'HIV', 'HEPATITIS', 'CHAGAS', 'DENGUE', 'WIDAL']],
        ['area' => 'HORMONAS', 'patrones' => ['HORMONA', 'TSH', 'T3', 'T4', 'PROLACTINA', 'TESTOSTERONA', 'ESTRADIOL', 'BHCG']],
        ['area' => 'MARCADORES TUMORALES', 'patrones' => ['MARCADOR', 'PROSTATICO', 'PSA', 'CEA', 'CA 125', 'AFP']],
        ['area' => 'INMUNOLOGIA', 'patrones' => ['INMUNOLOGIA', 'INMUNOGLOBULINA', 'ANTICUERPOS', 'ANTIGENO', 'FACTOR REUMATOIDEO', 'PCR', 'ASTO']],
    ];

    public function run(): void
    {
        // LABORATORIOS se conserva como área genérica: recoge los exámenes que
        // ninguna regla clasifica, para que nada quede fuera de la pantalla.
        $generica = TipoProducto::where('nombre', 'LABORATORIOS')->first();
        if ($generica) {
            $generica->update(['es_laboratorio' => true, 'orden' => 99]);
        }

        $areas = [];
        foreach (self::AREAS as $indice => $area) {
            $areas[$area['nombre']] = TipoProducto::updateOrCreate(
                ['nombre' => $area['nombre']],
                ['color' => $area['color'], 'es_laboratorio' => true, 'orden' => $indice + 1]
            );
        }

        if (! $generica) {
            return;
        }

        // Reclasifica lo que sigue colgando del área genérica.
        Producto::where('tipo_producto_id', $generica->id)
            ->get()
            ->each(function (Producto $producto) use ($areas) {
                $area = $this->areaPara($producto->nombre);
                if ($area && isset($areas[$area])) {
                    $producto->update(['tipo_producto_id' => $areas[$area]->id]);
                }
            });
    }

    private function areaPara(?string $nombre): ?string
    {
        $nombre = mb_strtoupper((string) $nombre);

        foreach (self::REGLAS as $regla) {
            foreach ($regla['patrones'] as $patron) {
                if (preg_match('/\b'.preg_quote($patron, '/').'\b/u', $nombre)) {
                    return $regla['area'];
                }
            }
        }

        return null;
    }
}
