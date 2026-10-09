<?php

use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Permission::updateOrCreate(
            ['name' => 'Ver Ventas Clientes', 'guard_name' => 'web'],
            ['modulo' => 'Ventas', 'descripcion' => Permisos::descripcionDe('Ver Ventas Clientes')],
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'Ver Ventas Clientes')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
