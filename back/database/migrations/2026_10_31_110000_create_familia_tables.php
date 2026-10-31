<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Portal de familia:
 * - vinculos_apoderado: qué estudiantes ve cada cuenta de apoderado/tutor.
 * - calificaciones: nota (1,0–7,0) de cada estudiante en un instrumento; la registra el docente.
 * - Permiso "familia.ver" y rol "Apoderado", que solo ve a sus pupilos.
 */
return new class extends Migration
{
    private const PERMISO = 'familia.ver';

    private const ROL = 'Apoderado';

    public function up(): void
    {
        Schema::create('vinculos_apoderado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->string('parentesco', 30); // ver App\Models\VinculoApoderado::PARENTESCOS
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'estudiante_id']);
        });

        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->decimal('puntaje', 6, 1);
            // Calculada con App\Support\EscalaNotas al guardar (no se edita a mano)
            $table->decimal('nota', 2, 1);
            $table->string('observacion', 300)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['instrumento_id', 'estudiante_id']);
            $table->index('estudiante_id');
        });

        $now = now();
        DB::table('permissions')->insertOrIgnore(['name' => self::PERMISO, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('roles')->insertOrIgnore(['name' => self::ROL, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_has_permissions')->insertOrIgnore([
            'permission_id' => DB::table('permissions')->where(['name' => self::PERMISO, 'guard_name' => 'web'])->value('id'),
            'role_id' => DB::table('roles')->where(['name' => self::ROL, 'guard_name' => 'web'])->value('id'),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
        Schema::dropIfExists('vinculos_apoderado');

        $permisoId = DB::table('permissions')->where('name', self::PERMISO)->value('id');
        DB::table('model_has_permissions')->where('permission_id', $permisoId)->delete();
        DB::table('role_has_permissions')->where('permission_id', $permisoId)->delete();
        DB::table('permissions')->where('id', $permisoId)->delete();

        $rolId = DB::table('roles')->where(['name' => self::ROL, 'guard_name' => 'web'])->value('id');
        DB::table('model_has_roles')->where('role_id', $rolId)->delete();
        DB::table('role_has_permissions')->where('role_id', $rolId)->delete();
        DB::table('roles')->where('id', $rolId)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
