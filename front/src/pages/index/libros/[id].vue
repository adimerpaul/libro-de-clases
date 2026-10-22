<template>
  <q-page class="page">
    <div class="pageHead">
      <div>
        <q-btn flat dense no-caps icon="fas fa-arrow-left" label="Todos los libros" class="volver" to="/libros" />
        <h1>Libro de Clases · {{ curso?.nombre || '…' }} <span v-if="curso" class="anio">{{ curso.anio }}</span></h1>
        <p>Hojea el libro como uno físico: portada, identificación del curso y nómina con sus apoderados. Haz clic en un estudiante para ver su ficha.</p>
      </div>
      <div class="row q-gutter-sm">
        <q-btn no-caps flat icon="fas fa-chevron-left" label="Anterior" :disable="hoja === 0" @click="pasar(-1)" />
        <q-btn no-caps unelevated class="btnPrimary" icon-right="fas fa-chevron-right" label="Siguiente" :disable="hoja === hojas.length - 1" @click="pasar(1)" />
      </div>
    </div>

    <div class="bookDesk">
      <q-spinner v-if="loading" color="white" size="40px" />

      <div v-else-if="curso" class="bookShell">
        <div class="bookBack" />
        <div class="spine" />

        <!-- Portada -->
        <div v-if="actual.tipo === 'portada'" class="cover">
          <div class="insideCover">
            <div class="indice">
              <div class="bookTitle">Índice</div>
              <button v-for="(h, i) in hojas.slice(1)" :key="i" class="indiceLinea" @click="hoja = i + 1">
                <span>{{ h.titulo }}</span><b>{{ 2 * (i + 1) - 1 }}</b>
              </button>
            </div>
          </div>
          <div class="frontCover">
            <div class="eyebrow">ECOSISTEMA 3.0</div>
            <h2>LIBRO DE<br>CLASES 3.0</h2>
            <div class="v">{{ curso.nombre }} · {{ curso.anio }}</div>
            <div class="coverCard">
              <div class="coverLine">Curso · {{ curso.nombre }} · Año {{ curso.anio }}</div>
              <div class="coverLine">Profesor(a) jefe · {{ curso.profesor_jefe?.name || 'Sin asignar' }}</div>
              <div class="coverLine">Matrícula · {{ activas.length }} estudiantes</div>
            </div>
            <div class="coverBadge"><q-icon name="fas fa-book-open" size="22px" /><br>LIBRO VIVO<br>Y TRAZABLE</div>
          </div>
        </div>

        <!-- Identificación del curso + asignaturas -->
        <div v-else-if="actual.tipo === 'datos'" class="spread">
          <section class="paper left">
            <div class="watermark">LIBRO<br>3.0</div>
            <div class="bookTitle">Identificación del curso</div>
            <div class="paperInfo">
              <div class="line"><b>Curso</b><span>{{ curso.nombre }}</span></div>
              <div class="line"><b>Nivel</b><span>{{ curso.nivel }}</span></div>
              <div class="line"><b>Año escolar</b><span>{{ curso.anio }}</span></div>
              <div class="line"><b>Profesor(a) jefe</b><span>{{ curso.profesor_jefe?.name || 'Sin asignar' }}</span></div>
              <div class="line"><b>Correo jefatura</b><span>{{ curso.profesor_jefe?.email || '—' }}</span></div>
            </div>
            <div class="paperBar">Resumen de matrícula</div>
            <div class="paperInfo">
              <div class="line"><b>Matrícula vigente</b><span>{{ activas.length }} estudiantes</span></div>
              <div class="line"><b>Mujeres / hombres</b><span>{{ porSexo('F') }} / {{ porSexo('M') }}</span></div>
              <div class="line"><b>PIE</b><span>{{ activas.filter(m => m.estudiante.pie).length }} estudiantes</span></div>
              <div class="line"><b>Retirados / trasladados</b><span>{{ curso.matriculas.length - activas.length }}</span></div>
            </div>
            <div class="pageNo">1</div>
          </section>
          <section class="paper right">
            <div class="watermark">LIBRO<br>3.0</div>
            <div class="bookTitle">Asignaturas y docentes</div>
            <table class="paperTable">
              <thead><tr><th>Asignatura</th><th>Docente</th><th>Hrs.</th></tr></thead>
              <tbody>
                <tr v-for="a in curso.asignaciones" :key="a.id">
                  <td><span class="punto" :style="{ background: a.asignatura.color || '#1f6fb2' }" />{{ a.asignatura.nombre }}</td>
                  <td>{{ a.docente?.name || '—' }}</td>
                  <td class="centro">{{ a.horas_semanales || '—' }}</td>
                </tr>
                <tr v-if="!curso.asignaciones.length"><td colspan="3" class="centro">Sin asignación docente registrada</td></tr>
              </tbody>
            </table>
            <div class="pageNo">2</div>
          </section>
        </div>

        <!-- Nómina: izquierda estudiantes, derecha apoderados (misma fila en ambas hojas) -->
        <div v-else class="spread">
          <section class="paper left">
            <div class="watermark">LIBRO<br>3.0</div>
            <div class="bookTitle">Nómina del curso</div>
            <table class="paperTable filas">
              <thead><tr><th>N°</th><th>Estudiante</th><th>RUN</th><th>F. nac.</th></tr></thead>
              <tbody>
                <tr v-for="m in actual.filas" :key="m.id" :class="{ inactivo: m.estado !== 'activa' }" @click="ficha = m">
                  <td class="centro">{{ m.numero_lista }}</td>
                  <td>{{ m.estudiante.nombre_completo }} <span v-if="m.estudiante.pie" class="pie">PIE</span></td>
                  <td class="nowrap">{{ formatoRun(m.estudiante.run) }}</td>
                  <td class="nowrap">{{ fechaCl(m.estudiante.fecha_nacimiento) || '—' }}</td>
                </tr>
              </tbody>
            </table>
            <div class="pageNo">{{ 2 * hoja - 1 }}</div>
          </section>
          <section class="paper right">
            <div class="watermark">LIBRO<br>3.0</div>
            <div class="bookTitle">Apoderados y contacto</div>
            <table class="paperTable filas">
              <thead><tr><th>N°</th><th>Apoderado(a)</th><th>Teléfono</th><th>Estado</th></tr></thead>
              <tbody>
                <tr v-for="m in actual.filas" :key="m.id" :class="{ inactivo: m.estado !== 'activa' }" @click="ficha = m">
                  <td class="centro">{{ m.numero_lista }}</td>
                  <td>{{ m.estudiante.apoderado_nombre || '—' }}</td>
                  <td class="nowrap">{{ m.estudiante.apoderado_telefono || '—' }}</td>
                  <td>{{ estadoTxt[m.estado] }}{{ m.fecha_retiro ? ` ${fechaCl(m.fecha_retiro)}` : '' }}</td>
                </tr>
              </tbody>
            </table>
            <div class="pageNo">{{ 2 * hoja }}</div>
          </section>
        </div>

        <div class="bookTabs">
          <button class="bookTab" :class="{ active: actual.tipo === 'datos' }" @click="hoja = 1">DATOS</button>
          <button class="bookTab" :class="{ active: actual.tipo === 'nomina' }" @click="hoja = Math.max(2, hoja)">NÓMINA</button>
        </div>

        <div class="bookControls">
          <button class="turn" :disabled="hoja === 0" aria-label="Hoja anterior" @click="pasar(-1)"><q-icon name="fas fa-chevron-left" size="14px" /></button>
          <div class="bookIndicator">{{ actual.titulo }}</div>
          <button class="turn" :disabled="hoja === hojas.length - 1" aria-label="Hoja siguiente" @click="pasar(1)"><q-icon name="fas fa-chevron-right" size="14px" /></button>
        </div>
      </div>
    </div>

    <!-- Ficha del estudiante -->
    <q-dialog :model-value="!!ficha" @update:model-value="v => { if (!v) ficha = null }">
      <q-card v-if="ficha" class="formCard">
        <q-card-section class="fichaHead">
          <div class="avatar">{{ iniciales(ficha.estudiante) }}</div>
          <div class="col">
            <div class="formTitle">{{ ficha.estudiante.nombres }} {{ ficha.estudiante.apellido_paterno }} {{ ficha.estudiante.apellido_materno }}</div>
            <div class="subtle">N° {{ ficha.numero_lista }} · {{ curso.nombre }} {{ curso.anio }} · RUN {{ formatoRun(ficha.estudiante.run) }}</div>
          </div>
          <q-btn flat round dense icon="fas fa-xmark" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="chips">
            <span class="badge" :class="ficha.estado === 'activa' ? 'ok' : 'red'">Matrícula {{ estadoTxt[ficha.estado].toLowerCase() }}</span>
            <span v-if="ficha.estudiante.pie" class="badge violeta">PIE</span>
          </div>
          <div class="fichaBloque">Estudiante</div>
          <div class="datos">
            <div><b>Fecha de nacimiento</b><span>{{ fechaCl(ficha.estudiante.fecha_nacimiento) || '—' }}{{ edad(ficha.estudiante.fecha_nacimiento) }}</span></div>
            <div><b>Sexo</b><span>{{ sexoTxt[ficha.estudiante.sexo] || '—' }}</span></div>
            <div class="full"><b>Dirección</b><span>{{ ficha.estudiante.direccion || '—' }}</span></div>
            <div><b>Fecha de matrícula</b><span>{{ fechaCl(ficha.fecha_matricula) || '—' }}</span></div>
            <div v-if="ficha.estado !== 'activa'"><b>Retiro</b><span>{{ fechaCl(ficha.fecha_retiro) }}{{ ficha.motivo_retiro ? ` · ${ficha.motivo_retiro}` : '' }}</span></div>
          </div>
          <div class="fichaBloque">Apoderado(a)</div>
          <div class="datos">
            <div class="full"><b>Nombre</b><span>{{ ficha.estudiante.apoderado_nombre || '—' }}</span></div>
            <div><b>RUN</b><span>{{ formatoRun(ficha.estudiante.apoderado_run) }}</span></div>
            <div><b>Teléfono</b><span>{{ ficha.estudiante.apoderado_telefono || '—' }}</span></div>
            <div class="full"><b>Correo</b><span>{{ ficha.estudiante.apoderado_email || '—' }}</span></div>
          </div>
          <template v-if="ficha.estudiante.observaciones">
            <div class="fichaBloque">Observaciones</div>
            <p class="obs">{{ ficha.estudiante.observaciones }}</p>
          </template>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, getCurrentInstance } from 'vue'
import { useRoute } from 'vue-router'
import { mensajeError } from '../../../composables/useCrud'
import { formatoRun } from '../../../composables/formato'
import { fechaCl } from '../../../composables/fechas'

definePage({ meta: { title: 'Libro de clases', permiso: ['libros.ver', 'clases.dictar'] } })

const { proxy } = getCurrentInstance()
const route = useRoute()

const POR_HOJA = 15
const estadoTxt = { activa: 'Activa', retirada: 'Retirada', trasladada: 'Trasladada' }
const sexoTxt = { F: 'Femenino', M: 'Masculino', X: 'Otro / no informa' }

const curso = ref(null)
const loading = ref(false)
const hoja = ref(0)
const ficha = ref(null)

const activas = computed(() => (curso.value?.matriculas || []).filter(m => m.estado === 'activa'))

// Portada, identificación y una doble página por cada tramo de la nómina
const hojas = computed(() => {
  const nomina = curso.value?.matriculas || []
  const res = [{ tipo: 'portada', titulo: 'Portada' }, { tipo: 'datos', titulo: 'Identificación del curso' }]
  for (let i = 0; i < Math.max(nomina.length, 1); i += POR_HOJA) {
    const filas = nomina.slice(i, i + POR_HOJA)
    const rango = filas.length ? `N° ${filas[0].numero_lista}–${filas[filas.length - 1].numero_lista}` : 'sin estudiantes'
    res.push({ tipo: 'nomina', titulo: `Nómina · ${rango}`, filas })
  }
  return res
})
const actual = computed(() => hojas.value[hoja.value] || hojas.value[0])

function pasar (d) {
  hoja.value = Math.min(hojas.value.length - 1, Math.max(0, hoja.value + d))
}

function porSexo (s) {
  return activas.value.filter(m => m.estudiante.sexo === s).length
}

function iniciales (e) {
  return `${e.nombres?.[0] || ''}${e.apellido_paterno?.[0] || ''}`.toUpperCase()
}

function edad (fecha) {
  if (!fecha) return ''
  const [a, m, d] = fecha.split('-').map(Number)
  const hoy = new Date()
  const anios = hoy.getFullYear() - a - ((hoy.getMonth() + 1 < m || (hoy.getMonth() + 1 === m && hoy.getDate() < d)) ? 1 : 0)
  return ` (${anios} años)`
}

// Flechas del teclado para hojear (salvo con la ficha abierta)
function teclas (e) {
  if (ficha.value || ['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return
  if (e.key === 'ArrowRight') pasar(1)
  if (e.key === 'ArrowLeft') pasar(-1)
}

onMounted(() => {
  window.addEventListener('keydown', teclas)
  loading.value = true
  proxy.$axios.get(`libros/${route.params.id}`)
    .then(res => { curso.value = res.data })
    .catch(err => {
      proxy.$alert.error(err.response?.status === 404 ? 'No tienes acceso a este libro o el curso no existe' : mensajeError(err))
      proxy.$router.replace('/libros')
    })
    .finally(() => { loading.value = false })
})
onBeforeUnmount(() => window.removeEventListener('keydown', teclas))
</script>

<style scoped>
.volver { color: #0f4f85; margin: 0 0 4px -6px; font-weight: 700 }
.anio { color: #6d8090; font-weight: 600 }

/* ── Libro físico-digital (ui2/index.html: .bookDesk, .paper, .cover…) ── */
.bookDesk {
  --paper: #fffdf6; --paper2: #f4ede0;
  background: linear-gradient(130deg, #70442b, #a86d45 50%, #71462e); border-radius: 20px; padding: 24px 24px 72px;
  box-shadow: inset 0 0 0 1px #ffffff18; min-height: 680px; display: grid; place-items: center; position: relative; overflow: hidden;
}
.bookDesk:after { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(92deg, #ffffff05 0 2px, #0000 2px 10px); opacity: .35; pointer-events: none }
.bookShell { width: min(1120px, 94%); height: 570px; position: relative; z-index: 2; filter: drop-shadow(0 24px 22px #0007) }
.bookBack { position: absolute; inset: 12px 5px 2px; background: #17283a; border-radius: 13px }
.spread { position: absolute; inset: 0; display: grid; grid-template-columns: 1fr 1fr }
.paper { position: relative; background: linear-gradient(90deg, var(--paper), #fff 92%, var(--paper2)); border: 1px solid #cfd8df; padding: 26px 30px 36px; overflow: hidden }
.paper.left { border-radius: 11px 2px 3px 11px; box-shadow: inset -17px 0 25px #7f5c2a14 }
.paper.right { border-radius: 2px 11px 11px 3px; box-shadow: inset 17px 0 25px #7f5c2a14 }
.paper:after { content: ""; position: absolute; inset: 0; pointer-events: none; opacity: .16; background-image: radial-gradient(#74552a15 1px, transparent 1px); background-size: 4px 4px }
.spine { position: absolute; left: 50%; top: 14px; bottom: 10px; width: 25px; transform: translateX(-50%); background: linear-gradient(90deg, #0002, transparent 35%, #fff8 48%, transparent 62%, #0002); z-index: 8; pointer-events: none }
.watermark { position: absolute; left: 50%; top: 57%; transform: translate(-50%, -50%); font-size: 70px; font-weight: 900; color: #1f6fb20b; line-height: .78; text-align: center; pointer-events: none }
.pageNo { position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: 900; color: #2a608b }
.bookTitle { font-family: Georgia, "Times New Roman", serif; text-align: center; font-size: 20px; color: #155d96; text-transform: uppercase; margin-bottom: 11px }
.paperBar { background: #1f6fb2; color: #fff; padding: 6px 8px; font-size: 10px; font-weight: 900; text-transform: uppercase }
.paperTable { width: 100%; border-collapse: collapse; font-size: 11px; color: #224e73; background: #fff9; position: relative; z-index: 1 }
.paperTable th, .paperTable td { border: 1px solid #aac2d5; padding: 4px 6px; height: 24px }
.paperTable th { background: #e7f1f9; color: #275f90; font-size: 9px; text-transform: uppercase }
.paperTable.filas tbody tr { cursor: pointer }
.paperTable.filas tbody tr:hover td { background: #fff3c4 }
.paperTable .inactivo td { color: #9aabb8; text-decoration: line-through }
.centro { text-align: center }
.nowrap { white-space: nowrap }
.punto { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px }
.pie { font-size: 8px; font-weight: 900; color: #5a3fb0; border: 1px solid #ddd3fb; background: #f1edff; border-radius: 4px; padding: 0 3px; text-decoration: none }
.paperInfo { border: 1px solid #aac2d5; background: #fff9; margin-bottom: 8px; position: relative; z-index: 1 }
.paperInfo .line { display: grid; grid-template-columns: 150px 1fr; border-bottom: 1px solid #d4e0e8; min-height: 28px }
.paperInfo .line:last-child { border-bottom: 0 }
.paperInfo b { font-size: 9px; color: #3d6e96; text-transform: uppercase; padding: 7px 6px }
.paperInfo span { font-family: Georgia, serif; font-size: 13px; padding: 5px; color: #173f60 }

.cover { position: absolute; inset: 0; display: grid; grid-template-columns: 1fr 1fr }
.insideCover { background: #e8dfd0; border-radius: 10px 2px 2px 10px; padding: 40px 46px }
.indice { background: #fffdf6cc; border: 1px solid #cdbfa6; border-radius: 6px; padding: 18px 20px }
.indiceLinea { display: flex; justify-content: space-between; width: 100%; border: 0; border-bottom: 1px dotted #9c8a6b; background: none; padding: 8px 2px; font-family: Georgia, serif; font-size: 13px; color: #173f60; cursor: pointer; text-align: left }
.indiceLinea:hover { color: #bd1f2d }
.frontCover { background: linear-gradient(145deg, #c82130 0 56%, #e0e3e5 56% 70%, #991822 70%); border-radius: 2px 10px 10px 2px; color: #fff; padding: 55px; position: relative }
.frontCover .eyebrow { font-size: 11px; font-weight: 800; letter-spacing: .14em; color: #ffd9dd }
.frontCover h2 { font-size: 53px; font-weight: 900; line-height: .88; margin: 8px 0 0 }
.frontCover .v { font-size: 22px; font-weight: 900; margin-top: 10px }
.coverCard { margin-top: 35px; background: #ffffffe9; color: #17324a; padding: 16px; border-radius: 5px }
.coverLine { border-bottom: 1px solid #6b8294; padding: 7px 3px; font-size: 10px; text-transform: uppercase }
.coverBadge { position: absolute; right: 35px; bottom: 35px; width: 112px; height: 112px; border-radius: 50%; display: grid; place-content: center; text-align: center; background: #1c2936; border: 5px solid #f3f4f6; font-size: 10px; font-weight: 900; line-height: 1.3 }

.bookTabs { position: absolute; right: -12px; top: 64px; display: flex; flex-direction: column; gap: 5px; z-index: 9 }
.bookTab { width: 25px; min-height: 86px; writing-mode: vertical-rl; border: 1px solid #aabfd0; border-left: 0; background: #e0ebf4; color: #356389; border-radius: 0 6px 6px 0; padding: 7px 3px; font-size: 9px; font-weight: 900; cursor: pointer }
.bookTab.active { background: #1f6fb2; color: #fff }
.bookControls { position: absolute; left: 50%; bottom: -54px; transform: translateX(-50%); display: flex; gap: 9px; align-items: center }
.turn { width: 39px; height: 39px; border-radius: 50%; border: 0; background: #fff; color: #183c5c; box-shadow: 0 6px 18px #0005; cursor: pointer }
.turn:disabled { opacity: .45; cursor: default }
.bookIndicator { background: #0a1c2bd9; color: #fff; border-radius: 999px; padding: 7px 14px; font-size: 11px; min-width: 200px; text-align: center }

/* Ficha */
.fichaHead { display: flex; align-items: center; gap: 12px }
.avatar { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; background: #e9eff5; color: #204664; font-weight: 900 }
.chips { display: flex; gap: 6px; margin-bottom: 4px }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
.fichaBloque { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #6d8090; margin: 14px 0 6px }
.datos { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px }
.datos .full { grid-column: 1 / -1 }
.datos b { display: block; font-size: 11px; color: #6d8090; font-weight: 600 }
.datos span { font-size: 13px }
.obs { font-size: 13px; line-height: 1.5; margin: 0; white-space: pre-line }

/* Pantallas angostas: las hojas se apilan y el libro crece en alto */
@media (max-width: 900px) {
  .bookDesk { padding: 12px 12px 72px; min-height: 0 }
  .bookShell { width: 100%; height: auto }
  .bookBack, .spine, .bookTabs { display: none }
  .spread, .cover { position: static; grid-template-columns: 1fr; gap: 10px }
  .paper { border-radius: 10px !important; padding: 20px 14px 36px; overflow-x: auto }
  .insideCover { display: none }
  .frontCover { border-radius: 10px; padding: 30px 24px 150px }
  .frontCover h2 { font-size: 40px }
  .bookControls { bottom: -56px }
}
</style>
