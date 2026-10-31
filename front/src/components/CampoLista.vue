<template>
  <!--
    Lista de textos como chips (habilidades, indicadores…). Agrega con Enter y también
    al salir del campo (Tab o clic afuera), para que lo escrito no se pierda. Pegar varios
    separados por ";" o salto de línea agrega uno por elemento.
  -->
  <q-select
    ref="sel" :model-value="modelValue || []" :label="label" outlined dense use-input use-chips multiple
    hide-dropdown-icon input-debounce="0" new-value-mode="add-unique" :hint="hint"
    @update:model-value="v => emit('update:modelValue', v)"
    @input-value="v => { pendiente = v }" @new-value="agregarNuevo" @blur="confirmar"
  />
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  hint: { type: String, default: undefined },
})
const emit = defineEmits(['update:modelValue'])

const sel = ref(null)
const pendiente = ref('')

const partes = texto => String(texto).split(/[;\n]+/).map(t => t.trim()).filter(Boolean)

function agregar (textos) {
  const actual = [...(props.modelValue || [])]
  for (const t of textos) if (!actual.includes(t)) actual.push(t)
  emit('update:modelValue', actual)
}

// Enter: admite varios elementos pegados de una vez
function agregarNuevo (texto, done) {
  agregar(partes(texto))
  pendiente.value = ''
  done()
}

// Al salir del campo, lo que quedó escrito se agrega en vez de perderse
function confirmar () {
  if (!pendiente.value.trim()) return
  agregar(partes(pendiente.value))
  pendiente.value = ''
  sel.value?.updateInputValue('', true)
}
</script>
