<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Catálogo de permisos (Spatie, guard web). Cada pestaña del menú Administrador
 * es un módulo con su CRUD: "<modulo>.ver", ".crear", ".editar" y ".eliminar".
 * Un usuario recibe los permisos de sus roles más los asignados directamente.
 */
class Permisos
{
    /** Puede tener asignaturas y jefatura de curso (es docente). */
    public const DICTAR_CLASES = 'clases.dictar';

    public const VER = 'ver';

    public const CREAR = 'crear';

    public const EDITAR = 'editar';

    public const ELIMINAR = 'eliminar';

    public const ACCIONES = [
        self::VER => 'Ver',
        self::CREAR => 'Crear',
        self::EDITAR => 'Editar',
        self::ELIMINAR => 'Eliminar',
    ];

    /** Módulos del menú Administrador: clave => [título, complemento para la etiqueta]. */
    public const MODULOS = [
        'cursos' => ['Cursos', 'cursos'],
        'estudiantes' => ['Estudiantes', 'estudiantes'],
        'matriculas' => ['Matrículas', 'matrículas (nómina por curso)'],
        'asignaturas' => ['Asignaturas', 'asignaturas'],
        'docentes' => ['Docentes', 'docentes'],
        'asignaciones' => ['Asignación docente', 'asignaciones docentes'],
        'usuarios' => ['Usuarios y permisos', 'usuarios (roles y permisos)'],
        'roles' => ['Roles', 'roles'],
    ];

    /** "cursos", "ver" → "cursos.ver" */
    public static function de(string $modulo, string $accion): string
    {
        return "{$modulo}.{$accion}";
    }

    /** Los cuatro permisos CRUD de un módulo. */
    public static function crud(string $modulo): array
    {
        return array_map(fn ($a) => self::de($modulo, $a), array_keys(self::ACCIONES));
    }

    /** Permisos que dan acceso a alguna pestaña del menú Administrador. */
    public static function administracion(): array
    {
        return array_map(fn ($m) => self::de($m, self::VER), array_keys(self::MODULOS));
    }

    /**
     * Catálogo agrupado para los formularios:
     * [['grupo' => 'Cursos', 'modulo' => 'cursos', 'permisos' => [['name', 'label', 'accion'], …]], …]
     */
    public static function grupos(): array
    {
        $grupos = [[
            'grupo' => 'Trabajo docente',
            'modulo' => null,
            'permisos' => [['name' => self::DICTAR_CLASES, 'label' => 'Dictar clases (docente)', 'accion' => null]],
        ]];

        foreach (self::MODULOS as $modulo => [$titulo, $complemento]) {
            $grupos[] = [
                'grupo' => $titulo,
                'modulo' => $modulo,
                'permisos' => array_map(fn ($accion, $label) => [
                    'name' => self::de($modulo, $accion),
                    'label' => "{$label} {$complemento}",
                    'accion' => $accion,
                ], array_keys(self::ACCIONES), self::ACCIONES),
            ];
        }

        return $grupos;
    }

    public static function todos(): array
    {
        return array_merge([self::DICTAR_CLASES], ...array_map(fn ($m) => self::crud($m), array_keys(self::MODULOS)));
    }

    /** Crea en BD los permisos del catálogo que falten. */
    public static function sincronizar(): void
    {
        foreach (self::todos() as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
