<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Rules\RunValido;
use App\Support\Permisos;
use App\Support\Run;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Usuarios, sus roles y sus permisos directos. Cada acción exige "usuarios.<accion>"
 * o "docentes.<accion>"; con este último solo se trabaja con docentes y no se
 * cambian roles ni permisos.
 */
class UsuarioController extends Controller
{
    /** Quien edita no puede quitarse este permiso (se quedaría sin poder devolvérselo). */
    private const EDITAR_USUARIOS = 'usuarios.editar';

    public function index(Request $request)
    {
        $permiso = $this->gestionaUsuarios($request, Permisos::VER) ? $request->permiso : Permisos::DICTAR_CLASES;
        $rol = $this->gestionaUsuarios($request, Permisos::VER) ? $request->rol : null;

        return User::with(['roles.permissions:id,name', 'permissions:id,name'])
            ->withCount(['asignaciones', 'jefaturas'])
            // permission() de Spatie considera permisos directos y los heredados por rol
            ->when(in_array($permiso, Permisos::todos(), true), fn ($q) => $q->permission($permiso))
            ->when($rol, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $rol)))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => $this->datos($u));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $user = User::create($datos);
        if ($this->gestionaUsuarios($request, Permisos::CREAR)) {
            $user->syncRoles($datos['roles']);
            $user->syncPermissions($datos['permisos']);
        } else {
            $user->syncPermissions([Permisos::DICTAR_CLASES]);
        }

        return response()->json($this->datos($user->loadCount(['asignaciones', 'jefaturas'])), 201);
    }

    public function update(Request $request, User $usuario)
    {
        $this->soloDocentesSiCorresponde($request, $usuario, Permisos::EDITAR);
        $datos = $this->validar($request, $usuario);

        // Sin "Editar usuarios" (solo "Editar docentes") se conservan sus roles y permisos
        $roles = $this->gestionaUsuarios($request, Permisos::EDITAR) ? $datos['roles'] : $usuario->getRoleNames()->all();
        $permisos = $this->gestionaUsuarios($request, Permisos::EDITAR) ? $datos['permisos'] : $usuario->getPermissionNames()->all();
        $efectivos = $this->efectivos($roles, $permisos);

        $esUnoMismo = $usuario->is($request->user());
        if ($esUnoMismo && $usuario->tienePermiso(self::EDITAR_USUARIOS) && ! in_array(self::EDITAR_USUARIOS, $efectivos, true)) {
            abort(422, 'No puede quitarse a sí mismo el permiso de editar usuarios.');
        }
        if ($esUnoMismo && ! empty($datos['bloqueado'])) {
            abort(422, 'No puede bloquear su propio usuario.');
        }
        if (! in_array(Permisos::DICTAR_CLASES, $efectivos, true)
            && ($usuario->asignaciones()->exists() || $usuario->jefaturas()->exists())) {
            abort(422, 'El usuario tiene asignaturas o jefaturas de curso; reasígnelas antes de quitarle el permiso Dictar clases.');
        }

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);
        $usuario->syncRoles($roles);
        $usuario->syncPermissions($permisos);

        // Un usuario bloqueado pierde sus sesiones abiertas
        if ($usuario->bloqueado) {
            $usuario->tokens()->delete();
        }

        return $this->datos($usuario->fresh()->loadCount(['asignaciones', 'jefaturas']));
    }

    public function destroy(Request $request, User $usuario)
    {
        $this->soloDocentesSiCorresponde($request, $usuario, Permisos::ELIMINAR);

        if ($usuario->is($request->user())) {
            abort(422, 'No puede eliminar su propio usuario.');
        }
        if ($usuario->asignaciones()->exists() || $usuario->jefaturas()->exists()) {
            return response()->json([
                'message' => 'El usuario tiene asignaturas o jefaturas de curso. Reasígnelas o bloquee al usuario.',
            ], 409);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }

    /** ¿Tiene "usuarios.<accion>"? Si no, solo llegó aquí con "docentes.<accion>". */
    private function gestionaUsuarios(Request $request, string $accion): bool
    {
        return $request->user()->tienePermiso(Permisos::de('usuarios', $accion));
    }

    /** Con solo el permiso de Docentes no se puede tocar a quien no dicta clases. */
    private function soloDocentesSiCorresponde(Request $request, User $usuario, string $accion): void
    {
        if (! $this->gestionaUsuarios($request, $accion) && ! $usuario->tienePermiso(Permisos::DICTAR_CLASES)) {
            abort(403, 'Solo puede modificar usuarios que dictan clases.');
        }
    }

    /** Permisos que resultarían de esos roles más esos permisos directos. */
    private function efectivos(array $roles, array $permisos): array
    {
        $deRoles = Role::with('permissions:id,name')->whereIn('name', $roles)->get()
            ->flatMap(fn ($r) => $r->permissions->pluck('name'));

        return $deRoles->merge($permisos)->unique()->values()->all();
    }

    private function datos(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'run' => $u->run,
            'telefono' => $u->telefono,
            'bloqueado' => $u->bloqueado,
            'roles' => $u->getRoleNames()->values(),
            // Asignados directamente (los de sus roles van en permisos_efectivos)
            'permisos' => $u->getPermissionNames()->values(),
            'permisos_efectivos' => $u->permisosEfectivos(),
            'asignaciones_count' => $u->asignaciones_count ?? 0,
            'jefaturas_count' => $u->jefaturas_count ?? 0,
        ];
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->email)),
            'run' => Run::normalizar($request->run ?: null),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($usuario)->withoutTrashed()],
            'run' => ['nullable', new RunValido, Rule::unique('users')->ignore($usuario)->withoutTrashed()],
            'telefono' => ['nullable', 'string', 'max:50'],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:6'],
            'bloqueado' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name')->where('guard_name', 'web')->withoutTrashed()],
            'permisos' => ['array'],
            'permisos.*' => [Rule::in(Permisos::todos())],
        ], [], ['name' => 'nombre', 'password' => 'clave', 'run' => 'RUN']) + ['roles' => [], 'permisos' => []];
    }
}
