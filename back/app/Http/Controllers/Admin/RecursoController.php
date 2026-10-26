<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ObjetivoAprendizaje;
use App\Models\Recurso;
use App\Support\Niveles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Administración de la biblioteca pedagógica (Administrador → Biblioteca). */
class RecursoController extends Controller
{
    public const EXTENSIONES = 'pdf,doc,docx,ppt,pptx,pps,ppsx,xls,xlsx,odt,odp,ods,txt,jpg,jpeg,png,gif,webp,mp3,mp4,zip';

    public function index()
    {
        return Recurso::with(Recurso::RELACIONES)->latest()->get();
    }

    public function store(Request $request)
    {
        $recurso = new Recurso($this->validar($request));
        $recurso->autor_id = $request->user()->id;
        $this->guardarArchivo($request, $recurso);
        $recurso->save();

        return $recurso->load(Recurso::RELACIONES);
    }

    public function update(Request $request, Recurso $recurso)
    {
        $recurso->fill($this->validar($request));
        $this->guardarArchivo($request, $recurso);
        $recurso->save();

        return $recurso->load(Recurso::RELACIONES);
    }

    public function destroy(Recurso $recurso)
    {
        $recurso->delete();

        return response()->json(['message' => 'Recurso eliminado']);
    }

    /** OA vigentes para vincular el recurso, por nivel y asignatura. */
    public function objetivos(Request $request)
    {
        $request->validate(['nivel' => ['nullable', 'string'], 'asignatura_id' => ['nullable', 'integer']]);

        return ObjetivoAprendizaje::vigentes()
            ->select('id', 'codigo', 'nivel', 'asignatura_id', 'texto')
            ->when($request->nivel, fn ($q, $nivel) => $q->where('nivel', $nivel))
            ->when($request->asignatura_id, fn ($q, $id) => $q->where('asignatura_id', $id))
            ->orderBy('codigo')
            ->get();
    }

    private function validar(Request $request): array
    {
        // El formulario llega como multipart: booleanos y vacíos vienen como texto
        $vacioANull = fn (string $campo) => $request->filled($campo) ? trim((string) $request->input($campo)) : null;
        $request->merge([
            'publicado' => filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN),
            'nivel' => $vacioANull('nivel'),
            'asignatura_id' => $vacioANull('asignatura_id'),
            'objetivo_aprendizaje_id' => $vacioANull('objetivo_aprendizaje_id'),
            'url' => $vacioANull('url'),
        ]);

        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(Recurso::TIPOS)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'nivel' => ['nullable', Rule::in(Niveles::TODOS)],
            'asignatura_id' => ['nullable', 'integer', 'exists:asignaturas,id,deleted_at,NULL'],
            'objetivo_aprendizaje_id' => ['nullable', 'integer', 'exists:objetivos_aprendizaje,id,deleted_at,NULL'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'archivo' => ['nullable', 'file', 'max:20480', 'mimes:'.self::EXTENSIONES],
            'publicado' => ['boolean'],
        ], [], [
            'asignatura_id' => 'asignatura',
            'objetivo_aprendizaje_id' => 'OA',
            'url' => 'enlace',
        ]);
        unset($datos['archivo']);

        // El OA debe ser de la misma asignatura y nivel del recurso; si faltan, los toma del OA
        if ($datos['objetivo_aprendizaje_id']) {
            $oa = ObjetivoAprendizaje::find($datos['objetivo_aprendizaje_id']);
            if (($datos['asignatura_id'] && $oa->asignatura_id !== (int) $datos['asignatura_id'])
                || ($datos['nivel'] && $oa->nivel !== $datos['nivel'])) {
                throw ValidationException::withMessages([
                    'objetivo_aprendizaje_id' => 'El OA no corresponde a la asignatura y nivel elegidos.',
                ]);
            }
            $datos['asignatura_id'] ??= $oa->asignatura_id;
            $datos['nivel'] ??= $oa->nivel;
        }

        return $datos;
    }

    /** Reemplaza o quita ("quitar_archivo") el archivo adjunto. */
    private function guardarArchivo(Request $request, Recurso $recurso): void
    {
        $quitar = filter_var($request->input('quitar_archivo', false), FILTER_VALIDATE_BOOLEAN);
        if (! $request->hasFile('archivo') && ! $quitar) {
            return;
        }

        if ($recurso->archivo_path) {
            Storage::disk(Recurso::DISCO)->delete($recurso->archivo_path);
        }
        $recurso->archivo_path = $recurso->archivo_nombre = $recurso->archivo_tamano = null;

        if ($archivo = $request->file('archivo')) {
            $recurso->archivo_path = $archivo->store('biblioteca', Recurso::DISCO);
            $recurso->archivo_nombre = $archivo->getClientOriginalName();
            $recurso->archivo_tamano = $archivo->getSize();
        }
    }
}
