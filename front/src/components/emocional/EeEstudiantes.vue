<template>
  <div class="card">
    <div class="cardHead">
      <h3>Estudiantes <span class="badge blue q-ml-sm">{{ rows.length }}</span></h3>
      <div class="filtros">
        <q-input v-model="q" dense outlined debounce="350" placeholder="Nombre o RUN" style="min-width: 200px" @update:model-value="cargar">
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
        </q-input>
        <q-select v-model="cursoId" :options="cat?.cursos || []" option-value="id" option-label="nombre" emit-value map-options clearable dense outlined label="Curso" style="min-width: 150px" @update:model-value="cargar" />
        <q-toggle v-model="conAlerta" dense label="Solo con alerta abierta" @update:model-value="cargar" />
      </div>
    </div>
    <q-table
      :rows="rows" :columns="columns" row-key="id" flat :loading="loading" :pagination="{ rowsPerPage: 25 }" class="adminTable"
      no-data-label="Sin estudiantes con estos filtros" @row-click="(e, row) => $router.push(`/emocional/${row.id}`)"
    >
      <template #body-cell-nombre="p">
        <q-td :props="p">
          <b>{{ p.row.nombre }}</b>
          <div class="subtle">{{ formatoRun(p.row.run) }}</div>
        </q-td>
      </template>
      <template #body-cell-dim="p">
        <q-td :props="p">
          <template v-if="p.row.dimensiones">
            <span v-for="d in ['personal', 'comunitario', 'ciudadano']" :key="d" class="badge q-mr-xs" :class="claseLogro(p.row.dimensiones[d])">
              {{ d[0].toUpperCase() }} {{ p.row.dimensiones[d] ?? '—' }}
            </span>
          </template>
          <span v-else class="subtle">Sin diagnóstico</span>
        </q-td>
      </template>
      <template #body-cell-promedio="p">
        <q-td :props="p"><b :class="`txt-${claseLogro(p.row.promedio)}`">{{ p.row.promedio ?? '—' }}</b></q-td>
      </template>
      <template #body-cell-alertas="p">
        <q-td :props="p">
          <span v-if="p.row.alertas_abiertas" class="badge" :class="p.row.alerta_alta ? 'red' : 'warn'">{{ p.row.alertas_abiertas }} abierta{{ p.row.alertas_abiertas > 1 ? 's' : '' }}</span>
          <span v-else class="subtle">—</span>
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useCrud } from '../../composables/useCrud'
import { formatoRun } from '../../composables/formato'
import { useCatalogosEmocional, claseLogro, etiqueta } from '../../composables/emocional'

const cat = useCatalogosEmocional()
const q = ref('')
const cursoId = ref(null)
const conAlerta = ref(false)

const { rows, loading, cargar } = useCrud('emocional/estudiantes', () => ({}), {
  params: () => ({ q: q.value || undefined, curso_id: cursoId.value || undefined, con_alerta: conAlerta.value ? 1 : undefined }),
})

const columns = [
  { name: 'nombre', label: 'Estudiante', field: 'nombre', align: 'left', sortable: true },
  { name: 'curso', label: 'Curso', field: 'curso', align: 'left', sortable: true },
  { name: 'dim', label: 'Dimensiones (personal · comunitario · ciudadano)', field: 'promedio', align: 'left' },
  { name: 'promedio', label: 'Promedio', field: 'promedio', align: 'center', sortable: true },
  { name: 'periodo', label: 'Último periodo', field: r => etiqueta(cat.value?.periodos, r.periodo), align: 'left' },
  { name: 'alertas', label: 'Alertas', field: 'alertas_abiertas', align: 'center', sortable: true },
]

onMounted(cargar)
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap; align-items: center }
.adminTable :deep(tbody tr) { cursor: pointer }
.txt-ok { color: #17703f }
.txt-warn { color: #b56b12 }
.txt-red { color: #bd1f2d }
</style>
