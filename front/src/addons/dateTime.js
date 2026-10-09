const BOLIVIA_TIMEZONE = 'America/La_Paz'
const MONTHS_SHORT = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic']

const boliviaDateTimeFormatter = new Intl.DateTimeFormat('en-CA', {
  timeZone: BOLIVIA_TIMEZONE,
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
  hourCycle: 'h23',
})

function localPartsFromText (value) {
  const match = String(value || '')
    .trim()
    .replace('Z', '')
    .match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?$/)
  if (!match) return null
  return {
    year: match[1],
    month: match[2],
    day: match[3],
    hour: match[4] || '00',
    minute: match[5] || '00',
    second: match[6] || '00',
  }
}

function boliviaPartsFromDate (value) {
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return null
  const parts = boliviaDateTimeFormatter.formatToParts(date)
  const map = {}
  parts.forEach((p) => {
    if (p.type !== 'literal') map[p.type] = p.value
  })
  return {
    year: map.year,
    month: map.month,
    day: map.day,
    hour: map.hour,
    minute: map.minute,
    second: map.second,
  }
}

function resolveParts (value) {
  return localPartsFromText(value) || boliviaPartsFromDate(value)
}

export function nowBoliviaDateTimeInput () {
  const p = boliviaPartsFromDate(new Date())
  return `${p.year}-${p.month}-${p.day}T${p.hour}:${p.minute}`
}

export function formatBoliviaDateTime (value, empty = '—') {
  if (!value) return empty
  const p = resolveParts(value)
  if (!p) return String(value)
  return `${p.year}-${p.month}-${p.day} ${p.hour}:${p.minute}`
}

export function formatBoliviaDate (value, empty = '') {
  if (!value) return empty
  const p = resolveParts(value)
  if (!p) return String(value)
  return `${p.year}-${p.month}-${p.day}`
}

export function formatBoliviaTime (value, empty = '') {
  if (!value) return empty
  const p = resolveParts(value)
  if (!p) return String(value)
  return `${p.hour}:${p.minute}`
}

export function formatBoliviaDateDmYHis (value, empty = '') {
  if (!value) return empty
  const p = resolveParts(value)
  if (!p) return String(value)
  const monthName = MONTHS_SHORT[Number(p.month) - 1] || p.month
  const hour24 = Number(p.hour)
  const hour12 = hour24 % 12 || 12
  const ampm = hour24 >= 12 ? 'PM' : 'AM'
  return `${p.day} ${monthName} ${p.year} ${String(hour12).padStart(2, '0')}:${p.minute} ${ampm}`
}
