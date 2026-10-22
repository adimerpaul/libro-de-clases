<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Asignacion extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'asignaciones';

    protected $fillable = ['curso_id', 'asignatura_id', 'docente_id', 'horas_semanales'];

    /** Soft delete en cascada: planificaciones e instrumentos de esta asignación. */
    protected static function booted(): void
    {
        static::deleting(function (Asignacion $asignacion) {
            Planificacion::where('asignacion_id', $asignacion->id)->get()->each->delete();
            Instrumento::where('asignacion_id', $asignacion->id)->get()->each->delete();
        });
    }

    protected function casts(): array
    {
        return ['horas_semanales' => 'integer'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }
}
