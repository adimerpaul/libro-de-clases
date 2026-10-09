<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos Farmacia</title>
    <style>
        @page { margin: 15px 18px; size: letter landscape; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8px; line-height: 1.3; }
        .header { border-bottom: 2px solid #00695C; padding-bottom: 5px; margin-bottom: 7px; overflow: hidden; }
        .brand { color: #00695C; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        .summary { background: #E0F2F1; border: 1px solid #80CBC4; padding: 5px 8px; margin-bottom: 7px; overflow: hidden; }
        .s-box { display: inline-block; width: 48%; vertical-align: top; }
        .s-label { color: #546E7A; font-size: 7px; text-transform: uppercase; display: block; }
        .s-val { color: #004D40; font-size: 12px; font-weight: bold; display: block; }
        .empty { border: 1px dashed #cbd5e1; color: #64748b; padding: 22px; text-align: center; margin-top: 18px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; }
        table.items th { background: #00695C; color: #fff; font-size: 7.5px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; text-align: left; }
        table.items td { padding: 3px; border-bottom: 1px solid #dbe4ee; }
        table.items tbody tr:nth-child(even) td { background: #F1F8F7; }
        .c-cod  { width:  6%; }
        .c-nom  { width: 19%; }
        .c-com  { width: 15%; }
        .c-marc { width:  9%; }
        .c-desc { width: 14%; }
        .c-fab  { width: 12%; }
        .c-unid { width:  6%; }
        .c-cat  { width: 12%; }
        .c-prec { width:  7%; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}<br>Total: {{ $total }} registros</div>
        <div class="brand">Clínica URME · Farmacia</div>
        <h1>Catálogo de Productos</h1>
    </div>

    <div class="summary">
        <div class="s-box">
            <span class="s-label">Total productos</span>
            <span class="s-val">{{ number_format($total, 0, ',', '.') }}</span>
        </div>
        <div class="s-box">
            <span class="s-label">Búsqueda</span>
            <span class="s-val" style="font-size:9px">{{ $q ?: '—' }}</span>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="empty">No existen productos para los filtros aplicados.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th class="c-cod">Código</th>
                    <th class="c-nom">Nombre genérico</th>
                    <th class="c-com">Nombre comercial</th>
                    <th class="c-marc">Marca</th>
                    <th class="c-desc">Descripción</th>
                    <th class="c-fab">Fabricante</th>
                    <th class="c-unid">Unidad</th>
                    <th class="c-cat">Categoría</th>
                    <th class="c-prec">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="c-cod">{{ $item->codigo ?: '—' }}</td>
                        <td class="c-nom">{{ $item->nombre }}</td>
                        <td class="c-com">{{ $item->nombre_comercial ?: '—' }}</td>
                        <td class="c-marc">{{ $item->marca ?: '—' }}</td>
                        <td class="c-desc">{{ $item->descripcion ?: '—' }}</td>
                        <td class="c-fab">{{ $item->fabricante?->nombre ?: '—' }}</td>
                        <td class="c-unid">{{ $item->unidad?->abreviatura ?: ($item->unidad?->nombre ?: '—') }}</td>
                        <td class="c-cat">{{ $item->tipoProducto?->nombre ?: '—' }}</td>
                        <td class="c-prec">{{ $item->precio ? number_format($item->precio, 2, ',', '.') : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
