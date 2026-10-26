<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Lectura de una tarjeta en un lector: evento físico, no la asistencia oficial. */
class MarcaRfid extends Model
{
    use SoftDeletes;

    protected $table = 'marcas_rfid';

    protected $fillable = ['codigo', 'tarjeta_id', 'estudiante_id', 'curso_id', 'lector', 'tipo', 'marcado_en'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['marcado_en' => 'datetime:Y-m-d H:i:s'];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }
}
