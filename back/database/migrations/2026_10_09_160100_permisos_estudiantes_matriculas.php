<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Módulos Estudiantes y Matrículas con su CRUD. Quien (usuario o rol) podía
 * ver/crear/editar/eliminar cursos recibe la misma acción en ambos módulos.
 */
return new class extends Migration
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    private const MODULOS = ['estudiantes', 'matriculas'];

    public function up(): void
    {
        $now = now();
        foreach (self::MODULOS as $modulo) {
            foreach (self::ACCIONES as $accion) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => "{$modulo}.{$accion}", 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        $ids = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');

        foreach (self::ACCIONES as $accion) {
            $base = $ids["cursos.{$accion}"] ?? null;
            if (! $base) {
                continue;
            }
            $nuevos = array_map(fn ($m) => $ids["{$m}.{$accion}"], self::MODULOS);

            foreach (DB::table('model_has_permissions')->where('permission_id', $base)->get() as $fila) {
                foreach ($nuevos as $id) {
                    DB::table('model_has_permissions')->insertOrIgnore([
                        'permission_id' => $id, 'model_type' => $fila->model_type, 'model_id' => $fila->model_id,
                    ]);
                }
            }
            foreach (DB::table('role_has_permissions')->where('permission_id', $base)->pluck('role_id') as $rolId) {
                foreach ($nuevos as $id) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rolId]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $nombres = [];
        foreach (self::MODULOS as $modulo) {
            foreach (self::ACCIONES as $accion) {
                $nombres[] = "{$modulo}.{$accion}";
            }
        }
        $ids = DB::table('permissions')->whereIn('name', $nombres)->pluck('id');

        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
