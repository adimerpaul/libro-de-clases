<?php

namespace App\Http\Controllers;

use App\Models\Reactivo;
use App\Services\ReactivoKardex;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReactivoKardexController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($this->datos($request));
    }

    public function pdf(Request $request)
    {
        $datos = $this->datos($request);
        $pdf = Pdf::loadView('reportes.reactivos-kardex', $datos)->setPaper('letter');
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(470, 770, 'Página {PAGE_NUM} / {PAGE_COUNT}', $font, 7);

        return $pdf->stream('kardex_reactivo_'.$datos['reactivo']->id.'_'.$datos['fecha_inicio'].'_'.$datos['fecha_fin'].'.pdf');
    }

    private function datos(Request $request): array
    {
        abort_unless($request->user()->hasPermissionTo('Ver Reactivos'), 403, 'No tiene permiso para consultar reactivos');
        $datos = $request->validate([
            'reactivo_id' => 'required|integer',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        return ReactivoKardex::datos(Reactivo::findOrFail($datos['reactivo_id']), $datos['fecha_inicio'], $datos['fecha_fin']);
    }
}
