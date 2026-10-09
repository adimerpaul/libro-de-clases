<template>
  <q-btn
    v-if="canVer"
    flat
    round
    dense
    color="primary"
    icon="notifications"
    aria-label="Productos por vencer"
    @click="cargar"
  >
    <q-badge v-if="total > 0" color="negative" floating rounded>
      {{ total > 99 ? '99+' : total }}
    </q-badge>

    <q-tooltip>Productos por vencer (30 días)</q-tooltip>

    <q-menu anchor="bottom right" self="top right" style="width:330px">
      <div class="row items-center q-px-md q-py-sm campana-header">
        <div class="col">
          <div class="text-subtitle2 text-weight-bold">Productos por vencer</div>
          <div class="text-caption text-grey-7">Próximos {{ DIAS }} días</div>
        </div>
        <q-spinner v-if="loading" color="primary" size="18px" />
        <q-badge v-else-if="total > 0" color="negative" rounded>{{ total }}</q-badge>
      </div>

      <q-separator />

      <q-list dense style="max-height:340px;overflow:auto">
        <q-item v-if="!loading && rows.length === 0">
          <q-item-section class="text-center text-grey-6 q-py-md">
            <q-icon name="check_circle" color="positive" size="32px" />
            <div class="text-caption q-mt-xs">Sin lotes por vencer</div>
          </q-item-section>
        </q-item>

        <q-item
          v-for="row in rows"
          :key="row.id"
          clickable
          v-ripple
          v-close-popup
          @click="irAPorVencer"
        >
          <q-item-section avatar>
            <q-avatar :color="chipColor(row)" text-color="white" size="34px" class="text-weight-bold">
              {{ Math.max(0, Number(row.dias_vencimiento || 0)) }}
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label lines="1" class="text-weight-medium">
              {{ row.producto?.nombre || 'Producto' }}
            </q-item-label>
            <q-item-label caption lines="1">
              Lote: {{ row.lote || 'S/L' }} · Vence {{ fecha(row.fecha_vencimiento) }}
            </q-item-label>
            <q-item-label caption class="text-weight-medium" :class="'text-' + chipColor(row)">
              {{ textoDias(row) }} · Existencia {{ numero(row.existencia) }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>

      <q-separator />

      <q-item clickable v-ripple v-close-popup @click="irAPorVencer">
        <q-item-section class="text-primary text-weight-medium text-center">
          Ver productos por vencer
        </q-item-section>
      </q-item>
    </q-menu>
  </q-btn>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, onBeforeUnmount, ref } from 'vue'

const { proxy } = getCurrentInstance()

const DIAS = 30
const PERMISO = 'Ver Productos por Vencer'
const REFRESCO_MS = 5 * 60 * 1000

const rows = ref([])
const total = ref(0)
const loading = ref(false)
let timer = null

const canVer = computed(() => proxy.$store.hasPermission(PERMISO))

onMounted(() => {
  if (!canVer.value) return
  cargar()
  timer = setInterval(cargar, REFRESCO_MS)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})

async function cargar () {
  if (!canVer.value || loading.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('/productos-por-vencer', {
      params: { valor: DIAS, unidad: 'DIAS', page: 1, per_page: 8 },
    })
    rows.value = data.data || []
    total.value = data.total || 0
  } catch {
    rows.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function irAPorVencer () {
  proxy.$router.push('/productos-por-vencer')
}

function fecha (value) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('es-BO').format(new Date(String(value).slice(0, 10) + 'T00:00:00'))
}

function numero (value) {
  return new Intl.NumberFormat('es-BO', { maximumFractionDigits: 4 }).format(Number(value || 0))
}

function textoDias (row) {
  const dias = Number(row.dias_vencimiento || 0)
  if (dias < 0) return `Venció hace ${Math.abs(dias)} día${Math.abs(dias) === 1 ? '' : 's'}`
  if (dias === 0) return 'Vence hoy'
  return `Vence en ${dias} día${dias === 1 ? '' : 's'}`
}

function chipColor (row) {
  const dias = Number(row.dias_vencimiento || 0)
  if (dias <= 0) return 'negative'
  if (dias <= 7) return 'orange-9'
  return 'warning'
}
</script>

<style scoped>
.campana-header {
  background: #f4f7f6;
}
</style>
