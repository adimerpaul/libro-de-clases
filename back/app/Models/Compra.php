<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Compra extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'compras';

    protected $fillable = [
        'user_id', 'proveedor_id', 'fecha_hora', 'nro_factura',
        'tipo_pago', 'comentario', 'estado', 'total',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'total'      => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }
}
