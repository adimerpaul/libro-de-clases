<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Doctor extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'doctores';

    protected $fillable = [
        'nombre', 'ci', 'telefono', 'email', 'registro', 'estado',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function setNombreAttribute($value): void
    {
        $this->attributes['nombre'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setCiAttribute($value): void
    {
        $this->attributes['ci'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setRegistroAttribute($value): void
    {
        $this->attributes['registro'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'doctor_especialidad');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
