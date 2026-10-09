<template>
  <div>
    <div class="row items-center q-mb-xs">
      <q-icon name="admin_panel_settings" size="18px" color="primary" class="q-mr-xs" />
      <span class="text-subtitle2 text-weight-bold">Permisos</span>
      <q-space />
      <q-badge color="primary" outline>{{ checkedCount }} activos</q-badge>
      <q-btn dense flat size="sm" icon="done_all" class="q-ml-xs" @click="setAll(true)">
        <q-tooltip>Activar todos</q-tooltip>
      </q-btn>
      <q-btn dense flat size="sm" icon="remove_done" @click="setAll(false)">
        <q-tooltip>Desactivar todos</q-tooltip>
      </q-btn>
    </div>

    <q-input v-model="permFilter" dense outlined clearable placeholder="Filtrar…" class="q-mb-xs">
      <template v-slot:prepend><q-icon name="search" /></template>
    </q-input>

    <div v-if="loading" class="text-center q-pa-sm">
      <q-spinner-dots color="primary" size="28px" />
    </div>

    <div v-else class="perm-groups" :style="{ maxHeight: maxHeight }">
      <div v-if="!groupedPermissions.length" class="text-center text-grey-6 q-pa-md">
        Sin permisos que coincidan con el filtro
      </div>
      <div v-for="group in groupedPermissions" :key="group.name" class="perm-group">
        <div class="perm-group__title">
          <q-icon :name="group.icon" size="16px" />
          {{ group.name }}
          <q-badge rounded color="grey-3" text-color="grey-8" class="q-ml-xs">
            {{ group.permissions.filter(p => p.checked).length }}/{{ group.permissions.length }}
          </q-badge>
        </div>
        <div class="perm-grid">
          <label v-for="perm in group.permissions" :key="perm.id" class="perm-item"
                 :class="{ 'perm-item--on': perm.checked }">
            <q-checkbox v-model="perm.checked" dense size="sm" color="primary" />
            <span class="perm-item__label">{{ perm.name }}</span>
            <q-icon name="help_outline" size="13px" class="perm-item__help" />
            <!-- Descripción del permiso: columna `descripcion` de la tabla permissions -->
            <q-tooltip anchor="top middle" self="bottom middle" :offset="[0, 6]"
                       class="perm-tooltip" max-width="280px">
              <div class="text-weight-bold q-mb-xs">{{ perm.name }}</div>
              <div>{{ perm.descripcion || 'Sin descripción registrada para este permiso.' }}</div>
            </q-tooltip>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  // Permisos del backend, cada uno con una bandera `checked` que este
  // componente modifica en el mismo objeto que recibe el padre.
  permissions: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  maxHeight: { type: String, default: '300px' },
})

const permFilter = ref('')

// El orden de esta lista es el orden en que se muestran los grupos;
// cualquier módulo nuevo aparece igual al final, nunca se oculta un permiso.
const MODULO_ICONOS = {
  Dashboard: 'dashboard',
  Usuarios: 'manage_accounts',
  Pacientes: 'badge',
  Internaciones: 'local_hotel',
  Doctores: 'medical_information',
  Farmacia: 'medication',
  'Productos Farmacia': 'inventory_2',
  Vencimientos: 'event_busy',
  Compras: 'shopping_cart',
  Ventas: 'point_of_sale',
  Caja: 'lock_clock',
  'Caja Administrativa': 'account_balance_wallet',
  'Caja General': 'savings',
  Seguros: 'verified_user',
  Laboratorio: 'biotech',
  Reportes: 'summarize',
  Configuracion: 'settings',
  Otros: 'apps',
}

const limpiar = (t) => (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')

const filteredPerms = computed(() => {
  const q = limpiar(permFilter.value)
  if (!q) return props.permissions
  return props.permissions.filter(p =>
    limpiar(p.name).includes(q) ||
    limpiar(p.modulo).includes(q) ||
    limpiar(p.descripcion).includes(q)
  )
})

const groupedPermissions = computed(() => {
  const orden = Object.keys(MODULO_ICONOS)
  const groups = []

  filteredPerms.value.forEach(permission => {
    const modulo = permission.modulo || 'Otros'
    let group = groups.find(g => g.name === modulo)
    if (!group) {
      group = { name: modulo, icon: MODULO_ICONOS[modulo] || 'apps', permissions: [] }
      groups.push(group)
    }
    group.permissions.push(permission)
  })

  return groups.sort((a, b) => {
    const ia = orden.indexOf(a.name)
    const ib = orden.indexOf(b.name)
    return (ia === -1 ? orden.length : ia) - (ib === -1 ? orden.length : ib)
  })
})

const checkedCount = computed(() => props.permissions.filter(p => p.checked).length)

// Aplica solo sobre lo que está visible con el filtro actual.
function setAll (val) {
  filteredPerms.value.forEach(p => { p.checked = val })
}
</script>

<style scoped>
.perm-groups {
  overflow-y: auto;
  background: #f7fafc;
  border-radius: 8px;
  padding: 7px;
}

.perm-group + .perm-group {
  margin-top: 9px;
}

.perm-group__title {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0 2px 5px;
  color: #455a64;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

.perm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 3px;
}

.perm-item {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 3px 7px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  cursor: pointer;
  user-select: none;
  transition: background 0.12s;
}

.perm-item:hover { background: #eef7f2; }

.perm-item--on {
  border-color: #8fd4bb;
  background: #e4f2ec;
}

.perm-item__label {
  font-size: 11.5px;
  line-height: 1.2;
  color: #2d3748;
}

.perm-item__help {
  margin-left: auto;
  color: #a0aec0;
  flex-shrink: 0;
}

.perm-item:hover .perm-item__help { color: #4a5568; }

.perm-tooltip {
  font-size: 12px;
  line-height: 1.35;
  background: #37474f;
}

@media (max-width: 599px) {
  .perm-grid { grid-template-columns: 1fr 1fr; }
}
</style>
