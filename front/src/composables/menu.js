import { PERMISOS_ADMIN } from './adminModulos'

// Menú lateral del Ecosistema 4.2 (ui3/app.js ROUTES). Los módulos con `pendiente: true` aún no
// tienen página propia: los muestra pages/index/[modulo].vue. Al construir uno, crear su página
// (p. ej. pages/index/pie.vue), cambiar `link` si hace falta, darle su `permiso` y quitar `pendiente`.
export const MENU = [
  {
    title: 'Trabajo',
    links: [
      { key: 'inicio', title: 'Inicio', icon: 'fas fa-house', link: '/' },
    ],
  },
  {
    title: 'Docencia',
    links: [
      { key: 'libro', title: 'Libro de clases', icon: 'fas fa-book', link: '/libros', permiso: ['libros.ver', 'clases.dictar'], desc: 'Asistencia, leccionario, evaluaciones y observaciones en formato físico-digital.' },
      { key: 'planificador', title: 'Planificador', icon: 'far fa-calendar-days', link: '/planificador', permiso: 'clases.dictar', desc: 'Curso/nivel → asignatura → OA. El OA elegido precarga contenidos, indicadores, inicio, desarrollo y cierre.' },
      { key: 'curriculum', title: 'Currículum y OA', icon: 'fas fa-bullseye', pendiente: true, desc: 'Navegación por Parvularia, Básica, Media HC y TP. OA → contenidos → competencias → indicadores → instrumentos, desde un catálogo versionado del Mineduc.' },
      { key: 'evaluaciones', title: 'Evaluaciones + corrector', icon: 'fas fa-flask', link: '/evaluaciones', permiso: 'clases.dictar', desc: 'Crea instrumentos por asignatura y OA, y corrige con criterios vinculados al OA.' },
      { key: 'recursos', title: 'Biblioteca', icon: 'fas fa-toolbox', link: '/recursos', permiso: ['recursos.ver', 'clases.dictar'], desc: 'Actividades, presentaciones, guías, material didáctico, videos y apoyos DUA/PIE organizados por nivel, asignatura y OA.' },
    ],
  },
  {
    title: 'Apoyo',
    links: [
      { key: 'pie', title: 'PIE / Inclusión', icon: 'fas fa-puzzle-piece', link: '/pie', permiso: 'pie.ver', desc: 'Evaluación integral, barreras, co-docencia, DUA, PACI, flexibilización, aula de recursos y vinculación familiar.' },
    ],
  },
  {
    title: 'Gestión',
    links: [
      { key: 'utp', title: 'Gestión UTP', icon: 'fas fa-chart-line', pendiente: true, desc: 'Alineación curricular, supervisión de planificaciones, cobertura, observación de aula, validación de instrumentos y coordinación PIE.' },
      { key: 'inspectoria', title: 'Inspectoría', icon: 'fas fa-shield-halved', pendiente: true, desc: 'RIE, asistencia, puntualidad, horarios, reemplazos, retiros, PISE, infraestructura, recreos y atención a apoderados.' },
      { key: 'convivencia', title: 'Convivencia', icon: 'fas fa-handshake', pendiente: true, desc: 'Plan de gestión, talleres, participación, prevención de violencia y ciberacoso, mediación, protocolos y redes externas.' },
      { key: 'protocolos', title: 'Protocolos', icon: 'fas fa-triangle-exclamation', pendiente: true, desc: 'Rutas de actuación compartidas por Inspectoría y Convivencia: hechos, medidas de resguardo, responsables y seguimiento.' },
      { key: 'reportes', title: 'Control Tower', icon: 'fas fa-table-cells-large', pendiente: true, desc: 'Vista agregada: cobertura, asistencia, inclusión, convivencia y cumplimiento.' },
    ],
  },
  {
    title: 'Operación',
    links: [
      { key: 'rfid', title: 'Asistencia RFID', icon: 'fas fa-tower-broadcast', link: '/rfid', permiso: 'rfid.ver', desc: 'El evento físico no es la asistencia oficial: el motor propone y los casos inciertos los valida una persona autorizada.' },
      { key: 'retiros', title: 'Retiro estudiantes', icon: 'fas fa-door-open', link: '/retiros', permiso: 'matriculas.ver', desc: 'Baja de estudiantes del año escolar (retiro o traslado) con fecha, motivo y trazabilidad.' },
    ],
  },
  {
    title: 'Comunidad',
    links: [
      { key: 'student360', title: 'Student 360', icon: 'fas fa-users', pendiente: true, desc: 'Vista longitudinal autorizada del estudiante; los dominios sensibles se muestran solo según finalidad y perfil.' },
      { key: 'comunidad', title: 'Comunidad', icon: 'far fa-comments', pendiente: true, desc: 'Comunicaciones segmentadas y autorizaciones según rol, curso, estudiante y finalidad.' },
      { key: 'libreta', title: 'Libreta estudiante', icon: 'fas fa-book-bookmark', pendiente: true, desc: 'Identificación, reglamento abreviado, citaciones, justificaciones, retiros, firmas, calendario, portafolio y rúbricas visibles.' },
      { key: 'familia', title: 'Mis hijos', icon: 'fas fa-house-chimney-user', pendiente: true, desc: 'Información exclusiva de los hijos del apoderado: asistencia, notas, anotaciones, citaciones y libreta.' },
    ],
  },
  {
    title: 'Administración',
    links: [
      // Cursos, asignaturas, docentes, asignación docente, usuarios y roles van como pestañas (pages/index/admin.vue)
      { key: 'admin', title: 'Administrador', icon: 'fas fa-gears', link: '/admin', permiso: PERMISOS_ADMIN },
      { key: 'estudiantes', title: 'Estudiantes', icon: 'fas fa-user-graduate', link: '/estudiantes', permiso: 'estudiantes.ver' },
      { key: 'matriculas', title: 'Matrículas', icon: 'fas fa-people-roof', link: '/matriculas', permiso: 'matriculas.ver' },
    ],
  },
]

// Los pendientes se enlazan a /<key> (pages/index/[modulo].vue)
MENU.forEach(g => g.links.forEach(l => { if (l.pendiente) l.link = `/${l.key}` }))

export const MODULOS = Object.fromEntries(MENU.flatMap(g => g.links).map(l => [l.key, l]))

/** Grupos con los enlaces que el usuario puede ver; los pendientes los ve todo usuario. */
export function menuVisible (auth) {
  return MENU
    .map(g => ({ ...g, links: g.links.filter(l => !l.permiso || auth.hasPermission(l.permiso)) }))
    .filter(g => g.links.length)
}

// Perfiles de ui3 (PROFILE_CONFIG): color de acento (clases .role-* de css/app.css) y tareas de inicio.
// Se elige por el primer rol de Spatie del usuario que tenga perfil; sin rol conocido, "docente".
const PERFILES = {
  docente: {
    tag: 'Enseñanza, evaluación y libro',
    titulo: 'Trabajo docente',
    desc: 'La plataforma concentra libro, planificación, OA, evaluación, corrección y recursos en un flujo único.',
    tareas: [
      ['libro', 'Libro de clases', 'Asistencia, leccionario, evaluaciones y observaciones en formato físico-digital.'],
      ['planificador', 'Planificar desde OA', 'Contenidos, competencias, indicadores, inicio, desarrollo, cierre, recursos, tiempo y espacio.'],
      ['evaluaciones', 'Crear y corregir', 'Diseña instrumentos por asignatura y corrige con criterios vinculados al OA.'],
      ['recursos', 'Biblioteca pedagógica', 'Actividades, presentaciones, guías, videos y material didáctico.'],
    ],
  },
  diferencial: {
    tag: 'PIE, DUA, PACI y co-docencia',
    titulo: 'Educación diferencial',
    desc: 'Evaluación integral, plan de apoyo, expediente PIE, co-docencia, aula de recursos y vinculación con la familia.',
    tareas: [
      ['pie', 'Casos PIE', 'Ingreso, diagnóstico, equipo de apoyo y estado de cada estudiante del programa.'],
      ['pie', 'Expediente', 'FUDEI, informes, autorizaciones y evidencias del estudiante en un solo lugar.'],
      ['pie', 'Bitácora de apoyos', 'Sesiones en aula de recursos, co-docencia, entrevistas y reuniones de equipo.'],
      ['recursos', 'Recursos DUA', 'Material didáctico y apoyos DUA/PIE de la biblioteca pedagógica.'],
    ],
  },
  utp: {
    tag: 'Gestión curricular y acompañamiento',
    titulo: 'Unidad Técnico Pedagógica',
    desc: 'Alineación curricular, revisión de planificaciones, cobertura, acompañamiento docente, evaluación y coordinación PIE.',
    tareas: [
      ['curriculum', 'Cobertura curricular', 'Revisa OA, competencias y avance por nivel/asignatura.'],
      ['utp', 'Supervisar planificaciones', 'Revisa coherencia, retroalimenta y aprueba planificaciones.'],
      ['utp', 'Acompañamiento de aula', 'Observaciones, feedback y acuerdos de mejora docente.'],
      ['evaluaciones', 'Validar instrumentos', 'Revisa criterios, rúbricas y pruebas antes de aplicar.'],
    ],
  },
  inspectoria: {
    tag: 'Asistencia, protocolos, seguridad y logística',
    titulo: 'Inspectoría General',
    desc: 'RIE, asistencia y puntualidad, horarios, reemplazos, retiros, PISE, planta física, recreos y atención a apoderados.',
    tareas: [
      ['rfid', 'Asistencia y puntualidad', 'Revisa asistencia diaria, atrasos e inasistencias reiteradas.'],
      ['retiros', 'Retiro de estudiantes', 'Autorización, identidad de quien retira, motivo, hora y trazabilidad.'],
      ['protocolos', 'Protocolos y RIE', 'Activa rutas de actuación y registra medidas de resguardo.'],
      ['inspectoria', 'Seguridad y logística', 'Horarios, reemplazos, PISE, infraestructura y turnos.'],
    ],
  },
  convivencia: {
    tag: 'Prevención, mediación y participación',
    titulo: 'Convivencia educativa',
    desc: 'Plan de gestión, participación, prevención, mediación, protocolos, redes externas y apoyo a docentes.',
    tareas: [
      ['convivencia', 'Plan de convivencia', 'Planifica talleres, participación y acciones preventivas.'],
      ['convivencia', 'Detección temprana', 'Indicadores de riesgo, exclusión, violencia y ciberacoso.'],
      ['convivencia', 'Mediación', 'Registra acuerdos restaurativos y seguimientos.'],
      ['protocolos', 'Protocolos', 'Coordina activación y seguimiento junto a Inspectoría.'],
    ],
  },
  sostenedor: {
    tag: 'Visión total del establecimiento',
    titulo: 'Control global',
    desc: 'Mayor nivel de visibilidad: módulos, cumplimiento, inclusión, convivencia y comunidad, manteniendo trazabilidad de accesos.',
    tareas: [
      ['reportes', 'Control Tower', 'Excepciones y cumplimiento de todo el establecimiento.'],
      ['libro', 'Libro 3.0', 'Supervisa evidencia y estado de los libros de clases.'],
      ['utp', 'UTP y cobertura', 'Cobertura OA, planificaciones y resultados.'],
      ['pie', 'PIE e inclusión', 'Seguimiento agregado de apoyos, PACI y co-docencia.'],
    ],
  },
}

const PERFIL_DE_ROL = {
  Administrador: 'sostenedor',
  'Dirección': 'sostenedor',
  Sostenedor: 'sostenedor',
  UTP: 'utp',
  'Educadora diferencial': 'diferencial',
  'Inspectoría': 'inspectoria',
  Convivencia: 'convivencia',
  Docente: 'docente',
}

export function perfilDe (user) {
  const rol = (user?.roles || []).find(r => PERFIL_DE_ROL[r])
  const clave = PERFIL_DE_ROL[rol] || 'docente'
  return { clave, rol: rol || user?.roles?.[0] || 'Usuario', ...PERFILES[clave] }
}
