<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Matrícula: el curso de un estudiante en un año escolar (el año lo da el curso). */
class Matricula extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    public const ESTADOS = ['activa', 'retirada', 'trasladada'];

    protected $fillable = [
        'estudiante_id', 'curso_id', 'numero_lista', 'fecha_matricula', 'estado', 'fecha_retiro', 'motivo_retiro',
    ];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'numero_lista' => 'integer',
            'fecha_matricula' => 'date:Y-m-d',
            'fecha_retiro' => 'date:Y-m-d',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    /** Matrícula vigente (no eliminada) del estudiante en ese año, en cualquier curso. */
    public static function delAnio(int $estudianteId, int $anio, ?int $excepto = null): ?self
    {
        return self::where('estudiante_id', $estudianteId)
            ->whereHas('curso', fn ($q) => $q->where('anio', $anio))
            ->when($excepto, fn ($q) => $q->where('id', '!=', $excepto))
            ->whereIn('estado', ['activa'])
            ->first();
    }

    /** Siguiente número de lista libre del curso. */
    public static function siguienteNumero(int $cursoId): int
    {
        return (int) self::where('curso_id', $cursoId)->max('numero_lista') + 1;
    }
}
