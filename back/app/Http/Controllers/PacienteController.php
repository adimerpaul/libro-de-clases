<?php

namespace App\Http\Controllers;

use App\Models\Internacion;
use App\Models\Paciente;
use App\Models\Venta;
use App\Services\CobroInternacion;
use App\Support\PdfTema;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, ['Ver Pacientes', 'Ver Ventas', 'Crear Ventas']);

        $q = $request->input('q', '');
        $tipoPaciente = $request->input('tipo_paciente', '');
        $estadoInternacion = $request->input('estado_internacion', '');
        $altaDesde = $request->input('alta_desde', '');
        $altaHasta = $request->input('alta_hasta', '');
        $perPage = (int) $request->input('per_page', 10);

        $query = Paciente::with(['latestInternacion', 'seguro'])->orderBy('nombre_completo');

        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre_completo', 'like', "%$q%")
                    ->orWhere('ci', 'like', "%$q%");
            });
        }

        if ($tipoPaciente) {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->where('tipo_paciente', $tipoPaciente));
        }

        if ($estadoInternacion === 'NO_INTERNADO') {
            $query->doesntHave('internaciones');
        } elseif ($estadoInternacion === 'INTERNADO') {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereNull('fecha_alta'));
        } elseif ($estadoInternacion === 'ALTA') {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereNotNull('fecha_alta'));
        }

        if ($altaDesde) {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereDate('fecha_alta', '>=', $altaDesde));
        }
        if ($altaHasta) {
            $query->whereHas('latestInternacion', fn ($sq) => $sq->whereDate('fecha_alta', '<=', $altaHasta));
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Internaciones del paciente, la más reciente primero.
     * Accesible también desde la venta (para ver si tiene una internación sin alta).
     */
    public function internaciones(Request $request, $id)
    {
        $this->req($request, ['Ver Internaciones', 'Ver Ventas', 'Crear Ventas']);

        $paciente = Paciente::findOrFail($id);

        $query = $paciente->internaciones()->with('seguro:id,nombre')->orderByDesc('fecha_ingreso')->orderByDesc('id');

        if ($request->boolean('abiertas')) {
            $query->whereNull('fecha_alta');
        }

        return response()->json($query->get());
    }

    public function show(Request $request, $id)
    {
        $this->req($request, 'Ver Pacientes');
        $paciente = Paciente::with(['seguro', 'internaciones.seguro:id,nombre', 'internaciones.pagadoPor:id,name', 'internaciones.items.producto:id,nombre', 'internaciones.items.user:id,name'])->findOrFail($id);

        return response()->json($paciente);
    }

    /**
     * Cobra las ventas pendientes y todas las internaciones con cargos por su importe exacto.
     */
    public function cobrarTodo(Request $request, $id)
    {
        $this->req($request, 'Crear Ventas');

        $request->validate([
            'tipo_pago' => 'nullable|string|max:30',
            'observacion' => 'nullable|string|max:255',
        ]);

        if (CierreCajaController::cierreDelDia($request->user()->id, now()->toDateString())) {
            abort(422, 'Su caja de hoy ya fue cerrada: no puede registrar más cobros hasta mañana');
        }

        $paciente = Paciente::findOrFail($id);
        $tipoPago = $request->tipo_pago ? mb_strtoupper($request->tipo_pago) : 'EFECTIVO';

        $resumen = DB::transaction(function () use ($paciente, $request, $tipoPago) {
            $usuario = $request->user();

            $pendientes = $this->ventasPendientes($paciente->id)->lockForUpdate()->get();
            $internaciones = $this->internacionesPorCobrar($paciente->id)->lockForUpdate()->get()
                ->filter(fn ($internacion) => CobroInternacion::total($internacion) > 0);
            if ($pendientes->isEmpty() && $internaciones->isEmpty()) {
                abort(422, 'El paciente no tiene ventas ni internaciones pendientes de cobro');
            }

            $totalVentas = 0;
            foreach ($pendientes as $venta) {
                $venta->update([
                    'cobrado_por_id' => $usuario->id,
                    'fecha_hora_cobro' => now(),
                    'tipo_pago' => $tipoPago,
                    'pago' => $venta->total,
                    'cambio' => 0,
                ]);
                $totalVentas += (float) $venta->total;
            }

            $totalInternaciones = 0;
            foreach ($internaciones as $internacion) {
                $venta = CobroInternacion::registrar($internacion, $usuario, $tipoPago, null, $request->observacion);
                $totalInternaciones += (float) $venta->total;
            }

            return [
                'internaciones_cobradas' => $internaciones->count(),
                'ventas_cobradas' => $pendientes->count(),
                'total_internaciones' => round($totalInternaciones, 2),
                'total_ventas' => round($totalVentas, 2),
                'total' => round($totalVentas + $totalInternaciones, 2),
            ];
        });

        return response()->json(array_merge(['message' => 'Cuenta del paciente cobrada'], $resumen));
    }

    /** Estado de cuenta: internaciones sin pagar y productos de farmacia pendientes. */
    public function estadoCuentaPdf(Request $request, $id)
    {
        $this->req($request, ['Ver Pacientes', 'Ver Ventas']);

        $paciente = Paciente::with('seguro:id,nombre')->findOrFail($id);

        $internaciones = $this->internacionesPorCobrar($paciente->id)
            ->with(['seguro:id,nombre', 'items.user:id,name'])
            ->get();

        $ventas = $this->ventasPendientes($paciente->id)
            ->with(['doctor:id,nombre', 'seguro:id,nombre', 'detalles:id,venta_id,nombre,lote,precio,cantidad,total'])
            ->orderBy('fecha_hora')
            ->get();

        $totalInternaciones = $internaciones->sum(fn ($internacion) => (float) $internacion->items->sum('total'));
        $totalVentas = (float) $ventas->sum('total');

        $pdf = Pdf::loadView('reportes.estado-cuenta', [
            'paciente' => $paciente,
            'internaciones' => $internaciones,
            'ventas' => $ventas,
            'totalInternaciones' => round($totalInternaciones, 2),
            'totalVentas' => round($totalVentas, 2),
            'total' => round($totalInternaciones + $totalVentas, 2),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('estado_cuenta_'.$paciente->id.'_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Estado de cuenta agrupado por categoría (tipo de producto padre): junta los
     * cargos de internaciones y ventas pendientes en LABORATORIO, FARMACIA, etc.
     */
    public function estadoCuentaCategoriaPdf(Request $request, $id)
    {
        $this->req($request, ['Ver Pacientes', 'Ver Ventas']);

        $paciente = Paciente::with('seguro:id,nombre')->findOrFail($id);
        $producto = fn (string $rel) => [
            $rel.'.producto:id,nombre,tipo_producto_id',
            $rel.'.producto.tipoProducto:id,tipo_producto_padre_id,nombre,color,es_laboratorio',
            $rel.'.producto.tipoProducto.padre:id,nombre,color,icono,es_laboratorio,orden',
        ];

        $internaciones = $this->internacionesPorCobrar($paciente->id)
            ->with($producto('items'))
            ->get();

        $ventas = $this->ventasPendientes($paciente->id)
            ->with(['detalles:id,venta_id,producto_id,nombre,lote,precio,cantidad,total', ...$producto('detalles')])
            ->orderBy('fecha_hora')
            ->get();

        $cargos = collect();
        foreach ($internaciones as $internacion) {
            foreach ($internacion->items as $item) {
                $cargos->push($this->cargo($item, 'Internación Nº '.str_pad($internacion->id, 6, '0', STR_PAD_LEFT), $internacion->fecha_ingreso ? Carbon::parse($internacion->fecha_ingreso)->format('d/m/Y') : null));
            }
        }
        foreach ($ventas as $venta) {
            foreach ($venta->detalles as $detalle) {
                $cargos->push($this->cargo($detalle, 'Venta Nº '.str_pad($venta->id, 6, '0', STR_PAD_LEFT), optional($venta->fecha_hora)->format('d/m/Y'), $detalle->lote));
            }
        }

        $grupos = $cargos
            ->groupBy(fn ($cargo) => $cargo['padre']?->id ?? 0)
            ->map(function ($items) {
                $padre = $items->first()['padre'];

                return [
                    'nombre' => $padre?->nombre ?? 'SIN CATEGORÍA',
                    'color' => PdfTema::color($padre?->color, '#607D8B'),
                    'icono' => $padre
                        ? PdfTema::iconoDePadre($padre->icono, $padre->nombre, (bool) $padre->es_laboratorio)
                        : 'etiqueta',
                    'orden' => $padre?->orden ?? PHP_INT_MAX,
                    'items' => $items->sortBy([['tipo', 'asc'], ['nombre', 'asc']])->values(),
                    'cantidad' => $items->count(),
                    'subtotal' => round((float) $items->sum('total'), 2),
                ];
            })
            ->sortBy([['orden', 'asc'], ['nombre', 'asc']])
            ->values();

        $pdf = Pdf::loadView('reportes.estado-cuenta-categoria', [
            'paciente' => $paciente,
            'grupos' => $grupos,
            'internaciones' => $internaciones->count(),
            'ventas' => $ventas->count(),
            'total' => round((float) $cargos->sum('total'), 2),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('estado_cuenta_categoria_'.$paciente->id.'_'.now()->format('Ymd_His').'.pdf');
    }

    /** Fila del estado de cuenta por categoría, venga de una internación o de una venta. */
    private function cargo($fila, string $origen, ?string $fecha, ?string $lote = null): array
    {
        $tipo = $fila->producto?->tipoProducto;

        return [
            'nombre' => $fila->nombre,
            'lote' => $lote,
            'tipo' => $tipo?->nombre,
            'tipo_color' => $tipo ? PdfTema::color($tipo->color) : null,
            'padre' => $tipo?->padre,
            'origen' => $origen,
            'fecha' => $fecha,
            'cantidad' => (float) $fila->cantidad,
            'precio' => (float) $fila->precio,
            'total' => (float) $fila->total,
        ];
    }

    /** Internaciones que todavía no se cobraron. */
    private function internacionesPorCobrar(int $pacienteId)
    {
        return Internacion::with('items')
            ->where('paciente_id', $pacienteId)
            ->whereNull('pagado_en')
            ->orderBy('fecha_ingreso')
            ->orderBy('id');
    }

    /** Ventas de farmacia dejadas en pendiente (los egresos de caja no son deuda del paciente). */
    private function ventasPendientes(int $pacienteId)
    {
        return Venta::where('paciente_id', $pacienteId)
            ->where('estado', 'PENDIENTE')
            ->whereNull('fecha_hora_cobro')
            ->where('tipo_movimiento', '<>', 'EGRESO');
    }

    public function store(Request $request)
    {
        $this->req($request, ['Crear Pacientes', 'Crear Ventas', 'Crear Solicitudes Laboratorio']);
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'sexo' => 'nullable|in:M,F',
            'seguro_id' => 'nullable|exists:seguros,id',
        ]);
        $paciente = Paciente::create($request->only(['seguro_id', 'nombre_completo', 'sexo', 'fecha_nacimiento', 'ci', 'estado', 'direccion', 'telefono']));

        return response()->json($paciente, 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Pacientes');
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'sexo' => 'nullable|in:M,F',
            'seguro_id' => 'nullable|exists:seguros,id',
        ]);
        $paciente = Paciente::findOrFail($id);
        $paciente->update($request->only(['seguro_id', 'nombre_completo', 'sexo', 'fecha_nacimiento', 'ci', 'estado', 'direccion', 'telefono']));

        return response()->json($paciente);
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Pacientes');
        Paciente::findOrFail($id)->delete();

        return response()->json(['message' => 'Paciente eliminado']);
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
