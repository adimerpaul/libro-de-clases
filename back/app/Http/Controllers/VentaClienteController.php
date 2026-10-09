<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Venta;
use Illuminate\Http\Request;

/**
 * Historial de compras de un cliente, para facturar.
 *
 * Solo exige 'Ver Ventas Clientes' y siempre filtra por un cliente: quien lo
 * usa (contabilidad) nunca ve el listado general ni los totales de ventas.
 * Un cliente es un paciente registrado o el nombre libre escrito en la venta.
 */
class VentaClienteController extends Controller
{
    /** Buscador de clientes: pacientes y nombres libres que tienen ventas. */
    public function clientes(Request $request)
    {
        $this->req($request);

        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $pacientes = Paciente::query()
            ->where(fn ($query) => $query->where('nombre_completo', 'like', "%{$q}%")->orWhere('ci', 'like', "%{$q}%"))
            ->whereIn('id', Venta::select('paciente_id')->whereNotNull('paciente_id'))
            ->orderBy('nombre_completo')
            ->limit(20)
            ->get(['id', 'nombre_completo', 'ci'])
            ->map(fn ($p) => [
                'key' => 'P'.$p->id,
                'tipo' => 'PACIENTE',
                'paciente_id' => $p->id,
                'nombre' => $p->nombre_completo,
                'ci' => $p->ci,
            ]);

        $libres = Venta::query()
            ->whereNull('paciente_id')
            ->where('cliente', 'like', "%{$q}%")
            ->distinct()
            ->orderBy('cliente')
            ->limit(10)
            ->pluck('cliente')
            ->map(fn ($nombre) => [
                'key' => 'C'.$nombre,
                'tipo' => 'CLIENTE',
                'cliente' => $nombre,
                'nombre' => $nombre,
                'ci' => null,
            ]);

        return response()->json($pacientes->concat($libres)->values());
    }

    /** Ventas (no anuladas) de un cliente entre dos fechas, con su detalle. */
    public function index(Request $request)
    {
        $this->req($request);

        $request->validate([
            'paciente_id' => 'nullable|integer|required_without:cliente',
            'cliente' => 'nullable|string|max:255|required_without:paciente_id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $query = Venta::query()
            ->where('tipo_movimiento', 'INGRESO')
            ->where('estado', '!=', 'ANULADO');

        if ($request->filled('paciente_id')) {
            $query->where('paciente_id', $request->paciente_id);
        } else {
            $query->whereNull('paciente_id')->where('cliente', $request->cliente);
        }
        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->whereDate('fecha_hora', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->whereDate('fecha_hora', '<=', $fechaFin);
        }

        $total = (float) (clone $query)->sum('total');

        $ventas = $query
            ->with(['seguro:id,nombre', 'detalles:id,venta_id,nombre,cantidad,precio,total'])
            ->orderByDesc('fecha_hora')
            ->paginate(min((int) $request->input('per_page', 20), 100), [
                'id', 'paciente_id', 'seguro_id', 'cliente', 'fecha_hora', 'fecha_hora_cobro', 'tipo_pago', 'estado', 'total',
            ]);

        return response()->json([
            'ventas' => $ventas,
            'total' => round($total, 2),
        ]);
    }

    private function req(Request $request): void
    {
        if (! $request->user()->hasPermissionTo('Ver Ventas Clientes')) {
            abort(403, 'No tiene permiso para realizar esta acción');
        }
    }
}
