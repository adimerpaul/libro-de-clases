<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Recurso de la biblioteca pedagógica (archivo adjunto, enlace o ambos). */
class Recurso extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    public const TIPOS = ['Actividad', 'Presentación', 'Guía', 'Video', 'Material didáctico', 'DUA / PIE', 'Evaluación'];

    /** Disco donde se guardan los archivos (privado: se descargan por la API). */
    public const DISCO = 'local';

    /** Relaciones que acompañan al recurso en la API. */
    public const RELACIONES = ['asignatura:id,nombre,codigo,color', 'objetivo:id,codigo,texto', 'autor:id,name'];

    protected $fillable = [
        'titulo', 'tipo', 'descripcion', 'nivel', 'asignatura_id', 'objetivo_aprendizaje_id',
        'url', 'archivo_path', 'archivo_nombre', 'archivo_tamano', 'publicado', 'autor_id',
    ];

    protected $hidden = ['archivo_path'];

    protected $appends = ['tiene_archivo'];

    protected function casts(): array
    {
        return ['publicado' => 'boolean', 'archivo_tamano' => 'integer'];
    }

    public function getTieneArchivoAttribute(): bool
    {
        return (bool) $this->archivo_path;
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivoAprendizaje::class, 'objetivo_aprendizaje_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
