<?php

namespace App\Exports;

use App\Http\Controllers\CierreCajaController;
use App\Models\CierreCaja;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Todos los movimientos (ventas y gastos) que componen un cierre de caja. */
class CierreCajaVentasExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected CierreCaja $cierre) {}

    public function collection()
    {
        return CierreCajaController::ventasDelDia($this->cierre->user_id, $this->cierre->fecha->toDateString())
            ->with(['paciente:id,nombre_completo,ci', 'detalles:id,venta_id,nombre,cantidad,precio,total'])
            ->orderBy('fecha_hora')
            ->get()
            ->map(fn ($venta) => [
                $venta->id,
                optional($venta->fecha_hora_cobro ?: $venta->fecha_hora)->format('d/m/Y H:i'),
                $venta->paciente?->nombre_completo ?: ($venta->cliente ?: ($venta->esEgreso() ? 'GASTO DE CAJA' : 'SIN CLIENTE')),
                $venta->paciente?->ci ?: '',
                $venta->esEgreso() ? 'GASTO' : 'VENTA',
                $venta->estado,
                $venta->tipo_pago ?: '',
                $venta->detalles->count(),
                $venta->detalles->pluck('nombre')->implode(', '),
                // El gasto va en negativo: así la columna suma el neto que queda en caja.
                $venta->esEgreso() ? -(float) $venta->total : (float) $venta->total,
            ]);
    }

    public function headings(): array
    {
        return ['N°', 'Fecha y hora', 'Cliente / Paciente', 'CI', 'Tipo', 'Estado', 'Pago', 'Ítems', 'Detalle', 'Total (Bs)'];
    }

    public function title(): string
    {
        return 'Movimientos del cierre';
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $last; $row++) {
            $color = ($row % 2 === 0) ? 'E0F2F1' : 'FFFFFF';
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        return [];
    }
}
