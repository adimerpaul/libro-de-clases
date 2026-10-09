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
    <title>Estado de cuenta por categoría · {{ $paciente->nombre_completo }}</title>
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
        .c-ori  { width: 19%; }
        .tipo-tag { font-size: 6.4px; font-weight: bold; color: #fff; padding: 0.5px 4px; letter-spacing: 0.3px; }
        .ori-fecha { color: #7c8ca0; font-size: 7px; }
        .bar-bg { background: #eef2f7; height: 5px; width: 100%; }
        .bar { height: 5px; }
        .rs-pct { text-align: right; color: #7c8ca0; width: 34px; font-size: 7.2px; }

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
            <td class="r" style="width:36%">
                <span class="doc-tag">ESTADO DE CUENTA POR CATEGORÍA</span>
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
                <div class="val">{{ $internaciones }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('pastilla', '#7c8ca0', 7) !!} Ventas pendientes</div>
                <div class="val">{{ $ventas }}</div>
            </td>
            <td>
                <div class="lbl">{!! PdfTema::icono('etiqueta', '#7c8ca0', 7) !!} Categorías</div>
                <div class="val">{{ $grupos->count() }}</div>
            </td>
            <td class="destacado">
                <div class="lbl">{!! PdfTema::icono('pago', '#7c8ca0', 7) !!} Saldo pendiente</div>
                <div class="val">Bs. {{ $money($total) }}</div>
            </td>
        </tr>
    </table>

    @if ($grupos->isEmpty())
        <div class="sello-ok">
            {!! PdfTema::icono('pago', '#1b7a3d', 11) !!} EL PACIENTE NO TIENE DEUDA PENDIENTE
        </div>
    @else
        <div class="titulo">{!! PdfTema::icono('etiqueta', $azul, 8) !!} Cargos pendientes agrupados por categoría</div>

        <table class="items">
            <thead>
                <tr>
                    <th class="marca"></th>
                    <th class="c-num">#</th>
                    <th>Detalle</th>
                    <th class="c-ori">Origen</th>
                    <th class="c-cant">Cant.</th>
                    <th class="c-prec">P. unit. Bs.</th>
                    <th class="c-tot">Importe Bs.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grupos as $grupo)
                    <tr class="grupo">
                        <td class="marca" style="background: {{ $grupo['color'] }}"></td>
                        <td class="c">{!! PdfTema::icono($grupo['icono'], $grupo['color'], 9) !!}</td>
                        <td colspan="4">
                            <span class="grupo-nom">{{ $grupo['nombre'] }}</span>
                            <span class="grupo-n">· {{ $grupo['cantidad'] }} {{ $grupo['cantidad'] === 1 ? 'cargo' : 'cargos' }}</span>
                        </td>
                        <td class="grupo-sub" style="color: {{ $grupo['color'] }}">{{ $money($grupo['subtotal']) }}</td>
                    </tr>
                    @foreach ($grupo['items'] as $i => $cargo)
                        <tr class="{{ ($i + 1) % 2 === 0 ? 'par' : '' }}">
                            <td class="marca" style="background: {{ $grupo['color'] }}"></td>
                            <td class="c-num">{{ $i + 1 }}</td>
                            <td class="item-nom">
                                {{ $cargo['nombre'] }}
                                @if ($cargo['lote'])<span class="lote"> · Lote {{ $cargo['lote'] }}</span>@endif
                                @if ($cargo['tipo'])
                                    <br><span class="tipo-tag" style="background: {{ $cargo['tipo_color'] }}">{{ $cargo['tipo'] }}</span>
                                @endif
                            </td>
                            <td class="c-ori item-por">
                                {{ $cargo['origen'] }}
                                @if ($cargo['fecha'])<br><span class="ori-fecha">{{ $cargo['fecha'] }}</span>@endif
                            </td>
                            <td class="c-cant">{{ $cant($cargo['cantidad']) }}</td>
                            <td class="c-prec">{{ $money($cargo['precio']) }}</td>
                            <td class="c-tot"><b>{{ $money($cargo['total']) }}</b></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Cierre ──────────────────────────────────────────────── --}}
    <table class="cierre">
        <tr>
            <td style="width:56%; padding-right:14px">
                <div class="titulo" style="padding-top:0">{!! PdfTema::icono('etiqueta', $azul, 8) !!} Resumen por categoría</div>
                <table class="resumen">
                    @forelse ($grupos as $grupo)
                        @php $pct = $total > 0 ? $grupo['subtotal'] / $total * 100 : 0; @endphp
                        <tr>
                            <td class="marca" style="background: {{ $grupo['color'] }}"></td>
                            <td class="rs-nom">
                                {!! PdfTema::icono($grupo['icono'], $grupo['color'], 7.5) !!}
                                {{ $grupo['nombre'] }} ({{ $grupo['cantidad'] }})
                                <div class="bar-bg"><div class="bar" style="width: {{ round($pct, 1) }}%; background: {{ $grupo['color'] }}"></div></div>
                            </td>
                            <td class="rs-pct">{{ number_format($pct, 1, ',', '.') }}%</td>
                            <td class="rs-val">{{ $money($grupo['subtotal']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="rs-nom">Sin cargos pendientes.</td></tr>
                    @endforelse
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
