/** RUN chileno para mostrar: 12345678-5 → 12.345.678-5 */
export function formatoRun (run) {
  if (!run) return '—'
  const [cuerpo, dv] = run.split('-')
  return cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (dv ? '-' + dv : '')
}
