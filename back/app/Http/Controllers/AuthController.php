<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', mb_strtolower(trim($request->email)))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Correo o clave incorrectos'], 401);
        }

        if ($user->bloqueado) {
            return response()->json(['message' => 'Usuario bloqueado. Contacte a la Dirección del establecimiento'], 403);
        }

        return response()->json([
            'token' => $user->createToken('libro')->plainTextToken,
            'user' => $user->datosSesion(),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()->datosSesion()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }
}
