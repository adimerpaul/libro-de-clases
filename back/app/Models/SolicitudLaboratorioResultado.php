<?php

namespace App\Models;

use App\Support\DescripcionHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SolicitudLaboratorioResultado extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'solicitud_laboratorio_item_id', 'producto_laboratorio_dato_id',
        'nombre', 'nombre_variable', 'unidad', 'metodo', 'muestra', 'rango_referencia',
        'formula', 'valor', 'orden', 'visible',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = ['visible' => 'boolean'];

    protected $appends = ['rango_referencia_html'];

    public function getRangoReferenciaHtmlAttribute(): string
    {
        return DescripcionHtml::mostrar($this->rango_referencia);
    }

    public function item()
    {
        return $this->belongsTo(SolicitudLaboratorioItem::class, 'solicitud_laboratorio_item_id');
    }
}
