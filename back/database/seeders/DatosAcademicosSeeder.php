<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Curso;
use App\Models\User;
use App\Support\Permisos;
use App\Support\Run;
use Illuminate\Database\Seeder;

/**
 * Datos académicos DEMO (100% ficticios): asignaturas, docentes, cursos del año
 * en curso y la asignación docente → asignatura → curso.
 */
class DatosAcademicosSeeder extends Seeder
{
    public function run(): void
    {
        $anio = (int) now()->year;

        // ── Asignaturas ──
        $asignaturas = collect([
            ['LEN', 'Lengua y Literatura', '#bd1f2d'],
            ['MAT', 'Matemática', '#0f4f85'],
            ['HIS', 'Historia, Geografía y Ciencias Sociales', '#9a6b1f'],
            ['CIE', 'Ciencias Naturales', '#1f8a5b'],
            ['ING', 'Inglés', '#6b4fbb'],
            ['EFI', 'Educación Física y Salud', '#e07a1f'],
            ['ART', 'Artes Visuales', '#c2407a'],
            ['MUS', 'Música', '#8a3fb0'],
            ['TEC', 'Tecnología', '#2b8fa8'],
            ['ORI', 'Orientación', '#5f7d8c'],
            ['FIL', 'Filosofía', '#4a5a9a'],
            ['ECI', 'Educación Ciudadana', '#7a6a2f'],
            ['CCI', 'Ciencias para la Ciudadanía', '#2f8a7a'],
        ])->mapWithKeys(fn ($a) => [$a[0] => Asignatura::create([
            'codigo' => $a[0], 'nombre' => $a[1], 'color' => $a[2],
        ])]);

        // ── Docentes (RUN ficticios con dígito verificador válido) ──
        // docente@demo.cl y utp@demo.cl ya existen (DatabaseSeeder): solo se les completa el RUN
        $docente = function (string $name, string $email, int $cuerpo): User {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'demo123']);
            $user->run ??= $cuerpo.'-'.Run::digitoVerificador((string) $cuerpo);
            $user->save();

            // utp@demo.cl ya dicta clases por su rol UTP
            return $user->tienePermiso(Permisos::DICTAR_CLASES) ? $user : $user->assignRole('Docente');
        };

        $d = [
            'marcela' => $docente('Marcela Soto Muñoz', 'docente@demo.cl', 15234876),
            'carolina' => $docente('Carolina Méndez', 'utp@demo.cl', 13987654),
            'tomas' => $docente('Tomás Reyes Bravo', 'treyes@demo.cl', 16543210),
            'andrea' => $docente('Andrea Vidal Fuentes', 'avidal@demo.cl', 17321456),
            'jorge' => $docente('Jorge Fuentes Araya', 'jfuentes@demo.cl', 12456789),
            'valentina' => $docente('Valentina Rojas Pizarro', 'vrojas@demo.cl', 18234567),
            'camila' => $docente('Camila Torres Vergara', 'ctorres@demo.cl', 17654321),
            'rodrigo' => $docente('Rodrigo Silva Núñez', 'rsilva@demo.cl', 14678901),
            'francisca' => $docente('Francisca Morales Lagos', 'fmorales@demo.cl', 16890123),
            'ignacio' => $docente('Ignacio Herrera Castro', 'iherrera@demo.cl', 15789012),
        ];

        // ── Plan de estudio simplificado: [código => horas semanales] por tramo ──
        $planes = [
            'basica' => ['LEN' => 6, 'MAT' => 6, 'HIS' => 4, 'CIE' => 4, 'ING' => 3, 'EFI' => 2, 'ART' => 2, 'MUS' => 1, 'TEC' => 1, 'ORI' => 1],
            'media' => ['LEN' => 6, 'MAT' => 7, 'HIS' => 4, 'CIE' => 6, 'ING' => 4, 'EFI' => 2, 'ART' => 2, 'TEC' => 2, 'ORI' => 1],
            'diferenciada' => ['LEN' => 3, 'MAT' => 3, 'ING' => 2, 'FIL' => 2, 'ECI' => 2, 'CCI' => 2, 'EFI' => 2, 'ORI' => 1],
        ];

        // Quién dicta cada asignatura según tramo: [básica, media 1°-2°, media 3°-4°]
        $quien = [
            'LEN' => ['andrea', 'carolina', 'andrea'],
            'MAT' => ['tomas', 'marcela', 'tomas'],
            'HIS' => ['jorge', 'jorge', 'jorge'],
            'CIE' => ['valentina', 'valentina', 'valentina'],
            'ING' => ['camila', 'camila', 'camila'],
            'EFI' => ['rodrigo', 'rodrigo', 'rodrigo'],
            'ART' => ['francisca', 'francisca', 'francisca'],
            'MUS' => ['francisca', 'francisca', 'francisca'],
            'TEC' => ['ignacio', 'ignacio', 'ignacio'],
            'ORI' => [null, null, null], // la dicta el profesor jefe
            'FIL' => ['jorge', 'jorge', 'jorge'],
            'ECI' => ['jorge', 'jorge', 'jorge'],
            'CCI' => ['valentina', 'valentina', 'valentina'],
        ];

        // ── Cursos [nivel, letra, profesor jefe, tramo] ──
        $cursos = [
            ['7° Básico', 'A', 'andrea', 'basica'],
            ['8° Básico', 'A', 'tomas', 'basica'],
            ['1° Medio', 'A', 'jorge', 'media'],
            ['1° Medio', 'B', 'valentina', 'media'],
            ['2° Medio', 'A', 'camila', 'media'],
            ['2° Medio', 'B', 'marcela', 'media'],
            ['3° Medio', 'A', 'rodrigo', 'diferenciada'],
            ['4° Medio', 'A', 'ignacio', 'diferenciada'],
        ];
        $tramo = ['basica' => 0, 'media' => 1, 'diferenciada' => 2];

        foreach ($cursos as [$nivel, $letra, $jefe, $plan]) {
            $curso = Curso::create([
                'anio' => $anio,
                'nivel' => $nivel,
                'letra' => $letra,
                'profesor_jefe_id' => $d[$jefe]->id,
            ]);

            foreach ($planes[$plan] as $codigo => $horas) {
                $responsable = $quien[$codigo][$tramo[$plan]] ?? $jefe;

                Asignacion::create([
                    'curso_id' => $curso->id,
                    'asignatura_id' => $asignaturas[$codigo]->id,
                    'docente_id' => $d[$responsable]->id,
                    'horas_semanales' => $horas,
                ]);
            }
        }
    }
}
