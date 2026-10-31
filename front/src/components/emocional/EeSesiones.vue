<template>
  <div class="card">
    <div class="cardHead">
      <h3>Sesiones y actividades <span class="badge blue q-ml-sm">{{ rows.length }}</span></h3>
      <div class="filtros">
        <q-input v-model="filtro.q" dense outlined debounce="350" placeholder="Buscar sesión o actividad" style="min-width: 210px" @update:model-value="cargar">
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
        </q-input>
        <q-select v-model="filtro.competencia" :options="cat?.competencias || []" emit-value map-options clearable dense outlined label="Competencia" style="min-width: 170px" @update:model-value="cargar" />
        <q-select v-model="filtro.tramo" :options="cat?.tramos || []" emit-value map-options clearable dense outlined label="Nivel" style="min-width: 170px" @update:model-value="cargar" />
        <q-select v-model="filtro.curso_id" :options="cat?.cursos || []" option-value="id" option-label="nombre" emit-value map-options clearable dense outlined label="Curso" style="min-width: 140px" @update:model-value="cargar" />
        <q-btn v-if="$auth.hasPermission('emocional.crear')" label="+ Registrar sesión" no-caps unelevated class="btnPrimary" @click="nuevo({ fecha: hoy })" />
      </div>
    </div>

    <q-table :rows="rows" :columns="columns" row-key="id" flat :loading="loading" :pagination="{ rowsPerPage: 20 }" class="adminTable" no-data-label="Sin sesiones con estos filtros">
      <template #body-cell-acciones="p">
        <q-td :props="p">
          <MenuAcciones :acciones="[
            { label: 'Editar', icon: 'fas fa-pen', permiso: 'emocional.editar', click: () => editar(p.row) },
            { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'emocional.eliminar', click: () => eliminar(p.row, `¿Eliminar la sesión «${p.row.titulo}»?`) },
          ]" />
        </q-td>
      </template>
      <template #body-cell-titulo="p">
        <q-td :props="p">
          <b>{{ p.row.titulo }}</b>
          <div class="subtle">{{ p.row.tipo }}<template v-if="p.row.objetivo"> · <span class="badge violeta">{{ p.row.objetivo.codigo }}</span></template></div>
        </q-td>
      </template>
      <template #body-cell-competencia="p">
        <q-td :props="p">
          <span class="badge" :style="{ color: colorCompetencia(cat, p.row.competencia), borderColor: colorCompetencia(cat, p.row.competencia) + '55' }">
            {{ etiqueta(cat?.competencias, p.row.competencia) }}
          </span>
        </q-td>
      </template>
      <template #body-cell-estado="p">
        <q-td :props="p"><span class="badge" :class="CLASE_ESTADO_SESION[p.row.estado]">{{ etiqueta(cat?.estados_sesion, p.row.estado) }}</span></q-td>
      </template>
      <template #body-cell-evidencias="p">
        <q-td :props="p">
          <q-icon v-if="p.row.evidencias" name="fas fa-paperclip" color="primary"><q-tooltip max-width="300px">{{ p.row.evidencias }}</q-tooltip></q-icon>
          <span v-else class="subtle">—</span>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog">
      <q-card class="formCard sesionCard">
        <q-form @submit.prevent="guardar()">
          <q-card-section class="formTitle">{{ form.id ? 'Editar sesión' : 'Registrar sesión' }}</q-card-section>
          <q-card-section class="formGrid">
            <q-input v-model="form.titulo" label="Título" outlined dense class="full" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-select v-model="form.curso_id" :options="cat?.cursos || []" option-value="id" option-label="nombre" emit-value map-options label="Curso" outlined dense @update:model-value="buscarOa" />
            <q-select v-model="form.tipo" :options="cat?.tipos_sesion || []" label="Tipo" outlined dense />
            <q-select v-model="form.competencia" :options="cat?.competencias || []" emit-value map-options label="Competencia" outlined dense />
            <q-select v-model="form.estado" :options="cat?.estados_sesion || []" emit-value map-options label="Estado" outlined dense />
            <q-input v-model="form.fecha" type="date" label="Fecha" stack-label outlined dense />
            <q-input v-model="form.fecha_fin" type="date" label="Hasta (opcional)" stack-label outlined dense />
            <q-select v-model="form.responsable_id" :options="cat?.responsables || []" option-value="id" option-label="name" emit-value map-options clearable label="Responsable" outlined dense />
            <q-input v-model.number="form.participantes" type="number" min="0" label="Participantes" outlined dense hint="Vacío = nómina del curso" />
            <q-select
              v-model="form.objetivo_aprendizaje_id" :options="oas" option-value="id" :option-label="o => `${o.codigo} · ${o.texto}`" emit-value map-options clearable
              label="OA de Orientación (opcional)" outlined dense class="full" :disable="!form.curso_id"
            />
            <div v-if="focoPlan" class="full foco"><b>Foco del plan para este tramo:</b> {{ focoPlan }}</div>
            <q-input v-model="form.descripcion" type="textarea" autogrow label="Descripción y desarrollo" outlined dense class="full" />
            <q-input v-model="form.evidencias" type="textarea" autogrow label="Evidencias (fotos, actas, enlaces)" outlined dense class="full" />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { useCrud } from '../../composables/useCrud'
import { iso, fechaCl } from '../../composables/fechas'
import { useCatalogosEmocional, etiqueta, colorCompetencia, CLASE_ESTADO_SESION } from '../../composables/emocional'
import MenuAcciones from '../MenuAcciones.vue'

const { proxy } = getCurrentInstance()
const cat = useCatalogosEmocional()
const hoy = iso(new Date())
const filtro = ref({ q: '', competencia: null, tramo: null, curso_id: null })
const oas = ref([])

const { rows, loading, dialog, saving, form, cargar, nuevo, editar, guardar, eliminar } = useCrud(
  'emocional/sesiones',
  () => ({ titulo: '', curso_id: null, tipo: 'Taller', competencia: 'autoconciencia', estado: 'planificada', fecha: hoy, fecha_fin: '', responsable_id: null, participantes: null, objetivo_aprendizaje_id: null, descripcion: '', evidencias: '' }),
  {
    nombre: 'Sesión',
    params: () => ({ q: filtro.value.q || undefined, competencia: filtro.value.competencia || undefined, tramo: filtro.value.tramo || undefined, curso_id: filtro.value.curso_id || undefined }),
    aForm: s => {
      const f = { ...s, fecha_fin: s.fecha_fin || '', curso: undefined, responsable: undefined, objetivo: undefined }
      buscarOa(s.curso_id)
      return f
    },
  },
)

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'titulo', label: 'Sesión / actividad', field: 'titulo', align: 'left', sortable: true },
  { name: 'competencia', label: 'Competencia', field: 'competencia', align: 'left', sortable: true },
  { name: 'curso', label: 'Curso', field: r => r.curso?.nombre, align: 'left', sortable: true },
  { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left', sortable: true, format: (v, r) => (r.fecha_fin ? `${fechaCl(v)} → ${fechaCl(r.fecha_fin)}` : fechaCl(v)) },
  { name: 'participantes', label: 'Participantes', field: 'participantes', align: 'center', sortable: true },
  { name: 'responsable', label: 'Responsable', field: r => r.responsable?.name || '—', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'evidencias', label: 'Evidencias', field: 'evidencias', align: 'center' },
]

const cursoForm = computed(() => (cat.value?.cursos || []).find(c => c.id === form.value.curso_id))
const focoPlan = computed(() => cursoForm.value && cat.value?.plan?.[form.value.competencia]?.[cursoForm.value.tramo])

// OA de Orientación (catálogo curricular) del nivel del curso
let oaOrientacion = null
async function buscarOa (cursoId) {
  oaOrientacion ||= proxy.$axios.get('emocional/orientacion').then(res => res.data.oa).catch(() => [])
  const curso = (cat.value?.cursos || []).find(c => c.id === cursoId)
  const todos = await oaOrientacion
  oas.value = curso ? todos.filter(o => o.nivel === curso.nivel) : []
}

onMounted(cargar)
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap; align-items: center }
.sesionCard { width: 720px; max-width: 96vw }
.foco { font-size: 12.5px; background: #f5f3ff; border-left: 4px solid #7c3aed; border-radius: 0 10px 10px 0; padding: 8px 10px; color: #4c3a8a }
</style>
