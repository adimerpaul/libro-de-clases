<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles base de Spatie (conjuntos de permisos). No cambia lo que ya tiene cada
 * usuario: sus permisos directos se mantienen y los roles se asignan desde
 * Usuarios y permisos.
 */
return new class extends Migration
{
    /** Rol → permisos que agrupa (nombres de ese momento, ver App\Support\Permisos). */
    private const ROLES = [
        'Docente' => ['clases.dictar'],
        'UTP' => ['clases.dictar', 'administracion.gestionar'],
        'Dirección' => ['administracion.gestionar'],
        'Administrador' => ['administracion.gestionar', 'usuarios.gestionar'],
    ];

    public function up(): void
    {
        $now = now();
        $permisos = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');

        foreach (self::ROLES as $rol => $nombres) {
            DB::table('roles')->insertOrIgnore([
                'name' => $rol, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $rolId = DB::table('roles')->where(['name' => $rol, 'guard_name' => 'web'])->value('id');

            foreach ($nombres as $nombre) {
                if (isset($permisos[$nombre])) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permisos[$nombre], 'role_id' => $rolId,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('roles')->whereIn('name', array_keys(self::ROLES))->pluck('id');

        DB::table('model_has_roles')->whereIn('role_id', $ids)->delete();
        DB::table('role_has_permissions')->whereIn('role_id', $ids)->delete();
        DB::table('roles')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
