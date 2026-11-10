<?php

namespace App\Models;

use App\Support\Niveles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Curso extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = ['anio', 'nivel', 'letra', 'profesor_jefe_id'];

    protected $appends = ['nombre'];

    /** La FK borra en cascada solo en un DELETE real; con soft delete se replica aquí. */
    protected static function booted(): void
    {
        static::deleting(function (Curso $curso) {
            $curso->asignaciones()->get()->each->delete();
            $curso->matriculas()->get()->each->delete();
            SesionSocioemocional::where('curso_id', $curso->id)->get()->each->delete();
            DiagnosticoSocioemocional::where('curso_id', $curso->id)->get()->each->delete();
            PieLibroHoja::where('curso_id', $curso->id)->get()->each->delete();
        });
    }

    protected function casts(): array
    {
        return ['anio' => 'integer'];
    }

    /** "2° Medio B" */
    protected function nombre(): Attribute
    {
        return Attribute::get(fn () => "{$this->nivel} {$this->letra}");
    }

    public function profesorJefe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesor_jefe_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    /** Nómina del curso (todas las matrículas; las vigentes con ->activas()). */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    /**
     * Cursos que ve $user: todos si tiene $permisoTodos; si no, solo donde es
     * profesor(a) jefe o dicta alguna asignatura.
     */
    public function scopeVisiblesPara(Builder $query, User $user, string $permisoTodos): Builder
    {
        return $query->when(! $user->tienePermiso($permisoTodos), fn ($q) => $q->where(
            fn ($q) => $q->where('profesor_jefe_id', $user->id)
                ->orWhereHas('asignaciones', fn ($q) => $q->where('docente_id', $user->id))
        ));
    }

    /** Ordena por nivel (Pre-kínder → 4° Medio) y letra. */
    public static function ordenar($cursos)
    {
        return $cursos->sortBy([
            fn ($a, $b) => Niveles::orden($a->nivel) <=> Niveles::orden($b->nivel),
            fn ($a, $b) => strcmp($a->letra, $b->letra),
        ])->values();
    }
}
