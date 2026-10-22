<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permiso "libros.ver": abrir el libro de clases de cualquier curso.
 * Lo reciben los roles base de gestión (UTP, Dirección, Administrador).
 */
return new class extends Migration
{
    private const ROLES = ['UTP', 'Dirección', 'Administrador'];

    public function up(): void
    {
        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'name' => 'libros.ver', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $id = DB::table('permissions')->where(['name' => 'libros.ver', 'guard_name' => 'web'])->value('id');

        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', self::ROLES)->pluck('id') as $rolId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rolId]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where(['name' => 'libros.ver', 'guard_name' => 'web'])->value('id');

        DB::table('model_has_permissions')->where('permission_id', $id)->delete();
        DB::table('role_has_permissions')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
