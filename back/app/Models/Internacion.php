<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Internacion extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'internaciones';

    protected $fillable = [
        'paciente_id', 'seguro_id', 'fecha_ingreso', 'tipo_paciente', 'fecha_alta', 'codigo_hc', 'sala',
        // Seguimiento de facturación al seguro
        'entrega_informe', 'respuesta_auditoria', 'fecha_facturacion', 'monto_facturado',
        'fecha_cancelacion', 'tipo_pago', 'observacion_seguro',
        // Pago total del paciente
        'pagado_en', 'pagado_por_id', 'venta_id', 'monto_pagado', 'pago_tipo', 'pago_observacion',
    ];

    protected $casts = [
        'pagado_en' => 'datetime',
        'monto_pagado' => 'decimal:2',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $appends = ['dias_internado', 'seguimiento_estado', 'seguimiento_llenados', 'pagada'];

    /**
     * Campos que la planilla del seguro exige para dar por cerrada una internación.
     * Mientras falte alguno, la internación queda como PENDIENTE.
     */
    public const CAMPOS_SEGUIMIENTO = [
        'entrega_informe',
        'respuesta_auditoria',
        'fecha_facturacion',
        'monto_facturado',
        'fecha_cancelacion',
        'tipo_pago',
    ];

    public function getDiasInternadoAttribute(): ?int
    {
        if (! $this->fecha_ingreso) {
            return null;
        }
        $hasta = $this->fecha_alta ? Carbon::parse($this->fecha_alta) : now();

        return max(1, Carbon::parse($this->fecha_ingreso)->diffInDays($hasta));
    }

    public function setTipoPacienteAttribute($value): void
    {
        $this->attributes['tipo_paciente'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setSalaAttribute($value): void
    {
        $this->attributes['sala'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setTipoPagoAttribute($value): void
    {
        $this->attributes['tipo_pago'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    /** Cuántos de los campos exigidos por la planilla ya están llenos. */
    public function getSeguimientoLlenadosAttribute(): int
    {
        return count(array_filter(
            self::CAMPOS_SEGUIMIENTO,
            fn ($campo) => $this->{$campo} !== null && $this->{$campo} !== ''
        ));
    }

    /** COMPLETADO solo cuando la planilla del seguro está llena por entero. */
    public function getSeguimientoEstadoAttribute(): string
    {
        return $this->seguimiento_llenados === count(self::CAMPOS_SEGUIMIENTO)
            ? 'COMPLETADO'
            : 'PENDIENTE';
    }

    /** Una internación pagada ya no se modifica: ni sus datos ni sus cargos. */
    public function getPagadaAttribute(): bool
    {
        return $this->pagado_en !== null;
    }

    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'pagado_por_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function seguro()
    {
        return $this->belongsTo(Seguro::class);
    }

    public function items()
    {
        return $this->hasMany(InternacionItem::class)->orderBy('created_at');
    }
}
