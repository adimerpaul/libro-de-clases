<?php

namespace App\Models;

use App\Support\Pie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Caso PIE de un estudiante en un año escolar, con su expediente y bitácora. */
class PieCaso extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'pie_casos';

    protected $fillable = [
        'estudiante_id', 'anio', 'estado', 'diagnostico', 'tipo_nee', 'diagnostico_detalle',
        'fecha_ingreso', 'fecha_evaluacion', 'fecha_reevaluacion', 'responsable_id', 'equipo', 'apoyos',
        'horas_semanales', 'fortalezas', 'barreras', 'necesidades_apoyo', 'acuerdos_familia', 'observaciones',
        'fecha_egreso', 'motivo_egreso',
    ];

    protected $hidden = ['deleted_at'];

    protected static function booted(): void
    {
        // La FK borra en cascada solo en un DELETE real; con soft delete se replica aquí
        static::deleting(function (PieCaso $caso) {
            $caso->documentos()->get()->each->delete();
            $caso->seguimientos()->get()->each->delete();
        });

        // La marca "PIE" de la ficha del estudiante la decide su caso del año más reciente
        static::saved(fn (PieCaso $caso) => $caso->estudiante?->sincronizarPie());
        static::deleted(fn (PieCaso $caso) => $caso->estudiante?->sincronizarPie());
    }

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'fecha_ingreso' => 'date:Y-m-d',
            'fecha_evaluacion' => 'date:Y-m-d',
            'fecha_reevaluacion' => 'date:Y-m-d',
            'fecha_egreso' => 'date:Y-m-d',
            'equipo' => 'array',
            'apoyos' => 'array',
            'horas_semanales' => 'integer',
        ];
    }

    public function scopeVigentes($query)
    {
        return $query->whereIn('estado', Pie::VIGENTES);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(PieDocumento::class);
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(PieSeguimiento::class);
    }
}
