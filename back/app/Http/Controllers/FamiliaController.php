<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\VinculoApoderado;
use App\Support\ResumenEstudiante;
use Illuminate\Http\Request;

/**
 * Portal de familia ("familia.ver"): el apoderado solo ve a los estudiantes vinculados
 * a su cuenta (VinculoApoderado). Notas del año y asistencia según las marcas RFID.
 */
class FamiliaController extends Controller
{
    public function hijos(Request $request)
    {
        $anio = (int) now()->year;

        return $this->vinculos($request)->with('estudiante')->get()->filter->estudiante
            ->map(fn (VinculoApoderado $v) => [
                'id' => $v->estudiante_id,
                'nombre' => $v->estudiante->nombre_completo,
                'nombres' => $v->estudiante->nombres,
                'apellido' => $v->estudiante->apellido_paterno,
                'parentesco' => $v->parentesco,
                'curso' => Matricula::delAnio($v->estudiante_id, $anio)?->curso?->nombre,
            ])->values();
    }

    public function show(Request $request, int $estudiante)
    {
        $vinculo = $this->vinculos($request)->where('estudiante_id', $estudiante)->first();
        abort_unless($vinculo, 404, 'Ese estudiante no está vinculado a su cuenta.');

        $est = Estudiante::findOrFail($estudiante);
        $anio = (int) now()->year;
        $matricula = Matricula::delAnio($est->id, $anio)?->load('curso.profesorJefe:id,name,email');

        return [
            'estudiante' => ['id' => $est->id, 'nombre' => $est->nombre_completo, 'nombres' => $est->nombres, 'run' => $est->run],
            'parentesco' => $vinculo->parentesco,
            'curso' => $matricula ? [
                'nombre' => $matricula->curso->nombre,
                'anio' => $matricula->curso->anio,
                'numero_lista' => $matricula->numero_lista,
                'profesor_jefe' => $matricula->curso->profesorJefe?->name,
                'profesor_jefe_email' => $matricula->curso->profesorJefe?->email,
            ] : null,
            'notas' => ResumenEstudiante::notas($est->id, $anio),
            'asistencia' => ResumenEstudiante::asistencia($est->id),
        ];
    }

    private function vinculos(Request $request)
    {
        return VinculoApoderado::where('user_id', $request->user()->id);
    }
}
