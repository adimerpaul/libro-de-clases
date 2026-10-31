{{-- Tabla de una sección del libro (App\Support\LibroCurso). $indices: filas a imprimir. --}}
<table class="tabla">
    <thead>
        <tr>@foreach ($tabla['columnas'] as $c)<th class="{{ $c[1] === 'l' ? 'izq' : '' }}">{{ $c[0] }}</th>@endforeach</tr>
    </thead>
    <tbody>
    @forelse ($indices as $i)
        <tr>
            @foreach ($tabla['filas'][$i] ?? [] as $j => $celda)
                <td class="{{ ($tabla['columnas'][$j][1] ?? 'c') === 'l' ? '' : 'centro' }} {{ is_array($celda) && ! empty($celda['c']) ? 'cel-'.$celda['c'] : '' }}">{{ is_array($celda) ? $celda['t'] : $celda }}</td>
            @endforeach
        </tr>
    @empty
        <tr><td colspan="{{ count($tabla['columnas']) }}" class="centro vacio">Sin registros</td></tr>
    @endforelse
    </tbody>
</table>
