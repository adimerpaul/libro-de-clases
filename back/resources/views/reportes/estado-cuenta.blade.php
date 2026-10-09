@php
    use App\Support\PdfTema;

    $azul = PdfTema::AZUL;
    $money = fn ($v) => number_format((float) $v, 2, ',', '.');
    // Las cantidades enteras se imprimen sin decimales: 5 en vez de 5,00.
    $cant = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta · {{ $paciente->nombre_completo }}</title>
    <style>
        @page { margin: 26px 26px 46px; size: letter portrait; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1c2733; font-size: 8.4px; line-height: 1.22; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .r { text-align: right; }
        .c { text-align: center; }

        /* ── Membrete ─────────────────────────────────────────── */
        .head td { vertical-align: middle; }
        .mark { width: 76px; padding-right: 8px; }
        .logo { width: 72px; height: auto; display: block; }
        .mark-box { width: 26px; height: 26px; background: {{ $azul }}; text-align: center; line-height: 26px; }
        .brand { font-size: 13px; font-weight: bold; color: {{ $azul }}; letter-spacing: 0.6px; }
        .brand-sub { font-size: 7.2px; color: #64748b; }
        .doc-tag { font-size: 9.4px; font-weight: bold; color: #fff; background: {{ $azul }}; padding: 2px 9px; letter-spacing: 0.5px; }
        .doc-meta { font-size: 7.2px; color: #64748b; margin-top: 3px; }
        .rule { height: 2px; background: {{ $azul }}; margin: 5px 0 0; }
        .rule-thin { height: 1px; background: #cbd9ea; margin: 0 0 7px; }

        /* ── Ficha del paciente ───────────────────────────────── */
        .ficha { margin-bottom: 8px; }
        .ficha td { border: 1px solid #d5e2f2; padding: 2.5px 6px; width: 25%; }
        .ficha .lbl { font-size: 6.6px; color: #7c8ca0; text-transform: uppercase; letter-spacing: 0.4px; }
        .ficha .val { font-size: 8.8px; font-weight: bold; color: #142c4c; }
        .ficha .destacado { background: #eef4fc; }

        /* ── Bloques ──────────────────────────────────────────── */
        .titulo { font-size: 8px; font-weight: bold; color: {{ $azul }}; text-transform: uppercase; letter-spacing: 0.8px; padding: 8px 0 2px; }
        .items thead th { background: {{ $azul }}; color: #fff; font-size: 6.8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; padding: 3px 5px; text-align: left; }
        .items td { padding: 2px 5px; border-bottom: 1px solid #e8eef6; font-size: 8.2px; }
        .items tbody tr.par td { background: #f7fafd; }
        .c-num  { width: 5%; text-align: center; }
        .c-cant { width: 8%; text-align: center; }
        .c-prec { width: 13%; text-align: right; }
        .c-tot  { width: 14%; text-align: right; }
        .c-por  { width: 20%; }

        tr.grupo td { background: #e7eff9; border-top: 1px solid #b9cee8; border-bottom: 1px solid #b9cee8; padding: 2.5px 5px; }
        .grupo-nom { font-size: 8.2px; font-weight: bold; color: #12304f; text-transform: uppercase; letter-spacing: 0.4px; }
        .grupo-n { font-size: 6.8px; color: #61748c; }
        .grupo-sub { font-size: 8.4px; font-weight: bold; color: {{ $azul }}; text-align: right; }
        .marca { width: 3px; padding: 0 !important; }
        .item-nom { font-weight: bold; color: #22303f; }
        .item-por { color: #7c8ca0; font-size: 7.4px; }
        .lote { color: #7c8ca0; font-weight: normal; font-size: 7.2px; }
        .empty { border: 1px dashed #b9cee8; color: #64748b; padding: 14px; text-align: center; }

        /* ── Cierre ───────────────────────────────────────────── */
        .cierre { margin-top: 10px; }
        .resumen td { padding: 2px 6px; font-size: 7.8px; border-bottom: 1px solid #eef2f7; }
        .resumen .rs-nom { color: #4a5c72; }
        .resumen .rs-val { text-align: right; font-weight: bold; color: #22303f; width: 80px; }
        .caja-total { border: 1.5px solid {{ $azul }}; }
        .caja-total .tl { background: {{ $azul }}; color: #fff; font-size: 7.4px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; padding: 2.5px 8px; }
        .caja-total .tv { font-size: 16px; font-weight: bold; color: {{ $azul }}; text-align: right; padding: 4px 8px 5px; }
        .letras { font-size: 7.2px; color: #4a5c72; padding: 0 8px 4px; border-top: 1px dotted #b9cee8; }
        .sello-ok { border: 1.5px solid #1b7a3d; color: #1b7a3d; padding: 8px; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-align: center; }

        .firmas { margin-top: 34px; }
        .firmas td { width: 33%; padding: 0 12px; }
        .firma-linea { border-top: 1px solid #8797ab; padding-top: 3px; font-size: 7.2px; color: #64748b; text-align: center; }

        .pie { position: fixed; bottom: -30px; left: 0; right: 0; border-top: 1px solid #d5e2f2; padding-top: 3px; font-size: 6.6px; color: #8797ab; }
        .pie .der { float: right; }
    </style>
</head>
<body>

    {{-- ── Membrete ────────────────────────────────────────────── --}}
    <table class="head">
        <tr>
            <td class="mark"><img class="logo" src="{{ public_path('images/logo-clinica-urme.png') }}" alt="Clínica URME"></td>
            <td>
                <div class="brand">CLÍNICA URME</div>
                <div class="brand-sub">Calle Cochabamba entre Soria Galvarro y 6 de Octubre · Oruro · Cel. 70431083</div>
                <div class="brand-sub">Atención de emergencias las 24 horas, los 365 días del año</div>
            </td>
            <td class="r" style="width:31%">
                <span class="doc-tag">ESTADO DE CUENTA</span>
                <div class="doc-meta">
                    {!! PdfTema::icono('reloj', '#64748b', 7) !!} Emitido {{ now()->format('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>
    <div class="rule-thin"></div>

    {{-- ── Ficha ───────────────────────────────────────────────── --}}
    <table class="ficha">
        <tr>
            <td colspan="2" class="destacado">
                <div class="lbl">{!! PdfTema::icono('persona', '#7c8ca0', 7) !!} Paciente</div>
                <div class="val">{{ $paciente->nombre_completo }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('documento', '#7c8ca0', 7) !!} C.I.</div>
                <div class="val">{{ $paciente->ci ?: '—' }}</div>
            </td>
            <td class="destacado">
                <div class="lbl">{!! PdfTema::icono('escudo', '#7c8ca0', 7) !!} Seguro</div>
                <div class="val">{{ $paciente->seguro->nombre ?? 'PARTICULAR' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="lbl">{!! PdfTema::icono('cama', '#7c8ca0', 7) !!} Internaciones por cobrar</div>
                <div class="val">{{ $internaciones->count() }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('pastilla', '#7c8ca0', 7) !!} Ventas pendientes</div>
                <div class="val">{{ $ventas->count() }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('etiqueta', '#7c8ca0', 7) !!} Teléfono</div>
                <div class="val">{{ $paciente->telefono ?: '—' }}</div>
            </td>
            <td class="destacado">
                <div class="lbl">{!! PdfTema::icono('pago', '#7c8ca0', 7) !!} Saldo pendiente</div>
                <div class="val">Bs. {{ $money($total) }}</div>
            </td>
        </tr>
    </table>

    @if ($internaciones->isEmpty() && $ventas->isEmpty())
        <div class="sello-ok">
            {!! PdfTema::icono('pago', '#1b7a3d', 11) !!} EL PACIENTE NO TIENE DEUDA PENDIENTE
        </div>
    @endif

    {{-- ── Internaciones pendientes ────────────────────────────── --}}
    @if ($internaciones->isNotEmpty())
        <div class="titulo">{!! PdfTema::icono('cama', $azul, 8) !!} Internaciones pendientes de cobro</div>

        <table class="items">
            <thead>
                <tr>
                    <th class="marca"></th>
                    <th class="c-num">#</th>
                    <th>Detalle</th>
                    <th class="c-cant">Cant.</th>
                    <th class="c-prec">P. unit. Bs.</th>
                    <th class="c-tot">Importe Bs.</th>
                    <th class="c-por">Registró</th>
                </tr>
            </thead>
            <tbody>
                @php $n = 0; @endphp
                @foreach ($internaciones as $internacion)
                    <tr class="grupo">
                        <td class="marca" style="background: {{ $azul }}"></td>
                        <td class="c">{!! PdfTema::icono('cama', $azul, 9) !!}</td>
                        <td colspan="3">
                            <span class="grupo-nom">Internación Nº {{ str_pad($internacion->id, 6, '0', STR_PAD_LEFT) }}</span>
                            <span class="grupo-n">
                                · Ingreso {{ $internacion->fecha_ingreso ?: '—' }}
                                · {{ $internacion->fecha_alta ? 'Alta '.$internacion->fecha_alta : 'SIN ALTA' }}
                                @if ($internacion->sala) · Sala {{ $internacion->sala }} @endif
                                · {{ $internacion->seguro->nombre ?? 'PARTICULAR' }}
                            </span>
                        </td>
                        <td class="grupo-sub">{{ $money($internacion->items->sum('total')) }}</td>
                        <td class="grupo-n">Subtotal internación</td>
                    </tr>
                    @forelse ($internacion->items as $item)
                        @php $n++; @endphp
                        <tr class="{{ $n % 2 === 0 ? 'par' : '' }}">
                            <td class="marca" style="background: {{ $azul }}"></td>
                            <td class="c-num">{{ $n }}</td>
                            <td class="item-nom">{{ $item->nombre }}</td>
                            <td class="c-cant">{{ $cant($item->cantidad) }}</td>
                            <td class="c-prec">{{ $money($item->precio) }}</td>
                            <td class="c-tot"><b>{{ $money($item->total) }}</b></td>
                            <td class="c-por item-por">{{ $item->user->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="marca" style="background: {{ $azul }}"></td>
                            <td colspan="6" class="item-por">Sin cargos registrados todavía.</td>
                        </tr>
                    @endforelse
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Productos de farmacia pendientes ────────────────────── --}}
    @if ($ventas->isNotEmpty())
        <div class="titulo">{!! PdfTema::icono('pastilla', $azul, 8) !!} Productos y servicios pendientes de pago</div>

        <table class="items">
            <thead>
                <tr>
                    <th class="marca"></th>
                    <th class="c-num">#</th>
                    <th>Detalle</th>
                    <th class="c-cant">Cant.</th>
                    <th class="c-prec">P. unit. Bs.</th>
                    <th class="c-tot">Importe Bs.</th>
                    <th class="c-por">Doctor</th>
                </tr>
            </thead>
            <tbody>
                @php $m = 0; @endphp
                @foreach ($ventas as $venta)
                    <tr class="grupo">
                        <td class="marca" style="background: #b26a00"></td>
                        <td class="c">{!! PdfTema::icono('pastilla', '#b26a00', 9) !!}</td>
                        <td colspan="3">
                            <span class="grupo-nom">Venta Nº {{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</span>
                            <span class="grupo-n">
                                · {{ optional($venta->fecha_hora)->format('d/m/Y H:i') }}
                                · {{ $venta->seguro->nombre ?? 'PARTICULAR' }}
                            </span>
                        </td>
                        <td class="grupo-sub">{{ $money($venta->total) }}</td>
                        <td class="grupo-n">Subtotal venta</td>
                    </tr>
                    @forelse ($venta->detalles as $detalle)
                        @php $m++; @endphp
                        <tr class="{{ $m % 2 === 0 ? 'par' : '' }}">
                            <td class="marca" style="background: #b26a00"></td>
                            <td class="c-num">{{ $m }}</td>
                            <td class="item-nom">
                                {{ $detalle->nombre }}
                                @if ($detalle->lote)<span class="lote"> · Lote {{ $detalle->lote }}</span>@endif
                            </td>
                            <td class="c-cant">{{ $cant($detalle->cantidad) }}</td>
                            <td class="c-prec">{{ $money($detalle->precio) }}</td>
                            <td class="c-tot"><b>{{ $money($detalle->total) }}</b></td>
                            <td class="c-por item-por">{{ $venta->doctor->nombre ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="marca" style="background: #b26a00"></td>
                            <td colspan="6" class="item-por">Sin ítems registrados.</td>
                        </tr>
                    @endforelse
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Cierre ──────────────────────────────────────────────── --}}
    <table class="cierre">
        <tr>
            <td style="width:52%; padding-right:14px">
                <div class="titulo" style="padding-top:0">{!! PdfTema::icono('etiqueta', $azul, 8) !!} Resumen</div>
                <table class="resumen">
                    <tr>
                        <td class="marca" style="background: {{ $azul }}"></td>
                        <td class="rs-nom">
                            {!! PdfTema::icono('cama', $azul, 7.5) !!}
                            Internaciones ({{ $internaciones->count() }})
                        </td>
                        <td class="rs-val">{{ $money($totalInternaciones) }}</td>
                    </tr>
                    <tr>
                        <td class="marca" style="background: #b26a00"></td>
                        <td class="rs-nom">
                            {!! PdfTema::icono('pastilla', '#b26a00', 7.5) !!}
                            Productos y servicios ({{ $ventas->count() }})
                        </td>
                        <td class="rs-val">{{ $money($totalVentas) }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="caja-total">
                    <tr>
                        <td class="tl">{!! PdfTema::icono('pago', '#ffffff', 8) !!} Total pendiente de cobro</td>
                    </tr>
                    <tr>
                        <td class="tv">Bs. {{ $money($total) }}</td>
                    </tr>
                    <tr>
                        <td class="letras">Son: {{ PdfTema::enLetras((float) $total) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="firmas">
        <tr>
            <td><div class="firma-linea">Firma del paciente o responsable</div></td>
            <td><div class="firma-linea">Caja</div></td>
            <td><div class="firma-linea">Administración</div></td>
        </tr>
    </table>

    <div class="pie">
        <span class="der">{{ $paciente->nombre_completo }}</span>
        Documento no fiscal · Los importes pueden variar mientras haya internaciones abiertas.
    </div>

</body>
</html>
