<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\ObjetivoAprendizaje;
use Illuminate\Database\Seeder;

/**
 * OA de DEMOSTRACIÓN (los de ui2/app.js y algunos más). Son resúmenes para validar
 * la experiencia, NO el texto oficial: van con es_demo = true. El catálogo oficial
 * se importa desde la fuente Mineduc según ui2/curriculum_import_schema.json.
 * Idempotente: se puede volver a ejecutar.
 */
class CurriculoSeeder extends Seeder
{
    public function run(): void
    {
        $oas = [
            // [código, nivel, asignatura, unidad, texto, habilidades]
            ['MAT-2M-01', '2° Medio', 'MAT', 'Números', 'Aplicar números reales, potencias, raíces y estimaciones para resolver problemas en contextos cotidianos.', ['Resolver problemas', 'Argumentar', 'Representar']],
            ['MAT-2M-02', '2° Medio', 'MAT', 'Álgebra y funciones', 'Modelar situaciones mediante relaciones lineales y cuadráticas, interpretando tablas, gráficos y expresiones algebraicas.', ['Modelar', 'Representar', 'Comunicar']],
            ['MAT-2M-03', '2° Medio', 'MAT', 'Geometría', 'Resolver problemas de geometría utilizando propiedades de figuras, transformaciones y relaciones métricas.', ['Resolver problemas', 'Visualizar']],
            ['LYL-2M-01', '2° Medio', 'LEN', 'Lectura', 'Analizar textos literarios y no literarios, considerando propósito, contexto, estructura, voces y evidencias.', ['Comprender', 'Analizar', 'Interpretar']],
            ['LYL-2M-02', '2° Medio', 'LEN', 'Escritura', 'Producir textos argumentativos y expositivos coherentes, adecuados a propósito, audiencia y contexto.', ['Escribir', 'Argumentar', 'Revisar']],
            ['HIS-2M-01', '2° Medio', 'HIS', 'Chile y mundo contemporáneo', 'Analizar transformaciones políticas, sociales, económicas y culturales, usando fuentes y perspectivas diversas.', ['Analizar fuentes', 'Pensamiento crítico', 'Temporalidad']],
            ['CN-2M-01', '2° Medio', 'CIE', 'Biología', 'Explicar procesos biológicos y su relación con sistemas del organismo, ambiente y salud.', ['Investigar', 'Explicar', 'Comunicar evidencia']],
            ['ING-2M-01', '2° Medio', 'ING', 'Comunicación', 'Comprender y producir mensajes orales y escritos en temas familiares y académicos, utilizando vocabulario y estructuras pertinentes.', ['Listening', 'Reading', 'Speaking', 'Writing']],
            ['MAT-1M-01', '1° Medio', 'MAT', 'Números', 'Calcular operaciones con números racionales y potencias de base racional, resolviendo problemas en contexto.', ['Resolver problemas', 'Representar']],
            ['MAT-1M-02', '1° Medio', 'MAT', 'Álgebra', 'Desarrollar productos notables y factorizar expresiones algebraicas para resolver ecuaciones y problemas.', ['Modelar', 'Argumentar']],
            ['MAT-1M-03', '1° Medio', 'MAT', 'Probabilidad y estadística', 'Interpretar datos mediante gráficos y medidas de tendencia central, y calcular probabilidades de eventos simples.', ['Representar', 'Argumentar', 'Comunicar']],
            ['LYL-1M-01', '1° Medio', 'LEN', 'Lectura', 'Comprender y analizar narraciones y poemas, reconociendo recursos literarios, personajes y conflictos.', ['Comprender', 'Interpretar']],
            ['MAT-8B-01', '8° Básico', 'MAT', 'Números y operaciones', 'Resolver problemas que involucren números racionales, proporcionalidad y porcentajes.', ['Resolver problemas', 'Representar']],
            ['LYL-8B-01', '8° Básico', 'LEN', 'Lectura', 'Profundizar la comprensión de narraciones, textos informativos y argumentativos, evaluando información y evidencias.', ['Analizar', 'Interpretar']],
            ['MAT-7B-01', '7° Básico', 'MAT', 'Números', 'Resolver problemas con números enteros, fracciones y decimales, usando estrategias de cálculo y estimación.', ['Resolver problemas', 'Comunicar']],
        ];

        $asignaturas = Asignatura::pluck('id', 'codigo');

        foreach ($oas as [$codigo, $nivel, $asignatura, $unidad, $texto, $habilidades]) {
            if (! isset($asignaturas[$asignatura])) {
                continue;
            }

            ObjetivoAprendizaje::updateOrCreate(
                ['codigo' => $codigo, 'version_curricular' => 'demo-2026'],
                [
                    'nivel' => $nivel,
                    'asignatura_id' => $asignaturas[$asignatura],
                    'unidad' => $unidad,
                    'texto' => $texto,
                    'habilidades' => $habilidades,
                    'fuente' => 'Resumen de demostración basado en la estructura del Currículum Nacional Mineduc (no es texto oficial)',
                    'fuente_url' => 'https://www.curriculumnacional.cl/',
                    'vigente_desde' => '2026-01-01',
                    'es_demo' => true,
                ],
            );
        }
    }
}
