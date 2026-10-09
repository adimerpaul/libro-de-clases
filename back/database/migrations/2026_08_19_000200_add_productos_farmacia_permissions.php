<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const MODULO = 'Productos Farmacia';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = Permisos::MODULOS[self::MODULO];

        foreach ($permisos as $permiso) {
            Permission::updateOrCreate(
                ['name' => $permiso, 'guard_name' => 'web'],
                ['modulo' => self::MODULO],
            );
        }

        // El administrador arranca con el módulo completo; al resto se le asigna
        // desde la pantalla de usuarios.
        $admin = DB::table('users')->where('username', 'admin')->first();
        if ($admin) {
            User::find($admin->id)?->givePermissionTo($permisos);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', Permisos::MODULOS[self::MODULO])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
