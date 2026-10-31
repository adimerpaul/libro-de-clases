<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Entrada de la Libreta del Estudiante (comunicación establecimiento ↔ familia). */
class LibretaEntrada extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'libreta_entradas';

    protected $fillable = [
        'estudiante_id', 'autor_id', 'origen', 'tipo', 'asunto', 'mensaje', 'fecha_evento', 'hora_evento',
        'requiere_respuesta', 'respuesta', 'comentario_respuesta', 'respondido_por', 'respondido_en', 'leido_por', 'leido_en',
    ];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'fecha_evento' => 'date:Y-m-d',
            'requiere_respuesta' => 'boolean',
            'respondido_en' => 'datetime',
            'leido_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    public function leidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leido_por');
    }
}
