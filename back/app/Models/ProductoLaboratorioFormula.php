<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProductoLaboratorioFormula extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'producto_laboratorio_formulas';

    protected $fillable = [
        'producto_id',
        'producto_laboratorio_dato_id',
        'nombre',
        'nombre_variable',
        'formula',
        'unidad',
        'orden',
        'visible',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['visible' => 'boolean'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function dato()
    {
        return $this->belongsTo(ProductoLaboratorioDato::class, 'producto_laboratorio_dato_id');
    }
}
