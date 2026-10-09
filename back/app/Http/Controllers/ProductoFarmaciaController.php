<?php

namespace App\Http\Controllers;

use App\Exports\ProductosExport;
use App\Models\Fabricante;
use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\Unidad;
use App\Services\ProductoHistorial;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Productos de farmacia: la misma tabla `productos` que usa /farmacia, pero
 * acotada al tipo FARMACIA (medicamentos e insumos) y con permisos propios,
 * separados de los del catálogo general que también contiene servicios.
 */
class ProductoFarmaciaController extends Controller
{
    private const TIPO = 'FARMACIA';

    // ── Listado ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->req($request, 'Ver Productos Farmacia');

        $q = $request->input('q', '');
        $perPage = (int) $request->input('per_page', 20);

        $query = $this->baseQuery()
            ->withSum(['compraDetalles as comprado' => function ($detalle) {
                $detalle->whereHas('compra', fn ($compra) => $compra->where('estado', 'ACTIVO'));
            }], 'cantidad')
            ->withSum(['ventaDetalles as vendido' => function ($detalle) {
                $detalle->whereHas('venta', fn ($venta) => $venta->where('estado', '<>', 'ANULADO'));
            }], 'cantidad')
            ->withSum(['bajaDetalles as dado_baja' => function ($detalle) {
                $detalle->whereHas('baja', fn ($baja) => $baja->where('estado', 'ACTIVO'));
            }], 'cantidad')
            ->orderBy('nombre');

        $this->aplicarBusqueda($query, $q);

        $productos = $query->paginate($perPage);

        // El stock disponible es lo comprado en compras vigentes menos lo vendido
        // y lo dado de baja (vencimiento, cruce, bonificación…).
        $productos->getCollection()->transform(function ($producto) {
            $producto->stock = (float) $producto->comprado - (float) $producto->vendido - (float) $producto->dado_baja;

            return $producto;
        });

        return response()->json($productos);
    }

    public function resumen(Request $request)
    {
        $this->req($request, 'Ver Productos Farmacia');

        $productos = $this->baseQuery()
            ->withSum(['compraDetalles as comprado' => function ($detalle) {
                $detalle->whereHas('compra', fn ($compra) => $compra->where('estado', 'ACTIVO'));
            }], 'cantidad')
            ->withSum(['ventaDetalles as vendido' => function ($detalle) {
                $detalle->whereHas('venta', fn ($venta) => $venta->where('estado', '<>', 'ANULADO'));
            }], 'cantidad')
            ->withSum(['bajaDetalles as dado_baja' => function ($detalle) {
                $detalle->whereHas('baja', fn ($baja) => $baja->where('estado', 'ACTIVO'));
            }], 'cantidad')
            ->get(['id', 'precio', 'tipo_producto_id']);

        $conStock = 0;
        $valorInventario = 0;
        foreach ($productos as $producto) {
            $stock = (float) $producto->comprado - (float) $producto->vendido - (float) $producto->dado_baja;
            if ($stock > 0) {
                $conStock++;
                $valorInventario += $stock * (float) $producto->precio;
            }
        }

        return response()->json([
            'productos' => $productos->count(),
            'con_stock' => $conStock,
            'sin_stock' => $productos->count() - $conStock,
            'valor_inventario' => round($valorInventario, 2),
        ]);
    }

    /** Fabricantes y unidades para los selects del formulario. */
    public function catalogos(Request $request)
    {
        $this->req($request, 'Ver Productos Farmacia');

        return response()->json([
            'fabricantes' => Fabricante::orderBy('nombre')->get(['id', 'nombre', 'pais']),
            'unidades' => Unidad::orderBy('nombre')->get(['id', 'nombre', 'abreviatura']),
        ]);
    }

    /** Movimientos de compras y ventas del producto. */
    public function historial(Request $request, $id)
    {
        $this->req($request, 'Ver Productos Farmacia');

        return response()->json(ProductoHistorial::para($this->buscar($id)));
    }

    // ── CRUD ──────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->req($request, 'Crear Productos Farmacia');
        $this->validar($request);

        $producto = Producto::create($this->datos($request));

        return response()->json($producto->load($this->relaciones()), 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Productos Farmacia');
        $producto = $this->buscar($id);
        $this->validar($request);

        $producto->update($this->datos($request));

        return response()->json($producto->load($this->relaciones()));
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos Farmacia');
        $this->buscar($id)->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }

    // ── Exportaciones ─────────────────────────────────────────────

    public function exportPdf(Request $request)
    {
        $this->req($request, 'Ver Productos Farmacia');
        ini_set('memory_limit', '1024M');

        $q = $request->input('q', '');
        $query = $this->baseQuery()->orderBy('nombre');
        $this->aplicarBusqueda($query, $q);
        $items = $query->get();

        $pdf = Pdf::loadView('reportes.productos', [
            'items' => $items,
            'q' => $q,
            'total' => $items->count(),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('productos_farmacia_'.now()->format('Ymd_His').'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $this->req($request, 'Ver Productos Farmacia');

        $filters = [
            'q' => $request->input('q', ''),
            'tipo_producto_id' => $this->tipoFarmaciaId(),
        ];

        return Excel::download(new ProductosExport($filters), 'productos_farmacia_'.now()->format('Ymd_His').'.xlsx');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function relaciones(): array
    {
        return ['fabricante:id,nombre', 'unidad:id,nombre,abreviatura', 'tipoProducto:id,nombre,color,es_laboratorio'];
    }

    private function baseQuery()
    {
        return Producto::with($this->relaciones())
            ->where('tipo_producto_id', $this->tipoFarmaciaId());
    }

    private function aplicarBusqueda($query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $query->where(function ($sq) use ($q) {
            $sq->where('nombre', 'like', "%$q%")
                ->orWhere('nombre_comercial', 'like', "%$q%")
                ->orWhere('codigo', 'like', "%$q%")
                ->orWhere('marca', 'like', "%$q%");
        });
    }

    /** El tipo FARMACIA se crea si no existe para que la pantalla nunca quede vacía por un dato faltante. */
    private function tipoFarmaciaId(): int
    {
        return TipoProducto::firstOrCreate(
            ['nombre' => self::TIPO],
            ['color' => 'teal', 'es_laboratorio' => false]
        )->id;
    }

    private function buscar($id): Producto
    {
        return Producto::where('tipo_producto_id', $this->tipoFarmaciaId())->findOrFail($id);
    }

    private function validar(Request $request): void
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'precio_seguro' => 'nullable|numeric|min:0',
            'fabricante_id' => 'nullable|exists:fabricantes,id',
            'unidad_id' => 'nullable|exists:unidades,id',
        ]);
    }

    private function datos(Request $request): array
    {
        return [
            'codigo' => $request->codigo ? mb_strtoupper($request->codigo) : null,
            'nombre' => mb_strtoupper($request->nombre),
            'nombre_comercial' => $request->nombre_comercial ? mb_strtoupper($request->nombre_comercial) : null,
            'descripcion' => $request->descripcion ? mb_strtoupper($request->descripcion) : null,
            'marca' => $request->marca ? mb_strtoupper($request->marca) : null,
            'fabricante_id' => $request->fabricante_id ?: null,
            'unidad_id' => $request->unidad_id ?: null,
            // El tipo no se elige: esta pantalla solo administra productos de farmacia.
            'tipo_producto_id' => $this->tipoFarmaciaId(),
            'precio' => $request->precio ?: 0,
            'precio_seguro' => $request->filled('precio_seguro') ? $request->precio_seguro : null,
        ];
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
