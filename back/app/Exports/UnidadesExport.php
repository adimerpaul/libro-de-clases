<?php

namespace App\Exports;

use App\Models\Unidad;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UnidadesExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Unidad::orderBy('nombre');
        return $query->get()->map(fn($u) => [
            $u->nombre       ?? '',
            $u->abreviatura  ?? '',
        ]);
    }

    public function headings(): array
    {
        return ['Nombre', 'Abreviatura'];
    }

    public function title(): string
    {
        return 'Unidades';
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A148C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $last; $row++) {
            $color = ($row % 2 === 0) ? 'F3E5F5' : 'FFFFFF';
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        return [];
    }
}
