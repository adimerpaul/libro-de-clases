<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Biblioteca pedagógica</h1>
        <p>Actividades, guías, presentaciones, material didáctico, videos y apoyos DUA/PIE, organizados por nivel, asignatura y propósito pedagógico.</p>
      </div>
      <q-btn
        v-if="$auth.hasPermission('recursos.ver')" to="/admin/recursos" label="Administrar biblioteca"
        icon="fas fa-gears" no-caps unelevated class="btnPrimary"
      />
    </div>

    <div class="toolbar">
      <q-select v-model="nivel" :options="niveles" dense outlined label="Nivel" clearable style="min-width: 150px" />
      <q-select
        v-model="asignatura" :options="asignaturas" dense outlined label="Asignatura" clearable multiple use-chips
        style="min-width: 220px" :hint="deCarga && asignatura?.length ?'Tus asignaturas (y material transversal)' : undefined"
      />
      <q-select v-model="tipo" :options="tipos" dense outlined label="Tipo de recurso" clearable style="min-width: 170px" />
      <q-input v-model="texto" dense outlined debounce="250" placeholder="Buscar recurso u OA" style="min-width: 220px">
        <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
      </q-input>
      <span class="subtle">{{ items.length }} de {{ recursos.length }} recursos</span>
    </div>

    <div v-if="loading" class="resourceGrid">
      <q-skeleton v-for="i in 6" :key="i" height="240px" style="border-radius: 15px" />
    </div>

    <div v-else-if="!items.length" class="card vacio">
      <q-icon name="fas fa-box-open" size="32px" />
      <b>{{ recursos.length ? 'Sin recursos para estos filtros.' : 'La biblioteca aún no tiene recursos publicados.' }}</b>
    </div>

    <div v-else class="resourceGrid">
      <div v-for="r in items" :key="r.id" class="resource">
        <div class="resourceThumb" :class="tipoRecurso(r.tipo).thumb">
          <q-icon :name="tipoRecurso(r.tipo).icon" />
        </div>
        <div class="resourceBody">
          <div class="resourceMeta">
            <span class="badge blue">{{ r.tipo }}</span>
            <span class="badge">{{ r.nivel || 'Transversal' }}</span>
            <span v-if="r.asignatura" class="badge">{{ r.asignatura.nombre }}</span>
            <span v-if="r.objetivo" class="badge ok">{{ r.objetivo.codigo }}</span>
          </div>
          <h4>{{ r.titulo }}</h4>
          <p>{{ r.descripcion }}</p>
          <div class="resourceActions">
            <q-btn flat dense no-caps size="sm" icon="far fa-eye" label="Vista previa" @click="ver(r)" />
            <q-btn v-if="r.tiene_archivo" flat dense no-caps size="sm" icon="fas fa-download" label="Descargar" @click="descargarArchivo(proxy, r)" />
            <q-btn v-if="r.url" flat dense no-caps size="sm" icon="fas fa-arrow-up-right-from-square" label="Abrir enlace" :href="r.url" target="_blank" rel="noopener" />
          </div>
        </div>
      </div>
    </div>

    <q-dialog v-model="detalle">
      <q-card v-if="actual" class="formCard">
        <q-card-section class="row items-center no-wrap q-gutter-md">
          <div class="resourceThumb chico" :class="tipoRecurso(actual.tipo).thumb">
            <q-icon :name="tipoRecurso(actual.tipo).icon" />
          </div>
          <div class="col">
            <div class="formTitle">{{ actual.titulo }}</div>
            <div class="resourceMeta">
              <span class="badge blue">{{ actual.tipo }}</span>
              <span class="badge">{{ actual.nivel || 'Transversal' }}</span>
              <span v-if="actual.asignatura" class="badge">{{ actual.asignatura.nombre }}</span>
            </div>
          </div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <p v-if="actual.descripcion" class="detalleTexto">{{ actual.descripcion }}</p>
          <div v-if="actual.objetivo" class="scopeNotice">
            <b>{{ actual.objetivo.codigo }}</b> · {{ actual.objetivo.texto }}
          </div>
          <div v-if="actual.tiene_archivo" class="adjunto">
            <q-icon name="far fa-file" />
            <span class="col ellipsis">{{ actual.archivo_nombre }}</span>
            <span class="subtle">{{ formatoTamano(actual.archivo_tamano) }}</span>
          </div>
          <div v-if="actual.autor" class="subtle q-mt-sm">Publicado por {{ actual.autor.name }}</div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cerrar" v-close-popup />
          <q-btn v-if="actual.url" flat no-caps icon="fas fa-arrow-up-right-from-square" label="Abrir enlace" :href="actual.url" target="_blank" rel="noopener" />
          <q-btn v-if="actual.tiene_archivo" no-caps unelevated icon="fas fa-download" label="Descargar" class="btnPrimary" @click="descargarArchivo(proxy, actual)" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { tipoRecurso, formatoTamano, descargarArchivo } from '../../composables/biblioteca'

definePage({ meta: { title: 'Biblioteca', permiso: ['recursos.ver', 'clases.dictar'] } })

const { proxy } = getCurrentInstance()

const recursos = ref([])
const loading = ref(false)
const nivel = ref(null)
const asignatura = ref([])
const deCarga = ref(false) // el filtro de asignatura viene de la carga del docente
const tipo = ref(null)
const texto = ref('')
const detalle = ref(false)
const actual = ref(null)

// Como en ui3, las opciones de filtro salen de los recursos publicados
const unicos = lista => [...new Set(lista.filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es'))
const niveles = computed(() => ['Transversal', ...unicos(recursos.value.map(r => r.nivel))])
const asignaturas = computed(() => unicos(recursos.value.map(r => r.asignatura?.nombre)))
const tipos = computed(() => unicos(recursos.value.map(r => r.tipo)))

const normalizar = s => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()

const items = computed(() => {
  const q = normalizar(texto.value)
  return recursos.value.filter(r =>
    (!nivel.value || (r.nivel || 'Transversal') === nivel.value) &&
    (!asignatura.value?.length || !r.asignatura || asignatura.value.includes(r.asignatura.nombre)) &&
    (!tipo.value || r.tipo === tipo.value) &&
    (!q || normalizar([r.titulo, r.descripcion, r.objetivo?.codigo, r.objetivo?.texto].join(' ')).includes(q)))
})

function ver (r) {
  actual.value = r
  detalle.value = true
}

onMounted(() => {
  loading.value = true
  // El docente llega filtrado por las asignaturas de su carga del año (puede quitar el filtro)
  const carga = proxy.$auth.hasPermission('clases.dictar')
    ? proxy.$axios.get('docente/asignaciones').then(res => res.data).catch(() => [])
    : Promise.resolve([])
  Promise.all([proxy.$axios.get('biblioteca'), carga])
    .then(([res, mias]) => {
      recursos.value = res.data
      const propias = unicos(mias.map(a => a.asignatura?.nombre))
      if (propias.length) {
        asignatura.value = propias
        deCarga.value = true
      }
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { loading.value = false })
})
</script>

<style scoped>
/* ui3: .toolbar, .resourceGrid, .resource, .resourceThumb… */
.toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px }
.resourceGrid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px }
.resource { border: 1px solid #d9e3ec; border-radius: 15px; overflow: hidden; background: #fff; display: flex; flex-direction: column }
.resourceThumb {
  height: 120px; background: linear-gradient(135deg, #eaf3fb, #f8fbfd); display: grid; place-items: center;
  font-size: 44px; color: #0f4f85;
}
.resourceThumb.video { background: linear-gradient(135deg, #e8ecff, #f7f6ff); color: #4a4fbb }
.resourceThumb.presentation { background: linear-gradient(135deg, #fff1e7, #fffaf4); color: #c4621b }
.resourceThumb.didactic { background: linear-gradient(135deg, #eaf8f1, #f8fdfb); color: #17703f }
.resourceThumb.chico { height: 64px; width: 64px; border-radius: 14px; font-size: 26px; flex: none }
.resourceBody { padding: 12px; display: flex; flex-direction: column; flex: 1 }
.resourceBody h4 { font-size: 14px; font-weight: 800; margin: 0 0 4px; line-height: 1.3 }
.resourceBody p { font-size: 12px; color: #6d8090; line-height: 1.45; margin: 0; flex: 1 }
.resourceMeta { display: flex; gap: 5px; flex-wrap: wrap; margin: 6px 0 9px }
.resourceActions { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 9px; color: #0f4f85 }
.vacio { display: grid; place-items: center; gap: 10px; padding: 40px; color: #6d8090; text-align: center }
.detalleTexto { color: #415c72; line-height: 1.5; margin: 0 0 10px }
.adjunto {
  display: flex; align-items: center; gap: 8px; margin-top: 10px; padding: 9px 12px;
  border: 1px solid #d9e3ec; border-radius: 12px; background: #f5f9fc;
}
@media (max-width: 1100px) { .resourceGrid { grid-template-columns: 1fr 1fr } }
@media (max-width: 700px) { .resourceGrid { grid-template-columns: 1fr } }
</style>
