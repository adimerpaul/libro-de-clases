<template>
  <q-page class="q-pa-sm lab-admin">

    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <!-- Cabecera compacta -->
      <div class="row items-center no-wrap q-mb-xs">
        <q-btn flat dense round icon="arrow_back" color="grey-7" class="q-mr-sm" to="/laboratorio">
          <q-tooltip>Volver a laboratorio</q-tooltip>
        </q-btn>
        <q-icon name="biotech" color="teal" size="20px" class="q-mr-xs" />
        <div class="col ellipsis">
          <div class="text-subtitle1 text-weight-bold ellipsis">{{ producto.nombre || 'Cargando…' }}</div>
          <div class="lab-sub text-grey-6">
            {{ producto.codigo || 'S/C' }} · Bs {{ money(producto.precio) }} ·
            {{ datos.length }} dato(s) · {{ conFormula }} con fórmula
          </div>
        </div>
        <q-space />
        <q-btn v-if="canEditar" dense unelevated color="positive" icon="add" label="Nuevo dato"
               no-caps padding="4px 12px" @click="nuevoDato" />
        <q-btn flat dense round icon="refresh" color="primary" class="q-ml-xs"
               :loading="adminLoading" @click="cargarConfiguracion">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>

      <q-card flat bordered>
        <q-markup-table dense flat separator="horizontal" class="full-width tabla-compacta">
          <thead>
            <tr class="bg-grey-1 text-grey-7 text-uppercase">
              <th style="width:28px"></th>
              <th class="text-left" style="width:96px">Opciones</th>
              <th class="text-left">Nombre</th>
              <th class="text-left">Variable</th>
              <th class="text-left">Unidad</th>
              <th class="text-left">Método</th>
              <th class="text-left">Muestra</th>
              <th class="text-left">Valores posibles</th>
              <th class="text-left">Rangos de referencia</th>
              <th class="text-left">Fórmula aplicada</th>
              <th class="text-center">Visible</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="adminLoading">
              <td colspan="11" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
            </tr>
            <tr v-else-if="!datos.length">
              <td colspan="11" class="text-center text-grey-5 q-pa-md">
                Sin datos configurados. Use «Nuevo dato» para agregar el primero.
              </td>
            </tr>
            <tr v-else v-for="dato in datos" :key="dato.id"
                :draggable="canEditar"
                :class="{ 'laboratorio-row--dragging': draggedDatoId === dato.id }"
                @dragstart="iniciarArrastreDato($event, dato)"
                @dragover.prevent
                @drop.prevent="soltarDato(dato)"
                @dragend="finalizarArrastreDato">
              <td class="text-center">
                <q-icon v-if="canEditar" name="drag_indicator" color="grey-6" size="18px" class="cursor-grab">
                  <q-tooltip>Arrastre para cambiar el orden</q-tooltip>
                </q-icon>
              </td>
              <td class="q-pa-xs">
                <q-btn-dropdown dense unelevated rounded color="primary" label="Opciones" no-caps size="10px">
                  <q-list dense style="min-width:190px">
                    <q-item v-if="canEditar" clickable v-close-popup @click="editarDato(dato)">
                      <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                      <q-item-section><q-item-label>Modificar dato</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEditar" clickable v-close-popup @click="administrarFormulaDato(dato)">
                      <q-item-section avatar><q-icon name="functions" color="deep-purple" /></q-item-section>
                      <q-item-section>
                        <q-item-label>{{ dato.formula ? 'Modificar fórmula' : 'Agregar fórmula' }}</q-item-label>
                      </q-item-section>
                    </q-item>
                    <q-item v-if="canEditar && dato.formula" clickable v-close-popup @click="eliminarFormula(dato.formula)">
                      <q-item-section avatar><q-icon name="settings_power" color="orange-9" /></q-item-section>
                      <q-item-section><q-item-label>Quitar fórmula</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEditar" clickable v-close-popup @click="eliminarDato(dato)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar dato</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td class="text-weight-medium">{{ dato.nombre }}</td>
              <td><q-badge outline color="primary">{{ dato.nombre_variable }}</q-badge></td>
              <td>{{ dato.unidad || '—' }}</td>
              <td>{{ dato.metodo || '—' }}</td>
              <td>{{ dato.muestra || '—' }}</td>
              <td class="lab-opciones">
                <template v-if="dato.opciones?.length">
                  <q-badge v-for="opcion in dato.opciones" :key="opcion.id" class="q-mr-xs q-mb-xs"
                           :color="opcion.valor === dato.valor_defecto ? 'primary' : 'grey-4'"
                           :text-color="opcion.valor === dato.valor_defecto ? 'white' : 'grey-9'">
                    {{ opcion.valor }}
                    <q-icon v-if="opcion.valor === dato.valor_defecto" name="star" size="10px" class="q-ml-xs" />
                  </q-badge>
                </template>
                <span v-else-if="dato.valor_defecto" class="text-grey-8">
                  Por defecto: <b>{{ dato.valor_defecto }}</b>
                </span>
                <span v-else class="text-grey-6">TEXTO LIBRE</span>
              </td>
              <td class="lab-rangos">
                <div v-if="dato.rango_referencia" class="html-rango" v-html="dato.rango_referencia_html" />
                <template v-else>—</template>
              </td>
              <td>
                <code v-if="dato.formula" class="text-deep-purple">{{ dato.formula.formula }}</code>
                <span v-else class="text-grey-6">INGRESO MANUAL</span>
              </td>
              <td class="text-center">
                <q-badge :color="dato.visible ? 'positive' : 'grey'">{{ dato.visible ? 'SÍ' : 'NO' }}</q-badge>
              </td>
            </tr>
          </tbody>
        </q-markup-table>
      </q-card>

      <div class="lab-sub text-grey-6 q-mt-xs q-px-xs">
        Los datos y fórmulas configurados aquí son la base para registrar resultados cuando este laboratorio
        se solicita a un paciente. El orden de la tabla es el orden de impresión.
      </div>

      <!-- ══ MENSAJES DE VALIDACIÓN ═════════════════════════════════ -->
      <div class="row items-center no-wrap q-mt-sm q-mb-xs">
        <q-icon name="rule" color="orange-9" size="18px" class="q-mr-xs" />
        <div>
          <div class="text-subtitle2 text-weight-bold">Mensajes de validación</div>
          <div class="lab-sub text-grey-6">
            Reglas que deben cumplirse al registrar resultados. Ej.: <code>a + b</code> = <b>100</b>.
          </div>
        </div>
        <q-space />
        <q-btn v-if="canEditar" dense unelevated color="orange-9" icon="add" label="Nuevo mensaje"
               no-caps padding="4px 12px" @click="nuevaValidacion" />
      </div>

      <q-card flat bordered>
        <q-markup-table dense flat separator="horizontal" class="full-width tabla-compacta">
          <thead>
            <tr class="bg-grey-1 text-grey-7 text-uppercase">
              <th class="text-left" style="width:96px">Opciones</th>
              <th class="text-left">Condición que debe cumplirse</th>
              <th class="text-left">Mensaje si no se cumple</th>
              <th class="text-center">Activo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!validaciones.length">
              <td colspan="4" class="text-center text-grey-5 q-pa-md">
                Sin mensajes configurados. Use «Nuevo mensaje» para agregar una regla.
              </td>
            </tr>
            <tr v-else v-for="validacion in validaciones" :key="validacion.id">
              <td class="q-pa-xs">
                <q-btn-dropdown dense unelevated rounded color="orange-9" label="Opciones" no-caps size="10px">
                  <q-list dense style="min-width:180px">
                    <q-item v-if="canEditar" clickable v-close-popup @click="editarValidacion(validacion)">
                      <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                      <q-item-section><q-item-label>Modificar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEditar" clickable v-close-popup @click="eliminarValidacion(validacion)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td><code class="text-deep-purple">{{ describirValidacion(validacion) }}</code></td>
              <td>{{ validacion.mensaje }}</td>
              <td class="text-center">
                <q-badge :color="validacion.activo ? 'positive' : 'grey'">{{ validacion.activo ? 'SÍ' : 'NO' }}</q-badge>
              </td>
            </tr>
          </tbody>
        </q-markup-table>
      </q-card>
    </template>

    <!-- DIALOG DATO -->
    <q-dialog v-model="datoDialog" persistent>
      <q-card style="width:min(94vw,620px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="fact_check" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">{{ datoForm.id ? 'Modificar' : 'Nuevo' }} dato</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardarDato">
          <q-card-section class="row q-col-gutter-sm q-pa-sm">
            <div class="col-12 col-sm-7">
              <q-input v-model="datoForm.nombre" v-uppercase dense outlined label="Nombre *"
                       :rules="[required]" @update:model-value="actualizarVariableDato" />
            </div>
            <div class="col-12 col-sm-5">
              <q-input v-model="datoForm.nombre_variable" dense outlined label="Variable *"
                       hint="Se numera sola si el nombre ya existe"
                       :rules="[required, validVariable, variableDisponible]" />
            </div>
            <div class="col-8 col-sm-4">
              <q-input v-model="datoForm.unidad" dense outlined label="Unidad" hint="mg/dL, %, U/L..." />
            </div>
            <div class="col-4 col-sm-4"><q-toggle v-model="datoForm.visible" dense label="Visible" /></div>
            <div class="col-12 col-sm-6">
              <q-input v-model="datoForm.metodo" v-uppercase dense outlined label="Método"
                       hint="Ej.: ELISA, CLIA, COLORIMÉTRICO" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="datoForm.muestra" v-uppercase dense outlined label="Muestra"
                       hint="Ej.: SUERO, PLASMA, ORINA" />
            </div>
            <div class="col-12">
              <div class="text-caption text-grey-7 q-mb-xs">Rangos de referencia</div>
              <q-editor v-model="datoForm.rango_referencia" min-height="110px" max-height="260px" dense
                        placeholder="Ej.: HOMBRES: 13–17 | MUJERES: 12–15"
                        :toolbar="[['bold', 'italic', 'underline', 'strike'], ['subscript', 'superscript'], ['left', 'center', 'right', 'justify'], ['unordered', 'ordered'], ['removeFormat'], ['undo', 'redo']]" />
            </div>

            <!-- Lista de valores posibles -->
            <div class="col-12">
              <q-separator class="q-mb-sm" />
              <div class="text-caption text-weight-bold text-grey-8">Valores posibles</div>
              <div class="lab-sub text-grey-6 q-mb-xs">
                Si deja la lista vacía, el resultado se escribe libremente. Ej.: COLOR → AMARILLO, BLANCO, ÁMBAR.
              </div>

              <div class="row q-col-gutter-xs items-start">
                <div class="col">
                  <q-input v-model="opcionNueva" v-uppercase dense outlined label="Agregar valor"
                           :error="!!opcionError" :error-message="opcionError"
                           @keydown.enter.prevent="agregarOpcion" />
                </div>
                <div class="col-auto">
                  <q-btn dense unelevated color="primary" icon="add" no-caps label="Agregar"
                         size="11px" padding="6px 10px" @click="agregarOpcion" />
                </div>
              </div>

              <div v-if="datoForm.opciones?.length" class="q-mt-xs">
                <q-chip v-for="(opcion, indice) in datoForm.opciones" :key="opcion"
                        dense removable size="11px"
                        :color="opcion === datoForm.valor_defecto ? 'primary' : 'grey-3'"
                        :text-color="opcion === datoForm.valor_defecto ? 'white' : 'grey-9'"
                        @remove="quitarOpcion(indice)">
                  {{ opcion }}
                </q-chip>
              </div>
            </div>

            <!-- Valor por defecto: lista cerrada si hay opciones, texto si no -->
            <div class="col-12 col-sm-6">
              <q-select v-if="datoForm.opciones?.length" v-model="datoForm.valor_defecto"
                        dense outlined clearable label="Valor por defecto"
                        :options="datoForm.opciones"
                        hint="Se precarga al registrar el resultado" />
              <q-input v-else v-model="datoForm.valor_defecto" v-uppercase dense outlined
                       label="Valor por defecto" hint="Se precarga al registrar el resultado" />
            </div>
          </q-card-section>
          <q-card-actions align="right" class="q-pa-sm">
            <q-btn flat dense label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" dense padding="4px 14px" color="primary" icon="save" label="Guardar"
                   no-caps :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- DIALOG MENSAJE DE VALIDACIÓN -->
    <q-dialog v-model="validacionDialog" persistent>
      <q-card style="width:min(94vw,660px)">
        <q-card-section class="row items-center bg-orange-9 text-white q-py-sm">
          <q-icon name="rule" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">
            {{ validacionForm.id ? 'Modificar' : 'Nuevo' }} mensaje de validación
          </span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardarValidacion">
          <q-card-section class="q-pa-sm">
            <div class="row q-col-gutter-sm">
              <div class="col-12">
                <q-input v-model="validacionForm.expresion" dense outlined label="Expresión *"
                         hint="Ej.: neutrofilos + linfocitos" :rules="[required]" />
                <div class="q-mt-xs">
                  <span class="lab-sub text-grey-7 q-mr-sm">Variables:</span>
                  <q-chip v-for="variable in variablesDatos" :key="variable" dense clickable size="sm"
                          color="deep-purple-1" text-color="deep-purple"
                          @click="insertarVariableValidacion(variable)">
                    {{ variable }}
                  </q-chip>
                </div>
                <div v-if="validacionError" class="text-negative lab-sub">{{ validacionError }}</div>
              </div>
              <div class="col-12 col-sm-4">
                <q-select v-model="validacionForm.operador" dense outlined label="Debe ser *"
                          :options="operadores" emit-value map-options />
              </div>
              <div class="col-6 col-sm-4">
                <q-input v-model.number="validacionForm.valor" dense outlined type="number" step="0.01"
                         :label="validacionForm.operador === 'entre' ? 'Desde *' : 'Valor *'" :rules="[required]" />
              </div>
              <div class="col-6 col-sm-4" v-if="validacionForm.operador === 'entre'">
                <q-input v-model.number="validacionForm.valor_hasta" dense outlined type="number" step="0.01"
                         label="Hasta *" :rules="[required]" />
              </div>
              <div class="col-12">
                <q-input v-model="validacionForm.mensaje" v-uppercase dense outlined label="Mensaje si no se cumple *"
                         hint="Ej.: LA SUMA DEL DIFERENCIAL DEBE SER 100%" :rules="[required]" />
              </div>
              <div class="col-12">
                <q-toggle v-model="validacionForm.activo" dense label="Activo" />
                <div class="lab-sub text-grey-7 q-mt-xs">
                  Al registrar el laboratorio se evalúa la expresión con los valores ingresados; si no cumple la
                  condición se muestra este mensaje. Vista previa:
                  <code class="text-deep-purple">{{ describirValidacion(validacionForm) }}</code>
                </div>
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right" class="q-pa-sm">
            <q-btn flat dense label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" dense padding="4px 14px" color="orange-9" icon="save" label="Guardar"
                   no-caps :disable="!!validacionError" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- DIALOG FÓRMULA -->
    <q-dialog v-model="formulaDialog" persistent>
      <q-card style="width:min(94vw,700px)">
        <q-card-section class="row items-center bg-deep-purple text-white q-py-sm">
          <q-icon name="functions" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">{{ formulaForm.id ? 'Modificar' : 'Nueva' }} fórmula</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardarFormula">
          <q-card-section class="q-pa-sm">
            <div class="text-caption q-mb-xs">
              Resultado: <strong>{{ formulaDato?.nombre }}</strong>
              (<code>{{ formulaDato?.nombre_variable }}</code>)
            </div>
            <q-input v-model="formulaForm.formula" dense outlined label="Fórmula *"
                     hint="Ej.: colesterol_total - hdl_colesterol" :rules="[required]" />
            <div class="q-mt-xs">
              <span class="lab-sub text-grey-7 q-mr-sm">Variables disponibles:</span>
              <q-chip v-for="variable in variablesDisponibles" :key="variable" dense clickable size="sm"
                      color="deep-purple-1" text-color="deep-purple" @click="insertarVariable(variable)">
                {{ variable }}
              </q-chip>
            </div>
            <div v-if="formulaError" class="text-negative lab-sub">{{ formulaError }}</div>
            <div class="lab-sub text-grey-7 q-mt-xs">
              Ejemplos: <code>(hematocrito * 10) / globulos_rojos</code> ·
              <code>(neutrofilos * globulos_blancos) / 100</code>
            </div>
          </q-card-section>
          <q-card-actions align="right" class="q-pa-sm">
            <q-btn flat dense label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" dense padding="4px 14px" color="deep-purple" icon="save" label="Guardar"
                   no-caps :disable="!!formulaError" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'

const { proxy } = getCurrentInstance()

const productoId = proxy.$route.params.id
const producto = ref({})
const datos = ref([])
const formulas = ref([])
const adminLoading = ref(false)
const saving = ref(false)
const datoDialog = ref(false)
const datoForm = ref({})
const opcionNueva = ref('')
const opcionError = ref('')
const formulaDialog = ref(false)
const formulaForm = ref({})
const formulaDato = ref(null)
const formulaError = ref('')
const draggedDatoId = ref(null)
const validaciones = ref([])
const validacionDialog = ref(false)
const validacionForm = ref({})
const validacionError = ref('')

const operadores = [
  { label: 'Igual a (=)', value: '=' },
  { label: 'Distinto de (≠)', value: '!=' },
  { label: 'Mayor que (>)', value: '>' },
  { label: 'Mayor o igual (≥)', value: '>=' },
  { label: 'Menor que (<)', value: '<' },
  { label: 'Menor o igual (≤)', value: '<=' },
  { label: 'Entre', value: 'entre' },
]

const canVer = computed(() => proxy.$store.hasPermission('Ver Productos'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Productos'))
const conFormula = computed(() => datos.value.filter(item => item.formula).length)
const variablesDisponibles = computed(() => datos.value
  .filter(item => item.id !== formulaDato.value?.id)
  .map(item => item.nombre_variable))
const variablesDatos = computed(() => datos.value.map(item => item.nombre_variable))

watch(() => proxy.$store.isLogged, logged => {
  if (logged && canVer.value) cargarConfiguracion()
}, { immediate: true })

watch(() => formulaForm.value.formula, validarFormula)

function required (value) {
  return value !== null && value !== undefined && value !== '' || 'Campo requerido'
}
function validVariable (value) {
  return /^[a-z][a-z0-9_]*$/.test(value || '') || 'Use minúsculas, números y guion bajo'
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
function toVariable (value) {
  return (value || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
}
// Variables ya usadas en este producto (datos + resultados de fórmulas)
function variablesTomadas (excluirId = null) {
  return new Set([
    ...datos.value
      .filter(item => item.id !== excluirId)
      .map(item => String(item.nombre_variable || '').toLowerCase()),
    ...formulas.value
      .filter(item => item.producto_laboratorio_dato_id !== excluirId)
      .map(item => String(item.nombre_variable || '').toLowerCase()),
  ])
}
// Si el nombre ya existe, agrega 2, 3, 4… hasta encontrar una variable libre
function variableUnica (base, excluirId = null) {
  if (!base) return ''
  const tomadas = variablesTomadas(excluirId)
  if (!tomadas.has(base)) return base
  let sufijo = 2
  while (tomadas.has(`${base}${sufijo}`)) sufijo++
  return `${base}${sufijo}`
}
function variableDisponible (value) {
  const variable = String(value || '').toLowerCase()
  if (!variable) return true
  return !variablesTomadas(datoForm.value.id || null).has(variable)
    || 'Esa variable ya existe en este laboratorio'
}

async function cargarConfiguracion () {
  adminLoading.value = true
  try {
    const { data } = await proxy.$axios.get(`productos/${productoId}/laboratorio-configuracion`)
    producto.value = data
    datos.value = data.laboratorio_datos || []
    formulas.value = data.laboratorio_formulas || []
    validaciones.value = data.laboratorio_validaciones || []
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar la configuración')
  } finally {
    adminLoading.value = false
  }
}

function nuevoDato () {
  datoForm.value = {
    nombre: '', nombre_variable: '', unidad: '', metodo: '', muestra: '', rango_referencia: '',
    valor_defecto: null, opciones: [], visible: true,
  }
  opcionNueva.value = ''
  opcionError.value = ''
  datoDialog.value = true
}
function editarDato (dato) {
  datoForm.value = {
    ...dato,
    // El editor trabaja con HTML: los rangos antiguos en texto plano llegan convertidos.
    rango_referencia: dato.rango_referencia_html || '',
    // El backend devuelve objetos {id, valor}; el formulario trabaja con texto.
    opciones: (dato.opciones || []).map(o => o.valor),
  }
  opcionNueva.value = ''
  opcionError.value = ''
  datoDialog.value = true
}

function agregarOpcion () {
  const valor = (opcionNueva.value || '').trim().toUpperCase()
  opcionError.value = ''
  if (!valor) return
  if (datoForm.value.opciones?.includes(valor)) {
    opcionError.value = 'Ese valor ya está en la lista'
    return
  }
  if (!datoForm.value.opciones) datoForm.value.opciones = []
  datoForm.value.opciones.push(valor)
  opcionNueva.value = ''
}
function quitarOpcion (indice) {
  const [quitado] = datoForm.value.opciones.splice(indice, 1)
  // Si se quita el valor que era el predeterminado, deja de serlo.
  if (datoForm.value.valor_defecto === quitado) datoForm.value.valor_defecto = null
}
function actualizarVariableDato () {
  if (!datoForm.value.id) {
    datoForm.value.nombre_variable = variableUnica(toVariable(datoForm.value.nombre))
  }
}
async function guardarDato () {
  saving.value = true
  try {
    if (datoForm.value.id) {
      await proxy.$axios.put(`producto-laboratorio-datos/${datoForm.value.id}`, datoForm.value)
    } else {
      await proxy.$axios.post(`productos/${productoId}/laboratorio-datos`, datoForm.value)
    }
    datoDialog.value = false
    proxy.$alert.success('Dato guardado')
    await cargarConfiguracion()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo guardar')
  } finally {
    saving.value = false
  }
}
function eliminarDato (dato) {
  proxy.$alert.dialog(`¿Eliminar el dato "${dato.nombre}"?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`producto-laboratorio-datos/${dato.id}`)
      proxy.$alert.success('Dato eliminado')
      await cargarConfiguracion()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar')
    }
  })
}

function iniciarArrastreDato (event, dato) {
  draggedDatoId.value = dato.id
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', String(dato.id))
}
async function soltarDato (destino) {
  const origenIndex = datos.value.findIndex(item => item.id === draggedDatoId.value)
  const destinoIndex = datos.value.findIndex(item => item.id === destino.id)
  if (origenIndex < 0 || destinoIndex < 0 || origenIndex === destinoIndex) return

  const ordenAnterior = [...datos.value]
  const nuevosDatos = [...datos.value]
  const [movido] = nuevosDatos.splice(origenIndex, 1)
  nuevosDatos.splice(destinoIndex, 0, movido)
  datos.value = nuevosDatos

  try {
    await proxy.$axios.put(`productos/${productoId}/laboratorio-datos/orden`, {
      ids: nuevosDatos.map(item => item.id),
    })
  } catch (error) {
    datos.value = ordenAnterior
    proxy.$alert.error(error.response?.data?.message || 'No se pudo actualizar el orden')
  } finally {
    draggedDatoId.value = null
  }
}
function finalizarArrastreDato () {
  draggedDatoId.value = null
}

function administrarFormulaDato (dato) {
  formulaDato.value = dato
  formulaForm.value = dato.formula ? { ...dato.formula } : { formula: '' }
  formulaError.value = ''
  formulaDialog.value = true
}
function insertarVariable (variable) {
  formulaForm.value.formula = `${formulaForm.value.formula || ''} ${variable}`.trim()
}
function validarFormula () {
  const expression = (formulaForm.value.formula || '').trim()
  formulaError.value = ''
  if (!expression) return
  const tokens = expression.replace(/[()]/g, ' ').split(/[\s+\-*/]+/)
    .filter(token => token && !/^\d+(\.\d+)?$/.test(token))
  const unknown = [...new Set(tokens.filter(token => !variablesDisponibles.value.includes(token)))]
  if (unknown.length) formulaError.value = `Variables desconocidas: ${unknown.join(', ')}`
}
async function guardarFormula () {
  validarFormula()
  if (formulaError.value) return
  saving.value = true
  try {
    if (formulaForm.value.id) {
      await proxy.$axios.put(`producto-laboratorio-formulas/${formulaForm.value.id}`, {
        formula: formulaForm.value.formula,
      })
    } else {
      await proxy.$axios.post(`producto-laboratorio-datos/${formulaDato.value.id}/formula`, {
        formula: formulaForm.value.formula,
      })
    }
    formulaDialog.value = false
    proxy.$alert.success('Fórmula guardada')
    await cargarConfiguracion()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo guardar')
  } finally {
    saving.value = false
  }
}
function eliminarFormula (formula) {
  proxy.$alert.dialog('¿Quitar la fórmula de este dato?').onOk(async () => {
    try {
      await proxy.$axios.delete(`producto-laboratorio-formulas/${formula.id}`)
      proxy.$alert.success('Fórmula eliminada')
      await cargarConfiguracion()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar')
    }
  })
}
// ── Mensajes de validación ─────────────────────────────────────
watch(() => validacionForm.value.expresion, validarExpresionValidacion)

function describirValidacion (validacion) {
  if (!validacion?.expresion) return '—'
  const simbolos = { '=': '=', '!=': '≠', '>': '>', '>=': '≥', '<': '<', '<=': '≤' }
  return validacion.operador === 'entre'
    ? `${validacion.expresion} entre ${validacion.valor} y ${validacion.valor_hasta}`
    : `${validacion.expresion} ${simbolos[validacion.operador] || validacion.operador} ${validacion.valor}`
}
function nuevaValidacion () {
  validacionForm.value = { expresion: '', operador: '=', valor: null, valor_hasta: null, mensaje: '', activo: true }
  validacionError.value = ''
  validacionDialog.value = true
}
function editarValidacion (validacion) {
  validacionForm.value = { ...validacion }
  validacionError.value = ''
  validacionDialog.value = true
}
function insertarVariableValidacion (variable) {
  validacionForm.value.expresion = `${validacionForm.value.expresion || ''} ${variable}`.trim()
}
function validarExpresionValidacion () {
  const expression = (validacionForm.value.expresion || '').trim()
  validacionError.value = ''
  if (!expression) return
  const tokens = expression.replace(/[()]/g, ' ').split(/[\s+\-*/]+/)
    .filter(token => token && !/^\d+(\.\d+)?$/.test(token))
  const unknown = [...new Set(tokens.filter(token => !variablesDatos.value.includes(token)))]
  if (unknown.length) validacionError.value = `Variables desconocidas: ${unknown.join(', ')}`
}
async function guardarValidacion () {
  validarExpresionValidacion()
  if (validacionError.value) return
  saving.value = true
  try {
    if (validacionForm.value.id) {
      await proxy.$axios.put(`producto-laboratorio-validaciones/${validacionForm.value.id}`, validacionForm.value)
    } else {
      await proxy.$axios.post(`productos/${productoId}/laboratorio-validaciones`, validacionForm.value)
    }
    validacionDialog.value = false
    proxy.$alert.success('Mensaje guardado')
    await cargarConfiguracion()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo guardar')
  } finally {
    saving.value = false
  }
}
function eliminarValidacion (validacion) {
  proxy.$alert.dialog(`¿Eliminar el mensaje "${validacion.mensaje}"?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`producto-laboratorio-validaciones/${validacion.id}`)
      proxy.$alert.success('Mensaje eliminado')
      await cargarConfiguracion()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar')
    }
  })
}

function firstValidationError (error) {
  const errors = error.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : null
}
</script>

<style scoped>
.laboratorio-row--dragging {
  opacity: 0.45;
}
.cursor-grab {
  cursor: grab;
}
.lab-sub {
  font-size: 10px;
  line-height: 1.3;
}
.lab-rangos {
  max-width: 260px;
  white-space: normal;
}
.lab-opciones {
  max-width: 240px;
  white-space: normal;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 2px 8px;
}

.lab-admin :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.lab-admin :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}
.lab-admin :deep(.q-field--dense .q-field__native),
.lab-admin :deep(.q-field--dense .q-field__input),
.lab-admin :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}
.lab-admin :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}
</style>
