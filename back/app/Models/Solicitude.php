<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Solicitude extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'paciente_id', 'doctor_id', 'user_id', 'codigo_solicitud', 'codigo_verificacion',
        'fecha_solicitud', 'hora_solicitud', 'diagnostico_clinico',
        'observaciones', 'estado', 'total',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'fecha_solicitud' => 'date:Y-m-d',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Solicitude $solicitude) {
            if ($solicitude->codigo_verificacion) {
                return;
            }

            do {
                $codigo = Str::random(32);
            } while (static::withTrashed()->where('codigo_verificacion', $codigo)->exists());

            $solicitude->codigo_verificacion = $codigo;
        });
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function laboratorioItems()
    {
        return $this->hasMany(SolicitudLaboratorioItem::class, 'solicitude_id')->orderBy('orden')->orderBy('id');
    }
}
