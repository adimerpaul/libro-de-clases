<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CompraDetalle extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'compra_detalles';

    protected $fillable = [
        'compra_id', 'producto_id', 'nombre', 'precio', 'cantidad',
        'total', 'factor', 'precio_venta', 'lote', 'fecha_vencimiento',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'precio' => 'decimal:2',
        'cantidad' => 'decimal:4',
        'total' => 'decimal:2',
        'factor' => 'decimal:4',
        'precio_venta' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ventaDetalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function bajaDetalles()
    {
        return $this->hasMany(BajaDetalle::class);
    }
}
