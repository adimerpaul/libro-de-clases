<?php

namespace App\Http\Controllers\Pie;

use App\Http\Controllers\Controller;
use App\Models\PieCaso;
use App\Models\PieDocumento;
use App\Support\Pie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Expediente PIE: documentos e imágenes en disco privado, servidos solo por la API. */
class DocumentoController extends Controller
{
    public function store(Request $request, PieCaso $caso)
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(Pie::TIPOS_DOCUMENTO)],
            'titulo' => ['nullable', 'string', 'max:200'],
            'fecha' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'archivo' => ['required', 'file', 'max:15360', 'mimes:'.Pie::EXTENSIONES],
        ], [], ['archivo' => 'archivo']);

        $archivo = $request->file('archivo');
        $documento = $caso->documentos()->create([
            'tipo' => $datos['tipo'],
            'titulo' => ($datos['titulo'] ?? null) ?: pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME),
            'fecha' => $datos['fecha'] ?? now()->toDateString(),
            'descripcion' => $datos['descripcion'] ?? null,
            'archivo_path' => $archivo->store("pie/{$caso->id}", PieDocumento::DISCO),
            'archivo_nombre' => $archivo->getClientOriginalName(),
            'archivo_mime' => $archivo->getMimeType(),
            'archivo_tamano' => $archivo->getSize(),
            'autor_id' => $request->user()->id,
        ]);

        return response()->json($documento->load('autor:id,name'), 201);
    }

    public function update(Request $request, PieDocumento $documento)
    {
        $documento->update($request->validate([
            'tipo' => ['required', Rule::in(Pie::TIPOS_DOCUMENTO)],
            'titulo' => ['required', 'string', 'max:200'],
            'fecha' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]));

        return $documento->load('autor:id,name');
    }

    /** Soft delete: el archivo se conserva para la auditoría del expediente. */
    public function destroy(PieDocumento $documento)
    {
        $documento->delete();

        return response()->json(['message' => 'Documento eliminado del expediente']);
    }

    /** Archivo (inline para ver imágenes y PDF en el navegador). */
    public function archivo(PieDocumento $documento)
    {
        abort_if(! Storage::disk(PieDocumento::DISCO)->exists($documento->archivo_path), 404, 'El archivo ya no está disponible');

        return Storage::disk(PieDocumento::DISCO)->response($documento->archivo_path, $documento->archivo_nombre, [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
