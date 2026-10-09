<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ventas del cierre de caja</title>
    <style>
        @page { margin: 18px 20px; size: letter; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8.5px; line-height: 1.35; }
        .header { border-bottom: 2px solid #00695C; padding-bottom: 5px; margin-bottom: 8px; overflow: hidden; }
        .brand { color: #00695C; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        .resumen { background: #E0F2F1; border: 1px solid #80CBC4; padding: 6px 8px; margin-bottom: 8px; }
        .resumen td { font-size: 8px; padding: 1px 6px; }
        .r-label { color: #546E7A; text-transform: uppercase; font-size: 7px; }
        .r-val { color: #004D40; font-weight: bold; font-size: 10px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; }
        table.items th { background: #00695C; color: #fff; font-size: 7.5px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; text-align: left; }
        table.items td { padding: 3px; border-bottom: 1px solid #dbe4ee; }
        table.items tbody tr:nth-child(even) td { background: #F1F8F7; }
        .num { text-align: right; }
        .det { color: #64748b; font-size: 7px; }
        .empty { border: 1px dashed #cbd5e1; color: #64748b; padding: 22px; text-align: center; margin-top: 18px; }
        tfoot td { font-weight: bold; border-top: 2px solid #00695C; padding: 4px 3px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}<br>
            Ventas: {{ $ventas->count() }}
        </div>
        <div class="brand">Clínica URME · Caja</div>
        <h1>Ventas del cierre de caja</h1>
    </div>

    <table class="resumen" width="100%">
        <tr>
            <td><span class="r-label">Cajero</span><br><span class="r-val">{{ $cierre->user?->name ?? '—' }}</span></td>
            <td><span class="r-label">Fecha</span><br><span class="r-val">{{ $cierre->fecha->format('d/m/Y') }}</span></td>
            <td><span class="r-label">Sistema</span><br><span class="r-val">{{ number_format((float) $cierre->monto_sistema, 2) }} Bs</span></td>
            <td><span class="r-label">Declarado</span><br><span class="r-val">{{ number_format((float) $cierre->monto, 2) }} Bs</span></td>
            <td><span class="r-label">Diferencia</span><br><span class="r-val">{{ number_format((float) $cierre->diferencia, 2) }} Bs</span></td>
        </tr>
    </table>

    @if ($ventas->isEmpty())
        <div class="empty">Este cierre no tiene movimientos registrados.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th width="6%">N°</th>
                    <th width="13%">Fecha y hora</th>
                    <th width="24%">Cliente / Paciente</th>
                    <th width="9%">Tipo</th>
                    <th width="9%">Estado</th>
                    <th width="10%">Pago</th>
                    <th width="7%" class="num">Ítems</th>
                    <th width="12%" class="num">Total (Bs)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ventas as $venta)
                    <tr>
                        <td>{{ $venta->id }}</td>
                        <td>{{ optional($venta->fecha_hora_cobro ?: $venta->fecha_hora)->format('d/m/Y H:i') }}</td>
                        <td>
                            {{ $venta->paciente?->nombre_completo ?: ($venta->cliente ?: ($venta->esEgreso() ? 'GASTO DE CAJA' : 'SIN CLIENTE')) }}
                            <div class="det">{{ $venta->detalles->pluck('nombre')->implode(', ') }}</div>
                        </td>
                        <td>{{ $venta->esEgreso() ? 'GASTO' : 'VENTA' }}</td>
                        <td>{{ $venta->estado }}</td>
                        <td>{{ $venta->tipo_pago ?: '—' }}</td>
                        <td class="num">{{ $venta->detalles->count() }}</td>
                        <td class="num">{{ number_format($venta->esEgreso() ? -(float) $venta->total : (float) $venta->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="num">Total del cierre (ventas menos gastos)</td>
                    <td class="num">{{ number_format((float) $ventas->sum(fn ($venta) => $venta->esEgreso() ? -(float) $venta->total : (float) $venta->total), 2) }} Bs</td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
