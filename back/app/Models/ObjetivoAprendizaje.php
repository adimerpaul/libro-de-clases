<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** OA del catálogo curricular versionado. */
class ObjetivoAprendizaje extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'objetivos_aprendizaje';

    protected $fillable = [
        'codigo', 'nivel', 'asignatura_id', 'curriculum_objective_id', 'unidad', 'texto', 'contenidos', 'habilidades', 'actitudes', 'indicadores',
        'instrumentos', 'estrategias', 'inicio', 'desarrollo', 'cierre', 'recursos', 'tiempo_espacio',
        'fuente', 'fuente_url', 'version_curricular', 'vigente_desde', 'vigente_hasta', 'fuente_hash', 'es_demo',
    ];

    protected function casts(): array
    {
        return [
            'habilidades' => 'array',
            'actitudes' => 'array',
            'indicadores' => 'array',
            'instrumentos' => 'array',
            'recursos' => 'array',
            'vigente_desde' => 'date:Y-m-d',
            'vigente_hasta' => 'date:Y-m-d',
            'es_demo' => 'boolean',
        ];
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function planificaciones(): HasMany
    {
        return $this->hasMany(Planificacion::class, 'objetivo_aprendizaje_id');
    }

    public function instrumentosEvaluacion(): HasMany
    {
        return $this->hasMany(Instrumento::class, 'objetivo_aprendizaje_id');
    }

    public function recursosBiblioteca(): HasMany
    {
        return $this->hasMany(Recurso::class, 'objetivo_aprendizaje_id');
    }

    /** OA vigentes a hoy. */
    public function scopeVigentes($query)
    {
        return $query->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', now()->toDateString()));
    }
}
