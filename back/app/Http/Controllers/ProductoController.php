<?php

namespace App\Http\Controllers;

use App\Exports\FabricantesExport;
use App\Exports\ProductosExport;
use App\Exports\UnidadesExport;
use App\Models\CompraDetalle;
use App\Models\Fabricante;
use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\TipoProductoPadre;
use App\Models\Unidad;
use App\Services\ProductoHistorial;
use App\Services\StockLote;
use App\Support\DescripcionHtml;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductoController extends Controller
{
    // ── Resumen ───────────────────────────────────────────────────

    public function datos(Request $request)
    {
        $this->req($request, 'Ver Productos');

        $qProductos = $request->input('q_prod', '');
        $qFabricantes = $request->input('q_fab', '');
        $qUnidades = $request->input('q_unid', '');
        $qTipos = $request->input('q_tipo', '');
        $qPadres = $request->input('q_padre', '');
        $tipoProductoPadreId = $request->input('tipo_producto_padre_id', '');
        $tipoProductoId = $request->input('tipo_producto_id', '');
        $perPage = (int) $request->input('per_page', 15);
        $pageProductos = (int) $request->input('page_prod', 1);
        $pageFabrican = (int) $request->input('page_fab', 1);
        $pageUnidades = (int) $request->input('page_unid', 1);
        $pageTipos = (int) $request->input('page_tipo', 1);
        $pagePadres = (int) $request->input('page_padre', 1);

        $productosQuery = Producto::with(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura', 'tipoProducto:id,nombre,color,es_laboratorio'])
            ->withSum(['compraDetalles as stock' => function ($q) {
                $q->whereHas('compra', fn ($cq) => $cq->where('estado', 'ACTIVO'));
            }], 'cantidad')
            ->orderBy('nombre');
        if ($qProductos) {
            $productosQuery->where(function ($sq) use ($qProductos) {
                $sq->where('nombre', 'like', "%$qProductos%")
                    ->orWhere('codigo', 'like', "%$qProductos%")
                    ->orWhere('marca', 'like', "%$qProductos%");
            });
        }
        if ($tipoProductoId) {
            $productosQuery->where('tipo_producto_id', $tipoProductoId);
        }

        $fabricantesQuery = Fabricante::orderBy('nombre');
        if ($qFabricantes) {
            $fabricantesQuery->where(function ($sq) use ($qFabricantes) {
                $sq->where('nombre', 'like', "%$qFabricantes%")
                    ->orWhere('pais', 'like', "%$qFabricantes%");
            });
        }

        $unidadesQuery = Unidad::orderBy('nombre');
        if ($qUnidades) {
            $unidadesQuery->where(function ($sq) use ($qUnidades) {
                $sq->where('nombre', 'like', "%$qUnidades%")
                    ->orWhere('abreviatura', 'like', "%$qUnidades%");
            });
        }

        $tiposQuery = TipoProducto::with('padre:id,nombre,color,icono')
            ->withCount('productos')
            ->orderBy('nombre');
        if ($qTipos) {
            $tiposQuery->where('nombre', 'like', "%$qTipos%");
        }
        if ($tipoProductoPadreId) {
            $tiposQuery->where('tipo_producto_padre_id', $tipoProductoPadreId);
        }

        $padresQuery = TipoProductoPadre::withCount('tipos')->orderBy('orden')->orderBy('nombre');
        if ($qPadres) {
            $padresQuery->where('nombre', 'like', "%$qPadres%");
        }

        return response()->json([
            'resumen' => [
                'productos' => Producto::count(),
                'fabricantes' => Fabricante::count(),
                'unidades' => Unidad::count(),
                'tipos' => TipoProducto::count(),
                'padres' => TipoProductoPadre::count(),
            ],
            'productos' => $productosQuery->paginate($perPage, ['*'], 'page_prod', $pageProductos),
            'fabricantes' => $fabricantesQuery->paginate($perPage, ['*'], 'page_fab', $pageFabrican),
            'unidades' => $unidadesQuery->paginate($perPage, ['*'], 'page_unid', $pageUnidades),
            'tipos' => $tiposQuery->paginate($perPage, ['*'], 'page_tipo', $pageTipos),
            'padres' => $padresQuery->paginate($perPage, ['*'], 'page_padre', $pagePadres),
            'allFabricantes' => Fabricante::orderBy('nombre')->get(['id', 'nombre', 'pais']),
            'allUnidades' => Unidad::orderBy('nombre')->get(['id', 'nombre', 'abreviatura']),
            'allTipoProductos' => TipoProducto::with('padre:id,nombre,color,icono')
                ->orderBy('nombre')
                ->get(['id', 'tipo_producto_padre_id', 'nombre', 'color']),
            'allTipoProductoPadres' => TipoProductoPadre::orderBy('orden')->orderBy('nombre')
                ->get(['id', 'nombre', 'color', 'icono', 'es_laboratorio']),
        ]);
    }

    public function resumen(Request $request)
    {
        $this->req($request, 'Ver Productos');

        return response()->json([
            'productos' => Producto::count(),
            'fabricantes' => Fabricante::count(),
            'unidades' => Unidad::count(),
            'tipos' => TipoProducto::count(),
            'padres' => TipoProductoPadre::count(),
        ]);
    }

    public function historial(Request $request, $id)
    {
        $this->req($request, 'Ver Productos');

        return response()->json(ProductoHistorial::para(Producto::findOrFail($id)));
    }

    public function lotesDisponibles(Request $request, $id)
    {
        $this->req($request, ['Ver Ventas', 'Crear Ventas', 'Ver Productos']);
        $producto = Producto::findOrFail($id);

        $lotes = CompraDetalle::with('compra:id,fecha_hora,estado')
            ->where('producto_id', $producto->id)
            ->whereNotNull('lote')
            ->where('lote', '<>', '')
            ->whereHas('compra', fn ($query) => $query->where('estado', 'ACTIVO'))
            ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento')
            ->orderBy('id')
            ->get();

        $vendidoPorDetalle = StockLote::vendidoPorLote($lotes->pluck('id'));
        $bajadoPorDetalle = StockLote::bajadoPorLote($lotes->pluck('id'));

        return response()->json($lotes->map(function ($detalle) use ($vendidoPorDetalle, $bajadoPorDetalle) {
            $disponible = max(0, (float) $detalle->cantidad
                - (float) ($vendidoPorDetalle[$detalle->id] ?? 0)
                - (float) ($bajadoPorDetalle[$detalle->id] ?? 0));

            return [
                'compra_detalle_id' => $detalle->id,
                'compra_id' => $detalle->compra_id,
                'lote' => $detalle->lote,
                'fecha_vencimiento' => $detalle->fecha_vencimiento?->format('Y-m-d'),
                'cantidad_comprada' => (float) $detalle->cantidad,
                'cantidad_disponible' => $disponible,
                'fecha_compra' => $detalle->compra?->fecha_hora,
            ];
        })->filter(fn ($lote) => $lote['cantidad_disponible'] > 0)->values());
    }

    // ── Catálogos ─────────────────────────────────────────────────

    public function fabricantes(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Fabricante::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                    ->orWhere('pais', 'like', "%$q%");
            });
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }

        return response()->json($query->get());
    }

    public function storeFabricante(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $fab = Fabricante::create([
            'nombre' => mb_strtoupper($request->nombre),
            'pais' => $request->pais ? mb_strtoupper($request->pais) : null,
        ]);

        return response()->json($fab, 201);
    }

    public function updateFabricante(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $fab = Fabricante::findOrFail($id);
        $fab->update([
            'nombre' => mb_strtoupper($request->nombre),
            'pais' => $request->pais ? mb_strtoupper($request->pais) : null,
        ]);

        return response()->json($fab);
    }

    public function destroyFabricante(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        Fabricante::findOrFail($id)->delete();

        return response()->json(['message' => 'Fabricante eliminado']);
    }

    public function unidades(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Unidad::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                    ->orWhere('abreviatura', 'like', "%$q%");
            });
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }

        return response()->json($query->get());
    }

    public function storeUnidad(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $u = Unidad::create([
            'nombre' => mb_strtoupper($request->nombre),
            'abreviatura' => $request->abreviatura ? mb_strtoupper($request->abreviatura) : null,
        ]);

        return response()->json($u, 201);
    }

    public function updateUnidad(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $request->validate(['nombre' => 'required|string|max:255']);
        $u = Unidad::findOrFail($id);
        $u->update([
            'nombre' => mb_strtoupper($request->nombre),
            'abreviatura' => $request->abreviatura ? mb_strtoupper($request->abreviatura) : null,
        ]);

        return response()->json($u);
    }

    public function destroyUnidad(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        Unidad::findOrFail($id)->delete();

        return response()->json(['message' => 'Unidad eliminada']);
    }

    // ── Catálogos - Tipos de producto ───────────────────────────────

    public function tiposProducto(Request $request)
    {
        // Quien vende necesita las categorías para filtrar el catálogo de la venta.
        $this->req($request, ['Ver Productos', 'Crear Ventas']);
        $q = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = TipoProducto::with('padre:id,nombre,color,icono')->withCount('productos');

        if ($request->filled('tipo_producto_padre_id')) {
            $query->where('tipo_producto_padre_id', $request->input('tipo_producto_padre_id'));
        }

        // ?laboratorio=1 devuelve solo las áreas de laboratorio; ?laboratorio=0
        // solo el resto. Sin el parámetro se devuelve todo, como antes.
        if ($request->filled('laboratorio')) {
            $query->where('es_laboratorio', $request->boolean('laboratorio'));
        }

        $query->orderBy('orden')->orderBy('nombre');

        if ($q) {
            $query->where('nombre', 'like', "%$q%");
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }

        return response()->json($query->get());
    }

    public function storeTipoProducto(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate([
            'tipo_producto_padre_id' => 'nullable|exists:tipo_producto_padres,id',
            'nombre' => 'required|string|max:255',
            'color' => 'nullable|string|max:30',
            'es_laboratorio' => 'nullable|boolean',
            'orden' => 'nullable|integer|min:0',
        ]);
        $tipo = TipoProducto::create([
            'tipo_producto_padre_id' => $request->input('tipo_producto_padre_id'),
            'nombre' => mb_strtoupper($request->nombre),
            'color' => $request->color ?: 'primary',
            'es_laboratorio' => $request->boolean('es_laboratorio'),
            // Sin orden explícito va al final, no al principio de la lista.
            'orden' => (int) $request->input('orden', TipoProducto::max('orden') + 1),
        ]);

        return response()->json($tipo->load('padre:id,nombre,color,icono'), 201);
    }

    public function updateTipoProducto(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $request->validate([
            'tipo_producto_padre_id' => 'nullable|exists:tipo_producto_padres,id',
            'nombre' => 'required|string|max:255',
            'color' => 'nullable|string|max:30',
            'es_laboratorio' => 'nullable|boolean',
            'orden' => 'nullable|integer|min:0',
        ]);
        $tipo = TipoProducto::findOrFail($id);
        $tipo->update([
            // Sin el campo en la petición (p. ej. pantalla de laboratorio) se conserva el padre.
            'tipo_producto_padre_id' => $request->has('tipo_producto_padre_id')
                ? $request->input('tipo_producto_padre_id')
                : $tipo->tipo_producto_padre_id,
            'nombre' => mb_strtoupper($request->nombre),
            'color' => $request->color ?: 'primary',
            'es_laboratorio' => $request->has('es_laboratorio')
                ? $request->boolean('es_laboratorio')
                : $tipo->es_laboratorio,
            'orden' => (int) $request->input('orden', $tipo->orden),
        ]);

        return response()->json($tipo->load('padre:id,nombre,color,icono'));
    }

    public function destroyTipoProducto(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        $tipo = TipoProducto::withCount('productos')->findOrFail($id);

        // Borrarlo dejaría esos productos sin tipo y fuera de todos los filtros.
        if ($tipo->productos_count > 0) {
            return response()->json([
                'message' => "No se puede eliminar: {$tipo->nombre} tiene {$tipo->productos_count} producto(s) asignado(s).",
            ], 422);
        }

        $tipo->delete();

        return response()->json(['message' => 'Tipo de producto eliminado']);
    }

    // ── Catálogos - Tipos de producto padre ─────────────────────────

    public function tiposProductoPadre(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $q = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = TipoProductoPadre::withCount('tipos')->orderBy('orden')->orderBy('nombre');

        if ($q) {
            $query->where('nombre', 'like', "%$q%");
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }

        return response()->json($query->get());
    }

    public function storeTipoProductoPadre(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $datos = $this->validarTipoProductoPadre($request);

        $padre = TipoProductoPadre::create([
            ...$datos,
            'orden' => (int) $request->input('orden', TipoProductoPadre::max('orden') + 1),
        ]);

        return response()->json($padre, 201);
    }

    public function updateTipoProductoPadre(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $datos = $this->validarTipoProductoPadre($request);

        $padre = TipoProductoPadre::findOrFail($id);
        $padre->update([
            ...$datos,
            'orden' => (int) $request->input('orden', $padre->orden),
        ]);

        // Los hijos heredan la marca de laboratorio del padre.
        $padre->tipos()->update(['es_laboratorio' => $padre->es_laboratorio]);

        return response()->json($padre->loadCount('tipos'));
    }

    public function destroyTipoProductoPadre(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        $padre = TipoProductoPadre::withCount('tipos')->findOrFail($id);

        if ($padre->tipos_count > 0) {
            return response()->json([
                'message' => "No se puede eliminar: {$padre->nombre} tiene {$padre->tipos_count} tipo(s) de producto asignado(s).",
            ], 422);
        }

        $padre->delete();

        return response()->json(['message' => 'Tipo de producto padre eliminado']);
    }

    private function validarTipoProductoPadre(Request $request): array
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'color' => 'nullable|string|max:30',
            'icono' => 'nullable|string|max:60',
            'es_laboratorio' => 'nullable|boolean',
            'orden' => 'nullable|integer|min:0',
        ]);

        return [
            'nombre' => mb_strtoupper($request->nombre),
            'color' => $request->color ?: 'primary',
            'icono' => $request->icono ?: 'category',
            'es_laboratorio' => $request->boolean('es_laboratorio'),
        ];
    }

    // ── Productos ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        // Quien vende arma la venta desde este catálogo aunque no administre productos.
        $this->req($request, ['Ver Productos', 'Crear Ventas']);

        $q = $request->input('q', '');
        $tipoProductoId = $request->input('tipo_producto_id', '');
        $tipoNombre = $request->input('tipo', '');
        $perPage = (int) $request->input('per_page', 20);

        $query = Producto::with(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura', 'tipoProducto:id,nombre,color,es_laboratorio'])
            ->addSelect([
                'ultimo_precio_compra' => CompraDetalle::query()
                    ->select('compra_detalles.precio')
                    ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
                    ->whereColumn('compra_detalles.producto_id', 'productos.id')
                    ->whereNull('compra_detalles.deleted_at')
                    ->whereNull('compras.deleted_at')
                    ->where('compras.estado', 'ACTIVO')
                    ->orderByDesc('compras.fecha_hora')
                    ->orderByDesc('compra_detalles.id')
                    ->limit(1),
            ])
            ->withSum(['compraDetalles as cantidad_con_lote' => function ($detalle) {
                $detalle->whereNotNull('lote')
                    ->where('lote', '<>', '')
                    ->whereHas('compra', fn ($compra) => $compra->where('estado', 'ACTIVO'));
            }], 'cantidad')
            ->withSum(['ventaDetalles as cantidad_vendida_lote' => function ($detalle) {
                $detalle->whereNotNull('compra_detalle_id')
                    ->whereHas('venta', fn ($venta) => $venta->where('estado', '<>', 'ANULADO'));
            }], 'cantidad')
            ->withSum(['bajaDetalles as cantidad_baja_lote' => function ($detalle) {
                $detalle->whereNotNull('compra_detalle_id')
                    ->whereHas('baja', fn ($baja) => $baja->where('estado', 'ACTIVO'));
            }], 'cantidad')
            ->orderBy('nombre');

        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                    ->orWhere('codigo', 'like', "%$q%")
                    ->orWhere('marca', 'like', "%$q%");
            });
        }

        if ($tipoProductoId) {
            $query->where('tipo_producto_id', $tipoProductoId);
        }

        if ($tipoNombre) {
            $query->whereHas('tipoProducto', fn ($tq) => $tq->where('nombre', mb_strtoupper($tipoNombre)));
        }

        // ?laboratorio=1 trae los productos de cualquier área de laboratorio.
        if ($request->boolean('laboratorio')) {
            $query->whereHas('tipoProducto', fn ($tq) => $tq->where('es_laboratorio', true));
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Productos');
        $request->validate([
            'descripcion' => 'nullable|string|max:60000',
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'precio_seguro' => 'nullable|numeric|min:0',
            'tipo_producto_id' => 'nullable|exists:tipo_productos,id',
        ]);
        $producto = Producto::create([
            'codigo' => $request->codigo ? mb_strtoupper($request->codigo) : null,
            'nombre' => mb_strtoupper($request->nombre),
            'descripcion' => $request->descripcion ? DescripcionHtml::guardar($request->descripcion) : null,
            'marca' => $request->marca ? mb_strtoupper($request->marca) : null,
            'fabricante_id' => $request->fabricante_id ?: null,
            'unidad_id' => $request->unidad_id ?: null,
            'tipo_producto_id' => $request->tipo_producto_id ?: null,
            'precio' => $request->precio ?: 0,
            'precio_seguro' => $request->filled('precio_seguro') ? $request->precio_seguro : null,
        ]);

        return response()->json($producto->load(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura', 'tipoProducto:id,nombre,color,es_laboratorio']), 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Productos');
        $producto = Producto::findOrFail($id);
        $request->validate([
            'descripcion' => 'nullable|string|max:60000',
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'precio_seguro' => 'nullable|numeric|min:0',
            'tipo_producto_id' => 'nullable|exists:tipo_productos,id',
        ]);
        $producto->update([
            'codigo' => $request->codigo ? mb_strtoupper($request->codigo) : null,
            'nombre' => mb_strtoupper($request->nombre),
            'descripcion' => $request->descripcion ? DescripcionHtml::guardar($request->descripcion) : null,
            'marca' => $request->marca ? mb_strtoupper($request->marca) : null,
            'fabricante_id' => $request->fabricante_id ?: null,
            'unidad_id' => $request->unidad_id ?: null,
            'tipo_producto_id' => $request->tipo_producto_id ?: null,
            'precio' => $request->precio ?: 0,
            'precio_seguro' => $request->filled('precio_seguro') ? $request->precio_seguro : null,
        ]);

        return response()->json($producto->load(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura', 'tipoProducto:id,nombre,color,es_laboratorio']));
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Productos');
        Producto::findOrFail($id)->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }

    // ── Exportar Productos ────────────────────────────────────────

    public function exportProductosPdf(Request $request)
    {
        $this->req($request, 'Ver Productos');
        ini_set('memory_limit', '1024M');
        $q = $request->input('q', '');
        $tipoProductoId = $request->input('tipo_producto_id', '');

        $query = Producto::with(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura', 'tipoProducto:id,nombre,color,es_laboratorio'])
            ->orderBy('nombre');
        if ($tipoProductoId) {
            $query->where('tipo_producto_id', $tipoProductoId);
        }
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                    ->orWhere('codigo', 'like', "%$q%")
                    ->orWhere('marca', 'like', "%$q%");
            });
        }
        $items = $query->get();

        $pdf = Pdf::loadView('reportes.productos', [
            'items' => $items,
            'q' => $q,
            'total' => $items->count(),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('productos_'.now()->format('Ymd_His').'.pdf');
    }

    public function exportProductosExcel(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $filters = $request->only(['q', 'tipo_producto_id']);

        return Excel::download(new ProductosExport($filters), 'productos_'.now()->format('Ymd_His').'.xlsx');
    }

    // ── Exportar Fabricantes ──────────────────────────────────────

    public function exportFabricantesPdf(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $query = Fabricante::orderBy('nombre');
        $items = $query->get();

        $pdf = Pdf::loadView('reportes.fabricantes', [
            'items' => $items,
            'q' => '',
            'total' => $items->count(),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('fabricantes_'.now()->format('Ymd_His').'.pdf');
    }

    public function exportFabricantesExcel(Request $request)
    {
        $this->req($request, 'Ver Productos');

        return Excel::download(new FabricantesExport, 'fabricantes_'.now()->format('Ymd_His').'.xlsx');
    }

    // ── Exportar Unidades ─────────────────────────────────────────

    public function exportUnidadesPdf(Request $request)
    {
        $this->req($request, 'Ver Productos');
        $query = Unidad::orderBy('nombre');
        $items = $query->get();

        $pdf = Pdf::loadView('reportes.unidades', [
            'items' => $items,
            'q' => '',
            'total' => $items->count(),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('unidades_'.now()->format('Ymd_His').'.pdf');
    }

    public function exportUnidadesExcel(Request $request)
    {
        $this->req($request, 'Ver Productos');

        return Excel::download(new UnidadesExport, 'unidades_'.now()->format('Ymd_His').'.xlsx');
    }

    // ── Helper ────────────────────────────────────────────────────

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
