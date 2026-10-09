<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Kardex de reactivos / insumos</title>
<style>
@page { margin: 25px 25px 30px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { border: 1px solid #777; padding: 4px 3px; overflow-wrap: break-word; }
th { background: #f0f0f0; font-size: 7px; }
.header td { text-align: center; }
.title { font-size: 14px; font-weight: bold; }
.logo { width: 65px; }
.meta { font-size: 7px; text-align: left !important; }
.info { margin-top: 8px; }
.movimientos { margin-top: 8px; }
.movimientos td { height: 15px; }
thead { display: table-header-group; }
tr { page-break-inside: avoid; }
.num { text-align: right; }
.center { text-align: center; }
.note { font-size: 7px; color: #555; margin: 7px 0; }
</style>
</head>
<body>
<table class="header"><tr>
<td style="width:14%"><img class="logo" src="{{ public_path('images/logo-laboratorio-urme.jpg') }}"></td>
<td class="title">KARDEX DE REACTIVOS / INSUMOS</td>
<td class="meta" style="width:25%">ÁREA: LABORATORIO<br>CLÍNICA URME<br>PERIODO: {{ $periodo }}<br>GENERADO: {{ now()->format('d/m/Y H:i') }}</td>
</tr></table>
<table class="info">
<tr><th style="width:27%">NOMBRE DEL REACTIVO</th><td>{{ $reactivo->nombre }}</td><th style="width:14%">CÓDIGO</th><td style="width:18%">{{ $reactivo->codigo ?: '-' }}</td></tr>
<tr><th>UNIDAD DE DESCARGO</th><td>{{ $reactivo->unidad }}</td><th>SALDO INICIAL</th><td class="num">{{ $saldo_inicial === null ? 'Sin registro' : number_format($saldo_inicial, 4) }}</td></tr>
</table>
@if (!empty($nota))<p class="note">{{ $nota }}</p>@endif
<table class="movimientos">
<colgroup><col style="width:4%"><col style="width:4%"><col style="width:4%"><col style="width:7%"><col style="width:7%"><col style="width:7%"><col style="width:10%"><col style="width:8%"><col style="width:8%"><col style="width:14%"><col style="width:27%"></colgroup>
<thead>
<tr><th colspan="3">FECHA</th><th rowspan="2">INGRESO</th><th rowspan="2">MARCA</th><th rowspan="2">LOTE</th><th rowspan="2">VENCIMIENTO</th><th rowspan="2">SALIDA<br>CALCULADA</th><th rowspan="2">SALDO</th><th rowspan="2">REGISTRADO POR</th><th rowspan="2">OBSERVACIONES / PRUEBAS</th></tr>
<tr><th>DÍA</th><th>MES</th><th>AÑO</th></tr>
</thead>
<tbody>
@forelse ($movimientos as $fila)
@php($fecha = \Carbon\Carbon::parse($fila['fecha']))
<tr>
<td class="center">{{ $fecha->format('d') }}</td><td class="center">{{ $fecha->format('m') }}</td><td class="center">{{ $fecha->format('y') }}</td>
<td class="num">{{ $fila['ingreso'] ? number_format($fila['ingreso'], 3) : '-' }}</td>
<td>{{ $fila['marca'] ?: '-' }}</td><td>{{ $fila['lote'] ?: '-' }}</td><td class="center">{{ $fila['vencimiento'] ? \Carbon\Carbon::parse($fila['vencimiento'])->format('d/m/Y') : '-' }}</td>
<td class="num">{{ $fila['salida'] ? number_format($fila['salida'], 4) : '-' }}</td><td class="num">{{ $fila['saldo'] === null ? '-' : number_format($fila['saldo'], 4) }}</td>
<td>{{ $fila['responsable'] ?: '-' }}</td><td>{{ $fila['observaciones'] ?: '-' }}</td>
</tr>
@empty
<tr><td colspan="11" class="center">Sin movimientos registrados en el periodo seleccionado.</td></tr>
@endforelse
</tbody>
</table>
<table class="info"><tr><th>TOTAL INGRESOS</th><td class="num">{{ $total_ingresos === null ? 'Sin registro' : number_format($total_ingresos, 4) }}</td><th>TOTAL SALIDAS</th><td class="num">{{ number_format($total_salidas, 4) }}</td><th>SALDO FINAL</th><td class="num">{{ $saldo_final === null ? 'Sin registro' : number_format($saldo_final, 4) }}</td></tr></table>
</body>
</html>
