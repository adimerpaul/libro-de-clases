<?php

use App\Http\Controllers\Admin\AsignacionController;
use App\Http\Controllers\Admin\AsignaturaController;
use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\CopiarCursosController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\EstudianteController;
use App\Http\Controllers\Admin\MatriculaController;
use App\Http\Controllers\Admin\RecursoController;
use App\Http\Controllers\Admin\RolController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Asistencia\RfidController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Biblioteca\BibliotecaController;
use App\Http\Controllers\Docente\CurriculoController;
use App\Http\Controllers\Docente\InstrumentoController;
use App\Http\Controllers\Docente\MisAsignacionesController;
use App\Http\Controllers\Docente\PlanificacionController;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\Pie\CasoController;
use App\Http\Controllers\Pie\DocumentoController;
use App\Http\Controllers\Pie\SeguimientoController;
use App\Support\Permisos;
use Illuminate\Support\Facades\Route;

// ── Rutas públicas ────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Prueba sin lector, abrible en el navegador: GET /api/rfid/simular?codigo=RFID-000001 (o ?curso_id=2).
// Solo con RFID_SIMULADOR_GET=true (por defecto, en local); en producción responde 404.
Route::get('/rfid/simular', [RfidController::class, 'simular'])->middleware('throttle:60,1');

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
        Route::get('matriculas/retiros', [MatriculaController::class, 'retiros'])->middleware('permiso:matriculas.ver');
        Route::post('matriculas/{matricula}/baja', [MatriculaController::class, 'baja'])->middleware('permiso:matriculas.editar');
        Route::get('recursos/oa', [RecursoController::class, 'objetivos'])->middleware('permiso:recursos.crear,recursos.editar');

        $crud('cursos', CursoController::class, 'curso', 'cursos', ['asignaciones.ver', 'estudiantes.ver', 'matriculas.ver']);
        $crud('estudiantes', EstudianteController::class, 'estudiante', 'estudiantes', ['matriculas.ver']);
        $crud('matriculas', MatriculaController::class, 'matricula', 'matriculas');
        $crud('asignaturas', AsignaturaController::class, 'asignatura', 'asignaturas', ['asignaciones.ver', 'recursos.crear', 'recursos.editar']);
        $crud('asignaciones', AsignacionController::class, 'asignacion', 'asignaciones');
        // Docentes = usuarios que dictan clases; sin permisos de "usuarios" el controlador limita a ellos
        $crud('usuarios', UsuarioController::class, 'usuario', 'usuarios', ['cursos.crear', 'cursos.editar', 'asignaciones.ver'], 'docentes');
        $crud('roles', RolController::class, 'rol', 'roles', ['usuarios.ver']);
        // Biblioteca: el archivo va multipart, por eso el front edita con POST + _method=PUT
        $crud('recursos', RecursoController::class, 'recurso', 'recursos');
    });

    // ── Biblioteca pedagógica: la consultan los docentes y quien la administra ──
    Route::prefix('biblioteca')->middleware('permiso:recursos.ver,'.Permisos::DICTAR_CLASES)->group(function () {
        Route::get('/', [BibliotecaController::class, 'index']);
        Route::get('/{recurso}/archivo', [BibliotecaController::class, 'archivo'])->whereNumber('recurso');
    });

    // ── Libro de clases: todos con "libros.ver"; el docente, los de su carga o jefatura ──
    Route::prefix('libros')->middleware('permiso:'.Permisos::VER_LIBROS.','.Permisos::DICTAR_CLASES)->group(function () {
        Route::get('/', [LibroController::class, 'index']);
        Route::get('/{curso}', [LibroController::class, 'show'])->whereNumber('curso');
    });

    // ── Asistencia RFID: panel en vivo; cada marca se avisa al socket (services.socket) ──
    Route::prefix('rfid')->middleware('permiso:'.Permisos::VER_RFID)->group(function () {
        Route::get('/cursos', [RfidController::class, 'cursos']);
        Route::get('/estudiantes', [RfidController::class, 'estudiantes']);
        Route::get('/marcas/hoy', [RfidController::class, 'hoy']);
        Route::post('/marcar', [RfidController::class, 'marcar'])->middleware('throttle:120,1');
    });

    // ── PIE / Inclusión: casos por estudiante y año, expediente (documentos e imágenes) y bitácora ──
    Route::prefix('pie')->group(function () {
        $ver = 'permiso:pie.ver';
        $editar = 'permiso:pie.editar';

        Route::get('/catalogos', [CasoController::class, 'catalogos'])->middleware($ver);
        Route::get('/estudiantes', [CasoController::class, 'estudiantes'])->middleware('permiso:pie.crear');
        Route::get('/casos', [CasoController::class, 'index'])->middleware($ver);
        Route::get('/casos/{caso}', [CasoController::class, 'show'])->middleware($ver)->whereNumber('caso');
        Route::post('/casos', [CasoController::class, 'store'])->middleware('permiso:pie.crear');
        Route::put('/casos/{caso}', [CasoController::class, 'update'])->middleware($editar)->whereNumber('caso');
        Route::delete('/casos/{caso}', [CasoController::class, 'destroy'])->middleware('permiso:pie.eliminar')->whereNumber('caso');

        // Documentos e imágenes (multipart); el archivo se sirve por la API, nunca público
        Route::post('/casos/{caso}/documentos', [DocumentoController::class, 'store'])->middleware($editar);
        Route::put('/documentos/{documento}', [DocumentoController::class, 'update'])->middleware($editar);
        Route::delete('/documentos/{documento}', [DocumentoController::class, 'destroy'])->middleware($editar);
        Route::get('/documentos/{documento}/archivo', [DocumentoController::class, 'archivo'])->middleware($ver);

        Route::post('/casos/{caso}/seguimientos', [SeguimientoController::class, 'store'])->middleware($editar);
        Route::put('/seguimientos/{seguimiento}', [SeguimientoController::class, 'update'])->middleware($editar);
        Route::delete('/seguimientos/{seguimiento}', [SeguimientoController::class, 'destroy'])->middleware($editar);
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
