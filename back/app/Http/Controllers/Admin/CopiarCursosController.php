<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;

/**
 * Prepara un año escolar copiando los cursos (nivel y letra) de otro año.
 * No copia profesores jefe, asignaciones ni estudiantes: eso se define para el año nuevo.
 */
class CopiarCursosController extends Controller
{
    public function __invoke(Request $request)
    {
        $datos = $request->validate([
            'desde_anio' => ['required', 'integer', 'between:2000,2100'],
            'hacia_anio' => ['required', 'integer', 'between:2000,2100', 'different:desde_anio'],
        ], [], ['desde_anio' => 'año de origen', 'hacia_anio' => 'año de destino']);

        $existentes = Curso::where('anio', $datos['hacia_anio'])->get()
            ->map(fn ($c) => "{$c->nivel}|{$c->letra}");

        $creados = Curso::where('anio', $datos['desde_anio'])->get()
            ->reject(fn ($c) => $existentes->contains("{$c->nivel}|{$c->letra}"))
            ->each(fn ($c) => Curso::create(['anio' => $datos['hacia_anio'], 'nivel' => $c->nivel, 'letra' => $c->letra]))
            ->count();

        return [
            'creados' => $creados,
            'message' => $creados
                ? "{$creados} curso(s) creado(s) para {$datos['hacia_anio']}"
                : "El año {$datos['hacia_anio']} ya tenía todos los cursos de {$datos['desde_anio']}",
        ];
    }
}
