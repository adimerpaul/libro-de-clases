<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Instrumento;
use App\Models\Matricula;
use App\Support\EscalaNotas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Notas de un instrumento propio: el docente registra el puntaje de cada estudiante
 * del curso y la nota (1,0–7,0) se calcula con EscalaNotas. Las ve el apoderado.
 */
class NotasController extends Controller
{
    public function show(Request $request, Instrumento $instrumento)
    {
        $this->esPropio($request, $instrumento);

        return $this->planilla($instrumento);
    }

    /** notas: [{ estudiante_id, puntaje (null = borrar la nota), observacion? }] */
    public function update(Request $request, Instrumento $instrumento)
    {
        $this->esPropio($request, $instrumento);
        $maximo = $instrumento->puntajeTotal();
        abort_unless($maximo > 0, 422, 'El instrumento no tiene preguntas con puntaje: agréguelas antes de registrar notas.');

        $datos = $request->validate([
            'notas' => ['present', 'array'],
            'notas.*.estudiante_id' => ['required', 'integer', 'distinct'],
            'notas.*.puntaje' => ['nullable', 'numeric', 'min:0', 'max:'.$maximo],
            'notas.*.observacion' => ['nullable', 'string', 'max:300'],
        ], [], ['notas.*.puntaje' => 'puntaje']);

        // Solo estudiantes con matrícula activa en el curso del instrumento
        $nomina = $this->nomina($instrumento)->pluck('estudiante_id')->all();
        foreach ($datos['notas'] as $n) {
            abort_unless(in_array($n['estudiante_id'], $nomina, true), 422, 'Hay estudiantes que no están matriculados en este curso.');
        }

        DB::transaction(function () use ($instrumento, $datos, $maximo) {
            foreach ($datos['notas'] as $n) {
                $actual = $instrumento->calificaciones()->where('estudiante_id', $n['estudiante_id'])->first();
                if (! isset($n['puntaje'])) {
                    $actual?->delete();

                    continue;
                }
                $valores = [
                    'puntaje' => $n['puntaje'],
                    'nota' => EscalaNotas::nota((float) $n['puntaje'], $maximo, $instrumento->exigencia),
                    'observacion' => $n['observacion'] ?? null,
                ];
                $actual ? $actual->update($valores) : $instrumento->calificaciones()->create($valores + ['estudiante_id' => $n['estudiante_id']]);
            }
        });

        return $this->planilla($instrumento);
    }

    private function planilla(Instrumento $instrumento): array
    {
        $instrumento->load('asignacion.curso', 'asignacion.asignatura:id,nombre,color');
        $maximo = $instrumento->puntajeTotal();
        $notas = $instrumento->calificaciones()->get()->keyBy('estudiante_id');

        $estudiantes = $this->nomina($instrumento)->map(function (Matricula $m) use ($notas) {
            $c = $notas->get($m->estudiante_id);

            return [
                'id' => $m->estudiante_id,
                'numero_lista' => $m->numero_lista,
                'nombre' => $m->estudiante->nombre_completo,
                'puntaje' => $c?->puntaje,
                'nota' => $c?->nota,
                'observacion' => $c?->observacion,
            ];
        })->values();

        return [
            'instrumento' => [
                'id' => $instrumento->id,
                'nombre' => $instrumento->nombre,
                'tipo' => $instrumento->tipo,
                'fecha_aplicacion' => $instrumento->fecha_aplicacion?->format('Y-m-d'),
                'exigencia' => $instrumento->exigencia,
                'curso' => $instrumento->asignacion->curso->nombre,
                'asignatura' => $instrumento->asignacion->asignatura->nombre,
            ],
            'puntaje_total' => $maximo,
            'puntaje_aprobacion' => $maximo > 0 ? EscalaNotas::puntajeAprobacion($maximo, $instrumento->exigencia) : null,
            'promedio' => EscalaNotas::promedio($estudiantes->pluck('nota')),
            'estudiantes' => $estudiantes,
        ];
    }

    private function nomina(Instrumento $instrumento)
    {
        return Matricula::activas()->where('curso_id', $instrumento->asignacion->curso_id)
            ->with('estudiante')->orderBy('numero_lista')->get()->filter->estudiante->values();
    }

    private function esPropio(Request $request, Instrumento $instrumento): void
    {
        abort_unless($instrumento->docente_id === $request->user()->id, 403, 'Este instrumento es de otro docente.');
    }
}
