<?php

use App\Models\Producto;
use App\Models\ProductoLaboratorioDato;
use App\Models\ProductoLaboratorioDatoOpcion;
use App\Models\ProductoLaboratorioFormula;
use App\Models\TipoProducto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Carga el arancel oficial de laboratorio (S/GDLC-F-015, versión 05/2025).
 *
 * - Renombra las áreas existentes a las del arancel para no duplicar catálogos.
 * - Crea o actualiza los 254 exámenes (precio normal + precio seguro, tiempo de
 *   entrega, tipo de muestra y derivación en la descripción).
 * - Genera los datos de resultado de cada examen nuevo. Los exámenes que ya
 *   tenían datos configurados a mano no se tocan.
 * - Los laboratorios que no figuran en el arancel quedan con soft-delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $areas = $this->sincronizarAreas();
            $conservados = [];

            foreach ($this->arancel() as [$area, $nombreCompleto, $pSeguro, $pNormal, $tiempo, $muestra, $obs]) {
                [$nombre, $detalle] = $this->nombreYDetalle($nombreCompleto);
                $producto = $this->buscarProducto($nombre);
                $descripcion = $this->descripcion($tiempo, $muestra, $obs, $detalle);

                if ($producto) {
                    $producto->fill([
                        'nombre' => $nombre,
                        'tipo_producto_id' => $areas[$area],
                        'precio' => $pNormal,
                        'precio_seguro' => $pSeguro,
                        'descripcion' => $descripcion,
                    ])->save();
                } else {
                    $producto = Producto::create([
                        'nombre' => $nombre,
                        'tipo_producto_id' => $areas[$area],
                        'precio' => $pNormal,
                        'precio_seguro' => $pSeguro,
                        'descripcion' => $descripcion,
                    ]);
                }

                $conservados[] = $producto->id;

                if ($producto->laboratorioDatos()->count() === 0) {
                    $this->crearDatos($producto, $nombre, $area, $muestra);
                }
            }

            // Laboratorios que ya no figuran en el arancel oficial
            Producto::query()
                ->whereHas('tipoProducto', fn ($query) => $query->where('es_laboratorio', true))
                ->whereNotIn('id', $conservados)
                ->get()
                ->each->delete();

            // Áreas de laboratorio que quedaron sin exámenes
            TipoProducto::query()
                ->where('es_laboratorio', true)
                ->whereDoesntHave('productos')
                ->get()
                ->each->delete();
        });
    }

    public function down(): void
    {
        // Carga de catálogo: no se revierte.
    }

    // ── Áreas ─────────────────────────────────────────────────────

    private function sincronizarAreas(): array
    {
        // Se reutilizan las áreas ya existentes (mismo concepto, otro nombre)
        // para no perder el historial de los productos que las referencian.
        $renombrar = [
            'QUIMICA SANGUINEA' => 'BIOQUIMICA CLINICA',
            'UROANALISIS' => 'UROLOGIA',
            'PARASITOLOGIA' => 'COPROLOGIA',
            'MICROBIOLOGIA' => 'BACTERIOLOGIA',
            'COAGULACION' => 'COAGULOGRAMA',
            'SEROLOGIA' => 'SEROLOGIAS',
            'HORMONAS' => 'HORMONAS - INMUNOLOGIA',
            'MARCADORES TUMORALES' => 'MARCADORES TUMORALES - INMUNOLOGIA',
            'INMUNOLOGIA' => 'AUTOINMUNES - INMUNOLOGIA',
        ];

        foreach ($renombrar as $actual => $nuevo) {
            TipoProducto::where('nombre', $actual)->update(['nombre' => $nuevo]);
        }

        $colores = [
            'HEMATOLOGIA' => '#c62828',
            'COAGULOGRAMA' => '#ad1457',
            'BIOQUIMICA CLINICA' => '#1565c0',
            'IONOGRAMA / ELECTROLITOS' => '#0277bd',
            'CINETICA DE HIERRO' => '#6a1b9a',
            'PERFIL CARDIACO' => '#b71c1c',
            'CITOQUIMICOS' => '#00838f',
            'UROLOGIA' => '#f9a825',
            'COPROLOGIA' => '#6d4c41',
            'SECRECIONES' => '#ef6c00',
            'SEROLOGIAS' => '#2e7d32',
            'GASOMETRIAS' => '#37474f',
            'PERFIL TIROIDEO' => '#00695c',
            'FERTILIDAD - INMUNOLOGIA' => '#ad1457',
            'HORMONAS - INMUNOLOGIA' => '#7b1fa2',
            'AUTOINMUNES - INMUNOLOGIA' => '#4527a0',
            'MARCADORES TUMORALES - INMUNOLOGIA' => '#283593',
            'INFECCIOSOS (ELISA) - INMUNOLOGIA' => '#00897b',
            'PESQUIZA NEONATAL' => '#d81b60',
            'VITAMINAS' => '#f57f17',
            'VARIOS' => '#546e7a',
            'BACTERIOLOGIA' => '#2e7d32',
            'DROGAS DE ABUSO' => '#424242',
            'CITOLOGIA' => '#8e24aa',
            'BIOLOGIA MOLECULAR' => '#0097a7',
            'GENETICA' => '#5d4037',
        ];

        $areas = [];
        $orden = 10;
        foreach ($colores as $nombre => $color) {
            $tipo = TipoProducto::withTrashed()->firstOrNew(['nombre' => $nombre]);
            $tipo->fill([
                'color' => $color,
                'es_laboratorio' => true,
                'orden' => $orden,
                'deleted_at' => null,
            ])->save();
            $areas[$nombre] = $tipo->id;
            $orden += 10;
        }

        return $areas;
    }

    // ── Productos ─────────────────────────────────────────────────

    /**
     * Busca el examen ya cargado: primero por nombre exacto y luego por los
     * alias de los 20 laboratorios que existían antes del arancel.
     */
    private function buscarProducto(string $nombre): ?Producto
    {
        $alias = [
            'HEMOGRAMA (INDICES HEMATIMÉTRICOS + PLAQUETAS)' => 'HEMOGRAMA COMPLETO',
            'PLAQUETAS' => 'RECUENTO DE PLAQUETAS',
            'RETICULOCITOS + IPR' => 'RECUENTO DE RETICULOCITOS',
            'GRUPO SANGUÍNEO Y FACTOR RH' => 'GRUPO SANGUINEO Y FACTOR',
            'GASOMETRIA ARTERIAL CON NA, K, HTO, HEMOGLOBINA' => 'GASOMETRIA ARTERIAL (HEMATOLOGIA)',
            'HEMOGLOBINA GLICOSILADA HBA1C' => 'HEMOGLOBINA GLICOSILADA',
            'GLICEMIA EN AYUNAS' => 'GLUCOSA (QUIMICA SANGUINEA)',
            'GLICEMIA POST-PRANDIAL' => 'GLUCOSA POSPRANDIAL',
            'EXAMEN GENERAL DE ORINA - EGO' => 'EXAMEN GENERAL DE ORINA (UROANALISIS)',
            'COPROPARASITOLOGICO SERIADO' => 'COPROPARASITOLOGICO SERIADO (3 MUE)',
            'TIEMPO DE SANGRÍA + TIEMPO DE COAGULACION' => 'TIEMPO DE SANGRIA Y COAGULACION',
            'TIEMPO DE PROTROMBINA - TP - % ACT - INR' => 'TIEMPO DE PROTROMBINA + INR ACTIVIDAD (COAGULOGRAMA)',
            'TINCIÓN GRAM' => 'TINCION GRAM (ESPUTO, HECES, SECRECIÓN VAGINAL) - BACTERIOLOGIA',
            'EXAMEN EN FRESCO MAS PH, CELULAS CLAVE, AMINAS' => 'EXAMEN EN FRESCO',
            'MOCO FECAL' => 'GRASA FECAL Y MOCO FECAL EXAMEN EN FRESCO',
        ];

        return Producto::where('nombre', $nombre)->first()
            ?? (isset($alias[$nombre]) ? Producto::where('nombre', $alias[$nombre])->first() : null);
    }

    /**
     * Los paneles del arancel enumeran todos sus agentes en el nombre y no
     * entran en la columna. Se corta en los dos puntos y el detalle pasa a
     * la descripción.
     */
    private function nombreYDetalle(string $nombre): array
    {
        if (mb_strlen($nombre) <= 120 || ! str_contains($nombre, ':')) {
            return [mb_substr($nombre, 0, 255), ''];
        }

        [$corto, $detalle] = explode(':', $nombre, 2);

        return [trim($corto), trim($detalle, " .\t\n\r")];
    }

    private function descripcion(string $tiempo, string $muestra, string $obs, string $detalle = ''): string
    {
        $partes = [];
        if ($detalle !== '') {
            $partes[] = 'INCLUYE: '.$detalle;
        }
        if ($tiempo !== '') {
            $partes[] = 'ENTREGA: '.mb_strtoupper($tiempo);
        }
        if ($muestra !== '') {
            $partes[] = 'MUESTRA: '.mb_strtoupper($muestra);
        }
        if ($obs !== '') {
            $partes[] = 'DERIVA: '.mb_strtoupper($obs);
        }

        return implode(' · ', $partes);
    }

    // ── Datos de resultado ────────────────────────────────────────

    private function crearDatos(Producto $producto, string $nombre, string $area, string $muestra): void
    {
        $orden = 0;
        foreach ($this->definicionDatos($nombre, $area, $muestra) as $definicion) {
            $orden += 10;
            $dato = ProductoLaboratorioDato::create([
                'producto_id' => $producto->id,
                'nombre' => $definicion['nombre'],
                'nombre_variable' => $definicion['variable'],
                'unidad' => $definicion['unidad'] ?? null,
                'rango_referencia' => $definicion['rango'] ?? null,
                'valor_defecto' => $definicion['defecto'] ?? null,
                'orden' => $orden,
                'visible' => true,
            ]);

            foreach ($definicion['opciones'] ?? [] as $indice => $opcion) {
                ProductoLaboratorioDatoOpcion::create([
                    'producto_laboratorio_dato_id' => $dato->id,
                    'valor' => $opcion,
                    'orden' => ($indice + 1) * 10,
                ]);
            }

            if (! empty($definicion['formula'])) {
                ProductoLaboratorioFormula::create([
                    'producto_id' => $producto->id,
                    'producto_laboratorio_dato_id' => $dato->id,
                    'nombre' => $dato->nombre,
                    'nombre_variable' => $dato->nombre_variable,
                    'formula' => $definicion['formula'],
                    'unidad' => $dato->unidad,
                    'orden' => $orden,
                    'visible' => true,
                ]);
            }
        }
    }

    private function definicionDatos(string $nombre, string $area, string $muestra): array
    {
        if ($panel = $this->panelConocido($nombre, $area)) {
            return $panel;
        }

        // Pruebas cualitativas: el resultado es una lista cerrada de valores
        if ($opciones = $this->opcionesCualitativas($nombre)) {
            return [[
                'nombre' => 'RESULTADO',
                'variable' => 'resultado',
                'opciones' => $opciones,
                'defecto' => $opciones[1] ?? $opciones[0],
            ]];
        }

        return [[
            'nombre' => 'RESULTADO',
            'variable' => 'resultado',
            'unidad' => $this->unidadPara($nombre, $area),
        ]];
    }

    private function opcionesCualitativas(string $nombre): ?array
    {
        $reactivo = ['REACTIVO', 'NO REACTIVO'];
        $positivo = ['POSITIVO', 'NEGATIVO'];
        $desarrollo = ['SIN DESARROLLO BACTERIANO', 'CON DESARROLLO BACTERIANO'];

        $claves = [
            'PRUEBA RÁPIDA' => $positivo,
            'PRUEBA RAPIDA' => $positivo,
            'CULTIVO' => $desarrollo,
            'UROCULTIVO' => $desarrollo,
            'COPROCULTIVO' => $desarrollo,
            'ESPERMOCULTIVO' => $desarrollo,
            'HEMOCULTIVO' => $desarrollo,
            'BACILOSCOPIA' => ['NEGATIVO', 'PAUCIBACILAR', '+', '++', '+++'],
            'RPR/SIFILIS/VDRL' => $reactivo,
            'VIH' => $reactivo,
            'CHAGAS' => $reactivo,
            'TOXOPLASMOSIS HAI' => $reactivo,
            'COOMBS' => $positivo,
            'SANGRE OCULTA' => $positivo,
            'ROTAVIRUS' => $positivo,
            'ADENOVIRUS' => $positivo,
            'GIARDIA' => $positivo,
            'AMEBA HISTOLITICA' => $positivo,
            'TEST DE GRAHAM' => $positivo,
            'GOTA GRUESA' => $positivo,
            'CELULAS L.E.' => $positivo,
            'TEST DE CRISTALIZACIÓN' => $positivo,
            'TINTA CHINA' => $positivo,
            'FACTOR REUMATOIDE' => $positivo,
            'REACCIÓN DE WIDAL' => $positivo,
            'ANTICOAGULANTE LÚPICO' => $positivo,
            'IGG' => $positivo,
            'IGM' => $positivo,
            'ANTIGENO' => $positivo,
            'ANTÍGENO NASAL' => $positivo,
            'PANEL' => $positivo,
            'VIRUS DEL PAPILOMA' => $positivo,
            'TUBERCULOSIS' => $positivo,
            'TEST DE EMBARAZO' => $positivo,
            'INFLUENZA' => $positivo,
            'ADN (PATERNIDAD' => ['NO EXCLUIDO (COMPATIBLE)', 'EXCLUIDO (NO COMPATIBLE)'],
        ];

        foreach ($claves as $clave => $opciones) {
            if (str_contains($nombre, $clave)) {
                return $opciones;
            }
        }

        return null;
    }

    private function unidadPara(string $nombre, string $area): ?string
    {
        $unidades = [
            'MG/DL' => ['GLICEMIA', 'GLUCOSA', 'CREATININA', 'UREA', 'ACIDO URICO', 'ÁCIDO ÚRICO',
                'COLESTEROL', 'TRIGLICERIDOS', 'BILIRRUBINA', 'CALCIO', 'FOSFORO', 'MAGNESIO',
                'CREATINURIA', 'GLUCOSURIA'],
            'G/DL' => ['PROTEINAS TOTALES', 'ALBUMINA'],
            'U/L' => ['TRANSAMINASAS', 'FOSFATASA', 'GGT', 'AMILASA', 'LIPASA', 'DESHIDROGENASA',
                'CK-MB', 'CK-TOTAL'],
            'MEQ/L' => ['SODIO', 'CLORO', 'POTASIO'],
            'MG/L' => ['PROTEÍNA C REACTIVA', 'FIBRINOGENO', 'MICROALBUMINURIA'],
            'UG/DL' => ['HIERRO SERICO'],
            'NG/ML' => ['FERRITINA', 'PSA', 'TROPONINA', 'PROCALCITONINA', 'INSULINA', 'CORTISOL',
                'PROGESTERONA', 'TESTOSTERONA', 'TIROGLOBULINA', 'VITAMINA'],
            'PG/ML' => ['ESTRADIOL', 'ACTH', 'PARATOHORMONA', 'PRO BNP', 'NT-PROBNP', 'VITAMINA B 12'],
            'UUI/ML' => ['TSH', 'FSH', 'LH', 'PROLACTINA'],
            'MM/H' => ['VES-VSG'],
            'SEGUNDOS' => ['TIEMPO DE TROMBINA', 'TIEMPO TROMBOPLASTINA'],
            'ML/MIN' => ['DEPURACIÓN', 'CLEARENCE'],
            '%' => ['SATURACION', 'HEMOGLOBINA GLICOSILADA'],
            'U/ML' => ['CA-15.3', 'CA-19.9', 'CA-125', 'CA-127', 'CA 72.4', 'ASTO', 'ASO'],
        ];

        foreach ($unidades as $unidad => $claves) {
            foreach ($claves as $clave) {
                if (str_contains($nombre, $clave)) {
                    return $unidad;
                }
            }
        }

        return match ($area) {
            'CITOQUIMICOS', 'BACTERIOLOGIA', 'CITOLOGIA', 'BIOLOGIA MOLECULAR', 'GENETICA',
            'SECRECIONES', 'COPROLOGIA' => null,
            default => null,
        };
    }

    /**
     * Paneles con desglose conocido. El resto se crea con un único resultado.
     */
    private function panelConocido(string $nombre, string $area): ?array
    {
        $sn = ['SI', 'NO'];

        if (str_starts_with($nombre, 'HEMOGRAMA')) {
            return [
                ['nombre' => 'GLÓBULOS BLANCOS', 'variable' => 'globulos_blancos', 'unidad' => '/mm3', 'rango' => '4.000 - 10.000'],
                ['nombre' => 'GLÓBULOS ROJOS', 'variable' => 'globulos_rojos', 'unidad' => 'mill/mm3', 'rango' => 'HOMBRES: 4,5 - 5,9 | MUJERES: 4,1 - 5,1'],
                ['nombre' => 'HEMOGLOBINA', 'variable' => 'hemoglobina', 'unidad' => 'g/dL', 'rango' => 'HOMBRES: 13 - 17 | MUJERES: 12 - 15'],
                ['nombre' => 'HEMATOCRITO', 'variable' => 'hematocrito', 'unidad' => '%', 'rango' => 'HOMBRES: 40 - 52 | MUJERES: 36 - 46'],
                ['nombre' => 'VCM', 'variable' => 'vcm', 'unidad' => 'fL', 'rango' => '80 - 100', 'formula' => '(hematocrito * 10) / globulos_rojos'],
                ['nombre' => 'HCM', 'variable' => 'hcm', 'unidad' => 'pg', 'rango' => '27 - 33', 'formula' => '(hemoglobina * 10) / globulos_rojos'],
                ['nombre' => 'CHCM', 'variable' => 'chcm', 'unidad' => 'g/dL', 'rango' => '32 - 36', 'formula' => '(hemoglobina * 100) / hematocrito'],
                ['nombre' => 'PLAQUETAS', 'variable' => 'plaquetas', 'unidad' => '/mm3', 'rango' => '150.000 - 450.000'],
                ['nombre' => 'NEUTRÓFILOS', 'variable' => 'neutrofilos', 'unidad' => '%', 'rango' => '50 - 70'],
                ['nombre' => 'LINFOCITOS', 'variable' => 'linfocitos', 'unidad' => '%', 'rango' => '20 - 40'],
                ['nombre' => 'MONOCITOS', 'variable' => 'monocitos', 'unidad' => '%', 'rango' => '2 - 10'],
                ['nombre' => 'EOSINÓFILOS', 'variable' => 'eosinofilos', 'unidad' => '%', 'rango' => '1 - 6'],
                ['nombre' => 'BASÓFILOS', 'variable' => 'basofilos', 'unidad' => '%', 'rango' => '0 - 2'],
            ];
        }

        if ($nombre === 'HEMOGLOBINA + HEMATOCRITO') {
            return [
                ['nombre' => 'HEMOGLOBINA', 'variable' => 'hemoglobina', 'unidad' => 'g/dL', 'rango' => 'HOMBRES: 13 - 17 | MUJERES: 12 - 15'],
                ['nombre' => 'HEMATOCRITO', 'variable' => 'hematocrito', 'unidad' => '%', 'rango' => 'HOMBRES: 40 - 52 | MUJERES: 36 - 46'],
            ];
        }

        if ($nombre === 'PLAQUETAS') {
            return [['nombre' => 'RECUENTO DE PLAQUETAS', 'variable' => 'plaquetas', 'unidad' => '/mm3', 'rango' => '150.000 - 450.000']];
        }

        if (str_starts_with($nombre, 'RETICULOCITOS')) {
            return [
                ['nombre' => 'RETICULOCITOS', 'variable' => 'reticulocitos', 'unidad' => '%', 'rango' => '0,5 - 2,5'],
                ['nombre' => 'HEMATOCRITO', 'variable' => 'hematocrito', 'unidad' => '%'],
                ['nombre' => 'IPR', 'variable' => 'ipr', 'rango' => 'MENOR A 2: HIPOPROLIFERATIVA'],
            ];
        }

        if (str_starts_with($nombre, 'GRUPO SANG')) {
            return [
                ['nombre' => 'GRUPO SANGUÍNEO', 'variable' => 'grupo_sanguineo', 'opciones' => ['O', 'A', 'B', 'AB']],
                ['nombre' => 'FACTOR RH', 'variable' => 'factor_rh', 'opciones' => ['POSITIVO', 'NEGATIVO']],
            ];
        }

        if (str_starts_with($nombre, 'TIEMPO DE SANGRÍA')) {
            return [
                ['nombre' => 'TIEMPO DE SANGRÍA', 'variable' => 'tiempo_sangria', 'unidad' => 'min', 'rango' => '1 - 3 MINUTOS'],
                ['nombre' => 'TIEMPO DE COAGULACIÓN', 'variable' => 'tiempo_coagulacion', 'unidad' => 'min', 'rango' => '5 - 10 MINUTOS'],
            ];
        }

        if (str_starts_with($nombre, 'TIEMPO DE PROTROMBINA')) {
            return [
                ['nombre' => 'TIEMPO DE PROTROMBINA', 'variable' => 'tp_paciente', 'unidad' => 'seg', 'rango' => '11 - 14 SEGUNDOS'],
                ['nombre' => 'TP CONTROL', 'variable' => 'tp_control', 'unidad' => 'seg', 'defecto' => '12'],
                ['nombre' => 'ACTIVIDAD PROTROMBÍNICA', 'variable' => 'actividad', 'unidad' => '%', 'rango' => '70 - 100'],
                ['nombre' => 'INR', 'variable' => 'inr', 'rango' => '0,8 - 1,2', 'formula' => 'tp_paciente / tp_control'],
            ];
        }

        if (str_contains($nombre, 'HDL-LDL')) {
            return [
                ['nombre' => 'COLESTEROL TOTAL', 'variable' => 'colesterol_total', 'unidad' => 'mg/dL', 'rango' => 'MENOR A 200'],
                ['nombre' => 'TRIGLICÉRIDOS', 'variable' => 'trigliceridos', 'unidad' => 'mg/dL', 'rango' => 'MENOR A 150'],
                ['nombre' => 'HDL COLESTEROL', 'variable' => 'hdl', 'unidad' => 'mg/dL', 'rango' => 'HOMBRES: MAYOR A 40 | MUJERES: MAYOR A 50'],
                ['nombre' => 'VLDL COLESTEROL', 'variable' => 'vldl', 'unidad' => 'mg/dL', 'rango' => '5 - 40', 'formula' => 'trigliceridos / 5'],
                ['nombre' => 'LDL COLESTEROL', 'variable' => 'ldl', 'unidad' => 'mg/dL', 'rango' => 'MENOR A 130', 'formula' => 'colesterol_total - hdl - (trigliceridos / 5)'],
            ];
        }

        if (str_contains($nombre, 'ALBUMINA-GLOBULINA')) {
            return [
                ['nombre' => 'PROTEÍNAS TOTALES', 'variable' => 'proteinas_totales', 'unidad' => 'g/dL', 'rango' => '6,4 - 8,3'],
                ['nombre' => 'ALBÚMINA', 'variable' => 'albumina', 'unidad' => 'g/dL', 'rango' => '3,5 - 5,2'],
                ['nombre' => 'GLOBULINA', 'variable' => 'globulina', 'unidad' => 'g/dL', 'rango' => '2,0 - 3,5', 'formula' => 'proteinas_totales - albumina'],
                ['nombre' => 'RELACIÓN A/G', 'variable' => 'relacion_ag', 'rango' => '1,1 - 2,2', 'formula' => 'albumina / (proteinas_totales - albumina)'],
            ];
        }

        if (str_contains($nombre, 'BILIRRUBINA TOTAL')) {
            return [
                ['nombre' => 'BILIRRUBINA TOTAL', 'variable' => 'bilirrubina_total', 'unidad' => 'mg/dL', 'rango' => '0,2 - 1,2'],
                ['nombre' => 'BILIRRUBINA DIRECTA', 'variable' => 'bilirrubina_directa', 'unidad' => 'mg/dL', 'rango' => '0,0 - 0,3'],
                ['nombre' => 'BILIRRUBINA INDIRECTA', 'variable' => 'bilirrubina_indirecta', 'unidad' => 'mg/dL', 'rango' => '0,1 - 0,9', 'formula' => 'bilirrubina_total - bilirrubina_directa'],
            ];
        }

        if (str_starts_with($nombre, 'EXAMEN GENERAL DE ORINA')) {
            return [
                ['nombre' => 'COLOR', 'variable' => 'color', 'opciones' => ['AMARILLO CLARO', 'AMARILLO', 'AMBAR', 'ROJIZO', 'INCOLORO'], 'defecto' => 'AMARILLO'],
                ['nombre' => 'ASPECTO', 'variable' => 'aspecto', 'opciones' => ['TRANSPARENTE', 'LIGERAMENTE TURBIO', 'TURBIO'], 'defecto' => 'TRANSPARENTE'],
                ['nombre' => 'DENSIDAD', 'variable' => 'densidad', 'rango' => '1.005 - 1.030'],
                ['nombre' => 'PH', 'variable' => 'ph', 'rango' => '5,0 - 7,0'],
                ['nombre' => 'PROTEÍNAS', 'variable' => 'proteinas', 'opciones' => ['NEGATIVO', 'INDICIOS', '+', '++', '+++'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'GLUCOSA', 'variable' => 'glucosa', 'opciones' => ['NEGATIVO', '+', '++', '+++'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'CUERPOS CETÓNICOS', 'variable' => 'cuerpos_cetonicos', 'opciones' => ['NEGATIVO', '+', '++', '+++'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'SANGRE', 'variable' => 'sangre', 'opciones' => ['NEGATIVO', '+', '++', '+++'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'NITRITOS', 'variable' => 'nitritos', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'UROBILINÓGENO', 'variable' => 'urobilinogeno', 'opciones' => ['NORMAL', 'AUMENTADO'], 'defecto' => 'NORMAL'],
                ['nombre' => 'LEUCOCITOS (SEDIMENTO)', 'variable' => 'leucocitos', 'unidad' => '/campo', 'rango' => '0 - 5'],
                ['nombre' => 'HEMATÍES (SEDIMENTO)', 'variable' => 'hematies', 'unidad' => '/campo', 'rango' => '0 - 2'],
                ['nombre' => 'CÉLULAS EPITELIALES', 'variable' => 'celulas_epiteliales', 'opciones' => ['ESCASAS', 'REGULARES', 'ABUNDANTES'], 'defecto' => 'ESCASAS'],
                ['nombre' => 'BACTERIAS', 'variable' => 'bacterias', 'opciones' => ['NO SE OBSERVAN', 'ESCASAS', 'REGULARES', 'ABUNDANTES'], 'defecto' => 'NO SE OBSERVAN'],
                ['nombre' => 'CRISTALES', 'variable' => 'cristales', 'opciones' => ['NO SE OBSERVAN', 'OXALATO DE CALCIO', 'URATOS AMORFOS', 'FOSFATOS AMORFOS'], 'defecto' => 'NO SE OBSERVAN'],
                ['nombre' => 'CILINDROS', 'variable' => 'cilindros', 'opciones' => ['NO SE OBSERVAN', 'HIALINOS', 'GRANULOSOS'], 'defecto' => 'NO SE OBSERVAN'],
            ];
        }

        if (str_starts_with($nombre, 'COPROPARASITOLOGICO')) {
            return [
                ['nombre' => 'COLOR', 'variable' => 'color', 'opciones' => ['CAFÉ', 'CAFÉ CLARO', 'CAFÉ OSCURO', 'VERDOSO', 'AMARILLENTO'], 'defecto' => 'CAFÉ'],
                ['nombre' => 'CONSISTENCIA', 'variable' => 'consistencia', 'opciones' => ['FORMADA', 'BLANDA', 'PASTOSA', 'LÍQUIDA'], 'defecto' => 'FORMADA'],
                ['nombre' => 'MOCO', 'variable' => 'moco', 'opciones' => $sn, 'defecto' => 'NO'],
                ['nombre' => 'RESTOS ALIMENTICIOS', 'variable' => 'restos_alimenticios', 'opciones' => ['NO SE OBSERVAN', 'ESCASOS', 'REGULARES', 'ABUNDANTES'], 'defecto' => 'NO SE OBSERVAN'],
                ['nombre' => 'LEUCOCITOS', 'variable' => 'leucocitos', 'unidad' => '/campo', 'rango' => '0 - 3'],
                ['nombre' => 'HEMATÍES', 'variable' => 'hematies', 'unidad' => '/campo', 'rango' => '0 - 1'],
                ['nombre' => 'PARÁSITOS OBSERVADOS', 'variable' => 'parasitos', 'defecto' => 'NO SE OBSERVAN PARÁSITOS'],
            ];
        }

        if (str_contains($nombre, 'CITOQUÍMICO')) {
            return [
                ['nombre' => 'ASPECTO', 'variable' => 'aspecto', 'opciones' => ['CRISTALINO', 'LIGERAMENTE TURBIO', 'TURBIO', 'HEMÁTICO'], 'defecto' => 'CRISTALINO'],
                ['nombre' => 'COLOR', 'variable' => 'color', 'defecto' => 'INCOLORO'],
                ['nombre' => 'RECUENTO CELULAR', 'variable' => 'recuento_celular', 'unidad' => '/mm3'],
                ['nombre' => 'POLIMORFONUCLEARES', 'variable' => 'polimorfonucleares', 'unidad' => '%'],
                ['nombre' => 'MONONUCLEARES', 'variable' => 'mononucleares', 'unidad' => '%'],
                ['nombre' => 'PROTEÍNAS', 'variable' => 'proteinas', 'unidad' => 'mg/dL'],
                ['nombre' => 'GLUCOSA', 'variable' => 'glucosa', 'unidad' => 'mg/dL'],
                ['nombre' => 'LDH', 'variable' => 'ldh', 'unidad' => 'U/L'],
            ];
        }

        if (str_contains($nombre, 'CULTIVO') || str_starts_with($nombre, 'UROCULTIVO')
            || str_starts_with($nombre, 'COPROCULTIVO') || str_starts_with($nombre, 'HEMOCULTIVO')
            || str_starts_with($nombre, 'ESPERMOCULTIVO')) {
            return [
                ['nombre' => 'RESULTADO', 'variable' => 'resultado', 'opciones' => ['SIN DESARROLLO BACTERIANO', 'CON DESARROLLO BACTERIANO'], 'defecto' => 'SIN DESARROLLO BACTERIANO'],
                ['nombre' => 'RECUENTO', 'variable' => 'recuento', 'unidad' => 'UFC/mL'],
                ['nombre' => 'GERMEN AISLADO', 'variable' => 'germen_aislado'],
                ['nombre' => 'ANTIBIOGRAMA', 'variable' => 'antibiograma'],
            ];
        }

        if (str_starts_with($nombre, 'TINCIÓN GRAM') || str_starts_with($nombre, 'TINCION GRAM')) {
            return [
                ['nombre' => 'LEUCOCITOS', 'variable' => 'leucocitos', 'unidad' => '/campo'],
                ['nombre' => 'CÉLULAS EPITELIALES', 'variable' => 'celulas_epiteliales', 'opciones' => ['ESCASAS', 'REGULARES', 'ABUNDANTES']],
                ['nombre' => 'COCOS GRAM POSITIVOS', 'variable' => 'cocos_gram_positivos', 'opciones' => ['NO SE OBSERVAN', 'ESCASOS', 'REGULARES', 'ABUNDANTES'], 'defecto' => 'NO SE OBSERVAN'],
                ['nombre' => 'BACILOS GRAM NEGATIVOS', 'variable' => 'bacilos_gram_negativos', 'opciones' => ['NO SE OBSERVAN', 'ESCASOS', 'REGULARES', 'ABUNDANTES'], 'defecto' => 'NO SE OBSERVAN'],
                ['nombre' => 'LEVADURAS', 'variable' => 'levaduras', 'opciones' => ['NO SE OBSERVAN', 'ESCASAS', 'REGULARES', 'ABUNDANTES'], 'defecto' => 'NO SE OBSERVAN'],
            ];
        }

        if (str_starts_with($nombre, 'EXAMEN EN FRESCO')) {
            return [
                ['nombre' => 'PH', 'variable' => 'ph', 'rango' => '3,8 - 4,5'],
                ['nombre' => 'CÉLULAS CLAVE', 'variable' => 'celulas_clave', 'opciones' => ['NO SE OBSERVAN', 'PRESENTES'], 'defecto' => 'NO SE OBSERVAN'],
                ['nombre' => 'TEST DE AMINAS (KOH)', 'variable' => 'test_aminas', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'TRICHOMONA VAGINALIS', 'variable' => 'trichomona', 'opciones' => ['NO SE OBSERVA', 'SE OBSERVA'], 'defecto' => 'NO SE OBSERVA'],
                ['nombre' => 'LEVADURAS', 'variable' => 'levaduras', 'opciones' => ['NO SE OBSERVAN', 'ESCASAS', 'REGULARES', 'ABUNDANTES'], 'defecto' => 'NO SE OBSERVAN'],
            ];
        }

        if ($area === 'GASOMETRIAS') {
            return [
                ['nombre' => 'PH', 'variable' => 'ph', 'rango' => '7,35 - 7,45'],
                ['nombre' => 'PCO2', 'variable' => 'pco2', 'unidad' => 'mmHg', 'rango' => '35 - 45'],
                ['nombre' => 'PO2', 'variable' => 'po2', 'unidad' => 'mmHg', 'rango' => '80 - 100'],
                ['nombre' => 'HCO3', 'variable' => 'hco3', 'unidad' => 'mmol/L', 'rango' => '22 - 26'],
                ['nombre' => 'EXCESO DE BASE', 'variable' => 'exceso_base', 'unidad' => 'mmol/L', 'rango' => '-2 A +2'],
                ['nombre' => 'SATURACIÓN O2', 'variable' => 'saturacion_o2', 'unidad' => '%', 'rango' => '95 - 100'],
                ['nombre' => 'SODIO', 'variable' => 'sodio', 'unidad' => 'mEq/L', 'rango' => '135 - 145'],
                ['nombre' => 'POTASIO', 'variable' => 'potasio', 'unidad' => 'mEq/L', 'rango' => '3,5 - 5,1'],
                ['nombre' => 'HEMATOCRITO', 'variable' => 'hematocrito', 'unidad' => '%'],
                ['nombre' => 'HEMOGLOBINA', 'variable' => 'hemoglobina', 'unidad' => 'g/dL'],
            ];
        }

        if ($area === 'PERFIL TIROIDEO' || $area === 'FERTILIDAD - INMUNOLOGIA'
            || $area === 'HORMONAS - INMUNOLOGIA') {
            return null; // resultado único con unidad inferida
        }

        if ($nombre === 'PAP - PAPANICOLAU') {
            return [
                ['nombre' => 'CALIDAD DE LA MUESTRA', 'variable' => 'calidad_muestra', 'opciones' => ['SATISFACTORIA', 'INSATISFACTORIA'], 'defecto' => 'SATISFACTORIA'],
                ['nombre' => 'DIAGNÓSTICO CITOLÓGICO', 'variable' => 'diagnostico_citologico'],
                ['nombre' => 'MICROORGANISMOS', 'variable' => 'microorganismos'],
                ['nombre' => 'RECOMENDACIÓN', 'variable' => 'recomendacion'],
            ];
        }

        if ($area === 'BIOLOGIA MOLECULAR') {
            return [
                ['nombre' => 'RESULTADO', 'variable' => 'resultado', 'opciones' => ['NO DETECTADO', 'DETECTADO'], 'defecto' => 'NO DETECTADO'],
                ['nombre' => 'GENOTIPOS / AGENTES DETECTADOS', 'variable' => 'agentes_detectados'],
                ['nombre' => 'MÉTODO', 'variable' => 'metodo', 'defecto' => 'PCR EN TIEMPO REAL'],
            ];
        }

        if (str_starts_with($nombre, 'PANEL DE DROGAS')) {
            return [
                ['nombre' => 'ANFETAMINAS', 'variable' => 'anfetaminas', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'COCAÍNA', 'variable' => 'cocaina', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'MARIHUANA', 'variable' => 'marihuana', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'BENZODIACEPINAS', 'variable' => 'benzodiacepinas', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'ANTIDEPRESIVOS TRICÍCLICOS', 'variable' => 'triciclicos', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'BARBITÚRICOS', 'variable' => 'barbituricos', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'ÉXTASIS', 'variable' => 'extasis', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'METADONA', 'variable' => 'metadona', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'MORFINA', 'variable' => 'morfina', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'FENCICLIDINA', 'variable' => 'fenciclidina', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
                ['nombre' => 'TRAMADOL', 'variable' => 'tramadol', 'opciones' => ['NEGATIVO', 'POSITIVO'], 'defecto' => 'NEGATIVO'],
            ];
        }

        if (str_contains($nombre, 'IGG-IGM') || str_contains($nombre, 'IGG/IGM')
            || str_contains($nombre, 'IGG IGM') || str_contains($nombre, 'IGG-IGA')
            || str_contains($nombre, 'IGG/IGA')) {
            $segunda = str_contains($nombre, 'IGA') ? 'IGA' : 'IGM';

            return [
                ['nombre' => 'IGG', 'variable' => 'igg', 'opciones' => ['NO REACTIVO', 'REACTIVO'], 'defecto' => 'NO REACTIVO'],
                ['nombre' => $segunda, 'variable' => mb_strtolower($segunda), 'opciones' => ['NO REACTIVO', 'REACTIVO'], 'defecto' => 'NO REACTIVO'],
            ];
        }

        if (str_starts_with($nombre, 'TIBC')) {
            return [
                ['nombre' => 'TIBC', 'variable' => 'tibc', 'unidad' => 'ug/dL', 'rango' => '250 - 450'],
                ['nombre' => 'TRANSFERRINA', 'variable' => 'transferrina', 'unidad' => 'mg/dL', 'rango' => '200 - 360'],
                ['nombre' => 'HIERRO SÉRICO', 'variable' => 'hierro_serico', 'unidad' => 'ug/dL', 'rango' => '60 - 170'],
                ['nombre' => '% DE SATURACIÓN', 'variable' => 'saturacion', 'unidad' => '%', 'rango' => '20 - 50', 'formula' => '(hierro_serico * 100) / tibc'],
            ];
        }

        return null;
    }

    // ── Arancel oficial ───────────────────────────────────────────

    private function arancel(): array
    {
        return [
            ['HEMATOLOGIA', 'HEMOGRAMA (INDICES HEMATIMÉTRICOS + PLAQUETAS)', 30, 35, 'En el Día', 'Sangre c/EDTA', ''],
            ['HEMATOLOGIA', 'HEMOGLOBINA + HEMATOCRITO', 10, 15, 'En el Día', 'Sangre c/EDTA', ''],
            ['HEMATOLOGIA', 'PLAQUETAS', 15, 20, 'En el Día', 'Sangre c/EDTA', ''],
            ['HEMATOLOGIA', 'RETICULOCITOS + IPR', 15, 20, 'En el Día', 'Sangre c/EDTA', ''],
            ['HEMATOLOGIA', 'GRUPO SANGUÍNEO Y FACTOR RH', 20, 25, 'En el Día', 'Sangre c/EDTA', ''],
            ['HEMATOLOGIA', 'COOMBS DIRECTO', 30, 35, 'En el Día', 'Sangre c/EDTA', 'CEDIMIK'],
            ['HEMATOLOGIA', 'COOMBS INDIRECTO', 35, 40, 'En el Día', 'Suero', 'CEDIMIK'],
            ['HEMATOLOGIA', 'VES-VSG, VELOCIDAD DE ERITROSEDIMENTACION GLOBULAR.', 10, 15, 'En el Día', 'Sangre c/Citrato', ''],
            ['HEMATOLOGIA', 'CELULAS L.E.', 125, 150, 'En el Día', 'Sangre c/EDTA', 'CEDIMIK'],
            ['HEMATOLOGIA', 'GOTA GRUESA', 15, 20, 'En el Día', 'Sangre c/EDTA', ''],
            ['HEMATOLOGIA', 'FROTIS DE SANGRE PERIFERICA (UNA SOLA MUESTRA)', 20, 25, 'En el Día', 'Sangre c/EDTA', ''],
            ['COAGULOGRAMA', 'TIEMPO DE SANGRÍA + TIEMPO DE COAGULACION', 10, 15, 'En el Día', 'Sangre Entera', ''],
            ['COAGULOGRAMA', 'TIEMPO DE PROTROMBINA - TP - % ACT - INR', 35, 40, 'En el Día', 'Plasma c/Citrato', ''],
            ['COAGULOGRAMA', 'TIEMPO TROMBOPLASTINA PARCIAL - APTT', 55, 60, 'En el Día', '', ''],
            ['COAGULOGRAMA', 'TIEMPO DE TROMBINA - TT', 40, 45, 'En el Día', '', ''],
            ['COAGULOGRAMA', 'FIBRINOGENO', 85, 90, 'En el Día', '', ''],
            ['COAGULOGRAMA', 'DIMERO D', 120, 140, 'En el Día', '', ''],
            ['COAGULOGRAMA', 'ANTICOAGULANTE LÚPICO', 150, 170, '3 días', '', 'CEDIMIK'],
            ['COAGULOGRAMA', 'FACTOR V', 340, 400, 'Der/5 dias', 'Plasma Citratado', 'CEDIMIK'],
            ['BIOQUIMICA CLINICA', 'GLICEMIA EN AYUNAS', 25, 30, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'GLICEMIA POST-PRANDIAL', 25, 30, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'GLUCOSA POSTPRANDIAL (0-1 HORAS)', 50, 60, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'GLUCOSA CON CARGA DE GLUCOSA (3 TOMAS DE MUESTRA)', 120, 140, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'HEMOGLOBINA GLICOSILADA HBA1C', 75, 85, 'En el Día', 'Sangre c/EDTA', ''],
            ['BIOQUIMICA CLINICA', 'CREATININA', 25, 30, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'UREA /NUS-BUN', 25, 30, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'ACIDO URICO', 25, 30, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'AMILASA', 55, 70, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'LIPASA', 100, 110, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'LACTATO DESHIDROGENASA', 45, 50, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'COLESTEROL TOTAL', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'TRIGLICERIDOS', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'HDL-LDL- VLDLCOLESTEROL', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'ACIDOS BILIARES', 300, 350, 'En el Día', 'Bilis', 'CEDIMIK'],
            ['BIOQUIMICA CLINICA', 'TRANSAMINASAS (ALT-GPT)', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'TRANSAMINASAS (AST-GOT)', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'FOSFATASA ALCALINA', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'BILIRRUBINA TOTAL Y FRACCIONES', 35, 40, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'GGT GAMA GLUTAMIL TRANSFERASA', 40, 45, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'PROTEINAS TOTALES', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'ALBUMINA-GLOBULINA- REL A/G', 30, 35, 'En el Día', 'Suero', ''],
            ['BIOQUIMICA CLINICA', 'ELECTROFORESIS DE PROTEINAS', 340, 380, '24 horas', 'Suero', 'CEDIMIK'],
            ['IONOGRAMA / ELECTROLITOS', 'SODIO', 43, 50, 'En el Día', 'Suero/Orina', ''],
            ['IONOGRAMA / ELECTROLITOS', 'CLORO', 43, 50, 'En el Día', 'Suero/Orina', ''],
            ['IONOGRAMA / ELECTROLITOS', 'POTASIO', 43, 50, 'En el Día', 'Suero/Orina', ''],
            ['IONOGRAMA / ELECTROLITOS', 'CALCIO IONICO', 43, 50, 'En el Día', 'Suero', ''],
            ['IONOGRAMA / ELECTROLITOS', 'CALCIO TOTAL', 43, 50, 'En el Día', 'Suero/Orina', ''],
            ['IONOGRAMA / ELECTROLITOS', 'MAGNESIO', 43, 50, 'En el Día', 'Suero/Orina', ''],
            ['IONOGRAMA / ELECTROLITOS', 'FOSFORO', 43, 50, 'En el Día', 'Suero/Orina', ''],
            ['CINETICA DE HIERRO', 'HIERRO SERICO', 60, 65, 'En el Día', 'Suero', ''],
            ['CINETICA DE HIERRO', 'TIBC+TRANSFERRINA+% DE SATURACION', 100, 150, 'En el Día', 'Suero', ''],
            ['CINETICA DE HIERRO', 'FERRITINA', 120, 130, 'En el Dia', 'Suero', ''],
            ['PERFIL CARDIACO', 'TROPONINA CUANTIFICADA', 110, 130, 'En el Día', 'Suero', ''],
            ['PERFIL CARDIACO', 'CK-MB', 90, 100, 'En el Día', 'Suero', ''],
            ['PERFIL CARDIACO', 'CK-TOTAL', 60, 70, 'En el Día', 'Suero', ''],
            ['PERFIL CARDIACO', 'MIOGLOBINA/CK_MB/CTNL', 210, 230, 'En el Día', 'Suero', ''],
            ['PERFIL CARDIACO', 'PRO BNP', 140, 150, '24 horas', 'Sangre con EDTA', 'CEDIMIK'],
            ['PERFIL CARDIACO', 'NT-PROBNP (NT-PROPÉPTIDO NATRIURÉTICO CEREBRAL)', 130, 150, '24 horas', '', 'CEDIMIK'],
            ['PERFIL CARDIACO', 'NT-PROBNP (NT-PROPÉPTIDO NATRIURÉTICO AURICULAR)', 130, 150, '24 horas', '', 'CEDIMIK'],
            ['PERFIL CARDIACO', 'NT-PROBNP (NT-PROPÉPTIDO NATRIURÉTICO CENTRAL)', 130, 150, '24 horas', '', 'CEDIMIK'],
            ['CITOQUIMICOS', 'CITOQUÍMICO DE LÍQUIDO SINOVIAL', 190, 200, 'En el Día', 'Líquido', ''],
            ['CITOQUIMICOS', 'CITOQUÍMICO DE LÍQUIDO ASCÍTICO O PERITONEAL', 190, 200, 'En el Día', '', ''],
            ['CITOQUIMICOS', 'CITOQUÍMICO DE LÍQUIDO PLEURAL)', 190, 200, 'En el Día', '', ''],
            ['CITOQUIMICOS', 'CITOQUÍMICO DE LÍQUIDO PERICÁRDICO', 190, 200, 'En el Día', '', ''],
            ['CITOQUIMICOS', 'CITOQUÍMICO DE LCR', 190, 200, 'En el Día', '', ''],
            ['CITOQUIMICOS', 'TINTA CHINA', 100, 120, 'En el Día', 'LCR', ''],
            ['UROLOGIA', 'EXAMEN GENERAL DE ORINA - EGO', 20, 25, 'En el Día', 'Orina Ocasional', ''],
            ['UROLOGIA', 'PROTEINURIA 24 HORAS', 35, 40, 'En el Día', 'Orina ocasional u orina 24 hrs', ''],
            ['UROLOGIA', 'DEPURACIÓN O CLEARENCE DE CREATININA', 50, 60, 'En el Día', '', ''],
            ['UROLOGIA', 'OSMOLARIDAD URINARIA', 140, 160, 'En el Día', '', 'CEDIMIK'],
            ['UROLOGIA', 'UREA/NUS-BUN URINARIA', 25, 30, 'En el Día', '', ''],
            ['UROLOGIA', 'ÁCIDO ÚRICO URINARIO', 25, 30, 'En el Día', '', ''],
            ['UROLOGIA', 'CREATINURIA (CREATININA EN ORINA)', 25, 30, 'En el Día', '', ''],
            ['UROLOGIA', 'MICROALBUMINURIA', 75, 80, 'En el Día', '', ''],
            ['UROLOGIA', 'RELACIÓN CREATINURIA / ALBUMINURIA', 80, 85, 'En el Día', '', ''],
            ['UROLOGIA', 'GLUCOSURIA', 25, 30, 'En el Día', '', ''],
            ['UROLOGIA', 'AMILASA EN ORINA', 55, 70, 'En el Día', '', ''],
            ['UROLOGIA', 'FENA', 95, 110, 'En el Día', 'Sangre y Orina', ''],
            ['UROLOGIA', 'EFU', 95, 110, 'En el Dia', '', ''],
            ['UROLOGIA', 'FAUA', 95, 110, 'En el Dia', '', ''],
            ['COPROLOGIA', 'COPROPARASITOLOGICO SIMPLE - HECES RUTINA', 15, 20, 'En el Día', 'Heces', ''],
            ['COPROLOGIA', 'COPROPARASITOLOGICO SERIADO', 55, 60, 'En el Día', '', ''],
            ['COPROLOGIA', 'MOCO FECAL', 15, 20, 'En el Día', '', ''],
            ['COPROLOGIA', 'HELICOBACTER PYLORI EN HECES (PRUEBA RÁPIDA)', 80, 95, 'En el Día', '', ''],
            ['COPROLOGIA', 'SANGRE OCULTA (PRUEBA RAPIDA)', 45, 50, 'En el Día', '', ''],
            ['COPROLOGIA', 'SANGRE OCULTA (PRUEBA RAPIDA/SERIADO)', 135, 150, 'Tres dias', '', ''],
            ['COPROLOGIA', 'AZUCARES REDUCTORES (REACCION DE BENEDICT)', 35, 40, 'En el Día', '', ''],
            ['COPROLOGIA', 'PH EN HECES', 15, 20, 'En el Día', '', ''],
            ['COPROLOGIA', 'TEST DE GRAHAM', 15, 20, 'En el Día', '', ''],
            ['COPROLOGIA', 'TINCION GRAM EN HECES SIMPLE', 20, 25, 'En el Día', '', ''],
            ['COPROLOGIA', 'TINCIÓN GRAM EN HECES (SERIADO)', 60, 75, 'En el Día', '', ''],
            ['COPROLOGIA', 'TEST DE GRAHAM SERIADO', 45, 60, 'En el Día', '', ''],
            ['COPROLOGIA', 'ROTAVIRUS', 80, 90, 'En el Día', '', ''],
            ['COPROLOGIA', 'ADENOVIRUS', 80, 90, 'En el Día', '', ''],
            ['COPROLOGIA', 'HELICOBACTER PYLORI EN HECES (ELISA)', 100, 150, 'En el Día', '', ''],
            ['COPROLOGIA', 'GIARDIA (ELISA)', 120, 150, 'En el Día', '', ''],
            ['COPROLOGIA', 'AMEBA HISTOLITICA (ELISA)', 120, 150, 'En el Día', '', ''],
            ['COPROLOGIA', 'CALPROTECTINA FECAL', 125, 135, 'En el Día', '', ''],
            ['SECRECIONES', 'TEST DE CRISTALIZACIÓN - TEST DE HELECHOS', 15, 20, 'En el Día', 'Secreción vaginal o uretral', ''],
            ['SECRECIONES', 'EXAMEN EN FRESCO MAS PH, CELULAS CLAVE, AMINAS', 40, 45, 'En el Día', '', ''],
            ['SECRECIONES', 'TINCIÓN GRAM', 20, 25, 'En el Día', '', ''],
            ['SEROLOGIAS', 'PROTEÍNA C REACTIVA - PCR - CRP(CUANTIFICADO)', 65, 70, 'En el Día', 'Suero', ''],
            ['SEROLOGIAS', 'FACTOR REUMATOIDE (LATEX) - FR', 35, 40, 'En el Día', '', ''],
            ['SEROLOGIAS', 'ASTO - ASO - ANTIESTREPTOLISINA O', 35, 40, 'En el Día', '', ''],
            ['SEROLOGIAS', 'RPR/SIFILIS/VDRL (FLOCULACIÓN)', 35, 40, 'En el Día', '', ''],
            ['SEROLOGIAS', 'REACCIÓN DE WIDAL (ANTIGENOS A, B,O,H)', 35, 40, 'En el Día', '', ''],
            ['SEROLOGIAS', 'H. PYLORI EN SANGRE (PRUEBA RÁPIDA - CROMATOGRAFÍA)', 55, 60, 'En el Día', '', ''],
            ['SEROLOGIAS', 'HEPATITIS A IGG + IGM (PRUEBA RÁPIDA)', 100, 120, 'En el Día', '', ''],
            ['SEROLOGIAS', 'HEPATITIS B (PRUEBA RÁPIDA)', 90, 100, 'En el Día', '', ''],
            ['SEROLOGIAS', 'HEPATITIS C (PRUEBA RÁPIDA)', 90, 100, 'En el Día', '', ''],
            ['SEROLOGIAS', 'VIH 1/2 - HIV PRUEBA RAPIDA', 60, 70, 'En el Día', '', ''],
            ['SEROLOGIAS', 'TEST DE EMBARAZO EN SANGRE (PRUEBA RAPIDA)', 30, 35, 'En el Día', '', ''],
            ['SEROLOGIAS', 'TORCH IGG-IGM (PRUEBA RÁPIDA)', 250, 380, 'En el Día', '', 'CEDIMIK'],
            ['SEROLOGIAS', 'CHAGAS HAI', 90, 100, 'En el Día', '', ''],
            ['SEROLOGIAS', 'TOXOPLASMOSIS HAI', 90, 100, 'En el Día', '', ''],
            ['SEROLOGIAS', 'DENGUE AC-AG (PRUEBA RÁPIDA)', 75, 80, 'En el Día', '', ''],
            ['SEROLOGIAS', 'PROCALCITONINA CUANTIFICADO', 125, 150, 'En el Día', '', ''],
            ['GASOMETRIAS', 'GASOMETRIA ARTERIAL CON NA, K, HTO, HEMOGLOBINA', 350, 400, 'En el Día', 'Sangre arterial', ''],
            ['GASOMETRIAS', 'GASOMETRIA ARTERIAL CON LACTATO', 400, 450, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'T3 TOTAL', 100, 120, 'En el Día', 'Suero', ''],
            ['PERFIL TIROIDEO', 'T4 TOTAL', 100, 120, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'TSH ULTRASENSIBLE', 100, 120, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'TSH - TIROTROPINA', 100, 120, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'T4 LIBRE', 100, 120, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'T3 LIBRE', 100, 120, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'ANTI-TIROGLOBULINA / ANTI TG', 140, 160, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'ANTI- PEROXIDASA / ANTI TPO', 140, 160, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'TIROGLOBULINA', 140, 160, 'En el Día', '', ''],
            ['PERFIL TIROIDEO', 'TSH-NEONATAL', 130, 155, '1 semana', '', ''],
            ['PERFIL TIROIDEO', 'ANTI RECEPTOR TSH', 250, 270, '4 días Derivación', '', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'ANTIMULLERIANA (AHM)', 150, 200, 'En el Día', 'Suero', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'FSH - HORMONA FOLÍCULO ESTIMULANTE', 100, 120, 'En el Día', '', ''],
            ['FERTILIDAD - INMUNOLOGIA', 'LH - HORMONA LUTEINIZANTE', 100, 120, 'En el Día', '', ''],
            ['FERTILIDAD - INMUNOLOGIA', 'PROLACTINA', 100, 120, 'En el Día', '', ''],
            ['FERTILIDAD - INMUNOLOGIA', 'ESTRADIOL', 100, 120, 'En el Día', '', ''],
            ['FERTILIDAD - INMUNOLOGIA', 'PROGESTERONA', 100, 120, 'En el Día', '', ''],
            ['FERTILIDAD - INMUNOLOGIA', 'TESTOSTERONA TOTAL', 100, 120, 'En el Día', '', ''],
            ['FERTILIDAD - INMUNOLOGIA', 'TESTOSTERONA LIBRE', 100, 120, 'En el Día', '', ''],
            ['FERTILIDAD - INMUNOLOGIA', '17-HIDROXIPROGESTERONA', 200, 250, '2 dias', '', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'DHEA-S / DEHIDROEPIANDROSTERONA SULFATO', 110, 160, '2 días', '', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'DHEA / DEHIDROEPIANDROSTERONA', 110, 160, '2 días', '', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'ESTRIOL LIBRE', 220, 250, '2 días', '', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'ESTRIOL', 200, 230, '2 días', '', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'SHBG- GLOBULINA FIJADORA DE HORMONAS SEXUALES', 200, 250, '1 Día', '', 'CEDIMIK'],
            ['FERTILIDAD - INMUNOLOGIA', 'ANDROSTENEDIONA', 200, 250, '3 días', '', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'CORTISOL SOLO AM', 100, 120, 'En el Día', 'Suero', ''],
            ['HORMONAS - INMUNOLOGIA', 'CORTISOL (AM Y PM)', 200, 240, '1 dia', '', ''],
            ['HORMONAS - INMUNOLOGIA', 'IGF-1', 200, 250, '1 Día', '', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'INSULINA', 120, 150, 'En el Día', '', ''],
            ['HORMONAS - INMUNOLOGIA', 'PARATOHORMONA', 150, 170, 'En el Día', '', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'GH- HORMONA DEL CRECIMIENTO', 140, 160, 'En el Día', '', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'PÉPTIDO C', 150, 180, 'En el Día', '', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'ERITROPOYETINA', 180, 200, '2 días', '', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'ALDOSTERONA', 270, 300, '4 días', 'Suero u orina', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'ADH - HORMONA ANTIDIURÉTICA', 330, 360, '6 días', '', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'CALCITONINA', 250, 270, '3 días', 'Suero', 'CEDIMIK'],
            ['HORMONAS - INMUNOLOGIA', 'ACTH - ADRENOCORTICOTROPINA', 150, 180, '1 dia', 'Plasma c/EDTA', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTI - CCP', 200, 220, 'En el Día', 'Suero', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANA (ANTICUERPOS ANTINUCLEARES)', 150, 200, 'En el Día', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTI-DNA DS', 150, 180, 'En el Día', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTI-DNA SS', 150, 180, 'En el Día', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'PERFIL ENA (SS-A, SS-B, SCL-70, JO-1, RNP, SM)', 650, 700, 'En el Día', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'C-ANCA / PR3', 150, 180, 'En el Día', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'P-ANCA / MPO', 150, 180, 'En el Día', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'COMPLEMENTO C4', 90, 110, '2 días', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'COMPLEMENTO C3', 90, 110, '2 dias', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'COMPLEMENTO TOTAL CH50', 250, 300, '2 dias', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'INMUNOGLOBULINA IGA', 130, 170, '3 dias', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'INMUNOGLOBULINA IGG', 130, 170, '3 dias', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'INMUNOGLOBULINA IGM', 130, 170, '3 dias', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'INMUNOGLOBULINA IGE', 130, 170, 'En el Día', '', ''],
            ['AUTOINMUNES - INMUNOLOGIA', 'INTERLEUCINAS', 120, 150, '2 dias', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'PANEL ALÉRGICO PEDIÁTRICO', 550, 700, '2 días', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'PANEL ALÉRGICO ALIMENTICIO', 550, 700, '2 días', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'PANEL ALÉRGICO RESPIRATORIO', 550, 700, '2 días', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'PANEL ALERGENOS PEDIATRICOS', 550, 700, '2 dias', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTICUERPOS ANTIPLAQUETARIOS', 320, 350, '4 días', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ACS ANTIFOSFOLIPIDICOS IGG-IGM', 180, 200, '2 días', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTICARDIOLIPINA IGG-IGM', 180, 200, '2 dias', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTI - B2-GLICOPROTEÍNA I IGG/IGM', 400, 450, '1 Día', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTI GLIADINA IGG/IGA', 450, 500, '1 Día', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTI TRANSGLUTAMINASA IGG/IGA', 450, 500, '1 Día', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTI ENDOMISIO IGG/IGA', 450, 500, '1 Día', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTICUERPOS ANTIMITOCONDRIALES - AMA', 200, 230, '1 dia', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTICUERPOS ANTIMUSCULO LISO - MSA - ASMA', 200, 250, '1 Día', '', 'CEDIMIK'],
            ['AUTOINMUNES - INMUNOLOGIA', 'ANTICUERPOS TIPO 1 MICROSOMALES DE HÍGADO Y RIÑÓN - LKM-1', 250, 280, '1 Día', '', 'CEDIMIK'],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'ALFA FETO PROTEÍNA AFP', 100, 120, '1 Día', 'Suero', ''],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'ANTÍGENO CARCINO EMBRIONARIO CEA', 100, 120, '1 Día', '', ''],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'CA-15.3', 110, 130, '1 Día', '', 'CEDIMIK'],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'CA-19.9', 110, 130, '1 Día', '', 'CEDIMIK'],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'CA-125', 110, 130, '1 Día', '', 'CEDIMIK'],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'CA-127', 110, 130, '1 Día', '', 'CEDIMIK'],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'CA 72.4', 110, 130, '1 Día', '', 'CEDIMIK'],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'B-HCG CUANTITATIVO', 120, 140, '1 Día', '', ''],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'B-2 MICROGLOBULINA', 180, 200, '1 Día', '', ''],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'PSA LIBRE', 120, 140, 'En el Día', '', ''],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'PSA TOTAL', 120, 140, 'En el Día', '', ''],
            ['MARCADORES TUMORALES - INMUNOLOGIA', 'LEPTINA', 400, 420, '6 Días', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'TOXOPLASMOSIS IGG IGM', 200, 230, 'En el Día', 'Suero', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'RUBEOLA IGG-IGM', 200, 230, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'CITOMEGALOVIRUS IGG-IGM', 200, 230, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'CLAMIDIA IGG-IGM', 200, 230, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'HERPES ZOSTER IGG-IGM', 250, 300, '3 días', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'HERPES VIRUS SIMPLEX 1 IGG-IGM', 200, 250, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'HERPES VIRUS SIMPLEX 2 IGG-IGM', 200, 250, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'VIRUS DE EPSTEIN BARR IGG-IGM', 250, 300, '1 Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'H. PYLORI IGG-IGM EN SANGRE', 200, 220, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'HIDATIDOSIS IGG', 150, 200, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'CISTICERCOSIS EIA', 200, 250, '3 días', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'CHAGAS', 100, 120, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'HEPATITIS A IGG/IGM', 140, 170, '3 Días', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'HEPATITIS B', 110, 160, 'En el Día', '', 'CEDIMIK'],
            ['INFECCIOSOS (ELISA) - INMUNOLOGIA', 'HEPATITIS C IGG / IGM', 110, 160, 'En el Día', '', 'CEDIMIK'],
            ['PESQUIZA NEONATAL', 'TIROTROFINA NEONATAL - TSH NEONATAL (HIPOTIROIDISMO CONGENITO)', 120, 140, '1 día', 'Sangre Entera', ''],
            ['VITAMINAS', 'VITAMINA B 9 - ÁCIDO FÓLICO', 190, 220, 'En el Día', 'Suero', ''],
            ['VITAMINAS', 'VITAMINA B 12 - COBALAMINA', 190, 220, 'En el Día', '', ''],
            ['VITAMINAS', 'VITAMINA B 2 - RIBOFLAVINA', 400, 420, '3 días', '', ''],
            ['VITAMINAS', 'VITAMINA B 6 - PIRIDOXINA', 400, 420, '3 días', '', ''],
            ['VITAMINAS', 'VITAMINA D3 - COLECALCIFEROL', 190, 220, 'En el Día', '', ''],
            ['VARIOS', 'COVID-19 IGG/IGM (ELISA) CUANTIFICADO', 80, 100, 'En el Día', 'H. Nasofaringeo', ''],
            ['VARIOS', 'INTERLEUQUINA 6 -- IL-6', 260, 300, 'En el Día', '', 'CEDIMIK'],
            ['VARIOS', 'COVID-19 ANTÍGENO NASAL', 100, 120, 'En el Día', '', ''],
            ['VARIOS', 'INFLUENZA PRUEBA RAPIDA', 130, 150, 'En el Día', '', ''],
            ['BACTERIOLOGIA', 'UROCULTIVO', 130, 150, '3 días', 'Orina', 'CPS'],
            ['BACTERIOLOGIA', 'COPROCULTIVO', 130, 150, '3 días', 'Heces', 'CPS'],
            ['BACTERIOLOGIA', 'ESPERMOCULTIVO', 130, 150, '3 días', 'Semen', 'CPS'],
            ['BACTERIOLOGIA', 'CULTIVO SECRECION VAGINAL', 130, 150, '3 días', 'diferentes muestras biologicas', 'CPS'],
            ['BACTERIOLOGIA', 'CULTIVO SECRECION URETRAL', 130, 150, '3 días', '', 'CPS'],
            ['BACTERIOLOGIA', 'CULTIVO DE ESPUTO', 130, 150, '3 días', '', 'CPS'],
            ['BACTERIOLOGIA', 'CULTIVO HISOPADO FARINGEO', 130, 150, '3 días', '', 'CPS'],
            ['BACTERIOLOGIA', 'CULTIVO DE ABCESOS / SECRECIONES……………', 130, 150, '3 días', '', 'CPS'],
            ['BACTERIOLOGIA', 'CULTIVO DE PUNTA DE CATETER', 130, 150, '3 días', '', 'CPS'],
            ['BACTERIOLOGIA', 'HEMOCULTIVO', 150, 160, '7 días', 'Sangre Entera', 'CPS'],
            ['BACTERIOLOGIA', 'HEMOCULTIVO SERIADO', 150, 480, '10 dias', 'Sangre Entera', 'CPS'],
            ['BACTERIOLOGIA', 'BACILOSCOPIA SIMPLE EN ESPUTO - BK ESPUTO', 25, 35, 'En el Día', 'Esputo', 'CPS'],
            ['BACTERIOLOGIA', 'BACILOSCOPIA SERIADA EN ESPUTO 3 MUESTRAS)', 75, 105, '3 dias', '', 'CPS'],
            ['BACTERIOLOGIA', 'CULTIVO DE SUPERFICIES,AMBIENTES U OTROS', 130, 150, '3 dias', 'Variedad', 'CPS'],
            ['DROGAS DE ABUSO', 'PANEL DE DROGAS (11 PARAMETROS): ANFETAMINAS, COCAINA, MARIHUANA, BENZODIACEPINAS, ANTIDEPRESIVOS TRICÍCLICOS, BARBITÚRICOS, ÉXTASIS, METADONA, MORFINA, FENOCICLINA, TRAMADOL', 350, 400, 'En el Día', 'Orina', ''],
            ['CITOLOGIA', 'PAP - PAPANICOLAU', 80, 100, '5 días', 'Sec. Endo-exocervical', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'VIRUS DEL PAPILOMA HUMANO VHP: 20 GENOTIPOS', 750, 800, '1 dia', 'Sec. Endocervical', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'VIRUS DEL PAPILOMA HUMANO VHP: 21 GENOTIPOS', 700, 750, '1 dia', 'Sec. Endocervical', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PANEL DE TRANSMISION SEXUAL T12: NEISSERIA GONORREAE,CHLAMIDIA TRACHOMATIS, MICOPLASMA GENITALIUM, TRICHOMONA VAGINALES, UREAPLASMA UREALYTICUM, UREAPLASMA PARVUM, MICOPLASMA HOMINIS, HERPES VIRUS 1, HERPERS VIIRUS 2, TREPONEMA PALLIDUM, HAEMOPHILUS DUCREY.', 750, 800, '1 dia', 'Sec. Vaginal, Uretral y orina', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PROSTATITIS BACTERIANA G10: ENTEROBACTER SPP, KLEBSIELLA SPP, ENTEROCOCCUS FEACALIS, ENTEROCOCCUS FAECIUM, ESCHERICHIA COLI,POTEUS SPP, PSEUDOMONA AURIGINOSA, SERRATIA SPP, STAPHYLOCOCCUS AEREUS, STREPTOCOCCUS SPP.', 850, 900, '2 dias', 'Sec. Uretral', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PANEL RESPIRATORIO G3: SARVS-COV 2, INFLUENZA A, INFLUENZA B', 550, 600, '2 dias', 'Hisopado Combinado', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PANEL RESPIRATORIO G16: COVID-19 (SARVS-COV2) , INFLUENZA A, INFLUENZA B, PARAINFLUENZA, CORONAVIRUS HUMANO, BOCAVIRUS,RHINOVIRUS, VIRUS SINCITIAL RESPIRATORIO, ADENOVIRUS, METAPNEUMOVIRUS.', 950, 1000, '2 dias', '', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'TUBERCULOSIS : MICOBACTERIUM TUBERCULOSIS', 550, 600, '2 dias', 'Suero, orina, liquido pleural, ascitico , LCR y esputo', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PANEL DE DETECCION DE MENINGITIS BACTERIANA G3: STREPTOCOCCUS PNEUMONIAE, HAEMOPHILUS AGALACTIAE, HAEMOPHILUS INFLUENZAE TIPO B, LISTERIA MONOCYTOGENES, NEISSERIA MENINGITIDIS, ESCHERICHIA COLI.', 550, 600, '2 dias', 'LCR y sangre c/EDTA', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PANEL DE DETECCION DE MININGITIS BACTERIANA D6: STREPTOCOCCUS PNEUMONIAE; STREPTOCOCCUS GALACTIAE, HAEMOPHILUS INFLUENZAE TIPO B, LISTERIA MONOCYTOGENES, NEISSERIA MENINGITIDIS, ESCHERICHIA COLI.', 900, 1000, '2 dias', '', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PANEL DE DETECCION DE MENINGITIS VIRAL G4: CITOMEGALOVIRUS (CMV) EPSTEIN - BARR (EBV), HERPES VIRUS HUMANO 3 (HHV6), PARVOVIRUS B19', 750, 800, '2 dias', '', 'CEDIMIK'],
            ['BIOLOGIA MOLECULAR', 'PANEL GASTRICO G9: SHIGELLA SPP, SALMONELLA SPP, E. COLI, CAMPYLOBACTER SPP; ADENOVIRUS, ROTAVIRUS, ASTROVIRUS, NOROVIRUS 1 Y 2.', 900, 950, '2 dias', '', 'CEDIMIK'],
            ['GENETICA', 'ADN (PATERNIDAD 2 MUESTRAS PADRE E HIJO)', 2200, 2500, '4-7 DIAS', 'HISOPADO BUCAL/SANGRE', 'GENETOX'],
            ['GENETICA', 'ADN (PATERNIDAD 3 MUESTRAS PADRE, MADRE E HIJO)', 2700, 3000, '4-7 DIAS', 'HISOPADO BUCAL/SANGRE', 'GENETOX'],
            ['GENETICA', 'ADN (PATERNIDAD 2 MUESTRAS PADRE E HIJO) - CABELLO/UÑAS', 2500, 2800, '4-7 DIAS', 'CABELLO/UÑAS', 'GENETOX'],
            ['GENETICA', 'ADN (PATERNIDAD 2 MUESTRAS PADRE E HIJO) EXPRESS', 3200, 3500, '24 HORAS', 'HISOPADO BUCAL/SANGRE/CABELLO/UÑAS', 'GENETOX'],
        ];
    }
};
