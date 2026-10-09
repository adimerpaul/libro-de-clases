<template>
  <q-page class="q-pa-md compras-compactas">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver proveedores</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <div class="row items-center q-mb-md">
        <q-btn flat dense round icon="arrow_back" color="grey-7" class="q-mr-sm" to="/compras">
          <q-tooltip>Volver a compras</q-tooltip>
        </q-btn>
        <div>
          <div class="text-h5 text-weight-bold">Proveedores</div>
          <div class="text-body2 text-grey-6">Empresas y personas a las que se compra mercadería</div>
        </div>
        <q-space />
        <q-input v-model="filterProv" placeholder="Buscar…" dense outlined rounded clearable
                 bg-color="white" style="width:200px" class="q-mr-sm" @update:model-value="onFiltroChange">
          <template v-slot:prepend><q-icon name="search" /></template>
        </q-input>
        <q-btn outline rounded no-caps color="grey-7" icon="refresh" class="q-mr-sm" @click="loadProveedores">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
        <q-btn v-if="canCrear" rounded unelevated color="primary" label="Nuevo proveedor" icon="add" no-caps
               @click="provNew" />
      </div>

      <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders">
        <thead>
          <tr class="bg-grey-1 text-grey-7 text-uppercase">
            <th class="text-left" style="width:64px"></th>
            <th class="text-left">Nombre</th>
            <th class="text-left">NIT</th>
            <th class="text-left">Contacto</th>
            <th class="text-left">Teléfono</th>
            <th class="text-center">Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loadingProv">
            <td colspan="6" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
          </tr>
          <tr v-else-if="!proveedores.length">
            <td colspan="6" class="text-center text-grey-5 q-pa-md">Sin datos</td>
          </tr>
          <tr v-else v-for="row in proveedores" :key="row.id">
            <td class="q-pa-xs">
              <q-btn-dropdown v-if="canEditar || canEliminar" label="Opciones" no-caps size="10px" dense rounded unelevated color="primary">
                <q-list>
                  <q-item v-if="canEditar" clickable v-close-popup @click="provEdit(row)">
                    <q-item-section avatar><q-icon name="edit" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="provDelete(row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </td>
            <td>{{ row.nombre }}</td>
            <td>{{ row.nit || '—' }}</td>
            <td>{{ row.contacto || '—' }}</td>
            <td>{{ row.telefono || '—' }}</td>
            <td class="text-center">
              <q-badge rounded
                       :color="row.estado === 'ACTIVO' ? 'green-1' : 'grey-3'"
                       :text-color="row.estado === 'ACTIVO' ? 'positive' : 'grey-7'"
                       class="text-weight-bold">{{ row.estado }}</q-badge>
            </td>
          </tr>
        </tbody>
      </q-markup-table>

    </template>

    <!-- DIALOG PROVEEDOR -->
    <q-dialog v-model="dialogProv" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="local_shipping" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ provAction }} proveedor</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogProv = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="provSave">
            <div class="row q-col-gutter-sm">
              <div class="col-12">
                <q-input v-model="prov.nombre" label="Nombre *" dense outlined
                         :rules="[v => !!v || 'Requerido']" v-uppercase />
              </div>
              <div class="col-6"><q-input v-model="prov.nit" label="NIT" dense outlined /></div>
              <div class="col-6"><q-input v-model="prov.razon_social" label="Razón social" dense outlined v-uppercase /></div>
              <div class="col-6"><q-input v-model="prov.contacto" label="Contacto" dense outlined v-uppercase /></div>
              <div class="col-6"><q-input v-model="prov.telefono" label="Teléfono" dense outlined /></div>
              <div class="col-6"><q-input v-model="prov.email" label="Email" dense outlined /></div>
              <div class="col-6">
                <q-select v-model="prov.estado" label="Estado" dense outlined :options="['ACTIVO', 'INACTIVO']" />
              </div>
              <div class="col-12"><q-input v-model="prov.direccion" label="Dirección" dense outlined v-uppercase /></div>
              <div class="col-12"><q-input v-model="prov.observacion" label="Observación" dense outlined type="textarea" rows="2" /></div>
            </div>
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogProv = false" />
              <q-btn color="primary" :label="prov.id ? 'Guardar cambios' : 'Crear proveedor'"
                     type="submit" no-caps :loading="savingProv" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'

const { proxy } = getCurrentInstance()

// ── Permisos — los proveedores se rigen por los permisos de Compras ──
const canVer      = computed(() => proxy.$store.hasPermission('Ver Compras'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Compras'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Compras'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Compras'))

const proveedores = ref([])
const loadingProv = ref(false)
const savingProv  = ref(false)
const dialogProv  = ref(false)
const provAction  = ref('Nuevo')
const filterProv  = ref('')
const prov        = ref({})

let timerFiltro = null
function onFiltroChange () {
  clearTimeout(timerFiltro)
  timerFiltro = setTimeout(loadProveedores, 350)
}

async function loadProveedores () {
  loadingProv.value = true
  try {
    const res = await proxy.$axios.get('proveedores', { params: { q: filterProv.value, per_page: 100 } })
    proveedores.value = res.data?.data || []
  } catch (e) {
    proxy.$alert.error('Error al cargar proveedores')
  } finally {
    loadingProv.value = false
  }
}

function provNew ()     { prov.value = { nombre: '', estado: 'ACTIVO' }; provAction.value = 'Nuevo'; dialogProv.value = true }
function provEdit (row) { prov.value = { ...row }; provAction.value = 'Editar'; dialogProv.value = true }

async function provSave () {
  savingProv.value = true
  try {
    if (prov.value.id) {
      await proxy.$axios.put('proveedores/' + prov.value.id, prov.value)
      proxy.$alert.success('Proveedor actualizado')
    } else {
      await proxy.$axios.post('proveedores', prov.value)
      proxy.$alert.success('Proveedor creado')
    }
    dialogProv.value = false
    loadProveedores()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingProv.value = false
  }
}

function provDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el proveedor?').onOk(() => {
    proxy.$axios.delete('proveedores/' + id)
      .then(() => { proxy.$alert.success('Proveedor eliminado'); loadProveedores() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

watch(() => proxy.$store.isLogged, (val) => { if (val) loadProveedores() }, { immediate: true })
</script>

<style scoped>
.compras-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.compras-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}

.compras-compactas :deep(.q-field--dense .q-field__native),
.compras-compactas :deep(.q-field--dense .q-field__input),
.compras-compactas :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}

.compras-compactas :deep(.q-field--dense .q-field__append),
.compras-compactas :deep(.q-field--dense .q-field__prepend) {
  height: 30px;
}

.compras-compactas :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}
</style>
