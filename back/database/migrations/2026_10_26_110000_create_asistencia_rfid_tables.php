<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Asistencia RFID: cada estudiante tiene su tarjeta y cada lectura del portal es una
 * marca. La marca es el evento físico, no la asistencia oficial (ui3/ARQUITECTURA.md):
 * el motor propone y una persona autorizada valida. Las marcas las escribe el servidor
 * en tiempo real (sockets/), que las emite por Socket.IO.
 * Permiso "rfid.ver": ver el panel en vivo (Administrador, Dirección y UTP).
 */
return new class extends Migration
{
    private const PERMISO = 'rfid.ver';

    private const ROLES = ['UTP', 'Dirección', 'Administrador'];

    public function up(): void
    {
        Schema::create('tarjetas_rfid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->string('codigo', 64)->index(); // UID que entrega el lector
            $table->boolean('activa')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('marcas_rfid', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 64);
            $table->foreignId('tarjeta_id')->nullable()->constrained('tarjetas_rfid')->nullOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->string('lector', 60);              // "Portal principal", "Sala 2° B"…
            $table->string('tipo', 10)->default('entrada'); // entrada | salida
            $table->dateTime('marcado_en')->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['estudiante_id', 'marcado_en']);
        });

        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'name' => self::PERMISO, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $id = DB::table('permissions')->where(['name' => self::PERMISO, 'guard_name' => 'web'])->value('id');
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', self::ROLES)->pluck('id') as $rolId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rolId]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', self::PERMISO)->value('id');
        DB::table('model_has_permissions')->where('permission_id', $id)->delete();
        DB::table('role_has_permissions')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('marcas_rfid');
        Schema::dropIfExists('tarjetas_rfid');
    }
};
