<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Catálogo de permisos por módulo: App\Support\Permisos
        foreach (Permisos::MODULOS as $modulo => $permisos) {
            foreach ($permisos as $p) {
                Permission::updateOrCreate(
                    ['name' => $p, 'guard_name' => 'web'],
                    ['modulo' => $modulo, 'descripcion' => Permisos::descripcionDe($p)],
                );
            }
        }

        $this->call(SeguroSeeder::class);
        $this->call(PacienteSeeder::class);
        $this->call(InternacionSeeder::class);
        $this->call(LaboratorioTipoSeeder::class);
        $this->call(ProductoNombreComercialSeeder::class);

        // Admin
        $admin = User::create([
            'name' => 'ADMINISTRADOR',
            'username' => 'admin',
            'email' => 'admin@urme.com',
            'ci' => '00000000',
            'password' => bcrypt('admin123Admin'),
        ]);
        $admin->syncPermissions(Permission::all());

        // Milton Enrique Tito Cadima
        User::create([
            'name' => 'MILTON ENRIQUE TITO CADIMA',
            'username' => 'mtito',
            'email' => 'mtito@urme.com',
            'ci' => '12749265',
            'password' => bcrypt('12749265'),
        ]);

        // Daniela Alejandra Peña Valverde
        User::create([
            'name' => 'DANIELA ALEJANDRA PEÑA VALVERDE',
            'username' => 'dpena',
            'email' => 'dpena@urme.com',
            'ci' => '7340511',
            'password' => bcrypt('7340511'),
        ]);

        // Darwin Gabriel Limachi Tito
        User::create([
            'name' => 'DARWIN GABRIEL LIMACHI TITO',
            'username' => 'dlimachi',
            'email' => 'dlimachi@urme.com',
            'ci' => '12644872',
            'password' => bcrypt('12644872'),
        ]);
    }
}
