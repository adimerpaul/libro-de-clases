<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eliminación lógica (deleted_at) en todas las tablas de modelos. Los únicos
 * de nombre/código/curso/correo pasan a validarse en código ignorando los
 * eliminados, para poder volver a crear un registro que se eliminó.
 * (users ya tenía deleted_at; roles conserva su único y se restaura al recrearlo.)
 */
return new class extends Migration
{
    private const TABLAS = [
        'asignaturas', 'cursos', 'asignaciones', 'objetivos_aprendizaje',
        'planificaciones', 'instrumentos', 'instrumento_preguntas', 'roles',
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->softDeletes());
        }

        Schema::table('asignaturas', function (Blueprint $t) {
            $t->dropUnique(['nombre']);
            $t->dropUnique(['codigo']);
            $t->index('codigo');
        });

        Schema::table('cursos', function (Blueprint $t) {
            $t->dropUnique(['anio', 'nivel', 'letra']);
            $t->index(['anio', 'nivel', 'letra']);
        });

        // La FK de curso_id usaba el único compuesto como índice: se le da uno propio antes
        Schema::table('asignaciones', fn (Blueprint $t) => $t->index('curso_id'));
        Schema::table('asignaciones', fn (Blueprint $t) => $t->dropUnique(['curso_id', 'asignatura_id']));

        Schema::table('users', function (Blueprint $t) {
            $t->dropUnique(['email']);
            $t->dropUnique(['run']);
            $t->index('email');
            $t->index('run');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['email']);
            $t->dropIndex(['run']);
            $t->unique('email');
            $t->unique('run');
        });

        Schema::table('asignaciones', fn (Blueprint $t) => $t->unique(['curso_id', 'asignatura_id']));
        Schema::table('asignaciones', fn (Blueprint $t) => $t->dropIndex(['curso_id']));

        Schema::table('cursos', function (Blueprint $t) {
            $t->dropIndex(['anio', 'nivel', 'letra']);
            $t->unique(['anio', 'nivel', 'letra']);
        });

        Schema::table('asignaturas', function (Blueprint $t) {
            $t->dropIndex(['codigo']);
            $t->unique('nombre');
            $t->unique('codigo');
        });

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropSoftDeletes());
        }
    }
};
