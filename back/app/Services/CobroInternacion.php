<?php

namespace App\Services;

use App\Models\Internacion;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;

/**
 * Cobro total de una internación: genera la venta con todos sus cargos y
 * congela la internación. Se utiliza en el cobro individual de internaciones.
 */
class CobroInternacion
{
    public static function total(Internacion $internacion): float
    {
        return round((float) $internacion->items->sum('total'), 2);
    }

    /**
     * @param  float|null  $pago  Monto entregado; si es null se cobra el total exacto.
     */
    public static function registrar(
        Internacion $internacion,
        User $usuario,
        string $tipoPago = 'EFECTIVO',
        ?float $pago = null,
        ?string $observacion = null
    ): Venta {
        if ($internacion->pagado_en) {
            abort(422, 'La internación #'.$internacion->id.' ya fue pagada');
        }
        if ($internacion->items->isEmpty()) {
            abort(422, 'La internación #'.$internacion->id.' no tiene cargos que cobrar');
        }

        $total = self::total($internacion);
        $pago = $pago !== null ? round($pago, 2) : $total;

        if ($pago < $total) {
            abort(422, 'El pago no puede ser menor al total de la internación');
        }

        $tipoPago = mb_strtoupper($tipoPago);

        $venta = Venta::create([
            'user_id' => $usuario->id,
            'paciente_id' => $internacion->paciente_id,
            'seguro_id' => $internacion->seguro_id,
            'fecha_hora' => now(),
            'tipo_pago' => $tipoPago,
            'comentario' => 'Pago total de la internación #'.$internacion->id,
            'estado' => 'ACTIVO',
            'total' => $total,
            'pago' => $pago,
            'cambio' => round($pago - $total, 2),
        ]);

        // Los cargos viajan como ítems sueltos: la internación no mueve lotes,
        // así que la venta no debe descontar stock de farmacia.
        foreach ($internacion->items as $item) {
            VentaDetalle::create([
                'venta_id' => $venta->id,
                'producto_id' => null,
                'nombre' => $item->nombre,
                'precio' => $item->precio,
                'cantidad' => $item->cantidad,
                'total' => $item->total,
            ]);
        }

        $internacion->update([
            'pagado_en' => now(),
            'pagado_por_id' => $usuario->id,
            'venta_id' => $venta->id,
            'monto_pagado' => $total,
            'pago_tipo' => $tipoPago,
            'pago_observacion' => $observacion ?: null,
        ]);

        return $venta;
    }
}
