<template>
  <q-page class="page">
    <q-btn flat dense no-caps icon="fas fa-arrow-left" label="Casos PIE" class="volver" to="/pie" />

    <div v-if="loading && !caso" class="q-mt-md">
      <q-skeleton height="120px" style="border-radius: 18px" class="q-mb-md" />
      <q-skeleton height="320px" style="border-radius: 18px" />
    </div>

    <template v-else-if="caso">
      <!-- Encabezado del caso -->
      <div class="casoHead">
        <div class="avatar" :class="caso.tipo_nee">{{ iniciales(caso.estudiante) }}</div>
        <div class="col">
          <div class="eyebrow">Programa de Integración Escolar · {{ caso.anio }}</div>
          <h1>{{ caso.estudiante.nombres }} {{ caso.estudiante.apellido_paterno }} {{ caso.estudiante.apellido_materno }}</h1>
          <div class="chips">
            <span class="pill" :class="ESTADOS_PIE[caso.estado].pill">{{ ESTADOS_PIE[caso.estado].label }}</span>
            <span class="badge" :class="TIPOS_NEE[caso.tipo_nee].badge">{{ TIPOS_NEE[caso.tipo_nee].label }}</span>
            <span class="badge">{{ caso.curso?.nombre || 'Sin matrícula' }}</span>
            <span class="subtle">{{ formatoRun(caso.estudiante.run) }}<template v-if="edad"> · {{ edad }} años</template></span>
          </div>
        </div>
        <div class="row q-gutter-sm">
          <q-btn v-if="$auth.hasPermission('pie.editar')" no-caps unelevated class="btnPrimary" icon="fas fa-pen" label="Editar caso" @click="formAbierto = true" />
          <MenuAcciones :acciones="[
            { label: 'Ver ficha del estudiante', icon: 'fas fa-user-graduate', permiso: 'estudiantes.ver', to: { path: '/estudiantes', query: { curso: caso.curso?.id, anio: caso.anio } } },
            { label: 'Eliminar caso', icon: 'far fa-trash-can', peligro: true, permiso: 'pie.eliminar', click: eliminarCaso },
          ]" />
        </div>
      </div>

      <div class="workbenchTabs">
        <button v-for="t in tabs" :key="t.key" class="workbenchTab" :class="{ active: tab === t.key }" @click="tab = t.key">
          {{ t.label }}<span v-if="t.n !== undefined" class="cuenta">{{ t.n }}</span>
        </button>
      </div>

      <!-- 1. Ficha y diagnóstico -->
      <div v-if="tab === 'ficha'" class="grid2">
        <div class="infoCard">
          <h3><q-icon name="fas fa-stethoscope" /> Diagnóstico</h3>
          <div class="infoRows">
            <div><b>Diagnóstico</b><span>{{ caso.diagnostico }}</span></div>
            <div v-if="caso.diagnostico_detalle"><b>Detalle</b><span>{{ caso.diagnostico_detalle }}</span></div>
            <div><b>Tipo de NEE</b><span>{{ TIPOS_NEE[caso.tipo_nee].label }}</span></div>
            <div><b>Ingreso al programa</b><span>{{ fecha(caso.fecha_ingreso) }}</span></div>
            <div><b>Evaluación diagnóstica</b><span>{{ fecha(caso.fecha_evaluacion) }}</span></div>
            <div>
              <b>Próxima reevaluación</b>
              <span>{{ fecha(caso.fecha_reevaluacion) }}
                <span v-if="avisoReevaluacion" class="badge q-ml-xs" :class="avisoReevaluacion.clase">{{ avisoReevaluacion.texto }}</span>
              </span>
            </div>
            <div><b>Responsable</b><span>{{ caso.responsable?.name || 'Sin asignar' }}</span></div>
            <div><b>Horas de apoyo</b><span>{{ caso.horas_semanales != null ? `${caso.horas_semanales} h semanales` : '—' }}</span></div>
            <template v-if="caso.estado === 'egresado'">
              <div><b>Egreso</b><span>{{ fecha(caso.fecha_egreso) }}</span></div>
              <div><b>Motivo</b><span>{{ caso.motivo_egreso }}</span></div>
            </template>
          </div>
        </div>

        <div class="infoCard">
          <h3><q-icon name="far fa-id-card" /> Identificación del estudiante</h3>
          <div class="infoRows">
            <div><b>Nombre</b><span>{{ caso.estudiante.nombre_completo }}</span></div>
            <div><b>RUN</b><span>{{ formatoRun(caso.estudiante.run) }}</span></div>
            <div><b>Nacimiento</b><span>{{ fecha(caso.estudiante.fecha_nacimiento) }}<template v-if="edad"> ({{ edad }} años)</template></span></div>
            <div><b>Sexo</b><span>{{ SEXOS[caso.estudiante.sexo] || '—' }}</span></div>
            <div><b>Curso {{ caso.anio }}</b><span>{{ caso.curso?.nombre || 'Sin matrícula activa' }}</span></div>
            <div><b>Dirección</b><span>{{ caso.estudiante.direccion || '—' }}</span></div>
          </div>
        </div>

        <div class="infoCard">
          <h3><q-icon name="fas fa-people-group" /> Equipo multiprofesional</h3>
          <div v-if="!caso.equipo?.length" class="subtle">Sin profesionales registrados.</div>
          <div v-for="(p, i) in caso.equipo" :key="i" class="profesional">
            <div class="rowIcon"><q-icon name="fas fa-user-doctor" size="14px" /></div>
            <div class="rowText"><b>{{ p.nombre }}</b><small>{{ p.especialidad }}</small></div>
          </div>
        </div>

        <div class="infoCard">
          <h3><q-icon name="fas fa-clock-rotate-left" /> Trayectoria en el PIE</h3>
          <div class="profesional actual">
            <div class="rowIcon">{{ caso.anio }}</div>
            <div class="rowText"><b>{{ caso.diagnostico }}</b><small>{{ ESTADOS_PIE[caso.estado].label }} · año actual</small></div>
          </div>
          <router-link v-for="h in caso.historial" :key="h.id" :to="`/pie/${h.id}`" class="profesional enlace">
            <div class="rowIcon">{{ h.anio }}</div>
            <div class="rowText"><b>{{ h.diagnostico }}</b><small>{{ ESTADOS_PIE[h.estado].label }}</small></div>
          </router-link>
          <div v-if="!caso.historial.length" class="subtle q-mt-sm">Primer año en el programa.</div>
        </div>

        <div v-if="caso.observaciones" class="infoCard full">
          <h3><q-icon name="far fa-note-sticky" /> Observaciones</h3>
          <p class="texto">{{ caso.observaciones }}</p>
        </div>
      </div>

      <!-- 2. Plan de apoyo -->
      <div v-else-if="tab === 'plan'" class="workbenchPanel">
        <h3>Plan de apoyo</h3>
        <p>Apoyos y adecuaciones del estudiante (Decreto 83: DUA, adecuaciones de acceso y, cuando corresponde, PACI).</p>
        <div class="apoyos">
          <span v-for="a in cat.apoyos" :key="a" class="apoyo" :class="{ on: caso.apoyos?.includes(a) }">
            <q-icon :name="caso.apoyos?.includes(a) ? 'fas fa-circle-check' : 'far fa-circle'" size="13px" /> {{ a }}
          </span>
        </div>
        <div class="grid3 q-mt-md">
          <div v-for="b in bloquesPlan" :key="b.titulo" class="detailItem">
            <b>{{ b.titulo }}</b>
            <p>{{ caso[b.campo] || 'Sin registro.' }}</p>
          </div>
        </div>
      </div>

      <!-- 3. Expediente: documentos e imágenes -->
      <div v-else-if="tab === 'expediente'" class="workbenchPanel">
        <div class="row items-start justify-between q-gutter-sm">
          <div>
            <h3>Expediente del estudiante</h3>
            <p class="q-mb-none">FUDEI, informes de evaluación, certificados, autorizaciones, PACI y evidencias. Los archivos son privados: solo los ve quien tiene acceso al PIE.</p>
          </div>
          <q-btn v-if="$auth.hasPermission('pie.editar')" no-caps unelevated class="btnPrimary" icon="fas fa-upload" label="Subir documento o imagen" @click="abrirSubida" />
        </div>

        <div class="filtrosDoc q-mt-md">
          <q-btn-toggle v-model="filtroDoc" no-caps unelevated dense toggle-color="primary" color="white" text-color="grey-8" class="toggle"
            :options="[{ label: `Todos (${caso.documentos.length})`, value: 'todos' }, { label: `Imágenes (${imagenes.length})`, value: 'imagenes' }, { label: `Documentos (${caso.documentos.length - imagenes.length})`, value: 'documentos' }]" />
        </div>

        <div v-if="!documentosFiltrados.length" class="vacio">
          <q-icon name="far fa-folder-open" size="30px" />
          <b>Sin archivos en el expediente.</b>
        </div>
        <div v-else class="docGrid">
          <div v-for="d in documentosFiltrados" :key="d.id" class="doc">
            <div class="docThumb" :class="{ imagen: d.es_imagen }" @click="verDocumento(d)">
              <img v-if="d.es_imagen && miniaturas[d.id]" :src="miniaturas[d.id]" :alt="d.titulo">
              <q-spinner v-else-if="d.es_imagen" color="primary" />
              <q-icon v-else :name="iconoArchivo(d)" />
            </div>
            <div class="docBody">
              <span class="badge blue">{{ d.tipo }}</span>
              <b class="ellipsis-2-lines">{{ d.titulo }}</b>
              <small class="subtle">{{ fecha(d.fecha) }} · {{ formatoTamano(d.archivo_tamano) }}<template v-if="d.autor"> · {{ d.autor.name }}</template></small>
              <small v-if="d.descripcion" class="desc">{{ d.descripcion }}</small>
            </div>
            <div class="docAcciones">
              <q-btn flat dense round size="sm" icon="far fa-eye" @click="verDocumento(d)"><q-tooltip>Ver</q-tooltip></q-btn>
              <q-btn flat dense round size="sm" icon="fas fa-download" @click="descargarDocumento(proxy, d)"><q-tooltip>Descargar</q-tooltip></q-btn>
              <q-btn v-if="$auth.hasPermission('pie.editar')" flat dense round size="sm" icon="far fa-trash-can" color="negative" @click="eliminarDocumento(d)"><q-tooltip>Eliminar</q-tooltip></q-btn>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Bitácora -->
      <div v-else-if="tab === 'bitacora'" class="workbenchPanel">
        <div class="row items-start justify-between q-gutter-sm">
          <div>
            <h3>Bitácora de apoyos</h3>
            <p class="q-mb-none">Sesiones en aula de recursos, co-docencia, observaciones, entrevistas familiares y reuniones de equipo.</p>
          </div>
          <q-btn v-if="$auth.hasPermission('pie.editar')" no-caps unelevated class="btnPrimary" icon="fas fa-plus" label="Registrar" @click="abrirSeguimiento()" />
        </div>
        <div class="kpiLine resumen">
          <div class="kpiMini"><b>{{ caso.seguimientos.length }}</b><small>registros</small></div>
          <div class="kpiMini"><b>{{ sesionesRecursos }}</b><small>sesiones en aula de recursos</small></div>
          <div class="kpiMini"><b>{{ horasRegistradas }}</b><small>horas de apoyo registradas</small></div>
          <div class="kpiMini"><b>{{ fecha(caso.seguimientos[0]?.fecha) }}</b><small>último registro</small></div>
        </div>
        <PieBitacora :items="caso.seguimientos" @editar="abrirSeguimiento" @eliminar="eliminarSeguimiento" />
      </div>

      <!-- 5. Familia -->
      <div v-else-if="tab === 'familia'" class="grid2">
        <div class="infoCard">
          <h3><q-icon name="fas fa-house-chimney-user" /> Apoderado</h3>
          <div class="infoRows">
            <div><b>Nombre</b><span>{{ caso.estudiante.apoderado_nombre || '—' }}</span></div>
            <div><b>RUN</b><span>{{ formatoRun(caso.estudiante.apoderado_run) }}</span></div>
            <div><b>Teléfono</b><span>{{ caso.estudiante.apoderado_telefono || '—' }}</span></div>
            <div><b>Correo</b><span>{{ caso.estudiante.apoderado_email || '—' }}</span></div>
          </div>
        </div>
        <div class="infoCard">
          <h3><q-icon name="far fa-handshake" /> Acuerdos con la familia</h3>
          <p class="texto">{{ caso.acuerdos_familia || 'Sin acuerdos registrados.' }}</p>
        </div>
        <div class="infoCard full">
          <div class="row items-center justify-between q-mb-sm">
            <h3 class="q-mb-none"><q-icon name="far fa-comments" /> Entrevistas familiares</h3>
            <q-btn v-if="$auth.hasPermission('pie.editar')" flat dense no-caps color="primary" icon="fas fa-plus" label="Registrar entrevista"
              @click="abrirSeguimiento(null, 'Entrevista familiar')" />
          </div>
          <PieBitacora :items="entrevistas" @editar="abrirSeguimiento" @eliminar="eliminarSeguimiento" />
        </div>
      </div>
    </template>

    <PieCasoForm v-if="caso" v-model="formAbierto" :caso="caso" :anio="caso.anio" @guardado="c => { caso = c; cargarMiniaturas() }" />

    <!-- Subir documento -->
    <q-dialog v-model="subida.abierto" persistent>
      <q-card class="formCard">
        <q-form @submit.prevent="subirDocumento">
          <q-card-section class="formTitle">Subir al expediente</q-card-section>
          <q-card-section class="formGrid">
            <q-file
              v-model="subida.archivo" outlined dense class="full" label="Archivo (PDF, Word, Excel o imagen · máx. 15 MB)"
              :accept="ACEPTA" max-file-size="15728640" :rules="[v => !!v || 'Elija un archivo']" hide-bottom-space
              @rejected="() => $alert.error('El archivo supera 15 MB o su formato no está permitido')"
              @update:model-value="sugerirTipo"
            >
              <template #prepend><q-icon name="fas fa-paperclip" size="14px" /></template>
            </q-file>
            <q-select v-model="subida.tipo" :options="cat.tipos_documento" outlined dense class="full" label="Tipo de documento" />
            <q-input v-model="subida.titulo" outlined dense class="full" label="Título" hint="Si lo deja vacío se usa el nombre del archivo" />
            <q-input v-model="subida.fecha" type="date" stack-label outlined dense label="Fecha del documento" />
            <q-input v-model="subida.descripcion" type="textarea" autogrow outlined dense class="full" label="Descripción" />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Subir" icon="fas fa-upload" class="btnPrimary" :loading="subida.guardando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Registro de bitácora -->
    <q-dialog v-model="seg.abierto">
      <q-card class="formCard">
        <q-form @submit.prevent="guardarSeguimiento">
          <q-card-section class="formTitle">{{ seg.form.id ? 'Editar registro' : 'Nuevo registro de bitácora' }}</q-card-section>
          <q-card-section class="formGrid">
            <q-input v-model="seg.form.fecha" type="date" stack-label outlined dense label="Fecha" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-select v-model="seg.form.tipo" :options="cat.tipos_seguimiento" outlined dense label="Tipo" />
            <q-select v-model="seg.form.area" :options="cat.areas" clearable outlined dense label="Área de intervención" />
            <q-input v-model.number="seg.form.duracion_minutos" type="number" min="1" outlined dense label="Duración (minutos)" />
            <q-input v-model="seg.form.descripcion" type="textarea" autogrow outlined dense class="full" label="Descripción" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input v-model="seg.form.acuerdos" type="textarea" autogrow outlined dense class="full" label="Acuerdos / próximos pasos" />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="seg.guardando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Visor de imágenes -->
    <q-dialog v-model="visor.abierto" maximized transition-show="fade" transition-hide="fade">
      <div class="visor" @click.self="visor.abierto = false">
        <q-btn flat round icon="fas fa-xmark" color="white" class="cerrar" @click="visor.abierto = false" />
        <q-btn v-if="imagenes.length > 1" flat round icon="fas fa-chevron-left" color="white" class="nav izq" @click="moverVisor(-1)" />
        <img v-if="visorDoc && miniaturas[visorDoc.id]" :src="miniaturas[visorDoc.id]" :alt="visorDoc.titulo">
        <q-btn v-if="imagenes.length > 1" flat round icon="fas fa-chevron-right" color="white" class="nav der" @click="moverVisor(1)" />
        <div v-if="visorDoc" class="pie">
          <b>{{ visorDoc.titulo }}</b> · {{ visorDoc.tipo }} · {{ fecha(visorDoc.fecha) }}
          <q-btn flat dense no-caps color="white" icon="fas fa-download" label="Descargar" class="q-ml-md" @click="descargarDocumento(proxy, visorDoc)" />
        </div>
      </div>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, getCurrentInstance } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeError } from '../../../composables/useCrud'
import { formatoRun } from '../../../composables/formato'
import { formatoTamano } from '../../../composables/biblioteca'
import {
  ESTADOS_PIE, TIPOS_NEE, iniciales, fecha, diasHasta, useCatalogosPie, urlArchivo, descargarDocumento, abrirDocumento,
} from '../../../composables/pie'
import PieCasoForm from '../../../components/PieCasoForm.vue'
import PieBitacora from '../../../components/PieBitacora.vue'
import MenuAcciones from '../../../components/MenuAcciones.vue'

definePage({ meta: { title: 'PIE / Inclusión', permiso: 'pie.ver' } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()
const cat = useCatalogosPie(proxy)

const SEXOS = { F: 'Femenino', M: 'Masculino', X: 'Otro / no informa' }
const ACEPTA = '.pdf,.doc,.docx,.odt,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.heic'
const hoy = () => new Date().toISOString().slice(0, 10)

const caso = ref(null)
const loading = ref(false)
const tab = ref(route.query.tab || 'ficha')
const formAbierto = ref(false)

const tabs = computed(() => [
  { key: 'ficha', label: '1. Ficha y diagnóstico' },
  { key: 'plan', label: '2. Plan de apoyo' },
  { key: 'expediente', label: '3. Expediente', n: caso.value?.documentos.length },
  { key: 'bitacora', label: '4. Bitácora', n: caso.value?.seguimientos.length },
  { key: 'familia', label: '5. Familia' },
])

watch(tab, t => router.replace({ query: { ...route.query, tab: t } }))

const bloquesPlan = [
  { titulo: 'Fortalezas e intereses', campo: 'fortalezas' },
  { titulo: 'Barreras para el aprendizaje y la participación', campo: 'barreras' },
  { titulo: 'Necesidades de apoyo y adecuaciones', campo: 'necesidades_apoyo' },
]

const edad = computed(() => {
  const n = caso.value?.estudiante.fecha_nacimiento
  if (!n) return null
  const nac = new Date(`${n}T00:00:00`)
  const ahora = new Date()
  return ahora.getFullYear() - nac.getFullYear() - (ahora < new Date(ahora.getFullYear(), nac.getMonth(), nac.getDate()) ? 1 : 0)
})

const avisoReevaluacion = computed(() => {
  if (!caso.value || caso.value.estado === 'egresado') return null
  const d = diasHasta(caso.value.fecha_reevaluacion)
  if (d === null || d > 60) return null
  return d < 0 ? { texto: 'vencida', clase: 'red' } : { texto: `en ${d} días`, clase: 'amber' }
})

function cargar () {
  loading.value = true
  return proxy.$axios.get(`pie/casos/${route.params.id}`)
    .then(res => {
      caso.value = res.data
      cargarMiniaturas()
    })
    .catch(err => {
      proxy.$alert.error(mensajeError(err))
      if (err?.response?.status === 404) router.replace('/pie')
    })
    .finally(() => { loading.value = false })
}

watch(() => route.params.id, id => { if (id) { liberarMiniaturas(); cargar() } })

function eliminarCaso () {
  proxy.$alert.confirm(`¿Eliminar el caso PIE ${caso.value.anio} de ${caso.value.estudiante.nombres}? Se eliminan también su expediente y bitácora.`).onOk(() => {
    proxy.$axios.delete(`pie/casos/${caso.value.id}`)
      .then(res => {
        proxy.$alert.success(res.data.message)
        router.replace('/pie')
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

// ── Expediente ──
const miniaturas = reactive({})
const filtroDoc = ref('todos')
const imagenes = computed(() => (caso.value?.documentos || []).filter(d => d.es_imagen))
const documentosFiltrados = computed(() => (caso.value?.documentos || []).filter(d =>
  filtroDoc.value === 'todos' || (filtroDoc.value === 'imagenes') === d.es_imagen))

function cargarMiniaturas () {
  for (const d of imagenes.value) {
    if (miniaturas[d.id]) continue
    urlArchivo(proxy, d).then(url => { miniaturas[d.id] = url }).catch(() => {})
  }
}

function liberarMiniaturas () {
  for (const id of Object.keys(miniaturas)) {
    URL.revokeObjectURL(miniaturas[id])
    delete miniaturas[id]
  }
}

onBeforeUnmount(liberarMiniaturas)

function iconoArchivo (d) {
  const ext = (d.archivo_nombre.split('.').pop() || '').toLowerCase()
  if (ext === 'pdf') return 'far fa-file-pdf'
  if (['doc', 'docx', 'odt'].includes(ext)) return 'far fa-file-word'
  if (['xls', 'xlsx'].includes(ext)) return 'far fa-file-excel'
  return 'far fa-file'
}

const subida = reactive({ abierto: false, guardando: false, archivo: null, tipo: null, titulo: '', fecha: '', descripcion: '' })

function abrirSubida () {
  Object.assign(subida, { abierto: true, archivo: null, tipo: cat.value.tipos_documento[0], titulo: '', fecha: hoy(), descripcion: '' })
}

function sugerirTipo (archivo) {
  if (archivo?.type?.startsWith('image/') && subida.tipo === cat.value.tipos_documento[0]) subida.tipo = 'Evidencia / fotografía'
}

function subirDocumento () {
  const datos = new FormData()
  datos.append('archivo', subida.archivo)
  datos.append('tipo', subida.tipo)
  if (subida.titulo) datos.append('titulo', subida.titulo)
  if (subida.fecha) datos.append('fecha', subida.fecha)
  if (subida.descripcion) datos.append('descripcion', subida.descripcion)

  subida.guardando = true
  proxy.$axios.post(`pie/casos/${caso.value.id}/documentos`, datos)
    .then(res => {
      caso.value.documentos.unshift(res.data)
      cargarMiniaturas()
      subida.abierto = false
      proxy.$alert.success('Archivo agregado al expediente', res.data.titulo)
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { subida.guardando = false })
}

function eliminarDocumento (d) {
  proxy.$alert.confirm(`¿Eliminar "${d.titulo}" del expediente?`).onOk(() => {
    proxy.$axios.delete(`pie/documentos/${d.id}`)
      .then(res => {
        caso.value.documentos = caso.value.documentos.filter(x => x.id !== d.id)
        proxy.$alert.success(res.data.message)
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

// Imágenes en el visor; PDF y otros en una pestaña nueva
const visor = reactive({ abierto: false, indice: 0 })
const visorDoc = computed(() => imagenes.value[visor.indice])

function verDocumento (d) {
  if (!d.es_imagen) return abrirDocumento(proxy, d)
  visor.indice = imagenes.value.findIndex(x => x.id === d.id)
  visor.abierto = true
}

function moverVisor (paso) {
  visor.indice = (visor.indice + paso + imagenes.value.length) % imagenes.value.length
}

// ── Bitácora ──
const entrevistas = computed(() => (caso.value?.seguimientos || []).filter(s => s.tipo === 'Entrevista familiar'))
const sesionesRecursos = computed(() => (caso.value?.seguimientos || []).filter(s => s.tipo === 'Sesión en aula de recursos').length)
const horasRegistradas = computed(() => {
  const min = (caso.value?.seguimientos || []).reduce((t, s) => t + (s.duracion_minutos || 0), 0)
  return (Math.round(min / 6) / 10).toString().replace('.', ',')
})

const seg = reactive({ abierto: false, guardando: false, form: {} })

function abrirSeguimiento (s = null, tipo = null) {
  seg.form = s
    ? { ...s }
    : { fecha: hoy(), tipo: tipo || cat.value.tipos_seguimiento[0], area: null, duracion_minutos: null, descripcion: '', acuerdos: '' }
  seg.abierto = true
}

function guardarSeguimiento () {
  const { id, fecha: f, tipo, area, duracion_minutos: duracion, descripcion, acuerdos } = seg.form
  const datos = { fecha: f, tipo, area: area || null, duracion_minutos: duracion || null, descripcion, acuerdos: acuerdos || null }
  seg.guardando = true
  const req = id ? proxy.$axios.put(`pie/seguimientos/${id}`, datos) : proxy.$axios.post(`pie/casos/${caso.value.id}/seguimientos`, datos)
  req.then(res => {
    const lista = caso.value.seguimientos.filter(x => x.id !== res.data.id)
    lista.push(res.data)
    caso.value.seguimientos = lista.sort((a, b) => b.fecha.localeCompare(a.fecha) || b.id - a.id)
    seg.abierto = false
    proxy.$alert.success(id ? 'Registro actualizado' : 'Registro agregado a la bitácora')
  })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { seg.guardando = false })
}

function eliminarSeguimiento (s) {
  proxy.$alert.confirm(`¿Eliminar el registro "${s.tipo}" del ${fecha(s.fecha)}?`).onOk(() => {
    proxy.$axios.delete(`pie/seguimientos/${s.id}`)
      .then(res => {
        caso.value.seguimientos = caso.value.seguimientos.filter(x => x.id !== s.id)
        proxy.$alert.success(res.data.message)
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

onMounted(cargar)
</script>

<style scoped>
.volver { color: #0f4f85; margin-bottom: 8px }
.casoHead {
  display: flex; gap: 16px; align-items: center; background: #fff; border: 1px solid #d9e3ec; border-radius: 18px;
  padding: 18px; border-left: 6px solid var(--role, #16866b); flex-wrap: wrap;
}
.casoHead h1 { font-size: 26px; font-weight: 800; letter-spacing: -.03em; margin: 2px 0 6px; line-height: 1.15 }
.avatar { width: 68px; height: 68px; border-radius: 20px; background: #eaf8f3; color: #16634f; display: grid; place-items: center; font-size: 24px; font-weight: 900; flex: none }
.avatar.permanente { background: #f1edff; color: #5a3fb0 }
.chips { display: flex; gap: 6px; align-items: center; flex-wrap: wrap }

.workbenchTabs { display: flex; gap: 7px; flex-wrap: wrap; margin: 14px 0 }
.workbenchTab {
  border: 1px solid #cfdce7; background: #fff; color: #3a5871; border-radius: 999px; padding: 8px 13px;
  font-size: 12px; font-weight: 800; cursor: pointer; display: inline-flex; gap: 6px; align-items: center;
}
.workbenchTab.active { background: var(--role, #16866b); color: #fff; border-color: var(--role, #16866b); box-shadow: 0 6px 16px #16866b33 }
.cuenta { background: #ffffff33; border-radius: 99px; padding: 0 7px; font-size: 11px }
.workbenchTab:not(.active) .cuenta { background: #edf3f7 }
.workbenchPanel { border: 1px solid #d9e3ec; background: linear-gradient(180deg, #fff, #fbfdff); border-radius: 18px; padding: 18px }
.workbenchPanel h3 { margin: 0 0 5px; font-size: 16px; font-weight: 800 }
.workbenchPanel p { color: #6d8090; font-size: 12px; line-height: 1.55 }

.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px }
.grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px }
.full { grid-column: 1 / -1 }
.infoCard { border: 1px solid #dbe5ed; border-radius: 14px; padding: 14px; background: #fff }
.infoCard h3 { font-size: 14px; font-weight: 800; margin: 0 0 10px; color: #275b82; display: flex; gap: 7px; align-items: center }
.infoRows > div { display: grid; grid-template-columns: 170px 1fr; gap: 8px; padding: 7px 0; border-bottom: 1px dashed #e3eaf0; font-size: 13px }
.infoRows > div:last-child { border-bottom: 0 }
.infoRows b { color: #6c8091; font-weight: 700 }
.texto { margin: 0; color: #33536d; line-height: 1.55; white-space: pre-line }

.profesional { display: flex; gap: 10px; align-items: center; padding: 8px; border: 1px solid #e0e8ef; border-radius: 12px; margin-bottom: 7px; color: inherit; text-decoration: none }
.profesional.actual { border-color: var(--role, #16866b) }
.profesional.enlace:hover { border-color: #9fc3e2 }
.rowIcon { min-width: 34px; height: 34px; padding: 0 4px; border-radius: 10px; display: grid; place-items: center; background: var(--roleSoft, #e8f8f3); color: var(--role, #16866b); font-weight: 900; font-size: 11px }
.rowText b { display: block; font-size: 13px }
.rowText small { color: #6d8090; font-size: 11px }

.apoyos { display: flex; flex-wrap: wrap; gap: 8px }
.apoyo { border: 1px solid #dce5ec; border-radius: 999px; padding: 6px 12px; font-size: 12px; color: #8aa0b1; background: #fff }
.apoyo.on { border-color: #b8e2cf; background: #eaf8f1; color: #17703f; font-weight: 700 }
.detailItem { padding: 12px; border-left: 4px solid var(--role, #16866b); background: #f8fbfd; border-radius: 0 10px 10px 0 }
.detailItem b { font-size: 13px }
.detailItem p { margin: 4px 0 0; color: #5f7487; font-size: 12px; line-height: 1.5; white-space: pre-line }

.toggle { border: 1px solid #d9e3ec; border-radius: 10px; overflow: hidden }
.docGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 12px }
.doc { border: 1px solid #d9e3ec; border-radius: 15px; overflow: hidden; background: #fff; display: flex; flex-direction: column }
.docThumb { height: 140px; display: grid; place-items: center; background: linear-gradient(135deg, #eaf3fb, #f8fbfd); color: #0f4f85; font-size: 44px; cursor: pointer; overflow: hidden }
.docThumb.imagen { background: #0b2440 }
.docThumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .2s }
.docThumb:hover img { transform: scale(1.04) }
.docBody { padding: 10px 12px 4px; display: flex; flex-direction: column; gap: 4px; flex: 1 }
.docBody .badge { align-self: flex-start; max-width: 100%; overflow: hidden; text-overflow: ellipsis }
.docBody b { font-size: 13px; line-height: 1.3 }
.desc { color: #5f7487; font-size: 11px }
.docAcciones { display: flex; justify-content: flex-end; padding: 2px 6px 6px; color: #33536d }

.kpiLine.resumen { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 14px 0 }
.kpiMini { background: #fff; border: 1px solid #dbe5ec; border-radius: 12px; padding: 10px 12px }
.kpiMini b { display: block; font-size: 20px; font-weight: 800; color: #0b2440 }
.kpiMini small { color: #6d8090; font-size: 11px }

.pill { padding: 5px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; background: #edf3f7; color: #486477 }
.pill.green { background: #eaf8f1; color: #20704f }
.pill.amber { background: #fff4e2; color: #a66412 }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
.badge.amber { background: #fff4e2; border-color: #f5dcb2; color: #a66412 }
.vacio { display: grid; place-items: center; gap: 8px; padding: 30px; color: #6d8090; text-align: center }

.visor { background: #0b2440f2; display: grid; place-items: center; position: relative; width: 100%; height: 100% }
.visor img { max-width: 90vw; max-height: 82vh; border-radius: 10px; box-shadow: 0 20px 60px #0008 }
.visor .cerrar { position: absolute; top: 16px; right: 16px }
.visor .nav { position: absolute; top: 50% }
.visor .izq { left: 16px }
.visor .der { right: 16px }
.visor .pie { position: absolute; bottom: 18px; color: #fff; font-size: 13px; display: flex; align-items: center }

@media (max-width: 1000px) {
  .grid2, .grid3 { grid-template-columns: 1fr }
  .kpiLine.resumen { grid-template-columns: 1fr 1fr }
  .infoRows > div { grid-template-columns: 130px 1fr }
}
</style>
