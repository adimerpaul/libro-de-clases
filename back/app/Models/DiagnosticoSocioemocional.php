<?php

namespace App\Models;

use App\Support\Socioemocional;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Diagnóstico socioemocional tipo DIA de un estudiante en un periodo: logro 0–100 por competencia. */
class DiagnosticoSocioemocional extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'ee_diagnosticos';

    protected $fillable = [
        'estudiante_id', 'curso_id', 'anio', 'periodo', 'fecha', 'observacion', 'aplicado_por_id',
        'autoconciencia', 'autorregulacion', 'conciencia_social', 'relaciones', 'decisiones',
    ];

    protected $hidden = ['deleted_at'];

    protected $appends = ['dimensiones', 'promedio'];

    protected function casts(): array
    {
        return array_fill_keys(array_keys(Socioemocional::COMPETENCIAS), 'integer') + ['anio' => 'integer', 'fecha' => 'date:Y-m-d'];
    }

    /** competencia => logro */
    public function puntajes(): array
    {
        return collect(array_keys(Socioemocional::COMPETENCIAS))->mapWithKeys(fn ($c) => [$c => $this->{$c}])->all();
    }

    /** Logro por dimensión DIA (personal, comunitario, ciudadano). */
    protected function dimensiones(): Attribute
    {
        return Attribute::get(fn () => Socioemocional::dimensiones($this->puntajes()));
    }

    protected function promedio(): Attribute
    {
        return Attribute::get(fn () => Socioemocional::promedio($this->puntajes()));
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por_id');
    }
}
