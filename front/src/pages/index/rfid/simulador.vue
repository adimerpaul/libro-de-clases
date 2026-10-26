<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Simulador de lector RFID</h1>
        <p>Hace de lector físico: cada pasada la registra Laravel y la avisa al socket. Abre <b>Asistencia RFID</b> en otra pestaña para ver a los estudiantes cruzar el portal.</p>
      </div>
      <div class="row q-gutter-sm">
        <q-btn label="Ver portal en vivo" icon="fas fa-tower-broadcast" no-caps unelevated class="btnPrimary" :href="urlPortal" target="_blank" />
      </div>
    </div>

    <div class="estadoVivo" :class="{ on: conectado }">
      <span class="punto" />
      <b>Socket {{ conectado ? 'conectado' : 'desconectado' }}</b>
      <span class="subtle">{{ URL_SOCKET }} · evento <code>{{ EVENTO_RFID }}</code></span>
    </div>

    <div class="simGrid">
      <div class="card">
        <div class="cardHead">
          <h3>Pasar tarjeta</h3>
          <q-select
            v-model="cursoId" :options="cursos" option-value="id" option-label="nombre" emit-value map-options
            dense outlined label="Curso" style="min-width: 190px" @update:model-value="cargar"
          />
        </div>

        <div class="controles">
          <q-input v-model="lector" dense outlined label="Lector" style="min-width: 200px" />
          <q-btn label="Pasada al azar" icon="fas fa-shuffle" no-caps outline color="primary" class="btnOutline" :disable="!!rafaga" @click="pasar()" />
          <q-input v-model.number="cantidad" type="number" dense outlined label="Cantidad" style="width: 100px" :min="1" :max="200" />
          <q-input v-model.number="cada" type="number" dense outlined label="Cada (s)" style="width: 100px" :min="0.3" step="0.5" />
          <q-btn
            :label="rafaga ? `Detener (${rafaga.hechas}/${rafaga.total})` : 'Ráfaga de llegadas'" :icon="rafaga ? 'fas fa-stop' : 'fas fa-play'"
            no-caps unelevated :class="rafaga ? 'btnDetener' : 'btnPrimary'" @click="rafaga ? detener() : iniciarRafaga()"
          />
        </div>

        <q-inner-loading :showing="cargando" />
        <div class="alumnos">
          <div v-for="e in estudiantes" :key="e.id" class="alumno" :class="estadoDe(e)">
            <span class="datos">
              <b>{{ e.numero_lista }}. {{ e.nombre }}</b>
              <small>{{ e.codigo || 'Sin tarjeta' }}{{ e.entrada ? ` · entrada ${hora(e.entrada)}` : '' }}</small>
            </span>
            <q-btn dense flat round icon="fas fa-id-card" color="primary" :disable="!e.codigo" @click="pasar(e.codigo)">
              <q-tooltip>Pasar su tarjeta</q-tooltip>
            </q-btn>
            <q-btn v-if="urlGet && e.codigo" dense flat round icon="fas fa-link" color="grey-7" :href="urlGet(e.codigo)" target="_blank">
              <q-tooltip>Abrir el GET de prueba en el navegador</q-tooltip>
            </q-btn>
          </div>
        </div>
        <div v-if="!cargando && !estudiantes.length" class="subtle q-pa-md">Sin estudiantes matriculados en este curso.</div>
      </div>

      <div class="column q-gutter-y-md">
        <div class="card">
          <div class="cardHead"><h3>Por GET (navegador, Postman, lector)</h3></div>
          <p class="subtle q-mt-none">Sin sesión, solo en local (<code>RFID_SIMULADOR_GET</code>). Cada llamada registra la marca y la avisa al socket.</p>
          <div class="urlBox"><code>{{ API }}/rfid/simular?codigo=RFID-000001</code></div>
          <div class="urlBox"><code>{{ API }}/rfid/simular?curso_id={{ cursoId || 1 }}</code> <span class="subtle">(al azar del curso)</span></div>
          <div class="urlBox"><code>php artisan rfid:simular 20 --cada=1</code> <span class="subtle">(desde back/)</span></div>
        </div>

        <div class="card log">
          <div class="cardHead">
            <h3>Pasadas enviadas</h3>
            <span class="badge blue">{{ envios.length }}</span>
          </div>
          <div v-for="x in envios" :key="x.key" class="envio" :class="x.clase">
            <q-icon :name="x.icono" />
            <div class="datos">
              <b>{{ x.titulo }}</b>
              <small>{{ x.meta }}</small>
            </div>
            <span v-if="x.id" class="badge" :class="x.socket ? 'ok' : ''">
              <q-icon :name="x.socket ? 'fas fa-check' : 'fas fa-hourglass-half'" size="9px" class="q-mr-xs" />{{ x.socket ? 'socket' : 'esperando' }}
            </span>
          </div>
          <div v-if="!envios.length" class="subtle">Aún no se envían pasadas.</div>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { getCurrentInstance, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { crearSocket, EVENTO_RFID, URL_SOCKET } from '../../../composables/sockets'

definePage({ meta: { title: 'Simulador RFID', permiso: 'rfid.ver' } })
defineOptions({ name: 'SimuladorRfidPage' })

const { proxy } = getCurrentInstance()
const API = import.meta.env.VITE_API_BACK
const urlPortal = useRouter().resolve('/rfid').href
// El GET sin sesión solo existe en local
const urlGet = import.meta.env.DEV ? codigo => `${API}/rfid/simular?codigo=${encodeURIComponent(codigo)}` : null

const cursos = ref([])
const cursoId = ref(null)
const estudiantes = ref([])
const cargando = ref(false)
const lector = ref('Portal principal')
const cantidad = ref(20)
const cada = ref(1)
const rafaga = ref(null)
const envios = ref([])
const conectado = ref(false)

let socket = null
let timer = null
let secuencia = 0

const hora = f => (f || '').slice(11, 16)
const estadoDe = e => (e.entrada && !e.dentro ? 'salio' : e.propuesta)
const ETIQUETAS = { presente: 'Presente', atrasado: 'Atrasado' }

async function cargar () {
  if (!cursoId.value) return
  cargando.value = true
  try {
    estudiantes.value = (await proxy.$axios.get('rfid/estudiantes', { params: { curso_id: cursoId.value } })).data
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'No se pudo cargar el curso')
  } finally {
    cargando.value = false
  }
}

function anotar (d) {
  const e = estudiantes.value.find(x => x.id === d.estudiante_id)
  if (e && !d.duplicada) {
    if (d.tipo === 'entrada' && !e.entrada) Object.assign(e, { entrada: d.marcado_en, propuesta: d.propuesta })
    e.dentro = d.tipo === 'entrada'
  }
  envios.value = [{
    key: ++secuencia,
    id: d.duplicada ? null : d.id,
    socket: false,
    clase: d.duplicada ? 'repetida' : d.desconocida ? 'desconocida' : '',
    icono: d.duplicada ? 'fas fa-rotate' : d.tipo === 'salida' ? 'fas fa-right-from-bracket' : 'fas fa-right-to-bracket',
    titulo: d.duplicada ? `${d.nombre || d.codigo}: repetida, se ignora` : (d.nombre || `Tarjeta desconocida ${d.codigo}`),
    meta: d.duplicada ? 'Rebote del lector' : `${hora(d.marcado_en)} · ${d.tipo}${d.propuesta ? ` · ${ETIQUETAS[d.propuesta]}` : ''} · ${d.curso || 'sin curso'}`,
  }, ...envios.value].slice(0, 80)
}

async function pasar (codigo = null) {
  try {
    const { data } = await proxy.$axios.post('rfid/marcar', { codigo, curso_id: codigo ? null : cursoId.value, lector: lector.value || null })
    anotar(data)
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'No se pudo simular la pasada')
    detener()
  }
}

function iniciarRafaga () {
  const total = Math.min(200, Math.max(1, cantidad.value || 1))
  rafaga.value = { hechas: 0, total }
  const paso = async () => {
    if (!rafaga.value) return
    await pasar()
    if (!rafaga.value) return
    rafaga.value.hechas++
    if (rafaga.value.hechas >= total) return detener()
    timer = setTimeout(paso, Math.max(0.3, cada.value || 1) * 1000)
  }
  paso()
}

function detener () {
  clearTimeout(timer)
  rafaga.value = null
}

onMounted(async () => {
  socket = crearSocket()
  socket.on('connect', () => { conectado.value = true })
  socket.on('disconnect', () => { conectado.value = false })
  socket.on('connect_error', () => { conectado.value = false })
  // Confirma que la pasada volvió por el socket (lo mismo que recibe el portal)
  socket.on(EVENTO_RFID, m => {
    const x = envios.value.find(v => v.id === m.id)
    if (x) x.socket = true
  })
  socket.connect()

  try {
    cursos.value = (await proxy.$axios.get('rfid/cursos')).data.cursos
    cursoId.value = cursos.value[0]?.id ?? null
    await cargar()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'No se pudo cargar los cursos')
  }
})

onBeforeUnmount(() => {
  detener()
  socket?.disconnect()
})
</script>

<style scoped>
.btnDetener { background: #bd1f2d; color: #fff; border-radius: 11px; font-weight: 800 }
.btnOutline { border-radius: 11px; font-weight: 800 }

.estadoVivo { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #fff; border: 1px solid #d9e3ec; border-radius: 14px; padding: 10px 14px; margin-bottom: 14px }
.estadoVivo .punto { width: 10px; height: 10px; border-radius: 50%; background: #9aa9b6 }
.estadoVivo.on { border-color: #c4e8d5; background: #f4fbf7 }
.estadoVivo.on .punto { background: #17a35a }

.simGrid { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 14px; align-items: start }
.controles { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 12px }
.alumnos { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px; position: relative }
.alumno { display: flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #d9e3ec; border-left: 4px solid #d9e3ec; border-radius: 12px; padding: 6px 6px 6px 10px }
.alumno.presente { border-left-color: #17703f }
.alumno.atrasado { border-left-color: #d97706 }
.alumno.salio { border-left-color: #1f6fb2 }
.alumno .datos, .envio .datos { flex: 1; min-width: 0; display: flex; flex-direction: column }
.alumno .datos b, .envio .datos b { font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.alumno .datos small, .envio .datos small { color: #6d8090; font-size: 11px }

.urlBox { background: #f4f7fa; border: 1px solid #dde6ed; border-radius: 10px; padding: 8px 10px; margin-top: 6px; font-size: 11px; word-break: break-all }
.urlBox code { color: #0f4f85 }

.log { max-height: calc(100vh - 380px); min-height: 200px; overflow: auto }
.envio { display: flex; align-items: center; gap: 10px; padding: 8px 4px; border-bottom: 1px solid #eef3f7; color: #0f4f85 }
.envio.repetida { color: #8a99a6 }
.envio.desconocida { color: #bd1f2d }

@media (max-width: 1100px) {
  .simGrid { grid-template-columns: 1fr }
  .log { max-height: none }
}
</style>
