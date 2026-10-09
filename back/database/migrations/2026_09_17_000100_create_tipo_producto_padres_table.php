<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipos padre y los tipos de producto que agrupan. Los tipos conservan su
     * propio color; el padre solo agrupa y aporta color e ícono.
     */
    private const PADRES = [
        [
            'nombre' => 'LABORATORIO',
            'color' => 'deep-purple',
            'icono' => 'science',
            'es_laboratorio' => true,
            'tipos' => [], // Todos los tipos marcados con es_laboratorio.
        ],
        [
            'nombre' => 'FARMACIA',
            'color' => 'teal',
            'icono' => 'medication',
            'es_laboratorio' => false,
            'tipos' => ['FARMACIA', 'AMPOLLA'],
        ],
        [
            'nombre' => 'SERVICIOS MEDICOS',
            'color' => 'blue',
            'icono' => 'medical_services',
            'es_laboratorio' => false,
            'tipos' => ['SERVICIO MEDICO', 'CONSULTA ESPECIALISTA', 'ANESTESIOLOGIA', 'SERVICIO DE ENFERMERIA'],
        ],
        [
            'nombre' => 'HOSPITALIZACION',
            'color' => 'indigo',
            'icono' => 'local_hospital',
            'es_laboratorio' => false,
            'tipos' => ['INTERNACION', 'USO DE QUIROFANO', 'SALAS DE PROCEDIMIENTOS', 'OXIGENO TERAPIA', 'U.T.I. ADULTOS', 'NEONATOLOGIA', 'AMBULANCIA'],
        ],
        [
            'nombre' => 'IMAGENOLOGIA Y DIAGNOSTICO',
            'color' => 'orange',
            'icono' => 'monitor_heart',
            'es_laboratorio' => false,
            'tipos' => ['RAYOS X SIN INFORME', 'RAYOS X CONTRASTADOS', 'TOMOGRAFIA EN C.D.', 'ECOGRAFIA', 'ESTUDIOS DIAGNOSTICOS'],
        ],
    ];

    public function up(): void
    {
        Schema::create('tipo_producto_padres', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('color', 30)->default('primary');
            $table->string('icono', 60)->default('category');
            // Los tipos hijos heredan esta marca (ver TipoProducto::booted).
            $table->boolean('es_laboratorio')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
        });

        Schema::table('tipo_productos', function (Blueprint $table) {
            $table->foreignId('tipo_producto_padre_id')->nullable()->after('id')
                ->constrained('tipo_producto_padres')->nullOnDelete();
        });

        $ahora = now();

        foreach (self::PADRES as $indice => $padre) {
            $padreId = DB::table('tipo_producto_padres')->insertGetId([
                'nombre' => $padre['nombre'],
                'color' => $padre['color'],
                'icono' => $padre['icono'],
                'es_laboratorio' => $padre['es_laboratorio'],
                'orden' => $indice + 1,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $tipos = DB::table('tipo_productos');
            $padre['es_laboratorio']
                ? $tipos->where('es_laboratorio', true)
                : $tipos->whereIn('nombre', $padre['tipos'])->where('es_laboratorio', false);

            $tipos->update(['tipo_producto_padre_id' => $padreId]);
        }
    }

    public function down(): void
    {
        Schema::table('tipo_productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_producto_padre_id');
        });

        Schema::dropIfExists('tipo_producto_padres');
    }
};
