<template>
  <div>
    <q-btn-toggle v-model="pago.tipo_pago" spread no-caps unelevated dense class="pago-toggle q-mb-sm"
                  toggle-color="primary" color="grey-2" text-color="grey-8"
                  :options="[
                    { label: 'Efectivo', value: 'EFECTIVO', icon: 'payments' },
                    { label: 'QR', value: 'QR', icon: 'qr_code_2' },
                    { label: 'Mixto', value: 'MIXTO', icon: 'call_split' },
                  ]"
                  @update:model-value="onTipo" />

    <div class="row q-col-gutter-sm">
      <div v-if="pago.tipo_pago !== 'EFECTIVO'" :class="pago.tipo_pago === 'QR' ? 'col-12' : 'col-4'">
        <q-input v-if="pago.tipo_pago === 'QR'" :model-value="money(total)" label="Monto QR Bs" dense outlined
                 readonly input-class="text-right text-weight-bold">
          <template v-slot:prepend><q-icon name="qr_code_2" color="primary" /></template>
        </q-input>
        <q-input v-else v-model.number="pago.monto_qr" label="Monto QR Bs *" dense outlined autofocus
                 type="number" step="0.01" min="0" input-class="text-right" @update:model-value="onQr">
          <template v-slot:prepend><q-icon name="qr_code_2" color="primary" /></template>
        </q-input>
      </div>

      <template v-if="pago.tipo_pago !== 'QR'">
        <div :class="pago.tipo_pago === 'MIXTO' ? 'col-4' : 'col-6'">
          <q-input v-model.number="pago.monto_efectivo" dense outlined type="number" step="0.01" min="0"
                   input-class="text-right" :autofocus="pago.tipo_pago === 'EFECTIVO'"
                   :label="pago.tipo_pago === 'MIXTO' ? 'Efectivo Bs' : 'Efectivo recibido Bs'"
                   :placeholder="money(aCubrir)"
                   :hint="pago.tipo_pago === 'MIXTO' ? 'Resto a pagar: ' + money(aCubrir) + ' Bs' : ''">
            <template v-slot:prepend><q-icon name="payments" color="green-8" /></template>
          </q-input>
        </div>
        <div :class="pago.tipo_pago === 'MIXTO' ? 'col-4' : 'col-6'">
          <q-input :model-value="money(cambio)" :label="cambio < 0 ? 'Falta Bs' : 'Cambio Bs'" dense outlined readonly
                   :input-class="'text-right text-weight-bold ' + (cambio < 0 ? 'text-negative' : 'text-positive')" />
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { efectivoACubrir, cambioPago } from '../addons/pagoVenta'

// v-model: { tipo_pago, monto_efectivo, monto_qr } (ver addons/pagoVenta.js)
const pago = defineModel({ type: Object, required: true })
const props = defineProps({ total: { type: Number, default: 0 } })

const aCubrir = computed(() => efectivoACubrir(pago.value, props.total))
const cambio  = computed(() => cambioPago(pago.value, props.total))

function money (v) { return Number(v || 0).toFixed(2) }

function onTipo () {
  pago.value.monto_efectivo = null
  pago.value.monto_qr = null
}

// Al escribir el QR, el efectivo se llena solo con lo que falta.
function onQr () {
  const resto = aCubrir.value
  pago.value.monto_efectivo = resto > 0 ? resto : null
}
</script>

<style scoped>
.pago-toggle {
  border: 1px solid #e0e0e0;
}
</style>
