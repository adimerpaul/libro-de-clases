// Cobro de una venta en EFECTIVO, QR o MIXTO (parte QR y el resto en efectivo).
// Lo usan Nueva venta y Cobrar venta pendiente, junto con components/PagoVenta.vue.

const r2 = v => Math.round((Number(v) || 0) * 100) / 100

export function pagoVacio () {
  return { tipo_pago: 'EFECTIVO', monto_efectivo: null, monto_qr: null }
}

// Efectivo que falta cubrir después del QR (en EFECTIVO es todo el total).
export function efectivoACubrir (pago, total) {
  if (pago.tipo_pago === 'QR') return 0
  if (pago.tipo_pago === 'MIXTO') return r2(total - (Number(pago.monto_qr) || 0))
  return r2(total)
}

// Efectivo entregado por el cliente; vacío = el monto exacto.
export function efectivoRecibido (pago, total) {
  const v = pago.monto_efectivo
  return v === null || v === '' ? efectivoACubrir(pago, total) : r2(v)
}

export function cambioPago (pago, total) {
  if (pago.tipo_pago === 'QR') return 0
  return r2(efectivoRecibido(pago, total) - efectivoACubrir(pago, total))
}

// Mensaje de error, o null si el pago cubre el total.
export function validarPago (pago, total) {
  if (pago.tipo_pago === 'MIXTO') {
    const qr = Number(pago.monto_qr) || 0
    if (qr <= 0 || qr >= total) return 'En pago mixto el monto QR debe ser mayor a 0 y menor al total'
  }
  if (cambioPago(pago, total) < 0) return 'El efectivo no alcanza para cubrir el total'
  return null
}

export function payloadPago (pago, total) {
  return {
    tipo_pago: pago.tipo_pago,
    monto_qr: pago.tipo_pago === 'MIXTO' ? r2(pago.monto_qr) : (pago.tipo_pago === 'QR' ? r2(total) : 0),
    monto_efectivo: pago.tipo_pago === 'QR' ? 0 : efectivoRecibido(pago, total),
  }
}
