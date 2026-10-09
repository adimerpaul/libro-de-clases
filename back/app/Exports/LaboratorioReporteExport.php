<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaboratorioReporteExport extends StringValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Builder $items) {}

    public function query()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return ['Fecha de solicitud', 'Solicitud', 'Paciente', 'CI', 'Laboratorio / prueba', 'Estado', 'Importe (Bs)'];
    }

    public function map($item): array
    {
        return [
            $item->solicitude->fecha_solicitud->format('d/m/Y'),
            $item->solicitude->codigo_solicitud,
            $item->solicitude->paciente?->nombre_completo,
            $item->solicitude->paciente?->ci,
            $item->producto_nombre,
            $item->solicitude->estado,
            (float) $item->precio,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '00695C']]]];
    }
}
