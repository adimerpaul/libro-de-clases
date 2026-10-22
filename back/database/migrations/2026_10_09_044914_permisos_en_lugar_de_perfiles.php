<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * El acceso deja de ser por perfiles (roles) y pasa a ser por permisos directos.
 * Crea el catálogo de permisos, traduce los perfiles que ya tenía cada usuario
 * y elimina los roles.
 */
return new class extends Migration
{
    /** Permisos de ese momento (luego fusionados en administracion.gestionar). */
    private const PERMISOS = [
        'clases.dictar', 'cursos.gestionar', 'asignaturas.gestionar',
        'docentes.gestionar', 'asignaciones.gestionar', 'usuarios.gestionar',
    ];

    /** Perfil anterior → permisos equivalentes. */
    private const EQUIVALENCIAS = [
        'Docente' => ['clases.dictar'],
        'UTP' => ['asignaturas.gestionar', 'asignaciones.gestionar'],
        'Dirección' => ['cursos.gestionar', 'docentes.gestionar', 'asignaciones.gestionar'],
        'Administrador' => ['cursos.gestionar', 'asignaturas.gestionar', 'docentes.gestionar', 'asignaciones.gestionar', 'usuarios.gestionar'],
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::PERMISOS as $permiso) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permiso, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $ids = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');

        $asignados = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->get(['model_has_roles.model_id', 'model_has_roles.model_type', 'roles.name']);

        foreach ($asignados as $fila) {
            foreach (self::EQUIVALENCIAS[$fila->name] ?? [] as $permiso) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $ids[$permiso],
                    'model_type' => $fila->model_type,
                    'model_id' => $fila->model_id,
                ]);
            }
        }

        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('roles')->delete();

        // Los tokens emitidos con "perfil:<nombre>" ya no significan nada
        DB::table('personal_access_tokens')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('model_has_permissions')->whereIn(
            'permission_id',
            DB::table('permissions')->whereIn('name', self::PERMISOS)->pluck('id'),
        )->delete();
        DB::table('permissions')->whereIn('name', self::PERMISOS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
