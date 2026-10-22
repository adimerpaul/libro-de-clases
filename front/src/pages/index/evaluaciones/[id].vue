<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <q-btn flat dense no-caps icon="fas fa-arrow-left" label="Mis instrumentos" to="/evaluaciones" class="q-mb-xs volver" />
        <h1>{{ esNuevo ? 'Nuevo instrumento' : form.nombre || 'Instrumento' }}</h1>
        <p>Revisa y ajusta cada pregunta: el generador solo propone un borrador desde el OA, la versión final es tuya.</p>
      </div>
      <div class="row q-gutter-sm">
        <q-btn flat no-caps icon="fas fa-print" label="Vista previa" :disable="!form.preguntas.length" @click="vistaPrevia = true" />
        <q-btn no-caps unelevated icon="far fa-floppy-disk" label="Guardar" class="btnPrimary" :loading="guardando" @click="guardar" />
      </div>
    </div>

    <div v-if="cargando" class="card text-center q-pa-xl"><q-spinner size="28px" color="primary" /></div>

    <div v-else class="editor">
      <!-- ── Columna izquierda: datos, generador y resumen ── -->
      <div class="lateral">
        <div class="card">
          <div class="cardHead"><h3>Datos del instrumento</h3></div>
          <div class="campos">
            <q-select
              v-model="form.asignacion_id" :options="opcionesCarga" emit-value map-options label="Curso y asignatura"
              outlined dense @update:model-value="form.objetivo_aprendizaje_id = null"
            />
            <q-select
              v-model="form.objetivo_aprendizaje_id" :options="oas" option-value="id" :option-label="o => `${o.codigo} · ${o.unidad || ''}`"
              emit-value map-options clearable label="OA evaluado" outlined dense :disable="!form.asignacion_id" :loading="cargandoOa"
            >
              <template #option="{ opt, itemProps }">
                <q-item v-bind="itemProps">
                  <q-item-section>
                    <q-item-label><b>{{ opt.codigo }}</b> · {{ opt.unidad }}</q-item-label>
                    <q-item-label caption lines="2">{{ opt.texto }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
            <div v-if="oaSel" class="oaBox">
              <span v-if="oaSel.es_demo" class="badge red q-mb-xs">OA demo · no oficial</span>
              <div>{{ oaSel.texto }}</div>
            </div>
            <q-input v-model="form.nombre" label="Nombre" outlined dense placeholder="Ej. Control funciones lineales" />
            <div class="row q-col-gutter-sm">
              <div class="col-6"><q-select v-model="form.tipo" :options="tipos" label="Tipo" outlined dense /></div>
              <div class="col-6"><q-input v-model="form.fecha_aplicacion" type="date" label="Aplicación" stack-label outlined dense /></div>
            </div>
            <q-input
              v-model.number="form.exigencia" type="number" min="50" max="70" label="Exigencia (%)" outlined dense
              hint="Porcentaje de logro que equivale a la nota 4,0"
            />
            <q-input v-model="form.instrucciones" type="textarea" autogrow label="Instrucciones para el estudiante" outlined dense />
            <div>
              <div class="fieldLabel">Estado</div>
              <q-btn-toggle
                v-model="form.estado" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
                :options="[{ label: 'Borrador', value: 'borrador' }, { label: 'Listo para aplicar', value: 'listo' }]"
              />
            </div>
          </div>
        </div>

        <div class="card">
          <div class="cardHead">
            <h3>Generar borrador desde el OA</h3>
            <span class="badge">Sin IA</span>
          </div>
          <div class="campos">
            <div class="row q-col-gutter-sm">
              <div class="col-5"><q-input v-model.number="gen.cantidad" type="number" min="1" max="30" label="Preguntas" outlined dense /></div>
              <div class="col-7"><q-select v-model="gen.mezcla" :options="mezclas" emit-value map-options label="Tipo" outlined dense /></div>
            </div>
            <q-btn
              no-caps unelevated icon="fas fa-wand-magic-sparkles" label="Agregar preguntas sugeridas" class="btnSuave"
              :disable="!form.objetivo_aprendizaje_id" :loading="generando" @click="generar"
            />
            <div class="subtle">
              {{ form.objetivo_aprendizaje_id ? 'Se agregan al final; revísalas y edítalas antes de aplicar.' : 'Elige primero el OA evaluado.' }}
            </div>
          </div>
        </div>

        <div class="card resumen">
          <div class="resumenFila"><span>Preguntas</span><b>{{ form.preguntas.length }}</b></div>
          <div class="resumenFila"><span>Puntaje total</span><b>{{ total }} pts</b></div>
          <div class="resumenFila"><span>Nota 4,0 con</span><b>{{ escala?.puntaje_aprobacion ?? '—' }} pts</b></div>
          <q-btn flat dense no-caps icon="fas fa-table-list" label="Ver escala de notas" :disable="!total" @click="verEscala = true" />
        </div>
      </div>

      <!-- ── Columna derecha: preguntas ── -->
      <div class="preguntas">
        <div v-if="!form.preguntas.length" class="card vacio">
          <q-icon name="far fa-rectangle-list" size="30px" />
          <b>Sin preguntas todavía</b>
          <span class="subtle">Agrégalas a mano con los botones de abajo o genera un borrador desde el OA.</span>
        </div>

        <div v-for="(p, i) in form.preguntas" :key="p._k" class="card pregunta">
          <div class="pregHead">
            <span class="pregNum">Pregunta {{ i + 1 }}</span>
            <q-select v-model="p.tipo" :options="tiposPregunta" emit-value map-options dense outlined class="pregTipo" @update:model-value="t => cambiarTipo(p, t)" />
            <q-input v-model.number="p.puntaje" type="number" step="0.5" min="0.5" dense outlined suffix="pts" class="pregPuntaje" />
            <q-space />
            <q-btn flat round dense size="sm" icon="fas fa-arrow-up" :disable="i === 0" @click="mover(i, -1)"><q-tooltip>Subir</q-tooltip></q-btn>
            <q-btn flat round dense size="sm" icon="fas fa-arrow-down" :disable="i === form.preguntas.length - 1" @click="mover(i, 1)"><q-tooltip>Bajar</q-tooltip></q-btn>
            <q-btn flat round dense size="sm" icon="far fa-copy" @click="duplicar(i)"><q-tooltip>Duplicar</q-tooltip></q-btn>
            <q-btn flat round dense size="sm" icon="far fa-trash-can" color="negative" @click="form.preguntas.splice(i, 1)"><q-tooltip>Quitar</q-tooltip></q-btn>
          </div>

          <q-input v-model="p.enunciado" type="textarea" autogrow outlined dense placeholder="Enunciado de la pregunta" />

          <template v-if="p.tipo === 'seleccion'">
            <div class="fieldLabel q-mt-sm">Alternativas · marca la correcta</div>
            <div v-for="(alt, j) in p.alternativas" :key="j" class="alternativa">
              <q-radio v-model="p.respuesta_correcta" :val="String(j)" dense color="positive" />
              <span class="letra">{{ letra(j) }})</span>
              <q-input v-model="p.alternativas[j]" dense borderless class="col" placeholder="Texto de la alternativa" />
              <q-btn flat round dense size="xs" icon="fas fa-xmark" :disable="p.alternativas.length <= 2" @click="quitarAlternativa(p, j)" />
            </div>
            <q-btn
              v-if="p.alternativas.length < 6" flat dense no-caps size="sm" icon="fas fa-plus" label="Alternativa"
              @click="p.alternativas.push('')"
            />
          </template>

          <div v-else-if="p.tipo === 'verdadero_falso'" class="q-mt-sm row items-center q-gutter-sm">
            <span class="fieldLabel q-mb-none">Respuesta correcta:</span>
            <q-btn-toggle
              v-model="p.respuesta_correcta" no-caps unelevated dense toggle-color="positive" color="grey-2" text-color="grey-9"
              :options="[{ label: 'Verdadero', value: 'V' }, { label: 'Falso', value: 'F' }]"
            />
          </div>

          <q-input
            v-else v-model="p.criterios" type="textarea" autogrow outlined dense class="q-mt-sm"
            label="Pauta de corrección (criterios y puntaje)"
          />
        </div>

        <div class="agregar">
          <span class="subtle">Agregar:</span>
          <q-btn flat no-caps dense icon="far fa-circle-dot" label="Selección múltiple" @click="agregar('seleccion')" />
          <q-btn flat no-caps dense icon="fas fa-check-double" label="Verdadero o falso" @click="agregar('verdadero_falso')" />
          <q-btn flat no-caps dense icon="fas fa-align-left" label="Desarrollo" @click="agregar('desarrollo')" />
        </div>
      </div>
    </div>

    <!-- ── Escala de notas ── -->
    <q-dialog v-model="verEscala">
      <q-card class="formCard">
        <q-card-section>
          <div class="formTitle">Escala de notas</div>
          <div class="subtle">{{ total }} pts · exigencia {{ form.exigencia }} % · nota 4,0 con {{ escala?.puntaje_aprobacion }} pts</div>
        </q-card-section>
        <q-card-section class="q-pt-none escalaGrid">
          <div v-for="f in escala?.tabla || []" :key="f.puntaje" class="escalaFila" :class="{ rojo: f.nota < 4 }">
            <span>{{ f.puntaje }}</span><b>{{ nota(f.nota) }}</b>
          </div>
        </q-card-section>
        <q-card-actions align="right"><q-btn flat no-caps label="Cerrar" v-close-popup /></q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ── Vista previa imprimible ── -->
    <q-dialog v-model="vistaPrevia" maximized>
      <q-card class="previa">
        <div class="previaBar noPrint">
          <q-btn-toggle
            v-model="modoPrevia" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
            :options="[{ label: 'Prueba', value: 'prueba' }, { label: 'Pauta de corrección', value: 'pauta' }]"
          />
          <q-space />
          <q-btn no-caps unelevated icon="fas fa-print" label="Imprimir" class="btnPrimary q-mr-sm" @click="imprimir" />
          <q-btn flat round dense icon="fas fa-xmark" v-close-popup />
        </div>

        <div class="hoja printArea">
          <div class="hojaHead">
            <div>
              <div class="hojaTipo">{{ form.tipo }}{{ modoPrevia === 'pauta' ? ' · PAUTA DE CORRECCIÓN' : '' }}</div>
              <h2>{{ form.nombre }}</h2>
              <div>{{ asignacionSel?.asignatura.nombre }} · {{ asignacionSel?.curso.nombre }} {{ asignacionSel?.curso.anio }}</div>
              <div v-if="oaSel" class="hojaOa"><b>{{ oaSel.codigo }}:</b> {{ oaSel.texto }}</div>
            </div>
            <table class="hojaDatos">
              <tr><td>Puntaje total</td><td>{{ total }} pts</td></tr>
              <tr><td>Exigencia</td><td>{{ form.exigencia }} % ({{ escala?.puntaje_aprobacion }} pts = 4,0)</td></tr>
              <tr v-if="form.fecha_aplicacion"><td>Fecha</td><td>{{ fechaCl(form.fecha_aplicacion) }}</td></tr>
            </table>
          </div>

          <div v-if="modoPrevia === 'prueba'" class="hojaAlumno">
            <span>Nombre: ______________________________________</span>
            <span>Puntaje: ______ / {{ total }}</span>
            <span>Nota: ______</span>
          </div>
          <p v-if="form.instrucciones && modoPrevia === 'prueba'" class="hojaInstr"><b>Instrucciones:</b> {{ form.instrucciones }}</p>

          <div v-for="(p, i) in form.preguntas" :key="p._k" class="hojaPregunta">
            <div class="hojaPregHead"><b>{{ i + 1 }}.</b> {{ p.enunciado }} <span class="hojaPts">({{ p.puntaje }} pts)</span></div>
            <template v-if="p.tipo === 'seleccion'">
              <div
                v-for="(alt, j) in p.alternativas" :key="j" class="hojaAlt"
                :class="{ correcta: modoPrevia === 'pauta' && String(j) === String(p.respuesta_correcta) }"
              >
                {{ letra(j) }}) {{ alt }}
              </div>
            </template>
            <div v-else-if="p.tipo === 'verdadero_falso'" class="hojaAlt">
              <template v-if="modoPrevia === 'pauta'"><b class="correctaTxt">{{ p.respuesta_correcta === 'V' ? 'Verdadero' : 'Falso' }}</b></template>
              <template v-else>_____ V &nbsp;&nbsp; _____ F</template>
            </div>
            <template v-else>
              <div v-if="modoPrevia === 'pauta'" class="hojaPauta">{{ p.criterios || 'Sin pauta registrada.' }}</div>
              <div v-else class="hojaLineas" />
            </template>
          </div>

          <div v-if="modoPrevia === 'pauta' && escala" class="hojaEscala">
            <b>Escala de notas</b>
            <div class="escalaGrid">
              <div v-for="f in escala.tabla" :key="f.puntaje" class="escalaFila" :class="{ rojo: f.nota < 4 }">
                <span>{{ f.puntaje }}</span><b>{{ nota(f.nota) }}</b>
              </div>
            </div>
          </div>
        </div>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeError } from '../../../composables/useCrud'
import { fechaCl } from '../../../composables/fechas'

definePage({ meta: { title: 'Evaluaciones', permiso: 'clases.dictar' } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()

const tipos = ['Prueba', 'Control', 'Guía evaluada', 'Ticket de salida']
const tiposPregunta = [
  { label: 'Selección múltiple', value: 'seleccion' },
  { label: 'Verdadero o falso', value: 'verdadero_falso' },
  { label: 'Desarrollo', value: 'desarrollo' },
]
const mezclas = [
  { label: 'Mixto', value: 'mixto' },
  { label: 'Selección múltiple', value: 'seleccion' },
  { label: 'Verdadero o falso', value: 'verdadero_falso' },
  { label: 'Desarrollo', value: 'desarrollo' },
]
const PUNTAJE = { seleccion: 2, verdadero_falso: 1, desarrollo: 4 }

const esNuevo = computed(() => route.params.id === 'nuevo')
const cargando = ref(true)
const guardando = ref(false)
const carga = ref([])
const oas = ref([])
const cargandoOa = ref(false)

// Clave local estable para los v-for (las preguntas se reordenan)
let k = 0
const conClave = p => ({ ...p, _k: ++k })

const form = ref({
  asignacion_id: null,
  objetivo_aprendizaje_id: null,
  nombre: '',
  tipo: 'Prueba',
  fecha_aplicacion: '',
  exigencia: 60,
  instrucciones: 'Lee atentamente cada pregunta antes de responder. Justifica tus respuestas de desarrollo.',
  estado: 'borrador',
  preguntas: [],
})

const opcionesCarga = computed(() => carga.value.map(a => ({ label: `${a.curso.nombre} · ${a.asignatura.nombre}`, value: a.id })))
const asignacionSel = computed(() => carga.value.find(a => a.id === form.value.asignacion_id))
const oaSel = computed(() => oas.value.find(o => o.id === form.value.objetivo_aprendizaje_id))
const total = computed(() => Math.round(form.value.preguntas.reduce((s, p) => s + (Number(p.puntaje) || 0), 0) * 10) / 10)

const letra = j => String.fromCharCode(97 + j)
const nota = n => n.toFixed(1).replace('.', ',')

// OA del nivel y asignatura de la asignación elegida
watch(() => form.value.asignacion_id, id => {
  oas.value = []
  const a = carga.value.find(x => x.id === id)
  if (!a) return
  cargandoOa.value = true
  proxy.$axios.get('docente/oa', { params: { nivel: a.curso.nivel, asignatura_id: a.asignatura_id } })
    .then(res => { oas.value = res.data })
    .finally(() => { cargandoOa.value = false })
})

// ── Preguntas ──
function nuevaPregunta (tipo) {
  return conClave({
    tipo,
    enunciado: '',
    puntaje: PUNTAJE[tipo],
    alternativas: tipo === 'seleccion' ? ['', '', '', ''] : null,
    respuesta_correcta: tipo === 'verdadero_falso' ? 'V' : null,
    criterios: '',
  })
}

function agregar (tipo) {
  form.value.preguntas.push(nuevaPregunta(tipo))
}

function cambiarTipo (p, tipo) {
  p.alternativas = tipo === 'seleccion' ? (p.alternativas?.length ? p.alternativas : ['', '', '', '']) : null
  p.respuesta_correcta = tipo === 'verdadero_falso' ? 'V' : null
}

function mover (i, d) {
  const lista = form.value.preguntas
  const [p] = lista.splice(i, 1)
  lista.splice(i + d, 0, p)
}

function duplicar (i) {
  const p = form.value.preguntas[i]
  form.value.preguntas.splice(i + 1, 0, conClave({ ...p, alternativas: p.alternativas ? [...p.alternativas] : null }))
}

function quitarAlternativa (p, j) {
  p.alternativas.splice(j, 1)
  const c = Number(p.respuesta_correcta)
  if (p.respuesta_correcta !== null && c === j) p.respuesta_correcta = null
  else if (p.respuesta_correcta !== null && c > j) p.respuesta_correcta = String(c - 1)
}

// ── Generador (plantillas del backend, sin IA) ──
const gen = ref({ cantidad: 6, mezcla: 'mixto' })
const generando = ref(false)

function generar () {
  generando.value = true
  proxy.$axios.post('docente/instrumentos/generar', { objetivo_aprendizaje_id: form.value.objetivo_aprendizaje_id, ...gen.value })
    .then(res => {
      form.value.preguntas.push(...res.data.map(conClave))
      if (!form.value.nombre && oaSel.value) form.value.nombre = `${form.value.tipo} ${oaSel.value.unidad || oaSel.value.codigo}`
      proxy.$alert.success(`${res.data.length} preguntas agregadas`, 'Revísalas antes de aplicar')
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { generando.value = false })
}

// ── Escala de notas (la calcula el backend) ──
const escala = ref(null)
const verEscala = ref(false)
let timer = null

watch([total, () => form.value.exigencia], ([maximo, exigencia]) => {
  clearTimeout(timer)
  if (!maximo || !exigencia) { escala.value = null; return }
  timer = setTimeout(() => {
    proxy.$axios.get('docente/instrumentos/escala', { params: { maximo, exigencia } })
      .then(res => { escala.value = res.data })
      .catch(() => { escala.value = null })
  }, 300)
}, { immediate: true })

// ── Guardar ──
function guardar () {
  guardando.value = true
  const f = form.value
  const datos = {
    asignacion_id: f.asignacion_id,
    objetivo_aprendizaje_id: f.objetivo_aprendizaje_id,
    nombre: f.nombre,
    tipo: f.tipo,
    fecha_aplicacion: f.fecha_aplicacion || null,
    exigencia: f.exigencia,
    instrucciones: f.instrucciones,
    estado: f.estado,
    preguntas: f.preguntas.map(({ _k, ...p }) => p),
  }
  const req = esNuevo.value
    ? proxy.$axios.post('docente/instrumentos', datos)
    : proxy.$axios.put(`docente/instrumentos/${route.params.id}`, datos)

  req
    .then(res => {
      proxy.$alert.success(esNuevo.value ? 'Instrumento creado' : 'Instrumento guardado')
      if (esNuevo.value) router.replace(`/evaluaciones/${res.data.id}`)
      else cargarInstrumento(res.data)
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { guardando.value = false })
}

// ── Vista previa e impresión ──
const vistaPrevia = ref(false)
const modoPrevia = ref('prueba')
const imprimir = () => window.print()

function cargarInstrumento (data) {
  form.value = {
    ...data,
    fecha_aplicacion: data.fecha_aplicacion || '',
    preguntas: data.preguntas.map(p => conClave({
      tipo: p.tipo,
      enunciado: p.enunciado,
      puntaje: p.puntaje,
      alternativas: p.alternativas,
      respuesta_correcta: p.respuesta_correcta,
      criterios: p.criterios || '',
    })),
  }
}

function iniciar () {
  cargando.value = true
  const pedidos = [proxy.$axios.get('docente/asignaciones').then(res => { carga.value = res.data })]
  if (!esNuevo.value) {
    pedidos.push(proxy.$axios.get(`docente/instrumentos/${route.params.id}`).then(res => cargarInstrumento(res.data)))
  }
  Promise.all(pedidos)
    .then(() => {
      if (esNuevo.value && carga.value.length === 1) form.value.asignacion_id = carga.value[0].id
      if (route.query.imprimir) vistaPrevia.value = true
    })
    .catch(err => {
      proxy.$alert.error(mensajeError(err))
      router.replace('/evaluaciones')
    })
    .finally(() => { cargando.value = false })
}

// Al guardar uno nuevo la ruta cambia de /nuevo a /<id> y se recarga desde el backend
watch(() => route.params.id, (id, anterior) => { if (id && anterior && id !== anterior) iniciar() })
onMounted(iniciar)
</script>

<style scoped>
.volver { color: #486477 }
.editor { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 15px; align-items: start }
.lateral { display: flex; flex-direction: column; gap: 15px; position: sticky; top: 86px }
.campos { display: flex; flex-direction: column; gap: 10px }
.oaBox { background: #f6f3ff; border: 1px solid #e2dafb; border-radius: 12px; padding: 9px 11px; font-size: 12px; line-height: 1.45 }
.fieldLabel { font-size: 12px; font-weight: 700; color: #486477; margin-bottom: 4px }
.btnSuave { background: #f1edff; color: #5a3fb0; border-radius: 11px; font-weight: 800 }

.resumen { display: flex; flex-direction: column; gap: 6px }
.resumenFila { display: flex; justify-content: space-between; font-size: 13px }
.resumenFila b { font-size: 15px }

.preguntas { display: flex; flex-direction: column; gap: 12px }
.pregunta { padding: 13px 15px }
.pregHead { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap }
.pregNum { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #0f4f85 }
.pregTipo { width: 175px }
.pregPuntaje { width: 95px }
.alternativa { display: flex; align-items: center; gap: 6px; border-bottom: 1px dashed #e3eaf1 }
.letra { font-weight: 800; color: #486477; width: 16px }
.agregar { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; padding: 4px 6px }
.vacio { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 34px; text-align: center; color: #486477 }

.escalaGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(86px, 1fr)); gap: 4px }
.escalaFila { display: flex; justify-content: space-between; border: 1px solid #e3eaf1; border-radius: 8px; padding: 3px 8px; font-size: 12px }
.escalaFila b { color: #0f4f85 }
.escalaFila.rojo b { color: #bd1f2d }

/* Hoja imprimible */
.previa { background: #e9eef3 }
.previaBar { display: flex; align-items: center; gap: 8px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #d9e3ec; position: sticky; top: 0; z-index: 2 }
.hoja { background: #fff; color: #111; max-width: 820px; margin: 20px auto; padding: 36px 44px; box-shadow: 0 8px 30px rgba(0, 0, 0, .12); font-size: 13px; line-height: 1.5 }
.hojaHead { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 12px }
.hojaHead h2 { font-size: 20px; margin: 2px 0; line-height: 1.25 }
.hojaTipo { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #555 }
.hojaOa { font-size: 11px; margin-top: 4px; color: #333 }
.hojaDatos { font-size: 11px; border-collapse: collapse; white-space: nowrap; align-self: flex-start }
.hojaDatos td { border: 1px solid #999; padding: 3px 7px }
.hojaAlumno { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin: 10px 0 }
.hojaInstr { font-size: 12px; background: #f5f5f5; padding: 6px 10px; border-radius: 4px }
.hojaPregunta { margin-top: 14px; break-inside: avoid }
.hojaPts { color: #555; font-size: 11px }
.hojaAlt { margin: 3px 0 0 18px }
.hojaAlt.correcta, .correctaTxt { font-weight: 800; color: #17703f }
.hojaAlt.correcta::after { content: '  ✓' }
.hojaLineas { height: 92px; margin: 6px 0 0 18px; background: repeating-linear-gradient(#fff 0 22px, #bbb 23px) }
.hojaPauta { margin: 4px 0 0 18px; font-size: 12px; background: #f1f9f4; border-left: 3px solid #17703f; padding: 5px 9px }
.hojaEscala { margin-top: 22px; break-inside: avoid }

@media (max-width: 1023px) {
  .editor { grid-template-columns: 1fr }
  .lateral { position: static }
}
@media (max-width: 720px) {
  .hoja { padding: 20px 16px; margin: 0 }
  .hojaHead { flex-direction: column }
}
</style>
