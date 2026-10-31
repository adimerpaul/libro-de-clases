@php
    // 12345678-5 → 12.345.678-5
    $run = function (?string $v) {
        if (! $v) return '—';
        [$cuerpo, $dv] = array_pad(explode('-', $v), 2, '');
        return number_format((int) $cuerpo, 0, ',', '.').($dv !== '' ? "-{$dv}" : '');
    };
    $fecha = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d-m-Y') : '—';
    $estadoTxt = ['activa' => 'Activa', 'retirada' => 'Retirada', 'trasladada' => 'Trasladada'];
    $pagina = 0;
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Libro de Clases · {{ $curso->nombre }} {{ $curso->anio }}</title>
<style>
    @page { margin: 28px 34px 40px }
    * { box-sizing: border-box }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #173f60; margin: 0 }
    .salto { page-break-after: always }
    footer { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 8px; color: #6d8090 }
    footer .der { float: right }

    /* Portada */
    .portada { background: #c82130; color: #fff; height: 540px; padding: 60px 70px; position: relative }
    .portada .franja { position: absolute; left: 0; right: 0; bottom: 0; height: 120px; background: #991822 }
    .portada .gris { position: absolute; left: 0; right: 0; bottom: 120px; height: 40px; background: #e0e3e5 }
    .eyebrow { font-size: 11px; font-weight: bold; letter-spacing: 2px; color: #ffd9dd }
    .portada h1 { font-size: 54px; line-height: 1; margin: 10px 0 0; font-weight: bold }
    .portada .v { font-size: 22px; font-weight: bold; margin-top: 12px }
    .coverCard { margin-top: 34px; width: 430px; background: #fff; color: #17324a; padding: 12px 16px; border-radius: 5px }
    .coverLine { border-bottom: 1px solid #6b8294; padding: 7px 3px; font-size: 10px; text-transform: uppercase }
    .coverLine:last-child { border-bottom: 0 }

    /* Hojas */
    .titulo { font-family: "DejaVu Serif", serif; text-align: center; font-size: 17px; color: #155d96; text-transform: uppercase; margin: 0 0 10px }
    .encabezado { border-bottom: 2px solid #1f6fb2; padding-bottom: 5px; margin-bottom: 12px; font-size: 9px; color: #3d6e96 }
    .encabezado b { color: #0b2440; font-size: 11px }
    .encabezado .der { float: right }
    .bar { background: #1f6fb2; color: #fff; padding: 5px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; margin-top: 12px }
    table { width: 100%; border-collapse: collapse }
    .info td { border: 1px solid #aac2d5; padding: 6px }
    .info td.k { width: 38%; font-size: 8px; color: #3d6e96; text-transform: uppercase; font-weight: bold; background: #f4f8fb }
    .info td.v { font-family: "DejaVu Serif", serif; font-size: 11px }
    .tabla th, .tabla td { border: 1px solid #aac2d5; padding: 4px 5px }
    .tabla th { background: #e7f1f9; color: #275f90; font-size: 8px; text-transform: uppercase }
    .tabla td { font-size: 9px }
    .tabla tr.inactivo td { color: #9aabb8; text-decoration: line-through }
    .centro { text-align: center }
    .nowrap { white-space: nowrap }
    .punto { display: inline-block; width: 7px; height: 7px; border-radius: 4px; margin-right: 5px }
    .pie { font-size: 7px; font-weight: bold; color: #5a3fb0; border: 1px solid #ddd3fb; background: #f1edff; padding: 0 3px }
    .cols td.col { width: 50%; vertical-align: top; padding: 0 }
    .cols td.sep { width: 20px }

    /* Secciones */
    .resumen { margin-bottom: 6px }
    .resumen td { border-bottom: 1px dotted #aac2d5; padding: 3px 5px; font-size: 9px }
    .resumen td.k { color: #3d6e96; font-weight: bold; width: 22% }
    .resumen td.v { font-family: "DejaVu Serif", serif; width: 28% }
    .nota { font-size: 8px; color: #6d8090; margin: 4px 0 6px }
    .tabla th.izq { text-align: left }
    .tabla td.cel-rojo { color: #bd1f2d; font-weight: bold }
    .tabla td.cel-verde { color: #17703f }
    .tabla td.cel-ambar { color: #8a520c }
    .tabla td.cel-gris { color: #8597a6 }
    .tabla td.vacio { color: #8597a6; font-style: italic }
    .bar + .tabla { margin-top: 0 }

    /* Firmas */
    .firmas { margin-top: 70px }
    .firmas td { width: 33%; text-align: center; padding: 0 18px; font-size: 9px; vertical-align: top }
    .firmas .linea { border-bottom: 1px solid #173f60; height: 60px; margin-bottom: 5px }
    .firmas span { color: #6d8090; font-size: 8px; text-transform: uppercase }
    .verificacion { margin-top: 50px; border: 1px solid #aac2d5; background: #f4f8fb; padding: 10px 12px; font-size: 8px; color: #3d6e96 }
    .verificacion b { color: #0b2440; font-size: 9px }
    .verificacion .hash { font-family: "DejaVu Sans Mono", monospace; font-size: 10px; color: #0b2440 }
</style>
</head>
<body>

<footer>
    Libro de Clases 3.0 · {{ $curso->nombre }} · {{ $curso->anio }}
    · Folio {{ $respaldo->folio }} · Verificación {{ substr($firmas['hash'], 0, 16) }}
    <span class="der">Generado el {{ $generado }} por {{ $usuario }}</span>
</footer>

{{-- Portada --}}
<div class="portada salto">
    <div class="eyebrow">ECOSISTEMA 3.0</div>
    <h1>LIBRO DE<br>CLASES 3.0</h1>
    <div class="v">{{ $curso->nombre }} · {{ $curso->anio }}</div>
    <div class="coverCard">
        <div class="coverLine">Curso · {{ $curso->nombre }} · Año {{ $curso->anio }}</div>
        <div class="coverLine">Profesor(a) jefe · {{ $curso->profesorJefe?->name ?? 'Sin asignar' }}</div>
        <div class="coverLine">Matrícula · {{ $activas->count() }} estudiantes</div>
    </div>
    <div class="gris"></div>
    <div class="franja"></div>
</div>

{{-- Identificación del curso + asignaturas --}}
<div class="encabezado"><b>{{ $curso->nombre }} · {{ $curso->anio }}</b><span class="der">Hojas {{ ++$pagina }}–{{ ++$pagina }}</span></div>
<table class="cols">
    <tr>
        <td class="col">
            <div class="titulo">Identificación del curso</div>
            <table class="info">
                <tr><td class="k">Curso</td><td class="v">{{ $curso->nombre }}</td></tr>
                <tr><td class="k">Nivel</td><td class="v">{{ $curso->nivel }}</td></tr>
                <tr><td class="k">Año escolar</td><td class="v">{{ $curso->anio }}</td></tr>
                <tr><td class="k">Profesor(a) jefe</td><td class="v">{{ $curso->profesorJefe?->name ?? 'Sin asignar' }}</td></tr>
                <tr><td class="k">Correo jefatura</td><td class="v">{{ $curso->profesorJefe?->email ?? '—' }}</td></tr>
            </table>
            <div class="bar">Resumen de matrícula</div>
            <table class="info">
                <tr><td class="k">Matrícula vigente</td><td class="v">{{ $activas->count() }} estudiantes</td></tr>
                <tr><td class="k">Mujeres / hombres</td><td class="v">{{ $activas->where('estudiante.sexo', 'F')->count() }} / {{ $activas->where('estudiante.sexo', 'M')->count() }}</td></tr>
                <tr><td class="k">PIE</td><td class="v">{{ $activas->filter(fn ($m) => $m->estudiante?->pie)->count() }} estudiantes</td></tr>
                <tr><td class="k">Retirados / trasladados</td><td class="v">{{ $curso->matriculas->count() - $activas->count() }}</td></tr>
            </table>
        </td>
        <td class="sep"></td>
        <td class="col">
            <div class="titulo">Asignaturas y docentes</div>
            <table class="tabla">
                <thead><tr><th>Asignatura</th><th>Docente</th><th>Hrs.</th></tr></thead>
                <tbody>
                @forelse ($curso->asignaciones as $a)
                    <tr>
                        <td><span class="punto" style="background: {{ $a->asignatura?->color ?: '#1f6fb2' }}"></span>{{ $a->asignatura?->nombre }}</td>
                        <td>{{ $a->docente?->name ?? '—' }}</td>
                        <td class="centro">{{ $a->horas_semanales ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="centro">Sin asignación docente registrada</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>

{{-- Nómina: estudiantes a la izquierda, apoderados a la derecha (misma fila) --}}
@forelse ($tramos as $filas)
    <div style="page-break-before: always"></div>
    <div class="encabezado">
        <b>Nómina · N° {{ $filas->first()->numero_lista }}–{{ $filas->last()->numero_lista }}</b>
        <span class="der">Hojas {{ ++$pagina }}–{{ ++$pagina }}</span>
    </div>
    <table class="cols">
        <tr>
            <td class="col">
                <div class="titulo">Nómina del curso</div>
                <table class="tabla">
                    <thead><tr><th>N°</th><th>Estudiante</th><th>RUN</th><th>F. nac.</th></tr></thead>
                    <tbody>
                    @foreach ($filas as $m)
                        <tr class="{{ $m->estado !== 'activa' ? 'inactivo' : '' }}">
                            <td class="centro">{{ $m->numero_lista }}</td>
                            <td>{{ $m->estudiante?->nombre_completo }} @if ($m->estudiante?->pie)<span class="pie">PIE</span>@endif</td>
                            <td class="nowrap">{{ $run($m->estudiante?->run) }}</td>
                            <td class="nowrap">{{ $fecha($m->estudiante?->fecha_nacimiento) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </td>
            <td class="sep"></td>
            <td class="col">
                <div class="titulo">Apoderados y contacto</div>
                <table class="tabla">
                    <thead><tr><th>N°</th><th>Apoderado(a)</th><th>Teléfono</th><th>Estado</th></tr></thead>
                    <tbody>
                    @foreach ($filas as $m)
                        <tr class="{{ $m->estado !== 'activa' ? 'inactivo' : '' }}">
                            <td class="centro">{{ $m->numero_lista }}</td>
                            <td>{{ $m->estudiante?->apoderado_nombre ?: '—' }}</td>
                            <td class="nowrap">{{ $m->estudiante?->apoderado_telefono ?: '—' }}</td>
                            <td>{{ $estadoTxt[$m->estado] ?? $m->estado }}{{ $m->fecha_retiro ? ' '.$fecha($m->fecha_retiro) : '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
@empty
    <div style="page-break-before: always"></div>
    <div class="titulo">Nómina del curso</div>
    <p class="centro">El curso no tiene estudiantes matriculados.</p>
@endforelse

{{-- Secciones del libro (App\Support\LibroCurso): las mismas que muestra la vista --}}
@foreach ($secciones as $s)
    @php
        // En "pares" ambas tablas avanzan juntas (misma fila a la izquierda y a la derecha)
        $trozos = $s['modo'] === 'pares'
            ? collect($s['tablas'][0]['filas'])->keys()->chunk(20)
            : collect([collect($s['tablas'][0]['filas'])->keys()]);
    @endphp
    @foreach ($trozos as $n => $indices)
        <div style="page-break-before: always"></div>
        <div class="encabezado">
            <b>{{ $s['titulo'] }}{{ $trozos->count() > 1 ? ' · '.($n + 1).'/'.$trozos->count() : '' }}</b>
            <span class="der">{{ $curso->nombre }} · {{ $curso->anio }}</span>
        </div>
        @if ($n === 0)
            <div class="titulo">{{ $s['titulo'] }}</div>
            @if (! empty($s['resumen']))
                {{-- Dos pares por fila; la leyenda de notas (N1, N2…) uno por fila --}}
                <table class="resumen">
                    @foreach (collect($s['resumen'])->chunk(str_starts_with($s['clave'], 'notas-') ? 1 : 2) as $grupo)
                        <tr>@foreach ($grupo as $r)<td class="k">{{ $r[0] }}</td><td class="v">{{ $r[1] }}</td>@endforeach</tr>
                    @endforeach
                </table>
            @endif
            @if ($s['nota'])<p class="nota">{{ $s['nota'] }}</p>@endif
        @endif

        @if ($s['modo'] === 'pares')
            <table class="cols"><tr>
                @foreach ($s['tablas'] as $t => $tabla)
                    @if ($t > 0)<td class="sep"></td>@endif
                    <td class="col" style="width: {{ $t === 0 ? '58%' : '40%' }}">
                        <div class="bar">{{ $tabla['titulo'] }}</div>
                        @include('pdf.libro-tabla', ['tabla' => $tabla, 'indices' => $indices])
                    </td>
                @endforeach
            </tr></table>
        @else
            <div class="bar">{{ $s['tablas'][0]['titulo'] }}</div>
            @include('pdf.libro-tabla', ['tabla' => $s['tablas'][0], 'indices' => $indices])
        @endif
    @endforeach
@endforeach

{{-- Cierre: firmas y código de verificación --}}
<div style="page-break-before: always"></div>
<div class="encabezado"><b>Cierre, firmas y verificación</b><span class="der">Folio {{ $respaldo->folio }}</span></div>
<div class="titulo">Cierre del libro de clases</div>
<p class="nota" style="font-size: 9px">
    Este documento reúne la identificación del curso {{ $curso->nombre }} {{ $curso->anio }}, la nómina, el registro de asistencia,
    el leccionario, las calificaciones, el Programa de Integración Escolar, la formación socioemocional y las entrevistas y
    comunicaciones con apoderados registradas en {{ $establecimiento['nombre'] }} (RBD {{ $establecimiento['rbd'] }}).
    Generado el {{ $generado }} por {{ $usuario }}. Es el respaldo oficial del libro y se firma a continuación.
</p>
<table class="firmas"><tr>
    @foreach ($firmas['firmantes'] as $f)
        <td>
            <div class="linea"></div>
            <b>{{ $f['nombre'] ?? '' }}</b><br>
            <span>{{ $f['cargo'] }}</span><br>
            <span>Firma y timbre</span>
        </td>
    @endforeach
</tr></table>
<div class="verificacion">
    <b>Código de verificación (SHA-256 del contenido)</b><br>
    <span class="hash">{{ $firmas['hash'] }}</span><br>
    Folio {{ $respaldo->folio }} · registrado en el sistema. Si el libro se modifica después de esta fecha, el código del contenido
    cambia: este PDF corresponde exactamente al contenido del {{ $generado }}.
</div>

</body>
</html>
