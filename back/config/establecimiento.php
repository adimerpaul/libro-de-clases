<?php

/**
 * Datos del establecimiento que muestra la Libreta del Estudiante (identificación
 * institucional y reglamento abreviado). Se configuran en back/.env.
 */
return [
    'nombre' => env('ESTABLECIMIENTO_NOMBRE', 'Colegio Demo Ecosistema 4.2'),
    'rbd' => env('ESTABLECIMIENTO_RBD', '00000-0'),
    'direccion' => env('ESTABLECIMIENTO_DIRECCION', 'Av. Educación 1200, Temuco · dato ficticio'),
    'telefono' => env('ESTABLECIMIENTO_TELEFONO', '+56 45 200 0000'),
    'correo' => env('ESTABLECIMIENTO_CORREO', 'contacto@demo.cl'),
    'jornada' => env('ESTABLECIMIENTO_JORNADA', 'Jornada Escolar Completa'),

    // Reglamento interno abreviado: [título, texto]
    'reglamento' => [
        ['Derechos', 'Trato digno, participación, seguridad, apoyo pedagógico y acceso a información pertinente.'],
        ['Deberes', 'Asistir y participar, respetar a los integrantes de la comunidad, cuidar los espacios y cumplir los acuerdos formativos.'],
        ['Convivencia', 'Las situaciones se abordan con medidas formativas, protocolos y debido proceso según el Reglamento Interno vigente.'],
        ['Seguridad', 'Seguir las instrucciones del PISE, informar emergencias y mantener actualizados los contactos de retiro y emergencia.'],
    ],
];
