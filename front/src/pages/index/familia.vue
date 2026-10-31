<template>
  <q-page class="page">
    <div v-if="!cargandoHijos && !hijos.length" class="card vacio">
      <q-icon name="fas fa-house-chimney-user" size="34px" class="q-mb-sm" />
      <h3>Aún no tiene estudiantes vinculados</h3>
      <p class="subtle">El establecimiento debe vincular su cuenta a la ficha de su hijo o hija. Comuníquese con secretaría o con el profesor(a) jefe.</p>
    </div>

    <template v-else>
      <div class="familyHero">
        <div>
          <span class="eyebrow">Portal familia</span>
          <h2>{{ detalle?.estudiante.nombres || hijoActual?.nombres || '…' }} {{ hijoActual?.apellido }}</h2>
          <p>
            {{ detalle?.curso ? `${detalle.curso.nombre} ${detalle.curso.anio}` : (hijoActual?.curso || 'Sin matrícula este año') }}
            · Solo información vinculada al hijo/a seleccionado/a.
          </p>
        </div>
        <div class="childPicker">
          <button
            v-for="h in hijos" :key="h.id" type="button" class="childChip" :class="{ active: h.id === hijoId }"
            @click="elegir(h.id)"
          >
            {{ h.nombres.split(' ')[0] }} {{ h.apellido }}
          </button>
        </div>
      </div>

      <div class="kpiLine">
        <div class="kpiMini"><b>{{ detalle?.asistencia.porcentaje ?? '—' }}{{ detalle?.asistencia.porcentaje != null ? '%' : '' }}</b><small>asistencia del mes (RFID)</small></div>
        <div class="kpiMini"><b :class="claseNota(detalle?.notas.promedio)">{{ nota(detalle?.notas.promedio) }}</b><small>promedio general</small></div>
        <div class="kpiMini"><b>{{ detalle?.notas.total ?? '—' }}</b><small>notas registradas</small></div>
        <div class="kpiMini"><b :class="{ warn: detalle?.asistencia.atrasos }">{{ detalle?.asistencia.atrasos ?? '—' }}</b><small>atrasos este mes</small></div>
      </div>

      <div class="grid3">
        <!-- Notas -->
        <div class="card notasCard">
          <q-inner-loading :showing="cargando" />
          <div class="cardHead">
            <h3>Notas y evaluaciones</h3>
            <span v-if="detalle?.notas.bajo_4" class="badge red">{{ detalle.notas.bajo_4 }} bajo 4,0</span>
          </div>
          <div v-if="detalle && !detalle.notas.asignaturas.length" class="subtle">Aún no hay notas registradas este año.</div>
          <q-list v-else separator class="asignaturas">
            <q-expansion-item v-for="a in detalle?.notas.asignaturas || []" :key="a.asignatura" dense header-class="asigHead">
              <template #header>
                <q-item-section avatar><span class="dot" :style="{ background: a.color || '#c5d3df' }" /></q-item-section>
                <q-item-section>
                  <b>{{ a.asignatura }}</b>
                  <small class="subtle">{{ a.notas.length }} {{ a.notas.length === 1 ? 'nota' : 'notas' }}{{ a.docente ? ` · ${a.docente}` : '' }}</small>
                </q-item-section>
                <q-item-section side>
                  <span class="notaGrande" :class="claseNota(a.promedio)">{{ nota(a.promedio) }}</span>
                </q-item-section>
              </template>
              <div class="notas">
                <div v-for="n in a.notas" :key="n.id" class="notaFila">
                  <div>
                    <b>{{ n.instrumento }}</b>
                    <small class="subtle">{{ n.tipo }}{{ n.fecha ? ` · ${fechaCl(n.fecha)}` : '' }}{{ n.oa ? ` · ${n.oa}` : '' }}</small>
                    <small v-if="n.observacion" class="obs">{{ n.observacion }}</small>
                  </div>
                  <span class="nota" :class="claseNota(n.nota)">{{ nota(n.nota) }}</span>
                </div>
              </div>
            </q-expansion-item>
          </q-list>
        </div>

        <!-- Asistencia -->
        <div class="card">
          <q-inner-loading :showing="cargando" />
          <h3>Asistencia y puntualidad</h3>
          <template v-if="detalle">
            <p class="subtle q-mb-sm">
              {{ detalle.asistencia.dias_con_entrada }} de {{ detalle.asistencia.dias_habiles }} días con clases este mes ·
              {{ detalle.asistencia.atrasos }} {{ detalle.asistencia.atrasos === 1 ? 'atraso' : 'atrasos' }} (entrada después de las {{ detalle.asistencia.limite }}).
            </p>
            <div class="feedback" :class="detalle.asistencia.hoy.propuesta || 'sin'">
              <template v-if="detalle.asistencia.hoy.entrada">
                Hoy: {{ ETIQUETAS[detalle.asistencia.hoy.propuesta] }} · entrada {{ detalle.asistencia.hoy.entrada }}{{ detalle.asistencia.hoy.dentro ? ' · en el establecimiento' : ' · ya salió' }}
              </template>
              <template v-else>Hoy: sin marcación de entrada</template>
            </div>
            <div class="sectionLabel">Últimas marcaciones</div>
            <div v-for="m in detalle.asistencia.ultimas" :key="m.marcado_en + m.tipo" class="marca">
              <q-icon :name="m.tipo === 'entrada' ? 'fas fa-right-to-bracket' : 'fas fa-right-from-bracket'" size="13px" />
              <span>{{ fechaCl(m.marcado_en.slice(0, 10)) }} · {{ m.marcado_en.slice(11) }} · {{ m.tipo }}</span>
              <q-space />
              <span v-if="m.propuesta" class="badge" :class="m.propuesta === 'presente' ? 'ok' : 'warn'">{{ ETIQUETAS[m.propuesta] }}</span>
            </div>
            <div v-if="!detalle.asistencia.ultimas.length" class="subtle">Sin marcaciones este mes.</div>
            <p class="nota-legal">Registro del lector RFID: es una propuesta; la asistencia oficial la valida el establecimiento.</p>
          </template>
        </div>

        <!-- Curso -->
        <div class="card">
          <q-inner-loading :showing="cargando" />
          <h3>Curso y contacto</h3>
          <template v-if="detalle">
            <div class="detailItem">
              <b>{{ detalle.curso ? `${detalle.curso.nombre} · ${detalle.curso.anio}` : 'Sin matrícula activa este año' }}</b>
              <p v-if="detalle.curso?.numero_lista">N° de lista {{ detalle.curso.numero_lista }}</p>
            </div>
            <div class="detailItem">
              <b>Profesor(a) jefe</b>
              <p>{{ detalle.curso?.profesor_jefe || 'Sin asignar' }}</p>
              <p v-if="detalle.curso?.profesor_jefe_email"><a :href="`mailto:${detalle.curso.profesor_jefe_email}`">{{ detalle.curso.profesor_jefe_email }}</a></p>
            </div>
            <div class="detailItem">
              <b>Su vínculo</b>
              <p>{{ detalle.parentesco }} · RUN estudiante {{ formatoRun(detalle.estudiante.run) }}</p>
            </div>
            <q-btn flat no-caps dense color="primary" icon="fas fa-book-bookmark" label="Libreta del estudiante" to="/libreta" class="q-mt-sm" />
          </template>
        </div>
      </div>

      <div class="privacyNotice">
        <q-icon name="fas fa-lock" size="12px" class="q-mr-xs" />
        El perfil Apoderado/Tutor no accede a información de otros estudiantes, cursos, funcionarios ni módulos de gestión.
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { fechaCl } from '../../composables/fechas'
import { formatoRun } from '../../composables/formato'

definePage({ meta: { title: 'Mis hijos', permiso: 'familia.ver' } })
defineOptions({ name: 'FamiliaPage' })

const { proxy } = getCurrentInstance()
const CLAVE = 'familia.hijo'
const ETIQUETAS = { presente: 'Presente', atrasado: 'Atrasado' }

const hijos = ref([])
const hijoId = ref(null)
const detalle = ref(null)
const cargandoHijos = ref(true)
const cargando = ref(false)

const hijoActual = computed(() => hijos.value.find(h => h.id === hijoId.value))
const nota = n => (n == null ? '—' : Number(n).toFixed(1).replace('.', ','))
const claseNota = n => (n == null ? '' : n < 4 ? 'roja' : 'azul')

async function elegir (id) {
  hijoId.value = id
  try { localStorage.setItem(CLAVE, String(id)) } catch { /* sin almacenamiento */ }
  cargando.value = true
  try {
    detalle.value = (await proxy.$axios.get(`familia/hijos/${id}`)).data
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'No se pudo cargar la información del estudiante')
  } finally {
    cargando.value = false
  }
}

onMounted(async () => {
  try {
    hijos.value = (await proxy.$axios.get('familia/hijos')).data
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'No se pudieron cargar sus hijos')
  } finally {
    cargandoHijos.value = false
  }
  let guardado = null
  try { guardado = Number(localStorage.getItem(CLAVE)) } catch { /* sin almacenamiento */ }
  const inicial = hijos.value.find(h => h.id === guardado) || hijos.value[0]
  if (inicial) elegir(inicial.id)
})
</script>

<style scoped>
/* ui3: .familyHero / .childPicker / .kpiLine / .privacyNotice */
.familyHero { background: linear-gradient(135deg, #0f6c82, #1685a0 55%, #183f61); color: #fff; border-radius: 20px; padding: 20px; display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: center }
.familyHero .eyebrow { color: #c5f4fb; font-size: 10px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase }
.familyHero h2 { margin: 4px 0 0; font-size: 26px; font-weight: 800; line-height: 1.2 }
.familyHero p { margin: 6px 0 0; color: #d9f2f6; font-size: 12px }
.childPicker { display: flex; gap: 7px; flex-wrap: wrap; justify-content: flex-end }
.childChip { border: 1px solid #ffffff36; background: #ffffff14; color: #fff; border-radius: 999px; padding: 7px 12px; font: inherit; font-size: 12px; font-weight: 800; cursor: pointer }
.childChip:hover { background: #ffffff26 }
.childChip.active { background: #fff; color: #155b70 }

.kpiLine { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 14px }
.kpiMini { background: #fff; border: 1px solid #dbe5ec; border-radius: 14px; padding: 12px }
.kpiMini b { display: block; font-size: 24px; color: #0b2440 }
.kpiMini b.warn { color: #b56b12 }
.kpiMini small { color: #6d8090; font-size: 11px }

.grid3 { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 14px; margin-top: 14px; align-items: start }
.card { position: relative }
.card h3 { margin: 0 0 10px }

.dot { display: inline-block; width: 10px; height: 10px; border-radius: 50% }
.asignaturas :deep(.q-item) { padding: 8px 4px }
.asignaturas :deep(.q-item__section--avatar) { min-width: 22px }
.asignaturas small { display: block; font-size: 11px }
.notaGrande { font-size: 18px; font-weight: 900 }
.notas { padding: 0 4px 8px 26px }
.notaFila { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 7px 0; border-top: 1px dashed #e3eaf0 }
.notaFila b { font-size: 12.5px; display: block }
.notaFila small { display: block; font-size: 11px }
.obs { color: #3a5871; font-style: italic }
.nota { font-weight: 900; font-size: 15px; min-width: 32px; text-align: right }
.azul { color: #0f4f85 }
.roja { color: #bd1f2d }

.feedback { border-left: 4px solid #9aa9b6; background: #f6f9fc; border-radius: 0 10px 10px 0; padding: 9px 11px; font-size: 12px; font-weight: 700; color: #33536d }
.feedback.presente { border-left-color: #17703f; background: #f0faf4; color: #17703f }
.feedback.atrasado { border-left-color: #d97706; background: #fff8ec; color: #9a5a0c }
.sectionLabel { font-size: 10px; text-transform: uppercase; letter-spacing: .12em; color: var(--role); font-weight: 900; margin: 14px 0 6px }
.marca { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid #eef3f7; font-size: 12px; color: #33536d }
.badge.warn { background: #fff5e7; border-color: #f2d9b5; color: #b56b12 }
.nota-legal { margin: 10px 0 0; font-size: 10.5px; color: #6d8090 }

.detailItem { padding: 10px 11px; border-left: 4px solid var(--role); background: #f8fbfd; border-radius: 0 10px 10px 0; margin-bottom: 8px }
.detailItem b { font-size: 12.5px }
.detailItem p { margin: 3px 0 0; color: #5f7487; font-size: 12px }

.privacyNotice { margin-top: 14px; padding: 10px 12px; border: 1px dashed color-mix(in srgb, var(--role) 50%, #cfd8e3); background: var(--roleSoft); border-radius: 12px; font-size: 11.5px; color: #415c72 }

.vacio { text-align: center; padding: 40px 20px; color: #1685a0 }
.vacio h3 { color: #0b2440; margin: 0 0 6px }

@media (max-width: 1100px) {
  .grid3 { grid-template-columns: 1fr }
}
@media (max-width: 720px) {
  .familyHero { grid-template-columns: 1fr }
  .childPicker { justify-content: flex-start }
  .kpiLine { grid-template-columns: repeat(2, 1fr) }
}
</style>
