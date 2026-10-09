<template>
  <q-layout view="hHh lpR fFf" class="verification-layout">
    <q-header class="bg-primary text-white">
      <q-toolbar class="verification-toolbar">
        <q-icon name="biotech" size="30px" class="q-mr-sm" />
        <div>
          <div class="text-subtitle1 text-weight-bold">Laboratorio de Diagnóstico Clínico URME</div>
          <div class="text-caption">Verificación pública de resultados</div>
        </div>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <q-page class="q-pa-md">
        <div class="verification-content">
          <div v-if="loading" class="column items-center q-pa-xl q-gutter-md">
            <q-spinner color="primary" size="48px" />
            <div class="text-grey-7">Verificando documento...</div>
          </div>

          <q-card v-else-if="error" flat bordered class="q-pa-lg text-center">
            <q-icon name="gpp_bad" color="negative" size="64px" />
            <div class="text-h6 text-negative q-mt-sm">Documento no encontrado</div>
            <div class="text-body2 text-grey-7">El código no existe o el enlace no es válido.</div>
          </q-card>

          <template v-else>
            <q-banner rounded class="bg-green-1 text-green-9 q-mb-md">
              <template #avatar><q-icon name="verified" color="positive" size="34px" /></template>
              <div class="text-subtitle1 text-weight-bold">Documento auténtico</div>
              <div class="text-caption">El informe coincide con un registro emitido por Laboratorio URME.</div>
            </q-banner>

            <q-card flat bordered>
              <q-card-section class="row q-col-gutter-md">
                <div class="col-12 col-sm-6"><b>Solicitud:</b> {{ informe.codigo_solicitud }}</div>
                <div class="col-12 col-sm-6"><b>Fecha:</b> {{ fecha(informe.fecha_solicitud) }} {{ informe.hora_solicitud }}</div>
                <div class="col-12 col-sm-8"><b>Paciente:</b> {{ informe.paciente.nombre_completo }}</div>
                <div class="col-12 col-sm-4"><b>CI:</b> {{ informe.paciente.ci || '-' }}</div>
                <div class="col-12 col-sm-6"><b>Médico:</b> {{ informe.doctor?.nombre || 'NO ASIGNADO' }}</div>
                <div class="col-12 col-sm-6"><b>Estado:</b> {{ informe.estado }}</div>
                <div class="col-12"><b>Diagnóstico:</b> {{ informe.diagnostico_clinico || '-' }}</div>
              </q-card-section>
            </q-card>

            <q-card v-for="laboratorio in informe.laboratorios" :key="laboratorio.nombre"
                    flat bordered class="q-mt-md">
              <q-card-section class="bg-blue-grey-1 text-weight-bold text-primary">
                {{ laboratorio.nombre }}
              </q-card-section>
              <q-markup-table flat dense separator="horizontal">
                <thead><tr>
                  <th class="text-left">Análisis</th><th class="text-left">Resultado</th>
                  <th class="text-left">Referencia</th><th class="text-left">Método</th><th class="text-left">Muestra</th>
                </tr></thead>
                <tbody><tr v-for="resultado in laboratorio.resultados" :key="resultado.nombre">
                  <td>{{ resultado.nombre }}</td>
                  <td class="text-weight-bold">{{ resultado.valor || '-' }} {{ resultado.unidad || '' }}</td>
                  <td><div v-if="resultado.rango_referencia" class="html-rango" v-html="resultado.rango_referencia_html" /><template v-else>-</template></td>
                  <td>{{ resultado.metodo || '-' }}</td>
                  <td>{{ resultado.muestra || '-' }}</td>
                </tr></tbody>
              </q-markup-table>
            </q-card>

            <div v-if="informe.observaciones" class="q-mt-md text-body2">
              <b>Observaciones:</b> {{ informe.observaciones }}
            </div>
            <div class="text-caption text-grey-6 text-center q-mt-lg">
              Código de verificación: {{ informe.codigo_verificacion }}
            </div>
          </template>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { getCurrentInstance, onMounted, ref } from 'vue'

const { proxy } = getCurrentInstance()
const loading = ref(true)
const error = ref(false)
const informe = ref(null)

function fecha (value) {
  if (!value) return '-'
  return new Date(`${value}T00:00:00`).toLocaleDateString('es-BO')
}

async function cargarInforme () {
  try {
    const { data } = await proxy.$axios.get(`verificacion-laboratorio/${proxy.$route.params.codigo}`)
    informe.value = data
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

onMounted(cargarInforme)
</script>

<style scoped>
.verification-layout { background: #f4f7f9; min-height: 100vh; }
.verification-toolbar { max-width: 1120px; margin: 0 auto; width: 100%; }
.verification-content { max-width: 1120px; margin: 0 auto; }
@media (max-width: 700px) {
  :deep(.q-table__container) { overflow-x: auto; }
}
</style>
