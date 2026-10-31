<template>
  <q-page class="page">
    <q-btn flat dense no-caps icon="fas fa-arrow-left" label="Student 360" class="volver" @click="volver" />

    <div v-if="loading" class="card text-center q-pa-xl"><q-spinner size="30px" color="primary" /></div>

    <template v-else-if="d">
      <!-- Encabezado -->
      <div class="hero" :class="nivelTxt">
        <div class="heroAvatar">{{ iniciales }}</div>
        <div class="heroInfo">
          <div class="eyebrow">STUDENT 360 · {{ d.anio }}</div>
          <h1>{{ e.nombres }} {{ e.apellido_paterno }} {{ e.apellido_materno }}</h1>
          <div class="heroMeta">
            <span v-if="m?.curso"><q-icon name="fas fa-chalkboard-user" size="12px" /> {{ m.curso.nombre }} · N° {{ m.numero_lista }}</span>
            <span><q-icon name="far fa-id-card" size="12px" /> RUN {{ formatoRun(e.run) }}</span>
            <span v-if="e.fecha_nacimiento"><q-icon name="fas fa-cake-candles" size="12px" /> {{ fechaCl(e.fecha_nacimiento) }} · {{ edad }} años</span>
            <span v-if="m?.curso?.profesor_jefe"><q-icon name="fas fa-user-tie" size="12px" /> Jefatura: {{ m.curso.profesor_jefe }}</span>
          </div>
          <div class="chips">
            <span class="badge" :class="m?.estado === 'activa' ? 'ok' : 'red'">Matrícula {{ estadoTxt[m?.estado] || 'sin registro' }}</span>
            <span v-if="d.pie.tiene" class="badge violeta"><q-icon name="fas fa-puzzle-piece" size="9px" /> Apoyo PIE</span>
            <span v-for="(a, i) in d.alertas" :key="i" class="badge" :class="a.nivel === 'critica' ? 'red' : 'warn'">
              <q-icon :name="a.nivel === 'critica' ? 'fas fa-circle-exclamation' : 'fas fa-triangle-exclamation'" size="9px" /> {{ a.texto }}
            </span>
          </div>
        </div>
        <div class="heroKpis">
          <div class="anillo" :style="{ '--p': d.asistencia?.porcentaje ?? 0 }">
            <span><b>{{ pct(d.asistencia?.porcentaje) }}</b><small>asistencia</small></span>
          </div>
          <div class="miniKpis">
            <div><b>{{ d.asistencia?.dias ?? '—' }}<small>/{{ d.asistencia?.lectivos ?? '—' }}</small></b><span>días presentes</span></div>
            <div><b>{{ d.asistencia?.atrasos ?? '—' }}</b><span>atrasos</span></div>
            <div><b>{{ d.asignaturas.length }}</b><span>asignaturas</span></div>
            <div><b>{{ totalInstrumentos }}</b><span>evaluaciones</span></div>
          </div>
        </div>
      </div>

      <div class="layout">
        <div class="col1">
          <!-- Asistencia mensual -->
          <div class="card">
            <div class="cardHead">
              <h3><q-icon name="fas fa-calendar-check" class="hIcon" /> Asistencia mes a mes</h3>
              <span class="subtle">según RFID · propuesta</span>
            </div>
            <div v-if="!d.mensual.length" class="vacio">Sin marcas RFID registradas este año.</div>
            <template v-else>
              <div class="chart" role="img" :aria-label="d.mensual.map(x => `${MESES[x.mes]}: ${pct(x.porcentaje)}`).join(', ')">
                <div class="ejes">
                  <span style="bottom: 100%">100</span>
                  <span :style="{ bottom: `${escala(d.umbrales.riesgo)}%` }">{{ d.umbrales.riesgo }}</span>
                  <span :style="{ bottom: `${escala(d.umbrales.promocion)}%` }">{{ d.umbrales.promocion }}</span>
                  <span style="bottom: 0">{{ PISO }}</span>
                </div>
                <div class="plot">
                  <div class="umbral riesgo" :style="{ bottom: `${escala(d.umbrales.riesgo)}%` }" />
                  <div class="umbral promocion" :style="{ bottom: `${escala(d.umbrales.promocion)}%` }" />
                  <div v-for="x in d.mensual" :key="x.mes" class="mesCol">
                    <div class="valor">{{ Math.round(x.porcentaje) }}</div>
                    <div class="bar" :class="nivelDe(x.porcentaje)" :style="{ height: `${escala(x.porcentaje)}%` }" />
                    <q-tooltip anchor="top middle" self="bottom middle">
                      <b>{{ MESES_LARGO[x.mes] }}</b>: {{ pct(x.porcentaje) }}<br>
                      {{ x.dias }} de {{ x.lectivos }} días · {{ x.atrasos }} atrasos
                    </q-tooltip>
                  </div>
                </div>
              </div>
              <div class="meses">
                <span v-for="x in d.mensual" :key="x.mes">{{ MESES[x.mes] }}</span>
              </div>
              <div class="leyenda">
                <span><i class="ln riesgo" /> 90 % ausentismo crónico</span>
                <span><i class="ln promocion" /> 85 % requisito de promoción</span>
              </div>
              <div v-if="d.ausencias.length" class="ausencias">
                <b>Últimas inasistencias</b>
                <span v-for="a in d.ausencias" :key="a" class="chipFecha">{{ diaCl(a) }}</span>
              </div>
            </template>
          </div>

          <!-- Asignaturas -->
          <div class="card">
            <div class="cardHead">
              <h3><q-icon name="fas fa-book-open" class="hIcon" /> Asignaturas y evaluaciones</h3>
              <span class="subtle">{{ m?.curso?.nombre }}</span>
            </div>
            <div class="asigFiltro">
              <button class="chipAsig" :class="{ on: !asig }" @click="asig = null">Todas</button>
              <button
                v-for="a in d.asignaturas" :key="a.id" class="chipAsig" :class="{ on: asig === a.asignatura.id }"
                :style="{ '--c': a.asignatura.color || '#1f6fb2' }" @click="asig = asig === a.asignatura.id ? null : a.asignatura.id"
              >
                <span class="punto" />{{ a.asignatura.nombre }}
              </button>
            </div>
            <div v-if="!d.asignaturas.length" class="vacio">El curso no tiene asignación docente registrada.</div>
            <div class="asigLista">
              <div v-for="a in asignaturasVisibles" :key="a.id" class="asig" :style="{ '--c': a.asignatura.color || '#1f6fb2' }">
                <div class="asigHead">
                  <div class="asigIcon">{{ (a.asignatura.codigo || a.asignatura.nombre).slice(0, 3).toUpperCase() }}</div>
                  <div class="col">
                    <b>{{ a.asignatura.nombre }}</b>
                    <div class="subtle">{{ a.docente?.name || 'Sin docente' }}<template v-if="a.horas_semanales"> · {{ a.horas_semanales }} h semanales</template></div>
                  </div>
                  <div class="asigStats">
                    <span><b>{{ a.instrumentos.length }}</b> evaluaciones</span>
                    <span><b>{{ a.planificaciones.realizadas }}/{{ a.planificaciones.total }}</b> clases realizadas</span>
                  </div>
                </div>
                <div v-if="a.planificaciones.total" class="progreso">
                  <div :style="{ width: `${(a.planificaciones.realizadas * 100) / a.planificaciones.total}%` }" />
                </div>
                <div v-if="a.instrumentos.length" class="instrumentos">
                  <div v-for="i in a.instrumentos" :key="i.id" class="inst">
                    <q-icon name="fas fa-file-pen" size="13px" class="instIcon" />
                    <div class="col">
                      <b>{{ i.nombre }}</b>
                      <div class="subtle">{{ i.tipo }}<template v-if="i.oa"> · {{ i.oa }}</template> · {{ i.preguntas }} ítems · {{ fmtNum(i.puntaje) }} pts</div>
                    </div>
                    <span class="subtle nowrap">{{ i.fecha_aplicacion ? fechaCl(i.fecha_aplicacion) : 'Sin fecha' }}</span>
                    <span class="badge" :class="i.estado === 'listo' ? 'ok' : ''">{{ i.estado === 'listo' ? 'Lista' : 'Borrador' }}</span>
                    <span class="nota">Nota —<q-tooltip>Las calificaciones se registrarán con el corrector integrado</q-tooltip></span>
                  </div>
                </div>
                <div v-else class="subtle sinInst">Sin evaluaciones creadas para este curso.</div>
                <div v-if="a.oa_trabajados.length" class="oas">
                  <span v-for="o in a.oa_trabajados" :key="o.codigo" class="chipOa">{{ o.codigo }}<q-tooltip max-width="320px">{{ o.texto }}</q-tooltip></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col2">
          <!-- Trayectoria -->
          <div class="card">
            <div class="cardHead"><h3><q-icon name="fas fa-route" class="hIcon" /> Trayectoria escolar</h3></div>
            <div class="timeline">
              <div v-for="(t, i) in d.trayectoria" :key="i" class="evento" :class="t.estado">
                <div class="punto" />
                <div>
                  <b>{{ t.anio }} · {{ t.curso }}</b>
                  <small>N° {{ t.numero_lista }} · matrícula {{ fechaCl(t.fecha_matricula) || '—' }}</small>
                  <small v-if="t.estado !== 'activa'" class="retiro">{{ estadoTxt[t.estado] }} {{ fechaCl(t.fecha_retiro) }}{{ t.motivo_retiro ? ` · ${t.motivo_retiro}` : '' }}</small>
                </div>
              </div>
            </div>
          </div>

          <!-- PIE -->
          <div class="card">
            <div class="cardHead">
              <h3><q-icon name="fas fa-puzzle-piece" class="hIcon" /> Apoyos PIE</h3>
              <span v-if="d.pie.caso" class="badge violeta">{{ d.pie.caso.estado_txt }}</span>
            </div>
            <div v-if="d.pie.restringido" class="restringido">
              <q-icon name="fas fa-lock" size="14px" />
              <span>{{ d.pie.tiene ? 'El estudiante tiene apoyo PIE.' : 'Sin apoyo PIE registrado.' }} El detalle es información sensible y solo lo ve el equipo PIE.</span>
            </div>
            <div v-else-if="!d.pie.caso" class="subtle">Sin caso PIE registrado.</div>
            <template v-else>
              <div class="pieDiag">
                <span class="badge" :class="d.pie.caso.tipo_nee === 'permanente' ? 'violeta' : 'blue'">NEE {{ d.pie.caso.tipo_nee }}</span>
                <b>{{ d.pie.caso.diagnostico }}</b>
              </div>
              <div class="datos">
                <div><b>Responsable</b><span>{{ d.pie.caso.responsable || '—' }}</span></div>
                <div><b>Horas semanales</b><span>{{ d.pie.caso.horas_semanales || '—' }}</span></div>
                <div><b>Reevaluación</b><span>{{ fechaCl(d.pie.caso.fecha_reevaluacion) || '—' }}</span></div>
                <div><b>Bitácora</b><span>{{ d.pie.caso.seguimientos_count }} registros</span></div>
              </div>
              <div v-if="d.pie.caso.apoyos.length" class="apoyos">
                <span v-for="a in d.pie.caso.apoyos" :key="a" class="chipOa">{{ a }}</span>
              </div>
              <p v-if="d.pie.caso.fortalezas" class="texto"><b>Fortalezas:</b> {{ d.pie.caso.fortalezas }}</p>
              <p v-if="d.pie.caso.barreras" class="texto"><b>Barreras:</b> {{ d.pie.caso.barreras }}</p>
              <q-btn flat dense no-caps color="primary" icon-right="fas fa-arrow-right" label="Abrir caso PIE" :to="`/pie/${d.pie.caso.id}`" />
            </template>
          </div>

          <!-- Familia -->
          <div class="card">
            <div class="cardHead"><h3><q-icon name="fas fa-house-chimney-user" class="hIcon" /> Familia y contacto</h3></div>
            <div class="contacto">
              <div class="contAvatar"><q-icon name="fas fa-user" size="14px" /></div>
              <div>
                <b>{{ e.apoderado_nombre || 'Sin apoderado registrado' }}</b>
                <div class="subtle">Apoderado(a) · RUN {{ formatoRun(e.apoderado_run) }}</div>
              </div>
            </div>
            <div class="datos">
              <div><b>Teléfono</b><span><a v-if="e.apoderado_telefono" :href="`tel:${e.apoderado_telefono}`">{{ e.apoderado_telefono }}</a><template v-else>—</template></span></div>
              <div><b>Correo</b><span><a v-if="e.apoderado_email" :href="`mailto:${e.apoderado_email}`">{{ e.apoderado_email }}</a><template v-else>—</template></span></div>
              <div class="full"><b>Dirección</b><span>{{ e.direccion || '—' }}</span></div>
            </div>
          </div>

          <div v-if="e.observaciones" class="card">
            <div class="cardHead"><h3><q-icon name="far fa-note-sticky" class="hIcon" /> Observaciones</h3></div>
            <p class="texto">{{ e.observaciones }}</p>
          </div>
        </div>
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeError } from '../../../composables/useCrud'
import { formatoRun } from '../../../composables/formato'
import { fechaCl } from '../../../composables/fechas'

definePage({ meta: { title: 'Student 360', permiso: ['student360.ver', 'clases.dictar'] } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()

const MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
const MESES_LARGO = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const estadoTxt = { activa: 'activa', retirada: 'Retirada', trasladada: 'Trasladada' }
// El eje parte en 60 % para que se distingan 85 y 90 (se indica en el eje)
const PISO = 60

const d = ref(null)
const loading = ref(true)
const asig = ref(Number(route.query.asignatura_id) || null)

const e = computed(() => d.value?.estudiante || {})
const m = computed(() => d.value?.matricula)
const iniciales = computed(() => `${e.value.nombres?.[0] || ''}${e.value.apellido_paterno?.[0] || ''}`.toUpperCase())
const edad = computed(() => {
  if (!e.value.fecha_nacimiento) return ''
  const [a, mes, dia] = e.value.fecha_nacimiento.split('-').map(Number)
  const hoy = new Date()
  return hoy.getFullYear() - a - ((hoy.getMonth() + 1 < mes || (hoy.getMonth() + 1 === mes && hoy.getDate() < dia)) ? 1 : 0)
})
const totalInstrumentos = computed(() => (d.value?.asignaturas || []).reduce((s, a) => s + a.instrumentos.length, 0))
const asignaturasVisibles = computed(() => (d.value?.asignaturas || []).filter(a => !asig.value || a.asignatura.id === asig.value))

const pct = v => (v === null || v === undefined ? '—' : `${String(v).replace('.', ',')} %`)
const fmtNum = n => String(n).replace('.', ',')
const escala = p => Math.max(0, Math.min(100, ((p - PISO) * 100) / (100 - PISO)))
function nivelDe (p) {
  if (p === null || p === undefined) return 'nd'
  if (p >= 95) return 'ok'
  if (p >= 90) return 'bien'
  if (p >= 85) return 'riesgo'
  return 'critico'
}
const nivelTxt = computed(() => nivelDe(d.value?.asistencia?.porcentaje))
function diaCl (s) {
  const [a, mes, dia] = s.split('-').map(Number)
  return new Date(a, mes - 1, dia).toLocaleDateString('es-CL', { weekday: 'short', day: 'numeric', month: 'short' })
}

function volver () {
  if (window.history.state?.back?.startsWith('/student360')) router.back()
  else router.push('/student360')
}

onMounted(() => {
  // El detalle trae todas las asignaturas; el filtro de asignatura se aplica en pantalla
  proxy.$axios.get(`student360/${route.params.id}`, { params: { anio: route.query.anio } })
    .then(res => { d.value = res.data })
    .catch(err => {
      proxy.$alert.error(err.response?.status === 404 ? 'No tienes acceso a este estudiante o no existe' : mensajeError(err))
      router.replace('/student360')
    })
    .finally(() => { loading.value = false })
})
</script>

<style scoped>
.page { --ok: #17703f; --bien: #4f9a6d; --riesgo: #c98a12; --critico: #bd1f2d; --nd: #a9b8c6 }
.volver { color: #0f4f85; margin: 0 0 8px -6px; font-weight: 700 }

/* Encabezado */
.hero {
  --acc: var(--nd);
  display: grid; grid-template-columns: auto 1fr auto; gap: 22px; align-items: center; padding: 22px 24px; margin-bottom: 16px;
  border-radius: 22px; color: #fff; position: relative; overflow: hidden;
  background: radial-gradient(circle at 92% 10%, #ffffff1f 0 120px, transparent 121px), linear-gradient(135deg, #0b2440, #0f4f85 70%, #1f6fb2);
  box-shadow: 0 16px 36px rgba(11, 36, 64, .25)
}
.hero.ok { --acc: #4ade80 } .hero.bien { --acc: #86d6a4 } .hero.riesgo { --acc: #fbbf24 } .hero.critico { --acc: #ff6b78 }
.heroAvatar {
  width: 86px; height: 86px; border-radius: 50%; display: grid; place-items: center; font-size: 28px; font-weight: 900;
  background: #ffffff1c; border: 3px solid var(--acc); box-shadow: 0 0 0 6px #ffffff10
}
.heroInfo { min-width: 0 }
.hero .eyebrow { font-size: 10px; font-weight: 800; letter-spacing: .16em; color: #9cc3e6 }
.hero h1 { font-size: 26px; font-weight: 900; letter-spacing: -.03em; margin: 2px 0 6px; line-height: 1.15 }
.heroMeta { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 12px; color: #d3e4f3 }
.heroMeta .q-icon { opacity: .8; margin-right: 3px }
.chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px }
.heroKpis { display: flex; gap: 18px; align-items: center }
.anillo { --p: 0; width: 108px; height: 108px; border-radius: 50%; display: grid; place-items: center; background: conic-gradient(var(--acc) calc(var(--p) * 1%), #ffffff26 0) }
.anillo span { width: 88px; height: 88px; border-radius: 50%; background: #0d3a63; display: flex; flex-direction: column; align-items: center; justify-content: center }
.anillo b { font-size: 18px; font-weight: 900 }
.anillo small { font-size: 9px; color: #9cc3e6; text-transform: uppercase; letter-spacing: .08em }
.miniKpis { display: grid; grid-template-columns: repeat(2, minmax(92px, auto)); gap: 8px }
.miniKpis div { background: #ffffff14; border: 1px solid #ffffff1f; border-radius: 12px; padding: 7px 10px; display: flex; flex-direction: column }
.miniKpis b { font-size: 18px; font-weight: 900; line-height: 1.1 }
.miniKpis b small { font-size: 11px; color: #9cc3e6; font-weight: 700 }
.miniKpis span { font-size: 10px; color: #b8d3ea }

.layout { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(300px, 1fr); gap: 16px; align-items: start }
.col1, .col2 { display: flex; flex-direction: column; gap: 16px }
.hIcon { color: var(--role); font-size: 14px; margin-right: 6px }
.vacio { color: #6d8090; font-size: 13px; padding: 18px 0; text-align: center }
.subtle { font-size: 12px }
.nowrap { white-space: nowrap }

/* Gráfico mensual: barras finas, extremos redondeados, umbrales de referencia */
.chart { display: grid; grid-template-columns: 26px 1fr; gap: 6px; height: 190px; margin-top: 18px }
.ejes { position: relative }
.ejes span { position: absolute; right: 0; transform: translateY(50%); font-size: 10px; color: #8396a7 }
.plot { position: relative; display: flex; align-items: flex-end; gap: 2px; border-bottom: 1px solid #d9e3ec; background: repeating-linear-gradient(to top, transparent 0 calc(25% - 1px), #eef3f7 calc(25% - 1px) 25%) }
.umbral { position: absolute; left: 0; right: 0; border-top: 2px dashed; z-index: 1; pointer-events: none }
.umbral.riesgo { border-color: #c98a1299 }
.umbral.promocion { border-color: #bd1f2d99 }
.mesCol { flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; cursor: default; position: relative; z-index: 2 }
.mesCol:hover .bar { filter: brightness(1.12) }
.valor { font-size: 10px; font-weight: 800; color: #415c72; margin-bottom: 3px }
.bar { width: min(34px, 70%); border-radius: 4px 4px 0 0; background: var(--nd); min-height: 3px; transition: height .4s }
.bar.ok { background: var(--ok) } .bar.bien { background: var(--bien) } .bar.riesgo { background: var(--riesgo) } .bar.critico { background: var(--critico) }
.meses { display: flex; gap: 2px; margin-left: 32px; margin-top: 4px }
.meses span { flex: 1; text-align: center; font-size: 10px; color: #6d8090; font-weight: 700 }
.leyenda { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 10px; font-size: 11px; color: #6d8090 }
.ln { display: inline-block; width: 18px; border-top: 2px dashed; vertical-align: middle; margin-right: 4px }
.ln.riesgo { border-color: #c98a12 } .ln.promocion { border-color: #bd1f2d }
.ausencias { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #eef3f7; font-size: 12px }
.chipFecha { background: #fdeef0; color: #9a1b26; border-radius: 999px; padding: 2px 9px; font-size: 11px; text-transform: capitalize }

/* Asignaturas */
.asigFiltro { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px }
.chipAsig { --c: #0f4f85; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #d9e3ec; background: #fff; border-radius: 999px; padding: 5px 11px; font-size: 12px; color: #415c72; cursor: pointer }
.chipAsig .punto { width: 8px; height: 8px; border-radius: 50%; background: var(--c) }
.chipAsig.on { background: color-mix(in srgb, var(--c) 12%, #fff); border-color: var(--c); color: #0b2440; font-weight: 700 }
.asigLista { display: flex; flex-direction: column; gap: 10px }
.asig { border: 1px solid #e1e9f0; border-left: 4px solid var(--c); border-radius: 14px; padding: 12px 14px; background: #fcfdff }
.asigHead { display: flex; gap: 12px; align-items: center }
.asigIcon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; font-size: 11px; font-weight: 900; color: var(--c); background: color-mix(in srgb, var(--c) 13%, #fff) }
.asigHead b { color: #0b2440 }
.asigStats { display: flex; flex-direction: column; align-items: flex-end; font-size: 11px; color: #6d8090 }
.asigStats b { color: #0b2440 }
.progreso { height: 4px; border-radius: 99px; background: #e8eef3; margin: 10px 0 2px; overflow: hidden }
.progreso div { height: 100%; background: var(--c); border-radius: 99px }
.instrumentos { display: flex; flex-direction: column; gap: 6px; margin-top: 10px }
.inst { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #eef3f7; border-radius: 10px; padding: 8px 10px; font-size: 12px }
.inst b { color: #0b2440 }
.instIcon { color: var(--c) }
.nota { font-weight: 800; color: #8396a7; font-size: 12px; white-space: nowrap }
.sinInst { margin-top: 8px }
.oas { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px }
.chipOa { background: #eef4fa; color: #0f4f85; border-radius: 6px; padding: 2px 7px; font-size: 10px; font-weight: 800 }

/* Trayectoria */
.timeline { position: relative; padding-left: 18px }
.timeline:before { content: ""; position: absolute; left: 5px; top: 6px; bottom: 6px; width: 2px; background: #e1e9f0 }
.evento { position: relative; padding: 0 0 14px }
.evento:last-child { padding-bottom: 0 }
.evento .punto { position: absolute; left: -18px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: var(--role); border: 2px solid #fff; box-shadow: 0 0 0 2px var(--role) }
.evento.retirada .punto, .evento.trasladada .punto { background: #bd1f2d; box-shadow: 0 0 0 2px #bd1f2d }
.evento b { display: block; font-size: 13px; color: #0b2440 }
.evento small { display: block; font-size: 11px; color: #6d8090 }
.evento .retiro { color: #bd1f2d }

/* PIE y familia */
.restringido { display: flex; gap: 10px; align-items: flex-start; background: #f4f6f9; border-radius: 12px; padding: 10px 12px; font-size: 12px; color: #415c72; line-height: 1.5 }
.pieDiag { display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px }
.pieDiag .badge { align-self: flex-start; text-transform: capitalize }
.datos { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px; margin: 8px 0 }
.datos .full { grid-column: 1 / -1 }
.datos b { display: block; font-size: 10px; color: #6d8090; font-weight: 700; text-transform: uppercase; letter-spacing: .04em }
.datos span { font-size: 13px; color: #173f60; word-break: break-word }
.datos a { color: #0f4f85 }
.apoyos { display: flex; flex-wrap: wrap; gap: 5px; margin: 6px 0 }
.texto { font-size: 12px; line-height: 1.55; color: #415c72; margin: 6px 0; white-space: pre-line }
.contacto { display: flex; gap: 10px; align-items: center; margin-bottom: 6px }
.contAvatar { width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; background: var(--roleSoft); color: var(--role) }
.badge.violeta { background: #f1edff; border-color: #ddd3fb; color: #5a3fb0 }
.badge.warn { background: #fff5e7; border-color: #f3dfa8; color: #8a5a0c }

@media (max-width: 1100px) {
  .layout { grid-template-columns: 1fr }
  .hero { grid-template-columns: auto 1fr }
  .heroKpis { grid-column: 1 / -1 }
}
@media (max-width: 640px) {
  .hero { grid-template-columns: 1fr; text-align: left; padding: 18px }
  .heroAvatar { width: 64px; height: 64px; font-size: 22px }
  .heroKpis { flex-direction: column; align-items: stretch }
  .anillo { align-self: center }
  .inst { flex-wrap: wrap }
  .asigStats { display: none }
}
</style>
