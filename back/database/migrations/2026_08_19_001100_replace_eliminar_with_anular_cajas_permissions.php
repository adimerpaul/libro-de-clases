<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const CAMBIOS = [
        'Eliminar Caja Administrativa' => 'Anular Caja Administrativa',
        'Eliminar Caja General' => 'Anular Caja General',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::CAMBIOS as $anterior => $nuevo) {
            $permisoAnterior = Permission::where('name', $anterior)->first();
            $usuarios = $permisoAnterior?->users()->pluck('users.id') ?? collect();
            $permisoNuevo = Permission::updateOrCreate(
                ['name' => $nuevo, 'guard_name' => 'web'],
                ['modulo' => str_contains($nuevo, 'Administrativa') ? 'Caja Administrativa' : 'Caja General'],
            );
            foreach ($usuarios as $userId) {
                User::find($userId)?->givePermissionTo($permisoNuevo);
            }
            $permisoAnterior?->delete();
        }

        $admin = DB::table('users')->where('username', 'admin')->first();
        if ($admin) {
            User::find($admin->id)?->givePermissionTo(array_values(self::CAMBIOS));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::CAMBIOS as $anterior => $nuevo) {
            Permission::updateOrCreate(
                ['name' => $anterior, 'guard_name' => 'web'],
                ['modulo' => str_contains($anterior, 'Administrativa') ? 'Caja Administrativa' : 'Caja General'],
            );
            Permission::where('name', $nuevo)->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
