@php use App\Exports\VentasExport; @endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $soloFarmacia ? 'Ventas de farmacia' : 'Ventas' }}</title>
    <style>
        @page { margin: 18px 20px; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8px; line-height: 1.35; }
        .header { border-bottom: 2px solid #1565C0; padding-bottom: 5px; margin-bottom: 8px; overflow: hidden; }
        .brand { color: #1565C0; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        .rango { color: #475569; font-size: 8px; margin-top: 2px; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; }
        table.items th { background: #1565C0; color: #fff; font-size: 7px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; text-align: left; }
        table.items td { padding: 3px; border-bottom: 1px solid #dbe4ee; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #F1F6FC; }
        .num { text-align: right; }
        .det { color: #64748b; font-size: 6.5px; }
        .gasto { color: #D84315; font-weight: bold; }
        .anulado { color: #C62828; }
        .empty { border: 1px dashed #cbd5e1; color: #64748b; padding: 22px; text-align: center; margin-top: 18px; }
        tfoot td { font-weight: bold; border-top: 2px solid #1565C0; padding: 4px 3px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}<br>
            Registros: {{ $ventas->count() }}
        </div>
        <div class="brand">Clínica URME · Caja</div>
        <h1>{{ $soloFarmacia ? 'Ventas de farmacia' : 'Historial de ventas' }}</h1>
        <div class="rango">{{ $rango }}</div>
    </div>

    @if ($ventas->isEmpty())
        <div class="empty">No hay ventas con los filtros seleccionados.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th width="4%">N°</th>
                    <th width="9%">Fecha</th>
                    <th width="5%">Tipo</th>
                    <th>Cliente / Paciente</th>
                    <th width="12%">Doctor</th>
                    <th width="10%">Seguro</th>
                    <th width="11%">Usuario</th>
                    <th width="7%">Estado</th>
                    <th width="7%">Pago</th>
                    @if ($verMontos)
                        <th width="8%" class="num">Total (Bs)</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($ventas as $venta)
                    <tr>
                        <td>{{ $venta->id }}</td>
                        <td>{{ optional($venta->fecha_hora)->format('d/m/Y H:i') }}</td>
                        <td class="{{ $venta->esEgreso() ? 'gasto' : '' }}">{{ $venta->esEgreso() ? 'GASTO' : 'VENTA' }}</td>
                        <td>
                            {{ VentasExport::cliente($venta) }}
                            @unless ($venta->esEgreso())
                                <div class="det">{{ $venta->detalles->pluck('nombre')->implode(', ') }}</div>
                            @endunless
                        </td>
                        <td>{{ $venta->doctor?->nombre ?: '—' }}</td>
                        <td>{{ $venta->seguro?->nombre ?: 'PARTICULAR' }}</td>
                        <td>{{ $venta->user?->name ?: '—' }}</td>
                        <td class="{{ $venta->estado === 'ANULADO' ? 'anulado' : '' }}">{{ VentasExport::estado($venta) }}</td>
                        <td>{{ $venta->tipo_pago ?: '—' }}</td>
                        @if ($verMontos)
                            <td class="num {{ $venta->esEgreso() ? 'gasto' : '' }}">{{ number_format($venta->esEgreso() ? -(float) $venta->total : (float) $venta->total, 2) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            @if ($verMontos)
                <tfoot>
                    <tr>
                        <td colspan="9" class="num">Neto en caja (ventas cobradas menos gastos; sin anuladas ni pendientes)</td>
                        <td class="num">{{ number_format(VentasExport::neto($ventas), 2) }} Bs</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif
</body>
</html>
