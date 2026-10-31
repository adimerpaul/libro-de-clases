<?php

namespace App\Models;

use App\Support\Socioemocional;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Seguimiento individual de bienestar de un estudiante (confidencial). El protocolo DEC
 * es una alerta de tipo "desregulacion" con su fase.
 */
class AlertaBienestar extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'ee_alertas';

    protected $fillable = [
        'estudiante_id', 'anio', 'tipo', 'intensidad', 'estado', 'fase', 'fecha', 'descripcion', 'lugar',
        'reportado_por_id', 'responsable_id', 'equipo', 'familia_contactada', 'proxima_revision',
        'derivacion', 'fecha_cierre', 'motivo_cierre',
    ];

    protected $hidden = ['deleted_at'];

    protected static function booted(): void
    {
        static::deleting(fn (AlertaBienestar $a) => $a->intervenciones()->get()->each->delete());
    }

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'fecha' => 'date:Y-m-d',
            'proxima_revision' => 'date:Y-m-d',
            'fecha_cierre' => 'date:Y-m-d',
            'familia_contactada' => 'boolean',
        ];
    }

    public function scopeAbiertas($query)
    {
        return $query->whereIn('estado', Socioemocional::ABIERTAS);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function intervenciones(): HasMany
    {
        return $this->hasMany(IntervencionBienestar::class, 'alerta_id');
    }
}
