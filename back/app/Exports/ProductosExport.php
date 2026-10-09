<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Producto::with(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura', 'tipoProducto:id,nombre'])
            ->orderBy('nombre');

        $tipoProductoId = $this->filters['tipo_producto_id'] ?? '';
        if ($tipoProductoId) {
            $query->where('tipo_producto_id', $tipoProductoId);
        }

        $q = $this->filters['q'] ?? '';
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                    ->orWhere('nombre_comercial', 'like', "%$q%")
                    ->orWhere('codigo', 'like', "%$q%")
                    ->orWhere('marca', 'like', "%$q%");
            });
        }

        return $query->get()->map(fn ($p) => [
            $p->codigo ?? '',
            $p->nombre ?? '',
            $p->nombre_comercial ?? '',
            $p->marca ?? '',
            $p->descripcion ?? '',
            $p->fabricante?->nombre ?? '',
            $p->unidad ? ($p->unidad->abreviatura ?: $p->unidad->nombre) : '',
            $p->tipoProducto?->nombre ?? '',
            $p->precio ?? 0,
        ]);
    }

    public function headings(): array
    {
        return ['Código', 'Nombre genérico', 'Nombre comercial', 'Marca', 'Descripción', 'Fabricante', 'Unidad', 'Categoría', 'Precio'];
    }

    public function title(): string
    {
        return 'Productos';
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $last; $row++) {
            $color = ($row % 2 === 0) ? 'E0F2F1' : 'FFFFFF';
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        return [];
    }
}
