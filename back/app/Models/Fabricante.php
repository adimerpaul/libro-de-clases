<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Fabricante extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'fabricantes';

    protected $fillable = ['nombre', 'pais'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
