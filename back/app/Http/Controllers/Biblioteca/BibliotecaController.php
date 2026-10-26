<?php

namespace App\Http\Controllers\Biblioteca;

use App\Http\Controllers\Controller;
use App\Models\Recurso;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Consulta de la biblioteca pedagógica: solo recursos publicados. */
class BibliotecaController extends Controller
{
    public function index()
    {
        return Recurso::where('publicado', true)->with(Recurso::RELACIONES)->orderBy('titulo')->get();
    }

    /** Descarga el archivo adjunto; los no publicados solo para quien administra la biblioteca. */
    public function archivo(Request $request, Recurso $recurso)
    {
        abort_if(! $recurso->publicado && ! $request->user()->tienePermiso(Permisos::de('recursos', Permisos::VER)), 404);
        abort_if(! $recurso->archivo_path || ! Storage::disk(Recurso::DISCO)->exists($recurso->archivo_path), 404, 'El recurso no tiene archivo adjunto');

        return Storage::disk(Recurso::DISCO)->download($recurso->archivo_path, $recurso->archivo_nombre);
    }
}
