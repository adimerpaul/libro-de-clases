<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Documento o imagen del expediente PIE (informes, FUDEI, autorizaciones, evidencias). */
class PieDocumento extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    /** Disco privado: datos sensibles, se descargan por la API con permiso pie.ver. */
    public const DISCO = 'local';

    protected $table = 'pie_documentos';

    protected $fillable = [
        'pie_caso_id', 'tipo', 'titulo', 'fecha', 'descripcion',
        'archivo_path', 'archivo_nombre', 'archivo_mime', 'archivo_tamano', 'autor_id',
    ];

    protected $hidden = ['archivo_path', 'deleted_at'];

    protected $appends = ['es_imagen'];

    protected function casts(): array
    {
        return ['fecha' => 'date:Y-m-d', 'archivo_tamano' => 'integer'];
    }

    public function getEsImagenAttribute(): bool
    {
        return str_starts_with((string) $this->archivo_mime, 'image/');
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(PieCaso::class, 'pie_caso_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
