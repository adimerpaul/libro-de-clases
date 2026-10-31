<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/** Cuenta de apoderado/tutor (usuario con "familia.ver") vinculada a un estudiante. */
class VinculoApoderado extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    public const PARENTESCOS = ['Madre', 'Padre', 'Tutor(a) legal', 'Abuelo(a)', 'Tío(a)', 'Hermano(a)', 'Otro'];

    protected $table = 'vinculos_apoderado';

    protected $fillable = ['user_id', 'estudiante_id', 'parentesco'];

    protected $hidden = ['deleted_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}
