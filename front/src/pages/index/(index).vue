<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <h1>Hola, {{ $auth.user.name || '...' }}</h1>
        <p>
          Desde aquí accederás al Libro de Clases y a los módulos del ecosistema a medida que estén
          disponibles. En el menú lateral verás las secciones para las que tienes permiso.
        </p>
      </div>
    </div>

    <div class="moduleGrid">
      <div v-for="m in modulos" :key="m.title" class="card module" :style="{ background: m.bg }">
        <div class="moduleTop">
          <q-icon :name="m.icon" :style="{ color: m.color }" class="moduleIcon" />
          <span class="badge">{{ m.tag }}</span>
        </div>
        <h3>{{ m.title }}</h3>
        <p>{{ m.text }}</p>
        <q-btn
          v-if="m.link && $auth.hasPermission(m.permiso)" :to="m.link" label="Abrir" no-caps unelevated dense
          class="btnPrimary btnAbrir" icon-right="fas fa-arrow-right"
        />
        <span v-else class="soon">{{ m.link ? 'Requiere permiso' : 'Próximamente' }}</span>
      </div>
    </div>
  </q-page>
</template>

<script setup>
defineOptions({ name: 'InicioPage' })
definePage({ meta: { title: 'Inicio' } })

// Módulos de ui2/app.js (homePage); se irán habilitando uno a uno
const modulos = [
  { icon: 'fas fa-book-open', color: '#0f4f85', title: 'Libro de Clases 3.0', text: 'Núcleo oficial: asistencia, leccionario, evaluaciones, observaciones y firma.', bg: '#eaf3fb', tag: 'Núcleo' },
  { icon: 'far fa-calendar-days', color: '#1f8a5b', title: 'Planificador', link: '/planificador', permiso: 'clases.dictar', text: 'Semana, OA, inicio/desarrollo/cierre, evidencia y adecuaciones.', bg: '#eef8f2', tag: 'Docente' },
  { icon: 'fas fa-bullseye', color: '#6b4fbb', title: 'Currículum + OA', text: 'Catálogo curricular versionado y conectado con la planificación.', bg: '#f1edff', tag: 'Mineduc' },
  { icon: 'fas fa-file-pen', color: '#e07a1f', title: 'Evaluaciones', link: '/evaluaciones', permiso: 'clases.dictar', text: 'Instrumentos por asignatura, OA, puntaje y escala de notas.', bg: '#fff1e8', tag: 'Docente' },
  { icon: 'fas fa-door-open', color: '#bd1f2d', title: 'Retiro de estudiantes', text: 'Quién retira, relación, autorización, motivo, hora y firma.', bg: '#fdeef0', tag: 'Inspectoría' },
  { icon: 'fas fa-chart-line', color: '#0f4f85', title: 'Reportes', text: 'Asistencia, incidencias, cumplimiento y auditoría.', bg: '#eef4f8', tag: 'Gestión' },
]
</script>

<style scoped>
.page { padding: 24px; max-width: 1580px; margin: auto; color: #15324a }
.pageHead { margin-bottom: 18px }
.pageHead h1 { font-size: 30px; font-weight: 800; letter-spacing: -.035em; line-height: 1.2; margin: 0 }
.pageHead p { margin: 5px 0 0; color: #6d8090; max-width: 840px; line-height: 1.5 }
.moduleGrid { display: grid; gap: 15px; grid-template-columns: repeat(3, 1fr) }
.card { border: 1px solid #d9e3ec; border-radius: 18px; box-shadow: 0 7px 22px rgba(22, 52, 80, .055); padding: 17px }
.moduleTop { display: flex; justify-content: space-between; align-items: center }
.moduleIcon { font-size: 26px }
.badge { font-size: 10px; font-weight: 800; background: #fff; border: 1px solid #d9e3ec; border-radius: 999px; padding: 4px 9px }
.module h3 { font-size: 16px; font-weight: 800; margin: 12px 0 4px; line-height: 1.3 }
.module p { font-size: 12px; color: #486477; line-height: 1.5; margin: 0 0 10px }
.btnAbrir { padding: 2px 12px; font-size: 12px }
.soon { font-size: 11px; font-weight: 700; color: #6d8090 }

@media (max-width: 1023px) { .moduleGrid { grid-template-columns: repeat(2, 1fr) } }
@media (max-width: 720px) {
  .page { padding: 14px }
  .moduleGrid { grid-template-columns: 1fr }
}
</style>
