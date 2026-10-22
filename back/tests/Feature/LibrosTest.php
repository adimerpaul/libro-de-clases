<?php

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permisos::sincronizar();

    $this->docente = User::create(['name' => 'Marcela', 'email' => 'docente@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->jefe = User::create(['name' => 'Jefe', 'email' => 'jefe@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::DICTAR_CLASES);
    $this->utp = User::create(['name' => 'UTP', 'email' => 'utp@demo.cl', 'password' => 'demo123'])
        ->givePermissionTo(Permisos::VER_LIBROS);

    $anio = (int) now()->year;
    $this->conCarga = Curso::create(['anio' => $anio, 'nivel' => '2° Medio', 'letra' => 'B']);
    $this->conJefatura = Curso::create(['anio' => $anio, 'nivel' => '1° Medio', 'letra' => 'A', 'profesor_jefe_id' => $this->jefe->id]);
    Curso::create(['anio' => $anio, 'nivel' => '3° Medio', 'letra' => 'A']);

    $mat = Asignatura::create(['nombre' => 'Matemática', 'codigo' => 'MAT']);
    Asignacion::create(['curso_id' => $this->conCarga->id, 'asignatura_id' => $mat->id, 'docente_id' => $this->docente->id]);

    $base = ['apellido_materno' => 'Rojas', 'fecha_nacimiento' => '2010-03-01', 'sexo' => 'F'];
    foreach ([[2, '11111111-1', 'Soto'], [1, '22222222-2', 'Araya']] as [$n, $run, $ap]) {
        $e = Estudiante::create($base + ['run' => $run, 'nombres' => 'Ana', 'apellido_paterno' => $ap]);
        Matricula::create(['estudiante_id' => $e->id, 'curso_id' => $this->conCarga->id, 'numero_lista' => $n, 'fecha_matricula' => "{$anio}-03-01", 'estado' => 'activa']);
    }
});

it('muestra todos los libros a quien tiene libros.ver', function () {
    $this->actingAs($this->utp, 'sanctum')->getJson('/api/libros')
        ->assertOk()->assertJsonCount(3)
        ->assertJsonPath('0.nombre', '1° Medio A');
});

it('al docente solo le muestra los cursos de su carga o jefatura', function () {
    $this->actingAs($this->docente, 'sanctum')->getJson('/api/libros')
        ->assertOk()->assertJsonCount(1)
        ->assertJsonPath('0.estudiantes_count', 2)
        ->assertJsonPath('0.mis_asignaturas.0.nombre', 'Matemática');

    $this->actingAs($this->jefe, 'sanctum')->getJson('/api/libros')
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.es_jefatura', true);
});

it('abre el libro con la nómina ordenada por número de lista', function () {
    $this->actingAs($this->docente, 'sanctum')->getJson("/api/libros/{$this->conCarga->id}")
        ->assertOk()
        ->assertJsonPath('matriculas.0.estudiante.apellido_paterno', 'Araya')
        ->assertJsonPath('asignaciones.0.docente.name', 'Marcela');
});

it('no deja al docente abrir el libro de un curso ajeno', function () {
    $this->actingAs($this->docente, 'sanctum')->getJson("/api/libros/{$this->conJefatura->id}")->assertNotFound();
});

it('exige libros.ver o clases.dictar', function () {
    $sin = User::create(['name' => 'Sin', 'email' => 'sin@demo.cl', 'password' => 'demo123']);
    $this->actingAs($sin, 'sanctum')->getJson('/api/libros')->assertForbidden();
});
