<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Nómina de cada curso: matricular, cambiar de curso, retirar y numerar la lista.
 * Regla: un estudiante tiene a lo más una matrícula activa por año escolar.
 */
class MatriculaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['curso_id' => ['required', 'integer']]);

        return Matricula::with('estudiante')
            ->where('curso_id', $request->curso_id)
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->numero_lista ?? PHP_INT_MAX) <=> ($b->numero_lista ?? PHP_INT_MAX),
                fn ($a, $b) => strcmp($a->estudiante->nombre_completo, $b->estudiante->nombre_completo),
            ])
            ->values();
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'estudiante_id' => ['required', 'integer', 'exists:estudiantes,id,deleted_at,NULL'],
            'curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL'],
            'fecha_matricula' => ['nullable', 'date'],
            'numero_lista' => ['nullable', 'integer', 'between:1,99'],
        ], [], ['estudiante_id' => 'estudiante', 'curso_id' => 'curso']);

        $curso = Curso::find($datos['curso_id']);
        $this->sinOtraActiva($datos['estudiante_id'], $curso->anio);

        $matricula = Matricula::create($datos + [
            'fecha_matricula' => $datos['fecha_matricula'] ?? now()->toDateString(),
            'numero_lista' => $datos['numero_lista'] ?? Matricula::siguienteNumero($curso->id),
        ]);

        return response()->json($matricula->load('estudiante'), 201);
    }

    /** Cambio de curso (mismo año), número de lista, retiro o traslado. */
    public function update(Request $request, Matricula $matricula)
    {
        $datos = $request->validate([
            'curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL'],
            'numero_lista' => ['nullable', 'integer', 'between:1,99'],
            'fecha_matricula' => ['required', 'date'],
            'estado' => ['required', Rule::in(Matricula::ESTADOS)],
            'fecha_retiro' => ['nullable', 'date', 'after_or_equal:fecha_matricula'],
            'motivo_retiro' => ['nullable', 'string', 'max:200'],
        ], [], ['curso_id' => 'curso', 'fecha_retiro' => 'fecha de retiro']);

        $destino = Curso::find($datos['curso_id']);
        if ($destino->id !== $matricula->curso_id) {
            abort_if($destino->anio !== $matricula->curso->anio, 422, 'El cambio de curso debe ser dentro del mismo año escolar. Para otro año, matricule en el curso de ese año.');
            // En el curso nuevo queda al final de la lista
            $datos['numero_lista'] = Matricula::siguienteNumero($destino->id);
        }

        if ($datos['estado'] === 'activa') {
            $this->sinOtraActiva($matricula->estudiante_id, $destino->anio, $matricula->id);
            $datos['fecha_retiro'] = null;
            $datos['motivo_retiro'] = null;
        } else {
            $datos['fecha_retiro'] ??= now()->toDateString();
        }

        $matricula->update($datos);

        return $matricula->load('estudiante');
    }

    public function destroy(Matricula $matricula)
    {
        $matricula->delete();

        return response()->json(['message' => 'Matrícula eliminada']);
    }

    /**
     * Copia la nómina activa de un curso a otro (p. ej. 1° Medio A 2026 → 2° Medio A 2027).
     * Omite a quien ya tiene matrícula activa en el año del curso destino. No decide promoción:
     * es una carga masiva que luego se ajusta a mano.
     */
    public function copiar(Request $request)
    {
        $datos = $request->validate([
            'desde_curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL'],
            'hacia_curso_id' => ['required', 'integer', 'different:desde_curso_id', 'exists:cursos,id,deleted_at,NULL'],
        ], [], ['desde_curso_id' => 'curso de origen', 'hacia_curso_id' => 'curso de destino']);

        $destino = Curso::find($datos['hacia_curso_id']);
        $origen = Matricula::with('estudiante')->activas()->where('curso_id', $datos['desde_curso_id'])->get()
            ->sortBy(fn ($m) => $m->estudiante->nombre_completo);

        $copiados = 0;
        $omitidos = [];

        DB::transaction(function () use ($origen, $destino, &$copiados, &$omitidos) {
            $numero = Matricula::siguienteNumero($destino->id);
            foreach ($origen as $m) {
                if (Matricula::delAnio($m->estudiante_id, $destino->anio)) {
                    $omitidos[] = $m->estudiante->nombre_completo;

                    continue;
                }
                Matricula::create([
                    'estudiante_id' => $m->estudiante_id,
                    'curso_id' => $destino->id,
                    'numero_lista' => $numero++,
                    'fecha_matricula' => now()->toDateString(),
                ]);
                $copiados++;
            }
        });

        return [
            'copiados' => $copiados,
            'omitidos' => $omitidos,
            'message' => "{$copiados} estudiante(s) matriculado(s) en {$destino->nombre} {$destino->anio}"
                .($omitidos ? ' · '.count($omitidos).' ya tenían matrícula ese año' : ''),
        ];
    }

    /** Numera la lista en orden alfabético: primero las matrículas activas, luego retiros y traslados. */
    public function renumerar(Request $request)
    {
        $datos = $request->validate(['curso_id' => ['required', 'integer', 'exists:cursos,id,deleted_at,NULL']]);

        $matriculas = Matricula::with('estudiante')->where('curso_id', $datos['curso_id'])->get()
            ->sortBy([
                fn ($a, $b) => ($b->estado === 'activa') <=> ($a->estado === 'activa'),
                fn ($a, $b) => strcmp($a->estudiante->nombre_completo, $b->estudiante->nombre_completo),
            ])
            ->values();

        DB::transaction(fn () => $matriculas->each(fn ($m, $i) => $m->update(['numero_lista' => $i + 1])));

        return ['message' => 'Lista numerada en orden alfabético'];
    }

    private function sinOtraActiva(int $estudianteId, int $anio, ?int $excepto = null): void
    {
        $otra = Matricula::delAnio($estudianteId, $anio, $excepto);
        if ($otra) {
            abort(422, "El estudiante ya tiene matrícula activa en {$otra->curso->nombre} {$anio}. Use «Cambiar de curso» o retírelo primero.");
        }
    }
}
