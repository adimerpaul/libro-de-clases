<template>
  <div class="card">
    <div class="cardHead">
      <h3>OA de Orientación vinculados a lo socioemocional</h3>
      <span class="subtle">Desde el catálogo curricular · cubierto = hay una sesión realizada con ese OA</span>
    </div>
    <q-inner-loading :showing="cargando" />
    <div v-if="!cargando && !datos?.asignatura" class="subtle">No existe la asignatura Orientación (código ORI) en el catálogo.</div>
    <div v-else-if="!cargando && !datos.oa.length" class="subtle">
      No hay OA de Orientación vigentes en el catálogo. Cárguelos en <router-link to="/curriculum">Currículum y OA</router-link>.
    </div>
    <q-markup-table v-else flat dense class="tabla">
      <thead><tr><th class="text-left">Nivel</th><th class="text-left">OA</th><th class="text-left">Competencias trabajadas</th><th>Sesiones</th><th>Estado en el plan</th></tr></thead>
      <tbody>
        <tr v-for="o in datos?.oa || []" :key="o.id">
          <td>{{ o.nivel }}</td>
          <td>
            <span class="badge violeta">{{ o.codigo }}</span><span v-if="o.es_demo" class="badge q-ml-xs">demo</span>
            <div class="texto">{{ o.texto }}</div>
          </td>
          <td>
            <span v-for="c in o.competencias" :key="c" class="badge q-mr-xs" :style="{ color: colorCompetencia(cat, c), borderColor: colorCompetencia(cat, c) + '55' }">
              {{ etiqueta(cat?.competencias, c) }}
            </span>
            <span v-if="!o.competencias.length" class="subtle">—</span>
          </td>
          <td class="text-center">{{ o.sesiones }}</td>
          <td class="text-center"><span class="badge" :class="ESTADO[o.estado][1]">{{ ESTADO[o.estado][0] }}</span></td>
        </tr>
      </tbody>
    </q-markup-table>
    <p class="subtle q-mt-md q-mb-none">
      Cada sesión del plan puede vincularse a un OA de Orientación, lo que deja evidencia para el PME y la priorización curricular.
    </p>
  </div>
</template>

<script setup>
import { getCurrentInstance, onMounted, ref } from 'vue'
import { useCatalogosEmocional, etiqueta, colorCompetencia } from '../../composables/emocional'
import { mensajeError } from '../../composables/useCrud'

const { proxy } = getCurrentInstance()
const cat = useCatalogosEmocional()
const datos = ref(null)
const cargando = ref(true)
const ESTADO = { cubierto: ['Cubierto', 'ok'], parcial: ['Parcial', 'warn'], pendiente: ['Pendiente', ''] }

onMounted(() => {
  proxy.$axios.get('emocional/orientacion')
    .then(res => { datos.value = res.data })
    .catch(e => proxy.$alert.error(mensajeError(e)))
    .finally(() => { cargando.value = false })
})
</script>

<style scoped>
.tabla th { font-size: 11px; color: #6d8090; text-transform: uppercase }
.texto { font-size: 12.5px; margin-top: 4px; white-space: normal; max-width: 520px }
</style>
