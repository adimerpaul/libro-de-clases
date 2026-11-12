<?php

use App\Models\Curso;
use App\Support\Niveles;
use Illuminate\Database\Migrations\Migration;

/**
 * Un curso "A" del año en curso para cada nivel de Niveles::TODOS (Sala Cuna a 4° Medio TP)
 * que aún no tenga ninguno, para que todo el catálogo curricular tenga dónde usarse.
 * Quedan sin profesor jefe ni asignaturas: se asignan desde Administrador. En las pruebas
 * no se ejecuta: cada prueba arma sus cursos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $anio = (int) now()->year;
        foreach (Niveles::TODOS as $nivel) {
            if (! Curso::withTrashed()->where(['anio' => $anio, 'nivel' => $nivel])->exists()) {
                Curso::create(['anio' => $anio, 'nivel' => $nivel, 'letra' => 'A']);
            }
        }
    }

    public function down(): void
    {
        // Los cursos quedan: pueden tener matrículas, asignaturas o registros
    }
};
