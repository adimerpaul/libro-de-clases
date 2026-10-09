<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'Ver Solicitudes Laboratorio',
        'Crear Solicitudes Laboratorio',
        'Editar Solicitudes Laboratorio',
        'Eliminar Solicitudes Laboratorio',
    ];

    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->foreignId('doctor_id')->nullable()->constrained('doctores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('codigo_solicitud', 40)->nullable();
            $table->date('fecha_solicitud');
            $table->time('hora_solicitud');
            $table->string('diagnostico_clinico')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 30)->default('CREADO');
            $table->decimal('total', 12, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('solicitud_laboratorio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitude_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('producto_nombre');
            $table->decimal('precio', 10, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('solicitud_laboratorio_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_laboratorio_item_id')
                ->constrained('solicitud_laboratorio_items', indexName: 'slr_item_fk')
                ->cascadeOnDelete();
            $table->foreignId('producto_laboratorio_dato_id')
                ->nullable()
                ->constrained('producto_laboratorio_datos', indexName: 'slr_dato_fk')
                ->nullOnDelete();
            $table->string('nombre');
            $table->string('nombre_variable', 100);
            $table->string('unidad', 100)->nullable();
            $table->text('rango_referencia')->nullable();
            $table->text('formula')->nullable();
            $table->text('valor')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('visible')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        User::where('username', 'admin')->first()?->givePermissionTo($this->permissions);
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_laboratorio_resultados');
        Schema::dropIfExists('solicitud_laboratorio_items');
        Schema::dropIfExists('solicitudes');
        Permission::whereIn('name', $this->permissions)->delete();
    }
};
