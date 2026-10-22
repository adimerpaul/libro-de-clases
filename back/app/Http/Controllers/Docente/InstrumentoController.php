<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Instrumento;
use App\Models\InstrumentoPregunta;
use App\Models\ObjetivoAprendizaje;
use App\Support\EscalaNotas;
use App\Support\GeneradorInstrumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Instrumentos de evaluación del docente que consulta (solo los propios). */
class InstrumentoController extends Controller
{
    use ValidaCargaDocente;

    private const RELACIONES = [
        'asignacion.curso', 'asignacion.asignatura:id,nombre,codigo,color',
        'objetivo:id,codigo,unidad,texto,nivel,es_demo',
    ];

    public function index(Request $request)
    {
        return Instrumento::with(self::RELACIONES)
            ->withCount('preguntas')
            ->withSum('preguntas', 'puntaje')
            ->where('docente_id', $request->user()->id)
            ->latest()
            ->get();
    }

    public function show(Request $request, Instrumento $instrumento)
    {
        $this->esPropio($request, $instrumento);

        return $this->detalle($instrumento);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $instrumento = DB::transaction(function () use ($request, $datos) {
            $instrumento = Instrumento::create($datos['instrumento'] + ['docente_id' => $request->user()->id]);
            $this->guardarPreguntas($instrumento, $datos['preguntas']);

            return $instrumento;
        });

        return response()->json($this->detalle($instrumento), 201);
    }

    public function update(Request $request, Instrumento $instrumento)
    {
        $this->esPropio($request, $instrumento);
        $datos = $this->validar($request);

        DB::transaction(function () use ($instrumento, $datos) {
            $instrumento->update($datos['instrumento']);
            $instrumento->preguntas()->delete();
            $this->guardarPreguntas($instrumento, $datos['preguntas']);
        });

        return $this->detalle($instrumento);
    }

    public function destroy(Request $request, Instrumento $instrumento)
    {
        $this->esPropio($request, $instrumento);
        $instrumento->delete();

        return response()->json(['message' => 'Instrumento eliminado']);
    }

    /** Borrador de preguntas desde un OA (plantillas determinísticas, no se guarda). */
    public function generar(Request $request)
    {
        $datos = $request->validate([
            'objetivo_aprendizaje_id' => ['required', 'integer', 'exists:objetivos_aprendizaje,id,deleted_at,NULL'],
            'cantidad' => ['required', 'integer', 'between:1,30'],
            'mezcla' => ['required', Rule::in(['mixto', ...InstrumentoPregunta::TIPOS])],
        ], [], ['objetivo_aprendizaje_id' => 'OA']);

        $oa = ObjetivoAprendizaje::find($datos['objetivo_aprendizaje_id']);

        return GeneradorInstrumento::generar($oa, $datos['cantidad'], $datos['mezcla']);
    }

    /** Tabla de conversión puntaje → nota (1,0 – 7,0). */
    public function escala(Request $request)
    {
        $datos = $request->validate([
            'maximo' => ['required', 'numeric', 'min:0.5', 'max:1000'],
            'exigencia' => ['required', 'integer', 'between:1,100'],
        ]);

        return [
            'maximo' => (float) $datos['maximo'],
            'exigencia' => (int) $datos['exigencia'],
            'puntaje_aprobacion' => EscalaNotas::puntajeAprobacion($datos['maximo'], $datos['exigencia']),
            'tabla' => EscalaNotas::tabla($datos['maximo'], $datos['exigencia']),
        ];
    }

    private function detalle(Instrumento $instrumento): array
    {
        $instrumento->load([...self::RELACIONES, 'preguntas']);
        $total = $instrumento->puntajeTotal();

        return $instrumento->toArray() + [
            'puntaje_total' => $total,
            'puntaje_aprobacion' => EscalaNotas::puntajeAprobacion($total, $instrumento->exigencia),
        ];
    }

    private function esPropio(Request $request, Instrumento $instrumento): void
    {
        abort_unless($instrumento->docente_id === $request->user()->id, 403, 'Este instrumento es de otro docente.');
    }

    private function guardarPreguntas(Instrumento $instrumento, array $preguntas): void
    {
        foreach (array_values($preguntas) as $i => $p) {
            $instrumento->preguntas()->create([
                'orden' => $i + 1,
                'tipo' => $p['tipo'],
                'enunciado' => $p['enunciado'],
                'alternativas' => $p['tipo'] === 'seleccion' ? array_values($p['alternativas']) : null,
                'respuesta_correcta' => $p['tipo'] === 'desarrollo' ? null : (string) $p['respuesta_correcta'],
                'criterios' => $p['criterios'] ?? null,
                'puntaje' => $p['puntaje'],
            ]);
        }
    }

    private function validar(Request $request): array
    {
        $validator = validator($request->all(), [
            'asignacion_id' => ['required', 'integer', 'exists:asignaciones,id,deleted_at,NULL'],
            'objetivo_aprendizaje_id' => ['nullable', 'integer', 'exists:objetivos_aprendizaje,id,deleted_at,NULL'],
            'nombre' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(Instrumento::TIPOS)],
            'fecha_aplicacion' => ['nullable', 'date'],
            'exigencia' => ['required', 'integer', 'between:50,70'],
            'instrucciones' => ['nullable', 'string', 'max:3000'],
            'estado' => ['required', Rule::in(Instrumento::ESTADOS)],
            'preguntas' => ['present', 'array', 'max:100'],
            'preguntas.*.tipo' => ['required', Rule::in(InstrumentoPregunta::TIPOS)],
            'preguntas.*.enunciado' => ['required', 'string', 'max:3000'],
            'preguntas.*.puntaje' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'preguntas.*.alternativas' => ['nullable', 'array', 'max:6'],
            'preguntas.*.alternativas.*' => ['required', 'string', 'max:500'],
            'preguntas.*.respuesta_correcta' => ['nullable'],
            'preguntas.*.criterios' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'asignacion_id' => 'curso y asignatura', 'objetivo_aprendizaje_id' => 'OA',
            'fecha_aplicacion' => 'fecha de aplicación', 'preguntas.*.enunciado' => 'enunciado',
            'preguntas.*.puntaje' => 'puntaje', 'preguntas.*.alternativas.*' => 'alternativa',
        ]);

        // Reglas que dependen del tipo de cada pregunta
        $validator->after(function (Validator $v) use ($request) {
            if ($request->estado === 'listo' && empty($request->preguntas)) {
                $v->errors()->add('preguntas', 'Un instrumento listo debe tener al menos una pregunta.');
            }
            foreach ((array) $request->preguntas as $i => $p) {
                $n = $i + 1;
                $tipo = $p['tipo'] ?? null;
                $correcta = $p['respuesta_correcta'] ?? null;
                if ($tipo === 'seleccion') {
                    $alts = $p['alternativas'] ?? [];
                    if (count($alts) < 2) {
                        $v->errors()->add("preguntas.$i.alternativas", "La pregunta $n necesita al menos 2 alternativas.");
                    } elseif (! is_numeric($correcta) || (int) $correcta < 0 || (int) $correcta >= count($alts)) {
                        $v->errors()->add("preguntas.$i.respuesta_correcta", "Marque la alternativa correcta de la pregunta $n.");
                    }
                }
                if ($tipo === 'verdadero_falso' && ! in_array($correcta, ['V', 'F'], true)) {
                    $v->errors()->add("preguntas.$i.respuesta_correcta", "Indique si la pregunta $n es verdadera o falsa.");
                }
            }
        });

        $datos = $validator->validate();
        $this->validarAsignacionYOa($request, $datos);

        return [
            'instrumento' => collect($datos)->except('preguntas')->all(),
            'preguntas' => $datos['preguntas'],
        ];
    }
}
