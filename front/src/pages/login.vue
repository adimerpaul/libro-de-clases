<template>
  <div class="login">
    <section class="loginHero">
      <div class="brandLock">
        <div class="brandMark"><q-icon name="fas fa-book-open" size="30px" /></div>
        <div class="brandText">
          <b>ECOSISTEMA EDUCATIVO 4.2</b>
          <small>Libro · Currículum · Evaluación · Inclusión · Convivencia · Familia</small>
        </div>
      </div>

      <div class="heroCopy">
        <span class="eyebrow">Comunidad educativa completa · Chile</span>
        <h1>Una plataforma.<br><span>Un rol, tareas claras.</span></h1>
        <p>
          El ecosistema adapta la experiencia a cada integrante de la comunidad. Docencia trabaja con currículum
          y evaluación; PIE con apoyos y PACI; UTP con cobertura y acompañamiento; Inspectoría con operación y
          protocolos; Convivencia con prevención; la familia con información exclusiva de sus hijos.
        </p>
        <div class="heroPills">
          <span v-for="p in pills" :key="p.text" class="heroPill"><q-icon :name="p.icon" size="13px" class="q-mr-xs" />{{ p.text }}</span>
        </div>
      </div>

      <div class="heroFoot">© {{ year }} Ecosistema Educativo 4.2 · Libro de Clases 3.0 · v{{ $version }}</div>
    </section>

    <section class="loginPane">
      <div class="loginCard">
        <span class="eyebrow">Acceso</span>
        <h2>Entrar al Ecosistema 4.2</h2>
        <p class="muted">Ingresa con tu correo institucional y tu clave: el menú y las tareas se adaptan a tu rol.</p>

        <q-form @submit.prevent="login">
          <label for="email">Correo</label>
          <q-input
            v-model="email" for="email" type="email" outlined dense autocomplete="username"
            placeholder="nombre@colegio.cl" :rules="[v => !!v || 'Ingrese su correo']" hide-bottom-space
          >
            <template #prepend><q-icon name="far fa-envelope" size="18px" /></template>
          </q-input>

          <label for="password">Clave</label>
          <q-input
            v-model="password" for="password" :type="showPassword ? 'text' : 'password'" outlined dense
            autocomplete="current-password" :rules="[v => !!v || 'Ingrese su clave']" hide-bottom-space
          >
            <template #prepend><q-icon name="fas fa-lock" size="18px" /></template>
            <template #append>
              <q-icon
                :name="showPassword ? 'far fa-eye' : 'far fa-eye-slash'" size="18px" class="cursor-pointer"
                @click="showPassword = !showPassword"
              />
            </template>
          </q-input>

          <label for="perfil">Perfil</label>
          <q-select
            v-model="perfil" for="perfil" :options="opcionesPerfil" emit-value map-options outlined dense
            :loading="cargandoRoles" @update:model-value="elegirPerfil"
          >
            <template #prepend><q-icon name="fas fa-id-badge" size="18px" /></template>
            <template #option="{ itemProps, opt }">
              <q-item v-bind="itemProps">
                <q-item-section avatar>
                  <span class="perfilDot" :class="`role-${opt.clave}`" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ opt.label }}</q-item-label>
                  <q-item-label caption>{{ opt.tag }}</q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-select>

          <q-btn
            type="submit" label="Ingresar" color="accent" class="full-width btnLogin"
            no-caps unelevated :loading="loading"
          />
        </q-form>

        <div class="loginNote">
          Elige el perfil con el que trabajarás: la sesión tendrá solo los permisos de ese rol.
          Con «Todos mis perfiles» se suman todos tus roles. Todas las acciones quedan registradas en auditoría.
          <template v-if="esDemo"><br><b>Demo:</b> al elegir un perfil se completa su usuario demo; clave <b>demo123</b>.</template>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { TOKEN_KEY } from '../stores/auth'
import { perfilDeRol } from '../composables/menu'

const { proxy } = getCurrentInstance()

const pills = [
  { icon: 'fas fa-book-open', text: 'Libro físico-digital' },
  { icon: 'fas fa-bullseye', text: 'OA + competencias' },
  { icon: 'fas fa-puzzle-piece', text: 'PIE / DUA / PACI' },
  { icon: 'fas fa-shield-halved', text: 'Inspectoría' },
  { icon: 'fas fa-handshake', text: 'Convivencia' },
  { icon: 'fas fa-book-bookmark', text: 'Libreta del estudiante' },
]

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)

// Perfil (rol de Spatie) con que se abre la sesión, como el selector de ui3; null = todos sus roles
const TODOS = { value: null, label: 'Todos mis perfiles', tag: 'Suma los permisos de todos tus roles', clave: 'sostenedor' }
const PERFIL_KEY = 'perfilLibro'
const perfil = ref(leerPerfil())
const roles = ref([])
const cargandoRoles = ref(false)
const opcionesPerfil = computed(() => [
  TODOS,
  ...roles.value.map(r => { const p = perfilDeRol(r); return { value: r, label: r, tag: p.tag, clave: p.clave } }),
])

// Solo en desarrollo: usuarios demo del seeder (clave demo123)
const esDemo = import.meta.env.DEV
const DEMO = {
  Administrador: 'admin@demo.cl',
  'Dirección': 'direccion@demo.cl',
  UTP: 'utp@demo.cl',
  'Educadora diferencial': 'pie@demo.cl',
  Docente: 'docente@demo.cl',
}

function leerPerfil () {
  try { return localStorage.getItem(PERFIL_KEY) || null } catch { return null }
}

function elegirPerfil (rol) {
  if (!esDemo || !DEMO[rol]) return
  if (!email.value || email.value.endsWith('@demo.cl')) {
    email.value = DEMO[rol]
    if (!password.value) password.value = 'demo123'
  }
}

onMounted(() => {
  cargandoRoles.value = true
  proxy.$axios.get('login/roles')
    .then(res => {
      roles.value = res.data
      if (perfil.value && !roles.value.includes(perfil.value)) perfil.value = null
    })
    .catch(() => {})
    .finally(() => { cargandoRoles.value = false })
})

const year = computed(() => new Date().getFullYear())

function login () {
  loading.value = true
  proxy.$axios.post('login', { email: email.value, password: password.value, rol: perfil.value })
    .then(res => {
      localStorage.setItem(TOKEN_KEY, res.data.token)
      try { perfil.value ? localStorage.setItem(PERFIL_KEY, perfil.value) : localStorage.removeItem(PERFIL_KEY) } catch { /* sin storage */ }
      proxy.$auth.setSesion(res.data)
      proxy.$alert.success('Bienvenido(a) ' + res.data.user.name)
      proxy.$router.push('/')
    })
    .catch(err => {
      proxy.$alert.error(err?.response?.data?.message || 'No se pudo conectar con el servidor')
    })
    .finally(() => { loading.value = false })
}
</script>

<style scoped>
.login {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1.1fr .9fr;
  background: linear-gradient(135deg, #082039, #113958 50%, #481722);
}
.loginHero {
  padding: 58px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.loginHero:after {
  content: "";
  position: absolute;
  inset: auto -90px -110px auto;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  background: #ffffff0a;
  box-shadow: 0 0 0 70px #ffffff05, 0 0 0 140px #ffffff03;
}
.brandLock { display: flex; align-items: center; gap: 14px; position: relative; z-index: 2 }
.brandMark {
  width: 56px; height: 56px; border-radius: 15px;
  background: linear-gradient(145deg, #2c81c5, #0f4f85);
  display: grid; place-items: center; color: #fff;
  box-shadow: 0 14px 34px #0004;
}
.brandText b { font-size: 18px }
.brandText small { display: block; color: #cbd8e5 }
.heroCopy { position: relative; z-index: 2; max-width: 860px }
.heroCopy .eyebrow { color: #ffd8dd }
.heroCopy h1 {
  font-size: clamp(50px, 6vw, 92px);
  line-height: .9;
  letter-spacing: -.055em;
  font-weight: 800;
  margin: 12px 0 22px;
}
.heroCopy h1 span { color: #ffb3bc }
.heroCopy p { font-size: 19px; line-height: 1.62; color: #d9e4ee; max-width: 760px }
.heroPills { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 22px }
.heroPill { border: 1px solid #ffffff28; background: #ffffff10; border-radius: 999px; padding: 8px 11px; font-size: 12px }
.heroFoot { font-size: 11px; color: #b8cad8; position: relative; z-index: 2 }

.loginPane { display: grid; place-items: center; padding: 36px; background: #f7fafc }
.loginCard {
  width: min(480px, 100%);
  background: #fff;
  border: 1px solid #e3eaf0;
  border-radius: 26px;
  padding: 34px;
  box-shadow: 0 25px 80px #00152f33;
  color: #15324a;
}
.loginCard h2 { font-size: 29px; font-weight: 800; line-height: 1.2; margin: 5px 0 4px }
.muted { color: #6d8090; margin: 0 }
.eyebrow { font-size: 10px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; color: #bd1f2d }
label { display: block; font-size: 12px; font-weight: 800; margin: 14px 0 6px }
.loginCard :deep(.q-field--outlined .q-field__control) { border-radius: 11px }
.perfilDot { width: 14px; height: 14px; border-radius: 50%; background: var(--role); box-shadow: 0 0 0 4px var(--roleSoft) }
.btnLogin { margin-top: 18px; border-radius: 11px; padding: 6px 0; font-weight: 800 }
.loginNote {
  margin-top: 17px;
  background: #f4f7fa;
  border: 1px solid #dde6ed;
  border-radius: 12px;
  padding: 12px;
  font-size: 12px;
  line-height: 1.55;
}

@media (max-width: 720px) {
  .login { grid-template-columns: 1fr }
  .loginHero { display: none }
  .loginPane { padding: 16px }
  .loginCard { padding: 24px }
}
</style>
