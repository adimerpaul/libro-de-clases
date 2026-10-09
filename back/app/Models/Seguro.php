<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Seguro extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'seguros';

    protected $fillable = ['nombre', 'nit'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function pacientes()
    {
        return $this->hasMany(Paciente::class);
    }

    public function internaciones()
    {
        return $this->hasMany(Internacion::class);
    }
}
