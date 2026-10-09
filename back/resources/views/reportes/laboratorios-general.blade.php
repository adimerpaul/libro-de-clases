<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reportes generales de laboratorio</title>
    <style>
        @page { margin: 25px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #172033; }
        h1 { font-size: 18px; color: #00695c; margin: 0 0 8px; }
        .summary { background: #e0f2f1; padding: 10px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background: #00695c; color: white; text-align: left; }
        td, th { padding: 5px; border-bottom: 1px solid #ddd; overflow-wrap: break-word; }
        tbody tr:nth-child(even) { background: #f1f8f7; }
        .money { text-align: right; }
    </style>
</head>
<body>
    <h1>Clínica URME · Reportes generales de laboratorio</h1>
    <div>Fecha de solicitud: {{ $filters['desde'] }} al {{ $filters['hasta'] }}</div>
    <div>Laboratorio / prueba: {{ $filters['nombre'] ?? 'Todos' }} · Generado: {{ now()->format('d/m/Y H:i') }}</div>
    <div class="summary">
        Solicitudes: {{ $items->pluck('solicitude_id')->unique()->count() }} ·
        Pruebas: {{ $items->count() }} · Importe de pruebas: Bs {{ number_format($items->sum('precio'), 2) }}
    </div>
    <table>
        <thead><tr><th style="width:9%">Fecha</th><th style="width:16%">Solicitud</th><th style="width:20%">Paciente</th><th style="width:9%">CI</th><th style="width:23%">Laboratorio / prueba</th><th style="width:14%">Estado</th><th style="width:9%" class="money">Importe (Bs)</th></tr></thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td>{{ $item->solicitude->fecha_solicitud->format('d/m/Y') }}</td>
                <td>{{ $item->solicitude->codigo_solicitud }}</td>
                <td>{{ $item->solicitude->paciente?->nombre_completo }}</td>
                <td>{{ $item->solicitude->paciente?->ci }}</td>
                <td>{{ $item->producto_nombre }}</td>
                <td>{{ $item->solicitude->estado }}</td>
                <td class="money">{{ number_format($item->precio, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No hay laboratorios para los filtros seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
