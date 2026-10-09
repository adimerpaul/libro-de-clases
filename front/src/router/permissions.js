// Fuente única de verdad: qué permiso exige cada ruta del sistema.
// Se usa tanto en el guard del router (bloquea el acceso por URL)
// como en el menú lateral (oculta los enlaces sin permiso).
// El orden importa: las rutas más específicas van primero.
export const routePermissions = [
  // '/' no se lista: el panel es accesible para todos y decide por dentro
  // si muestra los indicadores ('Ver Dashboard') o solo el acceso a ventas.
  { path: '/usuarios', can: 'Ver Usuarios' },

  { path: '/farmacia', can: 'Ver Productos' },
  { path: '/productos', can: 'Editar Productos' },
  { path: '/productos-farmacia', can: 'Ver Productos Farmacia' },
  { path: '/productos-por-vencer', can: 'Ver Productos por Vencer' },
  { path: '/productos-vencidos', can: 'Ver Productos Vencidos' },
  { path: '/bajas', can: 'Ver Bajas' },
  { path: '/compras/crear', can: 'Crear Compras' },
  { path: '/compras', can: 'Ver Compras' },
  { path: '/proveedores', can: 'Ver Compras' },
  { path: '/ventas-farmacia/crear', can: 'Crear Ventas' },
  { path: '/ventas-farmacia', can: 'Ver Ventas' },
  { path: '/ventas-clientes', can: 'Ver Ventas Clientes' },
  { path: '/compras-pacientes', can: 'Ver Compras Pacientes' },
  { path: '/ventas/crear', can: 'Crear Ventas' },
  { path: '/ventas', can: 'Ver Ventas' },
  { path: '/cierres-caja', can: 'Ver Cierres Caja' },
  { path: '/cajas/administrativa', can: 'Ver Caja Administrativa' },
  { path: '/cajas/general', can: 'Ver Caja General' },

  { path: '/seguros', can: 'Ver Seguros' },
  { path: '/pacientes', can: ['Ver Pacientes', 'Ver Internaciones'] },
  { path: '/doctores', can: 'Ver Doctores' },

  { path: '/laboratorio', can: 'Ver Productos' },
  { path: '/reportes-laboratorio', can: 'Ver Solicitudes Laboratorio' },
  { path: '/reactivos', can: 'Ver Reactivos' },
  { path: '/reactivos-kardex', can: 'Ver Reactivos' },
  { path: '/solicitudes-laboratorio/nueva', can: 'Crear Solicitudes Laboratorio' },
  { path: '/solicitudes-laboratorio', can: 'Ver Solicitudes Laboratorio' },
  { path: '/derivaciones', can: 'Ver Derivaciones' },
]

// Devuelve el permiso exigido por una ruta (null si es pública para logueados).
export function permissionForPath (path) {
  const clean = (path || '/').replace(/\/+$/, '') || '/'
  const match = routePermissions
    .filter(r => clean === r.path || clean.startsWith(r.path + '/'))
    .sort((a, b) => b.path.length - a.path.length)[0]
  return match ? match.can : null
}

export function hasPermission (permissions, can) {
  if (!can) return true
  const list = permissions || []
  if (Array.isArray(can)) return can.some(p => list.includes(p))
  return list.includes(can)
}
