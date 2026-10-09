<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SolicitudLaboratorioItem extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = ['solicitude_id', 'producto_id', 'producto_nombre', 'precio', 'orden'];

    protected $hidden = ['deleted_at'];

    protected $casts = ['precio' => 'decimal:2'];

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function resultados()
    {
        return $this->hasMany(SolicitudLaboratorioResultado::class)->orderBy('orden')->orderBy('id');
    }
}
