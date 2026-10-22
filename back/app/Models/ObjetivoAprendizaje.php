<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** OA del catálogo curricular versionado. */
class ObjetivoAprendizaje extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'objetivos_aprendizaje';

    protected $fillable = [
        'codigo', 'nivel', 'asignatura_id', 'unidad', 'texto', 'habilidades', 'indicadores',
        'fuente', 'fuente_url', 'version_curricular', 'vigente_desde', 'vigente_hasta', 'fuente_hash', 'es_demo',
    ];

    protected function casts(): array
    {
        return [
            'habilidades' => 'array',
            'indicadores' => 'array',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'es_demo' => 'boolean',
        ];
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    /** OA vigentes a hoy. */
    public function scopeVigentes($query)
    {
        return $query->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', now()->toDateString()));
    }
}
