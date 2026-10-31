<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Libreta del Estudiante: comunicación bidireccional establecimiento ↔ familia con
 * trazabilidad (emisor, fecha, lectura y respuesta/firma). Las entradas no se editan
 * ni se borran desde la aplicación. Permiso "libreta.ver": el personal abre la libreta
 * de cualquier estudiante (el docente, la de sus cursos; el apoderado, la de sus hijos).
 */
return new class extends Migration
{
    private const PERMISO = 'libreta.ver';

    private const ROLES = ['Administrador', 'Dirección', 'UTP'];

    public function up(): void
    {
        Schema::create('libreta_entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('autor_id')->constrained('users');
            $table->string('origen', 20); // establecimiento | familia
            $table->string('tipo', 30); // App\Support\Libreta::TIPOS
            $table->string('asunto', 150);
            $table->text('mensaje')->nullable();
            $table->date('fecha_evento')->nullable();
            $table->time('hora_evento')->nullable();
            $table->boolean('requiere_respuesta')->default(false);
            $table->string('respuesta', 20)->nullable();
            $table->string('comentario_respuesta', 300)->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users');
            $table->timestamp('respondido_en')->nullable();
            $table->foreignId('leido_por')->nullable()->constrained('users');
            $table->timestamp('leido_en')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['estudiante_id', 'created_at']);
        });

        $now = now();
        DB::table('permissions')->insertOrIgnore(['name' => self::PERMISO, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        $permisoId = DB::table('permissions')->where(['name' => self::PERMISO, 'guard_name' => 'web'])->value('id');
        foreach (self::ROLES as $rol) {
            $rolId = DB::table('roles')->where(['name' => $rol, 'guard_name' => 'web'])->value('id');
            if ($rolId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permisoId, 'role_id' => $rolId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('libreta_entradas');

        $permisoId = DB::table('permissions')->where('name', self::PERMISO)->value('id');
        DB::table('model_has_permissions')->where('permission_id', $permisoId)->delete();
        DB::table('role_has_permissions')->where('permission_id', $permisoId)->delete();
        DB::table('permissions')->where('id', $permisoId)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
