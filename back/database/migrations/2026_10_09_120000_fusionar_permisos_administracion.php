<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cursos, asignaturas, docentes y asignación docente pasan a un solo menú
 * "Administrador" con un solo permiso. Quien tenía alguno de los cuatro
 * permisos anteriores recibe el nuevo.
 */
return new class extends Migration
{
    private const NUEVO = 'administracion.gestionar';

    private const ANTERIORES = ['cursos.gestionar', 'asignaturas.gestionar', 'docentes.gestionar', 'asignaciones.gestionar'];

    public function up(): void
    {
        $this->reemplazar(self::ANTERIORES, [self::NUEVO]);
    }

    public function down(): void
    {
        $this->reemplazar([self::NUEVO], self::ANTERIORES);
    }

    /** Da $nuevos a quien tenía alguno de $viejos y elimina $viejos. */
    private function reemplazar(array $viejos, array $nuevos): void
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

        DB::table('model_has_permissions')->whereIn('permission_id', $idsViejos)->delete();
        DB::table('role_has_permissions')->whereIn('permission_id', $idsViejos)->delete();
        DB::table('permissions')->whereIn('id', $idsViejos)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
