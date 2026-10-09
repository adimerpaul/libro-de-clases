<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class InternacionItem extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = ['internacion_id', 'producto_id', 'user_id', 'nombre', 'cantidad', 'precio', 'total'];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio'   => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function setNombreAttribute($value): void
    {
        $this->attributes['nombre'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function internacion()
    {
        return $this->belongsTo(Internacion::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
