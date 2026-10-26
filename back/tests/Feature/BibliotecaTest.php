<?php

use App\Models\Asignatura;
use App\Models\ObjetivoAprendizaje;
use App\Models\Recurso;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();
    Storage::fake(Recurso::DISCO);

    $this->utp = User::create(['name' => 'UTP', 'email' => 'utp@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::crud('recursos'));
    $this->docente = User::create(['name' => 'Docente', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->mat = Asignatura::create(['nombre' => 'Matemática', 'codigo' => 'MAT']);
    $this->len = Asignatura::create(['nombre' => 'Lengua y Literatura', 'codigo' => 'LEN']);
    $this->oa = ObjetivoAprendizaje::create([
        'codigo' => 'MAT-2M-02', 'nivel' => '2° Medio', 'asignatura_id' => $this->mat->id, 'texto' => 'Funciones', 'version_curricular' => 'demo-2026', 'fuente' => 'DEMO', 'es_demo' => true,
    ]);
});

function comoEnBiblioteca(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

test('quien administra la biblioteca registra un recurso con archivo y OA', function () {
    $recurso = comoEnBiblioteca($this->utp)->post('/api/admin/recursos', [
        'titulo' => 'Funciones en contexto',
        'tipo' => 'Presentación',
        'objetivo_aprendizaje_id' => $this->oa->id,
        'publicado' => '1',
        'archivo' => UploadedFile::fake()->create('funciones.pdf', 120, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertCreated()->json();

    // Nivel y asignatura se completan desde el OA
    expect($recurso['nivel'])->toBe('2° Medio')
        ->and($recurso['asignatura_id'])->toBe($this->mat->id)
        ->and($recurso['tiene_archivo'])->toBeTrue()
        ->and($recurso['archivo_nombre'])->toBe('funciones.pdf')
        ->and($recurso)->not->toHaveKey('archivo_path');

    Storage::disk(Recurso::DISCO)->assertExists(Recurso::find($recurso['id'])->archivo_path);
});

test('el OA debe ser de la misma asignatura y nivel', function () {
    comoEnBiblioteca($this->utp)->postJson('/api/admin/recursos', [
        'titulo' => 'Guía', 'tipo' => 'Guía', 'asignatura_id' => $this->len->id, 'objetivo_aprendizaje_id' => $this->oa->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('objetivo_aprendizaje_id');

    comoEnBiblioteca($this->utp)->postJson('/api/admin/recursos', ['titulo' => 'X', 'tipo' => 'Podcast'])
        ->assertUnprocessable()->assertJsonValidationErrors('tipo');
});

test('editar con multipart reemplaza o quita el archivo', function () {
    $id = comoEnBiblioteca($this->utp)->post('/api/admin/recursos', [
        'titulo' => 'Kit', 'tipo' => 'Material didáctico', 'archivo' => UploadedFile::fake()->create('kit.pdf', 10),
    ], ['Accept' => 'application/json'])->assertCreated()->json('id');
    $anterior = Recurso::find($id)->archivo_path;

    comoEnBiblioteca($this->utp)->post("/api/admin/recursos/{$id}", [
        '_method' => 'PUT', 'titulo' => 'Kit de fracciones', 'tipo' => 'Material didáctico',
        'archivo' => UploadedFile::fake()->create('kit2.pdf', 10),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('archivo_nombre', 'kit2.pdf');
    Storage::disk(Recurso::DISCO)->assertMissing($anterior);

    comoEnBiblioteca($this->utp)->post("/api/admin/recursos/{$id}", [
        '_method' => 'PUT', 'titulo' => 'Kit de fracciones', 'tipo' => 'Material didáctico', 'quitar_archivo' => '1',
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('tiene_archivo', false);
});

test('el docente consulta solo lo publicado y no administra', function () {
    $publicado = Recurso::create(['titulo' => 'Visible', 'tipo' => 'Guía', 'publicado' => true]);
    $borrador = Recurso::create(['titulo' => 'Borrador', 'tipo' => 'Guía', 'publicado' => false, 'archivo_path' => 'biblioteca/x.pdf', 'archivo_nombre' => 'x.pdf']);
    Storage::disk(Recurso::DISCO)->put('biblioteca/x.pdf', 'pdf');

    comoEnBiblioteca($this->docente)->getJson('/api/biblioteca')->assertOk()
        ->assertJsonCount(1)->assertJsonPath('0.id', $publicado->id);
    comoEnBiblioteca($this->docente)->get("/api/biblioteca/{$borrador->id}/archivo")->assertNotFound();
    comoEnBiblioteca($this->utp)->get("/api/biblioteca/{$borrador->id}/archivo")->assertOk();

    comoEnBiblioteca($this->docente)->getJson('/api/admin/recursos')->assertForbidden();
    comoEnBiblioteca($this->docente)->postJson('/api/admin/recursos', ['titulo' => 'X', 'tipo' => 'Guía'])->assertForbidden();
});

test('sin permisos no se entra a la biblioteca', function () {
    $otro = User::create(['name' => 'Otro', 'email' => 'otro@demo.cl', 'password' => 'demo123']);

    comoEnBiblioteca($otro)->getJson('/api/biblioteca')->assertForbidden();
});
