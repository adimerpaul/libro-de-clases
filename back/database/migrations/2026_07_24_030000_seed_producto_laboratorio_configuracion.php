<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $catalogo = [
            'HEMOGRAMA COMPLETO' => [
                'datos' => [
                    ['GLOBULOS ROJOS', 'globulos_rojos', '10^6/µL', 'HOMBRES: 4.5–5.9 | MUJERES: 4.1–5.1'],
                    ['HEMOGLOBINA', 'hemoglobina', 'g/dL', 'HOMBRES: 13.5–17.5 | MUJERES: 12.0–15.5'],
                    ['HEMATOCRITO', 'hematocrito', '%', 'HOMBRES: 41–53 | MUJERES: 36–46'],
                    ['GLOBULOS BLANCOS', 'globulos_blancos', '10^3/µL', '4.0–11.0'],
                    ['NEUTROFILOS', 'neutrofilos', '%', '40–70'],
                    ['LINFOCITOS', 'linfocitos', '%', '20–45'],
                    ['MONOCITOS', 'monocitos', '%', '2–10'],
                    ['EOSINOFILOS', 'eosinofilos', '%', '0–6'],
                    ['BASOFILOS', 'basofilos', '%', '0–2'],
                    ['PLAQUETAS', 'plaquetas', '10^3/µL', '150–450'],
                ],
                'formulas' => [
                    ['VOLUMEN CORPUSCULAR MEDIO', 'vcm', '(hematocrito * 10) / globulos_rojos', 'fL'],
                    ['HEMOGLOBINA CORPUSCULAR MEDIA', 'hcm', '(hemoglobina * 10) / globulos_rojos', 'pg'],
                    ['CONCENTRACION DE HEMOGLOBINA CORPUSCULAR MEDIA', 'chcm', '(hemoglobina * 100) / hematocrito', 'g/dL'],
                ],
            ],
            'HEMOGLOBINA + HEMATOCRITO' => [
                'datos' => [
                    ['HEMOGLOBINA', 'hemoglobina', 'g/dL', 'HOMBRES: 13.5–17.5 | MUJERES: 12.0–15.5'],
                    ['HEMATOCRITO', 'hematocrito', '%', 'HOMBRES: 41–53 | MUJERES: 36–46'],
                ],
            ],
            'LEUCOGRAMA' => [
                'datos' => [
                    ['GLOBULOS BLANCOS', 'globulos_blancos', '10^3/µL', '4.0–11.0'],
                    ['NEUTROFILOS', 'neutrofilos', '%', '40–70'],
                    ['LINFOCITOS', 'linfocitos', '%', '20–45'],
                    ['MONOCITOS', 'monocitos', '%', '2–10'],
                    ['EOSINOFILOS', 'eosinofilos', '%', '0–6'],
                    ['BASOFILOS', 'basofilos', '%', '0–2'],
                ],
            ],
            'RECUENTO DE PLAQUETAS' => [
                'datos' => [['PLAQUETAS', 'plaquetas', '10^3/µL', '150–450']],
            ],
            'RECUENTO DE RETICULOCITOS' => [
                'datos' => [
                    ['RETICULOCITOS', 'reticulocitos', '%', '0.5–2.5'],
                    ['HEMATOCRITO DEL PACIENTE', 'hematocrito_paciente', '%', 'SEGUN PACIENTE'],
                ],
                'formulas' => [
                    ['RETICULOCITOS CORREGIDOS', 'reticulocitos_corregidos', '(reticulocitos * hematocrito_paciente) / 45', '%'],
                ],
            ],
            'GRUPO SANGUINEO Y FACTOR' => [
                'datos' => [
                    ['GRUPO SANGUINEO', 'grupo_sanguineo', null, 'A, B, AB U O'],
                    ['FACTOR RH', 'factor_rh', null, 'POSITIVO O NEGATIVO'],
                ],
            ],
            'TIEMPO DE PROTROMBINA + INR ACTIVIDAD (COAGULOGRAMA)' => [
                'datos' => [
                    ['TIEMPO DE PROTROMBINA', 'tiempo_protrombina', 'seg', '11–14'],
                    ['INR', 'inr', null, '0.8–1.2'],
                    ['ACTIVIDAD DE PROTROMBINA', 'actividad_protrombina', '%', '70–120'],
                ],
            ],
            'TIEMPO DE SANGRIA Y COAGULACION' => [
                'datos' => [
                    ['TIEMPO DE SANGRIA', 'tiempo_sangria', 'min', '2–7'],
                    ['TIEMPO DE COAGULACION', 'tiempo_coagulacion', 'min', '5–15'],
                ],
            ],
            'GASOMETRIA ARTERIAL (HEMATOLOGIA)' => [
                'datos' => [
                    ['PH', 'ph', null, '7.35–7.45'],
                    ['PCO2', 'pco2', 'mmHg', '35–45'],
                    ['PO2', 'po2', 'mmHg', '80–100'],
                    ['BICARBONATO', 'bicarbonato', 'mmol/L', '22–26'],
                    ['EXCESO DE BASE', 'exceso_base', 'mmol/L', '-2 A +2'],
                    ['SATURACION DE OXIGENO', 'saturacion_oxigeno', '%', '95–100'],
                    ['LACTATO', 'lactato', 'mmol/L', '0.5–2.2'],
                ],
            ],
            'GLUCOSA (QUIMICA SANGUINEA)' => [
                'datos' => [['GLUCOSA', 'glucosa', 'mg/dL', 'AYUNAS: 70–100']],
            ],
            'GLUCOSA POSPRANDIAL' => [
                'datos' => [['GLUCOSA POSPRANDIAL', 'glucosa_posprandial', 'mg/dL', '2 HORAS: MENOR A 140']],
            ],
            'HB. GLICOSILADA' => [
                'datos' => [['HEMOGLOBINA GLICOSILADA', 'hba1c', '%', 'NORMAL: < 5.7 | PREDIABETES: 5.7–6.4 | DIABETES: >= 6.5']],
            ],
            'HEMOGLOBINA GLICOSILADA' => [
                'datos' => [['HEMOGLOBINA GLICOSILADA', 'hba1c', '%', 'NORMAL: < 5.7 | PREDIABETES: 5.7–6.4 | DIABETES: >= 6.5']],
            ],
            'EXAMEN GENERAL DE ORINA (UROANALISIS)' => [
                'datos' => [
                    ['COLOR', 'color', null, 'AMARILLO'],
                    ['ASPECTO', 'aspecto', null, 'CLARO'],
                    ['PH', 'ph', null, '5.0–8.0'],
                    ['DENSIDAD', 'densidad', null, '1.005–1.030'],
                    ['PROTEINAS', 'proteinas', null, 'NEGATIVO'],
                    ['GLUCOSA', 'glucosa', null, 'NEGATIVO'],
                    ['CETONAS', 'cetonas', null, 'NEGATIVO'],
                    ['NITRITOS', 'nitritos', null, 'NEGATIVO'],
                    ['LEUCOCITOS', 'leucocitos', 'POR CAMPO', '0–5'],
                    ['ERITROCITOS', 'eritrocitos', 'POR CAMPO', '0–2'],
                    ['CELULAS EPITELIALES', 'celulas_epiteliales', 'POR CAMPO', 'ESCASAS'],
                    ['BACTERIAS', 'bacterias', 'POR CAMPO', 'AUSENTES'],
                ],
            ],
            'COPROPARASITOLOGICO SERIADO (3 MUE)' => [
                'datos' => [
                    ['COLOR', 'color', null, 'CARACTERISTICO'],
                    ['CONSISTENCIA', 'consistencia', null, 'FORMADA'],
                    ['PARASITOS MUESTRA 1', 'parasitos_muestra_1', null, 'NO SE OBSERVAN'],
                    ['PARASITOS MUESTRA 2', 'parasitos_muestra_2', null, 'NO SE OBSERVAN'],
                    ['PARASITOS MUESTRA 3', 'parasitos_muestra_3', null, 'NO SE OBSERVAN'],
                ],
            ],
            'EXAMEN EN FRESCO' => [
                'datos' => [
                    ['CELULAS EPITELIALES', 'celulas_epiteliales', 'POR CAMPO', 'ESCASAS'],
                    ['LEUCOCITOS', 'leucocitos', 'POR CAMPO', 'ESCASOS'],
                    ['LEVADURAS', 'levaduras', null, 'NO SE OBSERVAN'],
                    ['TRICHOMONAS', 'trichomonas', null, 'NO SE OBSERVAN'],
                    ['OBSERVACIONES', 'observaciones', null, 'SIN OBSERVACIONES'],
                ],
            ],
            'GRAN DEPO (EX. FRESCO Y TINCION GRAM)' => [
                'datos' => [
                    ['CELULAS EPITELIALES', 'celulas_epiteliales', 'POR CAMPO', 'ESCASAS'],
                    ['LEUCOCITOS', 'leucocitos', 'POR CAMPO', 'ESCASOS'],
                    ['BACILOS GRAM POSITIVOS', 'bacilos_gram_positivos', null, 'NO SE OBSERVAN'],
                    ['COCOS GRAM POSITIVOS', 'cocos_gram_positivos', null, 'NO SE OBSERVAN'],
                    ['LEVADURAS', 'levaduras', null, 'NO SE OBSERVAN'],
                ],
            ],
            'GRASA FECAL Y MOCO FECAL EXAMEN EN FRESCO' => [
                'datos' => [
                    ['GRASA FECAL', 'grasa_fecal', null, 'NEGATIVO'],
                    ['MOCO FECAL', 'moco_fecal', null, 'AUSENTE'],
                    ['LEUCOCITOS', 'leucocitos', 'POR CAMPO', 'NO SE OBSERVAN'],
                    ['ERITROCITOS', 'eritrocitos', 'POR CAMPO', 'NO SE OBSERVAN'],
                ],
            ],
            'TINCION GRAM (ESPUTO, HECES, SECRECIÓN VAGINAL) - BACTERIOLOGIA' => [
                'datos' => [
                    ['MUESTRA', 'muestra', null, 'SEGUN SOLICITUD'],
                    ['LEUCOCITOS', 'leucocitos', 'POR CAMPO', 'ESCASOS'],
                    ['CELULAS EPITELIALES', 'celulas_epiteliales', 'POR CAMPO', 'ESCASAS'],
                    ['GRAM POSITIVOS', 'gram_positivos', null, 'NO SE OBSERVAN'],
                    ['GRAM NEGATIVOS', 'gram_negativos', null, 'NO SE OBSERVAN'],
                    ['OBSERVACIONES', 'observaciones', null, 'SIN OBSERVACIONES'],
                ],
            ],
        ];

        foreach ($catalogo as $productoNombre => $configuracion) {
            $productoId = DB::table('productos')
                ->join('tipo_productos', 'tipo_productos.id', '=', 'productos.tipo_producto_id')
                ->whereNull('productos.deleted_at')
                ->where('tipo_productos.nombre', 'LABORATORIOS')
                ->where('productos.nombre', $productoNombre)
                ->value('productos.id');

            if (! $productoId) {
                continue;
            }

            foreach ($configuracion['datos'] ?? [] as $index => [$nombre, $variable, $unidad, $rango]) {
                $exists = DB::table('producto_laboratorio_datos')
                    ->where('producto_id', $productoId)
                    ->where('nombre_variable', $variable)
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $exists) {
                    DB::table('producto_laboratorio_datos')->insert([
                        'producto_id' => $productoId,
                        'nombre' => $nombre,
                        'nombre_variable' => $variable,
                        'unidad' => $unidad,
                        'rango_referencia' => $rango,
                        'orden' => ($index + 1) * 10,
                        'visible' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            foreach ($configuracion['formulas'] ?? [] as $index => [$nombre, $variable, $formula, $unidad]) {
                $exists = DB::table('producto_laboratorio_formulas')
                    ->where('producto_id', $productoId)
                    ->where('nombre_variable', $variable)
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $exists) {
                    DB::table('producto_laboratorio_formulas')->insert([
                        'producto_id' => $productoId,
                        'nombre' => $nombre,
                        'nombre_variable' => $variable,
                        'formula' => $formula,
                        'unidad' => $unidad,
                        'orden' => ($index + 1) * 10,
                        'visible' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Los registros pueden haber sido ajustados por el laboratorio.
        // No se eliminan automáticamente para no borrar configuración clínica editada.
    }
};
