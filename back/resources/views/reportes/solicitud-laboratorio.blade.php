<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: letter; margin: 22px 32px 92px; }
        .description { margin: 5px 0 8px; line-height: 1.4; }
        .description p { margin: 3px 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 8.5px; }
        .header, .patient, .results { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 3px; border-bottom: 2px solid #2d82b7; }
        .header td { vertical-align: middle; padding-bottom: 5px; }
        .laboratory-logo { width: 205px; height: auto; display: block; }
        .document-code { color: #155ca8; font-size: 11px; }
        .meta { color: #4b5563; font-size: 8px; line-height: 1.45; }
        .title { color: #164e7a; font-size: 12px; font-weight: bold; text-align: center; margin: 5px 0 6px; letter-spacing: .3px; }
        .patient { margin-bottom: 7px; }
        .patient td { border: 1px solid #aeb9c5; padding: 3px 5px; }
        .label { font-weight: bold; color: #374151; }
        .service-title { color: #1f2937; font-size: 9.5px; font-weight: bold; text-align: center; margin: 7px 0 2px; }
        .results { margin-bottom: 4px; }
        .results th { border-top: 1.5px solid #2868a8; border-bottom: 1.5px solid #2868a8; color: #1f4f7d; padding: 4px; text-align: left; font-size: 7.5px; }
        .results td { border-bottom: 1px solid #e5e7eb; padding: 3.5px 4px; vertical-align: top; }
        .results tr { page-break-inside: avoid; }
        .area td { color: #1f2937; font-size: 9px; font-weight: bold; padding: 4px; text-decoration: underline; }
        .result { font-weight: bold; }
        .reference p, .reference div, .reference ul, .reference ol, .reference blockquote { margin: 0; }
        .reference ul, .reference ol { padding-left: 12px; }
        .signature { margin-top: 24px; width: 230px; border-top: 1px solid #374151; text-align: center; padding-top: 3px; font-size: 8px; }
        .signature-name { margin-top: 2px; font-weight: bold; color: #164e7a; font-size: 8.5px; }
        .page-footer { position: fixed; left: 0; right: 0; bottom: -78px; height: 70px; border-top: 1px solid #c2cad3; color: #374151; }
        .page-footer table { width: 100%; height: 66px; border-collapse: collapse; }
        .page-footer td { padding: 3px 5px; vertical-align: middle; }
        .footer-message { width: 62%; color: #245f88; font-size: 8.5px; font-style: italic; font-weight: bold; letter-spacing: .1px; white-space: nowrap; }
        .footer-patient { width: 25%; text-align: right; font-size: 8px; text-transform: uppercase; }
        .footer-qr { width: 13%; text-align: right; }
        .footer-qr img { width: 56px; height: 56px; }
    </style>
    @if($paraNavegador ?? false)
    <style>
        /* Impresión desde el navegador: el pie queda dentro del área imprimible y
           un tfoot vacío le reserva espacio al final de cada hoja. */
        @page { size: letter; margin: 8mm 11mm; }
        .page-footer { bottom: 0; background: #fff; }
        .print-layout { width: 100%; border-collapse: collapse; }
        .print-layout > tbody > tr > td, .print-layout > tfoot > tr > td { padding: 0; }
        .footer-space { height: 78px; }
    </style>
    @endif
</head>
<body>
@if($paraNavegador ?? false)
<table class="print-layout"><tfoot><tr><td class="footer-space"></td></tr></tfoot><tbody><tr><td>
@endif
<table class="header"><tr>
    <td style="width:42%">
        <img class="laboratory-logo" src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('images/logo-laboratorio-urme.jpg'))) }}" alt="Laboratorio de Diagnóstico Clínico URME">
    </td>
    <td class="meta" style="width:58%;text-align:right">
        <strong class="document-code">{{ $solicitude->codigo_solicitud }}</strong><br>
        Fecha de solicitud: {{ $solicitude->fecha_solicitud->format('d/m/Y') }} {{ substr($solicitude->hora_solicitud, 0, 5) }}<br>
        Fecha de impresión: {{ now()->format('d/m/Y H:i') }}
    </td>
</tr></table>
<div class="title">INFORME DE LABORATORIO CLÍNICO</div>
<table class="patient">
    <tr>
        <td colspan="2"><span class="label">PACIENTE:</span> {{ $solicitude->paciente->nombre_completo }}</td>
        <td><span class="label">CI:</span> {{ $solicitude->paciente->ci ?: '-' }}</td>
    </tr>
    <tr>
        <td><span class="label">SEXO:</span> {{ $solicitude->paciente->sexo === 'F' ? 'FEMENINO' : ($solicitude->paciente->sexo === 'M' ? 'MASCULINO' : '-') }}</td>
        <td colspan="2"><span class="label">MÉDICO:</span> {{ $solicitude->doctor?->nombre ?: 'NO ASIGNADO' }}</td>
    </tr>
    <tr><td colspan="3"><span class="label">DIAGNÓSTICO:</span> {{ $solicitude->diagnostico_clinico ?: '-' }}</td></tr>
</table>
@foreach($solicitude->laboratorioItems->filter(fn ($item) => $item->resultados->where('visible', true)->isNotEmpty() || filled($item->producto?->descripcion)) as $item)
    <div class="service-title">{{ $item->producto_nombre }}</div>
    @if($item->producto?->descripcion)
        <div class="description">{!! $item->producto->descripcion_html !!}</div>
    @endif
    <table class="results">
        <thead><tr>
            <th style="width:25%">ANÁLISIS</th>
            <th style="width:15%">RESULTADO</th>
            <th style="width:26%">VALORES DE REFERENCIA</th>
            <th style="width:18%">MÉTODO</th>
            <th style="width:16%">MUESTRA</th>
        </tr></thead>
        <tbody>
            <tr class="area">
                <td colspan="5">ÁREA DE {{ $item->producto?->tipoProducto?->nombre ?: 'LABORATORIO' }}</td>
            </tr>
            @foreach($item->resultados->where('visible', true) as $resultado)
                <tr>
                    <td>{{ $resultado->nombre }}</td>
                    <td class="result">{{ filled($resultado->valor) ? $resultado->valor : '-' }} {{ $resultado->unidad }}</td>
                    <td class="reference">{!! filled($resultado->rango_referencia) ? $resultado->rango_referencia_html : '-' !!}</td>
                    <td>{{ $resultado->metodo ?: '-' }}</td>
                    <td>{{ $resultado->muestra ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach
@if($solicitude->observaciones)
    <div style="margin-top:12px"><span class="label">OBSERVACIONES:</span> {{ $solicitude->observaciones }}</div>
@endif
<div class="signature">
    <div>RESPONSABLE DE LABORATORIO</div>
    <div class="signature-name">{{ $impresoPor->name }}</div>
</div>
@if($paraNavegador ?? false)
</td></tr></tbody></table>
@endif
<div class="page-footer">
    <table><tr>
        <td class="footer-message">Nuestro compromiso es brindarte resultados confiables para un mejor mañana.</td>
        <td class="footer-patient">{{ $solicitude->paciente->nombre_completo }}</td>
        <td class="footer-qr"><img src="{{ $qrDataUri }}" alt="QR de verificación"></td>
    </tr></table>
</div>
</body>
</html>
