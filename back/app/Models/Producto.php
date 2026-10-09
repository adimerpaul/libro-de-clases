<?php

namespace App\Models;

use App\Support\DescripcionHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Producto extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo', 'nombre', 'nombre_comercial', 'descripcion', 'marca',
        'fabricante_id', 'unidad_id',
        'tipo_producto_id', 'precio', 'precio_seguro',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $appends = ['descripcion_html'];

    public function getDescripcionHtmlAttribute(): string
    {
        return DescripcionHtml::mostrar($this->descripcion);
    }

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_seguro' => 'decimal:2',
    ];

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class);
    }

    public function inventarios()
    {
        return $this->hasMany(ProductoInventario::class);
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function ventaDetalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function bajaDetalles()
    {
        return $this->hasMany(BajaDetalle::class);
    }

    public function laboratorioDatos()
    {
        return $this->hasMany(ProductoLaboratorioDato::class)->orderBy('orden')->orderBy('id');
    }

    public function laboratorioFormulas()
    {
        return $this->hasMany(ProductoLaboratorioFormula::class)->orderBy('orden')->orderBy('id');
    }

    public function laboratorioValidaciones()
    {
        return $this->hasMany(ProductoLaboratorioValidacion::class)->orderBy('orden')->orderBy('id');
    }
}
