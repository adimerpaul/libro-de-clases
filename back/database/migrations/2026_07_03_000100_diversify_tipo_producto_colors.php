<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $colores = [
            'FARMACIA'                 => 'teal',
            'INTERNACION'               => 'indigo',
            'USO DE QUIROFANO'          => 'deep-orange',
            'SALAS DE PROCEDIMIENTOS'   => 'purple',
            'OXIGENO TERAPIA'           => 'light-blue',
            'SERVICIO MEDICO'           => 'blue',
            'ESTUDIOS DIAGNOSTICOS'     => 'pink',
            'SERVICIO DE ENFERMERIA'    => 'green',
            'U.T.I. ADULTOS'            => 'negative',
            'NEONATOLOGIA'              => 'amber',
            'RAYOS X SIN INFORME'       => 'brown',
            'RAYOS X CONTRASTADOS'      => 'deep-purple',
            'TOMOGRAFIA EN C.D.'        => 'cyan',
            'LABORATORIOS'              => 'lime',
            'ECOGRAFIA'                 => 'orange',
            'AMBULANCIA'                => 'red',
        ];

        foreach ($colores as $nombre => $color) {
            DB::table('tipo_productos')->where('nombre', $nombre)->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        DB::table('tipo_productos')->update(['color' => 'primary']);
    }
};
