<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Planificacion extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'planificaciones';

    public const ESTADOS = ['borrador', 'planificada', 'realizada'];

    protected $fillable = [
        'docente_id', 'asignacion_id', 'objetivo_aprendizaje_id', 'fecha', 'hora_inicio', 'duracion_minutos',
        'titulo', 'inicio', 'desarrollo', 'cierre', 'evidencia', 'recursos', 'adecuaciones', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'duracion_minutos' => 'integer',
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
}
