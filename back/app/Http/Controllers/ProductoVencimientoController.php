<?php

namespace App\Http\Controllers;

use App\Models\CompraDetalle;
use App\Services\StockLote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductoVencimientoController extends Controller
{
    public function porVencer(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('Ver Productos por Vencer'), 403);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'valor' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'unidad' => ['nullable', 'in:DIAS,MESES,ANIOS'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $desde = Carbon::today();
        $valor = (int) ($data['valor'] ?? 30);
        $hasta = match ($data['unidad'] ?? 'DIAS') {
            'MESES' => $desde->copy()->addMonths($valor),
            'ANIOS' => $desde->copy()->addYears($valor),
            default => $desde->copy()->addDays($valor),
        };

        return $this->listar(
            $request,
            fn (Builder $query) => $query
                ->whereDate('fecha_vencimiento', '>=', $desde)
                ->whereDate('fecha_vencimiento', '<=', $hasta),
            $desde
        );
    }

    public function vencidos(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('Ver Productos Vencidos'), 403);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'hasta' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $hasta = isset($data['hasta']) ? Carbon::parse($data['hasta']) : Carbon::today();

        return $this->listar(
            $request,
            fn (Builder $query) => $query->whereDate('fecha_vencimiento', '<', $hasta),
            Carbon::today()
        );
    }

    private function listar(Request $request, callable $filtroFecha, Carbon $hoy)
    {
        $query = CompraDetalle::query()
            ->select('compra_detalles.*')
            ->with([
                'producto:id,codigo,nombre,unidad_id,tipo_producto_id',
                'producto.unidad:id,nombre',
                'compra:id,user_id,proveedor_id,fecha_hora,nro_factura,estado',
                'compra.user:id,name,username',
                'compra.proveedor:id,nombre',
            ])
            ->withSum([
                'ventaDetalles as cantidad_vendida' => fn (Builder $venta) => $venta
                    ->whereHas('venta', fn (Builder $v) => $v->where('estado', '!=', 'ANULADO')),
            ], 'cantidad')
            ->withSum([
                'bajaDetalles as cantidad_baja' => fn (Builder $detalle) => $detalle
                    ->whereHas('baja', fn (Builder $b) => $b->where('estado', 'ACTIVO')),
            ], 'cantidad')
            ->whereNotNull('fecha_vencimiento')
            ->whereHas('compra', fn (Builder $compra) => $compra->where('estado', 'ACTIVO'))
            ->whereHas('producto.tipoProducto', fn (Builder $tipo) => $tipo->whereRaw('UPPER(nombre) = ?', ['FARMACIA']))
            // Solo lotes con saldo: descuenta ventas vigentes y bajas activas.
            ->whereRaw(StockLote::conSaldoSql());

        $filtroFecha($query);

        if ($request->filled('q')) {
            $buscar = '%'.$request->string('q')->trim().'%';
            $query->where(function (Builder $filtro) use ($buscar) {
                $filtro
                    ->where('compra_detalles.lote', 'like', $buscar)
                    ->orWhereHas('producto', fn (Builder $producto) => $producto
                        ->where('nombre', 'like', $buscar)
                        ->orWhere('codigo', 'like', $buscar))
                    ->orWhereHas('compra', fn (Builder $compra) => $compra
                        ->where('nro_factura', 'like', $buscar)
                        ->orWhereHas('proveedor', fn (Builder $proveedor) => $proveedor->where('nombre', 'like', $buscar)));
            });
        }

        $paginator = $query
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->paginate((int) $request->input('per_page', 15));

        $paginator->through(function (CompraDetalle $detalle) use ($hoy) {
            $vendida = (float) ($detalle->cantidad_vendida ?? 0);
            $baja = (float) ($detalle->cantidad_baja ?? 0);
            $detalle->setAttribute('cantidad_vendida', $vendida);
            $detalle->setAttribute('cantidad_baja', $baja);
            $detalle->setAttribute('existencia', max(0, (float) $detalle->cantidad - $vendida - $baja));
            $detalle->setAttribute('dias_vencimiento', $hoy->diffInDays($detalle->fecha_vencimiento, false));

            return $detalle;
        });

        return response()->json($paginator);
    }
}
