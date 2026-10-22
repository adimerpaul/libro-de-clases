<?php

use App\Http\Controllers\Admin\AsignacionController;
use App\Http\Controllers\Admin\AsignaturaController;
use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\CopiarCursosController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\EstudianteController;
use App\Http\Controllers\Admin\MatriculaController;
use App\Http\Controllers\Admin\RolController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Docente\CurriculoController;
use App\Http\Controllers\Docente\InstrumentoController;
use App\Http\Controllers\Docente\MisAsignacionesController;
use App\Http\Controllers\Docente\PlanificacionController;
use App\Support\Permisos;
use Illuminate\Support\Facades\Route;

// ── Rutas públicas ────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// ── Rutas protegidas ──────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Menú Administrador: cada módulo con su CRUD (ver, crear, editar, eliminar) ──
    Route::prefix('admin')->group(function () {
        Route::get('/catalogos', CatalogoController::class)
            ->middleware('permiso:'.implode(',', Permisos::administracion()));

        /**
         * index/store/update/destroy de un recurso, cada uno con "<modulo>.<accion>".
         * $listan: otros permisos que también pueden leer el listado (lo usan en sus formularios).
         * $tambien: módulo alternativo que comparte las escrituras (Docentes usa el recurso usuarios).
         */
        $crud = function (string $uri, string $controller, string $param, string $modulo, array $listan = [], ?string $tambien = null) {
            $permiso = fn (string $accion) => 'permiso:'.implode(',', array_filter([
                Permisos::de($modulo, $accion), $tambien ? Permisos::de($tambien, $accion) : null,
            ]));

            Route::get($uri, [$controller, 'index'])->middleware($permiso(Permisos::VER).($listan ? ','.implode(',', $listan) : ''));
            Route::post($uri, [$controller, 'store'])->middleware($permiso(Permisos::CREAR));
            Route::match(['put', 'patch'], "{$uri}/{{$param}}", [$controller, 'update'])->middleware($permiso(Permisos::EDITAR));
            Route::delete("{$uri}/{{$param}}", [$controller, 'destroy'])->middleware($permiso(Permisos::ELIMINAR));
        };

        // Acciones por año escolar (antes de los $crud para no confundirse con {id})
        Route::post('cursos/copiar', CopiarCursosController::class)->middleware('permiso:cursos.crear');
        Route::post('matriculas/copiar', [MatriculaController::class, 'copiar'])->middleware('permiso:matriculas.crear');
        Route::post('matriculas/renumerar', [MatriculaController::class, 'renumerar'])->middleware('permiso:matriculas.editar');

        $crud('cursos', CursoController::class, 'curso', 'cursos', ['asignaciones.ver', 'estudiantes.ver', 'matriculas.ver']);
        $crud('estudiantes', EstudianteController::class, 'estudiante', 'estudiantes', ['matriculas.ver']);
        $crud('matriculas', MatriculaController::class, 'matricula', 'matriculas');
        $crud('asignaturas', AsignaturaController::class, 'asignatura', 'asignaturas', ['asignaciones.ver']);
        $crud('asignaciones', AsignacionController::class, 'asignacion', 'asignaciones');
        // Docentes = usuarios que dictan clases; sin permisos de "usuarios" el controlador limita a ellos
        $crud('usuarios', UsuarioController::class, 'usuario', 'usuarios', ['cursos.crear', 'cursos.editar', 'asignaciones.ver'], 'docentes');
        $crud('roles', RolController::class, 'rol', 'roles', ['usuarios.ver']);
    });

    // ── Trabajo docente: planificador y creador de evaluaciones (datos propios) ──
    Route::prefix('docente')->middleware('permiso:'.Permisos::DICTAR_CLASES)->group(function () {
        Route::get('/asignaciones', MisAsignacionesController::class);
        Route::get('/oa', [CurriculoController::class, 'index']);

        Route::apiResource('planificaciones', PlanificacionController::class)->except('show')
            ->parameters(['planificaciones' => 'planificacion']);

        Route::post('/instrumentos/generar', [InstrumentoController::class, 'generar']);
        Route::get('/instrumentos/escala', [InstrumentoController::class, 'escala']);
        Route::apiResource('instrumentos', InstrumentoController::class);
    });
});
