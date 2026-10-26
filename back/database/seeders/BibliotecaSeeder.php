<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\Recurso;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Recursos de ejemplo de la biblioteca pedagógica (ui3, RESOURCES). Datos ficticios,
 * sin archivo adjunto. Se omite si la biblioteca ya tiene recursos.
 */
class BibliotecaSeeder extends Seeder
{
    public function run(): void
    {
        if (Recurso::withTrashed()->exists()) {
            return;
        }

        $asignatura = fn (string $codigo) => Asignatura::where('codigo', $codigo)->value('id');
        $autor = User::where('email', 'utp@demo.cl')->value('id');

        $recursos = [
            // [tipo, título, nivel, asignatura, descripción]
            ['Actividad', 'Estaciones de aprendizaje', '2° Medio', 'MAT', 'Secuencia por estaciones con desafío, colaboración y ticket de salida.'],
            ['Presentación', 'Funciones en contexto', '2° Medio', 'MAT', 'Presentación de 14 diapositivas con ejemplos, preguntas y cierre.'],
            ['Guía', 'Lectura con evidencia', '6° Básico', 'LEN', 'Guía imprimible con organizador, inferencias y pauta de revisión.'],
            ['Video', 'Homeostasis visual', '2° Medio', 'CIE', 'Video breve con preguntas antes, durante y después.'],
            ['Material didáctico', 'Kit de fracciones', '4° Básico', 'MAT', 'Tarjetas, recta numérica y piezas manipulables.'],
            ['DUA / PIE', 'Banco de apoyos visuales', null, null, 'Pictogramas, organizadores, instrucciones graduadas y apoyos de autorregulación.'],
        ];

        foreach ($recursos as [$tipo, $titulo, $nivel, $codigo, $descripcion]) {
            Recurso::create([
                'tipo' => $tipo,
                'titulo' => $titulo,
                'nivel' => $nivel,
                'asignatura_id' => $codigo ? $asignatura($codigo) : null,
                'descripcion' => $descripcion,
                'publicado' => true,
                'autor_id' => $autor,
            ]);
        }
    }
}
