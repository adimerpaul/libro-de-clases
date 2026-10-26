<?php

namespace App\Models;

use App\Support\Pie;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Ficha del estudiante. Su curso en cada año escolar está en Matricula. */
class Estudiante extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'run', 'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 'sexo', 'direccion',
        'apoderado_nombre', 'apoderado_run', 'apoderado_telefono', 'apoderado_email', 'pie', 'observaciones',
    ];

    protected $appends = ['nombre_completo'];

    protected $hidden = ['deleted_at'];

    /** La FK borra en cascada solo en un DELETE real; con soft delete se replica aquí. */
    protected static function booted(): void
    {
        static::deleting(function (Estudiante $e) {
            $e->matriculas()->get()->each->delete();
            $e->pieCasos()->get()->each->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date:Y-m-d',
            'pie' => 'boolean',
        ];
    }

    /** "Soto Muñoz, Marcela Andrea" (así se ordena la nómina) */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->apellido_paterno} {$this->apellido_materno}").", {$this->nombres}");
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function pieCasos(): HasMany
    {
        return $this->hasMany(PieCaso::class);
    }

    /** "pie" = su caso del año más reciente sigue vigente (en evaluación o activo). */
    public function sincronizarPie(): void
    {
        if ($this->trashed()) {
            return;
        }
        $ultimo = $this->pieCasos()->orderByDesc('anio')->first();
        $this->forceFill(['pie' => $ultimo && in_array($ultimo->estado, Pie::VIGENTES, true)])->saveQuietly();
    }
}
