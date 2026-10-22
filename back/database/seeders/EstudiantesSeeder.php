<?php

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Support\Niveles;
use App\Support\Run;
use Illuminate\Database\Seeder;

/**
 * Estudiantes FICTICIOS matriculados en los cursos del año actual (unos 25 por curso),
 * con lista numerada alfabéticamente. Determinístico; se omite si ya hay estudiantes.
 */
class EstudiantesSeeder extends Seeder
{
    private const NOMBRES_F = ['Sofía', 'Isidora', 'Agustina', 'Florencia', 'Emilia', 'Josefa', 'Martina', 'Catalina', 'Antonia', 'Trinidad', 'Fernanda', 'Amanda', 'Javiera', 'Constanza', 'Valentina'];

    private const NOMBRES_M = ['Benjamín', 'Vicente', 'Martín', 'Matías', 'Joaquín', 'Agustín', 'Tomás', 'Cristóbal', 'Maximiliano', 'Lucas', 'Diego', 'Sebastián', 'Felipe', 'Gaspar', 'Renato'];

    private const APELLIDOS = ['González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 'Reyes', 'Gutiérrez', 'Castro', 'Pizarro', 'Álvarez', 'Vásquez', 'Sánchez', 'Fernández'];

    public function run(): void
    {
        if (Estudiante::exists()) {
            return;
        }

        $anio = (int) now()->year;
        $cursos = Curso::ordenar(Curso::where('anio', $anio)->get());
        $n = 0;

        foreach ($cursos as $c => $curso) {
            $edad = 4 + Niveles::orden($curso->nivel); // Pre-kínder ≈ 4 años
            $total = 22 + ($c * 3) % 9;               // entre 22 y 30 por curso

            $matriculas = [];
            for ($i = 0; $i < $total; $i++, $n++) {
                $mujer = $n % 2 === 0;
                $nombres = $mujer ? self::NOMBRES_F[$n % 15] : self::NOMBRES_M[($n * 7) % 15];
                $segundo = $mujer ? self::NOMBRES_F[($n * 4 + 3) % 15] : self::NOMBRES_M[($n * 2 + 5) % 15];
                $paterno = self::APELLIDOS[($n * 11) % 30];
                $materno = self::APELLIDOS[($n * 17 + 5) % 30];
                $cuerpo = 23000000 + $n * 7919 % 2900000 + $n;

                $estudiante = Estudiante::create([
                    'run' => $cuerpo.'-'.Run::digitoVerificador((string) $cuerpo),
                    'nombres' => $nombres === $segundo ? $nombres : "{$nombres} {$segundo}",
                    'apellido_paterno' => $paterno,
                    'apellido_materno' => $materno,
                    'fecha_nacimiento' => sprintf('%d-%02d-%02d', $anio - $edad - ($i % 3 === 0 ? 1 : 0), 1 + $n % 12, 1 + $n % 28),
                    'sexo' => $mujer ? 'F' : 'M',
                    'direccion' => 'Pasaje Ficticio '.(100 + $n).', Comuna Demo',
                    'apoderado_nombre' => ($n % 3 ? 'Patricia ' : 'Rodrigo ').$paterno,
                    'apoderado_telefono' => '+56 9 '.(5000 + $n).' '.(1000 + $n * 3 % 9000),
                    'apoderado_email' => 'apoderado'.($n + 1).'@demo.cl',
                    'pie' => $n % 11 === 0,
                ]);
                $matriculas[] = $estudiante;
            }

            // Lista en orden alfabético, como se numera al inicio del año
            usort($matriculas, fn ($a, $b) => strcmp($a->nombre_completo, $b->nombre_completo));
            foreach ($matriculas as $i => $estudiante) {
                Matricula::create([
                    'estudiante_id' => $estudiante->id,
                    'curso_id' => $curso->id,
                    'numero_lista' => $i + 1,
                    'fecha_matricula' => "{$anio}-03-01",
                ]);
            }
        }
    }
}
