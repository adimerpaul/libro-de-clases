<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ServicioLaboratorioReactivo extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'servicio_laboratorio_reactivos';

    protected $fillable = ['producto_id', 'reactivo_id', 'cantidad'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['cantidad' => 'decimal:4'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function reactivo()
    {
        return $this->belongsTo(Reactivo::class);
    }
}
