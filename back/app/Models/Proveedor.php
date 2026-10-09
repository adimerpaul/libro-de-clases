<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Proveedor extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre', 'nit', 'razon_social', 'contacto',
        'telefono', 'email', 'direccion', 'estado', 'observacion',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }
}
