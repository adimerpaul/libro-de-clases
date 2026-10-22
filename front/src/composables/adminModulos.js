// Pestañas del menú Administrador. Cada una es un módulo con permisos CRUD
// "<modulo>.ver|crear|editar|eliminar" (back/app/Support/Permisos.php).
export const MODULOS_ADMIN = [
  { modulo: 'cursos', label: 'Cursos', icon: 'fas fa-school', to: '/admin/cursos' },
  { modulo: 'asignaturas', label: 'Asignaturas', icon: 'fas fa-book', to: '/admin/asignaturas' },
  { modulo: 'docentes', label: 'Docentes', icon: 'fas fa-chalkboard-user', to: '/admin/docentes' },
  { modulo: 'asignaciones', label: 'Asignación docente', icon: 'fas fa-list-check', to: '/admin/asignaciones' },
  { modulo: 'usuarios', label: 'Usuarios y permisos', icon: 'fas fa-user-shield', to: '/admin/usuarios' },
  { modulo: 'roles', label: 'Roles', icon: 'fas fa-id-badge', to: '/admin/roles' },
]

/** Basta ver un módulo para entrar al menú Administrador. */
export const PERMISOS_ADMIN = MODULOS_ADMIN.map(m => `${m.modulo}.ver`)
