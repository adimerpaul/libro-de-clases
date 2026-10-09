<?php

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
            $table->string('descripcion', 500)->nullable()->after('modulo');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Crea los permisos que falten y escribe módulo + descripción de cada uno.
        foreach (Permisos::MODULOS as $modulo => $permisos) {
            foreach ($permisos as $permiso) {
                Permission::updateOrCreate(
                    ['name' => $permiso, 'guard_name' => 'web'],
                    ['modulo' => $modulo, 'descripcion' => Permisos::descripcionDe($permiso)],
                );
            }
        }

        // Permisos preexistentes fuera del catálogo: quedan agrupados en 'Otros'
        // y sin descripción; el tooltip muestra el nombre del permiso.
        Permission::whereNull('modulo')->update(['modulo' => 'Otros']);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });
    }
};
