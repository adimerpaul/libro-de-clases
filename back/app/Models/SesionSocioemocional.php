<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Sesión o actividad socioemocional de un curso (Educación Emocional). */
class SesionSocioemocional extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'ee_sesiones';

    protected $fillable = [
        'curso_id', 'responsable_id', 'objetivo_aprendizaje_id', 'titulo', 'tipo', 'competencia',
        'fecha', 'fecha_fin', 'estado', 'participantes', 'descripcion', 'evidencias',
    ];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['fecha' => 'date:Y-m-d', 'fecha_fin' => 'date:Y-m-d', 'participantes' => 'integer'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivoAprendizaje::class, 'objetivo_aprendizaje_id');
    }
}
