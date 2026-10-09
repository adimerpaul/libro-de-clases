<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Unidad extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'unidades';

    protected $fillable = ['nombre', 'abreviatura'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
