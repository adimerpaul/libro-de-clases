<template>
  <div class="matriz">
    <!-- Permisos sueltos (sin CRUD), p. ej. Dictar clases -->
    <div v-for="g in sueltos" :key="g.grupo" class="suelto">
      <div class="fieldLabel">{{ g.grupo }}</div>
      <q-checkbox
        v-for="p in g.permisos" :key="p.name" dense
        :model-value="marcado(p.name)" :disable="bloqueado(p.name)"
        :label="bloqueado(p.name) ? `${p.label} (por rol)` : p.label"
        @update:model-value="v => cambiar([p.name], v)"
      />
    </div>

    <div class="tablaWrap">
      <table class="tabla">
        <thead>
          <tr>
            <th class="izq">Módulo</th>
            <th v-for="a in catalogos.acciones" :key="a.key">{{ a.label }}</th>
            <th>Todo</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="g in modulos" :key="g.modulo">
            <td class="izq"><b>{{ g.grupo }}</b></td>
            <td v-for="a in catalogos.acciones" :key="a.key">
              <template v-for="p in g.permisos.filter(x => x.accion === a.key)" :key="p.name">
                <q-checkbox
                  dense :model-value="marcado(p.name)" :disable="bloqueado(p.name)"
                  :color="bloqueado(p.name) ? 'grey-6' : 'primary'"
                  @update:model-value="v => cambiar([p.name], v)"
                >
                  <q-tooltip>{{ bloqueado(p.name) ? `${p.label} (por rol)` : p.label }}</q-tooltip>
                </q-checkbox>
              </template>
            </td>
            <td>
              <q-checkbox
                dense :model-value="estadoFila(g)" toggle-indeterminate
                @update:model-value="() => cambiar(libres(g), estadoFila(g) !== true)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="bloqueados.length" class="subtle q-mt-xs">Los marcados en gris vienen de los roles.</div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useCatalogos } from '../composables/useCrud'

/**
 * Matriz módulo × acción (Ver, Crear, Editar, Eliminar) para elegir permisos.
 * v-model: permisos elegidos; bloqueados: los que ya vienen de roles (marcados y sin editar).
 */
const props = defineProps({
  modelValue: { type: Array, required: true },
  bloqueados: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const catalogos = useCatalogos()
const sueltos = computed(() => catalogos.value.permisos.filter(g => !g.modulo))
const modulos = computed(() => catalogos.value.permisos.filter(g => g.modulo))

const bloqueado = name => props.bloqueados.includes(name)
const marcado = name => bloqueado(name) || props.modelValue.includes(name)
const libres = g => g.permisos.map(p => p.name).filter(n => !bloqueado(n))

function estadoFila (g) {
  const n = g.permisos.filter(p => marcado(p.name)).length
  return n === 0 ? false : n === g.permisos.length ? true : null
}

function cambiar (names, activar) {
  const set = new Set(props.modelValue)
  names.forEach(n => (activar ? set.add(n) : set.delete(n)))
  // Ver es la base del módulo: dar cualquier acción incluye Ver; quitar Ver quita todo
  for (const g of modulos.value) {
    const ver = g.permisos.find(p => p.accion === 'ver')?.name
    if (!ver) continue
    const resto = g.permisos.filter(p => p.accion !== 'ver').map(p => p.name)
    if (activar && names.some(n => resto.includes(n))) set.add(ver)
    if (!activar && names.includes(ver) && !bloqueado(ver)) resto.forEach(n => set.delete(n))
  }
  emit('update:modelValue', [...set])
}
</script>

<style scoped>
.suelto { margin-bottom: 10px }
.fieldLabel { font-size: 12px; font-weight: 700; color: #486477; margin-bottom: 4px }
.tablaWrap { overflow-x: auto; border: 1px solid #e2e9ef; border-radius: 13px }
.tabla { width: 100%; border-collapse: collapse; font-size: 12.5px }
.tabla th, .tabla td { padding: 6px 8px; border-bottom: 1px solid #e2e9ef; text-align: center }
.tabla tr:last-child td { border-bottom: 0 }
.tabla th { background: #f4f7fa; color: #5c7182; text-transform: uppercase; letter-spacing: .05em; font-size: 10px }
.tabla .izq { text-align: left }
</style>
