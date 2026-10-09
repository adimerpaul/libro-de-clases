<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::updateOrCreate(
            ['name' => 'Ver Montos Caja', 'guard_name' => 'web'],
            ['modulo' => 'Ventas'],
        );

        // Permiso restrictivo: solo lo reciben los administradores (quienes ya
        // gestionan permisos). Al resto se le asigna desde la pantalla de usuarios.
        foreach (User::all() as $user) {
            if ($user->hasPermissionTo('Gestionar Permisos')) {
                $user->givePermissionTo('Ver Montos Caja');
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', 'Ver Montos Caja')->delete();
    }
};
