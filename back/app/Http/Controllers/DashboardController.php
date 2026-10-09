<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Internacion;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Solicitude;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\StockLote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->can('Ver Dashboard')) {
            abort(403, 'No tiene permiso para ver el panel general');
        }

        [$periodo, $desde, $hasta, $granularidad] = $this->rango($request);

        $inicio = $desde->copy()->startOfDay();
        $fin = $hasta->copy()->endOfDay();
        $hoy = Carbon::today();

        $verVentas = $user->can('Ver Ventas');
        $verCompras = $user->can('Ver Compras');
        $verProductos = $user->can('Ver Productos');
        $verPacientes = $user->can('Ver Pacientes');
        $verLaboratorio = $user->can('Ver Solicitudes Laboratorio');
        $verInternaciones = $user->can('Ver Internaciones');

        return response()->json([
            'rango' => [
                'periodo' => $periodo,
                'granularidad' => $granularidad,
                'dias' => (int) $desde->diffInDays($hasta) + 1,
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hasta->format('Y-m-d'),
            ],
            'permisos' => [
                'ventas' => $verVentas,
                'compras' => $verCompras,
                'productos' => $verProductos,
                'pacientes' => $verPacientes,
                'laboratorio' => $verLaboratorio,
                'internaciones' => $verInternaciones,
            ],
            'resumen' => $this->resumen($inicio, $fin, $hoy, $verVentas, $verCompras, $verProductos, $verPacientes, $verLaboratorio, $verInternaciones),
            'serie' => $verVentas || $verCompras ? $this->serie($inicio, $fin, $granularidad, $verVentas, $verCompras) : [],
            'tipo_pago' => $verVentas ? $this->tipoPago($inicio, $fin) : [],
            'top_productos' => $verVentas ? $this->topProductos($inicio, $fin) : [],
            'por_tipo_producto' => $verVentas ? $this->porTipoProducto($inicio, $fin) : [],
            'top_vendedores' => $verVentas ? $this->topVendedores($inicio, $fin) : [],
            'top_profesionales' => $verVentas ? $this->topProfesionales($inicio, $fin) : [],
            'ventas_por_hora' => $verVentas ? $this->ventasPorHora($inicio, $fin) : [],
            'solicitudes_estado' => $verLaboratorio ? $this->solicitudesPorEstado($inicio, $fin) : [],
            'vencimientos' => $verProductos ? $this->vencimientos($hoy) : [],
            'stock_critico' => $verProductos ? $this->stockCritico() : [],
        ]);
    }

    // ── Rango del período ─────────────────────────────────────────

    /**
     * Resuelve el período pedido (hoy, ayer, semana, mes, anio o rango libre)
     * a fechas de calendario y a la granularidad con la que conviene dibujar
     * la serie: un solo día se abre por hora y más de un trimestre se agrupa
     * por mes para no apretar cientos de puntos en el eje.
     *
     * @return array{0:string,1:Carbon,2:Carbon,3:string}
     */
    private function rango(Request $request): array
    {
        $hoy = Carbon::today();
        $periodo = mb_strtolower((string) $request->input('periodo', 'semana'));

        switch ($periodo) {
            case 'hoy':
                $desde = $hoy->copy();
                $hasta = $hoy->copy();
                break;
            case 'ayer':
                $desde = $hoy->copy()->subDay();
                $hasta = $desde->copy();
                break;
            case 'mes':
                $desde = $hoy->copy()->startOfMonth();
                $hasta = $hoy->copy();
                break;
            case 'anio':
                $desde = $hoy->copy()->startOfYear();
                $hasta = $hoy->copy();
                break;
            case 'rango':
                $desde = $this->fecha($request->input('desde')) ?? $hoy->copy()->startOfWeek();
                $hasta = $this->fecha($request->input('hasta')) ?? $hoy->copy();
                if ($desde->gt($hasta)) {
                    [$desde, $hasta] = [$hasta, $desde];
                }
                // Techo defensivo: cinco años bastan para cualquier comparativo
                if ((int) $desde->diffInDays($hasta) > 1826) {
                    $desde = $hasta->copy()->subDays(1826);
                }
                break;
            default:
                $periodo = 'semana';
                $desde = $hoy->copy()->startOfWeek();
                $hasta = $hoy->copy();
        }

        $dias = (int) $desde->diffInDays($hasta) + 1;
        $granularidad = $dias <= 1 ? 'hora' : ($dias <= 92 ? 'dia' : 'mes');

        return [$periodo, $desde, $hasta, $granularidad];
    }

    private function fecha($valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        try {
            return Carbon::parse($valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Resumen compacto ──────────────────────────────────────────

    private function resumen(Carbon $inicio, Carbon $fin, Carbon $hoy, bool $verVentas, bool $verCompras, bool $verProductos, bool $verPacientes, bool $verLaboratorio, bool $verInternaciones): array
    {
        $resumen = [];

        if ($verVentas) {
            // Los gastos de caja no son ventas: quedan fuera de todos los indicadores.
            $ventas = Venta::where('tipo_movimiento', 'INGRESO')->where('estado', 'ACTIVO')->whereBetween('fecha_hora', [$inicio, $fin]);

            $total = (float) (clone $ventas)->sum('total');
            $cantidad = (clone $ventas)->count();

            $resumen['ventas_total'] = $total;
            $resumen['ventas_cantidad'] = $cantidad;
            $resumen['ticket_promedio'] = $cantidad > 0 ? round($total / $cantidad, 2) : 0;
            $resumen['ventas_pendientes'] = (float) Venta::where('tipo_movimiento', 'INGRESO')->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->sum('total');
            $resumen['ventas_pendientes_cantidad'] = Venta::where('tipo_movimiento', 'INGRESO')->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->count();
        }

        if ($verCompras) {
            $resumen['compras_total'] = (float) Compra::where('estado', 'ACTIVO')
                ->whereBetween('fecha_hora', [$inicio, $fin])
                ->sum('total');
        }

        if ($verProductos) {
            $resumen['productos'] = Producto::count();
            $resumen['stock_valorizado'] = $this->stockValorizado();
            $resumen['por_vencer'] = $this->stockQuery()
                ->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '>=', $hoy)
                ->whereDate('fecha_vencimiento', '<=', $hoy->copy()->addDays(30))
                ->count();
            $resumen['vencidos'] = $this->stockQuery()
                ->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '<', $hoy)
                ->count();
        }

        if ($verPacientes) {
            $resumen['pacientes'] = Paciente::count();
            $resumen['pacientes_nuevos'] = Paciente::whereBetween('created_at', [$inicio, $fin])->count();
        }

        if ($verLaboratorio) {
            $resumen['solicitudes'] = Solicitude::whereBetween('fecha_solicitud', [$inicio, $fin])->count();
            $resumen['solicitudes_pendientes'] = Solicitude::whereNotIn('estado', ['FINALIZADO', 'ANULADO'])->count();
        }

        if ($verInternaciones) {
            $resumen['internados'] = Internacion::whereNull('fecha_alta')->count();
        }

        return $resumen;
    }

    // ── Serie temporal del período ────────────────────────────────

    private function serie(Carbon $inicio, Carbon $fin, string $granularidad, bool $verVentas, bool $verCompras): array
    {
        [$expresion, $formato] = match ($granularidad) {
            'hora' => ["DATE_FORMAT(fecha_hora, '%Y-%m-%d %H')", 'Y-m-d H'],
            'mes' => ["DATE_FORMAT(fecha_hora, '%Y-%m')", 'Y-m'],
            default => ['DATE(fecha_hora)', 'Y-m-d'],
        };

        $ventas = $verVentas
            ? Venta::where('tipo_movimiento', 'INGRESO')
                ->where('estado', 'ACTIVO')
                ->whereBetween('fecha_hora', [$inicio, $fin])
                ->selectRaw("$expresion as clave, SUM(total) as total, COUNT(*) as cantidad")
                ->groupBy('clave')
                ->get()
                ->keyBy(fn ($fila) => (string) $fila->clave)
            : collect();

        $compras = $verCompras
            ? Compra::where('estado', 'ACTIVO')
                ->whereBetween('fecha_hora', [$inicio, $fin])
                ->selectRaw("$expresion as clave, SUM(total) as total")
                ->groupBy('clave')
                ->get()
                ->keyBy(fn ($fila) => (string) $fila->clave)
            : collect();

        $cursor = $granularidad === 'mes' ? $inicio->copy()->startOfMonth() : $inicio->copy();
        $serie = [];

        while ($cursor->lte($fin)) {
            $clave = $cursor->format($formato);
            $serie[] = [
                'clave' => $clave,
                'etiqueta' => $this->etiqueta($cursor, $granularidad),
                'detalle' => $this->detalleEtiqueta($cursor, $granularidad),
                'ventas' => (float) ($ventas[$clave]->total ?? 0),
                'cantidad' => (int) ($ventas[$clave]->cantidad ?? 0),
                'compras' => (float) ($compras[$clave]->total ?? 0),
            ];

            match ($granularidad) {
                'hora' => $cursor->addHour(),
                'mes' => $cursor->addMonth(),
                default => $cursor->addDay(),
            };
        }

        return $serie;
    }

    private function etiqueta(Carbon $fecha, string $granularidad): string
    {
        return match ($granularidad) {
            'hora' => $fecha->format('H:i'),
            'mes' => mb_strtoupper($fecha->locale('es')->isoFormat('MMM YY')),
            default => $fecha->format('d/m'),
        };
    }

    private function detalleEtiqueta(Carbon $fecha, string $granularidad): string
    {
        return match ($granularidad) {
            'hora' => $fecha->format('d/m/Y H:i'),
            'mes' => mb_strtoupper($fecha->locale('es')->isoFormat('MMMM [de] YYYY')),
            default => $fecha->format('d/m/Y'),
        };
    }

    private function ventasPorHora(Carbon $inicio, Carbon $fin): array
    {
        $filas = Venta::where('tipo_movimiento', 'INGRESO')
            ->where('estado', 'ACTIVO')
            ->whereBetween('fecha_hora', [$inicio, $fin])
            ->selectRaw('HOUR(fecha_hora) as hora, COUNT(*) as cantidad, SUM(total) as total')
            ->groupBy('hora')
            ->get()
            ->keyBy(fn ($fila) => (int) $fila->hora);

        $serie = [];
        for ($hora = 0; $hora < 24; $hora++) {
            $serie[] = [
                'hora' => sprintf('%02d:00', $hora),
                'cantidad' => (int) ($filas[$hora]->cantidad ?? 0),
                'total' => (float) ($filas[$hora]->total ?? 0),
            ];
        }

        return $serie;
    }

    // ── Distribuciones ────────────────────────────────────────────

    private function tipoPago(Carbon $inicio, Carbon $fin): array
    {
        $filas = Venta::where('tipo_movimiento', 'INGRESO')
            ->where('estado', 'ACTIVO')
            ->whereBetween('fecha_hora', [$inicio, $fin])
            ->selectRaw('COALESCE(tipo_pago, ?) as tipo_pago, SUM(total) as total, COUNT(*) as cantidad', ['SIN DEFINIR'])
            ->groupBy('tipo_pago')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => [
                'tipo_pago' => (string) $fila->tipo_pago,
                'total' => (float) $fila->total,
                'cantidad' => (int) $fila->cantidad,
            ]);

        return $this->plegarEnOtros($filas, 'tipo_pago');
    }

    private function topProductos(Carbon $inicio, Carbon $fin): array
    {
        return VentaDetalle::query()
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereNull('ventas.deleted_at')
            ->where('ventas.estado', 'ACTIVO')
            ->whereBetween('ventas.fecha_hora', [$inicio, $fin])
            ->selectRaw('venta_detalles.nombre as nombre, SUM(venta_detalles.cantidad) as cantidad, SUM(venta_detalles.total) as total')
            ->groupBy('venta_detalles.nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($fila) => [
                'nombre' => (string) $fila->nombre,
                'cantidad' => (float) $fila->cantidad,
                'total' => (float) $fila->total,
            ])
            ->all();
    }

    private function porTipoProducto(Carbon $inicio, Carbon $fin): array
    {
        $filas = VentaDetalle::query()
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->leftJoin('tipo_productos', 'tipo_productos.id', '=', 'productos.tipo_producto_id')
            ->whereNull('ventas.deleted_at')
            ->where('ventas.estado', 'ACTIVO')
            ->whereBetween('ventas.fecha_hora', [$inicio, $fin])
            ->selectRaw('COALESCE(tipo_productos.nombre, ?) as tipo, SUM(venta_detalles.total) as total', ['SIN CATEGORÍA'])
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => [
                'tipo' => (string) $fila->tipo,
                'total' => (float) $fila->total,
            ]);

        return $this->plegarEnOtros($filas, 'tipo');
    }

    /**
     * Las tortas se pintan con la paleta categórica en orden por tamaño, así que
     * cualquier par de porciones puede quedar contiguo. Solo cuatro ranuras
     * superan la validación con todos los pares, de modo que el excedente se
     * pliega en OTROS en lugar de reciclar colores.
     */
    private function plegarEnOtros($filas, string $campo, int $limite = 3): array
    {
        if ($filas->count() <= $limite + 1) {
            return $filas->values()->all();
        }

        $resto = $filas->slice($limite);
        $otros = [$campo => 'OTROS', 'total' => round((float) $resto->sum('total'), 2)];

        if (array_key_exists('cantidad', $filas->first())) {
            $otros['cantidad'] = (int) $resto->sum('cantidad');
        }

        return $filas->take($limite)->push($otros)->values()->all();
    }

    private function topVendedores(Carbon $inicio, Carbon $fin): array
    {
        return Venta::query()
            ->join('users', 'users.id', '=', 'ventas.user_id')
            ->where('ventas.tipo_movimiento', 'INGRESO')
            ->where('ventas.estado', 'ACTIVO')
            ->whereBetween('ventas.fecha_hora', [$inicio, $fin])
            ->selectRaw('users.name as nombre, SUM(ventas.total) as total, COUNT(*) as cantidad')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($fila) => [
                'nombre' => (string) $fila->nombre,
                'total' => (float) $fila->total,
                'cantidad' => (int) $fila->cantidad,
            ])
            ->all();
    }

    /**
     * Profesionales (doctores) que derivaron las ventas del período. Las ventas
     * sin doctor asignado no forman parte del ranking.
     */
    private function topProfesionales(Carbon $inicio, Carbon $fin): array
    {
        return Venta::query()
            ->join('doctores', 'doctores.id', '=', 'ventas.doctor_id')
            ->whereNull('doctores.deleted_at')
            ->where('ventas.tipo_movimiento', 'INGRESO')
            ->where('ventas.estado', 'ACTIVO')
            ->whereBetween('ventas.fecha_hora', [$inicio, $fin])
            ->selectRaw('doctores.nombre as nombre, SUM(ventas.total) as total, COUNT(*) as cantidad')
            ->groupBy('doctores.id', 'doctores.nombre')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($fila) => [
                'nombre' => (string) $fila->nombre,
                'total' => (float) $fila->total,
                'cantidad' => (int) $fila->cantidad,
            ])
            ->all();
    }

    private function solicitudesPorEstado(Carbon $inicio, Carbon $fin): array
    {
        return Solicitude::query()
            ->whereBetween('fecha_solicitud', [$inicio, $fin])
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->orderByDesc('cantidad')
            ->get()
            ->map(fn ($fila) => [
                'estado' => (string) $fila->estado,
                'cantidad' => (int) $fila->cantidad,
            ])
            ->all();
    }

    // ── Inventario ────────────────────────────────────────────────

    private function vencimientos(Carbon $hoy): array
    {
        $tramos = [
            ['clave' => 'VENCIDOS', 'etiqueta' => 'Vencidos', 'desde' => null, 'hasta' => -1],
            ['clave' => 'D30', 'etiqueta' => '≤ 30 días', 'desde' => 0, 'hasta' => 30],
            ['clave' => 'D90', 'etiqueta' => '31 a 90 días', 'desde' => 31, 'hasta' => 90],
            ['clave' => 'SEGURO', 'etiqueta' => '> 90 días', 'desde' => 91, 'hasta' => null],
        ];

        $resultado = [];
        foreach ($tramos as $tramo) {
            $query = $this->stockQuery()->whereNotNull('fecha_vencimiento');

            if ($tramo['desde'] !== null) {
                $query->whereDate('fecha_vencimiento', '>=', $hoy->copy()->addDays($tramo['desde']));
            }
            if ($tramo['hasta'] !== null) {
                $query->whereDate('fecha_vencimiento', '<=', $hoy->copy()->addDays($tramo['hasta']));
            }

            $resultado[] = [
                'clave' => $tramo['clave'],
                'etiqueta' => $tramo['etiqueta'],
                'cantidad' => $query->count(),
            ];
        }

        return $resultado;
    }

    private function stockCritico(): array
    {
        $existencia = $this->existenciaSql();

        return CompraDetalle::query()
            ->join('productos', 'productos.id', '=', 'compra_detalles.producto_id')
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->whereNull('compras.deleted_at')
            ->where('compras.estado', 'ACTIVO')
            ->whereNull('compra_detalles.deleted_at')
            ->selectRaw("productos.id as producto_id, productos.nombre as nombre, productos.codigo as codigo, SUM($existencia) as existencia")
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->havingRaw('existencia > 0 AND existencia <= 10')
            ->orderBy('existencia')
            ->limit(8)
            ->get()
            ->map(fn ($fila) => [
                'producto_id' => (int) $fila->producto_id,
                'nombre' => (string) $fila->nombre,
                'codigo' => (string) $fila->codigo,
                'existencia' => (float) $fila->existencia,
            ])
            ->all();
    }

    private function stockValorizado(): float
    {
        $existencia = $this->existenciaSql();

        $total = CompraDetalle::query()
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->whereNull('compras.deleted_at')
            ->where('compras.estado', 'ACTIVO')
            ->whereNull('compra_detalles.deleted_at')
            ->selectRaw("COALESCE(SUM($existencia * COALESCE(compra_detalles.precio_venta, compra_detalles.precio, 0)), 0) as total")
            ->value('total');

        return round((float) $total, 2);
    }

    /**
     * Detalles de compra que todavía tienen saldo disponible en almacén.
     */
    private function stockQuery(): Builder
    {
        return CompraDetalle::query()
            ->whereHas('compra', fn (Builder $compra) => $compra->where('estado', 'ACTIVO'))
            ->whereRaw(StockLote::conSaldoSql());
    }

    /**
     * Expresión SQL con el saldo restante de un compra_detalles (nunca negativo).
     */
    private function existenciaSql(): string
    {
        return StockLote::existenciaSql();
    }
}
