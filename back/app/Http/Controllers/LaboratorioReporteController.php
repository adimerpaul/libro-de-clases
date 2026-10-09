<?php

namespace App\Http\Controllers;

use App\Exports\LaboratorioReporteExport;
use App\Models\SolicitudLaboratorioItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaboratorioReporteController extends Controller
{
    public function index(Request $request)
    {
        [$query, $filters] = $this->query($request);
        $resumen = [
            'pruebas' => (clone $query)->count(),
            'solicitudes' => (clone $query)->distinct()->count('solicitude_id'),
            'importe' => (float) (clone $query)->sum('precio'),
        ];

        return response()->json([
            ...$query->paginate((int) $request->input('per_page', 25))->toArray(),
            'resumen' => $resumen,
            'filtros' => $filters,
        ]);
    }

    public function excel(Request $request)
    {
        [$query, $filters] = $this->query($request);

        return Excel::download(new LaboratorioReporteExport($query), "laboratorios_{$filters['desde']}_{$filters['hasta']}.xlsx");
    }

    public function pdf(Request $request)
    {
        [$query, $filters] = $this->query($request);

        return Pdf::loadView('reportes.laboratorios-general', [
            'items' => $query->get(), 'filters' => $filters,
        ])->setPaper('letter', 'landscape')->download("laboratorios_{$filters['desde']}_{$filters['hasta']}.pdf");
    }

    private function query(Request $request): array
    {
        abort_unless($request->user()->hasPermissionTo('Ver Solicitudes Laboratorio'), 403, 'No tiene permiso para realizar esta acción');
        $request->merge([
            'desde' => $request->input('desde', now()->subMonthNoOverflow()->startOfMonth()->toDateString()),
            'hasta' => $request->input('hasta', now()->subMonthNoOverflow()->endOfMonth()->toDateString()),
        ]);
        $filters = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $query = SolicitudLaboratorioItem::query()
            ->with(['solicitude.paciente:id,nombre_completo,ci'])
            ->whereHas('solicitude', fn ($solicitud) => $solicitud
                ->whereBetween('fecha_solicitud', [$filters['desde'], $filters['hasta']]))
            ->when(trim($filters['nombre'] ?? ''), fn ($items, $nombre) => $items
                ->where('producto_nombre', 'like', '%'.$nombre.'%'))
            ->orderByDesc('solicitude_id')->orderBy('orden')->orderBy('id');

        return [$query, $filters];
    }
}
