<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Baja de inventario de farmacia (salida que no es venta).
 * Solo las bajas en estado ACTIVO descuentan stock; anularla lo devuelve.
 */
class Baja extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    /** Motivos disponibles; el front los lee de /bajas/catalogos. */
    public const MOTIVOS = [
        'VENCIMIENTO' => 'Vencimiento',
        'CRUCE' => 'Cruce',
        'BONIFICACION' => 'Bonificación',
        'DETERIORO' => 'Deterioro o rotura',
        'MUESTRA_MEDICA' => 'Muestra médica',
        'DEVOLUCION_PROVEEDOR' => 'Devolución a proveedor',
        'PERDIDA' => 'Pérdida o robo',
        'AJUSTE_INVENTARIO' => 'Ajuste de inventario',
        'USO_INTERNO' => 'Uso interno / clínica',
        'OTRO' => 'Otro',
    ];

    protected $table = 'bajas';

    protected $fillable = [
        'user_id', 'fecha_hora', 'motivo', 'observacion', 'estado', 'total',
        'anulado_por', 'anulado_en', 'motivo_anulacion',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'anulado_en' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function detalles()
    {
        return $this->hasMany(BajaDetalle::class);
    }
}
