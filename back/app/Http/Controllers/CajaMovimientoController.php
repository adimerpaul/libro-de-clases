<?php

namespace App\Http\Controllers;

use App\Exports\CajaMovimientosExport;
use App\Models\CajaMovimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CajaMovimientoController extends Controller
{
    private const CAJAS = ['ADMINISTRATIVA', 'GENERAL'];

    private const TIPOS = ['INGRESO', 'GASTO'];

    public function index(Request $request)
    {
        [$caja, $tipo] = $this->contexto($request);
        $this->autorizar($request, 'Ver', $caja);
        $this->validarRango($request);

        $query = CajaMovimiento::with(['user:id,name,username', 'anuladoPor:id,name,username'])
            ->where('caja', $caja)
            ->where('tipo', $tipo)
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('concepto', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%")
                    ->orWhere('beneficiario', 'like', "%{$q}%")
                    ->orWhere('documento', 'like', "%{$q}%");
            });
        }
        if ($desde = $request->input('desde')) {
            $query->whereDate('fecha_hora', '>=', $desde);
        }
        if ($hasta = $request->input('hasta')) {
            $query->whereDate('fecha_hora', '<=', $hasta);
        }

        $total = (clone $query)->where('estado', 'ACTIVO')->sum('importe');
        $paginador = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'movimientos' => $paginador,
            'resumen' => ['cantidad' => $paginador->total(), 'total' => round((float) $total, 2)],
        ]);
    }

    /**
     * Reporte por fechas de una caja: ingresos y gastos del rango, con su saldo.
     * A diferencia de index(), no se acota a un solo tipo: el reporte es de ambos.
     */
    public function reporte(Request $request)
    {
        return response()->json($this->datosReporte($request));
    }

    public function reportePdf(Request $request)
    {
        $datos = $this->datosReporte($request);

        $pdf = Pdf::loadView('reportes.caja-movimientos', $datos)->setPaper('letter');

        return $pdf->stream($this->nombreArchivo($datos, 'pdf'));
    }

    public function reporteExcel(Request $request)
    {
        $datos = $this->datosReporte($request);

        return Excel::download(
            new CajaMovimientosExport($datos),
            $this->nombreArchivo($datos, 'xlsx')
        );
    }

    /** Movimientos y totales del rango pedido, para las tres salidas del reporte. */
    private function datosReporte(Request $request): array
    {
        $caja = mb_strtoupper((string) $request->input('caja'));
        abort_unless(in_array($caja, self::CAJAS, true), 422, 'Caja no válida');
        $this->autorizar($request, 'Ver', $caja);
        $this->validarRango($request);

        $desde = Carbon::parse($request->input('desde') ?: now()->toDateString())->startOfDay();
        $hasta = Carbon::parse($request->input('hasta') ?: now()->toDateString())->endOfDay();

        $movimientos = CajaMovimiento::with('user:id,name,username')
            ->where('caja', $caja)
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->orderBy('fecha_hora')
            ->orderBy('id')
            ->get();

        $ingresos = $movimientos->where('tipo', 'INGRESO')->values();
        $gastos = $movimientos->where('tipo', 'GASTO')->values();

        // Los anulados se listan, pero no suman: el saldo es solo de los activos.
        $totalIngresos = round((float) $ingresos->where('estado', 'ACTIVO')->sum('importe'), 2);
        $totalGastos = round((float) $gastos->where('estado', 'ACTIVO')->sum('importe'), 2);

        return [
            'caja' => $caja,
            'titulo_caja' => $caja === 'GENERAL' ? 'Caja General' : 'Caja Administrativa',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'resumen' => [
                'cantidad_ingresos' => $ingresos->count(),
                'cantidad_gastos' => $gastos->count(),
                'total_ingresos' => $totalIngresos,
                'total_gastos' => $totalGastos,
                'saldo' => round($totalIngresos - $totalGastos, 2),
            ],
        ];
    }

    private function validarRango(Request $request): void
    {
        $request->validate([
            'desde' => 'nullable|required_with:hasta|date_format:Y-m-d',
            'hasta' => 'nullable|required_with:desde|date_format:Y-m-d|after_or_equal:desde',
        ]);
    }

    private function nombreArchivo(array $datos, string $extension): string
    {
        return 'caja_'.mb_strtolower($datos['caja']).'_'.$datos['desde'].'_a_'.$datos['hasta'].'.'.$extension;
    }

    public function store(Request $request)
    {
        [$caja, $tipo] = $this->contexto($request);
        $this->autorizar($request, 'Crear', $caja);
        $datos = $this->validar($request);

        $movimiento = CajaMovimiento::create([
            ...$datos,
            'user_id' => $request->user()->id,
            'caja' => $caja,
            'tipo' => $tipo,
            'fecha_hora' => $datos['fecha_hora'] ?? now(),
        ]);

        return response()->json($movimiento->load('user:id,name,username'), 201);
    }

    public function update(Request $request, CajaMovimiento $cajaMovimiento)
    {
        $this->autorizar($request, 'Editar', $cajaMovimiento->caja);
        abort_if($cajaMovimiento->estado === 'ANULADO', 422, 'Un movimiento anulado no puede modificarse');
        $cajaMovimiento->update($this->validar($request));

        return response()->json($cajaMovimiento->load('user:id,name,username'));
    }

    public function anular(Request $request, CajaMovimiento $cajaMovimiento)
    {
        $this->autorizar($request, 'Anular', $cajaMovimiento->caja);
        abort_if($cajaMovimiento->estado === 'ANULADO', 422, 'El movimiento ya está anulado');
        $datos = $request->validate(['motivo_anulacion' => 'required|string|max:500']);
        $cajaMovimiento->update([
            'estado' => 'ANULADO',
            'anulado_por_id' => $request->user()->id,
            'anulado_en' => now(),
            'motivo_anulacion' => $datos['motivo_anulacion'],
        ]);

        return response()->json([
            'message' => 'Movimiento anulado',
            'movimiento' => $cajaMovimiento->load(['user:id,name,username', 'anuladoPor:id,name,username']),
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'fecha_hora' => 'nullable|date',
            'categoria' => 'nullable|string|max:100',
            'concepto' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'beneficiario' => 'nullable|string|max:255',
            'documento' => 'nullable|string|max:100',
            'importe' => 'required|numeric|gt:0|max:999999999999.99',
        ]);
    }

    private function contexto(Request $request): array
    {
        $caja = mb_strtoupper((string) $request->input('caja'));
        $tipo = mb_strtoupper((string) $request->input('tipo'));
        abort_unless(in_array($caja, self::CAJAS, true), 422, 'Caja no válida');
        abort_unless(in_array($tipo, self::TIPOS, true), 422, 'Tipo de movimiento no válido');

        return [$caja, $tipo];
    }

    private function autorizar(Request $request, string $accion, string $caja): void
    {
        $nombre = $caja === 'ADMINISTRATIVA' ? 'Caja Administrativa' : 'Caja General';
        abort_unless($request->user()->can("{$accion} {$nombre}"), 403, 'No tiene permiso para realizar esta acción');
    }
}
