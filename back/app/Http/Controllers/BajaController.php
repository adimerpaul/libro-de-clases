<?php

namespace App\Http\Controllers;

use App\Models\Baja;
use App\Models\BajaDetalle;
use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\TipoProducto;
use App\Services\StockLote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bajas de farmacia: salidas de inventario que no son ventas (vencimiento,
 * cruce, bonificación, deterioro…). Cada línea descuenta de un lote concreto
 * de compra, igual que una venta, para que el saldo por lote siga cuadrando.
 */
class BajaController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Bajas');

        $query = Baja::with($this->relaciones())
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id');

        if ($request->filled('motivo')) {
            $query->where('motivo', $request->input('motivo'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_hora', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_hora', '<=', $request->input('hasta'));
        }

        if ($request->filled('q')) {
            $buscar = '%'.trim($request->input('q')).'%';
            $query->where(function (Builder $filtro) use ($buscar) {
                $filtro->where('observacion', 'like', $buscar)
                    ->orWhereHas('detalles', fn (Builder $detalle) => $detalle
                        ->where('nombre', 'like', $buscar)
                        ->orWhere('lote', 'like', $buscar))
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('name', 'like', $buscar)
                        ->orWhere('username', 'like', $buscar));
            });
        }

        return response()->json($query->paginate((int) $request->input('per_page', 15)));
    }

    public function show(Request $request, $id)
    {
        $this->req($request, 'Ver Bajas');

        return response()->json($this->buscar($id));
    }

    /** Tarjetas del encabezado: cuánto se dio de baja en el mes en curso. */
    public function resumen(Request $request)
    {
        $this->req($request, 'Ver Bajas');

        $desde = now()->startOfMonth();
        $hasta = now()->endOfMonth();

        $delMes = fn () => Baja::where('estado', 'ACTIVO')->whereBetween('fecha_hora', [$desde, $hasta]);

        return response()->json([
            'bajas_mes' => $delMes()->count(),
            'costo_mes' => round((float) $delMes()->sum('total'), 2),
            'unidades_mes' => round((float) BajaDetalle::whereHas(
                'baja',
                fn (Builder $baja) => $baja->where('estado', 'ACTIVO')->whereBetween('fecha_hora', [$desde, $hasta])
            )->sum('cantidad'), 4),
            'por_motivo' => $delMes()
                ->selectRaw('motivo, COUNT(*) as bajas, SUM(total) as costo')
                ->groupBy('motivo')
                ->orderByDesc('costo')
                ->get(),
        ]);
    }

    /** Motivos disponibles para los selects del formulario. */
    public function catalogos(Request $request)
    {
        $this->req($request, ['Ver Bajas', 'Crear Bajas']);

        return response()->json([
            'motivos' => collect(Baja::MOTIVOS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    /** Productos de farmacia para el buscador del formulario. */
    public function productos(Request $request)
    {
        $this->req($request, ['Crear Bajas', 'Ver Bajas']);

        $query = Producto::with('unidad:id,nombre,abreviatura')
            ->where('tipo_producto_id', $this->tipoFarmaciaId())
            ->orderBy('nombre');

        if ($request->filled('q')) {
            $buscar = '%'.trim($request->input('q')).'%';
            $query->where(fn (Builder $filtro) => $filtro
                ->where('nombre', 'like', $buscar)
                ->orWhere('nombre_comercial', 'like', $buscar)
                ->orWhere('codigo', 'like', $buscar));
        }

        return response()->json($query->limit(50)->get(
            ['id', 'codigo', 'nombre', 'nombre_comercial', 'unidad_id', 'precio']
        ));
    }

    /** Lotes con saldo de un producto: lote, vencimiento y cuánto queda. */
    public function lotes(Request $request, $productoId)
    {
        $this->req($request, ['Crear Bajas', 'Ver Bajas']);

        $lotes = CompraDetalle::with('compra:id,fecha_hora,nro_factura,estado')
            ->where('producto_id', $productoId)
            ->whereHas('compra', fn (Builder $compra) => $compra->where('estado', 'ACTIVO'))
            ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento')
            ->orderBy('id')
            ->get();

        $vendido = StockLote::vendidoPorLote($lotes->pluck('id'));
        $bajado = StockLote::bajadoPorLote($lotes->pluck('id'));

        return response()->json(
            $lotes->map(function (CompraDetalle $detalle) use ($vendido, $bajado) {
                $disponible = (float) $detalle->cantidad
                    - (float) ($vendido[$detalle->id] ?? 0)
                    - (float) ($bajado[$detalle->id] ?? 0);

                return [
                    'compra_detalle_id' => $detalle->id,
                    'lote' => $detalle->lote,
                    'fecha_vencimiento' => $detalle->fecha_vencimiento?->format('Y-m-d'),
                    'dias_vencimiento' => $detalle->fecha_vencimiento
                        ? now()->startOfDay()->diffInDays($detalle->fecha_vencimiento, false)
                        : null,
                    'cantidad_comprada' => (float) $detalle->cantidad,
                    'disponible' => max(0, $disponible),
                    'precio' => (float) $detalle->precio,
                    'nro_factura' => $detalle->compra?->nro_factura,
                    'fecha_compra' => $detalle->compra?->fecha_hora,
                ];
            })->filter(fn ($lote) => $lote['disponible'] > 0)->values()
        );
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Bajas');

        $motivos = implode(',', array_keys(Baja::MOTIVOS));

        $request->validate([
            'motivo' => ['required', 'string', 'in:'.$motivos],
            'observacion' => ['nullable', 'string', 'max:500'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.compra_detalle_id' => ['required', 'exists:compra_detalles,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.motivo' => ['nullable', 'string', 'in:'.$motivos],
            'detalles.*.observacion' => ['nullable', 'string', 'max:255'],
        ]);

        $baja = DB::transaction(function () use ($request) {
            $baja = Baja::create([
                'user_id' => $request->user()->id,
                // La fecha la pone el servidor, igual que en compras y ventas.
                'fecha_hora' => now(),
                'motivo' => $request->motivo,
                'observacion' => $request->observacion ? mb_strtoupper($request->observacion) : null,
                'estado' => 'ACTIVO',
                'total' => 0,
            ]);

            $total = 0;
            // Un mismo lote puede venir en varias líneas: se acumula lo ya
            // descontado para no validar cada una contra el saldo original.
            $descontado = [];

            foreach ($request->detalles as $item) {
                $producto = Producto::findOrFail($item['producto_id']);
                $cantidad = (float) $item['cantidad'];

                $lote = CompraDetalle::with('compra:id,estado')
                    ->lockForUpdate()
                    ->findOrFail($item['compra_detalle_id']);

                if ((int) $lote->producto_id !== (int) $producto->id || $lote->compra?->estado !== 'ACTIVO') {
                    abort(422, "El lote seleccionado para {$producto->nombre} no es válido");
                }

                $yaUsado = $descontado[$lote->id] ?? 0;
                $disponible = StockLote::disponibleDe($lote) - $yaUsado;

                if ($cantidad > $disponible) {
                    $etiqueta = $lote->lote ?: 'SIN LOTE';
                    abort(422, "Stock insuficiente en el lote {$etiqueta} de {$producto->nombre}. Disponible: {$disponible}");
                }

                $descontado[$lote->id] = $yaUsado + $cantidad;

                // El costo de la baja es el precio de compra del lote, no el de venta.
                $precio = (float) $lote->precio;
                $lineaTotal = round($precio * $cantidad, 2);
                $total += $lineaTotal;

                BajaDetalle::create([
                    'baja_id' => $baja->id,
                    'producto_id' => $producto->id,
                    'compra_detalle_id' => $lote->id,
                    'nombre' => mb_strtoupper($producto->nombre),
                    'lote' => $lote->lote,
                    'fecha_vencimiento' => $lote->fecha_vencimiento,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'total' => $lineaTotal,
                    'motivo' => $item['motivo'] ?? $request->motivo,
                    'observacion' => ! empty($item['observacion']) ? mb_strtoupper($item['observacion']) : null,
                ]);
            }

            $baja->update(['total' => $total]);

            return $baja;
        });

        return response()->json($this->buscar($baja->id), 201);
    }

    /**
     * Anular devuelve el stock: los detalles se conservan pero dejan de
     * descontar, porque el saldo solo mira bajas en estado ACTIVO.
     */
    public function anular(Request $request, $id)
    {
        $this->req($request, 'Anular Bajas');

        $request->validate(['motivo_anulacion' => ['required', 'string', 'max:500']]);

        $baja = Baja::findOrFail($id);

        if ($baja->estado === 'ANULADO') {
            abort(422, 'La baja ya está anulada');
        }

        $baja->update([
            'estado' => 'ANULADO',
            'anulado_por' => $request->user()->id,
            'anulado_en' => now(),
            'motivo_anulacion' => mb_strtoupper($request->motivo_anulacion),
        ]);

        return response()->json($this->buscar($baja->id));
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function relaciones(): array
    {
        return [
            'user:id,name,username',
            'anuladoPor:id,name,username',
            'detalles.producto:id,codigo,nombre,unidad_id',
            'detalles.producto.unidad:id,nombre,abreviatura',
        ];
    }

    private function buscar($id): Baja
    {
        return Baja::with($this->relaciones())->findOrFail($id);
    }

    private function tipoFarmaciaId(): int
    {
        return TipoProducto::firstOrCreate(
            ['nombre' => 'FARMACIA'],
            ['color' => 'teal', 'es_laboratorio' => false]
        )->id;
    }

    private function req(Request $request, string|array $permission): void
    {
        $user = $request->user();
        foreach ((array) $permission as $p) {
            if ($user->hasPermissionTo($p)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
