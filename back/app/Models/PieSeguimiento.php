<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Registro de la bitácora PIE: sesión, co-docencia, entrevista familiar, reunión de equipo… */
class PieSeguimiento extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'pie_seguimientos';

    protected $fillable = ['pie_caso_id', 'fecha', 'tipo', 'area', 'duracion_minutos', 'descripcion', 'acuerdos', 'autor_id'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['fecha' => 'date:Y-m-d', 'duracion_minutos' => 'integer'];
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(PieCaso::class, 'pie_caso_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
