<?php

namespace App\Http\Controllers;

use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\VentaDetalle;
use App\Services\ProductoHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoLoteController extends Controller
{
    public function update(Request $request, Producto $producto, string $tipo, int $detalle)
    {
        abort_unless($request->user()->checkPermissionTo('Editar Lotes Farmacia')
            && $request->user()->hasAnyPermission(['Ver Productos', 'Ver Productos Farmacia']), 403);
        abort_unless($producto->tipoProducto?->nombre === 'FARMACIA', 404);
        abort_unless(in_array($tipo, ['COMPRA', 'VENTA'], true), 404);

        $datos = $request->validate([
            'lote' => 'required|string|max:255',
            'fecha_vencimiento' => 'present|nullable|date_format:Y-m-d',
        ]);
        $datos['lote'] = mb_strtoupper(trim($datos['lote']));
        abort_if($datos['lote'] === '', 422, 'Ingrese el lote');

        DB::transaction(function () use ($producto, $tipo, $detalle, $datos) {
            $movimiento = ($tipo === 'COMPRA' ? CompraDetalle::query() : VentaDetalle::query())
                ->where('producto_id', $producto->id)->findOrFail($detalle);
            $compraId = $tipo === 'COMPRA' ? $movimiento->id : $movimiento->compra_detalle_id;

            if ($compraId) {
                // La compra identifica el lote; conservar su vínculo y auditar cada corrección.
                $compra = CompraDetalle::where('producto_id', $producto->id)
                    ->lockForUpdate()->findOrFail($compraId);
                $compra->update($datos);
                foreach ([$compra->ventaDetalles(), $compra->bajaDetalles()] as $relacion) {
                    foreach ($relacion->withTrashed()->lockForUpdate()->get() as $vinculado) {
                        $vinculado->update($datos);
                    }
                }
            } else {
                // Ventas antiguas sin vínculo a una compra: corregir solo su detalle.
                VentaDetalle::where('producto_id', $producto->id)
                    ->lockForUpdate()->findOrFail($detalle)->update($datos);
            }
        });

        return response()->json(ProductoHistorial::para($producto));
    }
}
