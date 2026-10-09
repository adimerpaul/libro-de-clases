<?php

namespace App\Services;

use App\Models\Reactivo;
use App\Models\SolicitudLaboratorioItem;
use Carbon\Carbon;

class ReactivoKardex
{
    /** Kardex del reactivo entre dos fechas (Y-m-d), ambas incluidas. */
    public static function datos(Reactivo $reactivo, string $fechaInicio, string $fechaFin): array
    {
        $desde = Carbon::createFromFormat('!Y-m-d', $fechaInicio);
        $hasta = Carbon::createFromFormat('!Y-m-d', $fechaFin);
        $cantidades = $reactivo->servicios()->pluck('cantidad', 'producto_id');
        $items = SolicitudLaboratorioItem::with('solicitude.user:id,name')
            ->whereIn('producto_id', $cantidades->keys())
            ->whereHas('solicitude', fn ($query) => $query
                ->whereBetween('fecha_solicitud', [$desde->toDateString(), $hasta->toDateString()])
                ->where('estado', '<>', 'ANULADO'))
            ->where(function ($query) {
                $query->whereHas('solicitude', fn ($solicitud) => $solicitud->whereIn('estado', ['ANALIZADO', 'FINALIZADO']))
                    ->orWhereHas('resultados', fn ($resultado) => $resultado->whereNotNull('valor')->whereRaw("TRIM(valor) <> ''"));
            })->get();

        $movimientos = $items->groupBy(fn ($item) => $item->solicitude->fecha_solicitud->toDateString().'|'.$item->solicitude->user_id)
            ->map(function ($grupo) use ($cantidades) {
                $solicitud = $grupo->first()->solicitude;

                return [
                    'fecha' => $solicitud->fecha_solicitud->toDateString(),
                    'ingreso' => null,
                    'marca' => null,
                    'lote' => null,
                    'vencimiento' => null,
                    'salida' => round($grupo->sum(fn ($item) => (float) $cantidades[$item->producto_id]), 4),
                    'saldo' => null,
                    'responsable' => $solicitud->user?->name,
                    'observaciones' => $grupo->groupBy('producto_nombre')->map(fn ($pruebas, $nombre) => $nombre.' ('.$pruebas->count().')')->implode('; '),
                    'pruebas' => $grupo->count(),
                ];
            })->sortBy('fecha')->values();

        return [
            'reactivo' => $reactivo,
            'fecha_inicio' => $desde->toDateString(),
            'fecha_fin' => $hasta->toDateString(),
            'periodo' => $desde->format('d/m/Y').' al '.$hasta->format('d/m/Y'),
            'movimientos' => $movimientos,
            'cantidad_pruebas' => $items->count(),
            'saldo_inicial' => null,
            'total_ingresos' => null,
            'total_salidas' => round($movimientos->sum('salida'), 4),
            'saldo_final' => null,
            'nota' => 'Consumo calculado con la cantidad por prueba configurada actualmente. Incluye pruebas con resultados o solicitudes ANALIZADO/FINALIZADO, por fecha de solicitud. Responsable: usuario que registró la solicitud. Ingresos, lotes y saldos: sin historial registrado; no se modifica el stock.',
        ];
    }
}
