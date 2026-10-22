<?php

namespace App\Http\Controllers\Docente;

use App\Models\Asignacion;
use App\Models\ObjetivoAprendizaje;
use Illuminate\Http\Request;

trait ValidaCargaDocente
{
    /** La asignación debe ser del docente y el OA de la misma asignatura y nivel del curso. */
    private function validarAsignacionYOa(Request $request, array $datos): void
    {
        $asignacion = Asignacion::with('curso')->find($datos['asignacion_id']);
        abort_unless($asignacion->docente_id === $request->user()->id, 403, 'Esa asignatura no está en su carga docente.');

        if (! empty($datos['objetivo_aprendizaje_id'])) {
            $oa = ObjetivoAprendizaje::find($datos['objetivo_aprendizaje_id']);
            if ($oa->asignatura_id !== $asignacion->asignatura_id || $oa->nivel !== $asignacion->curso->nivel) {
                abort(422, 'El OA no corresponde a la asignatura y nivel del curso.');
            }
        }
    }
}
