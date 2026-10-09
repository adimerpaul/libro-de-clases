<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TipoProducto extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'tipo_productos';

    protected $fillable = ['tipo_producto_padre_id', 'nombre', 'color', 'es_laboratorio', 'orden'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'es_laboratorio' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (TipoProducto $tipo) {
            // El padre manda: un tipo bajo LABORATORIO es de laboratorio y uno
            // bajo cualquier otro padre no lo es. Sin padre, las áreas creadas
            // desde la pantalla de laboratorio caen en el padre de laboratorio.
            if ($tipo->tipo_producto_padre_id) {
                $padre = TipoProductoPadre::find($tipo->tipo_producto_padre_id);
                if ($padre) {
                    $tipo->es_laboratorio = $padre->es_laboratorio;
                }
            } elseif ($tipo->es_laboratorio) {
                $tipo->tipo_producto_padre_id = TipoProductoPadre::laboratorio()->orderBy('orden')->value('id');
            }
        });
    }

    public function scopeLaboratorio($query)
    {
        return $query->where('es_laboratorio', true);
    }

    public function padre()
    {
        return $this->belongsTo(TipoProductoPadre::class, 'tipo_producto_padre_id');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
