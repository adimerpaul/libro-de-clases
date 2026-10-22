// Fechas en hora local (sin pasar por UTC, que corre el día en Chile)

/** Date → 'YYYY-MM-DD' */
export function iso (d) {
  const p = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`
}

/** Lunes de la semana de esa fecha, a mediodía (evita saltos por cambio de horario). */
export function lunesDe (d) {
  const x = new Date(d.getFullYear(), d.getMonth(), d.getDate(), 12)
  x.setDate(x.getDate() - ((x.getDay() + 6) % 7))
  return x
}

export function sumarDias (d, n) {
  const x = new Date(d)
  x.setDate(x.getDate() + n)
  return x
}

/** 'YYYY-MM-DD' → '09-10-2026' */
export function fechaCl (s) {
  return s ? s.slice(0, 10).split('-').reverse().join('-') : ''
}
