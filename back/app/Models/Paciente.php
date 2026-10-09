<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Paciente extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = ['seguro_id', 'nombre_completo', 'sexo', 'fecha_nacimiento', 'ci', 'estado', 'direccion', 'telefono'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['fecha_nacimiento' => 'date:Y-m-d'];

    protected $appends = ['estado_internacion', 'edad'];

    public function setNombreCompletoAttribute($value): void
    {
        $this->attributes['nombre_completo'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setDireccionAttribute($value): void
    {
        $this->attributes['direccion'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    /** Años cumplidos según la fecha de nacimiento (null si no está registrada). */
    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }

    public function internaciones()
    {
        return $this->hasMany(Internacion::class)->orderBy('fecha_ingreso');
    }

    public function seguro()
    {
        return $this->belongsTo(Seguro::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function latestInternacion()
    {
        return $this->hasOne(Internacion::class)->latestOfMany('id');
    }

    public function getEstadoInternacionAttribute(): string
    {
        $ultima = $this->latestInternacion;
        if (! $ultima) {
            return 'NO_INTERNADO';
        }

        return $ultima->fecha_alta ? 'ALTA' : 'INTERNADO';
    }
}
