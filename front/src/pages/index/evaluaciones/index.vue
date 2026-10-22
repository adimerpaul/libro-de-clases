<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Creador de evaluaciones</h1>
        <p>
          Instrumentos por curso, asignatura y OA: arma las preguntas, genera un borrador desde el OA,
          revisa la escala de notas (1,0 – 7,0) e imprime la prueba y su pauta.
        </p>
      </div>
      <q-btn label="+ Nuevo instrumento" no-caps unelevated class="btnPrimary" to="/evaluaciones/nuevo" />
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>Mis instrumentos <span class="badge blue q-ml-sm">{{ filas.length }}</span></h3>
        <div class="filtros">
          <q-select
            v-model="filtroAsignacion" :options="opcionesCarga" emit-value map-options clearable dense outlined
            label="Curso y asignatura" style="min-width: 240px"
          />
          <q-input v-model="filtro" dense outlined placeholder="Buscar por nombre u OA" style="min-width: 220px">
            <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
          </q-input>
        </div>
      </div>

      <q-table
        :rows="filas" :columns="columns" row-key="id" flat :loading="loading" :filter="filtro"
        :pagination="{ rowsPerPage: 20 }" class="adminTable" no-data-label="Aún no tienes instrumentos. Crea el primero con «+ Nuevo instrumento»."
        @row-click="(e, row) => $router.push(`/evaluaciones/${row.id}`)"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p" auto-width @click.stop>
            <q-btn flat round dense size="sm" icon="fas fa-ellipsis-vertical" aria-label="Opciones">
              <q-menu auto-close anchor="bottom left" self="top left">
                <q-list dense class="accionesMenu">
                  <q-item clickable :to="`/evaluaciones/${p.row.id}`">
                    <q-item-section avatar><q-icon name="fas fa-pen" size="13px" /></q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>
                  <q-item clickable :to="{ path: `/evaluaciones/${p.row.id}`, query: { imprimir: 1 } }">
                    <q-item-section avatar><q-icon name="fas fa-print" size="13px" /></q-item-section>
                    <q-item-section>Vista previa e impresión</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable class="text-negative" @click="eliminar(p.row, `¿Eliminar el instrumento «${p.row.nombre}»?`)">
                    <q-item-section avatar><q-icon name="far fa-trash-can" size="13px" /></q-item-section>
                    <q-item-section>Eliminar</q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-td>
        </template>
        <template #body-cell-nombre="p">
          <q-td :props="p">
            <b>{{ p.row.nombre }}</b>
            <div class="subtle">{{ p.row.tipo }}</div>
          </q-td>
        </template>
        <template #body-cell-curso="p">
          <q-td :props="p">
            <span class="dot" :style="{ background: p.row.asignacion.asignatura.color || '#c5d3df' }" />
            {{ p.row.asignacion.curso.nombre }} · {{ p.row.asignacion.asignatura.nombre }}
          </q-td>
        </template>
        <template #body-cell-oa="p">
          <q-td :props="p">
            <span v-if="p.row.objetivo" class="badge violeta">{{ p.row.objetivo.codigo }}</span>
            <span v-else class="subtle">—</span>
          </q-td>
        </template>
        <template #body-cell-estado="p">
          <q-td :props="p">
            <span class="badge" :class="p.row.estado === 'listo' ? 'ok' : ''">{{ p.row.estado === 'listo' ? 'Listo' : 'Borrador' }}</span>
          </q-td>
        </template>
      </q-table>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { useCrud } from '../../../composables/useCrud'
import { fechaCl } from '../../../composables/fechas'

definePage({ meta: { title: 'Evaluaciones', permiso: 'clases.dictar' } })

const { proxy } = getCurrentInstance()
const filtro = ref('')
const filtroAsignacion = ref(null)
const carga = ref([])

const { rows, loading, cargar, eliminar } = useCrud('docente/instrumentos', () => ({}), { nombre: 'Instrumento' })

const filas = computed(() => rows.value.filter(r => !filtroAsignacion.value || r.asignacion_id === filtroAsignacion.value))
const opcionesCarga = computed(() => carga.value.map(a => ({ label: `${a.curso.nombre} · ${a.asignatura.nombre}`, value: a.id })))

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left' },
  { name: 'nombre', label: 'Instrumento', field: 'nombre', align: 'left', sortable: true },
  { name: 'curso', label: 'Curso y asignatura', field: r => `${r.asignacion.curso.nombre} ${r.asignacion.asignatura.nombre}`, align: 'left', sortable: true },
  { name: 'oa', label: 'OA', field: r => r.objetivo?.codigo || '', align: 'left' },
  { name: 'preguntas', label: 'Preguntas', field: 'preguntas_count', align: 'center', sortable: true },
  { name: 'puntaje', label: 'Puntaje', field: r => Number(r.preguntas_sum_puntaje || 0), align: 'center', sortable: true, format: v => `${v} pts` },
  { name: 'fecha', label: 'Aplicación', field: 'fecha_aplicacion', align: 'left', sortable: true, format: v => fechaCl(v) || '—' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
]

onMounted(() => {
  cargar()
  proxy.$axios.get('docente/asignaciones').then(res => { carga.value = res.data }).catch(() => {})
})
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap }
.dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
.accionesMenu { min-width: 200px; font-size: 13px }
.accionesMenu :deep(.q-item__section--avatar) { min-width: 26px }
.adminTable :deep(tbody tr) { cursor: pointer }
</style>
