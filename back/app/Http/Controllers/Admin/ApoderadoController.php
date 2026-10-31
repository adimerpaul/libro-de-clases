<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\User;
use App\Models\VinculoApoderado;
use App\Support\Permisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Vínculos apoderado ↔ estudiante (ficha del estudiante). La cuenta del apoderado es un
 * usuario con "familia.ver" (rol Apoderado); en el portal de familia solo ve a sus vinculados.
 */
class ApoderadoController extends Controller
{
    public const ROL = 'Apoderado';

    public function index(Estudiante $estudiante)
    {
        return [
            'vinculos' => $estudiante->vinculos()->with('user:id,name,email,run,telefono')->get()
                ->filter->user->values(),
            'parentescos' => VinculoApoderado::PARENTESCOS,
        ];
    }

    /** Cuentas con el rol Apoderado (búsqueda por nombre, correo o RUN), con cuántos estudiantes tienen vinculados. */
    public function cuentas(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $run = preg_replace('/[^0-9kK]/', '', (string) $request->q);

        return self::conRolApoderado()
            ->when($request->q, fn ($q, $texto) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$texto}%")
                ->orWhere('email', 'like', "%{$texto}%")
                ->when(strlen($run) >= 3, fn ($q) => $q->orWhere('run', 'like', "%{$run}%"))))
            ->withCount('vinculos as estudiantes_count')
            ->orderBy('name')->limit(50)->get(['id', 'name', 'email', 'run', 'telefono']);
    }

    /** Vincula una cuenta existente (user_id) o crea la cuenta con el rol Apoderado (cuenta). */
    public function store(Request $request, Estudiante $estudiante)
    {
        $datos = $request->validate([
            'parentesco' => ['required', Rule::in(VinculoApoderado::PARENTESCOS)],
            'user_id' => ['required_without:cuenta', 'nullable', 'integer', 'exists:users,id,deleted_at,NULL'],
            'cuenta' => ['required_without:user_id', 'nullable', 'array'],
            'cuenta.name' => ['required_with:cuenta', 'string', 'max:150'],
            'cuenta.email' => ['required_with:cuenta', 'email', 'max:150', Rule::unique('users', 'email')->withoutTrashed()],
            'cuenta.password' => ['required_with:cuenta', 'string', 'min:6', 'max:100'],
            'cuenta.telefono' => ['nullable', 'string', 'max:50'],
        ], [], ['cuenta.name' => 'nombre', 'cuenta.email' => 'correo', 'cuenta.password' => 'clave', 'user_id' => 'cuenta']);

        if (empty($datos['user_id'])) {
            abort_unless($request->user()->tienePermiso(Permisos::de('usuarios', Permisos::CREAR)), 403, 'Crear cuentas requiere el permiso de crear usuarios.');
        } else {
            abort_unless(self::conRolApoderado()->whereKey($datos['user_id'])->exists(), 422, 'Esa cuenta no tiene el rol Apoderado.');
            abort_if($estudiante->vinculos()->where('user_id', $datos['user_id'])->exists(), 422, 'Esa cuenta ya está vinculada a este estudiante.');
        }

        $vinculo = DB::transaction(function () use ($datos, $estudiante) {
            $userId = $datos['user_id'] ?? User::create($datos['cuenta'])->assignRole(self::ROL)->id;

            return $estudiante->vinculos()->create(['user_id' => $userId, 'parentesco' => $datos['parentesco']]);
        });

        return response()->json($vinculo->load('user:id,name,email,run,telefono'), 201);
    }

    public function destroy(VinculoApoderado $vinculo)
    {
        $vinculo->delete();

        return response()->json(['message' => 'Vínculo eliminado']);
    }

    /** Usuarios con el rol Apoderado (se consulta model_has_roles: Role::users() falla bajo sanctum). */
    public static function conRolApoderado(): Builder
    {
        return User::query()->whereIn('id', DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where(['roles.name' => self::ROL, 'roles.guard_name' => 'web', 'model_has_roles.model_type' => (new User)->getMorphClass()])
            ->select('model_has_roles.model_id'));
    }
}
