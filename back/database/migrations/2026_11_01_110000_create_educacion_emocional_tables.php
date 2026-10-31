<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Educación Emocional / Socioemocional (ui3/educacion-emocional.html). Catálogos en
 * App\Support\Socioemocional.
 * - ee_sesiones: sesiones y actividades socioemocionales por curso (vinculables a un OA de Orientación).
 * - ee_alertas: seguimiento individual de bienestar; el protocolo DEC es una alerta de desregulación con su fase.
 * - ee_intervenciones: bitácora de cada alerta.
 * - ee_diagnosticos: diagnóstico tipo DIA por estudiante y periodo (logro 0–100 por competencia).
 * Permisos "emocional.ver|crear|editar|eliminar" y "emocional.reportar" (el docente activa el
 * protocolo DEC para estudiantes de sus cursos). Rol nuevo "Convivencia".
 */
return new class extends Migration
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /** Rol => permisos que recibe */
    private const ROLES = [
        'Convivencia' => ['emocional.ver', 'emocional.crear', 'emocional.editar', 'emocional.eliminar', 'emocional.reportar'],
        'Administrador' => ['emocional.ver', 'emocional.crear', 'emocional.editar', 'emocional.eliminar', 'emocional.reportar'],
        'Dirección' => ['emocional.ver'],
        'UTP' => ['emocional.ver'],
        'Educadora diferencial' => ['emocional.ver', 'emocional.reportar'],
        'Docente' => ['emocional.reportar'],
    ];

    public function up(): void
    {
        Schema::create('ee_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('objetivo_aprendizaje_id')->nullable()->constrained('objetivos_aprendizaje')->nullOnDelete();
            $table->string('titulo', 200);
            $table->string('tipo', 40);
            $table->string('competencia', 30);
            $table->date('fecha');
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('planificada');
            $table->unsignedSmallInteger('participantes')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('evidencias')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['curso_id', 'fecha']);
        });

        Schema::create('ee_alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->string('tipo', 20);        // desregulacion | bienestar | riesgo
            $table->string('intensidad', 10);  // baja | media | alta
            $table->string('estado', 20)->default('activa');
            $table->string('fase', 20)->nullable(); // protocolo DEC: prevencion | contencion | intervencion | seguimiento
            $table->date('fecha');
            $table->text('descripcion');
            $table->string('lugar', 150)->nullable();
            $table->foreignId('reportado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('equipo', 200)->nullable(); // "Psicóloga + Profesor jefe"
            $table->boolean('familia_contactada')->default(false);
            $table->date('proxima_revision')->nullable();
            $table->string('derivacion', 300)->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->string('motivo_cierre', 300)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['anio', 'estado']);
            $table->index('estudiante_id');
        });

        Schema::create('ee_intervenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerta_id')->constrained('ee_alertas')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tipo', 30);
            $table->text('descripcion');
            $table->text('acuerdos')->nullable();
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['alerta_id', 'fecha']);
        });

        Schema::create('ee_diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->string('periodo', 20); // inicial | intermedio | final
            $table->date('fecha');
            // Logro 0–100 por competencia (App\Support\Socioemocional::COMPETENCIAS)
            foreach (['autoconciencia', 'autorregulacion', 'conciencia_social', 'relaciones', 'decisiones'] as $c) {
                $table->unsignedTinyInteger($c)->nullable();
            }
            $table->string('observacion', 500)->nullable();
            $table->foreignId('aplicado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['curso_id', 'anio', 'periodo']);
            $table->index(['estudiante_id', 'anio']);
        });

        $now = now();
        foreach ([...array_map(fn ($a) => "emocional.{$a}", self::ACCIONES), 'emocional.reportar'] as $permiso) {
            DB::table('permissions')->insertOrIgnore(['name' => $permiso, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('roles')->insertOrIgnore(['name' => 'Convivencia', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);

        $ids = DB::table('permissions')->where('guard_name', 'web')->where('name', 'like', 'emocional.%')->pluck('id', 'name');
        foreach (self::ROLES as $rol => $permisos) {
            $rolId = DB::table('roles')->where(['name' => $rol, 'guard_name' => 'web'])->value('id');
            foreach ($rolId ? $permisos : [] as $p) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids[$p], 'role_id' => $rolId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('ee_diagnosticos');
        Schema::dropIfExists('ee_intervenciones');
        Schema::dropIfExists('ee_alertas');
        Schema::dropIfExists('ee_sesiones');

        $ids = DB::table('permissions')->where('name', 'like', 'emocional.%')->pluck('id');
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        $rolId = DB::table('roles')->where(['name' => 'Convivencia', 'guard_name' => 'web'])->value('id');
        DB::table('model_has_roles')->where('role_id', $rolId)->delete();
        DB::table('role_has_permissions')->where('role_id', $rolId)->delete();
        DB::table('roles')->where('id', $rolId)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
