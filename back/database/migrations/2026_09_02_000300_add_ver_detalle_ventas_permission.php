<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISO = 'Ver Detalle Ventas';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::updateOrCreate(
            ['name' => self::PERMISO, 'guard_name' => 'web'],
            ['modulo' => 'Ventas', 'descripcion' => Permisos::descripcionDe(self::PERMISO)],
        );

        // Nadie pierde lo que ya hacía: quien hoy ve ventas conserva el detalle y
        // la reimpresión. A partir de aquí el permiso se quita a quien no deba tenerlo.
        User::whereHas('permissions', fn ($permiso) => $permiso->where('name', 'Ver Ventas'))
            ->get()
            ->each(fn (User $user) => $user->givePermissionTo(self::PERMISO));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISO)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
