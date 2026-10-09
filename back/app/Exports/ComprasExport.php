<?php

namespace App\Exports;

use App\Models\Compra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ComprasExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Compra::with(['proveedor:id,nombre', 'user:id,name'])
            ->orderByDesc('fecha_hora');

        if (!empty($this->filters['fecha_inicio'])) {
            $query->whereDate('fecha_hora', '>=', $this->filters['fecha_inicio']);
        }
        if (!empty($this->filters['fecha_fin'])) {
            $query->whereDate('fecha_hora', '<=', $this->filters['fecha_fin']);
        }
        if (!empty($this->filters['proveedor_id'])) {
            $query->where('proveedor_id', $this->filters['proveedor_id']);
        }
        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }
        if (!empty($this->filters['estado'])) {
            $query->where('estado', $this->filters['estado']);
        }

        return $query->get()->map(fn($c) => [
            $c->id,
            $c->fecha_hora?->format('d/m/Y H:i'),
            $c->proveedor?->nombre ?? '',
            $c->user?->name ?? '',
            $c->estado,
            $c->tipo_pago,
            $c->nro_factura ?? '',
            $c->total,
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Fecha', 'Proveedor', 'Usuario', 'Estado', 'Pago', 'Nro. Factura', 'Total'];
    }

    public function title(): string
    {
        return 'Compras';
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $last; $row++) {
            $color = ($row % 2 === 0) ? 'E3F2FD' : 'FFFFFF';
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        return [];
    }
}
