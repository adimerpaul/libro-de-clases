<template>
  <q-page class="q-pa-sm">
    <div class="row items-center q-gutter-sm q-mb-sm">
      <div><div class="text-h6 text-weight-bold">Reactivos</div><div class="text-caption text-grey-6">Consumo vinculado a servicios de laboratorio</div></div>
      <q-space />
      <q-input v-model="filtro" dense outlined clearable debounce="350" placeholder="Código o reactivo" @update:model-value="recargar">
        <template #prepend><q-icon name="search" /></template>
      </q-input>
      <q-btn v-if="canCrear" color="positive" icon="add" label="Nuevo reactivo" no-caps @click="nuevo" />
    </div>

    <q-table ref="tableRef" flat bordered dense row-key="id" :rows="rows" :columns="columns" :loading="loading"
             v-model:pagination="pagination" :rows-per-page-options="[15, 25, 50]" @request="onRequest">
      <template #body-cell-stock="props"><q-td :props="props"><q-badge :color="Number(props.row.stock_actual) <= Number(props.row.stock_minimo) ? 'negative' : 'positive'">{{ numero(props.row.stock_actual) }} {{ props.row.unidad }}</q-badge></q-td></template>
      <template #body-cell-servicios="props"><q-td :props="props" class="servicios-cell"><q-chip v-for="uso in props.row.servicios" :key="uso.id" dense size="sm" color="blue-1" text-color="blue-9">{{ uso.producto?.nombre }}<!-- consumo por prueba oculto por ahora: · {{ numero(uso.cantidad) }} {{ props.row.unidad }} --></q-chip><span v-if="!props.row.servicios.length" class="text-grey-5">Sin vincular</span></q-td></template>
      <template #body-cell-estado="props"><q-td :props="props"><q-badge :color="props.row.estado === 'ACTIVO' ? 'positive' : 'grey'">{{ props.row.estado }}</q-badge></q-td></template>
      <template #body-cell-opciones="props"><q-td :props="props"><q-btn flat round dense icon="visibility" color="primary" @click="mostrar(props.row)" /><q-btn v-if="canEditar" flat round dense icon="edit" color="indigo" @click="editar(props.row)" /><q-btn v-if="canEliminar" flat round dense icon="delete" color="negative" @click="eliminar(props.row)" /></q-td></template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="width:min(95vw,760px);max-width:760px">
        <q-card-section class="row items-center bg-primary text-white q-py-sm"><q-icon name="vaccines" class="q-mr-sm" /><b>{{ soloLectura ? 'Detalle del reactivo' : form.id ? 'Modificar reactivo' : 'Nuevo reactivo' }}</b><q-space /><q-btn flat round dense icon="close" v-close-popup /></q-card-section>
        <q-form @submit.prevent="guardar">
          <q-card-section class="q-pa-sm">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-3"><q-input v-model="form.codigo" label="Código" dense outlined :readonly="soloLectura" v-uppercase /></div>
              <div class="col-12 col-sm-6"><q-input v-model="form.nombre" label="Nombre *" dense outlined :readonly="soloLectura" v-uppercase :rules="[required]" /></div>
              <div class="col-12 col-sm-3"><q-select v-model="form.unidad" label="Unidad *" dense outlined :readonly="soloLectura" :options="unidades" use-input new-value-mode="add-unique" :rules="[required]" /></div>
              <div class="col-6 col-sm-3"><q-input v-model.number="form.stock_actual" type="number" step="0.001" min="0" label="Stock actual *" dense outlined :readonly="soloLectura" /></div>
              <div class="col-6 col-sm-3"><q-input v-model.number="form.stock_minimo" type="number" step="0.001" min="0" label="Stock mínimo *" dense outlined :readonly="soloLectura" /></div>
              <div class="col-12 col-sm-3"><q-select v-model="form.estado" label="Estado" dense outlined :readonly="soloLectura" :options="['ACTIVO', 'INACTIVO']" /></div>
              <div class="col-12"><q-input v-model="form.descripcion" label="Descripción" dense outlined :readonly="soloLectura" v-uppercase /></div>
            </div>
            <div class="row items-center q-mt-md q-mb-xs"><b>Servicios de laboratorio vinculados</b><q-space /><q-btn v-if="!soloLectura" flat dense no-caps color="primary" icon="add" label="Agregar servicio" @click="agregarServicio" /></div>
            <q-list bordered separator>
              <q-item v-for="(uso, index) in form.servicios" :key="index">
                <q-item-section><q-select v-model="uso.producto_id" dense outlined label="Servicio" :options="servicios" option-value="id" option-label="nombre" emit-value map-options use-input :readonly="soloLectura" @filter="filtrarServicios" /></q-item-section>
                <!-- Cantidad por prueba: oculto por ahora (no se usa), se deja para uso futuro.
                     El valor sigue enviándose al backend con el default de agregarServicio(). -->
                <!-- <q-item-section side style="width:180px"><q-input v-model.number="uso.cantidad" dense outlined type="number" min="0.0001" step="0.0001" label="Cantidad por prueba" :readonly="soloLectura" :suffix="form.unidad" /></q-item-section> -->
                <q-item-section v-if="!soloLectura" side><q-btn flat round dense icon="delete" color="negative" @click="form.servicios.splice(index, 1)" /></q-item-section>
              </q-item>
              <q-item v-if="!form.servicios.length"><q-item-section class="text-grey-5 text-center">Sin servicios vinculados</q-item-section></q-item>
            </q-list>
          </q-card-section>
          <q-card-actions align="right"><q-btn flat label="Cerrar" no-caps v-close-popup /><q-btn v-if="!soloLectura" color="primary" label="Guardar" icon="save" no-caps type="submit" :loading="saving" /></q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'
const { proxy } = getCurrentInstance()
const tableRef = ref(null); const rows = ref([]); const loading = ref(false); const saving = ref(false); const filtro = ref('')
const dialog = ref(false); const soloLectura = ref(false); const serviciosTodos = ref([]); const servicios = ref([])
const pagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })
const unidades = ['ML', 'L', 'MG', 'G', 'UNIDAD', 'TIRA', 'KIT', 'FRASCO']
const canCrear = computed(() => proxy.$store.hasPermission('Crear Reactivos')); const canEditar = computed(() => proxy.$store.hasPermission('Editar Reactivos')); const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Reactivos'))
const columns = [{ name: 'opciones', label: 'Opciones', field: 'id', align: 'left' }, { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' }, { name: 'nombre', label: 'Reactivo', field: 'nombre', align: 'left' }, { name: 'stock', label: 'Stock', field: 'stock_actual', align: 'center' }, { name: 'servicios', label: 'Servicios', field: 'servicios', align: 'left' } /* antes: 'Servicios y consumo' */, { name: 'estado', label: 'Estado', field: 'estado', align: 'center' }]
const vacio = () => ({ codigo: '', nombre: '', unidad: 'ML', stock_actual: 0, stock_minimo: 0, estado: 'ACTIVO', descripcion: '', servicios: [] })
const form = ref(vacio())
function recargar () { tableRef.value?.requestServerInteraction() }
function onRequest ({ pagination: p }) { cargar(p) }
async function cargar (p = pagination.value) { loading.value = true; try { const { data } = await proxy.$axios.get('reactivos', { params: { q: filtro.value, page: p.page, per_page: p.rowsPerPage } }); rows.value = data.data; pagination.value = { page: data.current_page, rowsPerPage: data.per_page, rowsNumber: data.total } } catch (e) { proxy.$alert.error(e.response?.data?.message || 'No se pudieron cargar los reactivos') } finally { loading.value = false } }
async function cargarServicios () { if (serviciosTodos.value.length) return; const { data } = await proxy.$axios.get('reactivos/form-data'); serviciosTodos.value = data.servicios; servicios.value = data.servicios }
async function abrir (row, lectura) { await cargarServicios(); soloLectura.value = lectura; form.value = { ...row, servicios: (row.servicios || []).map(x => ({ producto_id: x.producto_id, cantidad: Number(x.cantidad) || 1 })) }; dialog.value = true }
async function nuevo () { await cargarServicios(); soloLectura.value = false; form.value = vacio(); dialog.value = true }
function mostrar (row) { abrir(row, true) } function editar (row) { abrir(row, false) }
// cantidad: 1 es el default mientras "Cantidad por prueba" está oculto en el front (el backend la exige > 0)
function agregarServicio () { form.value.servicios.push({ producto_id: null, cantidad: 1 }) }
function filtrarServicios (value, update) { update(() => { const q = String(value || '').toUpperCase(); servicios.value = q ? serviciosTodos.value.filter(x => `${x.codigo || ''} ${x.nombre}`.toUpperCase().includes(q)) : serviciosTodos.value }) }
async function guardar () { saving.value = true; try { if (form.value.id) await proxy.$axios.put(`reactivos/${form.value.id}`, form.value); else await proxy.$axios.post('reactivos', form.value); proxy.$alert.success('Reactivo guardado'); dialog.value = false; recargar() } catch (e) { proxy.$alert.error(e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat()[0] || 'No se pudo guardar') } finally { saving.value = false } }
function eliminar (row) { proxy.$alert.dialog(`¿Eliminar el reactivo ${row.nombre}?`).onOk(async () => { try { await proxy.$axios.delete(`reactivos/${row.id}`); proxy.$alert.success('Reactivo eliminado'); recargar() } catch (e) { proxy.$alert.error(e.response?.data?.message || 'No se pudo eliminar') } }) }
function required (v) { return (v !== null && v !== '') || 'Campo requerido' } function numero (v) { return Number(v || 0).toLocaleString('es-BO', { maximumFractionDigits: 4 }) }
cargar()
</script>

<style scoped>.servicios-cell { max-width: 520px; white-space: normal; }</style>
