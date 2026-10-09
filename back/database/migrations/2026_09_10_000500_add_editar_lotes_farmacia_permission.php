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
            ['name' => 'Editar Lotes Farmacia', 'guard_name' => 'web'],
            ['modulo' => 'Productos Farmacia', 'descripcion' => Permisos::descripcionDe('Editar Lotes Farmacia')],
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'Editar Lotes Farmacia')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
