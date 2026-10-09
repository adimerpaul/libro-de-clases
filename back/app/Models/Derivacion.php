<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Derivacion extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'derivaciones';

    protected $fillable = [
        'user_id', 'fecha', 'paciente', 'laboratorio_destino',
        'servicio', 'observaciones', 'imagen',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    protected $appends = ['imagen_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getImagenUrlAttribute(): string
    {
        return '/images/derivaciones/'.$this->imagen;
    }
}
