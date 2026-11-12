<?php

use App\Models\Instrumento;
use App\Models\ObjetivoAprendizaje;
use App\Models\Planificacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Con el catálogo oficial cargado, se retiran los OA de demostración (es_demo = true:
 * CurriculoSeeder, EmocionalSeeder y los resúmenes del paquete curricular) junto con las
 * planificaciones y evaluaciones hechas sobre ellos. Recursos de biblioteca y sesiones
 * socioemocionales se conservan, sin OA. Cursos, docentes y matrículas no se tocan.
 * Todo con soft delete (auditable y recuperable).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $ids = ObjetivoAprendizaje::where('es_demo', true)->pluck('id');
            if ($ids->isEmpty()) {
                return;
            }

            Planificacion::whereIn('objetivo_aprendizaje_id', $ids)->get()->each->delete();
            Instrumento::whereIn('objetivo_aprendizaje_id', $ids)->get()->each->delete(); // con preguntas y notas
            DB::table('recursos')->whereIn('objetivo_aprendizaje_id', $ids)->update(['objetivo_aprendizaje_id' => null]);
            DB::table('ee_sesiones')->whereIn('objetivo_aprendizaje_id', $ids)->update(['objetivo_aprendizaje_id' => null]);
            ObjetivoAprendizaje::whereIn('id', $ids)->get()->each->delete();
        });
    }

    public function down(): void
    {
        // Quedan en la papelera (soft delete); no se restauran automáticamente
    }
};
