<?php

namespace App\Support;

/**
 * Catálogo único de permisos del sistema, agrupados por módulo.
 * Lo usan el seeder y las migraciones para mantener sincronizada
 * la tabla `permissions` (incluidas sus columnas `modulo` y `descripcion`).
 */
class Permisos
{
    public const MODULOS = [
        'Dashboard' => [
            'Ver Dashboard',
        ],
        'Usuarios' => [
            'Ver Usuarios', 'Crear Usuarios', 'Editar Usuarios', 'Eliminar Usuarios',
            'Gestionar Permisos',
        ],
        'Pacientes' => [
            'Ver Pacientes', 'Crear Pacientes', 'Editar Pacientes', 'Eliminar Pacientes',
        ],
        'Internaciones' => [
            'Ver Internaciones', 'Crear Internaciones', 'Editar Internaciones', 'Eliminar Internaciones',
        ],
        'Doctores' => [
            'Ver Doctores', 'Crear Doctores', 'Editar Doctores', 'Eliminar Doctores',
        ],
        'Farmacia' => [
            'Ver Productos', 'Crear Productos', 'Editar Productos', 'Eliminar Productos',
        ],
        'Productos Farmacia' => [
            'Ver Productos Farmacia', 'Crear Productos Farmacia',
            'Editar Productos Farmacia', 'Eliminar Productos Farmacia',
            'Editar Lotes Farmacia',
        ],
        'Vencimientos' => [
            'Ver Productos por Vencer', 'Ver Productos Vencidos',
        ],
        'Bajas' => [
            'Ver Bajas', 'Crear Bajas', 'Anular Bajas',
        ],
        'Compras' => [
            'Ver Compras', 'Crear Compras', 'Editar Compras', 'Eliminar Compras',
        ],
        'Ventas' => [
            'Ver Ventas', 'Ver Detalle Ventas', 'Crear Ventas', 'Editar Ventas', 'Eliminar Ventas',
            'Ver Montos Caja', 'Ver Ventas Clientes', 'Ver Compras Pacientes',
        ],
        'Caja' => [
            'Ver Cierres Caja', 'Cerrar Caja', 'Validar Cierres Caja',
        ],
        'Caja Administrativa' => [
            'Ver Caja Administrativa', 'Crear Caja Administrativa',
            'Editar Caja Administrativa', 'Anular Caja Administrativa',
        ],
        'Caja General' => [
            'Ver Caja General', 'Crear Caja General',
            'Editar Caja General', 'Anular Caja General',
        ],
        'Seguros' => [
            'Ver Seguros', 'Crear Seguros', 'Editar Seguros', 'Eliminar Seguros',
        ],
        'Laboratorio' => [
            'Ver Solicitudes Laboratorio', 'Crear Solicitudes Laboratorio',
            'Editar Solicitudes Laboratorio', 'Eliminar Solicitudes Laboratorio',
            'Ver Reactivos', 'Crear Reactivos', 'Editar Reactivos', 'Eliminar Reactivos',
            'Ver Derivaciones', 'Crear Derivaciones', 'Editar Derivaciones', 'Eliminar Derivaciones',
        ],
        'Reportes' => [
            'Ver Reportes', 'Imprimir Resultados', 'Exportar Excel',
        ],
        'Configuracion' => [
            'Ver Configuracion', 'Editar Configuracion',
        ],
    ];

    /**
     * Descripción de cada permiso: explica en lenguaje llano qué habilita.
     * Se guarda en `permissions.descripcion` y se muestra como tooltip
     * en la pantalla de permisos del usuario.
     */
    public const DESCRIPCIONES = [
        // Dashboard
        'Ver Dashboard' => 'Acceder al panel principal con los indicadores y resúmenes de la clínica.',

        // Usuarios
        'Ver Usuarios' => 'Ingresar al listado de usuarios y consultar sus datos de cuenta.',
        'Crear Usuarios' => 'Registrar nuevas cuentas de acceso al sistema.',
        'Editar Usuarios' => 'Modificar los datos de un usuario, cambiar su foto y restablecer su contraseña.',
        'Eliminar Usuarios' => 'Dar de baja cuentas de usuario del sistema.',
        'Gestionar Permisos' => 'Asignar o quitar permisos a cualquier usuario. Es el permiso más sensible del sistema.',

        // Pacientes
        'Ver Pacientes' => 'Consultar el listado de pacientes y su historial de atenciones.',
        'Crear Pacientes' => 'Registrar nuevos pacientes en el sistema.',
        'Editar Pacientes' => 'Corregir o actualizar los datos de un paciente ya registrado.',
        'Eliminar Pacientes' => 'Dar de baja pacientes del registro.',

        // Internaciones
        'Ver Internaciones' => 'Consultar las internaciones y los cargos aplicados a cada una.',
        'Crear Internaciones' => 'Registrar el ingreso de un paciente y abrir su internación.',
        'Editar Internaciones' => 'Modificar los datos de una internación y sus cargos de productos o servicios.',
        'Eliminar Internaciones' => 'Anular internaciones registradas.',

        // Doctores
        'Ver Doctores' => 'Consultar el listado de médicos, sus especialidades y sus pacientes.',
        'Crear Doctores' => 'Registrar nuevos médicos y especialidades.',
        'Editar Doctores' => 'Actualizar los datos de un médico registrado.',
        'Eliminar Doctores' => 'Dar de baja médicos del registro.',

        // Farmacia (catálogo general de productos y servicios)
        'Ver Productos' => 'Consultar el catálogo general de productos, servicios, fabricantes y unidades.',
        'Crear Productos' => 'Registrar productos, servicios, fabricantes, unidades y tipos de producto.',
        'Editar Productos' => 'Modificar productos del catálogo, sus precios y su configuración de laboratorio.',
        'Eliminar Productos' => 'Eliminar productos, fabricantes, unidades o tipos del catálogo.',

        // Productos Farmacia (inventario de la farmacia)
        'Ver Productos Farmacia' => 'Consultar el inventario de farmacia, sus lotes y su historial de movimientos.',
        'Crear Productos Farmacia' => 'Registrar nuevos productos en el inventario de farmacia.',
        'Editar Productos Farmacia' => 'Modificar los productos y existencias del inventario de farmacia.',
        'Eliminar Productos Farmacia' => 'Eliminar productos del inventario de farmacia.',
        'Editar Lotes Farmacia' => 'Corregir únicamente el lote y la fecha de vencimiento desde el historial de compras y ventas. Actualiza los movimientos vinculados al mismo lote.',

        // Vencimientos
        'Ver Productos por Vencer' => 'Consultar el reporte de productos próximos a su fecha de vencimiento.',
        'Ver Productos Vencidos' => 'Consultar el reporte de productos que ya vencieron.',

        // Bajas de farmacia
        'Ver Bajas' => 'Consultar las bajas de inventario de farmacia y su detalle por lote.',
        'Crear Bajas' => 'Registrar bajas de medicamentos (vencimiento, cruce, bonificación, deterioro…) descontando stock.',
        'Anular Bajas' => 'Anular una baja registrada y devolver el stock a su lote.',

        // Compras
        'Ver Compras' => 'Consultar las compras a proveedores y el detalle de cada una.',
        'Crear Compras' => 'Registrar compras a proveedores e ingresar la mercadería al inventario.',
        'Editar Compras' => 'Modificar compras ya registradas.',
        'Eliminar Compras' => 'Anular compras y revertir su ingreso al inventario.',

        // Ventas
        'Ver Ventas' => 'Consultar el listado de ventas realizadas.',
        'Ver Detalle Ventas' => 'Abrir el detalle de una venta y reimprimir su comprobante. Sin este permiso no ve el detalle ni puede imprimir.',
        'Crear Ventas' => 'Registrar ventas de productos y servicios, y cobrarlas.',
        'Editar Ventas' => 'Modificar o completar ventas ya registradas.',
        'Eliminar Ventas' => 'Anular ventas registradas.',
        'Ver Montos Caja' => 'Ver los importes de dinero en las pantallas de caja. Sin este permiso los montos se ocultan.',
        'Ver Ventas Clientes' => 'Buscar un cliente y ver el historial de lo que compró por fechas (para facturar). No da acceso al listado general de ventas.',
        'Ver Compras Pacientes' => 'Buscar un paciente y ver todas sus compras con el total acumulado. No da acceso al listado general de ventas.',

        // Caja
        'Ver Cierres Caja' => 'Consultar los cierres de caja realizados y el estado del turno actual.',
        'Cerrar Caja' => 'Ejecutar el cierre de caja del turno y registrar el arqueo.',
        'Validar Cierres Caja' => 'Revisar y validar los cierres de caja. Un cierre validado queda cerrado y ya no admite correcciones.',

        // Caja Administrativa
        'Ver Caja Administrativa' => 'Consultar los ingresos y gastos de la caja administrativa.',
        'Crear Caja Administrativa' => 'Registrar ingresos y gastos en la caja administrativa.',
        'Editar Caja Administrativa' => 'Modificar movimientos de la caja administrativa.',
        'Anular Caja Administrativa' => 'Anular movimientos de la caja administrativa.',

        // Caja General
        'Ver Caja General' => 'Consultar los ingresos y gastos de la caja general.',
        'Crear Caja General' => 'Registrar ingresos y gastos en la caja general.',
        'Editar Caja General' => 'Modificar movimientos de la caja general.',
        'Anular Caja General' => 'Anular movimientos de la caja general.',

        // Seguros
        'Ver Seguros' => 'Consultar los seguros o convenios y el detalle de sus tarifas.',
        'Crear Seguros' => 'Registrar nuevos seguros o convenios.',
        'Editar Seguros' => 'Modificar los datos y tarifas de un seguro.',
        'Eliminar Seguros' => 'Dar de baja seguros o convenios.',

        // Laboratorio
        'Ver Solicitudes Laboratorio' => 'Consultar las solicitudes de laboratorio y sus resultados.',
        'Crear Solicitudes Laboratorio' => 'Registrar nuevas solicitudes de análisis de laboratorio.',
        'Editar Solicitudes Laboratorio' => 'Cargar resultados y modificar solicitudes de laboratorio.',
        'Eliminar Solicitudes Laboratorio' => 'Anular solicitudes de laboratorio.',
        'Ver Reactivos' => 'Consultar los reactivos y su consumo configurado por servicio de laboratorio.',
        'Crear Reactivos' => 'Registrar reactivos y vincularlos con servicios de laboratorio.',
        'Editar Reactivos' => 'Modificar reactivos, existencias y cantidades utilizadas por servicio.',
        'Eliminar Reactivos' => 'Dar de baja reactivos del laboratorio.',
        'Ver Derivaciones' => 'Consultar las derivaciones registradas y generar su impresión.',
        'Crear Derivaciones' => 'Registrar derivaciones y adjuntar imágenes de resultados externos.',
        'Editar Derivaciones' => 'Modificar los datos o la imagen de una derivación.',
        'Eliminar Derivaciones' => 'Eliminar derivaciones registradas.',

        // Reportes
        'Ver Reportes' => 'Acceder a los reportes y estadísticas del sistema.',
        'Imprimir Resultados' => 'Generar e imprimir en PDF los resultados de laboratorio.',
        'Exportar Excel' => 'Descargar en Excel los listados y reportes del sistema.',

        // Configuración
        'Ver Configuracion' => 'Consultar los parámetros generales de configuración del sistema.',
        'Editar Configuracion' => 'Modificar los parámetros generales de configuración del sistema.',
    ];

    /** Todos los nombres de permiso, sin agrupar. */
    public static function todos(): array
    {
        return array_merge(...array_values(self::MODULOS));
    }

    /** Módulo al que pertenece un permiso ('Otros' si no está catalogado). */
    public static function moduloDe(string $permiso): string
    {
        foreach (self::MODULOS as $modulo => $permisos) {
            if (in_array($permiso, $permisos, true)) {
                return $modulo;
            }
        }

        return 'Otros';
    }

    /** Descripción del permiso (null si no está catalogado). */
    public static function descripcionDe(string $permiso): ?string
    {
        return self::DESCRIPCIONES[$permiso] ?? null;
    }
}
