<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('modulo')->nullable()->after('guard_name');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Crea los permisos faltantes (entre ellos 'Ver Dashboard') y marca su módulo.
        foreach (Permisos::MODULOS as $modulo => $permisos) {
            foreach ($permisos as $permiso) {
                Permission::updateOrCreate(
                    ['name' => $permiso, 'guard_name' => 'web'],
                    ['modulo' => $modulo],
                );
            }
        }

        // Permisos preexistentes fuera del catálogo: quedan agrupados en 'Otros'.
        Permission::whereNull('modulo')->update(['modulo' => 'Otros']);

        // 'Ver Dashboard' es nuevo: se otorga a los usuarios actuales para no
        // quitarle el panel a nadie. Se revoca desde la pantalla de usuarios.
        foreach (User::all() as $user) {
            $user->givePermissionTo('Ver Dashboard');
        }
    }

    public function down(): void
    {
        Permission::where('name', 'Ver Dashboard')->delete();

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('modulo');
        });
    }
};
