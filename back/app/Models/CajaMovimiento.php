<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CajaMovimiento extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'user_id', 'caja', 'tipo', 'fecha_hora', 'categoria', 'concepto',
        'descripcion', 'beneficiario', 'documento', 'importe', 'estado',
        'anulado_por_id', 'anulado_en', 'motivo_anulacion',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'anulado_en' => 'datetime',
        'importe' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por_id');
    }

    public function setMotivoAnulacionAttribute($value): void
    {
        $this->attributes['motivo_anulacion'] = $this->mayuscula($value);
    }

    public function setCategoriaAttribute($value): void
    {
        $this->attributes['categoria'] = $this->mayuscula($value);
    }

    public function setConceptoAttribute($value): void
    {
        $this->attributes['concepto'] = $this->mayuscula($value);
    }

    public function setDescripcionAttribute($value): void
    {
        $this->attributes['descripcion'] = $this->mayuscula($value);
    }

    public function setBeneficiarioAttribute($value): void
    {
        $this->attributes['beneficiario'] = $this->mayuscula($value);
    }

    public function setDocumentoAttribute($value): void
    {
        $this->attributes['documento'] = $this->mayuscula($value);
    }

    private function mayuscula($value): ?string
    {
        return $value !== null && trim((string) $value) !== ''
            ? mb_strtoupper(trim((string) $value))
            : null;
    }
}
