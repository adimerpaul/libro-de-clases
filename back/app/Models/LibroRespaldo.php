<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** PDF del libro de clases generado como respaldo (huella SHA-256 + folio). */
class LibroRespaldo extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'libro_respaldos';

    protected $fillable = ['curso_id', 'user_id', 'hash', 'folio', 'secciones'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return ['secciones' => 'array'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
