<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Unidades de Medida</title>
    <style>
        @page { margin: 15px 18px; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8px; line-height: 1.3; }
        .header { border-bottom: 2px solid #4A148C; padding-bottom: 5px; margin-bottom: 7px; overflow: hidden; }
        .brand { color: #4A148C; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        .summary { background: #F3E5F5; border: 1px solid #CE93D8; padding: 5px 8px; margin-bottom: 7px; overflow: hidden; }
        .s-label { color: #546E7A; font-size: 7px; text-transform: uppercase; display: block; }
        .s-val { color: #4A148C; font-size: 12px; font-weight: bold; display: block; }
        .th { background: #4A148C; color: #fff; font-size: 7.5px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; overflow: hidden; }
        .cell { display: inline-block; vertical-align: top; }
        .c-num  { width:  5%; text-align: right; padding-right: 4px; }
        .c-nom  { width: 65%; }
        .c-abr  { width: 28%; }
        .row-item { border-bottom: 1px solid #dbe4ee; padding: 3px; page-break-inside: avoid; }
        .row-item:nth-child(even) { background: #FAF5FF; }
        .empty { border: 1px dashed #cbd5e1; color: #64748b; padding: 22px; text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}<br>Total: {{ $total }} registros</div>
        <div class="brand">Clínica URME · Farmacia</div>
        <h1>Unidades de Medida</h1>
    </div>

    <div class="summary">
        <span class="s-label">Total unidades</span>
        <span class="s-val">{{ number_format($total, 0, ',', '.') }}</span>
    </div>

    @if ($items->isEmpty())
        <div class="empty">No existen unidades para los filtros aplicados.</div>
    @else
        <div class="th">
            <span class="cell c-num">#</span>
            <span class="cell c-nom">Nombre</span>
            <span class="cell c-abr">Abreviatura</span>
        </div>
        @foreach ($items as $i => $item)
            <div class="row-item">
                <span class="cell c-num">{{ $i + 1 }}</span>
                <span class="cell c-nom">{{ $item->nombre }}</span>
                <span class="cell c-abr">{{ $item->abreviatura ?: '—' }}</span>
            </div>
        @endforeach
    @endif
</body>
</html>
