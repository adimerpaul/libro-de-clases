<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Registro de una intervención en el seguimiento de una alerta de bienestar. */
class IntervencionBienestar extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'ee_intervenciones';

    protected $fillable = ['alerta_id', 'fecha', 'tipo', 'descripcion', 'acuerdos', 'autor_id'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['fecha' => 'date:Y-m-d'];
    }

    public function alerta(): BelongsTo
    {
        return $this->belongsTo(AlertaBienestar::class, 'alerta_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
