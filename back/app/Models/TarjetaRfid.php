<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Tarjeta RFID de un estudiante; "codigo" es el UID que entrega el lector. */
class TarjetaRfid extends Model
{
    use SoftDeletes;

    protected $table = 'tarjetas_rfid';

    protected $fillable = ['estudiante_id', 'codigo', 'activa'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}
