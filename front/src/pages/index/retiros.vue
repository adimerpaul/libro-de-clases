<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Retiro de estudiantes</h1>
        <p>Busca al estudiante y dalo de baja del año escolar (retiro o traslado) con fecha y motivo. La ficha se conserva y la baja queda en el historial.</p>
      </div>
      <q-select v-model="anio" :options="anios" dense outlined label="Año escolar" style="width: 130px" @update:model-value="cambiarAnio" />
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>Buscar estudiante</h3>
        <q-input
          v-model="texto" dense outlined debounce="350" placeholder="Nombre, apellido o RUN" autofocus clearable
          style="min-width: 260px" :loading="buscando" @update:model-value="buscar"
        >
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
        </q-input>
      </div>

      <div v-if="!texto" class="vacio">Escribe al menos 2 letras del nombre o del RUN.</div>
      <div v-else-if="!buscando && !encontrados.length" class="vacio">Sin estudiantes que coincidan con «{{ texto }}».</div>
      <div v-else class="resultados">
        <div v-for="e in encontrados" :key="e.id" class="resultado">
          <div class="rowText">
            <b>{{ e.nombre_completo }}</b>
            <span v-if="e.pie" class="badge violeta q-ml-xs">PIE</span>
            <div class="subtle">{{ formatoRun(e.run) }}</div>
          </div>
          <span v-if="!e.matricula" class="subtle">Sin matrícula {{ anio }}</span>
          <span v-else class="badge" :class="e.matricula.estado === 'activa' ? 'blue' : 'red'">
            {{ e.matricula.curso.nombre }}{{ e.matricula.estado !== 'activa' ? ` · ${estadoTxt[e.matricula.estado]}` : '' }}
          </span>
          <q-btn
            v-if="e.matricula?.estado === 'activa' && $auth.hasPermission('matriculas.editar')"
            no-caps unelevated dense label="Dar de baja" icon="fas fa-door-open" class="btnBaja" @click="abrirBaja(e)"
          />
        </div>
      </div>
    </div>

    <div class="card q-mt-md">
      <div class="cardHead">
        <h3>Bajas {{ anio }} <span class="badge red q-ml-sm">{{ bajas.length }}</span></h3>
        <div class="resumen">
          <span class="badge">{{ conteo.retirada }} retirados</span>
          <span class="badge">{{ conteo.trasladada }} trasladados</span>
        </div>
      </div>
      <q-table
        :rows="bajas" :columns="columns" row-key="id" flat :loading="cargando"
        :pagination="{ rowsPerPage: 25 }" class="adminTable" no-data-label="Aún no hay bajas registradas este año"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Ver nómina del curso', icon: 'fas fa-people-roof', permiso: 'matriculas.ver', to: { path: '/matriculas', query: { curso: p.row.curso_id, anio } } },
              { label: 'Reactivar matrícula', icon: 'fas fa-rotate-left', permiso: 'matriculas.editar', click: () => reactivar(p.row) },
            ]" />
          </q-td>
        </template>
        <template #body-cell-estudiante="p">
          <q-td :props="p">
            <b>{{ p.row.estudiante.nombre_completo }}</b>
            <div class="subtle">{{ formatoRun(p.row.estudiante.run) }}</div>
          </q-td>
        </template>
        <template #body-cell-estado="p">
          <q-td :props="p"><span class="badge red">{{ estadoTxt[p.row.estado] }}</span></q-td>
        </template>
      </q-table>
    </div>

    <!-- Dar de baja -->
    <q-dialog v-model="dialog">
      <q-card class="formCard">
        <q-form @submit.prevent="darBaja">
          <q-card-section>
            <div class="formTitle">Dar de baja</div>
            <div class="subtle">{{ sel?.nombre_completo }} · {{ sel?.matricula?.curso?.nombre }} {{ anio }}</div>
          </q-card-section>
          <q-card-section class="q-pt-none formGrid">
            <div class="full">
              <div class="fieldLabel">Tipo de baja</div>
              <q-btn-toggle
                v-model="form.estado" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9"
                :options="[{ label: 'Retiro', value: 'retirada' }, { label: 'Traslado a otro establecimiento', value: 'trasladada' }]"
              />
            </div>
            <q-input v-model="form.fecha_retiro" type="date" label="Fecha de baja" stack-label outlined dense class="full" />
            <q-select
              v-model="form.motivo_retiro" :options="motivos" use-input hide-selected fill-input new-value-mode="add-unique"
              input-debounce="0" label="Motivo" outlined dense class="full" hint="Elige uno o escribe otro"
              :rules="[v => !!v?.trim() || 'Indica el motivo de la baja']" @input-value="v => form.motivo_retiro = v"
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Confirmar baja" class="btnBaja" :loading="guardando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { formatoRun } from '../../composables/formato'
import { iso, fechaCl } from '../../composables/fechas'
import MenuAcciones from '../../components/MenuAcciones.vue'

definePage({ meta: { title: 'Retiro de estudiantes', permiso: 'matriculas.ver' } })

const { proxy } = getCurrentInstance()

const estadoTxt = { activa: 'Activa', retirada: 'Retirado', trasladada: 'Trasladado' }
const motivos = [
  'Cambio de domicilio', 'Traslado a otro establecimiento', 'Cambio de ciudad o región', 'Motivos de salud',
  'Motivos económicos', 'Decisión de la familia', 'Deserción escolar', 'Migración al extranjero',
]
const actual = new Date().getFullYear()
const anios = [actual - 1, actual, actual + 1]
const anio = ref(actual)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'fecha', label: 'Fecha', field: 'fecha_retiro', align: 'left', format: fechaCl, sortable: true },
  { name: 'estudiante', label: 'Estudiante', field: r => r.estudiante.nombre_completo, align: 'left', sortable: true },
  { name: 'curso', label: 'Curso', field: r => r.curso.nombre, align: 'left', sortable: true },
  { name: 'estado', label: 'Tipo', field: 'estado', align: 'left' },
  { name: 'motivo', label: 'Motivo', field: 'motivo_retiro', align: 'left', classes: 'motivo' },
]

// ── Búsqueda ──
const texto = ref('')
const encontrados = ref([])
const buscando = ref(false)

function buscar () {
  const q = (texto.value || '').trim()
  if (q.length < 2) { encontrados.value = []; return }
  buscando.value = true
  proxy.$axios.get('admin/estudiantes', { params: { anio: anio.value, q } })
    .then(res => { encontrados.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { buscando.value = false })
}

// ── Historial de bajas del año ──
const bajas = ref([])
const cargando = ref(false)
const conteo = computed(() => ({
  retirada: bajas.value.filter(b => b.estado === 'retirada').length,
  trasladada: bajas.value.filter(b => b.estado === 'trasladada').length,
}))

function cargarBajas () {
  cargando.value = true
  return proxy.$axios.get('admin/matriculas/retiros', { params: { anio: anio.value } })
    .then(res => { bajas.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { cargando.value = false })
}

function cambiarAnio () {
  cargarBajas()
  buscar()
}

// ── Dar de baja ──
const dialog = ref(false)
const sel = ref(null)
const form = ref({})
const guardando = ref(false)

function abrirBaja (e) {
  sel.value = e
  form.value = { estado: 'retirada', fecha_retiro: iso(new Date()), motivo_retiro: '' }
  dialog.value = true
}

function darBaja () {
  guardando.value = true
  proxy.$axios.post(`admin/matriculas/${sel.value.matricula.id}/baja`, { ...form.value, motivo_retiro: form.value.motivo_retiro.trim() })
    .then(() => {
      proxy.$alert.success(`${sel.value.nombre_completo} quedó dado de baja`)
      dialog.value = false
      cargarBajas()
      buscar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { guardando.value = false })
}

function reactivar (m) {
  proxy.$alert.confirm(`¿Reactivar la matrícula de ${m.estudiante.nombre_completo} en ${m.curso.nombre}?`).onOk(() => {
    proxy.$axios.put(`admin/matriculas/${m.id}`, {
      curso_id: m.curso_id, numero_lista: m.numero_lista, fecha_matricula: m.fecha_matricula, estado: 'activa',
    })
      .then(() => { proxy.$alert.success('Matrícula reactivada'); cargarBajas(); buscar() })
      .catch(err => proxy.$alert.error(mensajeError(err)))
  })
}

onMounted(cargarBajas)
</script>

<style scoped>
.vacio { padding: 24px; text-align: center; color: #6d8090 }
.resultados { display: flex; flex-direction: column }
.resultado { display: flex; align-items: center; gap: 12px; padding: 10px 4px; border-top: 1px solid #e6edf3 }
.resultado .rowText { flex: 1; min-width: 0 }
.resumen { display: flex; gap: 6px; flex-wrap: wrap }
.fieldLabel { font-size: 12px; font-weight: 700; color: #486477; margin-bottom: 4px }
.btnBaja { background: #bd1f2d; color: #fff; padding: 2px 12px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
:deep(.motivo) { white-space: normal; max-width: 320px }
</style>
