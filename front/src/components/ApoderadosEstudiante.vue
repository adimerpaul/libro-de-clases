<template>
  <q-dialog v-model="abierto">
    <q-card class="formCard apoCard">
      <q-card-section>
        <div class="formTitle">Apoderados con acceso al portal</div>
        <div class="subtle">{{ estudiante?.nombre_completo }} · solo estas cuentas verán sus notas y asistencia</div>
      </q-card-section>

      <q-card-section class="q-pt-none">
        <q-inner-loading :showing="cargando" />
        <div v-for="v in vinculos" :key="v.id" class="vinculo">
          <q-icon name="fas fa-user-shield" class="ico" />
          <div class="datos">
            <b>{{ v.user.name }}</b>
            <small class="subtle">{{ v.parentesco }} · {{ v.user.email }}</small>
          </div>
          <q-btn
            v-if="puedeEditar" flat round dense size="sm" icon="far fa-trash-can" color="negative" aria-label="Quitar vínculo"
            @click="quitar(v)"
          ><q-tooltip>Quitar acceso</q-tooltip></q-btn>
        </div>
        <div v-if="!cargando && !vinculos.length" class="subtle q-py-sm">Sin cuentas vinculadas. El apoderado aún no puede ver al estudiante en el portal.</div>
      </q-card-section>

      <template v-if="puedeEditar">
        <q-separator />
        <q-card-section>
          <q-btn-toggle
            v-model="modo" no-caps unelevated dense toggle-color="primary" color="grey-2" text-color="grey-9" class="q-mb-md"
            :options="[{ label: 'Vincular cuenta existente', value: 'existente' }, ...(puedeCrear ? [{ label: 'Crear cuenta de apoderado', value: 'nueva' }] : [])]"
          />
          <div class="formGrid">
            <q-select
              v-if="modo === 'existente'" v-model="userId" :options="disponibles" option-value="id" option-label="name"
              emit-value map-options use-input input-debounce="250" outlined dense clearable class="full"
              label="Apoderado (rol Apoderado)" hint="Busca por nombre, correo o RUN" @filter="buscar"
            >
              <template #prepend><q-icon name="fas fa-magnifying-glass" size="13px" /></template>
              <template #option="{ opt, itemProps }">
                <q-item v-bind="itemProps">
                  <q-item-section avatar><div class="apoAvatar">{{ iniciales(opt.name) }}</div></q-item-section>
                  <q-item-section>
                    <q-item-label><b>{{ opt.name }}</b></q-item-label>
                    <q-item-label caption>{{ opt.email }}<template v-if="opt.run"> · RUN {{ formatoRun(opt.run) }}</template></q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <span class="badge" :class="opt.estudiantes_count ? 'blue' : ''">{{ opt.estudiantes_count }} {{ opt.estudiantes_count === 1 ? 'estudiante' : 'estudiantes' }}</span>
                  </q-item-section>
                </q-item>
              </template>
              <template #no-option>
                <q-item><q-item-section class="subtle">Ningún usuario con el rol Apoderado coincide. Créalo en «Crear cuenta de apoderado».</q-item-section></q-item>
              </template>
            </q-select>
            <template v-else>
              <q-input v-model="cuenta.name" label="Nombre" outlined dense class="full" />
              <q-input v-model="cuenta.email" type="email" label="Correo (usuario)" outlined dense />
              <q-input v-model="cuenta.password" label="Clave inicial" outlined dense hint="Mínimo 6 caracteres" />
              <q-input v-model="cuenta.telefono" label="Teléfono" outlined dense />
            </template>
            <q-select v-model="parentesco" :options="parentescos" outlined dense label="Parentesco" />
          </div>
        </q-card-section>
      </template>

      <q-card-actions align="right">
        <q-btn flat no-caps label="Cerrar" v-close-popup />
        <q-btn
          v-if="puedeEditar" no-caps unelevated class="btnPrimary" icon="fas fa-link" label="Vincular" :loading="guardando"
          :disable="modo === 'existente' ? !userId : !cuenta.name || !cuenta.email || !cuenta.password" @click="vincular"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'
import { mensajeError } from '../composables/useCrud'
import { formatoRun } from '../composables/formato'

// Vínculos apoderado ↔ estudiante (portal de familia). Se abre con abrir(estudiante).
const { proxy } = getCurrentInstance()
const emit = defineEmits(['cambio'])

const abierto = ref(false)
const estudiante = ref(null)
const vinculos = ref([])
const parentescos = ref([])
const cuentas = ref([])
const cargando = ref(false)
const guardando = ref(false)
const modo = ref('existente')
const userId = ref(null)
const parentesco = ref('Madre')
const cuenta = ref({})

// Solo usuarios con el rol Apoderado que aún no están vinculados a este estudiante
const disponibles = computed(() => cuentas.value.filter(c => !vinculos.value.some(v => v.user.id === c.id)))
const iniciales = n => (n || '').split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase()

const puedeEditar = computed(() => proxy.$auth.hasPermission('estudiantes.editar'))
const puedeCrear = computed(() => proxy.$auth.hasPermission('usuarios.crear'))

async function cargar () {
  cargando.value = true
  try {
    const { data } = await proxy.$axios.get(`admin/estudiantes/${estudiante.value.id}/apoderados`)
    vinculos.value = data.vinculos
    parentescos.value = data.parentescos
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    cargando.value = false
  }
}

function buscar (texto, update) {
  proxy.$axios.get('admin/apoderados/cuentas', { params: { q: texto || undefined } })
    .then(res => update(() => { cuentas.value = res.data }))
    .catch(() => update(() => { cuentas.value = [] }))
}

function abrir (e) {
  estudiante.value = e
  vinculos.value = []
  userId.value = null
  modo.value = 'existente'
  parentesco.value = 'Madre'
  // La cuenta nueva parte con los datos del apoderado de la ficha
  cuenta.value = { name: e.apoderado_nombre || '', email: e.apoderado_email || '', password: '', telefono: e.apoderado_telefono || '' }
  abierto.value = true
  cargar()
  buscar('', fn => fn())
}

async function vincular () {
  guardando.value = true
  try {
    const body = modo.value === 'existente'
      ? { user_id: userId.value, parentesco: parentesco.value }
      : { cuenta: cuenta.value, parentesco: parentesco.value }
    await proxy.$axios.post(`admin/estudiantes/${estudiante.value.id}/apoderados`, body)
    proxy.$alert.success(modo.value === 'existente' ? 'Cuenta vinculada' : 'Cuenta de apoderado creada y vinculada')
    userId.value = null
    cargar()
    buscar('', fn => fn())
    emit('cambio')
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}

async function quitar (v) {
  try {
    await proxy.$axios.delete(`admin/apoderados/${v.id}`)
    proxy.$alert.success('Acceso quitado', v.user.name)
    cargar()
    emit('cambio')
  } catch (e) {
    proxy.$alert.error(mensajeError(e))
  }
}

defineExpose({ abrir })
</script>

<style scoped>
.apoCard { width: 560px; max-width: 96vw }
.vinculo { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid #d9e3ec; border-left: 4px solid #1685a0; border-radius: 12px; margin-bottom: 8px }
.vinculo .ico { color: #1685a0 }
.vinculo .datos { flex: 1; display: flex; flex-direction: column; min-width: 0 }
.vinculo small { font-size: 11px }
.apoAvatar { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: #e8f8fb; color: #1685a0; font-size: 11px; font-weight: 900 }
</style>
