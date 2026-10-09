<?php

namespace Database\Seeders;

use App\Models\Seguro;
use Illuminate\Database\Seeder;

class SeguroSeeder extends Seeder
{
    public function run(): void
    {
        $seguros = [
            ['nombre' => 'SEGUROS Y REASEGUROS PERSONALES UNIVIDA S.A.', 'nit' => '301204024'],
            ['nombre' => 'HOSPITAL GENERAL SAN JUAN DE DIOS', 'nit' => '1023357026'],
            ['nombre' => 'CAJA BANCA ESTAL DE SALUD', 'nit' => '1016529025'],
            ['nombre' => 'CORPORACION MINERA DE BOLIVIA (COLQUIRI)', 'nit' => '1006965023'],
            ['nombre' => 'CORPORACION DEL SEGURO SOCIAL MILITAR COSSMIL', 'nit' => '1019635023'],
            ['nombre' => 'BISA SEGUROS Y REASEGUROS S.A.', 'nit' => '1020655027'],
            ['nombre' => 'NACIONAL SEGUROS VIDA Y SALUD S.A.', 'nit' => '1028483024'],
            ['nombre' => 'CREDISEGURO S.A. SEGUROS PERSONALES', 'nit' => '191310020'],
            ['nombre' => 'CAJA DE SALUD DE LA BANCA PRIVADA', 'nit' => '1020635028'],
            ['nombre' => 'LA BOLIVIANA CIACRUZ SEGUROS PERSONALES S.A.', 'nit' => '1006989027'],
            ['nombre' => 'CIES SALUD SEXUAL SALUD REPRODUCTIVA', 'nit' => '1006987025'],
            ['nombre' => 'ALIANZA VIDA S.A.', 'nit' => '1015327022'],
            ['nombre' => 'ASEGURADORA FORTALEZA', 'nit' => null],
            ['nombre' => 'SEGURO F.O.C.A.T', 'nit' => null],
            ['nombre' => 'ASEGURADORA FORTALEZA', 'nit' => null],
        ];

        foreach ($seguros as $s) {
            Seguro::firstOrCreate(
                ['nombre' => $s['nombre'], 'nit' => $s['nit']],
                $s
            );
        }
    }
}
