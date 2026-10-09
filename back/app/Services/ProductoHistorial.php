<?php

namespace App\Services;

use App\Models\Baja;
use App\Models\BajaDetalle;
use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\VentaDetalle;

/**
 * Historial de movimientos de un producto (compras y ventas), compartido por el
 * catálogo general de farmacia y la pantalla de productos de farmacia.
 */
class ProductoHistorial
{
    public static function para(Producto $producto): array
    {
        $detallesCompra = CompraDetalle::with(['compra.proveedor:id,nombre'])
            ->where('producto_id', $producto->id)
            ->get();

        $vendidoPorCompraDetalle = VentaDetalle::query()
            ->whereIn('compra_detalle_id', $detallesCompra->pluck('id'))
            ->whereHas('venta', fn ($query) => $query->where('estado', 'ACTIVO'))
            ->selectRaw('compra_detalle_id, SUM(cantidad) as cantidad_vendida')
            ->groupBy('compra_detalle_id')
            ->pluck('cantidad_vendida', 'compra_detalle_id');

        $bajadoPorCompraDetalle = StockLote::bajadoPorLote($detallesCompra->pluck('id'));

        $compras = $detallesCompra->map(function ($detalle) use ($vendidoPorCompraDetalle, $bajadoPorCompraDetalle) {
            $cantidadVendida = (float) ($vendidoPorCompraDetalle[$detalle->id] ?? 0);
            $cantidadBaja = (float) ($bajadoPorCompraDetalle[$detalle->id] ?? 0);
            $saldo = $detalle->compra?->estado === 'ACTIVO'
                ? max(0, (float) $detalle->cantidad - $cantidadVendida - $cantidadBaja)
                : 0;

            return [
                'tipo' => 'COMPRA',
                'detalle_id' => $detalle->id,
                'id' => $detalle->compra_id,
                'compra_detalle_id' => $detalle->id,
                'fecha_hora' => $detalle->compra?->fecha_hora,
                'documento' => $detalle->compra?->nro_factura ?: 'Compra #'.$detalle->compra_id,
                'tercero' => $detalle->compra?->proveedor?->nombre ?: 'SIN PROVEEDOR',
                'cantidad' => $detalle->cantidad,
                'cantidad_vendida' => $cantidadVendida,
                'cantidad_baja' => $cantidadBaja,
                'saldo' => $saldo,
                'lote' => $detalle->lote,
                'fecha_vencimiento' => $detalle->fecha_vencimiento?->format('Y-m-d'),
                'precio' => $detalle->precio,
                'total' => $detalle->total,
                'estado' => $detalle->compra?->estado,
            ];
        });

        $ventas = VentaDetalle::with(['venta.paciente:id,nombre_completo'])
            ->where('producto_id', $producto->id)
            ->get()
            ->map(fn ($detalle) => [
                'tipo' => 'VENTA',
                'detalle_id' => $detalle->id,
                'compra_detalle_id' => $detalle->compra_detalle_id,
                'id' => $detalle->venta_id,
                'fecha_hora' => $detalle->venta?->fecha_hora,
                'documento' => 'Venta #'.$detalle->venta_id,
                'tercero' => $detalle->venta?->paciente?->nombre_completo
                    ?: ($detalle->venta?->cliente ?: 'SIN CLIENTE'),
                'cantidad' => $detalle->cantidad,
                'lote' => $detalle->lote,
                'fecha_vencimiento' => $detalle->fecha_vencimiento?->format('Y-m-d'),
                'precio' => $detalle->precio,
                'total' => $detalle->total,
                'estado' => $detalle->venta?->estado,
            ]);

        $bajas = BajaDetalle::with(['baja.user:id,name,username'])
            ->where('producto_id', $producto->id)
            ->get()
            ->map(fn ($detalle) => [
                'tipo' => 'BAJA',
                'id' => $detalle->baja_id,
                'fecha_hora' => $detalle->baja?->fecha_hora,
                'documento' => 'Baja #'.$detalle->baja_id,
                'tercero' => Baja::MOTIVOS[$detalle->motivo] ?? $detalle->motivo,
                'cantidad' => $detalle->cantidad,
                'lote' => $detalle->lote,
                'fecha_vencimiento' => $detalle->fecha_vencimiento?->format('Y-m-d'),
                'precio' => $detalle->precio,
                'total' => $detalle->total,
                'estado' => $detalle->baja?->estado,
            ]);

        return [
            'producto' => [...$producto->only(['id', 'codigo', 'nombre']), 'es_farmacia' => $producto->tipoProducto?->nombre === 'FARMACIA'],
            'movimientos' => $compras->concat($ventas)->concat($bajas)
                ->sortByDesc('fecha_hora')
                ->values(),
        ];
    }
}
