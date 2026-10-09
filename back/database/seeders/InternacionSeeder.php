<?php

namespace Database\Seeders;

use App\Models\Internacion;
use App\Models\Paciente;
use Illuminate\Database\Seeder;

class InternacionSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            'SN' => ['fecha_ingreso' => null, 'tipo_paciente' => null, 'fecha_alta' => null, 'codigo_hc' => null, 'sala' => null],
            'ANA PAULA FERNANDEZ GONDORCET' => ['fecha_ingreso' => '2026-06-24', 'tipo_paciente' => 'INSTITUCIONAL', 'fecha_alta' => '2026-06-25', 'codigo_hc' => '39/F/26', 'sala' => '2-A'],
            'ERICKA ESTHER CAYOJA BARRERA' => ['fecha_ingreso' => null, 'tipo_paciente' => 'EXTERNO', 'fecha_alta' => null, 'codigo_hc' => null, 'sala' => null],
            'JUAN CARLOS MAMANI QUISPE' => ['fecha_ingreso' => '2026-05-10', 'tipo_paciente' => 'INSTITUCIONAL', 'fecha_alta' => '2026-05-13', 'codigo_hc' => '12/M/26', 'sala' => '1-B'],
            'MARIA ELENA ROJAS VARGAS' => ['fecha_ingreso' => '2026-04-02', 'tipo_paciente' => 'INSTITUCIONAL', 'fecha_alta' => '2026-04-05', 'codigo_hc' => '08/F/26', 'sala' => '3-A'],
            'PEDRO ANTONIO CONDORI FLORES' => ['fecha_ingreso' => null, 'tipo_paciente' => null, 'fecha_alta' => null, 'codigo_hc' => null, 'sala' => null],
            'LUCIA FERNANDA APAZA TICONA' => ['fecha_ingreso' => '2026-03-15', 'tipo_paciente' => 'EXTERNO', 'fecha_alta' => '2026-03-16', 'codigo_hc' => '21/F/26', 'sala' => '2-B'],
            'JOSE LUIS GUTIERREZ SORIA' => ['fecha_ingreso' => '2026-02-20', 'tipo_paciente' => 'INSTITUCIONAL', 'fecha_alta' => null, 'codigo_hc' => '05/M/26', 'sala' => '1-A'],
            'CARMEN ROSA VELASQUEZ NINA' => ['fecha_ingreso' => null, 'tipo_paciente' => null, 'fecha_alta' => null, 'codigo_hc' => null, 'sala' => null],
            'MIGUEL ANGEL TORREZ CHOQUE' => ['fecha_ingreso' => '2026-06-01', 'tipo_paciente' => 'INSTITUCIONAL', 'fecha_alta' => '2026-06-04', 'codigo_hc' => '33/M/26', 'sala' => '3-B'],
        ];

        foreach ($datos as $nombre => $d) {
            $paciente = Paciente::where('nombre_completo', $nombre)->first();
            if (!$paciente) {
                continue;
            }
            Internacion::firstOrCreate(
                ['paciente_id' => $paciente->id, 'codigo_hc' => $d['codigo_hc']],
                array_merge(['paciente_id' => $paciente->id], $d)
            );
        }
    }
}
