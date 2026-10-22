<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Roles de Spatie: conjuntos de permisos con nombre (p. ej. "UTP") que se
 * asignan a los usuarios. Un usuario recibe todos los permisos de sus roles.
 */
class RolController extends Controller
{
    public function index()
    {
        $usuarios = $this->usuariosPorRol();

        return Role::with('permissions:id,name')->orderBy('name')->get()
            ->map(fn (Role $r) => $this->datos($r, $usuarios[$r->id] ?? 0));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        // Un rol eliminado con el mismo nombre se recupera (el nombre es único en BD)
        $rol = Role::onlyTrashed()->where(['name' => $datos['name'], 'guard_name' => 'web'])->first();
        $rol ? $rol->restore() : $rol = Role::create(['name' => $datos['name'], 'guard_name' => 'web']);
        $rol->syncPermissions($datos['permisos']);

        return response()->json($this->datos($rol, 0), 201);
    }

    public function update(Request $request, Role $rol)
    {
        $datos = $this->validar($request, $rol);

        // Que quien edita no pierda "Editar usuarios" por cambiar un rol
        $clave = 'usuarios.editar';
        if (! in_array($clave, $datos['permisos'], true) && $rol->hasPermissionTo($clave)) {
            $user = $request->user();
            $otraFuente = $user->hasDirectPermission($clave)
                || $user->roles->where('id', '!=', $rol->id)->contains(fn ($r) => $r->hasPermissionTo($clave));
            if ($user->hasRole($rol) && ! $otraFuente) {
                abort(422, 'Perdería su propio permiso de editar usuarios. Asígneselo directamente antes de quitarlo del rol.');
            }
        }

        $rol->update(['name' => $datos['name']]);
        $rol->syncPermissions($datos['permisos']);

        return $this->datos($rol, $this->usuariosPorRol()[$rol->id] ?? 0);
    }

    public function destroy(Role $rol)
    {
        if ($this->usuariosPorRol()[$rol->id] ?? 0) {
            return response()->json([
                'message' => 'El rol está asignado a usuarios. Quíteselo primero a cada uno.',
            ], 409);
        }

        $rol->delete();

        return response()->json(['message' => 'Rol eliminado']);
    }

    private function datos(Role $r, int $usuarios): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'permisos' => $r->permissions->pluck('name')->values(),
            'users_count' => $usuarios,
        ];
    }

    /**
     * role_id → cantidad de usuarios. Se cuenta en la tabla pivote porque Role::users()
     * resuelve el modelo según el guard actual (sanctum), que no tiene uno.
     */
    private function usuariosPorRol(): array
    {
        return DB::table('model_has_roles')
            ->where('model_type', (new User)->getMorphClass())
            // Los usuarios eliminados (soft delete) no cuentan
            ->whereIn('model_id', User::query()->select('id'))
            ->selectRaw('role_id, count(*) as total')->groupBy('role_id')
            ->pluck('total', 'role_id')->all();
    }

    private function validar(Request $request, ?Role $rol = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles')->where('guard_name', 'web')->ignore($rol)->withoutTrashed()],
            'permisos' => ['array'],
            'permisos.*' => [Rule::in(Permisos::todos())],
        ], [], ['name' => 'nombre']) + ['permisos' => []];
    }
}
