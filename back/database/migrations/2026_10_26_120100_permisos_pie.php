<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Módulo PIE ("pie.ver|crear|editar|eliminar") y rol "Educadora diferencial", que
 * administra los casos. UTP y Dirección los consultan; Administrador tiene todo.
 */
return new class extends Migration
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /** Rol => acciones que recibe. */
    private const ROLES = [
        'Educadora diferencial' => self::ACCIONES,
        'Administrador' => self::ACCIONES,
        'UTP' => ['ver'],
        'Dirección' => ['ver'],
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::ACCIONES as $accion) {
            DB::table('permissions')->insertOrIgnore([
                'name' => "pie.{$accion}", 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('roles')->insertOrIgnore([
            'name' => 'Educadora diferencial', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $this->nombres())->pluck('id', 'name');
        foreach (self::ROLES as $rol => $acciones) {
            $rolId = DB::table('roles')->where(['name' => $rol, 'guard_name' => 'web'])->value('id');
            foreach ($rolId ? $acciones : [] as $accion) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids["pie.{$accion}"], 'role_id' => $rolId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', $this->nombres())->pluck('id');

        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        $rolId = DB::table('roles')->where(['name' => 'Educadora diferencial', 'guard_name' => 'web'])->value('id');
        DB::table('model_has_roles')->where('role_id', $rolId)->delete();
        DB::table('roles')->where('id', $rolId)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function nombres(): array
    {
        return array_map(fn ($a) => "pie.{$a}", self::ACCIONES);
    }
};
