<template>
  <div>
    <q-inner-loading :showing="!r" />
    <template v-if="r">
      <div class="eeStats">
        <div class="card eeStat">
          <span class="eeIcon violeta"><q-icon name="fas fa-heart" /></span>
          <small>Sesiones realizadas {{ r.anio }}</small>
          <b>{{ r.sesiones_realizadas }}</b>
          <span class="subtle">{{ r.sesiones_total }} registradas en el año</span>
        </div>
        <div class="card eeStat">
          <span class="eeIcon verde"><q-icon name="fas fa-users" /></span>
          <small>Estudiantes alcanzados</small>
          <b>{{ r.estudiantes_alcanzados }}</b>
          <span class="subtle">{{ pct(r.estudiantes_alcanzados, r.estudiantes_total) }}% del total ({{ r.estudiantes_total }})</span>
        </div>
        <div class="card eeStat">
          <span class="eeIcon ambar"><q-icon name="fas fa-triangle-exclamation" /></span>
          <small>Alertas de bienestar abiertas</small>
          <b>{{ r.alertas_abiertas }}</b>
          <span class="subtle">{{ r.alertas_alta }} de intensidad alta</span>
        </div>
        <div class="card eeStat">
          <span class="eeIcon cian"><q-icon name="fas fa-circle-check" /></span>
          <small>Cobertura del plan</small>
          <b>{{ r.cobertura }}%</b>
          <span class="subtle">competencias × tramos con sesiones</span>
        </div>
      </div>

      <div class="eeGrid2">
        <div class="card">
          <div class="cardHead">
            <h3>Competencias socioemocionales</h3>
            <span class="subtle">logro según el último diagnóstico ({{ r.diagnosticados }} estudiantes)</span>
          </div>
          <div v-for="c in r.competencias" :key="c.value" class="barra">
            <div class="barraTop">
              <span>{{ c.label }} <small class="subtle">· {{ c.sesiones }} {{ c.sesiones === 1 ? 'sesión' : 'sesiones' }}</small></span>
              <b>{{ c.logro ?? '—' }}{{ c.logro != null ? '%' : '' }}</b>
            </div>
            <div class="track"><div class="fill" :style="{ width: `${c.logro || 0}%`, background: c.color }" /></div>
          </div>
        </div>

        <div class="card">
          <div class="cardHead"><h3>Alertas de bienestar recientes</h3></div>
          <div v-if="!r.recientes.length" class="subtle">Sin alertas registradas este año.</div>
          <router-link v-for="a in r.recientes" :key="a.id" :to="`/emocional/${a.estudiante_id}`" class="alertaFila" :class="a.estado === 'cerrada' ? 'cerrada' : a.tipo">
            <q-icon :name="a.estado === 'cerrada' ? 'fas fa-circle-check' : a.tipo === 'riesgo' ? 'fas fa-circle-exclamation' : 'fas fa-triangle-exclamation'" />
            <div>
              <b>{{ etiqueta(cat?.tipos_alerta, a.tipo) }}</b> · {{ a.curso || 'sin curso' }}
              <small>{{ a.estudiante }} · {{ etiqueta(cat?.estados_alerta, a.estado) }} · {{ fechaCl(a.fecha) }}</small>
            </div>
          </router-link>
          <q-btn outline no-caps color="primary" class="full-width q-mt-md btnOutline" label="Ver todos los seguimientos" @click="$emit('ir', 'seguimiento')" />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { getCurrentInstance, onMounted, ref } from 'vue'
import { useCatalogosEmocional, etiqueta } from '../../composables/emocional'
import { fechaCl } from '../../composables/fechas'
import { mensajeError } from '../../composables/useCrud'

defineEmits(['ir'])
const { proxy } = getCurrentInstance()
const cat = useCatalogosEmocional()
const r = ref(null)

const pct = (a, b) => (b ? Math.round((100 * a) / b) : 0)

onMounted(() => {
  proxy.$axios.get('emocional/resumen').then(res => { r.value = res.data }).catch(e => proxy.$alert.error(mensajeError(e)))
})
</script>

<style scoped>
.eeStats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 14px }
.eeStat { display: flex; flex-direction: column; gap: 2px }
.eeStat small { color: #6d8090; font-size: 12px; margin-top: 8px }
.eeStat b { font-size: 28px; color: #0b2440; line-height: 1.1 }
.eeIcon { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; font-size: 16px }
.eeIcon.violeta { background: #ede9fe; color: #7c3aed }
.eeIcon.verde { background: #d1fae5; color: #065f46 }
.eeIcon.ambar { background: #fef3c7; color: #92400e }
.eeIcon.cian { background: #cffafe; color: #155e75 }
.eeGrid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px }
.barra { margin-bottom: 14px }
.barraTop { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px }
.track { height: 9px; background: #eef2f7; border-radius: 99px; overflow: hidden }
.fill { height: 100%; border-radius: 99px; transition: width .5s }
.alertaFila { display: flex; gap: 10px; align-items: flex-start; padding: 10px 12px; border-radius: 12px; margin-bottom: 8px; text-decoration: none; color: #33536d; border: 1px solid transparent; font-size: 13px }
.alertaFila:hover { border-color: #d9e3ec }
.alertaFila small { display: block; font-size: 11.5px; margin-top: 2px }
.alertaFila .q-icon { margin-top: 3px }
.alertaFila.desregulacion { background: #fffbeb; color: #92400e }
.alertaFila.bienestar { background: #f5f3ff; color: #5b21b6 }
.alertaFila.riesgo { background: #fef2f2; color: #991b1b }
.alertaFila.cerrada { background: #f0fdf4; color: #166534 }
.btnOutline { border-radius: 11px; font-weight: 800 }
@media (max-width: 1100px) { .eeStats { grid-template-columns: repeat(2, 1fr) } .eeGrid2 { grid-template-columns: 1fr } }
@media (max-width: 600px) { .eeStats { grid-template-columns: 1fr } }
</style>
