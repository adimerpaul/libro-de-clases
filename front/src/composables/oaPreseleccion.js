/**
 * Llegada desde Currículum y OA ("Usar en planificación" / "Crear evaluación"):
 * la URL trae ?oa=<id>&nivel=<nivel>&asignatura_id=<id>. Devuelve la asignación de la
 * carga docente que corresponde (mismo nivel y asignatura) y el OA, o null si no viene.
 * Con varias asignaciones posibles (p. ej. 2° Medio A y B) toma la primera.
 */
export function oaDesdeRuta (route, carga) {
  const oa = Number(route.query.oa)
  if (!oa) return null
  const asignacion = carga.find(a => a.curso.nivel === route.query.nivel && a.asignatura_id === Number(route.query.asignatura_id))
  return { oa, asignacion: asignacion || null }
}
