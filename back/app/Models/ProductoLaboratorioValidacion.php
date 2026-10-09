<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProductoLaboratorioValidacion extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'producto_laboratorio_validaciones';

    protected $fillable = [
        'producto_id',
        'expresion',
        'operador',
        'valor',
        'valor_hasta',
        'mensaje',
        'activo',
        'orden',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'activo' => 'boolean',
        'valor' => 'float',
        'valor_hasta' => 'float',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function setMensajeAttribute($value)
    {
        $this->attributes['mensaje'] = mb_strtoupper(trim((string) $value));
    }
}
