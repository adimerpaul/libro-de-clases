<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Los roles base (Docente, UTP, Dirección, Administrador) los crea la migración crear_roles_base
        Permisos::sincronizar();

        // Usuarios DEMO (datos ficticios, clave demo123) con el rol de su cargo
        $demo = [
            ['Marcela Soto Muñoz', 'docente@demo.cl', ['Docente']],
            ['Carolina Méndez', 'utp@demo.cl', ['UTP']],
            ['Alejandro Rivas', 'direccion@demo.cl', ['Dirección']],
            ['Daniela Paredes', 'inspectoria@demo.cl', []],
            ['Paula Contreras', 'sostenedor@demo.cl', []],
            ['Administrador del Sistema', 'admin@demo.cl', ['Administrador']],
        ];

        foreach ($demo as [$name, $email, $roles]) {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'demo123',
            ])->syncRoles($roles);
        }

        $this->call([DatosAcademicosSeeder::class, CurriculoSeeder::class, TrabajoDocenteSeeder::class, EstudiantesSeeder::class]);
    }
}
