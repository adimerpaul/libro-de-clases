<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Especialidad extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'especialidades';

    protected $fillable = ['nombre'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function setNombreAttribute($value): void
    {
        $this->attributes['nombre'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function doctores()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_especialidad');
    }
}
