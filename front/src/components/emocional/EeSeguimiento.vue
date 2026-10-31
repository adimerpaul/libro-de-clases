<template>
  <div class="segGrid">
    <!-- Listado -->
    <div class="card">
      <div class="cardHead">
        <h3>Alertas <span class="badge blue q-ml-sm">{{ alertas.length }}</span></h3>
        <q-btn v-if="$auth.hasPermission('emocional.crear')" label="+ Nueva alerta" no-caps unelevated class="btnPrimary" @click="abrirForm()" />
      </div>
      <div class="filtros q-mb-sm">
        <q-select v-model="filtro.estado" :options="opcionesEstado" emit-value map-options dense outlined label="Estado" style="min-width: 160px" @update:model-value="cargar" />
        <q-select v-model="filtro.tipo" :options="cat?.tipos_alerta || []" emit-value map-options clearable dense outlined label="Tipo" style="min-width: 150px" @update:model-value="cargar" />
        <q-select v-model="filtro.curso_id" :options="cat?.cursos || []" option-value="id" option-label="nombre" emit-value map-options clearable dense outlined label="Curso" style="min-width: 130px" @update:model-value="cargar" />
      </div>
      <q-inner-loading :showing="cargando" />
      <div v-if="!cargando && !alertas.length" class="subtle q-pa-md">Sin alertas con estos filtros.</div>
      <button
        v-for="a in alertas" :key="a.id" type="button" class="filaAlerta" :class="{ activa: a.id === sel?.id }"
        @click="abrir(a.id)"
      >
        <span class="avatar">{{ iniciales(a.estudiante?.nombre_completo) }}</span>
        <span class="datos">
          <b>{{ a.estudiante?.nombre_completo }}</b>
          <small>{{ a.curso?.nombre || 'sin curso' }} · {{ fechaCl(a.fecha) }} · {{ a.intervenciones_count }} {{ a.intervenciones_count === 1 ? 'intervención' : 'intervenciones' }}</small>
        </span>
        <span class="badges">
          <span class="badge" :class="CLASE_TIPO_ALERTA[a.tipo]">{{ etiqueta(cat?.tipos_alerta, a.tipo) }}</span>
          <span class="badge" :class="CLASE_ESTADO_ALERTA[a.estado]">{{ etiqueta(cat?.estados_alerta, a.estado) }}</span>
        </span>
      </button>
      <div class="aviso q-mt-md">
        <q-icon name="fas fa-lock" class="q-mr-xs" />
        Información confidencial. Las alertas de riesgo se articulan con la derivación a la red de salud
        (Estrategia de Salud Mental MINEDUC-MINSAL).
      </div>
    </div>

    <!-- Ficha -->
    <div class="card ficha">
      <q-inner-loading :showing="cargandoSel" />
      <div v-if="!sel" class="subtle vacio">
        <q-icon name="fas fa-user-shield" size="28px" class="q-mb-sm" />
        <div>Elija una alerta para ver su ficha de seguimiento.</div>
      </div>
      <template v-else>
        <div class="fichaHead">
          <span class="avatar grande">{{ iniciales(sel.estudiante.nombre_completo) }}</span>
          <div class="col">
            <router-link :to="`/emocional/${sel.estudiante_id}`" class="nombre">{{ sel.estudiante.nombre_completo }}</router-link>
            <div class="subtle">{{ sel.curso?.nombre || 'Sin curso' }} · desde {{ fechaCl(sel.fecha) }}</div>
          </div>
          <MenuAcciones :acciones="[
            { label: 'Editar alerta', icon: 'fas fa-pen', permiso: 'emocional.editar', click: () => abrirForm(sel) },
            { label: 'Ficha socioemocional', icon: 'fas fa-id-card', to: `/emocional/${sel.estudiante_id}` },
            { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'emocional.eliminar', click: eliminarAlerta },
          ]" />
        </div>

        <div class="datosGrid">
          <div><small>Tipo de alerta</small><span class="badge" :class="CLASE_TIPO_ALERTA[sel.tipo]">{{ etiqueta(cat?.tipos_alerta, sel.tipo) }}</span></div>
          <div><small>Estado</small><span class="badge" :class="CLASE_ESTADO_ALERTA[sel.estado]">{{ etiqueta(cat?.estados_alerta, sel.estado) }}</span></div>
          <div><small>Intensidad</small>{{ etiqueta(cat?.intensidades, sel.intensidad) }}</div>
          <div v-if="sel.fase"><small>Fase protocolo DEC</small>{{ etiqueta(cat?.fases, sel.fase) }}</div>
          <div><small>Responsable</small>{{ sel.responsable?.name || '—' }}{{ sel.equipo ? ` · ${sel.equipo}` : '' }}</div>
          <div><small>Reportado por</small>{{ sel.reportado_por?.name || '—' }}</div>
          <div><small>Familia contactada</small>{{ sel.familia_contactada ? 'Sí' : 'No' }}</div>
          <div><small>Próxima revisión</small>{{ fechaCl(sel.proxima_revision) || '—' }}</div>
          <div v-if="sel.derivacion" class="full"><small>Derivación</small>{{ sel.derivacion }}</div>
          <div v-if="sel.estado === 'cerrada'" class="full"><small>Cierre</small>{{ fechaCl(sel.fecha_cierre) }} · {{ sel.motivo_cierre }}</div>
          <div class="full"><small>Situación</small>{{ sel.descripcion }}<span v-if="sel.lugar" class="subtle"> · {{ sel.lugar }}</span></div>
        </div>

        <div class="cardHead q-mt-md">
          <h3>Registro de intervenciones</h3>
        </div>
        <div v-if="$auth.hasPermission('emocional.editar')" class="nuevaInt">
          <div class="row q-col-gutter-sm">
            <q-input v-model="interv.fecha" type="date" dense outlined class="col-12 col-sm-4" label="Fecha" stack-label />
            <q-select v-model="interv.tipo" :options="cat?.tipos_intervencion || []" emit-value map-options dense outlined class="col-12 col-sm-8" label="Tipo" />
            <q-input v-model="interv.descripcion" type="textarea" autogrow dense outlined class="col-12" label="Qué se hizo" />
            <q-input v-model="interv.acuerdos" dense outlined class="col-12" label="Acuerdos (opcional)" />
          </div>
          <div class="text-right q-mt-sm">
            <q-btn no-caps unelevated dense class="btnPrimary q-px-md" icon="fas fa-plus" label="Registrar intervención" :loading="guardandoInt" :disable="!interv.descripcion" @click="registrarIntervencion" />
          </div>
        </div>
        <div v-for="i in sel.intervenciones" :key="i.id" class="interv">
          <div class="row items-center no-wrap">
            <b>{{ fechaCl(i.fecha) }}</b><span class="q-mx-xs">—</span><span class="badge violeta">{{ etiqueta(cat?.tipos_intervencion, i.tipo) }}</span>
            <q-space />
            <q-btn v-if="$auth.hasPermission('emocional.editar')" flat round dense size="xs" icon="far fa-trash-can" color="grey-7" @click="borrarIntervencion(i)" />
          </div>
          <div>{{ i.descripcion }}</div>
          <div v-if="i.acuerdos" class="subtle">Acuerdos: {{ i.acuerdos }}</div>
          <div class="subtle">{{ i.autor?.name }}</div>
        </div>
        <div v-if="sel.proxima_revision && sel.estado !== 'cerrada'" class="interv proxima">
          <b>Próxima</b> — {{ fechaCl(sel.proxima_revision) }} · evaluación de evolución
        </div>
        <div v-if="!sel.intervenciones.length" class="subtle">Sin intervenciones registradas.</div>
      </template>
    </div>

    <EeAlertaForm ref="formRef" @guardada="alGuardar" />
  </div>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { mensajeError } from '../../composables/useCrud'
import { fechaCl, iso } from '../../composables/fechas'
import { useCatalogosEmocional, etiqueta, CLASE_ESTADO_ALERTA, CLASE_TIPO_ALERTA } from '../../composables/emocional'
import MenuAcciones from '../MenuAcciones.vue'
import EeAlertaForm from './EeAlertaForm.vue'

const { proxy } = getCurrentInstance()
const route = useRoute()
const cat = useCatalogosEmocional()

const alertas = ref([])
const sel = ref(null)
const cargando = ref(false)
const cargandoSel = ref(false)
const guardandoInt = ref(false)
const filtro = ref({ estado: 'abiertas', tipo: null, curso_id: null })
const interv = ref({})
const formRef = ref(null)

const opcionesEstado = computed(() => [{ value: 'abiertas', label: 'Abiertas' }, { value: '', label: 'Todas' }, ...(cat.value?.estados_alerta || [])])
const iniciales = n => (n || '').split(/[ ,]+/).filter(Boolean).slice(0, 2).map(p => p[0]).join('')
const limpiarInterv = () => { interv.value = { fecha: iso(new Date()), tipo: 'sesion_individual', descripcion: '', acuerdos: '' } }

async function cargar () {
  cargando.value = true
  try {
    const { data } = await proxy.$axios.get('emocional/alertas', { params: { estado: filtro.value.estado || undefined, tipo: filtro.value.tipo || undefined, curso_id: filtro.value.curso_id || undefined } })
    alertas.value = data
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    cargando.value = false
  }
}

async function abrir (id) {
  cargandoSel.value = true
  limpiarInterv()
  try {
    sel.value = (await proxy.$axios.get(`emocional/alertas/${id}`)).data
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    cargandoSel.value = false
  }
}

function abrirForm (alerta = null) {
  formRef.value.abrir(alerta)
}

function alGuardar (alerta) {
  sel.value = alerta
  cargar()
}

async function registrarIntervencion () {
  guardandoInt.value = true
  try {
    sel.value = (await proxy.$axios.post(`emocional/alertas/${sel.value.id}/intervenciones`, interv.value)).data
    proxy.$alert.success('Intervención registrada')
    limpiarInterv()
    cargar()
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    guardandoInt.value = false
  }
}

function borrarIntervencion (i) {
  proxy.$alert.confirm('¿Eliminar esta intervención?').onOk(async () => {
    try {
      sel.value = (await proxy.$axios.delete(`emocional/intervenciones/${i.id}`)).data
      cargar()
    } catch (e) {
      proxy.$alert.error(mensajeError(e))
    }
  })
}

function eliminarAlerta () {
  proxy.$alert.confirm(`¿Eliminar el seguimiento de ${sel.value.estudiante.nombre_completo}? También se elimina su bitácora.`).onOk(async () => {
    try {
      await proxy.$axios.delete(`emocional/alertas/${sel.value.id}`)
      proxy.$alert.success('Seguimiento eliminado')
      sel.value = null
      cargar()
    } catch (e) {
      proxy.$alert.error(mensajeError(e))
    }
  })
}

onMounted(async () => {
  await cargar()
  const inicial = Number(route.query.alerta) || alertas.value[0]?.id
  if (inicial) abrir(inicial)
})

defineExpose({ cargar })
</script>

<style scoped>
.segGrid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 14px; align-items: start }
.filtros { display: flex; gap: 8px; flex-wrap: wrap }
.filaAlerta { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; font: inherit; color: inherit; background: #fff; border: 1px solid #e3eaf1; border-radius: 12px; padding: 9px 10px; margin-bottom: 7px; cursor: pointer }
.filaAlerta:hover { background: #f8fafc }
.filaAlerta.activa { border-color: #7c3aed; box-shadow: 0 0 0 2px #7c3aed22 }
.avatar { flex: none; width: 34px; height: 34px; border-radius: 50%; background: #ede9fe; color: #6d28d9; display: grid; place-items: center; font-size: 12px; font-weight: 900 }
.avatar.grande { width: 52px; height: 52px; font-size: 16px }
.datos { flex: 1; min-width: 0; display: flex; flex-direction: column }
.datos b { font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.datos small { color: #6d8090; font-size: 11px }
.badges { display: flex; flex-direction: column; gap: 4px; align-items: flex-end }
.aviso { font-size: 12px; background: #eff6ff; border: 1px solid #cfe0f5; color: #1e4e7d; border-radius: 12px; padding: 10px 12px }
.ficha { position: sticky; top: 12px; min-height: 300px }
.vacio { text-align: center; padding: 60px 10px; color: #7c3aed }
.fichaHead { display: flex; align-items: center; gap: 12px; margin-bottom: 12px }
.nombre { font-size: 16px; font-weight: 800; color: #0b2440; text-decoration: none }
.nombre:hover { text-decoration: underline }
.datosGrid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; font-size: 13px }
.datosGrid small { display: block; font-size: 10.5px; text-transform: uppercase; letter-spacing: .05em; color: #6d8090; font-weight: 800; margin-bottom: 2px }
.datosGrid .full { grid-column: 1 / -1 }
.nuevaInt { background: #faf7ff; border: 1px dashed #d8cdf7; border-radius: 12px; padding: 10px; margin-bottom: 10px }
.interv { padding: 10px 12px; background: #f8fafc; border-radius: 10px; margin-bottom: 8px; font-size: 13px; line-height: 1.5 }
.interv.proxima { background: #f5f3ff; color: #4c3a8a }
@media (max-width: 1100px) { .segGrid { grid-template-columns: 1fr } .ficha { position: static } }
</style>
