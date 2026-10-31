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

    /** Abre el libro de cualquier curso (UTP, Dirección); sin él, el docente ve solo los de su carga o jefatura. */
    public const VER_LIBROS = 'libros.ver';

    /** Panel en vivo de las marcas de las tarjetas RFID (servidor sockets/). */
    public const VER_RFID = 'rfid.ver';

    /** Student 360 de todos los cursos; sin él, el docente ve solo los de su carga o jefatura. */
    public const VER_STUDENT360 = 'student360.ver';

    /** Portal de familia (rol Apoderado): solo los estudiantes vinculados a su cuenta. */
    public const VER_FAMILIA = 'familia.ver';

    /** Educación emocional: el docente activa el protocolo DEC de estudiantes de sus cursos (sin ver los seguimientos). */
    public const REPORTAR_EMOCIONAL = 'emocional.reportar';

    /** Abre y escribe en la libreta de cualquier estudiante; sin él, el docente solo la de sus cursos. */
    public const VER_LIBRETAS = 'libreta.ver';

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
        'recursos' => ['Biblioteca', 'recursos de la biblioteca pedagógica'],
    ];

    /** Módulos con CRUD que tienen página propia en el menú lateral (no son pestañas de Administrador). */
    public const MODULOS_MENU = [
        'pie' => ['PIE / Inclusión', 'casos PIE (diagnóstico, expediente y bitácora)'],
        'curriculum' => ['Currículum y OA', 'OA del catálogo curricular'],
        'emocional' => ['Educación emocional', 'sesiones, seguimiento de bienestar, protocolo DEC y diagnóstico socioemocional'],
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
        ], [
            'grupo' => 'Libro de clases',
            'modulo' => null,
            'permisos' => [['name' => self::VER_LIBROS, 'label' => 'Ver los libros de todos los cursos', 'accion' => null]],
        ], [
            'grupo' => 'Asistencia RFID',
            'modulo' => null,
            'permisos' => [['name' => self::VER_RFID, 'label' => 'Ver las marcas RFID en tiempo real', 'accion' => null]],
        ], [
            'grupo' => 'Student 360',
            'modulo' => null,
            'permisos' => [['name' => self::VER_STUDENT360, 'label' => 'Ver la vista 360 de los estudiantes de todos los cursos', 'accion' => null]],
        ], [
            'grupo' => 'Portal de familia',
            'modulo' => null,
            'permisos' => [['name' => self::VER_FAMILIA, 'label' => 'Ver notas, asistencia y libreta de sus hijos vinculados (apoderado)', 'accion' => null]],
        ], [
            'grupo' => 'Libreta del estudiante',
            'modulo' => null,
            'permisos' => [['name' => self::VER_LIBRETAS, 'label' => 'Ver y escribir en la libreta de todos los estudiantes', 'accion' => null]],
        ], [
            'grupo' => 'Protocolo DEC',
            'modulo' => null,
            'permisos' => [['name' => self::REPORTAR_EMOCIONAL, 'label' => 'Activar el protocolo de desregulación (DEC) para estudiantes de sus cursos', 'accion' => null]],
        ]];

        foreach (self::MODULOS + self::MODULOS_MENU as $modulo => [$titulo, $complemento]) {
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
        return array_merge([self::DICTAR_CLASES, self::VER_LIBROS, self::VER_RFID, self::VER_STUDENT360, self::VER_FAMILIA, self::VER_LIBRETAS, self::REPORTAR_EMOCIONAL], ...array_map(fn ($m) => self::crud($m), array_keys(self::MODULOS + self::MODULOS_MENU)));
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
