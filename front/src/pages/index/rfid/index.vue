<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Presence Engine · RFID</h1>
        <p>El evento físico no es la asistencia oficial: el motor propone (presente / atrasado) y los casos inciertos los valida una persona autorizada.</p>
      </div>
      <div class="row q-gutter-sm">
        <q-btn
          :label="enVivo ? 'Detener tiempo real' : 'Activar tiempo real'" :icon="enVivo ? 'fas fa-stop' : 'fas fa-tower-broadcast'"
          no-caps unelevated :class="enVivo ? 'btnDetener' : 'btnPrimary'" :loading="conectando" @click="alternarVivo"
        />
        <q-btn label="Simular pasada" icon="fas fa-id-card" no-caps outline color="primary" class="btnOutline" :loading="simulando" @click="simular()" />
        <q-btn label="Simulador" icon="fas fa-up-right-from-square" no-caps flat color="primary" class="btnOutline" :href="urlSimulador" target="_blank" />
      </div>
    </div>

    <div class="estadoVivo" :class="{ on: enVivo }">
      <span class="punto" />
      <b>{{ enVivo ? 'En vivo' : 'Desconectado' }}</b>
      <span class="subtle">{{ enVivo ? 'Recibiendo las marcas de los lectores en tiempo real.' : 'Activa el tiempo real para ver a los estudiantes cruzar el portal.' }}</span>
      <q-space />
      <q-select
        v-model="cursoId" :options="cursos" option-value="id" option-label="nombre" emit-value map-options
        dense outlined label="Curso" style="min-width: 190px" bg-color="white" @update:model-value="cargar"
      />
    </div>

    <div class="portalWrap">
      <div class="card">
        <div class="portalStage">
          <div class="scanPanel" :class="ultima ? ultima.clase : ''">
            <b>{{ ultima ? ultima.titulo : 'Portal listo' }}</b>
            <small>{{ ultima ? ultima.meta : (enVivo ? 'Esperando la próxima pasada' : 'Activa el tiempo real') }}</small>
          </div>
          <div class="ground" />
          <div class="gate one"><span class="gateLabel">Portal establecimiento</span></div>
          <div class="gate two"><span class="gateLabel">Portal sala {{ nombreCurso }}</span></div>
          <div
            v-for="w in caminantes" :key="w.key" class="walker" :class="[w.clase, { salida: w.salida }]"
            @animationend="quitarCaminante(w.key)"
          >
            {{ w.iniciales }}
          </div>
        </div>

        <div class="rfidMetrics">
          <div class="rfidMetric"><small>Presentes</small><b class="ok">{{ conteo.presente }}</b></div>
          <div class="rfidMetric"><small>Atrasados</small><b class="warn">{{ conteo.atrasado }}</b></div>
          <div class="rfidMetric"><small>Sin marca</small><b class="red">{{ conteo.ausente }}</b></div>
          <div class="rfidMetric"><small>Salieron</small><b>{{ conteo.salieron }}</b></div>
        </div>
      </div>

      <div class="card">
        <div class="cardHead">
          <h3>Llegadas por tramo de 10 minutos</h3>
          <q-btn-toggle
            v-model="vistaGrafico" dense no-caps unelevated toggle-color="primary" color="grey-2" text-color="grey-9" size="sm"
            :options="[{ label: 'Gráfico', value: 'grafico' }, { label: 'Tabla', value: 'tabla' }]"
          />
        </div>
        <div class="leyenda">
          <span><i class="sw presente" />Presente</span>
          <span><i class="sw atrasado" />Atrasado</span>
          <span class="subtle">Límite {{ limite }}</span>
        </div>

        <div v-if="!tramos.length" class="subtle q-pa-md">Aún no hay entradas hoy en este curso.</div>
        <template v-else-if="vistaGrafico === 'grafico'">
          <div class="chart">
            <div v-for="t in tramos" :key="t.desde" class="col">
              <div class="pila" :style="{ height: `${(t.total / maxTramo) * 100}%` }">
                <span class="total">{{ t.total }}</span>
                <div v-if="t.atrasado" class="seg atrasado" :style="{ flexGrow: t.atrasado }" />
                <div v-if="t.presente" class="seg presente" :style="{ flexGrow: t.presente }" />
              </div>
              <q-tooltip>{{ t.desde }}–{{ t.hasta }} · {{ t.presente }} presentes · {{ t.atrasado }} atrasados</q-tooltip>
            </div>
          </div>
          <div class="ejeX">
            <span v-for="t in tramos" :key="t.desde">{{ t.desde }}</span>
          </div>
        </template>
        <q-markup-table v-else flat dense class="tablaTramos">
          <thead><tr><th class="text-left">Tramo</th><th>Presentes</th><th>Atrasados</th><th>Total</th></tr></thead>
          <tbody>
            <tr v-for="t in tramos" :key="t.desde">
              <td>{{ t.desde }}–{{ t.hasta }}</td><td class="text-center">{{ t.presente }}</td><td class="text-center">{{ t.atrasado }}</td><td class="text-center"><b>{{ t.total }}</b></td>
            </tr>
          </tbody>
        </q-markup-table>
      </div>
    </div>

    <div class="rfidGrid">
      <div class="card">
        <div class="cardHead">
          <h3>Estudiantes <span class="badge blue q-ml-sm">{{ estudiantes.length }}</span></h3>
          <span class="subtle">Clic en un estudiante para simular la pasada de su tarjeta.</span>
        </div>

        <q-inner-loading :showing="cargando" />
        <div class="alumnos">
          <button
            v-for="e in estudiantes" :key="e.id" type="button" class="alumno"
            :class="[estadoDe(e), { flash: destello === e.id }]" :title="`Tarjeta ${e.codigo || 'sin tarjeta'}`"
            :disabled="!e.codigo" @click="simular(e.codigo)"
          >
            <span class="avatar">{{ iniciales(e.nombre) }}</span>
            <span class="datos">
              <b>{{ e.numero_lista }}. {{ e.nombre }}</b>
              <small>{{ detalle(e) }}</small>
            </span>
            <span class="badge" :class="claseBadge(estadoDe(e))">{{ ETIQUETAS[estadoDe(e)] }}</span>
          </button>
        </div>
        <div v-if="!cargando && !estudiantes.length" class="subtle q-pa-md">Sin estudiantes matriculados en este curso.</div>
      </div>

      <div class="card feed">
        <div class="cardHead">
          <h3>Llegadas en vivo</h3>
          <span class="badge" :class="enVivo ? 'ok' : ''">{{ marcas.length }}</span>
        </div>
        <TransitionGroup name="marca" tag="div">
          <div v-for="m in marcas" :key="m.id" class="marca" :class="{ desconocida: m.desconocida }">
            <q-icon :name="m.desconocida ? 'fas fa-triangle-exclamation' : m.tipo === 'entrada' ? 'fas fa-right-to-bracket' : 'fas fa-right-from-bracket'" />
            <div class="datos">
              <b>{{ m.desconocida ? `Tarjeta desconocida ${m.codigo}` : m.nombre }}</b>
              <small>{{ hora(m.marcado_en) }} · {{ m.tipo }} · {{ m.curso || 'sin curso' }} · {{ m.lector }}</small>
            </div>
            <span v-if="m.propuesta" class="badge" :class="claseBadge(m.propuesta)">{{ ETIQUETAS[m.propuesta] }}</span>
          </div>
        </TransitionGroup>
        <div v-if="!marcas.length" class="subtle">Aún no hay marcas hoy.</div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { crearSocket, EVENTO_RFID } from '../../../composables/sockets'

definePage({ meta: { title: 'Asistencia RFID', permiso: ['rfid.ver', 'clases.dictar'] } })
defineOptions({ name: 'AsistenciaRfidPage' })

const { proxy } = getCurrentInstance()
const urlSimulador = useRouter().resolve('/rfid/simulador').href

const ETIQUETAS = { presente: 'Presente', atrasado: 'Atrasado', ausente: 'Sin marca', salio: 'Salió' }
const TRAMO_MIN = 10

const cursos = ref([])
const cursoId = ref(null)
const estudiantes = ref([])
const marcas = ref([])
const cargando = ref(false)
const simulando = ref(false)
const enVivo = ref(false)
const conectando = ref(false)
const destello = ref(null)
const caminantes = ref([])
const ultima = ref(null)
const limite = ref('')
const vistaGrafico = ref('grafico')

let socket = null
let timerDestello = null
let secuencia = 0

const estadoDe = e => (e.entrada && !e.dentro ? 'salio' : e.propuesta)
const claseBadge = estado => ({ presente: 'ok', atrasado: 'warn', ausente: 'red', salio: 'blue' }[estado] || '')
const hora = f => (f || '').slice(11, 16)
const iniciales = n => (n || '').split(/[ ,]+/).filter(Boolean).slice(0, 2).map(p => p[0]).join('')
const detalle = e => (e.entrada ? `Entrada ${hora(e.entrada)}${estadoDe(e) === 'salio' ? ' · ya salió' : ''}` : (e.codigo || 'Sin tarjeta'))
const nombreCurso = computed(() => cursos.value.find(c => c.id === cursoId.value)?.nombre || '')

const conteo = computed(() => {
  const c = { presente: 0, atrasado: 0, ausente: 0, salieron: 0 }
  estudiantes.value.forEach(e => {
    c[e.propuesta]++
    if (estadoDe(e) === 'salio') c.salieron++
  })
  return c
})

// Primera entrada de cada estudiante agrupada en tramos de 10 min (de la primera a la última)
const aMin = h => { const [a, b] = h.split(':').map(Number); return a * 60 + b }
const aHora = m => `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`
const tramos = computed(() => {
  const entradas = estudiantes.value.filter(e => e.entrada).map(e => ({ min: aMin(hora(e.entrada)), propuesta: e.propuesta }))
  if (!entradas.length) return []
  const tramo = m => Math.floor(m / TRAMO_MIN) * TRAMO_MIN
  const desde = tramo(Math.min(...entradas.map(e => e.min)))
  const hasta = tramo(Math.max(...entradas.map(e => e.min)))
  const filas = []
  for (let m = desde; m <= hasta; m += TRAMO_MIN) {
    const en = entradas.filter(e => tramo(e.min) === m)
    const presente = en.filter(e => e.propuesta === 'presente').length
    filas.push({ desde: aHora(m), hasta: aHora(m + TRAMO_MIN), presente, atrasado: en.length - presente, total: en.length })
  }
  return filas.slice(-14)
})
const maxTramo = computed(() => Math.max(1, ...tramos.value.map(t => t.total)))

function error (e, texto) {
  proxy.$alert.error(e.response?.data?.message || texto)
}

async function cargar () {
  if (!cursoId.value) return
  cargando.value = true
  try {
    const params = { curso_id: cursoId.value }
    const [est, mar] = await Promise.all([proxy.$axios.get('rfid/estudiantes', { params }), proxy.$axios.get('rfid/marcas/hoy', { params })])
    estudiantes.value = est.data
    marcas.value = mar.data
  } catch (e) {
    error(e, 'No se pudo cargar la asistencia')
  } finally {
    cargando.value = false
  }
}

// Un punto que cruza los dos portales (de vuelta si es salida)
function animar (m, e) {
  const clase = m.desconocida ? 'desconocida' : m.tipo === 'salida' ? 'salio' : m.propuesta
  caminantes.value.push({ key: ++secuencia, iniciales: e ? iniciales(e.nombre) : '?', clase, salida: m.tipo === 'salida' })
  ultima.value = {
    clase,
    titulo: m.desconocida ? `Tarjeta desconocida ${m.codigo}` : (e?.nombre || m.codigo),
    meta: `${hora(m.marcado_en)} · ${m.tipo}${m.propuesta ? ` · ${ETIQUETAS[m.propuesta]}` : ''} · ${m.lector}`,
  }
}
const quitarCaminante = key => { caminantes.value = caminantes.value.filter(w => w.key !== key) }

// El socket no trae nombres (es compartido y sin token): se completan con la nómina cargada
function recibir (m) {
  if (!cursoId.value || m.curso_id !== cursoId.value) return // solo el curso abierto (que es visible para quien mira)
  if (marcas.value.some(x => x.id === m.id)) return
  const e = estudiantes.value.find(x => x.id === m.estudiante_id)
  const curso = cursos.value.find(c => c.id === m.curso_id)
  marcas.value = [{ ...m, nombre: e?.nombre, curso: curso?.nombre }, ...marcas.value].slice(0, 100)
  animar(m, e)

  if (!e) return
  if (m.tipo === 'entrada' && !e.entrada) {
    e.entrada = m.marcado_en
    e.propuesta = m.propuesta
  }
  e.dentro = m.tipo === 'entrada'
  destello.value = e.id
  clearTimeout(timerDestello)
  timerDestello = setTimeout(() => { destello.value = null }, 1600)
}

function alternarVivo () {
  if (enVivo.value) {
    socket?.disconnect()
    enVivo.value = false
    return
  }
  socket ||= crearSocket()
  socket.off()
  socket.on('connect', () => {
    conectando.value = false
    enVivo.value = true
    cargar() // lo que llegó mientras estaba desconectado
  })
  socket.on('disconnect', () => { enVivo.value = false })
  socket.on('connect_error', e => {
    conectando.value = false
    enVivo.value = false
    socket.disconnect()
    proxy.$alert.error(`No se pudo activar el tiempo real: ${e.message}`)
  })
  socket.on(EVENTO_RFID, recibir)
  conectando.value = true
  socket.connect()
}

// Prueba sin lector físico: Laravel registra la marca y la avisa al socket
async function simular (codigo = null) {
  simulando.value = true
  try {
    const { data } = await proxy.$axios.post('rfid/marcar', { codigo, curso_id: codigo ? null : cursoId.value })
    if (data.duplicada) proxy.$alert.warning(`Pasada repetida de ${data.nombre}: se ignora (rebote del lector).`)
    else if (!enVivo.value) proxy.$alert.success(`Marca registrada: ${data.nombre || data.codigo}. Activa el tiempo real para verla llegar.`)
  } catch (e) {
    error(e, 'No se pudo simular la pasada')
  } finally {
    simulando.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await proxy.$axios.get('rfid/cursos')
    cursos.value = data.cursos
    limite.value = data.limite
    cursoId.value = cursos.value[0]?.id ?? null
    await cargar()
  } catch (e) {
    error(e, 'No se pudo cargar la asistencia RFID')
  }
  alternarVivo() // la pantalla del portal parte escuchando
})

onBeforeUnmount(() => {
  socket?.disconnect()
  clearTimeout(timerDestello)
})
</script>

<style scoped>
.btnDetener { background: #bd1f2d; color: #fff; border-radius: 11px; font-weight: 800 }
.btnOutline { border-radius: 11px; font-weight: 800 }

.estadoVivo { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #fff; border: 1px solid #d9e3ec; border-radius: 14px; padding: 8px 14px; margin-bottom: 14px }
.estadoVivo .punto { width: 10px; height: 10px; border-radius: 50%; background: #9aa9b6 }
.estadoVivo.on { border-color: #c4e8d5; background: #f4fbf7 }
.estadoVivo.on .punto { background: #17a35a; box-shadow: 0 0 0 0 rgba(23, 163, 90, .6); animation: pulso 1.6s infinite }
@keyframes pulso { 70% { box-shadow: 0 0 0 9px rgba(23, 163, 90, 0) } 100% { box-shadow: 0 0 0 0 rgba(23, 163, 90, 0) } }

/* Portal (ui3: .portalStage / .gate / .walker) */
.portalWrap { display: grid; grid-template-columns: 1.2fr .8fr; gap: 14px; margin-bottom: 14px }
.portalStage { height: 330px; border: 1px solid #c9d9e5; border-radius: 16px; background: linear-gradient(#edf6fb, #fff); position: relative; overflow: hidden }
.ground { position: absolute; left: 0; right: 0; bottom: 60px; height: 3px; background: #b7c8d5 }
.gate { position: absolute; bottom: 62px; width: 90px; height: 150px; border: 7px solid #426e91; border-bottom: 0; border-radius: 20px 20px 0 0 }
.gate.one { left: 23% }
.gate.two { right: 18% }
.gate::before, .gate::after { content: ""; position: absolute; top: 40px; width: 8px; height: 74px; background: #1f6fb2; border-radius: 5px; box-shadow: 0 0 18px #1f6fb2aa }
.gate::before { left: -8px }
.gate::after { right: -8px }
.gateLabel { position: absolute; top: -26px; left: 50%; transform: translateX(-50%); white-space: nowrap; font-size: 10px; font-weight: 900; color: #315e82 }
.scanPanel { position: absolute; left: 50%; top: 12px; transform: translateX(-50%); background: #ffffffdb; border: 1px solid #cbd9e3; border-left: 4px solid #cbd9e3; border-radius: 11px; padding: 8px 14px; text-align: center; min-width: 260px; max-width: 90%; z-index: 2 }
.scanPanel b { display: block; font-size: 13px; color: #0b2440 }
.scanPanel small { color: #688095 }
.scanPanel.presente { border-left-color: #17703f }
.scanPanel.atrasado { border-left-color: #d97706 }
.scanPanel.salio { border-left-color: #1f6fb2 }
.scanPanel.desconocida { border-left-color: #bd1f2d }

.walker { position: absolute; left: -30px; bottom: 64px; width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font-size: 10px; font-weight: 900; color: #fff; background: #bd1f2d; border: 2px solid #fff; box-shadow: 0 0 0 1px #0002, 0 4px 10px #0b244033; animation: walk 4.2s linear forwards }
.walker.presente { background: #17703f }
.walker.atrasado { background: #d97706 }
.walker.salio { background: #1f6fb2 }
.walker.salida { animation-name: walkBack }
@keyframes walk { 0% { left: -30px; opacity: 0 } 4% { opacity: 1 } 43% { left: 31% } 69% { left: 69% } 96% { opacity: 1 } 100% { left: 103%; opacity: 0 } }
@keyframes walkBack { 0% { left: 103%; opacity: 0 } 4% { opacity: 1 } 31% { left: 69% } 57% { left: 31% } 96% { opacity: 1 } 100% { left: -30px; opacity: 0 } }

.rfidMetrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 10px }
.rfidMetric { background: #fff; border: 1px solid #d7e1e9; border-radius: 12px; padding: 10px; text-align: center }
.rfidMetric small { display: block; color: #6d8090; font-size: 11px }
.rfidMetric b { font-size: 24px; color: #154c76 }
.rfidMetric b.ok { color: #17703f }
.rfidMetric b.warn { color: #b56b12 }
.rfidMetric b.red { color: #bd1f2d }

/* Gráfico de llegadas: barras apiladas presente / atrasado */
.leyenda { display: flex; gap: 14px; align-items: center; font-size: 11px; color: #3a5871; margin-bottom: 10px }
.leyenda .sw { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; vertical-align: -1px }
.sw.presente, .seg.presente { background: #17703f }
.sw.atrasado, .seg.atrasado { background: #d97706 }
.chart { height: 250px; display: flex; align-items: flex-end; gap: 6px; padding-top: 18px; border-bottom: 1px solid #c9d6e1; background: repeating-linear-gradient(to top, #eef3f7 0 1px, transparent 1px 25%) }
.chart .col { flex: 1; height: 100%; display: flex; align-items: flex-end; cursor: default }
.chart .col:hover .pila { opacity: .85 }
.pila { width: 100%; max-width: 38px; margin: 0 auto; display: flex; flex-direction: column; gap: 2px; position: relative; min-height: 4px }
.seg { flex-basis: 0; min-height: 3px }
.pila .seg:first-of-type { border-radius: 4px 4px 0 0 }
.total { position: absolute; top: -17px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: 800; color: #0b2440 }
.ejeX { display: flex; gap: 6px; margin-top: 4px }
.ejeX span { flex: 1; text-align: center; font-size: 9px; color: #6d8090; white-space: nowrap; overflow: hidden }
.tablaTramos { max-height: 280px; overflow: auto }

.rfidGrid { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 14px; align-items: start }
.alumnos { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 8px; position: relative }
.alumno { display: flex; align-items: center; gap: 10px; text-align: left; font: inherit; color: inherit; background: #fff; border: 1px solid #d9e3ec; border-left: 4px solid #d9e3ec; border-radius: 12px; padding: 8px 10px; cursor: pointer; transition: background .3s, transform .2s }
.alumno:hover:not(:disabled) { background: #f5f9fc }
.alumno:disabled { opacity: .55; cursor: not-allowed }
.alumno.presente { border-left-color: #17703f }
.alumno.atrasado { border-left-color: #d97706 }
.alumno.salio { border-left-color: #1f6fb2 }
.alumno.flash { background: #e6f7ee; transform: scale(1.02); box-shadow: 0 0 0 2px #17a35a55 }
.alumno .avatar { flex: none; width: 34px; height: 34px; border-radius: 50%; background: #eaf3fb; color: #0f4f85; display: grid; place-items: center; font-size: 12px; font-weight: 900 }
.alumno .datos, .marca .datos { flex: 1; min-width: 0; display: flex; flex-direction: column }
.alumno .datos b, .marca .datos b { font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.alumno .datos small, .marca .datos small { color: #6d8090; font-size: 11px }

.feed { position: sticky; top: 12px; max-height: calc(100vh - 120px); overflow: auto }
.marca { display: flex; align-items: center; gap: 10px; padding: 8px 4px; border-bottom: 1px solid #eef3f7; color: #0f4f85 }
.marca.desconocida { color: #bd1f2d }
.marca-enter-active { transition: all .45s ease }
.marca-enter-from { opacity: 0; transform: translateY(-10px); background: #e6f7ee }

.badge.warn { background: #fff5e7; border-color: #f2d9b5; color: #b56b12 }

@media (max-width: 1100px) {
  .portalWrap, .rfidGrid { grid-template-columns: 1fr }
  .feed { position: static; max-height: none }
}
@media (max-width: 600px) {
  .rfidMetrics { grid-template-columns: repeat(2, 1fr) }
  .gate { width: 64px; height: 140px }
  .scanPanel { min-width: 0; width: 88% }
}
</style>
