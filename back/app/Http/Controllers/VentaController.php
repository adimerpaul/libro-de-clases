<?php

namespace App\Http\Controllers;

use App\Exports\VentasExport;
use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\StockLote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Ventas');

        $fechaInicio = $request->input('fecha_inicio', '');
        $fechaFin = $request->input('fecha_fin', '');
        $horaInicio = $request->input('hora_inicio', '');
        $horaFin = $request->input('hora_fin', '');
        $pacienteId = $request->input('paciente_id', '');
        $userId = $request->input('user_id', '');
        $estado = $request->input('estado', '');
        $tipoMovimiento = $request->input('tipo_movimiento', '');
        $perPage = (int) $request->input('per_page', 15);

        // Los ítems de cada venta solo viajan a quien puede ver el detalle;
        // sin ese permiso el listado se queda en la cantidad de ítems.
        $verDetalle = $request->user()->can('Ver Detalle Ventas');

        $relaciones = [
            'paciente:id,nombre_completo,ci',
            'doctor:id,nombre',
            'seguro:id,nombre',
            'user:id,name',
            'cobradoPor:id,name',
        ];
        if ($verDetalle) {
            $relaciones[] = 'detalles:id,venta_id,nombre,lote,precio,precio_original,cantidad,total,total_original';
        }

        $query = Venta::with($relaciones)
            ->withCount('detalles')
            ->orderByDesc('fecha_hora');

        $this->applyFiltros($query, $fechaInicio, $fechaFin, $pacienteId, $userId, $estado, $horaInicio, $horaFin, $tipoMovimiento);

        // El resumen ignora el filtro de estado y el de tipo: sus tarjetas
        // separan ingresos de egresos por su cuenta.
        $resumenQuery = Venta::query();
        $this->applyFiltros($resumenQuery, $fechaInicio, $fechaFin, $pacienteId, $userId, '', $horaInicio, $horaFin);

        // Vendedores del rango: se arma sin el filtro de usuario para que el
        // selector siga ofreciendo a todos los que vendieron ese día.
        $usuariosQuery = Venta::query();
        $this->applyFiltros($usuariosQuery, $fechaInicio, $fechaFin, $pacienteId, '', $estado, $horaInicio, $horaFin, $tipoMovimiento);

        // Pantalla de Farmacia: solo las ventas compuestas únicamente por productos de farmacia.
        if ($request->boolean('solo_farmacia')) {
            $this->soloFarmacia($query);
            $this->soloFarmacia($resumenQuery);
            $this->soloFarmacia($usuariosQuery);
        }

        // Los montos acumulados de caja solo se envían a quien tiene el permiso.
        $verMontos = $request->user()->can('Ver Montos Caja');

        return response()->json([
            'ver_montos' => $verMontos,
            'resumen' => $verMontos ? [
                'total_ventas' => (clone $resumenQuery)->where('tipo_movimiento', 'INGRESO')->where(function ($query) {
                    $query->where('estado', 'ACTIVO')
                        ->orWhere(fn ($pendiente) => $pendiente->where('estado', 'PENDIENTE')->whereNotNull('fecha_hora_cobro'));
                })->sum('total'),
                'total_egresos' => (clone $resumenQuery)->where('tipo_movimiento', 'EGRESO')->where('estado', 'ACTIVO')->sum('total'),
                'total_pendientes' => (clone $resumenQuery)->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->sum('total'),
                'total_anuladas' => (clone $resumenQuery)->where('estado', 'ANULADO')->sum('total'),
                'cantidad' => (clone $resumenQuery)->count(),
                'cantidad_egresos' => (clone $resumenQuery)->where('tipo_movimiento', 'EGRESO')->where('estado', 'ACTIVO')->count(),
                'cantidad_pendientes' => (clone $resumenQuery)->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->count(),
            ] : null,
            'usuarios' => $this->usuariosVendedores($usuariosQuery),
            'ventas' => $query->paginate($perPage),
        ]);
    }

    /** Excel del historial con los mismos filtros de la pantalla (sin paginar). */
    public function exportExcel(Request $request)
    {
        $this->req($request, 'Ver Ventas');

        return Excel::download(
            new VentasExport($this->ventasExport($request), $request->user()->can('Ver Montos Caja')),
            'ventas_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /** PDF del historial con los mismos filtros de la pantalla: sirve para ver e imprimir. */
    public function exportPdf(Request $request)
    {
        $this->req($request, 'Ver Ventas');
        ini_set('memory_limit', '1024M');

        $pdf = Pdf::loadView('reportes.ventas', [
            'ventas' => $this->ventasExport($request),
            'verMontos' => $request->user()->can('Ver Montos Caja'),
            'rango' => $this->rangoTexto($request),
            'soloFarmacia' => $request->boolean('solo_farmacia'),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('ventas_'.now()->format('Ymd_His').'.pdf');
    }

    /** Todas las ventas que cumplen los filtros del historial, en orden cronológico. */
    private function ventasExport(Request $request)
    {
        $query = Venta::with([
            'paciente:id,nombre_completo,ci',
            'doctor:id,nombre',
            'seguro:id,nombre',
            'user:id,name',
            'detalles:id,venta_id,nombre',
        ])->orderBy('fecha_hora');

        $this->applyFiltros(
            $query,
            $request->input('fecha_inicio', ''),
            $request->input('fecha_fin', ''),
            $request->input('paciente_id', ''),
            $request->input('user_id', ''),
            $request->input('estado', ''),
            $request->input('hora_inicio', ''),
            $request->input('hora_fin', ''),
            $request->input('tipo_movimiento', '')
        );

        if ($request->boolean('solo_farmacia')) {
            $this->soloFarmacia($query);
        }

        return $query->get();
    }

    private function rangoTexto(Request $request): string
    {
        $desde = $request->input('fecha_inicio')
            ? $request->input('fecha_inicio').' '.($request->input('hora_inicio') ?: '00:00')
            : 'la primera venta';
        $hasta = $request->input('fecha_fin')
            ? $request->input('fecha_fin').' '.($request->input('hora_fin') ?: '23:59')
            : 'la última venta';

        return "Desde {$desde} hasta {$hasta}";
    }

    /**
     * Usuarios que registraron alguna venta dentro del rango filtrado, con la
     * cantidad de ventas de cada uno, para el selector del historial.
     */
    private function usuariosVendedores($query): array
    {
        return $query
            ->join('users', 'users.id', '=', 'ventas.user_id')
            ->selectRaw('users.id as id, users.name as name, COUNT(*) as cantidad')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($fila) => [
                'id' => (int) $fila->id,
                'name' => (string) $fila->name,
                'cantidad' => (int) $fila->cantidad,
            ])
            ->all();
    }

    /** Detalle completo de una venta: alimenta el modal de detalle y la reimpresión. */
    public function show(Request $request, $id)
    {
        $this->req($request, 'Ver Detalle Ventas');
        $venta = Venta::with(['paciente:id,nombre_completo,ci', 'doctor:id,nombre', 'seguro:id,nombre', 'user:id,name', 'cobradoPor:id,name', 'detalles.producto:id,nombre,codigo'])
            ->findOrFail($id);

        return response()->json($venta);
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Ventas');

        // Con la caja del día ya cerrada, este usuario no registra más ventas.
        if (CierreCajaController::cierreDelDia($request->user()->id, now()->toDateString())) {
            abort(422, 'Su caja de hoy ya fue cerrada: no puede registrar más ventas hasta mañana');
        }

        $request->validate([
            'paciente_id' => 'nullable|exists:pacientes,id',
            'doctor_id' => 'nullable|exists:doctores,id',
            'seguro_id' => 'nullable|exists:seguros,id',
            'cliente' => 'nullable|string|max:255',
            'tipo_pago' => 'nullable|in:EFECTIVO,QR,MIXTO',
            'comentario' => 'nullable|string|max:500',
            'pago' => 'nullable|numeric|min:0',
            'monto_efectivo' => 'nullable|numeric|min:0',
            'monto_qr' => 'nullable|numeric|min:0',
            //            'estado' => 'nullable|in:ACTIVO,PENDIENTE',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'nullable|exists:productos,id',
            'detalles.*.compra_detalle_id' => 'nullable|exists:compra_detalles,id',
            'detalles.*.lote' => 'nullable|string|max:255',
            'detalles.*.nombre' => 'required_without:detalles.*.producto_id|nullable|string|max:255',
            'detalles.*.precio' => 'required|numeric|min:0',
            'detalles.*.cantidad' => 'required|numeric|min:0.0001',
        ]);

        $estado = $request->estado === 'PENDIENTE' ? 'PENDIENTE' : 'ACTIVO';

        $venta = DB::transaction(function () use ($request, $estado) {
            $venta = Venta::create([
                'user_id' => $request->user()->id,
                'tipo_movimiento' => 'INGRESO',
                'paciente_id' => $request->paciente_id ?: null,
                'doctor_id' => $request->doctor_id ?: null,
                'seguro_id' => $request->seguro_id ?: null,
                'cliente' => $request->cliente ?: null,
                // La fecha la pone el servidor (zona America/La_Paz): el front no la envía.
                'fecha_hora' => now(),
                'tipo_pago' => $request->tipo_pago ? mb_strtoupper($request->tipo_pago) : 'EFECTIVO',
                'comentario' => $request->comentario ?: null,
                'estado' => $estado,
                'total' => 0,
                'pago' => 0,
                'cambio' => 0,
            ]);

            $total = 0;
            $totalOriginal = 0;
            foreach ($request->detalles as $item) {
                $producto = ! empty($item['producto_id'])
                    ? Producto::with('tipoProducto:id,nombre')->find($item['producto_id'])
                    : null;
                $precio = (float) $item['precio'];
                $cantidad = (float) $item['cantidad'];

                // El precio de lista sale del producto, nunca de lo que mandó el
                // navegador: es la referencia de cuánto debería haberse cobrado.
                // Los ítems sueltos (sin producto) no tienen lista: su referencia
                // es el precio con el que se registraron.
                $precioOriginal = $producto ? (float) $producto->precio : $precio;

                $loteCompra = null;
                $requiereLote = $producto
                    && mb_strtoupper($producto->tipoProducto?->nombre ?? '') === 'FARMACIA';
                if ($requiereLote) {
                    if (empty($item['compra_detalle_id']) || empty($item['lote'])) {
                        abort(422, "Debe seleccionar un lote para {$producto->nombre}");
                    }
                    $loteCompra = CompraDetalle::with('compra:id,estado')
                        ->lockForUpdate()
                        ->findOrFail($item['compra_detalle_id']);

                    if ((int) $loteCompra->producto_id !== (int) $producto->id
                        || $loteCompra->compra?->estado !== 'ACTIVO'
                        || ! $loteCompra->lote) {
                        abort(422, "El lote seleccionado para {$producto->nombre} no es válido");
                    }

                    // Descuenta ventas vigentes y bajas de inventario del lote.
                    $disponible = StockLote::disponibleDe($loteCompra);

                    if ($cantidad > $disponible) {
                        abort(422, "Stock insuficiente en el lote {$loteCompra->lote} de {$producto->nombre}. Disponible: {$disponible}");
                    }
                }

                $lineaTotal = round($precio * $cantidad, 2);
                $lineaTotalOriginal = round($precioOriginal * $cantidad, 2);
                $total += $lineaTotal;
                $totalOriginal += $lineaTotalOriginal;

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto?->id,
                    'compra_detalle_id' => $loteCompra?->id,
                    'nombre' => mb_strtoupper($item['nombre'] ?? $producto?->nombre ?? ''),
                    'lote' => $loteCompra?->lote,
                    'fecha_vencimiento' => $loteCompra?->fecha_vencimiento,
                    'precio' => $precio,
                    'precio_original' => $precioOriginal,
                    'cantidad' => $cantidad,
                    'total' => $lineaTotal,
                    'total_original' => $lineaTotalOriginal,
                ]);
            }

            if ($estado === 'PENDIENTE') {
                $venta->update(['total' => $total, 'total_original' => $totalOriginal]);

                return $venta;
            }

            $venta->update([
                'total' => $total,
                'total_original' => $totalOriginal,
                ...$this->montosPago($request, $total),
            ]);

            return $venta;
        });

        return response()->json(
            $venta->load(['paciente:id,nombre_completo,ci', 'doctor:id,nombre', 'seguro:id,nombre', 'user:id,name', 'detalles.producto:id,nombre,codigo']),
            201
        );
    }

    /**
     * Gasto de caja: dinero que sale (un refresco, el periódico, un taxi).
     * Se guarda como un movimiento de tipo EGRESO con un único ítem que lleva
     * la descripción, para que salga en el historial y en el cierre de caja.
     */
    public function gasto(Request $request)
    {
        $this->req($request, 'Crear Ventas');

        // Con la caja del día ya cerrada tampoco se registran salidas de dinero.
        if (CierreCajaController::cierreDelDia($request->user()->id, now()->toDateString())) {
            abort(422, 'Su caja de hoy ya fue cerrada: no puede registrar más movimientos hasta mañana');
        }

        $request->validate([
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'tipo_pago' => 'nullable|string|max:50',
            'comentario' => 'nullable|string|max:500',
        ]);

        $monto = round((float) $request->monto, 2);
        $descripcion = mb_strtoupper(trim($request->descripcion));

        $venta = DB::transaction(function () use ($request, $monto, $descripcion) {
            $venta = Venta::create([
                'user_id' => $request->user()->id,
                'tipo_movimiento' => 'EGRESO',
                // La fecha la pone el servidor (zona America/La_Paz), igual que en las ventas.
                'fecha_hora' => now(),
                'tipo_pago' => $request->tipo_pago ? mb_strtoupper($request->tipo_pago) : 'EFECTIVO',
                'comentario' => $request->comentario ?: null,
                'estado' => 'ACTIVO',
                'total' => $monto,
                'total_original' => $monto,
                // Un gasto no da cambio: sale exactamente lo que se gastó, en efectivo.
                'pago' => $monto,
                'cambio' => 0,
                'monto_efectivo' => $monto,
            ]);

            VentaDetalle::create([
                'venta_id' => $venta->id,
                'nombre' => $descripcion,
                'precio' => $monto,
                'precio_original' => $monto,
                'cantidad' => 1,
                'total' => $monto,
                'total_original' => $monto,
            ]);

            return $venta;
        });

        // Mismas relaciones que show(): la venta recién creada se imprime con este JSON.
        return response()->json($venta->load(['paciente:id,nombre_completo,ci', 'doctor:id,nombre', 'seguro:id,nombre', 'user:id,name', 'detalles.producto:id,nombre,codigo']), 201);
    }

    public function completar(Request $request, $id)
    {
        $this->req($request, 'Crear Ventas');

        // Cobrar una pendiente también mueve dinero: se bloquea igual que una venta nueva.
        if (CierreCajaController::cierreDelDia($request->user()->id, now()->toDateString())) {
            abort(422, 'Su caja de hoy ya fue cerrada: no puede cobrar ventas hasta mañana');
        }

        $request->validate([
            'tipo_pago' => 'nullable|in:EFECTIVO,QR,MIXTO',
            'pago' => 'nullable|numeric|min:0',
            'monto_efectivo' => 'nullable|numeric|min:0',
            'monto_qr' => 'nullable|numeric|min:0',
        ]);

        $venta = DB::transaction(function () use ($request, $id) {
            $venta = Venta::lockForUpdate()->findOrFail($id);

            if ($venta->estado !== 'PENDIENTE') {
                abort(422, 'Solo se pueden cobrar ventas pendientes');
            }
            if ($venta->fecha_hora_cobro) {
                abort(422, 'Esta venta pendiente ya fue cobrada');
            }

            // Si al cobrar no se indica cómo, vale lo que se eligió al crear la venta.
            if (! $request->tipo_pago) {
                $request->merge(['tipo_pago' => $venta->tipo_pago === 'QR' ? 'QR' : 'EFECTIVO']);
            }

            $venta->update([
                'cobrado_por_id' => $request->user()->id,
                'fecha_hora_cobro' => now(),
                ...$this->montosPago($request, (float) $venta->total),
            ]);

            return $venta;
        });

        return response()->json(
            $venta->load(['paciente:id,nombre_completo,ci', 'doctor:id,nombre', 'seguro:id,nombre', 'user:id,name', 'cobradoPor:id,name', 'detalles.producto:id,nombre,codigo'])
        );
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Ventas');
        $venta = Venta::findOrFail($id);

        if ($venta->estado === 'ANULADO') {
            abort(422, 'La venta ya se encuentra anulada');
        }

        $venta->update(['estado' => 'ANULADO']);

        return response()->json(['message' => $venta->esEgreso() ? 'Gasto anulado' : 'Venta anulada']);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Reparte el cobro entre efectivo y QR según el tipo de pago.
     * monto_efectivo + monto_qr = total: el efectivo se guarda sin el cambio
     * devuelto, que es lo que de verdad queda en caja.
     *   EFECTIVO: monto_efectivo (o pago) es lo que entregó el cliente.
     *   QR:       todo el total por QR, sin cambio.
     *   MIXTO:    monto_qr por QR y el resto en efectivo; si entrega más
     *             efectivo del que falta, la diferencia es el cambio.
     */
    private function montosPago(Request $request, float $total): array
    {
        $tipo = mb_strtoupper($request->tipo_pago ?: 'EFECTIVO');

        if ($tipo === 'QR') {
            return ['tipo_pago' => 'QR', 'pago' => $total, 'cambio' => 0, 'monto_efectivo' => 0, 'monto_qr' => $total];
        }

        if ($tipo === 'MIXTO') {
            $qr = round((float) $request->monto_qr, 2);
            if ($qr <= 0 || $qr >= $total) {
                abort(422, 'En pago mixto el monto QR debe ser mayor a 0 y menor al total');
            }
            $efectivo = round($total - $qr, 2);
            $recibido = round((float) ($request->monto_efectivo ?: $efectivo), 2);
            if ($recibido < $efectivo) {
                abort(422, "El efectivo no alcanza: con {$qr} Bs por QR faltan {$efectivo} Bs en efectivo");
            }

            return [
                'tipo_pago' => 'MIXTO',
                'pago' => round($qr + $recibido, 2),
                'cambio' => round($recibido - $efectivo, 2),
                'monto_efectivo' => $efectivo,
                'monto_qr' => $qr,
            ];
        }

        $pago = round((float) ($request->monto_efectivo ?: $request->pago ?: $total), 2);
        if ($pago < $total) {
            abort(422, 'El pago no puede ser menor al total de la venta');
        }

        return [
            'tipo_pago' => 'EFECTIVO',
            'pago' => $pago,
            'cambio' => round($pago - $total, 2),
            'monto_efectivo' => $total,
            'monto_qr' => 0,
        ];
    }

    private function applyFiltros($query, $fechaInicio, $fechaFin, $pacienteId, $userId, $estado, $horaInicio = '', $horaFin = '', $tipoMovimiento = ''): void
    {
        if ($fechaInicio) {
            $query->where('fecha_hora', '>=', $fechaInicio.' '.($horaInicio ?: '00:00').':00');
        }
        if ($fechaFin) {
            $query->where('fecha_hora', '<=', $fechaFin.' '.($horaFin ?: '23:59').':59');
        }
        if ($pacienteId) {
            $query->where('paciente_id', $pacienteId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($estado) {
            $query->where('estado', $estado);
        }
        if ($tipoMovimiento) {
            $query->where('tipo_movimiento', $tipoMovimiento);
        }
    }

    /**
     * Deja en la consulta solo las ventas "puras de farmacia": tienen al menos un
     * producto del tipo FARMACIA y ningún detalle de otro tipo (ni ítems sueltos).
     */
    private function soloFarmacia($query): void
    {
        $esFarmacia = fn ($producto) => $producto->whereHas(
            'tipoProducto',
            fn ($tipo) => $tipo->where('nombre', 'FARMACIA')
        );

        $query->whereHas('detalles', fn ($detalle) => $detalle->whereHas('producto', $esFarmacia))
            ->whereDoesntHave('detalles', fn ($detalle) => $detalle->whereDoesntHave('producto', $esFarmacia));
    }

    private function req(Request $request, string|array $permission): void
    {
        $user = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
