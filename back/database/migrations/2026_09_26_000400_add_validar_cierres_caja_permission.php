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
            ['name' => 'Validar Cierres Caja', 'guard_name' => 'web'],
            ['modulo' => 'Caja', 'descripcion' => Permisos::descripcionDe('Validar Cierres Caja')],
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::where('username', 'admin')->first()?->givePermissionTo('Validar Cierres Caja');
    }

    public function down(): void
    {
        Permission::where('name', 'Validar Cierres Caja')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
