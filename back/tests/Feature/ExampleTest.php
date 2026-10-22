<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    User::create([
        'name' => 'Docente Prueba',
        'email' => 'docente@demo.cl',
        'password' => 'demo123',
    ])->givePermissionTo(Permisos::DICTAR_CLASES);
});

function login(string $password = 'demo123')
{
    return test()->postJson('/api/login', [
        'email' => 'docente@demo.cl',
        'password' => $password,
    ]);
}

test('login con correo y clave devuelve token y permisos', function () {
    login()->assertOk()
        ->assertJsonStructure(['token', 'user' => ['permisos']])
        ->assertJsonPath('user.permisos', [Permisos::DICTAR_CLASES]);
});

test('login rechaza clave incorrecta', function () {
    login(password: 'otra')->assertStatus(401);
});

test('login rechaza usuario bloqueado', function () {
    User::where('email', 'docente@demo.cl')->update(['bloqueado' => true]);

    login()->assertStatus(403);
});

test('me devuelve el usuario con sus permisos', function () {
    $token = login()->json('token');

    $this->withToken($token)->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', 'docente@demo.cl')
        ->assertJsonPath('user.permisos', [Permisos::DICTAR_CLASES]);
});
