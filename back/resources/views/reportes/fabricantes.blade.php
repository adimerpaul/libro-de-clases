<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Fabricantes</title>
    <style>
        @page { margin: 15px 18px; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8px; line-height: 1.3; }
        .header { border-bottom: 2px solid #BF360C; padding-bottom: 5px; margin-bottom: 7px; overflow: hidden; }
        .brand { color: #BF360C; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        .summary { background: #FBE9E7; border: 1px solid #FFAB91; padding: 5px 8px; margin-bottom: 7px; overflow: hidden; }
        .s-label { color: #546E7A; font-size: 7px; text-transform: uppercase; display: block; }
        .s-val { color: #BF360C; font-size: 12px; font-weight: bold; display: block; }
        .th { background: #BF360C; color: #fff; font-size: 7.5px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; overflow: hidden; }
        .cell { display: inline-block; vertical-align: top; }
        .c-num  { width:  5%; text-align: right; padding-right: 4px; }
        .c-nom  { width: 60%; }
        .c-pais { width: 33%; }
        .row-item { border-bottom: 1px solid #dbe4ee; padding: 3px; page-break-inside: avoid; }
        .row-item:nth-child(even) { background: #FFF3F1; }
        .empty { border: 1px dashed #cbd5e1; color: #64748b; padding: 22px; text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}<br>Total: {{ $total }} registros</div>
        <div class="brand">Clínica URME · Farmacia</div>
        <h1>Fabricantes</h1>
    </div>

    <div class="summary">
        <span class="s-label">Total fabricantes</span>
        <span class="s-val">{{ number_format($total, 0, ',', '.') }}</span>
    </div>

    @if ($items->isEmpty())
        <div class="empty">No existen fabricantes para los filtros aplicados.</div>
    @else
        <div class="th">
            <span class="cell c-num">#</span>
            <span class="cell c-nom">Nombre</span>
            <span class="cell c-pais">País</span>
        </div>
        @foreach ($items as $i => $item)
            <div class="row-item">
                <span class="cell c-num">{{ $i + 1 }}</span>
                <span class="cell c-nom">{{ $item->nombre }}</span>
                <span class="cell c-pais">{{ $item->pais ?: '—' }}</span>
            </div>
        @endforeach
    @endif
</body>
</html>
