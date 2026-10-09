<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BajaDetalle extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'baja_detalles';

    protected $fillable = [
        'baja_id', 'producto_id', 'compra_detalle_id', 'nombre', 'lote',
        'fecha_vencimiento', 'cantidad', 'precio', 'total', 'motivo', 'observacion',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function baja()
    {
        return $this->belongsTo(Baja::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function compraDetalle()
    {
        return $this->belongsTo(CompraDetalle::class);
    }
}
