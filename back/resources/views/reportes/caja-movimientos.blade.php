<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ingresos y gastos</title>
    <style>
        @page { margin: 18px 20px; size: letter; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8.5px; line-height: 1.35; }
        .header { border-bottom: 2px solid #00695C; padding-bottom: 5px; margin-bottom: 8px; overflow: hidden; }
        .brand { color: #00695C; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        h2 { margin: 12px 0 4px; font-size: 10px; text-transform: uppercase; }
        h2.ing { color: #1B5E20; } h2.gas { color: #B71C1C; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        .resumen { background: #E0F2F1; border: 1px solid #80CBC4; padding: 6px 8px; margin-bottom: 8px; }
        .resumen td { font-size: 8px; padding: 1px 6px; }
        .r-label { color: #546E7A; text-transform: uppercase; font-size: 7px; }
        .r-val { color: #004D40; font-weight: bold; font-size: 10px; }
        .r-ing { color: #1B5E20; } .r-gas { color: #B71C1C; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; }
        table.items th { color: #fff; font-size: 7.5px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; text-align: left; }
        table.ing th { background: #2E7D32; } table.gas th { background: #C62828; }
        table.items td { padding: 3px; border-bottom: 1px solid #dbe4ee; }
        table.ing tbody tr:nth-child(even) td { background: #F1F8E9; }
        table.gas tbody tr:nth-child(even) td { background: #FDECEA; }
        tr.anulado td { color: #90a4ae; text-decoration: line-through; }
        .num { text-align: right; }
        .empty { border: 1px dashed #cbd5e1; color: #64748b; padding: 14px; text-align: center; }
        tfoot td { font-weight: bold; padding: 4px 3px; }
        table.ing tfoot td { border-top: 2px solid #2E7D32; }
        table.gas tfoot td { border-top: 2px solid #C62828; }
        .saldo { margin-top: 12px; border: 1px solid #80CBC4; background: #E0F2F1; padding: 6px 8px; text-align: right; font-size: 11px; }
        .saldo b { color: #004D40; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}<br>
            Periodo: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        </div>
        <div class="brand">Clínica URME · {{ $titulo_caja }}</div>
        <h1>Reporte de ingresos y gastos</h1>
    </div>

    <table class="resumen" width="100%">
        <tr>
            <td><span class="r-label">Ingresos</span><br><span class="r-val r-ing">{{ number_format($resumen['total_ingresos'], 2) }} Bs</span></td>
            <td><span class="r-label">Gastos</span><br><span class="r-val r-gas">{{ number_format($resumen['total_gastos'], 2) }} Bs</span></td>
            <td><span class="r-label">Saldo</span><br><span class="r-val">{{ number_format($resumen['saldo'], 2) }} Bs</span></td>
            <td><span class="r-label">Movimientos</span><br><span class="r-val">{{ $resumen['cantidad_ingresos'] + $resumen['cantidad_gastos'] }}</span></td>
        </tr>
    </table>

    @php
        $bloques = [
            ['titulo' => 'Ingresos', 'clase' => 'ing', 'filas' => $ingresos, 'total' => $resumen['total_ingresos'], 'quien' => 'Origen'],
            ['titulo' => 'Gastos', 'clase' => 'gas', 'filas' => $gastos, 'total' => $resumen['total_gastos'], 'quien' => 'Beneficiario'],
        ];
    @endphp

    @foreach ($bloques as $bloque)
        <h2 class="{{ $bloque['clase'] }}">{{ $bloque['titulo'] }} ({{ $bloque['filas']->count() }})</h2>

        @if ($bloque['filas']->isEmpty())
            <div class="empty">Sin {{ mb_strtolower($bloque['titulo']) }} registrados en el periodo.</div>
        @else
            <table class="items {{ $bloque['clase'] }}">
                <thead>
                    <tr>
                        <th width="12%">Fecha y hora</th>
                        <th width="13%">Categoría</th>
                        <th width="25%">Concepto</th>
                        <th width="18%">{{ $bloque['quien'] }}</th>
                        <th width="10%">Documento</th>
                        <th width="12%">Registrado por</th>
                        <th width="10%" class="num">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bloque['filas'] as $movimiento)
                        <tr @class(['anulado' => $movimiento->estado === 'ANULADO'])>
                            <td>{{ $movimiento->fecha_hora?->format('d/m/Y H:i') }}</td>
                            <td>{{ $movimiento->categoria ?: '—' }}</td>
                            <td>{{ $movimiento->concepto }}</td>
                            <td>{{ $movimiento->beneficiario ?: '—' }}</td>
                            <td>{{ $movimiento->documento ?: '—' }}</td>
                            <td>{{ $movimiento->user?->name ?: '—' }}</td>
                            <td class="num">{{ number_format((float) $movimiento->importe, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6">Total {{ mb_strtolower($bloque['titulo']) }} (sin anulados)</td>
                        <td class="num">{{ number_format($bloque['total'], 2) }} Bs</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @endforeach

    <div class="saldo">
        Saldo del periodo: <b>{{ number_format($resumen['saldo'], 2) }} Bs</b>
    </div>
</body>
</html>
