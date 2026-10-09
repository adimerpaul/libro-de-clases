<template>
  <q-page class="q-pa-sm">

    <div class="row items-center q-mb-xs">
      <div>
        <div class="text-h6 text-weight-bold">Actualizar productos</div>
        <div class="text-caption text-grey-6">Nombre, precio, categoría y datos del producto</div>
      </div>
      <q-space />
      <span class="text-subtitle2 text-grey-7 q-mr-sm">Total: {{ total }}</span>
      <q-select v-model="filterTipo" label="Categoría" dense outlined clearable
                :options="allTipoProductos" option-value="id" option-label="nombre"
                emit-value map-options style="width:180px" class="q-mr-xs" @update:model-value="onFilter">
        <template v-slot:option="scope">
          <q-item v-bind="scope.itemProps">
            <q-item-section avatar>
              <q-badge :color="scope.opt.color || 'primary'" style="width:16px;height:16px" />
            </q-item-section>
            <q-item-section>{{ scope.opt.nombre }}</q-item-section>
          </q-item>
        </template>
      </q-select>
      <q-input v-model="filter" label="Buscar" dense outlined clearable autofocus
               style="width:180px" class="q-mr-xs" @update:model-value="onFilter">
        <template v-slot:append><q-icon name="search" /></template>
      </q-input>
      <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
             no-caps dense @click="prodNew" />
    </div>

    <div class="tabla-wrap">
      <q-markup-table dense flat bordered separator="cell" class="tabla-fija full-width">
        <thead>
          <tr class="bg-grey-2">
            <th class="text-left" style="width:64px"></th>
            <th class="text-left">Código</th>
            <th class="text-left">Nombre</th>
            <th class="text-left">Tipo</th>
            <th class="text-right">Precio (Bs.)</th>
            <th class="text-right">Stock</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!productos.length && !loading">
            <td colspan="6" class="text-center text-grey-5 q-pa-md">Sin datos</td>
          </tr>
          <tr v-for="row in productos" :key="row.id">
            <td class="q-pa-xs">
              <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
                <q-list>
                  <q-item v-if="canEditar" clickable v-close-popup @click="prodEdit(row)">
                    <q-item-section avatar><q-icon name="edit" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="prodDelete(row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </td>
            <td>{{ row.codigo || '—' }}</td>
            <td>{{ row.nombre }}</td>
            <td>
              <q-badge v-if="row.tipo_producto" :color="row.tipo_producto.color || 'primary'">
                {{ row.tipo_producto.nombre }}
              </q-badge>
              <span v-else>—</span>
            </td>
            <td class="text-right">{{ row.precio ? Number(row.precio).toFixed(2) : '—' }}</td>
            <td class="text-right">
              <span :class="Number(row.stock) > 0 ? 'text-green-8 text-weight-bold' : 'text-grey-6'">
                {{ row.stock ? Number(row.stock).toFixed(0) : '0' }}
              </span>
            </td>
          </tr>
        </tbody>
      </q-markup-table>
      <q-inner-loading :showing="loading" color="primary" />
    </div>

    <div class="row items-center justify-between q-mt-xs q-px-xs">
      <div class="text-caption text-grey-6">Página {{ page }} de {{ pages }}</div>
      <q-pagination v-model="page" :max="pages" :max-pages="6"
                    boundary-links direction-links size="sm" @update:model-value="load" />
    </div>

    <!-- DIALOG PRODUCTO -->
    <q-dialog v-model="dialogProd" persistent>
      <q-card style="width:min(96vw,620px)">
        <q-card-section class="row items-center bg-teal text-white q-py-sm">
          <q-icon name="medication" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ prod.id ? 'Editar' : 'Nuevo' }} producto</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogProd = false" />
        </q-card-section>
        <q-card-section style="max-height:75vh;overflow-y:auto;padding:14px 16px">
          <q-form @submit.prevent="prodSave">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-4">
                <q-input v-model="prod.codigo" label="Código" dense outlined v-uppercase />
              </div>
              <div class="col-12 col-sm-8">
                <q-input v-model="prod.nombre" label="Nombre *" dense outlined
                         :rules="[v => !!v || 'Requerido']" v-uppercase />
              </div>
              <div class="col-12">
                <q-input v-model="prod.descripcion" label="Descripción" dense outlined
                         type="textarea" rows="2" v-uppercase />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.tipo_producto_id" label="Categoría (tipo de producto)" dense outlined
                          :options="allTipoProductos" option-value="id" option-label="nombre"
                          emit-value map-options clearable>
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar>
                        <q-badge :color="scope.opt.color || 'primary'" style="width:16px;height:16px" />
                      </q-item-section>
                      <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model.number="prod.precio" label="Precio (Bs.)" dense outlined
                         type="number" step="0.01" min="0" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="prod.marca" label="Marca" dense outlined v-uppercase />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.fabricante_id" label="Fabricante" dense outlined
                          :options="allFabricantes" option-value="id" option-label="nombre"
                          emit-value map-options clearable />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.unidad_id" label="Unidad de medida" dense outlined
                          :options="allUnidades" option-value="id"
                          :option-label="u => u.abreviatura ? u.nombre + ' (' + u.abreviatura + ')' : u.nombre"
                          emit-value map-options clearable />
              </div>
            </div>
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogProd = false" />
              <q-btn color="teal" :label="prod.id ? 'Guardar cambios' : 'Crear producto'"
                     type="submit" no-caps :loading="saving" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'

// Actualización rápida de productos (nombre, precio, categoría). El acceso a la
// ruta exige 'Editar Productos' (ver router/permissions.js).
const { proxy } = getCurrentInstance()

const canCrear    = computed(() => proxy.$store.hasPermission('Crear Productos'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Productos'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Productos'))

const productos  = ref([])
const loading    = ref(false)
const saving     = ref(false)
const dialogProd = ref(false)
const filter     = ref('')
const filterTipo = ref(null)
const page       = ref(1)
const total      = ref(0)
const perPage    = 15
const prod       = ref({})
const allFabricantes   = ref([])
const allUnidades      = ref([])
const allTipoProductos = ref([])
let timer = null

const pages = computed(() => Math.max(1, Math.ceil(total.value / perPage)))

onMounted(load)

async function load () {
  loading.value = true
  try {
    const res = await proxy.$axios.get('farmacia/datos', {
      params: {
        page_prod: page.value,
        per_page: perPage,
        q_prod: filter.value,
        tipo_producto_id: filterTipo.value,
      },
    })
    const data = res.data || {}
    productos.value = data.productos?.data || []
    total.value = data.productos?.total || 0
    allFabricantes.value = data.allFabricantes || []
    allUnidades.value = data.allUnidades || []
    allTipoProductos.value = data.allTipoProductos || []
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar productos')
  } finally {
    loading.value = false
  }
}

function onFilter () {
  clearTimeout(timer)
  timer = setTimeout(() => { page.value = 1; load() }, 350)
}

function prodNew () {
  prod.value = { codigo: '', nombre: '', descripcion: '', marca: '', fabricante_id: null, unidad_id: null, tipo_producto_id: null, precio: 0 }
  dialogProd.value = true
}

function prodEdit (row) {
  prod.value = { ...row, fabricante_id: row.fabricante?.id || null, unidad_id: row.unidad?.id || null, tipo_producto_id: row.tipo_producto?.id || null }
  dialogProd.value = true
}

async function prodSave () {
  saving.value = true
  try {
    if (prod.value.id) {
      await proxy.$axios.put('productos/' + prod.value.id, prod.value)
      proxy.$alert.success('Producto actualizado')
    } else {
      await proxy.$axios.post('productos', prod.value)
      proxy.$alert.success('Producto creado')
    }
    dialogProd.value = false
    load()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    saving.value = false
  }
}

function prodDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el producto?').onOk(() => {
    proxy.$axios.delete('productos/' + id)
      .then(() => { proxy.$alert.success('Producto eliminado'); load() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}
</script>

<style scoped>
.tabla-wrap {
  position: relative;
}

.tabla-fija {
  height: calc(100vh - 200px);
  min-height: 260px;
}

.tabla-fija :deep(thead tr) {
  background-color: #eeeeee;
}

.tabla-fija :deep(thead tr th) {
  position: sticky;
  top: 0;
  z-index: 1;
  background-color: inherit;
}
</style>
