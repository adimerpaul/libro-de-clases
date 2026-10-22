<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Libros de clases</h1>
        <p>
          Un libro por curso, con la forma de un libro físico: portada, identificación, nómina y apoderados.
          {{ todos ? 'Ves los libros de todos los cursos del año.' : 'Ves los cursos donde dictas clases o eres profesor(a) jefe.' }}
        </p>
      </div>
      <div class="filtros">
        <q-input v-model="buscar" dense outlined clearable placeholder="Buscar curso" style="width: 180px">
          <template #prepend><q-icon name="fas fa-magnifying-glass" size="13px" /></template>
        </q-input>
        <q-select v-model="anio" :options="anios" dense outlined label="Año escolar" style="width: 130px" @update:model-value="cargar" />
      </div>
    </div>

    <div v-if="loading" class="estante">
      <q-skeleton v-for="n in 4" :key="n" height="250px" class="skel" />
    </div>

    <div v-else-if="!filtrados.length" class="card vacio">
      <q-icon name="fas fa-book" size="28px" color="grey-5" />
      <div v-if="buscar">Ningún curso coincide con «{{ buscar }}».</div>
      <div v-else-if="todos">No hay cursos en {{ anio }}. Créalos en Administrador → Cursos.</div>
      <div v-else>No tienes cursos asignados en {{ anio }}. La asignación docente la registra UTP o Dirección.</div>
    </div>

    <template v-else>
      <div v-for="grupo in grupos" :key="grupo.titulo" class="grupo">
        <div class="grupoTitulo">{{ grupo.titulo }} <span class="subtle">· {{ grupo.cursos.length }}</span></div>
        <div class="estante">
          <router-link v-for="c in grupo.cursos" :key="c.id" :to="`/libros/${c.id}`" class="libro">
            <div class="tapa">
              <div class="lomo" />
              <div class="tapaTop">
                <span class="eyebrow">LIBRO DE CLASES · {{ c.anio }}</span>
                <h2>{{ c.nombre }}</h2>
              </div>
              <div class="etiqueta">
                <div class="etqLinea"><b>Profesor(a) jefe</b><span>{{ c.profesor_jefe?.name || 'Sin asignar' }}</span></div>
                <div class="etqLinea"><b>Matrícula</b><span>{{ c.estudiantes_count }} estudiantes</span></div>
                <div class="etqLinea"><b>Asignaturas</b><span>{{ c.asignaciones_count }}</span></div>
              </div>
            </div>
            <div class="pie">
              <span v-if="c.es_jefatura" class="badge red">Jefatura</span>
              <span v-for="a in c.mis_asignaturas" :key="a.id" class="badge blue">{{ a.nombre }}</span>
              <q-space />
              <span class="abrir">Abrir <q-icon name="fas fa-arrow-right" size="10px" /></span>
            </div>
          </router-link>
        </div>
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { mensajeError } from '../../../composables/useCrud'

definePage({ meta: { title: 'Libro de clases', permiso: ['libros.ver', 'clases.dictar'] } })

const { proxy } = getCurrentInstance()

const actual = new Date().getFullYear()
const anios = [actual - 1, actual, actual + 1]
const anio = ref(actual)
const cursos = ref([])
const loading = ref(false)
const buscar = ref('')
const todos = computed(() => proxy.$auth.hasPermission('libros.ver'))

const filtrados = computed(() => {
  const t = (buscar.value || '').toLowerCase().trim()
  return t ? cursos.value.filter(c => c.nombre.toLowerCase().includes(t)) : cursos.value
})

// Agrupa por ciclo según el nivel ("Pre-kínder", "3° Básico", "2° Medio"…); la API ya los entrega ordenados
const grupos = computed(() => {
  const ciclo = n => /medio/i.test(n) ? 'Enseñanza Media' : /b[aá]sico/i.test(n) ? 'Enseñanza Básica' : 'Educación Parvularia'
  const res = []
  for (const c of filtrados.value) {
    const titulo = ciclo(c.nivel)
    let g = res.find(x => x.titulo === titulo)
    if (!g) res.push(g = { titulo, cursos: [] })
    g.cursos.push(c)
  }
  return res
})

function cargar () {
  loading.value = true
  proxy.$axios.get('libros', { params: { anio: anio.value } })
    .then(res => { cursos.value = res.data })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { loading.value = false })
}

onMounted(cargar)
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap }
.vacio { display: grid; place-items: center; gap: 10px; padding: 40px; color: #6d8090; text-align: center }
.grupo { margin-bottom: 22px }
.grupoTitulo { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #486477; margin-bottom: 10px }
.estante { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 16px }
.skel { border-radius: 18px }

.libro {
  display: flex; flex-direction: column; text-decoration: none; color: inherit;
  background: #fff; border: 1px solid #d9e3ec; border-radius: 18px; overflow: hidden;
  box-shadow: 0 7px 22px rgba(22, 52, 80, .055); transition: transform .15s, box-shadow .15s;
}
.libro:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(22, 52, 80, .14) }

/* Tapa roja del libro (ui2 .frontCover) */
.tapa {
  position: relative; padding: 18px 18px 16px 30px; color: #fff; min-height: 196px;
  background: linear-gradient(145deg, #c82130 0 56%, #e0e3e5 56% 70%, #991822 70%);
}
.lomo { position: absolute; left: 0; top: 0; bottom: 0; width: 14px; background: linear-gradient(90deg, #0003, #fff3 55%, #0002) }
.eyebrow { font-size: 9px; font-weight: 800; letter-spacing: .14em; color: #ffd9dd }
.tapaTop h2 { font-size: 30px; font-weight: 900; line-height: 1; margin: 6px 0 14px; letter-spacing: -.02em }
.etiqueta { background: #ffffffeb; color: #17324a; border-radius: 5px; padding: 6px 10px }
.etqLinea { display: flex; justify-content: space-between; gap: 8px; border-bottom: 1px solid #6b829455; padding: 5px 0; font-size: 11px }
.etqLinea:last-child { border-bottom: 0 }
.etqLinea b { font-size: 9px; text-transform: uppercase; color: #3d6e96; white-space: nowrap }
.etqLinea span { text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap }

.pie { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; padding: 10px 14px; min-height: 44px }
.abrir { font-size: 12px; font-weight: 800; color: #0f4f85 }
</style>
