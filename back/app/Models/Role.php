<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Role as SpatieRole;

/** Rol de Spatie con eliminación lógica (config/permission.php → models.role). */
class Role extends SpatieRole implements Auditable
{
    use AuditableTrait, SoftDeletes;
}
