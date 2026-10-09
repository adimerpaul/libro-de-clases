<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISOS = [
        'Ver Caja Administrativa', 'Crear Caja Administrativa',
        'Editar Caja Administrativa', 'Eliminar Caja Administrativa',
        'Ver Caja General', 'Crear Caja General',
        'Editar Caja General', 'Eliminar Caja General',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISOS as $permiso) {
            Permission::updateOrCreate(
                ['name' => $permiso, 'guard_name' => 'web'],
                ['modulo' => str_contains($permiso, 'Administrativa') ? 'Caja Administrativa' : 'Caja General'],
            );
        }

        $admin = DB::table('users')->where('username', 'admin')->first();
        if ($admin) {
            User::find($admin->id)?->givePermissionTo(self::PERMISOS);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISOS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
