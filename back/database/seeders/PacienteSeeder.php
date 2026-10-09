<?php

namespace Database\Seeders;

use App\Models\Paciente;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $pacientes = [
            ['nombre_completo' => 'SN', 'sexo' => null, 'ci' => null, 'estado' => null, 'direccion' => null, 'telefono' => null],
            ['nombre_completo' => 'ANA PAULA FERNANDEZ GONDORCET', 'sexo' => 'F', 'ci' => null, 'estado' => null, 'direccion' => null, 'telefono' => null],
            ['nombre_completo' => 'ERICKA ESTHER CAYOJA BARRERA', 'sexo' => 'F', 'ci' => '0', 'estado' => null, 'direccion' => null, 'telefono' => '0'],
            ['nombre_completo' => 'JUAN CARLOS MAMANI QUISPE', 'sexo' => 'M', 'ci' => '4521367', 'estado' => 'ACTIVO', 'direccion' => 'AV. AROCE #245', 'telefono' => '71234567'],
            ['nombre_completo' => 'MARIA ELENA ROJAS VARGAS', 'sexo' => 'F', 'ci' => '3894521', 'estado' => 'ACTIVO', 'direccion' => 'CALLE JUNIN #120', 'telefono' => '70112233'],
            ['nombre_completo' => 'PEDRO ANTONIO CONDORI FLORES', 'sexo' => 'M', 'ci' => null, 'estado' => null, 'direccion' => 'ZONA NORTE', 'telefono' => null],
            ['nombre_completo' => 'LUCIA FERNANDA APAZA TICONA', 'sexo' => 'F', 'ci' => '5678234', 'estado' => 'ACTIVO', 'direccion' => null, 'telefono' => '76543210'],
            ['nombre_completo' => 'JOSE LUIS GUTIERREZ SORIA', 'sexo' => 'M', 'ci' => '2345678', 'estado' => null, 'direccion' => 'AV. 6 DE OCTUBRE #560', 'telefono' => null],
            ['nombre_completo' => 'CARMEN ROSA VELASQUEZ NINA', 'sexo' => 'F', 'ci' => null, 'estado' => 'INACTIVO', 'direccion' => null, 'telefono' => '77889900'],
            ['nombre_completo' => 'MIGUEL ANGEL TORREZ CHOQUE', 'sexo' => 'M', 'ci' => '6789123', 'estado' => 'ACTIVO', 'direccion' => 'CALLE POTOSI #78', 'telefono' => '71122334'],
        ];

        foreach ($pacientes as $p) {
            Paciente::firstOrCreate(['nombre_completo' => $p['nombre_completo']], $p);
        }
    }
}
