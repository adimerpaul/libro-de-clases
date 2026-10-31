<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permiso "student360.ver": vista 360 de los estudiantes de todos los cursos.
 * Lo reciben UTP, Dirección, Administrador y Educadora diferencial (el docente ve sus cursos con clases.dictar).
 */
return new class extends Migration
{
    private const ROLES = ['UTP', 'Dirección', 'Administrador', 'Educadora diferencial'];

    public function up(): void
    {
        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'name' => 'student360.ver', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $id = DB::table('permissions')->where(['name' => 'student360.ver', 'guard_name' => 'web'])->value('id');

        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', self::ROLES)->pluck('id') as $rolId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rolId]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where(['name' => 'student360.ver', 'guard_name' => 'web'])->value('id');

        DB::table('model_has_permissions')->where('permission_id', $id)->delete();
        DB::table('role_has_permissions')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
