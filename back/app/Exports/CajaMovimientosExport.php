<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reporte de ingresos y gastos de una caja en un rango (normalmente una semana).
 * Los movimientos anulados se listan igual, pero no entran en los totales.
 */
class CajaMovimientosExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param array $datos Salida de CajaMovimientoController::datosReporte() */
    public function __construct(protected array $datos) {}

    public function collection()
    {
        $filas = collect();

        foreach (['INGRESO' => 'ingresos', 'GASTO' => 'gastos'] as $tipo => $clave) {
            foreach ($this->datos[$clave] as $movimiento) {
                $filas->push([
                    $movimiento->id,
                    $movimiento->fecha_hora?->format('d/m/Y H:i'),
                    $tipo,
                    $movimiento->estado,
                    $movimiento->categoria ?: '',
                    $movimiento->concepto,
                    $movimiento->beneficiario ?: '',
                    $movimiento->documento ?: '',
                    $movimiento->user?->name ?: '',
                    // El anulado se muestra en 0 para que la columna sume el total real.
                    $movimiento->estado === 'ANULADO' ? 0 : (float) $movimiento->importe,
                ]);
            }
        }

        return $filas;
    }

    public function headings(): array
    {
        return [
            'N°', 'Fecha y hora', 'Tipo', 'Estado', 'Categoría', 'Concepto',
            'Origen / Beneficiario', 'Documento', 'Registrado por', 'Importe (Bs)',
        ];
    }

    public function title(): string
    {
        return 'Ingresos y gastos';
    }

    public function styles(Worksheet $sheet): array
    {
        $ultima = $sheet->getHighestRow();

        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Cada fila se pinta según su tipo: verde el ingreso, rojo el gasto, gris el anulado.
        for ($fila = 2; $fila <= $ultima; $fila++) {
            $tipo = $sheet->getCell("C{$fila}")->getValue();
            $estado = $sheet->getCell("D{$fila}")->getValue();
            $color = $estado === 'ANULADO' ? 'ECEFF1' : ($tipo === 'INGRESO' ? 'E8F5E9' : 'FFEBEE');

            $sheet->getStyle("A{$fila}:J{$fila}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
            $sheet->getStyle("J{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $this->escribirResumen($sheet, $ultima + 2);

        return [];
    }

    /** Bloque de totales al pie: ingresos, gastos y saldo del periodo. */
    private function escribirResumen(Worksheet $sheet, int $fila): void
    {
        $resumen = $this->datos['resumen'];
        $encabezado = $this->datos['titulo_caja'].' · '
            .$this->datos['desde'].' a '.$this->datos['hasta'];

        $sheet->setCellValue("A{$fila}", $encabezado);
        $sheet->mergeCells("A{$fila}:J{$fila}");
        $sheet->getStyle("A{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '37474F']],
        ]);

        $lineas = [
            ['Total ingresos ('.$resumen['cantidad_ingresos'].')', $resumen['total_ingresos'], 'E8F5E9'],
            ['Total gastos ('.$resumen['cantidad_gastos'].')', $resumen['total_gastos'], 'FFEBEE'],
            ['Saldo del periodo', $resumen['saldo'], 'E0F2F1'],
        ];

        foreach ($lineas as [$etiqueta, $valor, $color]) {
            $fila++;
            $sheet->setCellValue("H{$fila}", $etiqueta);
            $sheet->setCellValue("J{$fila}", $valor);
            $sheet->getStyle("H{$fila}:J{$fila}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'AAAAAA']]],
            ]);
            $sheet->getStyle("J{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }
}
