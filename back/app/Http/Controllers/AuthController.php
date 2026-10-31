<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'rol' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', mb_strtolower(trim($request->email)))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Correo o clave incorrectos'], 401);
        }

        if ($user->bloqueado) {
            return response()->json(['message' => 'Usuario bloqueado. Contacte a la Dirección del establecimiento'], 403);
        }

        // Perfil elegido en el login (como ui3): la sesión queda con los permisos de ese
        // rol más los directos. Sin perfil, la sesión usa todos sus roles.
        $rol = $request->filled('rol') ? $request->rol : null;
        if ($rol !== null && ! $user->hasRole($rol)) {
            $suyos = $user->getRoleNames()->implode(', ') ?: 'ninguno';

            return response()->json(['message' => "Tu cuenta no tiene el perfil «{$rol}». Perfiles disponibles: {$suyos}"], 422);
        }

        $token = $user->createToken('libro', $rol !== null ? [User::ABILITY_ROL.$rol] : ['*']);
        $user->withAccessToken($token->accessToken);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user->datosSesion(),
        ]);
    }

    /** Perfiles que se pueden elegir en el login (solo nombres de roles). */
    public function roles()
    {
        return response()->json(Role::orderBy('name')->pluck('name'));
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()->datosSesion()]);
    }

    /**
     * Cambia el perfil (rol) activo de la sesión sin volver a iniciarla: reescribe la
     * ability "rol:<Rol>" del token actual. rol = null → todos sus roles.
     */
    public function perfil(Request $request)
    {
        $request->validate(['rol' => 'nullable|string|max:100']);
        $user = $request->user();
        $rol = $request->filled('rol') ? $request->rol : null;

        if ($rol !== null && ! $user->hasRole($rol)) {
            return response()->json(['message' => "Tu cuenta no tiene el perfil «{$rol}»."], 422);
        }

        $token = $user->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken, 400, 'La sesión no usa un token.');
        $token->forceFill(['abilities' => $rol !== null ? [User::ABILITY_ROL.$rol] : ['*']])->save();
        $user->withAccessToken($token);

        return response()->json(['user' => $user->datosSesion()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }
}
