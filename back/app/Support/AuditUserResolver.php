<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\UserResolver;

class AuditUserResolver implements UserResolver
{
    public static function resolve()
    {
        // Las rutas de la API usan Sanctum; request()->user() puede consultar
        // el guard por defecto y devolver null aunque la petición esté autenticada.
        foreach (['sanctum', 'api', 'web'] as $guard) {
            if ($guard !== 'sanctum' && ! config("auth.guards.{$guard}")) {
                continue;
            }
            $user = Auth::guard($guard)->user();
            if ($user) {
                return $user;
            }
        }

        return null;
    }
}
