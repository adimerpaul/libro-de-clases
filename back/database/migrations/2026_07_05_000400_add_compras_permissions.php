<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'Ver Compras', 'Crear Compras', 'Editar Compras', 'Eliminar Compras',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = DB::table('users')->where('username', 'admin')->first();
        if ($admin) {
            $adminUser = \App\Models\User::find($admin->id);
            $adminUser->givePermissionTo($this->permissions);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->delete();
    }
};
