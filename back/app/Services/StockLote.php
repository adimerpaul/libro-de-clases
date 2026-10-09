<?php

namespace App\Services;

use App\Models\BajaDetalle;
use App\Models\CompraDetalle;
use App\Models\VentaDetalle;
use Illuminate\Support\Collection;

/**
 * Fuente única del saldo de un lote (`compra_detalles`).
 *
 *   existencia = comprado - vendido - dado de baja
 *
 * Vendido: ventas en estado distinto de ANULADO.
 * Dado de baja: bajas en estado ACTIVO.
 *
 * Los métodos *Sql() devuelven fragmentos para usar en whereRaw/selectRaw sobre
 * consultas que ya tienen `compra_detalles` en el FROM o en un JOIN.
 */
class StockLote
{
    public static function vendidoSql(string $tabla = 'compra_detalles'): string
    {
        return "(SELECT COALESCE(SUM(vd.cantidad), 0)
            FROM venta_detalles vd
            INNER JOIN ventas v ON v.id = vd.venta_id
            WHERE vd.compra_detalle_id = {$tabla}.id
              AND vd.deleted_at IS NULL
              AND v.deleted_at IS NULL
              AND v.estado != 'ANULADO')";
    }

    public static function bajadoSql(string $tabla = 'compra_detalles'): string
    {
        return "(SELECT COALESCE(SUM(bd.cantidad), 0)
            FROM baja_detalles bd
            INNER JOIN bajas b ON b.id = bd.baja_id
            WHERE bd.compra_detalle_id = {$tabla}.id
              AND bd.deleted_at IS NULL
              AND b.deleted_at IS NULL
              AND b.estado = 'ACTIVO')";
    }

    /** Saldo restante del lote, nunca negativo. */
    public static function existenciaSql(string $tabla = 'compra_detalles'): string
    {
        return "GREATEST({$tabla}.cantidad - ".self::vendidoSql($tabla).' - '.self::bajadoSql($tabla).', 0)';
    }

    /** Condición "al lote todavía le queda saldo". */
    public static function conSaldoSql(string $tabla = 'compra_detalles'): string
    {
        return "{$tabla}.cantidad > (".self::vendidoSql($tabla).' + '.self::bajadoSql($tabla).')';
    }

    /** Cantidades vendidas por lote, indexadas por compra_detalle_id. */
    public static function vendidoPorLote($ids): Collection
    {
        return VentaDetalle::query()
            ->whereIn('compra_detalle_id', $ids)
            ->whereHas('venta', fn ($query) => $query->where('estado', '<>', 'ANULADO'))
            ->selectRaw('compra_detalle_id, SUM(cantidad) as cantidad')
            ->groupBy('compra_detalle_id')
            ->pluck('cantidad', 'compra_detalle_id');
    }

    /** Cantidades dadas de baja por lote, indexadas por compra_detalle_id. */
    public static function bajadoPorLote($ids): Collection
    {
        return BajaDetalle::query()
            ->whereIn('compra_detalle_id', $ids)
            ->whereHas('baja', fn ($query) => $query->where('estado', 'ACTIVO'))
            ->selectRaw('compra_detalle_id, SUM(cantidad) as cantidad')
            ->groupBy('compra_detalle_id')
            ->pluck('cantidad', 'compra_detalle_id');
    }

    /**
     * Saldo actual de un lote concreto. Se consulta en el momento (no usa
     * agregados precargados) para que sirva dentro de una transacción con
     * lockForUpdate antes de descontar.
     */
    public static function disponibleDe(CompraDetalle $detalle): float
    {
        $vendido = (float) VentaDetalle::where('compra_detalle_id', $detalle->id)
            ->whereHas('venta', fn ($query) => $query->where('estado', '<>', 'ANULADO'))
            ->sum('cantidad');

        $bajado = (float) BajaDetalle::where('compra_detalle_id', $detalle->id)
            ->whereHas('baja', fn ($query) => $query->where('estado', 'ACTIVO'))
            ->sum('cantidad');

        return (float) $detalle->cantidad - $vendido - $bajado;
    }
}
