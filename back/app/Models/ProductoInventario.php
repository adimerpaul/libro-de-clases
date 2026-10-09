<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProductoInventario extends Model implements AuditableContract
{
    use AuditableTrait;

    protected $table = 'producto_inventarios';

    protected $fillable = [
        'producto_id', 'responsable', 'lote',
        'cantidad_principal', 'unidad_principal_id',
        'cantidad_secundaria', 'unidad_secundaria_id',
        'origen_archivo',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function unidadPrincipal()
    {
        return $this->belongsTo(Unidad::class, 'unidad_principal_id');
    }

    public function unidadSecundaria()
    {
        return $this->belongsTo(Unidad::class, 'unidad_secundaria_id');
    }
}
