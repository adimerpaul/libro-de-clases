<template>
  <div>
    <div class="card q-mb-md subtle">
      Inspirado en el Diagnóstico Integral de Aprendizajes (DIA), dimensión socioemocional. Uso interno del establecimiento para
      monitorear habilidades y orientar el plan. Logro de 0 a 100 por competencia; las dimensiones las calcula el sistema.
    </div>

    <div class="tres q-mb-md">
      <div v-for="d in cat?.dimensiones || []" :key="d.value" class="card">
        <b class="dimNombre">{{ d.label }}</b>
        <div class="dimValor" :style="{ color: d.color }">{{ res?.global[d.value] ?? '—' }}{{ res?.global[d.value] != null ? '%' : '' }}</div>
        <div class="subtle">{{ d.competencias.map(c => etiqueta(cat.competencias, c)).join(' + ') }}</div>
        <div class="track"><div class="fill" :style="{ width: `${res?.global[d.value] || 0}%`, background: d.color }" /></div>
      </div>
    </div>

    <div class="card q-mb-md">
      <div class="cardHead">
        <h3>Resultados por curso</h3>
        <span class="subtle">{{ res?.estudiantes || 0 }} estudiantes diagnosticados · último periodo aplicado en cada curso</span>
      </div>
      <q-markup-table flat dense class="tabla">
        <thead><tr><th class="text-left">Curso</th><th>Periodo</th><th>Personal</th><th>Comunitario</th><th>Ciudadano</th><th>Promedio</th><th>Tendencia</th><th /></tr></thead>
        <tbody>
          <tr v-for="c in res?.cursos || []" :key="c.curso_id" class="clic" @click="abrirPlanilla(c.curso_id, c.periodo)">
            <td><b>{{ c.curso }}</b> <span class="subtle">· {{ c.estudiantes }}</span></td>
            <td class="text-center">{{ etiqueta(cat?.periodos, c.periodo) }}</td>
            <td v-for="d in ['personal', 'comunitario', 'ciudadano']" :key="d" class="text-center"><span class="badge" :class="claseLogro(c.dimensiones[d])">{{ c.dimensiones[d] ?? '—' }}%</span></td>
            <td class="text-center"><b>{{ c.promedio ?? '—' }}%</b></td>
            <td class="text-center">
              <q-icon v-if="c.tendencia" :name="ICONO_TENDENCIA[c.tendencia]" :class="`t-${c.tendencia}`" size="16px"><q-tooltip>{{ TEXTO_TENDENCIA[c.tendencia] }}</q-tooltip></q-icon>
              <span v-else class="subtle">—</span>
            </td>
            <td class="text-right"><q-icon name="fas fa-chevron-right" size="11px" color="grey-6" /></td>
          </tr>
          <tr v-if="res && !res.cursos.length"><td colspan="8" class="subtle q-pa-md">Aún no se aplica el diagnóstico este año.</td></tr>
        </tbody>
      </q-markup-table>
    </div>

    <!-- Planilla -->
    <div ref="planillaRef" class="card">
      <div class="cardHead">
        <h3>Aplicar o editar diagnóstico</h3>
        <div class="filtros">
          <q-select v-model="cursoId" :options="cat?.cursos || []" option-value="id" option-label="nombre" emit-value map-options dense outlined label="Curso" style="min-width: 150px" @update:model-value="cargarPlanilla" />
          <q-select v-model="periodo" :options="cat?.periodos || []" emit-value map-options dense outlined label="Periodo" style="min-width: 190px" @update:model-value="cargarPlanilla" />
          <q-input v-model="fecha" type="date" dense outlined label="Fecha" stack-label style="width: 160px" />
        </div>
      </div>
      <q-inner-loading :showing="cargando" />
      <div v-if="!planilla" class="subtle">Elija un curso y un periodo.</div>
      <template v-else>
        <div class="resumenPlanilla">
          <span v-for="d in cat?.dimensiones || []" :key="d.value" class="badge" :class="claseLogro(planilla.dimensiones[d.value])">
            {{ d.label.replace('Aprendizaje socioemocional ', '') }}: {{ planilla.dimensiones[d.value] ?? '—' }}%
          </span>
        </div>
        <div class="tablaScroll">
          <q-markup-table flat dense class="tabla planilla">
            <thead>
              <tr>
                <th class="text-left">N°</th><th class="text-left">Estudiante</th>
                <th v-for="c in cat?.competencias || []" :key="c.value"><span class="dot" :style="{ background: c.color }" />{{ c.label }}</th>
                <th>Promedio</th><th class="text-left">Observación</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in filas" :key="f.id">
                <td class="subtle">{{ f.numero_lista }}</td>
                <td><router-link :to="`/emocional/${f.id}`" class="enlace">{{ f.nombre }}</router-link></td>
                <td v-for="c in cat?.competencias || []" :key="c.value" class="celda">
                  <q-input v-model.number="f[c.value]" type="number" min="0" max="100" dense outlined input-class="text-center" @update:model-value="f.cambiado = true" />
                </td>
                <td class="text-center"><b v-if="!f.cambiado" :class="`txt-${claseLogro(f.promedio)}`">{{ f.promedio ?? '—' }}</b><span v-else class="subtle">al guardar</span></td>
                <td><q-input v-model="f.observacion" dense outlined maxlength="500" @update:model-value="f.cambiado = true" /></td>
              </tr>
            </tbody>
          </q-markup-table>
        </div>
        <div class="row items-center q-mt-md">
          <span class="subtle">Deje una fila en blanco para quitar el diagnóstico de ese estudiante en este periodo.</span>
          <q-space />
          <q-btn
            v-if="$auth.hasPermission('emocional.editar')" no-caps unelevated class="btnPrimary" icon="fas fa-floppy-disk" label="Guardar diagnóstico"
            :loading="guardando" :disable="!filas.some(f => f.cambiado)" @click="guardar"
          />
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { getCurrentInstance, nextTick, onMounted, ref } from 'vue'
import { mensajeError } from '../../composables/useCrud'
import { iso } from '../../composables/fechas'
import { useCatalogosEmocional, etiqueta, claseLogro, ICONO_TENDENCIA } from '../../composables/emocional'

const { proxy } = getCurrentInstance()
const cat = useCatalogosEmocional()
const TEXTO_TENDENCIA = { sube: 'Mejora respecto del periodo anterior', estable: 'Sin cambio relevante', baja: 'Baja respecto del periodo anterior' }

const res = ref(null)
const planilla = ref(null)
const filas = ref([])
const cursoId = ref(null)
const periodo = ref('inicial')
const fecha = ref(iso(new Date()))
const cargando = ref(false)
const guardando = ref(false)
const planillaRef = ref(null)

function cargarResultados () {
  proxy.$axios.get('emocional/diagnosticos/resultados').then(r => { res.value = r.data }).catch(e => proxy.$alert.error(mensajeError(e)))
}

function aplicarPlanilla (data) {
  planilla.value = data
  if (data.fecha) fecha.value = data.fecha
  const comps = (cat.value?.competencias || []).map(c => c.value)
  filas.value = data.filas.map(f => ({
    id: f.id, numero_lista: f.numero_lista, nombre: f.nombre,
    ...Object.fromEntries(comps.map(c => [c, f.diagnostico?.[c] ?? null])),
    promedio: f.diagnostico?.promedio ?? null, observacion: f.diagnostico?.observacion || '', cambiado: false,
  }))
}

async function cargarPlanilla () {
  if (!cursoId.value || !periodo.value) return
  cargando.value = true
  try {
    aplicarPlanilla((await proxy.$axios.get('emocional/diagnosticos', { params: { curso_id: cursoId.value, periodo: periodo.value } })).data)
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    cargando.value = false
  }
}

async function abrirPlanilla (curso, per) {
  cursoId.value = curso
  periodo.value = per
  await cargarPlanilla()
  await nextTick()
  planillaRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function guardar () {
  guardando.value = true
  const comps = (cat.value?.competencias || []).map(c => c.value)
  const valor = v => (v === '' || v == null ? null : Number(v))
  try {
    const { data } = await proxy.$axios.put('emocional/diagnosticos', {
      curso_id: cursoId.value, periodo: periodo.value, fecha: fecha.value,
      filas: filas.value.filter(f => f.cambiado).map(f => ({
        estudiante_id: f.id, observacion: f.observacion || null, ...Object.fromEntries(comps.map(c => [c, valor(f[c])])),
      })),
    })
    aplicarPlanilla(data)
    proxy.$alert.success('Diagnóstico guardado')
    cargarResultados()
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}

onMounted(cargarResultados)
</script>

<style scoped>
.tres { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px }
.dimNombre { font-size: 13.5px }
.dimValor { font-size: 28px; font-weight: 900; margin: 4px 0 }
.track { height: 8px; background: #eef2f7; border-radius: 99px; overflow: hidden; margin-top: 8px }
.fill { height: 100%; border-radius: 99px }
.tabla th { font-size: 11px; color: #6d8090; text-transform: uppercase; white-space: nowrap }
.clic { cursor: pointer }
.clic:hover { background: #faf7ff }
.t-sube { color: #059669 }
.t-estable { color: #b56b12 }
.t-baja { color: #bd1f2d }
.filtros { display: flex; gap: 8px; flex-wrap: wrap }
.resumenPlanilla { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px }
.tablaScroll { overflow-x: auto }
.planilla { min-width: 980px }
.planilla th { text-transform: none; font-size: 11.5px }
.celda { width: 92px }
.dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px }
.enlace { color: #0b2440; text-decoration: none; font-weight: 700 }
.enlace:hover { text-decoration: underline }
.txt-ok { color: #17703f }
.txt-warn { color: #b56b12 }
.txt-red { color: #bd1f2d }
@media (max-width: 900px) { .tres { grid-template-columns: 1fr } }
</style>
