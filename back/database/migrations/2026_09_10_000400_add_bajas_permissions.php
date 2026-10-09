<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISOS = ['Ver Bajas', 'Crear Bajas', 'Anular Bajas'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISOS as $permiso) {
            Permission::updateOrCreate(
                ['name' => $permiso, 'guard_name' => 'web'],
                ['modulo' => 'Bajas', 'descripcion' => Permisos::descripcionDe($permiso)],
            );
        }
        // Quien ya administra el inventario de farmacia puede dar de baja.
        User::whereHas('permissions', fn ($query) => $query->where('name', 'Editar Productos Farmacia'))
            ->get()->each(fn (User $user) => $user->givePermissionTo(self::PERMISOS));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISOS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
