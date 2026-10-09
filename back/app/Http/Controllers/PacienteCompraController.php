<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Venta;
use Illuminate\Http\Request;

/**
 * Compras de un paciente: buscador de pacientes, resumen acumulado
 * (total gastado, cantidad de compras, primera y última) y el historial
 * paginado con el detalle de cada venta. Las ventas anuladas no cuentan.
 */
class PacienteCompraController extends Controller
{
    /** Buscador de pacientes por nombre o CI, con cuántas compras tiene cada uno. */
    public function pacientes(Request $request)
    {
        $this->req($request);

        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $pacientes = Paciente::query()
            ->where(fn ($query) => $query->where('nombre_completo', 'like', "%{$q}%")->orWhere('ci', 'like', "%{$q}%"))
            ->withCount(['ventas as compras_count' => fn ($query) => $this->soloCompras($query)])
            ->orderByDesc('compras_count')
            ->orderBy('nombre_completo')
            ->limit(20)
            ->get(['id', 'nombre_completo', 'ci'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre_completo' => $p->nombre_completo,
                'ci' => $p->ci,
                'compras_count' => $p->compras_count,
            ]);

        return response()->json($pacientes);
    }

    /** Resumen de compras del paciente y su historial paginado. */
    public function show(Request $request, $pacienteId)
    {
        $this->req($request);

        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $paciente = Paciente::with('seguro:id,nombre')
            ->findOrFail($pacienteId, ['id', 'seguro_id', 'nombre_completo', 'ci', 'telefono', 'fecha_nacimiento']);

        $base = $this->soloCompras(Venta::query()->where('paciente_id', $paciente->id));

        // Resumen de toda la vida del paciente (no depende del filtro de fechas)
        $resumen = (clone $base)
            ->selectRaw('COUNT(*) as compras, COALESCE(SUM(total), 0) as total, MIN(fecha_hora) as primera, MAX(fecha_hora) as ultima')
            ->first();
        $pendiente = (float) (clone $base)->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->sum('total');

        $query = clone $base;
        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->whereDate('fecha_hora', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->whereDate('fecha_hora', '<=', $fechaFin);
        }
        $totalPeriodo = (float) (clone $query)->sum('total');

        $ventas = $query
            ->with(['seguro:id,nombre', 'user:id,name', 'detalles:id,venta_id,nombre,cantidad,precio,total'])
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 10), [
                'id', 'user_id', 'seguro_id', 'fecha_hora', 'fecha_hora_cobro', 'tipo_pago', 'estado', 'total',
            ]);

        return response()->json([
            'paciente' => [
                'id' => $paciente->id,
                'nombre_completo' => $paciente->nombre_completo,
                'ci' => $paciente->ci,
                'telefono' => $paciente->telefono,
                'edad' => $paciente->edad,
                'seguro' => $paciente->seguro?->nombre,
            ],
            'resumen' => [
                'compras' => (int) $resumen->compras,
                'total' => round((float) $resumen->total, 2),
                'pendiente' => round($pendiente, 2),
                'primera' => $resumen->primera,
                'ultima' => $resumen->ultima,
            ],
            'total_periodo' => round($totalPeriodo, 2),
            'ventas' => $ventas,
        ]);
    }

    /** Solo ventas reales (no gastos de caja) que no fueron anuladas. */
    private function soloCompras($query)
    {
        return $query->where('tipo_movimiento', 'INGRESO')->where('estado', '!=', 'ANULADO');
    }

    private function req(Request $request): void
    {
        if (! $request->user()->hasPermissionTo('Ver Compras Pacientes')) {
            abort(403, 'No tiene permiso para realizar esta acción');
        }
    }
}
