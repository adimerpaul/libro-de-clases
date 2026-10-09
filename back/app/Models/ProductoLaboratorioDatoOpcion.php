<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProductoLaboratorioDatoOpcion extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'producto_laboratorio_dato_opciones';

    protected $fillable = [
        'producto_laboratorio_dato_id',
        'valor',
        'orden',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function setValorAttribute($value): void
    {
        $this->attributes['valor'] = mb_strtoupper(trim((string) $value));
    }

    public function dato()
    {
        return $this->belongsTo(ProductoLaboratorioDato::class, 'producto_laboratorio_dato_id');
    }
}
