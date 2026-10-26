<?php

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\MarcaRfid;
use App\Models\Matricula;
use App\Models\TarjetaRfid;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();
    config(['services.socket.url' => 'http://socket.test', 'services.rfid.hora_inicio' => '08:00', 'services.rfid.tolerancia' => 10, 'services.rfid.rebote' => 30]);
    Http::fake(['socket.test/*' => Http::response(['success' => true])]);

    $this->utp = User::create(['name' => 'UTP', 'email' => 'utp@demo.cl', 'password' => 'demo123'])->givePermissionTo(Permisos::VER_RFID);
    $this->curso = Curso::create(['anio' => now()->year, 'nivel' => '2° Medio', 'letra' => 'A']);
    $this->est = Estudiante::create(['run' => '11111111-1', 'nombres' => 'Ana', 'apellido_paterno' => 'Soto', 'apellido_materno' => 'Rojas']);
    Matricula::create(['estudiante_id' => $this->est->id, 'curso_id' => $this->curso->id, 'numero_lista' => 1, 'fecha_matricula' => now(), 'estado' => 'activa']);
    TarjetaRfid::create(['estudiante_id' => $this->est->id, 'codigo' => 'RFID-000001']);
});

function comoRfid(User $user)
{
    $token = test()->postJson('/api/login', ['email' => $user->email, 'password' => 'demo123'])->json('token');
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

test('la pasada se guarda, propone atrasado y se avisa al socket sin el nombre', function () {
    $this->travelTo(today()->setTime(8, 25));

    comoRfid($this->utp)->postJson('/api/rfid/marcar', ['codigo' => 'RFID-000001'])
        ->assertOk()
        ->assertJson(['duplicada' => false, 'tipo' => 'entrada', 'propuesta' => 'atrasado', 'curso_id' => $this->curso->id, 'nombre' => 'Soto Rojas, Ana']);

    Http::assertSent(fn (Request $r) => $r->url() === 'http://socket.test/notify'
        && $r['event'] === 'libro-clases-rfid'
        && $r['data']['estudiante_id'] === $this->est->id
        && ! isset($r['data']['nombre']));

    comoRfid($this->utp)->getJson('/api/rfid/estudiantes?curso_id='.$this->curso->id)
        ->assertJsonPath('0.propuesta', 'atrasado')
        ->assertJsonPath('0.dentro', true);
});

test('una pasada repetida se ignora y la siguiente es salida', function () {
    $this->travelTo(today()->setTime(7, 55));
    comoRfid($this->utp)->postJson('/api/rfid/marcar', ['codigo' => 'RFID-000001'])->assertJsonPath('propuesta', 'presente');

    $this->travel(10)->seconds();
    comoRfid($this->utp)->postJson('/api/rfid/marcar', ['codigo' => 'RFID-000001'])->assertJsonPath('duplicada', true);

    $this->travel(5)->hours();
    comoRfid($this->utp)->postJson('/api/rfid/marcar', ['codigo' => 'RFID-000001'])->assertJsonPath('tipo', 'salida');

    expect(MarcaRfid::count())->toBe(2);
});

test('si el socket no responde la marca queda guardada', function () {
    Http::fake(['socket.test/*' => Http::response('caído', 500)]);

    comoRfid($this->utp)->postJson('/api/rfid/marcar', ['curso_id' => $this->curso->id])->assertOk()->assertJsonPath('codigo', 'RFID-000001');
    expect(MarcaRfid::count())->toBe(1);
});

test('una tarjeta desconocida se registra como tal', function () {
    comoRfid($this->utp)->postJson('/api/rfid/marcar', ['codigo' => 'XX-99'])->assertJson(['desconocida' => true, 'estudiante_id' => null]);
});

test('el GET de simulación marca sin sesión solo si está habilitado', function () {
    config(['services.rfid.simulador_get' => true]);
    $this->getJson('/api/rfid/simular?codigo=RFID-000001')->assertOk()->assertJsonPath('estudiante_id', $this->est->id);

    config(['services.rfid.simulador_get' => false]);
    $this->getJson('/api/rfid/simular?codigo=RFID-000001')->assertNotFound();
    expect(MarcaRfid::count())->toBe(1);
});

test('sin rfid.ver no se accede al panel', function () {
    $otro = User::create(['name' => 'Otro', 'email' => 'otro@demo.cl', 'password' => 'demo123']);
    comoRfid($otro)->getJson('/api/rfid/cursos')->assertForbidden();
    comoRfid($otro)->postJson('/api/rfid/marcar', ['codigo' => 'RFID-000001'])->assertForbidden();
});
