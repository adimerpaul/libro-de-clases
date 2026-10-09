<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Permission::updateOrCreate(
            ['name' => 'Ver Compras Pacientes', 'guard_name' => 'web'],
            ['modulo' => 'Ventas', 'descripcion' => Permisos::descripcionDe('Ver Compras Pacientes')],
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::where('username', 'admin')->first()?->givePermissionTo('Ver Compras Pacientes');
    }

    public function down(): void
    {
        Permission::where('name', 'Ver Compras Pacientes')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
