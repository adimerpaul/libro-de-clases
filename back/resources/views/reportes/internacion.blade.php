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
    <title>Proforma de internación Nº {{ str_pad($internacion->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { margin: 26px 26px 46px; size: letter portrait; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1c2733; font-size: 8.4px; line-height: 1.22; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .ico { vertical-align: -1px; }
        .r { text-align: right; }
        .c { text-align: center; }

        /* ── Membrete ─────────────────────────────────────────── */
        .head td { vertical-align: middle; }
        .mark { width: 30px; }
        .mark-box { width: 26px; height: 26px; background: {{ $azul }}; text-align: center; line-height: 26px; }
        .brand { font-size: 13px; font-weight: bold; color: {{ $azul }}; letter-spacing: 0.6px; }
        .brand-sub { font-size: 7.2px; color: #64748b; }
        .doc-tag { font-size: 9.4px; font-weight: bold; color: #fff; background: {{ $azul }}; padding: 2px 9px; letter-spacing: 0.5px; }
        .doc-nro { font-size: 12px; font-weight: bold; color: {{ $azul }}; letter-spacing: 1px; margin-top: 2px; }
        .doc-meta { font-size: 7.2px; color: #64748b; }
        .rule { height: 2px; background: {{ $azul }}; margin: 5px 0 0; }
        .rule-thin { height: 1px; background: #cbd9ea; margin: 0 0 7px; }

        /* ── Ficha del paciente ───────────────────────────────── */
        .ficha { margin-bottom: 7px; }
        .ficha td { border: 1px solid #d5e2f2; padding: 2.5px 6px; width: 25%; }
        .ficha .lbl { font-size: 6.6px; color: #7c8ca0; text-transform: uppercase; letter-spacing: 0.4px; }
        .ficha .val { font-size: 8.8px; font-weight: bold; color: #142c4c; }
        .ficha .destacado { background: #eef4fc; }

        /* ── Detalle agrupado ─────────────────────────────────── */
        .titulo { font-size: 8px; font-weight: bold; color: {{ $azul }}; text-transform: uppercase; letter-spacing: 0.8px; padding-bottom: 2px; }
        .items thead th { background: {{ $azul }}; color: #fff; font-size: 6.8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; padding: 3px 5px; text-align: left; }
        .items td { padding: 1.6px 5px; border-bottom: 1px solid #eef3f9; font-size: 8.2px; }
        .items tbody tr.par td { background: #f7fafd; }
        .c-num  { width: 4%; text-align: center; }
        .c-cant { width: 8%; text-align: center; }
        .c-prec { width: 12%; text-align: right; }
        .c-tot  { width: 13%; text-align: right; }
        .c-por  { width: 20%; }

        tr.grupo td { background: #e7eff9; border-top: 1px solid #b9cee8; border-bottom: 1px solid #b9cee8; padding: 2.5px 5px; }
        .grupo-nom { font-size: 8.2px; font-weight: bold; color: #12304f; text-transform: uppercase; letter-spacing: 0.4px; }
        .grupo-n { font-size: 6.8px; color: #61748c; }
        .grupo-sub { font-size: 8.4px; font-weight: bold; color: {{ $azul }}; text-align: right; }
        .marca { width: 3px; padding: 0 !important; }
        .item-nom { font-weight: bold; color: #22303f; }
        .item-por { color: #7c8ca0; font-size: 7.4px; }
        .empty { border: 1px dashed #b9cee8; color: #64748b; padding: 14px; text-align: center; }

        /* ── Cierre ───────────────────────────────────────────── */
        .cierre { margin-top: 8px; }
        .resumen td { padding: 1.5px 6px; font-size: 7.6px; border-bottom: 1px solid #eef2f7; }
        .resumen .rs-nom { color: #4a5c72; }
        .resumen .rs-val { text-align: right; font-weight: bold; color: #22303f; }
        .caja-total { border: 1.5px solid {{ $azul }}; }
        .caja-total .tl { background: {{ $azul }}; color: #fff; font-size: 7.4px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; padding: 2.5px 8px; }
        .caja-total .tv { font-size: 16px; font-weight: bold; color: {{ $azul }}; text-align: right; padding: 4px 8px 5px; }
        .letras { font-size: 7.2px; color: #4a5c72; padding: 0 8px 4px; border-top: 1px dotted #b9cee8; }
        .sello { border: 1.5px solid #1b7a3d; color: #1b7a3d; padding: 3px 8px; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-align: center; }
        .sello-sub { font-size: 6.8px; font-weight: normal; letter-spacing: 0; color: #3f7a55; }

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
            <td class="mark"><div class="mark-box">{!! PdfTema::icono('cruz', '#ffffff', 16) !!}</div></td>
            <td>
                <div class="brand">CLÍNICA URME</div>
                <div class="brand-sub">Calle Cochabamba entre Soria Galvarro y 6 de Octubre · Oruro · Cel. 70431083</div>
                <div class="brand-sub">Atención de emergencias las 24 horas, los 365 días del año</div>
            </td>
            <td class="r" style="width:31%">
                <span class="doc-tag">PROFORMA DE INTERNACIÓN</span>
                <div class="doc-nro">Nº {{ str_pad($internacion->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="doc-meta">
                    {!! PdfTema::icono('reloj', '#64748b', 7) !!} Emitida {{ now()->format('d/m/Y H:i') }}
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
                <div class="val">{{ $internacion->paciente->nombre_completo }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('documento', '#7c8ca0', 7) !!} C.I.</div>
                <div class="val">{{ $internacion->paciente->ci ?: '—' }}</div>
            </td>
            <td class="destacado">
                <div class="lbl">{!! PdfTema::icono('escudo', '#7c8ca0', 7) !!} Seguro</div>
                <div class="val">{{ $internacion->seguro->nombre ?? 'PARTICULAR' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="lbl">{!! PdfTema::icono('calendario', '#7c8ca0', 7) !!} Fecha de ingreso</div>
                <div class="val">{{ $internacion->fecha_ingreso ?: '—' }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('calendario', '#7c8ca0', 7) !!} Fecha de alta</div>
                <div class="val">{{ $internacion->fecha_alta ?: 'SIN ALTA' }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('cama', '#7c8ca0', 7) !!} Días internado</div>
                <div class="val">{{ $internacion->dias_internado ?? '—' }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('maletin', '#7c8ca0', 7) !!} Tipo de paciente</div>
                <div class="val">{{ $internacion->tipo_paciente ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="lbl">{!! PdfTema::icono('codigo', '#7c8ca0', 7) !!} Código H.C.</div>
                <div class="val">{{ $internacion->codigo_hc ?: '—' }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('puerta', '#7c8ca0', 7) !!} Sala</div>
                <div class="val">{{ $internacion->sala ?: '—' }}</div>
            </td>
            <td colspan="2">
                <div class="lbl">{!! PdfTema::icono('etiqueta', '#7c8ca0', 7) !!} Cargos registrados</div>
                <div class="val">
                    {{ $internacion->items->count() }}
                    {{ $internacion->items->count() === 1 ? 'ítem' : 'ítems' }}
                    en {{ $grupos->count() }} {{ $grupos->count() === 1 ? 'área' : 'áreas' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Detalle agrupado por área ───────────────────────────── --}}
    <div class="titulo">{!! PdfTema::icono('documento', $azul, 8) !!} Detalle de cargos por área</div>

    @if ($internacion->items->isEmpty())
        <div class="empty">Sin cargos registrados en esta internación.</div>
    @else
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
                @foreach ($grupos as $grupo)
                    <tr class="grupo">
                        <td class="marca" style="background: {{ $grupo['color'] }}"></td>
                        <td class="c">{!! PdfTema::icono($grupo['icono'], $grupo['color'], 9) !!}</td>
                        <td colspan="3">
                            <span class="grupo-nom">{{ $grupo['nombre'] }}</span>
                            <span class="grupo-n">
                                · {{ $grupo['cantidad'] }} {{ $grupo['cantidad'] === 1 ? 'ítem' : 'ítems' }}
                            </span>
                        </td>
                        <td class="grupo-sub">{{ $money($grupo['subtotal']) }}</td>
                        <td></td>
                    </tr>
                    @foreach ($grupo['items'] as $item)
                        @php $n++; @endphp
                        <tr class="{{ $n % 2 === 0 ? 'par' : '' }}">
                            <td class="marca" style="background: {{ $grupo['color'] }}"></td>
                            <td class="c-num">{{ $n }}</td>
                            <td class="item-nom">{{ $item->nombre }}</td>
                            <td class="c-cant">{{ $cant($item->cantidad) }}</td>
                            <td class="c-prec">{{ $money($item->precio) }}</td>
                            <td class="c-tot"><b>{{ $money($item->total) }}</b></td>
                            <td class="c-por item-por">{{ $item->user->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Cierre: resumen por área + total ────────────────────── --}}
    <table class="cierre">
        <tr>
            <td style="width:52%; padding-right:14px">
                @if ($grupos->isNotEmpty())
                    <div class="titulo">{!! PdfTema::icono('etiqueta', $azul, 8) !!} Resumen por área</div>
                    <table class="resumen">
                        @foreach ($grupos as $grupo)
                            <tr>
                                <td class="marca" style="background: {{ $grupo['color'] }}"></td>
                                <td class="rs-nom">
                                    {!! PdfTema::icono($grupo['icono'], $grupo['color'], 7.5) !!}
                                    {{ $grupo['nombre'] }}
                                </td>
                                <td class="rs-val" style="width:70px">{{ $money($grupo['subtotal']) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                @if ($internacion->pagada)
                    <table style="margin-top:8px"><tr><td class="sello">
                        {!! PdfTema::icono('pago', '#1b7a3d', 9) !!} PAGADO
                        <div class="sello-sub">
                            {{ optional($internacion->pagado_en)->format('d/m/Y H:i') }}
                            · {{ $internacion->pago_tipo ?: 'EFECTIVO' }}
                            @if ($internacion->pagadoPor) · {{ $internacion->pagadoPor->name }} @endif
                        </div>
                    </td></tr></table>
                @endif
            </td>
            <td>
                <table class="caja-total">
                    <tr>
                        <td class="tl">{!! PdfTema::icono('pago', '#ffffff', 8) !!} Total de la cuenta</td>
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
        <span class="der">Proforma Nº {{ str_pad($internacion->id, 6, '0', STR_PAD_LEFT) }}</span>
        Documento no fiscal · Los importes pueden variar mientras la internación siga abierta.
    </div>

</body>
</html>
