<template>
  <q-btn v-if="puedeEditar" flat round dense size="sm" icon="edit" color="teal" @click="abrir">
    <q-tooltip>Editar lote y vencimiento</q-tooltip>
  </q-btn>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 420px; max-width: 95vw">
      <q-form @submit="guardar">
        <q-card-section class="text-h6">Editar lote y vencimiento</q-card-section>
        <q-card-section class="q-pt-none q-gutter-sm">
          <div>{{ producto.nombre }} · {{ movimiento.documento }}</div>
          <div v-if="movimiento.compra_detalle_id" class="text-caption text-grey-7">
            Se corregirán la compra y los movimientos vinculados a este lote.
          </div>
          <q-input v-model="form.lote" v-uppercase outlined dense label="Lote" maxlength="255"
                   :rules="[v => !!v?.trim() || 'Ingrese el lote']" />
          <q-input v-model="form.fecha_vencimiento" outlined dense type="date" clearable
                   label="Fecha de vencimiento" stack-label />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" :disable="guardando" v-close-popup />
          <q-btn unelevated no-caps color="teal" label="Guardar" type="submit" :loading="guardando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const props = defineProps({
  producto: { type: Object, required: true },
  movimiento: { type: Object, required: true },
})
const emit = defineEmits(['actualizado'])
const { proxy } = getCurrentInstance()
const puedeEditar = computed(() => props.producto.es_farmacia &&
  proxy.$store.hasPermission('Editar Lotes Farmacia'))
const dialog = ref(false)
const guardando = ref(false)
const form = ref({ lote: '', fecha_vencimiento: null })

function abrir () {
  form.value = {
    lote: props.movimiento.lote || '',
    fecha_vencimiento: props.movimiento.fecha_vencimiento || null,
  }
  dialog.value = true
}

async function guardar () {
  if (!puedeEditar.value || guardando.value) return
  guardando.value = true
  try {
    const { data } = await proxy.$axios.put(
      `productos/${props.producto.id}/historial/${props.movimiento.tipo}/${props.movimiento.detalle_id}`,
      { lote: form.value.lote, fecha_vencimiento: form.value.fecha_vencimiento || null },
    )
    dialog.value = false
    emit('actualizado', data)
    proxy.$alert.success('Lote y fecha de vencimiento actualizados')
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo actualizar el lote')
  } finally {
    guardando.value = false
  }
}
</script>
