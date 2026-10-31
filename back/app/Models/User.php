<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    use AuditableTrait, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'run',
        'telefono',
        'password',
        'avatar',
        'bloqueado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /** Prefijo de la ability del token que guarda el perfil (rol) elegido en el login. */
    public const ABILITY_ROL = 'rol:';

    protected $auditExclude = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'bloqueado' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** Asignaturas que dicta en cada curso. */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'docente_id');
    }

    /** Cursos de los que es profesor(a) jefe. */
    public function jefaturas(): HasMany
    {
        return $this->hasMany(Curso::class, 'profesor_jefe_id');
    }

    /** Estudiantes de los que es apoderado/tutor (portal de familia). */
    public function vinculos(): HasMany
    {
        return $this->hasMany(VinculoApoderado::class);
    }

    /** Con soft delete la FK no actúa: se replica aquí. */
    protected static function booted(): void
    {
        static::deleting(fn (User $user) => $user->vinculos()->get()->each->delete());
    }

    /**
     * Rol (perfil) elegido en el login para la sesión actual, o null si la sesión usa
     * todos sus roles. Solo aplica al usuario autenticado con su token.
     */
    public function rolActivo(): ?string
    {
        $token = $this->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return null;
        }

        foreach ($token->abilities ?? [] as $ability) {
            if (str_starts_with($ability, self::ABILITY_ROL)) {
                return substr($ability, strlen(self::ABILITY_ROL));
            }
        }

        return null;
    }

    /**
     * Permisos efectivos: los de sus roles más los asignados directamente.
     * Con un perfil elegido en el login, solo los de ese rol más los directos
     * (si le quitan ese rol, la sesión se queda solo con los directos).
     */
    public function permisosEfectivos(): Collection
    {
        $rol = $this->rolActivo();
        if ($rol === null) {
            return $this->getAllPermissions()->pluck('name')->unique()->values();
        }

        $delRol = $this->roles->firstWhere('name', $rol)?->permissions ?? collect();

        return $delRol->merge($this->getDirectPermissions())->pluck('name')->unique()->values();
    }

    public function tienePermiso(string $permiso): bool
    {
        return $this->permisosEfectivos()->contains($permiso);
    }

    /** Datos de sesión que consume el front. */
    public function datosSesion(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'run' => $this->run,
            'avatar' => $this->avatar,
            'roles' => $this->getRoleNames()->values(),
            'rol_activo' => $this->rolActivo(),
            'permisos' => $this->permisosEfectivos(),
        ];
    }
}
