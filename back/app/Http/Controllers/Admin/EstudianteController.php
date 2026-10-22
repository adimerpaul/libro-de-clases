<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Rules\RunValido;
use App\Support\Run;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Fichas de estudiantes, con su matrícula del año consultado. */
class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $delAnio = fn ($q) => $q->whereHas('curso', fn ($c) => $c->where('anio', $anio));

        $estudiantes = Estudiante::with(['matriculas' => fn ($q) => $delAnio($q)->with('curso')->orderByRaw("estado = 'activa' desc")])
            ->when($request->curso_id, fn ($q, $id) => $q->whereHas('matriculas', fn ($m) => $m->where('curso_id', $id)))
            // Sin matrícula activa en el año: candidatos para matricular
            ->when($request->boolean('sin_matricula'), fn ($q) => $q->whereDoesntHave('matriculas', fn ($m) => $delAnio($m)->activas()))
            ->when($request->q, fn ($q, $texto) => $q->where(fn ($w) => $w
                ->where('nombres', 'like', "%{$texto}%")
                ->orWhere('apellido_paterno', 'like', "%{$texto}%")
                ->orWhere('apellido_materno', 'like', "%{$texto}%")
                ->orWhere('run', 'like', '%'.preg_replace('/[^0-9kK]/', '', $texto).'%')))
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombres')
            ->get();

        return $estudiantes->map(fn (Estudiante $e) => $this->datos($e));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $cursoId = $request->validate(['curso_id' => ['nullable', 'integer', 'exists:cursos,id,deleted_at,NULL']])['curso_id'] ?? null;

        $estudiante = DB::transaction(function () use ($datos, $cursoId) {
            $estudiante = Estudiante::create($datos);
            if ($cursoId) {
                Matricula::create([
                    'estudiante_id' => $estudiante->id,
                    'curso_id' => $cursoId,
                    'numero_lista' => Matricula::siguienteNumero($cursoId),
                    'fecha_matricula' => now()->toDateString(),
                ]);
            }

            return $estudiante;
        });

        return response()->json($this->datos($estudiante->load('matriculas.curso')), 201);
    }

    public function update(Request $request, Estudiante $estudiante)
    {
        $estudiante->update($this->validar($request, $estudiante));

        return $this->datos($estudiante->load('matriculas.curso'));
    }

    public function destroy(Estudiante $estudiante)
    {
        $estudiante->delete();

        return response()->json(['message' => 'Estudiante eliminado']);
    }

    private function datos(Estudiante $e): array
    {
        $matricula = $e->matriculas->first();

        return $e->makeHidden('matriculas')->toArray() + [
            'matricula' => $matricula ? [
                'id' => $matricula->id,
                'estado' => $matricula->estado,
                'numero_lista' => $matricula->numero_lista,
                'curso' => $matricula->curso ? ['id' => $matricula->curso->id, 'nombre' => $matricula->curso->nombre, 'anio' => $matricula->curso->anio] : null,
            ] : null,
        ];
    }

    private function validar(Request $request, ?Estudiante $estudiante = null): array
    {
        $request->merge([
            'run' => Run::normalizar($request->run ?: null),
            'apoderado_run' => Run::normalizar($request->apoderado_run ?: null),
            'apoderado_email' => $request->apoderado_email ? mb_strtolower(trim($request->apoderado_email)) : null,
        ]);

        return $request->validate([
            'run' => ['required', new RunValido, Rule::unique('estudiantes')->ignore($estudiante)->withoutTrashed()],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:60'],
            'apellido_materno' => ['nullable', 'string', 'max:60'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'sexo' => ['nullable', Rule::in(['F', 'M', 'X'])],
            'direccion' => ['nullable', 'string', 'max:200'],
            'apoderado_nombre' => ['nullable', 'string', 'max:150'],
            'apoderado_run' => ['nullable', new RunValido],
            'apoderado_telefono' => ['nullable', 'string', 'max:50'],
            'apoderado_email' => ['nullable', 'email', 'max:150'],
            'pie' => ['boolean'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], ['run.unique' => 'Ya existe un estudiante con ese RUN.'], [
            'run' => 'RUN', 'apoderado_run' => 'RUN del apoderado', 'apoderado_email' => 'correo del apoderado',
            'fecha_nacimiento' => 'fecha de nacimiento',
        ]);
    }
}
