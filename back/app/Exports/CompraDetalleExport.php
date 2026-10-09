<?php

namespace App\Exports;

use App\Models\Compra;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CompraDetalleExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(private readonly Compra $compra)
    {
    }

    public function array(): array
    {
        $rows = [
            ['DETALLE DE COMPRA #'.$this->compra->id, '', '', '', '', ''],
            ['Fecha', $this->compra->fecha_hora?->format('d/m/Y H:i'), 'Proveedor', $this->compra->proveedor?->nombre ?? 'SIN PROVEEDOR', 'Estado', $this->compra->estado],
            ['Factura', $this->compra->nro_factura ?? 'SIN FACTURA', 'Tipo de pago', $this->compra->tipo_pago, 'Usuario', $this->compra->user?->name ?? ''],
            ['', '', '', '', '', ''],
            ['Código', 'Producto', 'Lote', 'Cantidad', 'Precio unitario (Bs.)', 'Subtotal (Bs.)'],
        ];

        foreach ($this->compra->detalles as $index => $detalle) {
            $excelRow = $index + 6;
            $rows[] = [
                $detalle->producto?->codigo ?? '',
                $detalle->nombre,
                $detalle->lote ?? '',
                (float) $detalle->cantidad,
                (float) $detalle->precio,
                "=D{$excelRow}*E{$excelRow}",
            ];
        }

        $lastDetailRow = $this->compra->detalles->count() + 5;
        $totalFormula = $this->compra->detalles->isEmpty()
            ? '=0'
            : "=SUM(F6:F{$lastDetailRow})";
        $rows[] = ['', '', '', '', 'TOTAL GENERAL (Bs.)', $totalFormula];

        return $rows;
    }

    public function title(): string
    {
        return 'Compra '.$this->compra->id;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDetailRow = $this->compra->detalles->count() + 5;
                $totalRow = $lastDetailRow + 1;

                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 15],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);

                foreach (['A2', 'C2', 'E2', 'A3', 'C3', 'E3'] as $cell) {
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                }

                $sheet->getStyle('A5:F5')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00897B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
                ]);

                if ($lastDetailRow >= 6) {
                    $sheet->getStyle("A6:F{$lastDetailRow}")->applyFromArray([
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'D0D7DE']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("D6:F{$lastDetailRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle("E{$totalRow}:F{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '0D47A1']]],
                ]);
                $sheet->getStyle("F{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->freezePane('A6');
                if ($lastDetailRow >= 6) {
                    $sheet->setAutoFilter("A5:F{$lastDetailRow}");
                }
            },
        ];
    }
}
