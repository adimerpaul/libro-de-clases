<?php

namespace App\Http\Controllers\Pie;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\PieCaso;
use App\Models\PieLibroHoja;
use App\Support\Pie;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Libro de Clases PIE por curso (formato del libro impreso del Programa de Integración
 * Escolar). La nómina sale de los casos PIE del año con matrícula en el curso; el resto
 * de las hojas (información general, asistencia del grupo PIE, actividades diarias,
 * evaluaciones, plan anual, entrevistas y hoja de vida) se guardan como documentos por clave.
 */
class LibroPieController extends Controller
{
    /** Claves válidas de las hojas del libro. */
    private const CLAVE = '#^(general|plan|matricula|familia|entrevistas|hojas|asistencia/\d{4}-(0[1-9]|1[0-2])|actividades/\d{4}-\d{2}-\d{2}|evaluaciones/(1S|2S|1T|2T|3T))$#';

    private const PREFIJOS = ['asistencia', 'actividades', 'evaluaciones'];

    /** Cursos del año con su cantidad de estudiantes PIE. */
    public function cursos(Request $request)
    {
        $anio = (int) ($request->anio ?: now()->year);
        $conteo = $this->matriculasPie(Curso::where('anio', $anio)->pluck('id'), $anio)->countBy('curso_id');

        $cursos = Curso::where('anio', $anio)->with('profesorJefe:id,name')->get()
            ->each(fn (Curso $c) => $c->pie_count = $conteo[$c->id] ?? 0);

        return Curso::ordenar($cursos);
    }

    /** Cabecera del libro: curso, establecimiento, nómina PIE y hojas fijas. */
    public function show(Curso $curso)
    {
        $curso->load('profesorJefe:id,name');
        $matriculas = $this->matriculasPie([$curso->id], $curso->anio);
        $casos = PieCaso::where('anio', $curso->anio)->whereIn('estudiante_id', $matriculas->pluck('estudiante_id'))
            ->with('responsable:id,name')->get()->keyBy('estudiante_id');

        $alumnos = $matriculas->map(function (Matricula $m) use ($casos) {
            $e = $m->estudiante;
            $c = $casos[$e->id];

            return [
                'id' => $e->id,
                'caso_id' => $c->id,
                'numero_lista' => $m->numero_lista,
                'apPat' => $e->apellido_paterno,
                'apMat' => $e->apellido_materno,
                'nombres' => $e->nombres,
                'run' => $e->run,
                'sexo' => $e->sexo,
                'fechaNac' => $e->fecha_nacimiento?->format('Y-m-d'),
                'fechaMatricula' => $m->fecha_matricula?->format('Y-m-d'),
                'direccion' => $e->direccion,
                'nee' => $c->tipo_nee ? ucfirst($c->tipo_nee) : null,
                'diagnostico' => $c->diagnostico,
                'estado' => Pie::ESTADOS[$c->estado] ?? $c->estado,
                'responsable' => $c->responsable?->name,
                'apoderado' => $e->apoderado_nombre,
                'telefono' => $e->apoderado_telefono,
                'email' => $e->apoderado_email,
            ];
        })->sortBy(fn ($a) => mb_strtolower("{$a['apPat']} {$a['apMat']} {$a['nombres']}"))->values();

        $hojas = PieLibroHoja::where('curso_id', $curso->id)
            ->whereIn('clave', ['general', 'plan', 'matricula', 'familia', 'entrevistas', 'hojas'])
            ->with('firmante:id,name')->get()->mapWithKeys(fn ($h) => [$h->clave => $h->paraFront()]);

        // Responsables de los casos del curso: sugerencia para "educador(a) diferencial"
        $educadores = $casos->pluck('responsable.name')->filter()->unique()->values();

        return [
            'curso' => $curso->only('id', 'nombre', 'nivel', 'letra', 'anio') + ['profesor_jefe' => $curso->profesorJefe?->name],
            'establecimiento' => config('establecimiento'),
            'educadores' => $educadores,
            'alumnos' => $alumnos,
            'hojas' => $hojas,
        ];
    }

    /** Hojas de una serie (asistencia, actividades o evaluaciones), con sus datos. */
    public function serie(Request $request, Curso $curso, string $prefijo)
    {
        abort_unless(in_array($prefijo, self::PREFIJOS, true), 404);
        $desde = $request->validate(['desde' => 'nullable|string|max:20'])['desde'] ?? null;

        return PieLibroHoja::where('curso_id', $curso->id)
            ->where('clave', 'like', "{$prefijo}/".($desde ? addcslashes($desde, '%_') : '').'%')
            ->with('firmante:id,name')->orderBy('clave')->get()
            ->map(fn ($h) => $h->paraFront());
    }

    public function hoja(Curso $curso, string $clave)
    {
        $this->validarClave($clave);
        $hoja = PieLibroHoja::where(['curso_id' => $curso->id, 'clave' => $clave])->with('firmante:id,name')->first();

        return $hoja?->paraFront() ?? ['clave' => $clave, 'datos' => new \stdClass, 'firma' => null, 'actualizado' => null];
    }

    public function guardar(Request $request, Curso $curso, string $clave)
    {
        $this->validarClave($clave);
        $request->validate(['datos' => 'present|array']);
        abort_if(strlen($request->getContent()) > 500_000, 422, 'La hoja es demasiado grande.');
        // Se guarda tal como llega (objetos como objetos): el cast "array" convertiría {} en []
        $datos = json_decode($request->getContent())->datos;

        $hoja = PieLibroHoja::firstOrNew(['curso_id' => $curso->id, 'clave' => $clave]);
        abort_if($hoja->firmado_en, 423, 'El registro está firmado: quite la firma para editarlo.');

        $hoja->fill(['datos' => $datos, 'updated_by' => $request->user()->id])->save();

        return $hoja->load('firmante:id,name')->paraFront();
    }

    /** Firma el registro de actividades de un día con el usuario autenticado. */
    public function firmar(Request $request, Curso $curso, string $fecha)
    {
        $hoja = PieLibroHoja::firstOrNew(['curso_id' => $curso->id, 'clave' => $this->claveActividades($fecha)]);
        abort_if($hoja->firmado_en, 422, 'El registro ya está firmado.');
        $hoja->datos ??= new \stdClass;
        $hoja->fill(['firmado_por' => $request->user()->id, 'firmado_en' => now(), 'updated_by' => $request->user()->id])->save();

        return $hoja->load('firmante:id,name')->paraFront();
    }

    /** Quita la firma (solo quien firmó). Queda en la auditoría. */
    public function quitarFirma(Request $request, Curso $curso, string $fecha)
    {
        $hoja = PieLibroHoja::where(['curso_id' => $curso->id, 'clave' => $this->claveActividades($fecha)])->firstOrFail();
        abort_if($hoja->firmado_por && $hoja->firmado_por !== $request->user()->id, 403, 'Solo quien firmó puede quitar la firma.');
        $hoja->fill(['firmado_por' => null, 'firmado_en' => null, 'updated_by' => $request->user()->id])->save();

        return $hoja->paraFront();
    }

    /** Matrículas activas (con estudiante) de los cursos, de estudiantes con caso PIE en el año. */
    private function matriculasPie($cursoIds, int $anio): Collection
    {
        return Matricula::whereIn('curso_id', $cursoIds)->activas()
            ->whereIn('estudiante_id', PieCaso::where('anio', $anio)->select('estudiante_id'))
            ->with('estudiante')->get();
    }

    private function validarClave(string $clave): void
    {
        abort_unless(preg_match(self::CLAVE, $clave), 404, 'Hoja del libro desconocida.');
    }

    private function claveActividades(string $fecha): string
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha), 404);

        return "actividades/{$fecha}";
    }
}
