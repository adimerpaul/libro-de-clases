<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Currículum, OA y competencias</h1>
        <p>
          La navegación cubre Educación Parvularia, Básica y Media. Cada OA conserva su fuente, versión y vigencia,
          y alimenta la planificación y el creador de evaluaciones.
        </p>
      </div>
      <div class="row q-gutter-sm items-center">
        <span v-if="cat.resumen.demo" class="badge warn">{{ cat.resumen.demo }} OA DEMO estructurados</span>
        <q-btn
          v-if="$auth.hasPermission('curriculum.crear')" flat no-caps icon="fas fa-file-import" label="Importar catálogo"
          @click="abrirImportar"
        />
        <q-btn
          v-if="$auth.hasPermission('curriculum.crear')" no-caps unelevated class="btnPrimary" icon="fas fa-plus"
          label="Nuevo OA" @click="nuevo"
        />
      </div>
    </div>

    <div class="sourceStrip">
      <b>Modelo curricular:</b> OA → contenidos → competencias/habilidades → indicadores → instrumentos → estrategias →
      inicio/desarrollo/cierre → recursos → tiempo y espacio. La versión productiva debe conservar fuente, vigencia y
      texto oficial del Mineduc; los OA marcados DEMO son resúmenes de ejemplo.
    </div>

    <div class="kpiLine q-mt-md">
      <div class="kpiMini"><q-icon name="fas fa-bullseye" class="kpiIcon" /><b>{{ cat.resumen.vigentes }}</b><small>OA vigentes</small></div>
      <div class="kpiMini"><q-icon name="fas fa-list-check" class="kpiIcon" /><b>{{ cat.resumen.con_indicadores }}</b><small>con indicadores de evaluación</small></div>
      <div class="kpiMini"><q-icon name="fas fa-layer-group" class="kpiIcon" /><b>{{ nivelesConOa }}</b><small>de {{ cat.niveles.length }} niveles con OA</small></div>
      <div class="kpiMini"><q-icon name="fas fa-code-branch" class="kpiIcon" /><b>{{ cat.versiones.length }}</b><small>versiones curriculares</small></div>
    </div>

    <div v-for="etapa in etapas" :key="etapa.nombre" class="etapa">
      <div class="etapaLabel">{{ etapa.nombre }}</div>
      <div class="curriculumMap">
        <button
          v-for="n in etapa.niveles" :key="n.nivel" type="button" class="levelChip" :class="{ active: n.nivel === nivel }"
          @click="elegirNivel(n.nivel)"
        >
          <b>{{ n.nivel }}</b>
          <small>{{ n.total ? `${n.total} OA` : 'Sin OA cargados' }}</small>
        </button>
      </div>
    </div>

    <div class="card q-mt-md">
      <div class="cardHead">
        <h3>{{ nivel }} <span class="badge blue q-ml-sm">{{ oas.length }} OA</span></h3>
      </div>

      <div class="asigPills">
        <button type="button" class="asigPill" :class="{ active: !asignatura }" @click="elegirAsignatura(null)">
          Todas <span>{{ totalNivel }}</span>
        </button>
        <button
          v-for="a in asignaturasNivel" :key="a.id" type="button" class="asigPill" :class="{ active: asignatura === a.id, vacia: !a.total }"
          @click="elegirAsignatura(a.id)"
        >
          <AsignaturaIcono :nombre="a.nombre" :color="a.color" /> {{ a.nombre }} <span>{{ a.total }}</span>
        </button>
      </div>

      <div class="filtros">
        <q-input v-model="texto" dense outlined debounce="300" placeholder="Buscar por código, texto o unidad" class="col" @update:model-value="cargar">
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
        </q-input>
        <q-select
          v-model="version" :options="cat.versiones" clearable dense outlined label="Versión curricular" style="min-width: 170px"
          @update:model-value="cargar"
        />
        <q-toggle v-model="todos" label="Incluir no vigentes" dense @update:model-value="cargar" />
      </div>

      <div v-if="loading" class="lista">
        <q-skeleton v-for="i in 3" :key="i" height="160px" style="border-radius: 13px" />
      </div>
      <div v-else-if="!oas.length" class="vacio">
        <q-icon name="fas fa-bullseye" size="30px" />
        <b>No hay OA {{ texto || version ? 'para estos filtros' : 'cargados para este nivel y asignatura' }}.</b>
        <span v-if="$auth.hasPermission('curriculum.crear')" class="subtle">Créalos uno a uno o importa el catálogo versionado en JSON.</span>
      </div>
      <div v-else class="lista">
        <article v-for="oa in oas" :key="oa.id" class="oaCard" :class="{ retirado: !vigente(oa) }">
          <div class="oaHead">
            <div class="oaTitulo">
              <div class="oaCode">
                <AsignaturaIcono :nombre="oa.asignatura?.nombre" :color="oa.asignatura?.color" />
                {{ oa.codigo }} · {{ oa.nivel }} · {{ oa.asignatura?.nombre }}
              </div>
              <h4>{{ oa.unidad || 'Sin unidad / eje' }}</h4>
              <div class="oaBadges">
                <span v-if="oa.es_demo" class="badge warn">DEMO</span>
                <span v-else class="badge ok">Oficial</span>
                <span v-if="oa.priorizado" class="badge blue">Priorizado<q-tooltip>OA priorizado por el Mineduc (Priorización Curricular)</q-tooltip></span>
                <span class="badge">Versión {{ oa.version_curricular }}</span>
                <span v-if="!vigente(oa)" class="badge red">Retirado {{ fechaCl(oa.vigente_hasta) }}</span>
                <span v-else-if="oa.vigente_desde" class="badge">Vigente desde {{ fechaCl(oa.vigente_desde) }}</span>
                <span v-if="oa.planificaciones_count" class="badge blue">{{ oa.planificaciones_count }} planificación(es)</span>
                <span v-if="oa.instrumentos_count" class="badge violeta">{{ oa.instrumentos_count }} instrumento(s)</span>
                <span v-if="oa.recursos_count" class="badge">{{ oa.recursos_count }} recurso(s)</span>
              </div>
            </div>
            <div class="oaActions">
              <template v-if="$auth.hasPermission('clases.dictar') && vigente(oa)">
                <q-btn flat dense no-caps size="sm" icon="far fa-calendar-plus" label="Usar en planificación" :to="enlace('/planificador', oa)" />
                <q-btn no-caps unelevated dense size="sm" class="btnPrimary q-px-sm" icon="fas fa-flask" label="Crear evaluación" :to="enlace('/evaluaciones/nuevo', oa)" />
              </template>
              <q-btn
                v-if="$auth.hasPermission('curriculum.editar')" outline dense no-caps size="sm" class="btnEditar q-px-sm"
                icon="fas fa-pen" label="Editar" @click="editar(oa)"
              />
              <!-- Sin uso se puede eliminar; en uso solo se retira (deja de ofrecerse, lo ya hecho se conserva) -->
              <q-btn
                v-if="!enUso(oa) && $auth.hasPermission('curriculum.eliminar')" outline dense no-caps size="sm"
                icon="far fa-trash-can" label="Eliminar" class="btnEliminar q-px-sm" @click="eliminar(oa)"
              >
                <q-tooltip>No se usa en planificaciones, evaluaciones ni biblioteca: se puede eliminar</q-tooltip>
              </q-btn>
              <q-btn
                v-else-if="vigente(oa) && $auth.hasPermission('curriculum.editar')" outline dense no-caps size="sm"
                icon="fas fa-box-archive" label="Retirar" class="btnRetirar q-px-sm" @click="retirar(oa)"
              >
                <q-tooltip>Está en uso: no se puede eliminar, pero se puede retirar del catálogo</q-tooltip>
              </q-btn>
              <q-btn
                v-else-if="!vigente(oa) && $auth.hasPermission('curriculum.editar')" outline dense no-caps size="sm"
                icon="fas fa-rotate-left" label="Reactivar" class="btnRetirar q-px-sm" @click="retirar(oa, true)"
              />
              <MenuAcciones :acciones="[
                { label: 'Editar', icon: 'fas fa-pen', permiso: 'curriculum.editar', click: () => editar(oa) },
                { label: 'Duplicar en otra versión', icon: 'far fa-copy', permiso: 'curriculum.crear', click: () => duplicar(oa) },
                { label: 'Retirar del catálogo', icon: 'fas fa-box-archive', permiso: 'curriculum.editar', si: vigente(oa), click: () => retirar(oa) },
                { label: 'Reactivar', icon: 'fas fa-rotate-left', permiso: 'curriculum.editar', si: !vigente(oa), click: () => retirar(oa, true) },
                { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'curriculum.eliminar', si: !enUso(oa), click: () => eliminar(oa) },
              ]" />
            </div>
          </div>

          <p class="oaTexto" style="white-space: pre-line"><b>OA / propósito:</b> {{ oa.texto }}</p>

          <div class="pedagogyGrid">
            <div class="pedBox">
              <h4>Contenidos · ¿qué enseñar?</h4>
              <p>{{ oa.contenidos || '—' }}</p>
            </div>
            <div class="pedBox">
              <h4>Competencias / habilidades</h4>
              <ul v-if="oa.habilidades?.length"><li v-for="h in oa.habilidades" :key="h">{{ h }}</li></ul>
              <p v-else>—</p>
              <p v-if="oa.actitudes?.length" class="q-mt-xs"><b>Actitudes:</b> {{ oa.actitudes.join(' · ') }}</p>
            </div>
            <div class="pedBox">
              <h4>Indicadores e instrumentos</h4>
              <ul v-if="oa.indicadores?.length"><li v-for="h in oa.indicadores" :key="h">{{ h }}</li></ul>
              <p v-else class="sinDato">Sin indicadores: agrégalos para evaluar este OA con criterios.</p>
              <p v-if="oa.instrumentos?.length" class="q-mt-xs"><b>Instrumentos:</b> {{ oa.instrumentos.join(', ') }}</p>
            </div>
            <div class="pedBox">
              <h4>Estrategias y organización didáctica</h4>
              <p>{{ oa.estrategias || '—' }}</p>
            </div>
          </div>

          <div v-if="oa.inicio || oa.desarrollo || oa.cierre" class="phaseGrid">
            <div class="phase"><b>Inicio</b><p>{{ oa.inicio || '—' }}</p></div>
            <div class="phase"><b>Desarrollo</b><p>{{ oa.desarrollo || '—' }}</p></div>
            <div class="phase"><b>Cierre</b><p>{{ oa.cierre || '—' }}</p></div>
          </div>

          <div v-if="oa.recursos?.length || oa.tiempo_espacio" class="pedagogyGrid dos">
            <div class="pedBox"><h4>Recursos y medios didácticos</h4><p>{{ oa.recursos?.join(' · ') || '—' }}</p></div>
            <div class="pedBox"><h4>Tiempo y espacio</h4><p>{{ oa.tiempo_espacio || '—' }}</p></div>
          </div>

          <div class="oaFuente">
            <q-icon name="fas fa-landmark" size="11px" />
            {{ oa.fuente || 'Sin fuente registrada' }}
            <a v-if="oa.fuente_url" :href="oa.fuente_url" target="_blank" rel="noopener">Ver fuente <q-icon name="fas fa-arrow-up-right-from-square" size="9px" /></a>
          </div>
        </article>
      </div>
    </div>

    <!-- ── Formulario de OA ── -->
    <q-dialog v-model="dialog" persistent>
      <q-card class="formCard dialogo">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ form.id ? `Editar ${form.codigo}` : 'Nuevo objetivo de aprendizaje' }}</div>
          <q-space />
          <q-btn flat round dense icon="fas fa-xmark" v-close-popup />
        </q-card-section>

        <q-form @submit.prevent="guardar">
          <q-card-section class="formScroll">
            <div class="seccion">Identificación y fuente</div>
            <div class="formGrid">
              <q-input v-model="form.codigo" label="Código *" outlined dense hint="Ej.: MA2M-OA01" />
              <q-select v-model="form.nivel" :options="cat.niveles.map(n => n.nivel)" label="Nivel *" outlined dense />
              <q-select
                v-model="form.asignatura_id" :options="cat.asignaturas" option-value="id" option-label="nombre"
                emit-value map-options label="Asignatura *" outlined dense
              />
              <q-input v-model="form.unidad" label="Unidad / eje" outlined dense />
              <q-input v-model="form.version_curricular" label="Versión curricular *" outlined dense hint="Ej.: Bases 2019, Ajuste 2026" />
              <q-input v-model="form.fuente_url" label="URL de la fuente" outlined dense type="url" />
              <q-input v-model="form.vigente_desde" label="Vigente desde" outlined dense type="date" stack-label />
              <q-input v-model="form.vigente_hasta" label="Vigente hasta" outlined dense type="date" stack-label hint="Fíjala para retirar un OA en uso" />
            </div>
            <q-input v-model="form.fuente" label="Fuente" outlined dense class="q-mt-sm" />
            <q-toggle v-model="form.es_demo" label="Es un resumen DEMO (no texto oficial del Mineduc)" class="q-mt-xs" />

            <div class="seccion">Objetivo</div>
            <q-input v-model="form.texto" label="Texto del OA *" outlined dense type="textarea" autogrow />
            <q-input v-model="form.contenidos" label="Contenidos · ¿qué enseñar?" outlined dense type="textarea" autogrow class="q-mt-sm" />

            <div class="seccion">Habilidades, indicadores e instrumentos</div>
            <p class="subtle q-mb-sm">Escribe cada elemento y presiona Enter (o sal del campo); para varios a la vez sepáralos con punto y coma. Los indicadores se usan como criterios al crear evaluaciones.</p>
            <div class="formGrid">
              <CampoLista v-for="l in LISTAS" :key="l.campo" v-model="form[l.campo]" :label="l.label" />
            </div>

            <div class="seccion">Orientaciones didácticas</div>
            <q-input v-model="form.estrategias" label="Estrategias y organización didáctica" outlined dense type="textarea" autogrow />
            <div class="formGrid tres q-mt-sm">
              <q-input v-model="form.inicio" label="Inicio" outlined dense type="textarea" autogrow />
              <q-input v-model="form.desarrollo" label="Desarrollo" outlined dense type="textarea" autogrow />
              <q-input v-model="form.cierre" label="Cierre" outlined dense type="textarea" autogrow />
            </div>
            <q-input v-model="form.tiempo_espacio" label="Tiempo y espacio" outlined dense class="q-mt-sm" />
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated class="btnPrimary" icon="far fa-floppy-disk" label="Guardar" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- ── Importación del catálogo (JSON) ── -->
    <q-dialog v-model="importar.abierto">
      <q-card class="formCard" style="width: 620px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Importar catálogo curricular</div>
          <q-space />
          <q-btn flat round dense icon="fas fa-xmark" v-close-popup />
        </q-card-section>
        <q-card-section>
          <p class="subtle">
            Archivo <b>.json</b> con un arreglo de OA (o <code>{ "oas": [...] }</code>) con las claves del esquema
            <code>curriculum_import_schema.json</code>: <code>level</code>, <code>subject</code> (nombre o código de la asignatura),
            <code>oa_code</code>, <code>oa_statement</code>, <code>unit_or_axis</code>, <code>skills</code>, <code>attitudes</code>,
            <code>indicators</code>, <code>source_url</code>, <code>curriculum_version</code>, <code>effective_from</code>,
            <code>effective_to</code>, <code>source_hash</code>. Un OA con el mismo código y versión se actualiza.
            Si alguna fila tiene errores, no se importa nada.
          </p>
          <q-file v-model="importar.archivo" accept=".json,application/json" outlined dense label="Archivo JSON" @update:model-value="leerArchivo">
            <template #prepend><q-icon name="far fa-file-code" /></template>
          </q-file>
          <div v-if="importar.filas.length" class="q-mt-sm subtle">{{ importar.filas.length }} OA leídos del archivo.</div>
          <ul v-if="importar.errores.length" class="errores">
            <li v-for="e in importar.errores" :key="e">{{ e }}</li>
          </ul>
          <q-btn flat dense no-caps size="sm" icon="fas fa-download" label="Descargar ejemplo" class="q-mt-sm" @click="descargarEjemplo" />
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat no-caps label="Cancelar" v-close-popup />
          <q-btn
            no-caps unelevated class="btnPrimary" icon="fas fa-file-import" label="Importar"
            :disable="!importar.filas.length" :loading="importar.enviando" @click="enviarImportacion"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { fechaCl } from '../../composables/fechas'
import AsignaturaIcono from '../../components/AsignaturaIcono.vue'
import MenuAcciones from '../../components/MenuAcciones.vue'
import CampoLista from '../../components/CampoLista.vue'

definePage({ meta: { title: 'Currículum y OA', permiso: 'curriculum.ver' } })

const { proxy } = getCurrentInstance()

const LISTAS = [
  { campo: 'habilidades', label: 'Competencias / habilidades' },
  { campo: 'actitudes', label: 'Actitudes' },
  { campo: 'indicadores', label: 'Indicadores de evaluación' },
  { campo: 'instrumentos', label: 'Instrumentos sugeridos' },
  { campo: 'recursos', label: 'Recursos y medios didácticos' },
]

// Nivel y asignatura elegidos se recuerdan en este navegador
const UI_KEY = 'curriculumUi'
function leerUi () {
  try { return JSON.parse(localStorage.getItem(UI_KEY)) || {} } catch { return {} }
}
function guardarUi () {
  try { localStorage.setItem(UI_KEY, JSON.stringify({ nivel: nivel.value, asignatura: asignatura.value })) } catch { /* sin storage */ }
}

const ui = leerUi()
const cat = ref({ niveles: [], asignaturas: [], conteo: [], versiones: [], resumen: { vigentes: 0, demo: 0, con_indicadores: 0 } })
const nivel = ref(ui.nivel || '2° Medio')
const asignatura = ref(ui.asignatura || null)
const texto = ref('')
const version = ref(null)
const todos = ref(false)
const oas = ref([])
const loading = ref(false)

const hoy = new Date().toISOString().slice(0, 10)
const vigente = oa => !oa.vigente_hasta || oa.vigente_hasta >= hoy

const etapas = computed(() => {
  const grupos = []
  for (const n of cat.value.niveles) {
    let g = grupos.find(x => x.nombre === n.etapa)
    if (!g) grupos.push(g = { nombre: n.etapa, niveles: [] })
    g.niveles.push(n)
  }
  return grupos
})
const nivelesConOa = computed(() => cat.value.niveles.filter(n => n.total).length)
const totalNivel = computed(() => cat.value.niveles.find(n => n.nivel === nivel.value)?.total || 0)

// Asignaturas con OA vigentes en el nivel (el catálogo tiene ~100: diferenciados y especialidades TP)
const asignaturasNivel = computed(() => cat.value.asignaturas
  .map(a => ({ ...a, total: cat.value.conteo.find(c => c.nivel === nivel.value && c.asignatura_id === a.id)?.total || 0 }))
  .filter(a => a.total || a.id === asignatura.value)
  .sort((a, b) => a.nombre.localeCompare(b.nombre)))

function cargarCatalogos () {
  return proxy.$axios.get('curriculum/catalogos')
    .then(res => {
      cat.value = res.data
      if (!cat.value.niveles.some(n => n.nivel === nivel.value)) nivel.value = cat.value.niveles[0]?.nivel
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
}

function cargar () {
  loading.value = true
  return proxy.$axios.get('curriculum/oa', {
    params: { nivel: nivel.value, asignatura_id: asignatura.value || undefined, q: texto.value || undefined, version: version.value || undefined, todos: todos.value ? 1 : undefined },
  })
    .then(res => { oas.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { loading.value = false })
}

function recargar () {
  return Promise.all([cargarCatalogos(), cargar()])
}

function elegirNivel (n) {
  nivel.value = n
  guardarUi()
  cargar()
}

function elegirAsignatura (id) {
  asignatura.value = id
  guardarUi()
  cargar()
}

// Enlace a planificación / evaluación con el OA preseleccionado
function enlace (ruta, oa) {
  return { path: ruta, query: { oa: oa.id, nivel: oa.nivel, asignatura_id: oa.asignatura_id } }
}

// ── Formulario ──
const dialog = ref(false)
const saving = ref(false)
const form = ref({})

const vacio = () => ({
  codigo: '', nivel: nivel.value, asignatura_id: asignatura.value, unidad: '', texto: '', contenidos: '',
  habilidades: [], actitudes: [], indicadores: [], instrumentos: [], recursos: [],
  estrategias: '', inicio: '', desarrollo: '', cierre: '', tiempo_espacio: '',
  fuente: '', fuente_url: '', version_curricular: cat.value.versiones.at(-1) || '', vigente_desde: '', vigente_hasta: '', es_demo: false,
})

function aForm (oa) {
  const f = { ...vacio() }
  for (const k of Object.keys(f)) f[k] = oa[k] ?? f[k]
  for (const l of LISTAS) f[l.campo] = [...(oa[l.campo] || [])]
  return { ...f, id: oa.id }
}

function nuevo () {
  form.value = vacio()
  dialog.value = true
}

function editar (oa) {
  form.value = aForm(oa)
  dialog.value = true
}

function duplicar (oa) {
  const f = aForm(oa)
  delete f.id
  form.value = { ...f, version_curricular: '', vigente_desde: '', vigente_hasta: '' }
  dialog.value = true
}

function guardar () {
  saving.value = true
  const { id, ...datos } = form.value
  for (const k of ['vigente_desde', 'vigente_hasta', 'fuente_url']) datos[k] = datos[k] || null
  const req = id ? proxy.$axios.put(`curriculum/oa/${id}`, datos) : proxy.$axios.post('curriculum/oa', datos)
  req
    .then(res => {
      proxy.$alert.success(id ? `OA ${res.data.codigo} actualizado` : `OA ${res.data.codigo} creado`)
      dialog.value = false
      recargar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { saving.value = false })
}

const enUso = oa => (oa.planificaciones_count || 0) + (oa.instrumentos_count || 0) + (oa.recursos_count || 0) > 0

function retirar (oa, reactivar = false) {
  const pregunta = reactivar
    ? `¿Reactivar el OA ${oa.codigo}? Volverá a ofrecerse en planificación y evaluaciones.`
    : `¿Retirar el OA ${oa.codigo}? Dejará de ofrecerse en planificación y evaluaciones; lo ya creado con él se conserva.`
  proxy.$alert.confirm(pregunta).onOk(() => {
    proxy.$axios.post(`curriculum/oa/${oa.id}/retirar`, { reactivar })
      .then(res => {
        proxy.$alert.success(res.data.message)
        recargar()
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

function eliminar (oa) {
  proxy.$alert.confirm(`¿Eliminar el OA ${oa.codigo} del catálogo?`).onOk(() => {
    proxy.$axios.delete(`curriculum/oa/${oa.id}`)
      .then(res => {
        proxy.$alert.success(res.data.message)
        recargar()
      })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

// ── Importación ──
const importar = ref({ abierto: false, archivo: null, filas: [], errores: [], enviando: false })

function abrirImportar () {
  importar.value = { abierto: true, archivo: null, filas: [], errores: [], enviando: false }
}

function leerArchivo (archivo) {
  importar.value.filas = []
  importar.value.errores = []
  if (!archivo) return
  archivo.text()
    .then(txt => {
      const json = JSON.parse(txt)
      const filas = Array.isArray(json) ? json : json.oas
      if (!Array.isArray(filas)) throw new Error('El archivo debe contener un arreglo de OA')
      importar.value.filas = filas
    })
    .catch(e => { importar.value.errores = [e instanceof SyntaxError ? 'El archivo no es un JSON válido.' : e.message] })
}

function enviarImportacion () {
  importar.value.enviando = true
  importar.value.errores = []
  proxy.$axios.post('curriculum/oa/importar', { oas: importar.value.filas })
    .then(res => {
      proxy.$alert.success(res.data.message)
      importar.value.abierto = false
      recargar()
    })
    .catch(err => {
      importar.value.errores = err?.response?.data?.errores || [mensajeError(err)]
    })
    .finally(() => { importar.value.enviando = false })
}

function descargarEjemplo () {
  const ejemplo = [{
    level: '2° Medio', subject: 'Matemática', oa_code: 'MA2M-OA01',
    oa_statement: 'Texto oficial del objetivo de aprendizaje.', unit_or_axis: 'Números',
    skills: ['Resolver problemas', 'Argumentar'], attitudes: ['Perseverancia'], indicators: ['Indicador de evaluación'],
    source_url: 'https://www.curriculumnacional.cl/', curriculum_version: 'Bases 2019',
    effective_from: '2020-01-01', effective_to: null, source_hash: null,
  }]
  const url = URL.createObjectURL(new Blob([JSON.stringify(ejemplo, null, 2)], { type: 'application/json' }))
  const a = document.createElement('a')
  a.href = url
  a.download = 'catalogo_oa_ejemplo.json'
  a.click()
  URL.revokeObjectURL(url)
}

onMounted(recargar)
</script>

<style scoped>
.sourceStrip { border: 1px dashed #b9cad7; background: #f5f9fc; padding: 10px 12px; border-radius: 12px; font-size: 12px; color: #587184; line-height: 1.5 }

.kpiLine { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px }
.kpiMini { position: relative; background: #fff; border: 1px solid #d9e3ec; border-radius: 15px; padding: 13px 14px }
.kpiMini b { display: block; font-size: 22px; font-weight: 800 }
.kpiMini small { color: #6d8090; font-size: 11px }
.kpiIcon { position: absolute; right: 14px; top: 14px; color: var(--role); font-size: 16px; opacity: .8 }

.etapa { margin-top: 14px }
.etapaLabel { font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #6d8090; margin-bottom: 6px }
.curriculumMap { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 9px }
.levelChip {
  border: 1px solid #dce5ec; background: #fff; border-radius: 12px; padding: 10px; cursor: pointer;
  text-align: left; font: inherit; color: inherit;
}
.levelChip:hover, .levelChip.active { border-color: var(--role); box-shadow: 0 0 0 2px color-mix(in srgb, var(--role) 14%, transparent) }
.levelChip.active { background: var(--roleSoft) }
.levelChip b { display: block; font-size: 12px }
.levelChip small { font-size: 10px; color: #6d8090 }

.asigPills { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px }
.asigPill {
  display: inline-flex; align-items: center; gap: 6px; border: 1px solid #dce5ec; background: #fff; border-radius: 999px;
  padding: 4px 10px 4px 5px; font: inherit; font-size: 12px; cursor: pointer; color: #15324a;
}
.asigPill:first-child { padding-left: 10px }
.asigPill span { font-size: 10px; font-weight: 800; background: #eef3f7; border-radius: 999px; padding: 1px 7px; color: #486477 }
.asigPill.vacia { opacity: .6 }
.asigPill.active { border-color: var(--role); background: var(--roleSoft); opacity: 1 }
.asigPill.active span { background: var(--role); color: #fff }

.filtros { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px }
.lista { display: grid; gap: 10px }
.vacio { display: grid; place-items: center; gap: 8px; text-align: center; padding: 34px 10px; color: #6d8090 }

.oaCard { border: 1px solid #dbe5ec; border-radius: 13px; padding: 14px; background: #fff }
.oaCard.retirado { background: #fafbfc; opacity: .8 }
.oaHead { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; flex-wrap: wrap }
.oaTitulo { min-width: 0; flex: 1 }
.oaCode { display: flex; align-items: center; gap: 7px; font-size: 11px; font-weight: 900; color: #1f6fb2 }
.oaCard h4 { margin: 6px 0; font-size: 15px; font-weight: 800; line-height: 1.3 }
.oaBadges { display: flex; flex-wrap: wrap; gap: 5px }
.oaActions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap }
.oaActions .q-btn--outline { border-radius: 9px; font-weight: 700 }
.btnEditar { color: #0f4f85 }
.btnEliminar { color: #bd1f2d }
.btnRetirar { color: #8a520c }
.oaTexto { font-size: 13px; color: #33536d; line-height: 1.55; margin: 10px 0 }
.pedagogyGrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 8px }
.pedagogyGrid.dos { grid-template-columns: 1fr 1fr }
.pedBox { border: 1px solid #e3eaf0; background: #f8fbfd; border-radius: 11px; padding: 10px }
.pedBox h4 { font-size: 11px; font-weight: 800; margin: 0 0 5px; color: #15324a }
.pedBox p, .pedBox li { font-size: 12px; color: #5f7485; line-height: 1.5; margin: 0 }
.pedBox ul { margin: 0; padding-left: 17px }
.sinDato { color: #b56b12 !important }
.phaseGrid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px }
.phase { border-left: 4px solid var(--role); background: #f8fbfd; border-radius: 0 10px 10px 0; padding: 9px 11px }
.phase b { font-size: 11px }
.phase p { font-size: 12px; color: #5f7485; margin: 3px 0 0; line-height: 1.45 }
.oaFuente { margin-top: 10px; font-size: 11px; color: #6d8090; display: flex; gap: 6px; align-items: center; flex-wrap: wrap }
.oaFuente a { color: #0f4f85; font-weight: 700 }

.badge.warn { background: #fff5e7; border-color: #f1d7ad; color: #8a520c }
.badge.violeta { background: #f0edff; border-color: #d9d1fb; color: #5b3fb0 }

.dialogo { width: 860px; max-width: 96vw }
.formScroll { max-height: 70vh; overflow-y: auto }
.seccion { font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #bd1f2d; margin: 14px 0 8px }
.seccion:first-child { margin-top: 0 }
.formGrid.tres { grid-template-columns: repeat(3, 1fr) }
.errores { margin: 10px 0 0; padding: 10px 10px 10px 26px; background: #fdeef0; border: 1px solid #f3c9cf; border-radius: 10px; color: #9b1c27; font-size: 12px }
code { background: #eef3f7; border-radius: 5px; padding: 0 4px; font-size: 11px }

@media (max-width: 1200px) {
  .pedagogyGrid { grid-template-columns: 1fr 1fr }
  .kpiLine { grid-template-columns: repeat(2, 1fr) }
}
@media (max-width: 720px) {
  .pedagogyGrid, .pedagogyGrid.dos, .phaseGrid, .formGrid.tres { grid-template-columns: 1fr }
  .oaActions { width: 100% }
}
</style>
