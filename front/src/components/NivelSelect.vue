<template>
  <!-- Selector de nivel agrupado por bases curriculares, como curriculumnacional.cl -->
  <q-select
    :model-value="modelValue" :options="opciones" option-value="value" option-label="label"
    :option-disable="o => o.header" emit-value map-options outlined dense v-bind="$attrs"
    @update:model-value="v => emit('update:modelValue', v)"
  >
    <template #option="{ opt, itemProps }">
      <q-item-label v-if="opt.header" header class="nivelBase">{{ opt.label }}</q-item-label>
      <q-item v-else v-bind="itemProps" class="nivelItem">
        <q-item-section>{{ opt.label }}</q-item-section>
      </q-item>
    </template>
  </q-select>
</template>

<script setup>
import { computed } from 'vue'
import { useCatalogos } from '../composables/useCrud'

defineOptions({ inheritAttrs: false })
defineProps({ modelValue: { type: String, default: null } })
const emit = defineEmits(['update:modelValue'])

const catalogos = useCatalogos()
const opciones = computed(() => (catalogos.value.niveles_bases || []).flatMap(b => [
  { header: true, label: b.base, value: `__${b.base}` },
  ...b.niveles.map(n => ({ label: n, value: n })),
]))
</script>

<style scoped>
.nivelBase { padding: 10px 14px 4px; font-size: 11px; font-weight: 700; color: #8a9aa8; min-height: 0 }
.nivelItem { padding-left: 28px; min-height: 34px }
</style>
