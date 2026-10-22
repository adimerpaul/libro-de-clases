<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Niveles;
use App\Support\Permisos;

class CatalogoController extends Controller
{
    /** Listas fijas que usan los formularios de administración. */
    public function __invoke()
    {
        return response()->json([
            'niveles' => Niveles::TODOS,
            'permisos' => Permisos::grupos(),
            'acciones' => collect(Permisos::ACCIONES)->map(fn ($label, $key) => compact('key', 'label'))->values(),
            'anio' => (int) now()->year,
        ]);
    }
}
