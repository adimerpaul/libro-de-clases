<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cada módulo del menú Administrador pasa a tener su CRUD: "<modulo>.ver",
 * ".crear", ".editar" y ".eliminar". Usuarios y roles que tenían los permisos
 * amplios anteriores reciben todos los CRUD equivalentes.
 */
return new class extends Migration
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /** Permiso anterior → módulos cuyo CRUD completo lo reemplaza. */
    private const EQUIVALENCIAS = [
        'administracion.gestionar' => ['cursos', 'asignaturas', 'docentes', 'asignaciones'],
        'usuarios.gestionar' => ['usuarios', 'roles'],
    ];

    public function up(): void
    {
        foreach (self::EQUIVALENCIAS as $anterior => $modulos) {
            $this->reemplazar([$anterior], $this->crud($modulos), true);
        }
    }

    public function down(): void
    {
        foreach (self::EQUIVALENCIAS as $anterior => $modulos) {
            $this->reemplazar($this->crud($modulos), [$anterior], true);
        }
    }

    private function crud(array $modulos): array
    {
        return array_merge(...array_map(
            fn ($m) => array_map(fn ($a) => "{$m}.{$a}", self::ACCIONES),
            $modulos,
        ));
    }

    /** Quien (usuario o rol) tenía alguno de $viejos recibe todos los $nuevos; luego se borran $viejos. */
    private function reemplazar(array $viejos, array $nuevos, bool $borrarViejos): void
    {
        $now = now();
        foreach ($nuevos as $permiso) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permiso, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $idsViejos = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $viejos)->pluck('id');
        $idsNuevos = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $nuevos)->pluck('id');

        $usuarios = DB::table('model_has_permissions')->whereIn('permission_id', $idsViejos)
            ->select('model_type', 'model_id')->distinct()->get();
        foreach ($usuarios as $u) {
            foreach ($idsNuevos as $id) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $id, 'model_type' => $u->model_type, 'model_id' => $u->model_id,
                ]);
            }
        }

        $roles = DB::table('role_has_permissions')->whereIn('permission_id', $idsViejos)->distinct()->pluck('role_id');
        foreach ($roles as $rolId) {
            foreach ($idsNuevos as $id) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rolId]);
            }
        }

        if ($borrarViejos) {
            DB::table('model_has_permissions')->whereIn('permission_id', $idsViejos)->delete();
            DB::table('role_has_permissions')->whereIn('permission_id', $idsViejos)->delete();
            DB::table('permissions')->whereIn('id', $idsViejos)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
