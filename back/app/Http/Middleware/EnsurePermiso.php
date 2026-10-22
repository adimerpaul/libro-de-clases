<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige al menos uno de los permisos indicados.
 * Uso: ->middleware('permiso:cursos.gestionar') o 'permiso:a,b' (cualquiera de ellos).
 */
class EnsurePermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $tiene = $request->user()?->permisosEfectivos()->intersect($permisos)->isNotEmpty();

        abort_unless($tiene, 403, 'No tiene permiso para realizar esta acción');

        return $next($request);
    }
}
