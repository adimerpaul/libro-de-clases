<?php

namespace App\Http\Controllers;

use App\Exports\ComprasExport;
use App\Exports\CompraDetalleExport;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Compras');

        $fechaInicio = $request->input('fecha_inicio', '');
        $fechaFin = $request->input('fecha_fin', '');
        $proveedorId = $request->input('proveedor_id', '');
        $userId = $request->input('user_id', '');
        $estado = $request->input('estado', '');
        $perPage = (int) $request->input('per_page', 15);

        $query = Compra::with(['proveedor:id,nombre', 'user:id,name'])
            ->withCount('detalles')
            ->orderByDesc('fecha_hora');

        $this->applyFiltros($query, $fechaInicio, $fechaFin, $proveedorId, $userId, $estado);

        $resumenQuery = Compra::query();
        $this->applyFiltros($resumenQuery, $fechaInicio, $fechaFin, $proveedorId, $userId, '');

        return response()->json([
            'resumen' => [
                'total_compras' => (clone $resumenQuery)->where('estado', 'ACTIVO')->sum('total'),
                'total_anuladas' => (clone $resumenQuery)->where('estado', 'ANULADO')->sum('total'),
                'cantidad' => (clone $resumenQuery)->count(),
            ],
            'compras' => $query->paginate($perPage),
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->req($request, 'Ver Compras');
        $compra = Compra::with(['proveedor:id,nombre', 'user:id,name', 'detalles.producto:id,nombre,codigo'])
            ->findOrFail($id);

        return response()->json($compra);
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Compras');

        $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'fecha_hora' => 'required|date',
            'nro_factura' => 'nullable|string|max:255',
            'tipo_pago' => 'nullable|string|max:50',
            'comentario' => 'nullable|string|max:500',
            'detalles' => 'required|array|min:1',
            // Solo se compran productos del catálogo de farmacia: el nombre libre ya no se acepta.
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.precio' => 'required|numeric|min:0',
            'detalles.*.cantidad' => 'required|numeric|min:0.0001',
            'detalles.*.factor' => 'nullable|numeric|min:0',
            'detalles.*.precio_venta' => 'nullable|numeric|min:0',
            'detalles.*.lote' => 'nullable|string|max:255',
            'detalles.*.fecha_vencimiento' => 'nullable|date',
        ]);

        // Las compras son de farmacia: todo producto del catálogo debe ser tipo FARMACIA
        $productoIds = collect($request->detalles)->pluck('producto_id')->filter()->unique()->values();
        $noFarmacia = Producto::whereIn('id', $productoIds)
            ->whereDoesntHave('tipoProducto', fn ($q) => $q->where('nombre', 'FARMACIA'))
            ->pluck('nombre');
        if ($noFarmacia->isNotEmpty()) {
            abort(422, 'Solo se pueden comprar productos de tipo FARMACIA. No permitidos: '.$noFarmacia->implode(', '));
        }

        $compra = DB::transaction(function () use ($request) {
            $compra = Compra::create([
                'user_id' => $request->user()->id,
                'proveedor_id' => $request->proveedor_id ?: null,
                'fecha_hora' => $request->fecha_hora,
                'nro_factura' => $request->nro_factura ?: null,
                'tipo_pago' => $request->tipo_pago ? mb_strtoupper($request->tipo_pago) : 'EFECTIVO',
                'comentario' => $request->comentario ?: null,
                'estado' => 'ACTIVO',
                'total' => 0,
            ]);

            $total = 0;
            foreach ($request->detalles as $item) {
                $producto = Producto::find($item['producto_id']);
                $precio = (float) $item['precio'];
                $cantidad = (float) $item['cantidad'];
                $lineaTotal = round($precio * $cantidad, 2);
                $total += $lineaTotal;

                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $producto->id,
                    'nombre' => mb_strtoupper($producto->nombre),
                    'precio' => $precio,
                    'cantidad' => $cantidad,
                    'total' => $lineaTotal,
                    'factor' => $item['factor'] ?? null,
                    'precio_venta' => $item['precio_venta'] ?? null,
                    'lote' => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                ]);

                if ($producto && ! empty($item['precio_venta'])) {
                    $producto->update(['precio' => $item['precio_venta']]);
                }
            }

            $compra->update(['total' => $total]);

            return $compra;
        });

        return response()->json(
            $compra->load(['proveedor:id,nombre', 'user:id,name', 'detalles.producto:id,nombre,codigo']),
            201
        );
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Compras');
        $compra = Compra::findOrFail($id);

        if ($compra->estado === 'ANULADO') {
            abort(422, 'La compra ya se encuentra anulada');
        }

        $compra->update(['estado' => 'ANULADO']);

        return response()->json(['message' => 'Compra anulada']);
    }

    public function exportExcel(Request $request)
    {
        $this->req($request, 'Ver Compras');
        $filters = $request->only(['fecha_inicio', 'fecha_fin', 'proveedor_id', 'user_id', 'estado']);

        return Excel::download(new ComprasExport($filters), 'compras_'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportDetalleExcel(Request $request, $id)
    {
        $this->req($request, 'Ver Compras');

        $compra = Compra::with([
            'proveedor:id,nombre',
            'user:id,name',
            'detalles.producto:id,codigo,nombre',
        ])->findOrFail($id);

        return Excel::download(
            new CompraDetalleExport($compra),
            'compra_'.$compra->id.'_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function applyFiltros($query, $fechaInicio, $fechaFin, $proveedorId, $userId, $estado): void
    {
        if ($fechaInicio) {
            $query->whereDate('fecha_hora', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('fecha_hora', '<=', $fechaFin);
        }
        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($estado) {
            $query->where('estado', $estado);
        }
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
