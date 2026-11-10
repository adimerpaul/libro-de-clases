<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Una hoja del Libro de Clases PIE de un curso (documento JSON por clave). */
class PieLibroHoja extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'pie_libro_hojas';

    protected $fillable = ['curso_id', 'clave', 'datos', 'firmado_por', 'firmado_en', 'updated_by'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['datos' => 'object', 'firmado_en' => 'datetime'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function firmante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'firmado_por');
    }

    /** Lo que recibe el front: datos + firma (si la hay). */
    public function paraFront(): array
    {
        return [
            'clave' => $this->clave,
            'datos' => $this->datos ?: new \stdClass,
            'firma' => $this->firmado_en ? ['nombre' => $this->firmante?->name, 'fecha' => $this->firmado_en->format('Y-m-d H:i')] : null,
            'actualizado' => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
