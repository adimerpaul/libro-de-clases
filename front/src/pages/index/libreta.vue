<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Libreta del Estudiante</h1>
        <p>
          Documento digital de identidad, comunicación y acompañamiento pedagógico. Vincula al establecimiento,
          al estudiante y a la familia con trazabilidad y foco en el aprendizaje.
        </p>
      </div>
      <q-btn
        v-if="libreta" no-caps unelevated class="btnPrimary" icon="fas fa-pen-to-square" label="Nueva comunicación"
        @click="nuevaEntrada()"
      />
    </div>

    <!-- Sin estudiantes -->
    <div v-if="!cargandoLista && !estudiantes.length && !esPersonal" class="card vacio">
      <q-icon name="fas fa-book-bookmark" size="34px" />
      <h3>Aún no tiene estudiantes vinculados</h3>
      <p class="subtle">El establecimiento debe vincular su cuenta a la ficha de su hijo o hija. Comuníquese con secretaría o con el profesor(a) jefe.</p>
    </div>

    <template v-else>
      <!-- Selector: chips para la familia, buscador para el personal -->
      <div v-if="esPersonal" class="card selector">
        <q-select
          v-model="estudianteId" :options="opciones" option-value="id" option-label="nombre" emit-value map-options
          use-input input-debounce="300" outlined dense label="Estudiante" style="min-width: 320px" class="col"
          :loading="cargandoLista" @filter="buscar" @update:model-value="abrir"
        >
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
          <template #option="{ itemProps, opt }">
            <q-item v-bind="itemProps">
              <q-item-section>
                <q-item-label>{{ opt.nombre }}</q-item-label>
                <q-item-label caption>{{ opt.curso || 'Sin matrícula este año' }}</q-item-label>
              </q-item-section>
            </q-item>
          </template>
          <template #no-option><q-item><q-item-section class="subtle">Sin estudiantes en tus cursos para esa búsqueda</q-item-section></q-item></template>
        </q-select>
        <span class="subtle">Escribe como <b>establecimiento</b>: el profesor(a) jefe y la familia verán la entrada.</span>
      </div>

      <div v-if="!libreta && !cargando" class="card vacio">
        <q-icon name="fas fa-book-bookmark" size="34px" />
        <b>Elige un estudiante para abrir su libreta.</b>
      </div>

      <div v-if="cargando && !libreta" class="card text-center q-pa-xl"><q-spinner size="28px" color="primary" /></div>

      <template v-if="libreta">
        <div class="libretaHero">
          <div class="studentPhoto">{{ iniciales }}</div>
          <div>
            <span class="eyebrow heroEyebrow">Estudiante</span>
            <h2>{{ libreta.estudiante.nombre }}</h2>
            <p>{{ libreta.curso ? `${libreta.curso.nombre} ${libreta.curso.anio}` : 'Sin matrícula este año' }} · {{ libreta.establecimiento.nombre }}</p>
            <div class="institutionStrip">
              <span><q-icon name="fas fa-calendar-check" /> Asistencia del mes {{ libreta.resumen.asistencia ?? '—' }}{{ libreta.resumen.asistencia != null ? '%' : '' }}</span>
              <span><q-icon name="fas fa-star" /> Promedio {{ nota(libreta.resumen.promedio) }}</span>
              <span><q-icon name="fas fa-clock" /> {{ libreta.resumen.atrasos }} atrasos</span>
              <span>Año {{ libreta.curso?.anio || anioActual }}</span>
            </div>
          </div>
          <div class="heroLado">
            <div v-if="hijos.length > 1" class="childPicker">
              <button
                v-for="h in hijos" :key="h.id" type="button" class="childChip" :class="{ active: h.id === estudianteId }"
                @click="abrir(h.id)"
              >
                {{ primerNombre(h.nombre) }}
              </button>
            </div>
            <div class="idBadge">
              <b>Libreta Digital 4.2</b>
              <small>RBD {{ libreta.establecimiento.rbd }} · N° lista {{ libreta.curso?.numero_lista ?? '—' }}<br>Actualizada: {{ hoyCl }}</small>
            </div>
          </div>
        </div>

        <div v-if="pendientes.length && esFamilia" class="pendientesAviso">
          <q-icon name="fas fa-bell" />
          <b>{{ pendientes.length }} {{ pendientes.length === 1 ? 'comunicación espera' : 'comunicaciones esperan' }} su respuesta.</b>
          <q-btn flat dense no-caps label="Ver" @click="tab = 'comunicacion'" />
        </div>

        <div class="notebookTabs">
          <button v-for="t in TABS" :key="t.value" type="button" class="notebookTab" :class="{ active: tab === t.value }" @click="tab = t.value">
            <q-icon :name="t.icon" class="q-mr-xs" /> {{ t.label }}
            <span v-if="t.value === 'comunicacion' && pendientes.length" class="tabBadge">{{ pendientes.length }}</span>
          </button>
        </div>

        <q-inner-loading :showing="cargando" />

        <!-- ── 1. Identificación e institución ── -->
        <div v-if="tab === 'identificacion'" class="libretaSectionGrid">
          <div class="infoCard">
            <h3><q-icon name="far fa-id-card" /> Identificación del estudiante</h3>
            <div class="infoRows">
              <div><b>Nombre completo</b><span>{{ libreta.estudiante.nombre }}</span></div>
              <div><b>RUN</b><span>{{ formatoRun(libreta.estudiante.run) }}</span></div>
              <div v-if="libreta.estudiante.fecha_nacimiento"><b>Fecha de nacimiento</b><span>{{ fechaCl(libreta.estudiante.fecha_nacimiento) }}</span></div>
              <div><b>Curso / grupo</b><span>{{ libreta.curso ? `${libreta.curso.nombre} ${libreta.curso.anio} · N° ${libreta.curso.numero_lista}` : 'Sin matrícula este año' }}</span></div>
              <div><b>Profesor(a) jefe</b><span>{{ libreta.curso?.profesor_jefe || '—' }}<template v-if="libreta.curso?.profesor_jefe_email"> · {{ libreta.curso.profesor_jefe_email }}</template></span></div>
              <div><b>Contacto de emergencia</b><span>{{ contacto }}</span></div>
              <div v-if="libreta.estudiante.direccion"><b>Dirección</b><span>{{ libreta.estudiante.direccion }}</span></div>
              <div v-if="libreta.estudiante.pie !== null"><b>Programa PIE</b><span>{{ libreta.estudiante.pie ? 'Sí, con apoyos de integración' : 'No' }}</span></div>
            </div>
            <div class="scopeNotice">Los datos de contacto se muestran solo a la familia y al personal autorizado. Si cambian, avise a secretaría o por una comunicación.</div>
          </div>
          <div class="infoCard">
            <h3><q-icon name="fas fa-school" /> Datos del establecimiento</h3>
            <div class="infoRows">
              <div><b>Centro educativo</b><span>{{ libreta.establecimiento.nombre }}</span></div>
              <div><b>RBD</b><span>{{ libreta.establecimiento.rbd }}</span></div>
              <div><b>Dirección</b><span>{{ libreta.establecimiento.direccion }}</span></div>
              <div><b>Teléfono emergencia</b><span>{{ libreta.establecimiento.telefono }}</span></div>
              <div><b>Correo</b><span>{{ libreta.establecimiento.correo }}</span></div>
              <div><b>Jornada</b><span>{{ libreta.establecimiento.jornada }}</span></div>
              <div><b>Canales oficiales</b><span>Libreta 4.2 · correo institucional · teléfono</span></div>
            </div>
          </div>
          <div class="infoCard full">
            <h3><q-icon name="fas fa-scale-balanced" /> Reglamento interno abreviado</h3>
            <div class="ruleList">
              <div v-for="r in libreta.establecimiento.reglamento" :key="r[0]" class="ruleItem"><b>{{ r[0] }}:</b> {{ r[1] }}</div>
            </div>
          </div>
        </div>

        <!-- ── 2. Comunicación y seguimiento ── -->
        <template v-if="tab === 'comunicacion'">
          <div v-if="pendientes.length" class="pendientesGrid">
            <div v-for="e in pendientes" :key="e.id" class="authCard">
              <span class="badge" :class="TIPO_CLASE[e.tipo]">{{ etiquetaTipo(e.tipo) }}</span>
              <b>{{ e.asunto }}</b>
              <p>{{ e.mensaje }}</p>
              <p v-if="e.fecha_evento" class="cuando"><q-icon name="far fa-calendar" /> {{ fechaLarga(e.fecha_evento) }}{{ e.hora_evento ? ` · ${e.hora_evento}` : '' }}</p>
              <small class="subtle">{{ e.autor }} · {{ fechaHora(e.creado_en) }}</small>
              <div v-if="esFamilia" class="actionRow">
                <q-btn
                  v-for="(r, j) in respuestasDe(e)" :key="r.value" no-caps dense unelevated size="sm" class="q-px-sm"
                  :class="j === 0 ? 'btnOk' : 'btnSuave'" :label="r.label" :loading="respondiendo === `${e.id}-${r.value}`"
                  @click="responder(e, r)"
                />
              </div>
              <div v-else class="subtle q-mt-xs"><q-icon name="far fa-hourglass-half" /> Esperando respuesta de la familia</div>
            </div>
          </div>

          <div class="communicationGrid">
            <div class="commBox">
              <h4><q-icon name="fas fa-handshake" /> Citaciones y entrevistas</h4>
              <p>Reuniones entre docentes, equipos y familia con fecha, motivo, responsable y confirmación.</p>
              <div v-for="e in porTipo('citacion')" :key="e.id" class="filaMini">
                <div><b>{{ fechaCorta(e.fecha_evento) }}{{ e.hora_evento ? ` · ${e.hora_evento}` : '' }}</b><small>{{ e.asunto }}</small></div>
                <span class="pill" :class="estadoRespuesta(e).clase">{{ estadoRespuesta(e).texto }}</span>
              </div>
              <div v-if="!porTipo('citacion').length" class="subtle">Sin citaciones.</div>
            </div>

            <div class="commBox">
              <h4><q-icon name="fas fa-user-clock" /> Asistencia y justificaciones</h4>
              <p>
                {{ libreta.resumen.asistencia != null ? `${libreta.resumen.asistencia}% de asistencia este mes` : 'Sin marcas de asistencia este mes' }}
                · {{ libreta.resumen.atrasos }} atrasos · {{ porTipo('justificacion').length }} justificaciones · {{ porTipo('retiro').length }} avisos de retiro.
              </p>
              <div v-if="esFamilia" class="actionRow">
                <q-btn no-caps dense outline size="sm" icon="fas fa-notes-medical" label="Justificar inasistencia" class="q-px-sm" @click="nuevaEntrada('justificacion')" />
                <q-btn no-caps dense outline size="sm" icon="fas fa-door-open" label="Avisar retiro anticipado" class="q-px-sm" @click="nuevaEntrada('retiro')" />
              </div>
              <small class="subtle">La asistencia es la propuesta de los lectores RFID; la oficial la registra el establecimiento.</small>
            </div>

            <div class="commBox">
              <h4><q-icon name="fas fa-file-signature" /> Acuerdos y firmas</h4>
              <p>Compromisos con responsables y conformidad de la familia.</p>
              <div v-for="e in porTipo('acuerdo')" :key="e.id" class="acuerdo">
                <b>{{ e.asunto }}</b>
                <div class="signatureLine"><span>Establecimiento</span><b class="signatureMark">{{ firma(e.autor) }}</b></div>
                <div class="signatureLine">
                  <span>Familia</span>
                  <b v-if="e.respuesta" class="signatureMark">{{ firma(e.respondido_por) }}</b>
                  <span v-else class="pill amber">Pendiente</span>
                </div>
              </div>
              <div v-if="!porTipo('acuerdo').length" class="subtle">Sin acuerdos registrados.</div>
            </div>
          </div>

          <div class="libretaBook">
            <div class="noteSpread">
              <div class="notePage">
                <h3>Establecimiento → familia</h3>
                <div v-for="e in delOrigen('establecimiento')" :key="e.id" class="noteEntry" :class="e.tipo">
                  <div class="noteHead"><span class="badge" :class="TIPO_CLASE[e.tipo]">{{ etiquetaTipo(e.tipo) }}</span><b>{{ e.asunto }}</b></div>
                  <div v-if="e.mensaje" class="noteTexto">{{ e.mensaje }}</div>
                  <div v-if="e.fecha_evento" class="cuando"><q-icon name="far fa-calendar" /> {{ fechaLarga(e.fecha_evento) }}{{ e.hora_evento ? ` · ${e.hora_evento}` : '' }}</div>
                  <small>{{ e.autor }} · {{ fechaHora(e.creado_en) }}</small>
                  <div class="traza">
                    <span v-if="e.respuesta" class="pill" :class="estadoRespuesta(e).clase">{{ estadoRespuesta(e).texto }} · {{ e.respondido_por }} {{ fechaHora(e.respondido_en) }}</span>
                    <span v-else-if="e.requiere_respuesta" class="pill amber">Espera respuesta</span>
                    <span v-if="e.leido_en" class="leido"><q-icon name="fas fa-check-double" /> Leída por {{ e.leido_por }} · {{ fechaHora(e.leido_en) }}</span>
                    <span v-else class="leido noLeido"><q-icon name="fas fa-check" /> Enviada, sin leer</span>
                  </div>
                  <div v-if="e.comentario_respuesta" class="comentario">“{{ e.comentario_respuesta }}”</div>
                </div>
                <div v-if="!delOrigen('establecimiento').length" class="vacioNota">Sin comunicaciones.</div>
              </div>
              <div class="notePage">
                <h3>Familia → establecimiento</h3>
                <div v-for="e in delOrigen('familia')" :key="e.id" class="noteEntry familia">
                  <div class="noteHead"><span class="badge" :class="TIPO_CLASE[e.tipo]">{{ etiquetaTipo(e.tipo) }}</span><b>{{ e.asunto }}</b></div>
                  <div v-if="e.mensaje" class="noteTexto">{{ e.mensaje }}</div>
                  <div v-if="e.fecha_evento" class="cuando"><q-icon name="far fa-calendar" /> {{ fechaLarga(e.fecha_evento) }}{{ e.hora_evento ? ` · ${e.hora_evento}` : '' }}</div>
                  <small>{{ e.autor }} · {{ fechaHora(e.creado_en) }}</small>
                  <div class="traza">
                    <span v-if="e.leido_en" class="leido"><q-icon name="fas fa-check-double" /> Leída por {{ e.leido_por }} · {{ fechaHora(e.leido_en) }}</span>
                    <span v-else class="leido noLeido"><q-icon name="fas fa-check" /> Enviada, sin leer</span>
                  </div>
                </div>
                <div v-if="!delOrigen('familia').length" class="vacioNota">Sin comunicaciones.</div>
              </div>
            </div>
          </div>
        </template>

        <!-- ── 3. Organización y aprendizaje ── -->
        <div v-if="tab === 'organizacion'" class="libretaSectionGrid">
          <div class="infoCard">
            <h3><q-icon name="far fa-calendar-days" /> Calendario de evaluaciones</h3>
            <p class="subtle q-mb-sm">Evaluaciones del curso del último mes y los próximos dos meses.</p>
            <div v-if="libreta.calendario.length" class="agendaGrid">
              <div class="head">Fecha</div><div class="head">Evaluación</div><div class="head">Estado</div>
              <template v-for="c in libreta.calendario" :key="c.id">
                <div>{{ fechaCorta(c.fecha) }}</div>
                <div class="celdaEval"><span class="dot" :style="{ background: c.color || '#c5d3df' }" /> <span><b>{{ c.asignatura }}</b> · {{ sinAsignatura(c) }}</span></div>
                <div><span class="pill" :class="ESTADO_EVAL[c.estado].clase">{{ ESTADO_EVAL[c.estado].texto }}</span></div>
              </template>
            </div>
            <div v-else class="subtle">No hay evaluaciones con fecha en este período.</div>
          </div>

          <div class="infoCard">
            <h3><q-icon name="fas fa-bullseye" /> Lo que se evaluará</h3>
            <p class="subtle q-mb-sm">El estudiante y la familia conocen de antemano los criterios: son los indicadores del objetivo de aprendizaje (OA) de cada evaluación.</p>
            <div v-for="c in conCriterios" :key="c.id" class="criterio">
              <div class="criterioHead">
                <b>{{ c.asignatura }} · {{ sinAsignatura(c) }}</b>
                <span class="badge blue">{{ c.oa.codigo }}</span>
              </div>
              <p>{{ c.oa.texto }}</p>
              <ul v-if="c.oa.indicadores.length"><li v-for="i in c.oa.indicadores" :key="i">{{ i }}</li></ul>
              <p v-else class="subtle">El OA aún no tiene indicadores registrados.</p>
            </div>
            <div v-if="!conCriterios.length" class="subtle">Las próximas evaluaciones aún no tienen un OA asociado.</div>
          </div>
        </div>

        <div class="privacyNotice">
          <q-icon name="fas fa-user-shield" />
          <b>Trazabilidad y privacidad:</b> cada entrada registra emisor, fecha, lectura y respuesta, y no se edita ni se borra.
          La familia solo accede a los estudiantes vinculados a su cuenta.
        </div>
      </template>
    </template>

    <!-- ── Nueva entrada ── -->
    <q-dialog v-model="dialogo">
      <q-card class="formCard" style="width: 560px">
        <q-form @submit.prevent="enviar">
          <q-card-section class="formTitle">
            Nueva comunicación · {{ esFamilia ? 'Familia → establecimiento' : 'Establecimiento → familia' }}
          </q-card-section>
          <q-card-section class="formGrid">
            <q-select v-model="form.tipo" :options="tiposPropios" emit-value map-options label="Tipo" outlined dense class="full" />
            <q-input v-model="form.asunto" label="Asunto" outlined dense class="full" maxlength="150" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-input
              v-model="form.mensaje" type="textarea" autogrow outlined dense class="full" maxlength="3000"
              :label="form.tipo === 'comunicacion' ? 'Mensaje' : 'Detalle (opcional)'"
            />
            <q-input v-if="conFecha" v-model="form.fecha_evento" type="date" stack-label outlined dense :label="ETIQUETA_FECHA[form.tipo] || 'Fecha'" />
            <q-input v-if="conHora" v-model="form.hora_evento" type="time" stack-label outlined dense label="Hora" />
            <q-toggle
              v-if="!esFamilia && form.tipo === 'comunicacion'" v-model="form.requiere_confirmacion" class="full"
              label="Pedir a la familia que confirme la lectura"
            />
            <div v-if="!esFamilia && respuestasTipo.length" class="full scopeNotice">
              La familia podrá responder: <b>{{ respuestasTipo.map(r => r.label).join(' / ') }}</b>.
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated class="btnPrimary" icon="far fa-paper-plane" label="Enviar" :loading="enviando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { fechaCl } from '../../composables/fechas'

definePage({ meta: { title: 'Libreta del estudiante', permiso: ['familia.ver', 'libreta.ver', 'clases.dictar'] } })

const { proxy } = getCurrentInstance()

const TABS = [
  { value: 'identificacion', label: '1. Identificación e institución', icon: 'far fa-id-card' },
  { value: 'comunicacion', label: '2. Comunicación y seguimiento', icon: 'far fa-comments' },
  { value: 'organizacion', label: '3. Organización y aprendizaje', icon: 'far fa-calendar-days' },
]
const TIPO_CLASE = {
  comunicacion: 'blue', citacion: 'violeta', autorizacion: 'warn', acuerdo: 'warn', felicitacion: 'ok', justificacion: 'blue', retiro: 'red',
}
const ESTADO_EVAL = {
  proxima: { texto: 'Próxima', clase: 'amber' },
  por_calificar: { texto: 'Por calificar', clase: 'gray' },
  evaluada: { texto: 'Evaluada', clase: 'green' },
}
const ETIQUETA_FECHA = { justificacion: 'Fecha de la inasistencia', retiro: 'Fecha del retiro', citacion: 'Fecha de la citación', autorizacion: 'Fecha de la actividad' }
const RESPUESTA_CLASE = { confirmada: 'green', autoriza: 'green', firmada: 'green', enterado: 'green', no_asistira: 'red', no_autoriza: 'red' }

const anioActual = new Date().getFullYear()
const hoyCl = new Date().toLocaleDateString('es-CL')

const estudiantes = ref([])
const opciones = ref([])
const cargandoLista = ref(true)
const estudianteId = ref(null)
const libreta = ref(null)
const cargando = ref(false)
const tab = ref('identificacion')

// Familia: solo sus hijos. Personal (libreta.ver o docente): buscador de estudiantes.
const esPersonal = computed(() => proxy.$auth.hasPermission(['libreta.ver', 'clases.dictar']))
const hijos = computed(() => estudiantes.value.filter(e => e.hijo))
const esFamilia = computed(() => libreta.value?.rol === 'familia')

const iniciales = computed(() => (libreta.value?.estudiante.nombre || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase())
const contacto = computed(() => {
  const c = libreta.value?.estudiante.contacto_emergencia || {}
  return [c.nombre, c.telefono, c.email].filter(Boolean).join(' · ') || 'Sin registrar'
})
const entradas = computed(() => libreta.value?.entradas || [])
const pendientes = computed(() => entradas.value.filter(e => e.origen === 'establecimiento' && e.requiere_respuesta && !e.respuesta))
const conCriterios = computed(() => (libreta.value?.calendario || []).filter(c => c.oa && c.estado !== 'evaluada'))

const porTipo = t => entradas.value.filter(e => e.tipo === t)
const delOrigen = o => entradas.value.filter(e => e.origen === o)
const tipoInfo = t => libreta.value?.tipos.find(x => x.value === t)
const etiquetaTipo = t => tipoInfo(t)?.label || t
const respuestasDe = e => tipoInfo(e.tipo)?.respuestas.length ? tipoInfo(e.tipo).respuestas : [{ value: 'enterado', label: 'Confirmo lectura' }]

function estadoRespuesta (e) {
  if (!e.respuesta) return e.requiere_respuesta ? { texto: 'Pendiente', clase: 'amber' } : { texto: 'Informativa', clase: 'gray' }
  const r = respuestasDe(e).find(x => x.value === e.respuesta)
  return { texto: e.respuesta === 'enterado' ? 'Lectura confirmada' : (r?.label || e.respuesta), clase: RESPUESTA_CLASE[e.respuesta] || 'gray' }
}

const nota = n => (n == null ? '—' : Number(n).toFixed(1).replace('.', ','))
const fechaHora = s => (s ? `${fechaCl(s.slice(0, 10))} ${s.slice(11, 16)}` : '')
const fechaCorta = s => (s ? new Date(s + 'T12:00:00').toLocaleDateString('es-CL', { day: '2-digit', month: 'short' }) : '—')
const fechaLarga = s => {
  const t = new Date(s + 'T12:00:00').toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long' })
  return t.charAt(0).toUpperCase() + t.slice(1)
}
// 'Control de avance · Matemática' → 'Control de avance' (la asignatura ya se muestra al lado)
const sinAsignatura = c => (c.asignatura && c.nombre.endsWith(' · ' + c.asignatura) ? c.nombre.slice(0, -(c.asignatura.length + 3)) : c.nombre)
// 'Castillo Pizarro, Fernanda Constanza' → 'Fernanda'
const primerNombre = n => (n.includes(',') ? n.split(',')[1] : n).trim().split(' ')[0]
const firma = nombre => (nombre || '').split(' ').filter(Boolean).map((w, i) => (i === 0 ? w[0] + '.' : w)).slice(0, 2).join(' ')

function formatoRun (run) {
  if (!run) return '—'
  const [cuerpo, dv] = run.split('-')
  return `${Number(cuerpo).toLocaleString('es-CL')}-${dv}`
}

// ── Carga ──
function cargarLista (q = '') {
  cargandoLista.value = true
  return proxy.$axios.get('libreta/estudiantes', { params: { q: q || undefined } })
    .then(res => {
      if (!q) estudiantes.value = res.data
      opciones.value = res.data
      return res.data
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { cargandoLista.value = false })
}

function buscar (texto, update) {
  cargarLista(texto).then(() => update())
}

function abrir (id) {
  if (!id) return
  estudianteId.value = id
  try { localStorage.setItem('libretaEstudiante', String(id)) } catch { /* sin storage */ }
  cargando.value = true
  return proxy.$axios.get(`libreta/${id}`)
    .then(res => { libreta.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { cargando.value = false })
}

// ── Responder (familia) ──
const respondiendo = ref(null)

function responder (e, r) {
  const enviar = comentario => {
    respondiendo.value = `${e.id}-${r.value}`
    proxy.$axios.post(`libreta/entradas/${e.id}/responder`, { respuesta: r.value, comentario: comentario || null })
      .then(() => {
        proxy.$alert.success('Respuesta registrada')
        abrir(estudianteId.value)
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
      .finally(() => { respondiendo.value = null })
  }
  proxy.$q.dialog({
    title: `${r.label}: ${e.asunto}`,
    message: 'Puede agregar un comentario para el establecimiento (opcional).',
    prompt: { model: '', type: 'text', maxlength: 300 },
    cancel: { flat: true, noCaps: true, label: 'Cancelar' },
    ok: { unelevated: true, noCaps: true, label: 'Enviar respuesta', color: 'primary' },
  }).onOk(enviar)
}

// ── Nueva entrada ──
const dialogo = ref(false)
const enviando = ref(false)
const form = ref({})

const tiposPropios = computed(() => (libreta.value?.tipos || [])
  .filter(t => t.origen === null || t.origen === libreta.value.rol)
  .map(t => ({ value: t.value, label: t.label })))
const conFecha = computed(() => ['citacion', 'justificacion', 'retiro', 'autorizacion'].includes(form.value.tipo))
const conHora = computed(() => ['citacion', 'retiro'].includes(form.value.tipo))
const respuestasTipo = computed(() => tipoInfo(form.value.tipo)?.respuestas || [])

function nuevaEntrada (tipo = 'comunicacion') {
  const asuntos = { justificacion: 'Justificación de inasistencia', retiro: 'Retiro anticipado' }
  form.value = { tipo, asunto: asuntos[tipo] || '', mensaje: '', fecha_evento: '', hora_evento: '', requiere_confirmacion: false }
  dialogo.value = true
}

function enviar () {
  enviando.value = true
  const f = form.value
  proxy.$axios.post(`libreta/${estudianteId.value}/entradas`, {
    tipo: f.tipo,
    asunto: f.asunto,
    mensaje: f.mensaje || null,
    fecha_evento: conFecha.value ? f.fecha_evento || null : null,
    hora_evento: conHora.value ? f.hora_evento || null : null,
    requiere_confirmacion: f.requiere_confirmacion,
  })
    .then(() => {
      proxy.$alert.success('Comunicación enviada')
      dialogo.value = false
      tab.value = 'comunicacion'
      abrir(estudianteId.value)
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { enviando.value = false })
}

onMounted(() => {
  cargarLista().then(lista => {
    if (!lista?.length) return
    let guardado = null
    try { guardado = Number(localStorage.getItem('libretaEstudiante')) } catch { /* sin storage */ }
    const inicial = lista.find(e => e.id === guardado) || lista.find(e => e.hijo) || (esPersonal.value ? null : lista[0])
    if (inicial) abrir(inicial.id)
  })
})
</script>

<style scoped>
.vacio { display: grid; place-items: center; gap: 6px; text-align: center; padding: 34px 16px; color: #6d8090 }
.vacio h3 { margin: 0; color: #15324a; font-size: 17px }
.selector { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; padding: 12px 14px }

.libretaHero {
  background: linear-gradient(135deg, #143b60, #1b6a9d 58%, #7a2430); color: #fff; border-radius: 20px;
  padding: 18px; display: grid; grid-template-columns: auto 1fr auto; gap: 16px; align-items: center;
}
.studentPhoto {
  width: 78px; height: 92px; border-radius: 14px; background: linear-gradient(135deg, #dceaf5, #bcd2e3);
  display: grid; place-items: center; font-size: 26px; font-weight: 900; color: #285a7c; border: 4px solid #ffffffaa;
}
.heroEyebrow { color: #d8edf9 }
.libretaHero h2 { margin: 2px 0 4px; font-size: 24px; font-weight: 800; line-height: 1.2 }
.libretaHero p { margin: 0; color: #deebf4; font-size: 12px }
.institutionStrip { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 9px }
.institutionStrip span { font-size: 11px; padding: 5px 9px; border-radius: 999px; background: #ffffff18; border: 1px solid #ffffff2b; display: inline-flex; gap: 5px; align-items: center }
.heroLado { display: flex; flex-direction: column; gap: 9px; align-items: flex-end }
.childPicker { display: flex; gap: 6px; flex-wrap: wrap }
.childChip { border: 1px solid #ffffff36; background: #ffffff14; color: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 800; cursor: pointer }
.childChip.active { background: #fff; color: #155b70 }
.idBadge { background: #ffffff18; border: 1px solid #ffffff27; border-radius: 12px; padding: 10px 12px; min-width: 190px }
.idBadge b { display: block; font-size: 12px }
.idBadge small { font-size: 10px; color: #e5eef5 }

.pendientesAviso {
  display: flex; align-items: center; gap: 8px; margin-top: 12px; padding: 9px 13px; border-radius: 12px;
  background: #fff5e7; border: 1px solid #f1d7ad; color: #8a520c; font-size: 13px;
}

.notebookTabs { display: flex; gap: 7px; flex-wrap: wrap; margin: 14px 0 13px }
.notebookTab {
  border: 1px solid #cfdce7; background: #fff; color: #35556e; border-radius: 999px; padding: 8px 13px;
  font: inherit; font-size: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center;
}
.notebookTab.active { background: var(--role); border-color: var(--role); color: #fff }
.tabBadge { margin-left: 6px; background: #bd1f2d; color: #fff; border-radius: 999px; padding: 0 6px; font-size: 10px }

.libretaSectionGrid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px }
.infoCard { border: 1px solid #dbe5ed; border-radius: 14px; padding: 14px; background: #fff }
.infoCard.full { grid-column: 1 / -1 }
.infoCard h3 { font-size: 14px; font-weight: 800; margin: 0 0 9px; color: #275b82; display: flex; gap: 7px; align-items: center }
.infoRows { display: grid }
.infoRows div { display: grid; grid-template-columns: 160px 1fr; gap: 8px; padding: 7px 0; border-bottom: 1px dashed #e3eaf0; font-size: 12px }
.infoRows div:last-child { border-bottom: 0 }
.infoRows b { color: #6c8091; font-weight: 700 }
.scopeNotice { margin-top: 10px; font-size: 11px; color: #587184; background: #f5f9fc; border: 1px dashed #b9cad7; border-radius: 10px; padding: 8px 10px }
.ruleList { display: grid; gap: 7px; grid-template-columns: 1fr 1fr }
.ruleItem { padding: 9px 11px; border-left: 4px solid #1f6fb2; background: #f6f9fc; border-radius: 0 10px 10px 0; font-size: 12px; line-height: 1.5 }

.pendientesGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; margin-bottom: 12px }
.authCard { border: 1px solid #f1d7ad; border-radius: 13px; padding: 12px; background: #fffdf8; display: flex; flex-direction: column; gap: 4px }
.authCard .badge { align-self: flex-start }
.authCard b { font-size: 13px }
.authCard p { font-size: 12px; color: #5f7485; margin: 0; line-height: 1.45 }
.cuando { font-size: 12px; color: #0f4f85 !important; font-weight: 700 }
.actionRow { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px }
.btnOk { background: #17703f; color: #fff; border-radius: 9px; font-weight: 800 }
.btnSuave { background: #eef3f7; color: #33536d; border-radius: 9px; font-weight: 800 }

.communicationGrid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px }
.commBox { border: 1px solid #dce5ec; border-radius: 13px; padding: 12px; background: #fff }
.commBox h4 { margin: 0 0 6px; font-size: 13px; font-weight: 800; display: flex; gap: 7px; align-items: center }
.commBox p { font-size: 12px; color: #60778a; line-height: 1.45; margin: 0 0 8px }
.filaMini { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 7px 0; border-top: 1px solid #eef3f7 }
.filaMini b { display: block; font-size: 12px }
.filaMini small { color: #6d8090; font-size: 11px }
.acuerdo { padding-top: 6px; border-top: 1px solid #eef3f7; margin-top: 6px }
.acuerdo > b { font-size: 12px }
.signatureLine { display: flex; gap: 8px; align-items: center; justify-content: space-between; padding: 6px 9px; border: 1px solid #dce5ec; border-radius: 10px; margin-top: 6px }
.signatureLine span { font-size: 10px; color: #647a8c }
.signatureMark { font-family: "Brush Script MT", "Segoe Script", cursive; font-size: 20px; color: #245e93; font-weight: 400 }

.pill { font-size: 10px; font-weight: 800; border-radius: 999px; padding: 3px 9px; white-space: nowrap; background: #eef3f7; color: #486477 }
.pill.green { background: #eaf8f1; color: #17703f }
.pill.amber { background: #fff5e7; color: #8a520c }
.pill.red { background: #fdeef0; color: #bd1f2d }
.pill.gray { background: #eef3f7; color: #6d8090 }
.badge.warn { background: #fff5e7; border-color: #f1d7ad; color: #8a520c }
.badge.violeta { background: #f0edff; border-color: #d9d1fb; color: #5b3fb0 }

.libretaBook {
  margin-top: 12px; background: linear-gradient(90deg, #fffdf7, #fff 48%, #fffdf7); border: 1px solid #cad7e1;
  border-radius: 18px; box-shadow: 0 16px 34px #15324a16; padding: 18px;
}
.noteSpread { display: grid; grid-template-columns: 1fr 1fr; gap: 26px }
.notePage { min-height: 300px; padding: 14px 16px; background: repeating-linear-gradient(#fff 0 28px, #d9e7f1 29px); border-radius: 10px }
.notePage h3 { background: #fff; margin: -2px -4px 12px; padding: 6px; color: #225c8e; font-family: Georgia, serif; font-size: 16px }
.noteEntry { background: #ffffffee; border: 1px solid #d6e2ea; border-left: 4px solid var(--role); border-radius: 9px; padding: 10px; margin: 8px 0; font-size: 12px; line-height: 1.45 }
.noteEntry.familia { border-left-color: #1685a0 }
.noteEntry.felicitacion { border-left-color: #17703f }
.noteEntry.citacion, .noteEntry.autorizacion, .noteEntry.acuerdo { border-left-color: #b56b12 }
.noteHead { display: flex; gap: 7px; align-items: center; flex-wrap: wrap; margin-bottom: 3px }
.noteTexto { color: #33536d }
.noteEntry small { color: #6d8090 }
.traza { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 6px }
.leido { font-size: 10px; color: #17703f }
.leido.noLeido { color: #8597a6 }
.comentario { margin-top: 5px; font-style: italic; color: #486477; font-size: 11px }
.vacioNota { color: #8597a6; font-size: 12px; padding: 10px 0 }

.agendaGrid { display: grid; grid-template-columns: 80px 1fr auto; border: 1px solid #dce5ec; border-radius: 12px; overflow: hidden }
.agendaGrid > div { padding: 8px 9px; border-bottom: 1px solid #e3eaf0; font-size: 12px; display: flex; align-items: center; gap: 5px }
.agendaGrid .head { background: #edf4f9; font-weight: 900; color: #315b7d; font-size: 11px }
.agendaGrid > .celdaEval { align-items: flex-start }
.celdaEval .dot { margin-top: 4px }
.dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex: none }
.criterio { border: 1px solid #e3eaf0; background: #f8fbfd; border-radius: 11px; padding: 10px 12px; margin-bottom: 8px }
.criterioHead { display: flex; justify-content: space-between; gap: 8px; align-items: center }
.criterioHead b { font-size: 12px }
.criterio p { font-size: 12px; color: #5f7485; margin: 4px 0 }
.criterio ul { margin: 0; padding-left: 18px; font-size: 12px; color: #33536d }

.privacyNotice { margin-top: 14px; border: 1px solid #cedce7; background: #f7fafc; border-radius: 12px; padding: 10px 12px; font-size: 11px; color: #617789; display: flex; gap: 6px; align-items: center; flex-wrap: wrap }

@media (max-width: 1100px) {
  .communicationGrid { grid-template-columns: 1fr }
}
@media (max-width: 860px) {
  .libretaHero { grid-template-columns: auto 1fr }
  .heroLado { grid-column: 1 / -1; align-items: stretch }
  .libretaSectionGrid, .noteSpread, .ruleList { grid-template-columns: 1fr }
  .infoRows div { grid-template-columns: 1fr }
}
</style>
