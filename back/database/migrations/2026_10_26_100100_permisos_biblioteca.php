<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Módulo Biblioteca ("recursos.ver|crear|editar|eliminar"): administra el
 * catálogo de recursos pedagógicos. Lo reciben UTP, Dirección y Administrador.
 * Los docentes consultan la biblioteca con "clases.dictar".
 */
return new class extends Migration
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    private const ROLES = ['UTP', 'Dirección', 'Administrador'];

    public function up(): void
    {
        $now = now();
        foreach (self::ACCIONES as $accion) {
            DB::table('permissions')->insertOrIgnore([
                'name' => "recursos.{$accion}", 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $this->nombres())->pluck('id');

        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', self::ROLES)->pluck('id') as $rolId) {
            foreach ($ids as $id) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rolId]);
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function nombres(): array
    {
        return array_map(fn ($a) => "recursos.{$a}", self::ACCIONES);
    }
};
