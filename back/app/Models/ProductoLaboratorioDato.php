<?php

namespace App\Models;

use App\Support\DescripcionHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProductoLaboratorioDato extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'producto_laboratorio_datos';

    protected $fillable = [
        'producto_id',
        'nombre',
        'nombre_variable',
        'unidad',
        'metodo',
        'muestra',
        'rango_referencia',
        'valor_defecto',
        'orden',
        'visible',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['visible' => 'boolean'];

    protected $appends = ['rango_referencia_html'];

    public function getRangoReferenciaHtmlAttribute(): string
    {
        return DescripcionHtml::mostrar($this->rango_referencia);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function formula()
    {
        return $this->hasOne(ProductoLaboratorioFormula::class);
    }

    public function opciones()
    {
        return $this->hasMany(ProductoLaboratorioDatoOpcion::class, 'producto_laboratorio_dato_id')
            ->orderBy('orden')
            ->orderBy('id');
    }
}
