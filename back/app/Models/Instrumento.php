<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Instrumento de evaluación (prueba, control, guía evaluada, ticket de salida). */
class Instrumento extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    public const TIPOS = ['Prueba', 'Control', 'Guía evaluada', 'Ticket de salida'];

    public const ESTADOS = ['borrador', 'listo'];

    protected $fillable = [
        'docente_id', 'asignacion_id', 'objetivo_aprendizaje_id', 'nombre', 'tipo',
        'fecha_aplicacion', 'exigencia', 'instrucciones', 'estado',
    ];

    /** Soft delete en cascada de sus preguntas. */
    protected static function booted(): void
    {
        static::deleting(fn (Instrumento $instrumento) => $instrumento->preguntas()->delete());
    }

    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date:Y-m-d',
            'exigencia' => 'integer',
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivoAprendizaje::class, 'objetivo_aprendizaje_id');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(InstrumentoPregunta::class)->orderBy('orden');
    }

    public function puntajeTotal(): float
    {
        return (float) $this->preguntas->sum('puntaje');
    }
}
