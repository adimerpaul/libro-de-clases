<?php

namespace App\Exports;

use App\Models\Venta;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Historial de ventas y gastos tal como lo filtra la pantalla de Ventas. */
class VentasExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** Sin 'Ver Montos Caja' la hoja sale sin la columna de total. */
    public function __construct(protected Collection $ventas, protected bool $verMontos) {}

    public function collection()
    {
        $filas = $this->ventas->map(function (Venta $venta) {
            $fila = [
                $venta->id,
                optional($venta->fecha_hora)->format('d/m/Y H:i'),
                $venta->esEgreso() ? 'GASTO' : 'VENTA',
                self::cliente($venta),
                $venta->paciente?->ci ?: '',
                $venta->doctor?->nombre ?: '',
                $venta->seguro?->nombre ?: 'PARTICULAR',
                $venta->user?->name ?: '',
                self::estado($venta),
                $venta->tipo_pago ?: '',
                $venta->detalles->pluck('nombre')->implode(', '),
            ];
            if ($this->verMontos) {
                // El gasto va en negativo, como en la pantalla.
                $fila[] = $venta->esEgreso() ? -(float) $venta->total : (float) $venta->total;
            }

            return $fila;
        });

        if ($this->verMontos) {
            $filas->push(array_merge(array_fill(0, 10, ''), ['NETO EN CAJA (ventas cobradas menos gastos)', self::neto($this->ventas)]));
        }

        return $filas;
    }

    public function headings(): array
    {
        $cols = ['N°', 'Fecha y hora', 'Tipo', 'Cliente / Paciente', 'CI', 'Doctor', 'Seguro', 'Usuario', 'Estado', 'Pago', 'Detalle'];
        if ($this->verMontos) {
            $cols[] = 'Total (Bs)';
        }

        return $cols;
    }

    public function title(): string
    {
        return 'Ventas';
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();
        $col = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$col}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $last; $row++) {
            $color = ($row % 2 === 0) ? 'E3F2FD' : 'FFFFFF';
            $sheet->getStyle("A{$row}:{$col}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        if ($this->verMontos) {
            $sheet->getStyle("{$col}2:{$col}{$last}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("A{$last}:{$col}{$last}")->getFont()->setBold(true);
        }

        return [];
    }

    // ── Helpers compartidos con la vista PDF ─────────────────────

    /** Mismo rótulo que la tabla: el gasto muestra en qué se gastó. */
    public static function cliente(Venta $venta): string
    {
        if ($venta->esEgreso()) {
            return $venta->detalles->first()?->nombre ?: ($venta->comentario ?: 'GASTO DE CAJA');
        }

        return $venta->paciente?->nombre_completo ?: ($venta->cliente ?: '—');
    }

    /** Una venta pendiente que ya se cobró se muestra como COBRADO. */
    public static function estado(Venta $venta): string
    {
        return $venta->estado === 'PENDIENTE' && $venta->fecha_hora_cobro ? 'COBRADO' : (string) $venta->estado;
    }

    /** Lo que queda en caja: ventas cobradas menos gastos activos (sin anuladas ni pendientes). */
    public static function neto(Collection $ventas): float
    {
        return (float) $ventas->sum(function (Venta $venta) {
            if ($venta->esEgreso()) {
                return $venta->estado === 'ACTIVO' ? -(float) $venta->total : 0;
            }
            $cobrada = $venta->estado === 'ACTIVO' || ($venta->estado === 'PENDIENTE' && $venta->fecha_hora_cobro);

            return $cobrada ? (float) $venta->total : 0;
        });
    }
}
