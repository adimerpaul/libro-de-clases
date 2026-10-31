<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();
    $this->user = User::create(['name' => 'Admin', 'email' => 'admin@demo.cl', 'password' => 'demo123'])
        ->assignRole(['Administrador', 'Docente']);
    $this->token = $this->postJson('/api/login', ['email' => 'admin@demo.cl', 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();
});

it('cambia el perfil activo de la sesión y con él los permisos, sin nuevo token', function () {
    $api = fn () => tap($this->withToken($this->token), fn () => app('auth')->forgetGuards());

    $api()->getJson('/api/me')->assertJsonPath('user.rol_activo', null)
        ->assertJson(fn ($j) => $j->where('user.permisos', fn ($p) => collect($p)->contains('usuarios.ver'))->etc());

    $api()->putJson('/api/perfil', ['rol' => 'Docente'])->assertOk()
        ->assertJsonPath('user.rol_activo', 'Docente')
        ->assertJson(fn ($j) => $j->where('user.permisos', fn ($p) => ! collect($p)->contains('usuarios.ver') && collect($p)->contains('clases.dictar'))->etc());

    // El mismo token ya no entra a lo del Administrador
    $api()->getJson('/api/admin/usuarios')->assertForbidden();

    $api()->putJson('/api/perfil', ['rol' => null])->assertOk()->assertJsonPath('user.rol_activo', null);
    $api()->getJson('/api/admin/usuarios')->assertOk();
});

it('no permite un perfil que la cuenta no tiene', function () {
    $this->withToken($this->token)->putJson('/api/perfil', ['rol' => 'UTP'])->assertStatus(422);
});
