<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Nota (1,0–7,0) de un estudiante en un instrumento; la nota sale del puntaje con EscalaNotas. */
class Calificacion extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'calificaciones';

    protected $fillable = ['instrumento_id', 'estudiante_id', 'puntaje', 'nota', 'observacion'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['puntaje' => 'float', 'nota' => 'float'];
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}
