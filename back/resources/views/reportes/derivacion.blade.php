<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px 34px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9px; }
        .header, .data { width: 100%; border-collapse: collapse; }
        .header { border-bottom: 2px solid #2d82b7; margin-bottom: 6px; }
        .header td { vertical-align: middle; padding-bottom: 6px; }
        .logo { display: block; width: 205px; height: auto; }
        .title { color: #164e7a; font-size: 14px; font-weight: bold; text-align: right; }
        .subtitle { color: #4b5563; font-size: 8px; text-align: right; }
        .data { margin: 8px 0; }
        .data td { border: 1px solid #aeb9c5; padding: 4px 6px; }
        .label { color: #374151; font-weight: bold; }
        .image-wrap { width: 100%; text-align: center; margin-top: 10px; }
        .result-image { max-width: 100%; max-height: 670px; height: auto; }
        .footer { margin-top: 9px; border-top: 1px solid #c2cad3; padding-top: 5px; color: #6b7280; font-size: 7.5px; }
    </style>
</head>
<body>
<table class="header"><tr>
    <td style="width:48%">
        <img class="logo" src="{{ public_path('images/logo-laboratorio-urme.jpg') }}" alt="Laboratorio URME">
    </td>
    <td style="width:52%">
        <div class="title">DERIVACIÓN DE LABORATORIO</div>
        <div class="subtitle">Registro N.º {{ $derivacion->id }} - Impreso {{ now()->format('d/m/Y H:i') }}</div>
    </td>
</tr></table>

<table class="data">
    <tr>
        <td style="width:62%"><span class="label">PACIENTE:</span> {{ $derivacion->paciente }}</td>
        <td><span class="label">FECHA:</span> {{ $derivacion->fecha->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td><span class="label">SERVICIO:</span> {{ $derivacion->servicio ?: '-' }}</td>
        <td><span class="label">REGISTRADO POR:</span> {{ $derivacion->user->name }}</td>
    </tr>
    <tr><td colspan="2"><span class="label">LABORATORIO DE DESTINO:</span> {{ $derivacion->laboratorio_destino ?: '-' }}</td></tr>
    @if($derivacion->observaciones)
        <tr><td colspan="2"><span class="label">OBSERVACIONES:</span> {{ $derivacion->observaciones }}</td></tr>
    @endif
</table>

<div class="image-wrap">
    <img class="result-image" src="{{ public_path('images/derivaciones/'.$derivacion->imagen) }}" alt="Documento derivado">
</div>

<div class="footer">Clínica URME - Laboratorio de Diagnóstico Clínico</div>
</body>
</html>
