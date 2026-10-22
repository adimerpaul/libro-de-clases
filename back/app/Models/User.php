<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
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

    /** Permisos efectivos: los de sus roles más los asignados directamente. */
    public function permisosEfectivos(): Collection
    {
        return $this->getAllPermissions()->pluck('name')->unique()->values();
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
            'permisos' => $this->permisosEfectivos(),
        ];
    }
}
