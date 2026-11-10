<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurriculumDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('curriculum_levels')->insert([
            ['code'=>'PARV','name'=>'Educación Parvularia','sort_order'=>10,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'BASICA_1_6','name'=>'Educación Básica 1° a 6°','sort_order'=>20,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'BASICA_7_8_MEDIA_1_2','name'=>'7° Básico a 2° Medio','sort_order'=>30,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'MEDIA_3_4_FG_HC','name'=>'3° y 4° Medio FG/HC','sort_order'=>40,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'MEDIA_TP','name'=>'3° y 4° Medio TP','sort_order'=>50,'created_at'=>$now,'updated_at'=>$now],
        ]);
        // El paquete SQLite trae una semilla funcional más amplia. Para producción,
        // importar OA y recursos mediante un proceso versionado y revisado.
    }
}
