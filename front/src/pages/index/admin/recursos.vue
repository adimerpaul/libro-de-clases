<template>
  <div>
    <div class="pageHead">
      <div>
        <h1>Biblioteca pedagógica</h1>
        <p>Registra actividades, guías, presentaciones, videos, material didáctico y apoyos DUA/PIE. Los recursos publicados aparecen en la Biblioteca de los docentes, filtrados por nivel, asignatura y OA.</p>
      </div>
      <div class="row q-gutter-sm">
        <q-btn to="/recursos" label="Ver biblioteca" icon="far fa-eye" no-caps flat />
        <q-btn v-if="$auth.hasPermission('recursos.crear')" label="+ Nuevo recurso" no-caps unelevated class="btnPrimary" @click="nuevo()" />
      </div>
    </div>

    <div class="card">
      <div class="cardHead">
        <h3>Recursos <span class="badge blue q-ml-sm">{{ filtrados.length }}</span></h3>
        <div class="filtros">
          <q-select v-model="filtroTipo" :options="TIPOS" dense outlined clearable label="Tipo" style="min-width: 160px" />
          <q-input v-model="filtro" dense outlined placeholder="Buscar título, asignatura u OA" style="min-width: 260px">
            <template #prepend><q-icon name="fas fa-magnifying-glass" size="14px" /></template>
          </q-input>
        </div>
      </div>

      <q-table
        :rows="filtrados" :columns="columns" row-key="id" flat :loading="loading" :filter="filtro"
        :pagination="{ rowsPerPage: 20 }" class="adminTable" no-data-label="Sin recursos registrados"
      >
        <template #body-cell-acciones="p">
          <q-td :props="p">
            <MenuAcciones :acciones="[
              { label: 'Editar', icon: 'fas fa-pen', permiso: 'recursos.editar', click: () => editar(p.row) },
              { label: 'Descargar archivo', icon: 'fas fa-download', si: p.row.tiene_archivo, click: () => descargarArchivo(proxy, p.row) },
              { label: p.row.publicado ? 'Ocultar de la biblioteca' : 'Publicar', icon: p.row.publicado ? 'far fa-eye-slash' : 'far fa-eye', permiso: 'recursos.editar', click: () => alternarPublicado(p.row) },
              { label: 'Eliminar', icon: 'far fa-trash-can', peligro: true, permiso: 'recursos.eliminar', click: () => eliminar(p.row, `¿Eliminar el recurso «${p.row.titulo}»?`) },
            ]" />
          </q-td>
        </template>
        <template #body-cell-titulo="p">
          <q-td :props="p">
            <div class="row items-center no-wrap q-gutter-sm">
              <span class="tipoIcono" :class="tipoRecurso(p.row.tipo).thumb"><q-icon :name="tipoRecurso(p.row.tipo).icon" size="14px" /></span>
              <div>
                <b>{{ p.row.titulo }}</b>
                <div class="subtle">
                  {{ p.row.tipo }}
                  <template v-if="p.row.tiene_archivo"> · <q-icon name="fas fa-paperclip" size="10px" /> {{ formatoTamano(p.row.archivo_tamano) }}</template>
                  <template v-if="p.row.url"> · <q-icon name="fas fa-link" size="10px" /> enlace</template>
                </div>
              </div>
            </div>
          </q-td>
        </template>
        <template #body-cell-asignatura="p">
          <q-td :props="p">
            <template v-if="p.row.asignatura">
              <AsignaturaIcono :nombre="p.row.asignatura.nombre" :color="p.row.asignatura.color" />
              {{ p.row.asignatura.nombre }}
            </template>
            <span v-else class="subtle">—</span>
          </q-td>
        </template>
        <template #body-cell-oa="p">
          <q-td :props="p">
            <span v-if="p.row.objetivo" class="badge ok">{{ p.row.objetivo.codigo }}<q-tooltip max-width="320px">{{ p.row.objetivo.texto }}</q-tooltip></span>
            <span v-else class="subtle">—</span>
          </q-td>
        </template>
        <template #body-cell-publicado="p">
          <q-td :props="p">
            <span class="badge" :class="p.row.publicado ? 'ok' : 'red'">{{ p.row.publicado ? 'Publicado' : 'Oculto' }}</span>
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog">
      <q-card class="formCard recursoCard">
        <q-form @submit.prevent="enviar">
          <q-card-section class="formTitle">{{ form.id ? 'Editar recurso' : 'Nuevo recurso' }}</q-card-section>
          <q-card-section class="formGrid">
            <q-input v-model="form.titulo" label="Título" outlined dense class="full" maxlength="200" :rules="[v => !!v || 'Requerido']" hide-bottom-space />
            <q-select v-model="form.tipo" :options="TIPOS" label="Tipo de recurso" outlined dense :rules="[v => !!v || 'Requerido']" hide-bottom-space>
              <template #option="s">
                <q-item v-bind="s.itemProps">
                  <q-item-section avatar style="min-width: 28px"><q-icon :name="tipoRecurso(s.opt).icon" size="14px" /></q-item-section>
                  <q-item-section>{{ s.opt }}</q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-select
              v-model="form.nivel" :options="catalogos.niveles" label="Nivel" outlined dense clearable
              hint="Vacío = transversal" @update:model-value="cargarOas"
            />
            <q-select
              v-model="form.asignatura_id" :options="asignaturas" option-value="id" option-label="nombre" emit-value map-options
              label="Asignatura" outlined dense clearable class="full" @update:model-value="cargarOas"
            />
            <q-select
              v-model="form.objetivo_aprendizaje_id" :options="oas" option-value="id" :option-label="o => `${o.codigo} · ${o.texto}`"
              emit-value map-options clearable label="OA vinculado (opcional)" outlined dense class="full"
              :disable="!form.nivel || !form.asignatura_id" :loading="cargandoOas"
              :hint="!form.nivel || !form.asignatura_id ? 'Elige nivel y asignatura para ver sus OA' : `${oas.length} OA vigentes`"
            >
              <template #selected-item="s"><span class="ellipsis">{{ s.opt.codigo }} · {{ s.opt.texto }}</span></template>
            </q-select>
            <q-input v-model="form.descripcion" type="textarea" autogrow label="Descripción" outlined dense class="full" maxlength="2000" />
            <q-input v-model="form.url" label="Enlace (video, presentación en línea…)" outlined dense class="full" placeholder="https://" type="url">
              <template #prepend><q-icon name="fas fa-link" size="14px" /></template>
            </q-input>

            <div class="full">
              <div v-if="form.archivo_nombre && !form.quitar_archivo && !archivo" class="adjunto">
                <q-icon name="far fa-file" />
                <span class="col ellipsis">{{ form.archivo_nombre }}</span>
                <span class="subtle">{{ formatoTamano(form.archivo_tamano) }}</span>
                <q-btn flat round dense size="sm" icon="far fa-trash-can" color="negative" aria-label="Quitar archivo" @click="form.quitar_archivo = true" />
              </div>
              <q-file
                v-else v-model="archivo" outlined dense clearable :accept="ACEPTA" max-file-size="20971520"
                :label="form.archivo_nombre ? 'Reemplazar archivo' : 'Archivo (opcional)'" hint="PDF, Word, PowerPoint, Excel, imagen, audio, video o ZIP · máx. 20 MB"
                @rejected="$alert.error('El archivo supera 20 MB o no es de un tipo permitido')"
              >
                <template #prepend><q-icon name="fas fa-paperclip" size="14px" /></template>
              </q-file>
            </div>

            <q-toggle v-model="form.publicado" label="Publicado en la biblioteca" class="full" />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" v-close-popup />
            <q-btn type="submit" no-caps unelevated label="Guardar" class="btnPrimary" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import { useCrud, useCatalogos, mensajeError } from '../../../composables/useCrud'
import { TIPOS_RECURSO, tipoRecurso, formatoTamano, descargarArchivo } from '../../../composables/biblioteca'
import MenuAcciones from '../../../components/MenuAcciones.vue'
import AsignaturaIcono from '../../../components/AsignaturaIcono.vue'

definePage({ meta: { title: 'Biblioteca', permiso: 'recursos.ver' } })

const { proxy } = getCurrentInstance()
const catalogos = useCatalogos()

const TIPOS = Object.keys(TIPOS_RECURSO)
const ACEPTA = '.pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.xls,.xlsx,.odt,.odp,.ods,.txt,.jpg,.jpeg,.png,.gif,.webp,.mp3,.mp4,.zip'

const filtro = ref('')
const filtroTipo = ref(null)
const asignaturas = ref([])
const oas = ref([])
const cargandoOas = ref(false)
const archivo = ref(null)

const { rows, loading, dialog, saving, form, cargar, nuevo: abrirNuevo, editar: abrirEditar, eliminar } = useCrud(
  'admin/recursos',
  () => ({
    titulo: '', tipo: null, descripcion: '', nivel: null, asignatura_id: null, objetivo_aprendizaje_id: null,
    url: '', publicado: true, archivo_nombre: null, archivo_tamano: null, quitar_archivo: false,
  }),
  { nombre: 'Recurso', aForm: row => ({ ...row, descripcion: row.descripcion || '', url: row.url || '', quitar_archivo: false }) },
)

const filtrados = computed(() => rows.value.filter(r => !filtroTipo.value || r.tipo === filtroTipo.value))

const columns = [
  { name: 'acciones', label: '', field: 'id', align: 'left', style: 'width: 44px' },
  { name: 'titulo', label: 'Recurso', field: r => `${r.titulo} ${r.tipo} ${r.descripcion || ''}`, align: 'left', sortable: true, sort: (a, b, ra, rb) => ra.titulo.localeCompare(rb.titulo, 'es') },
  { name: 'nivel', label: 'Nivel', field: r => r.nivel || 'Transversal', align: 'left', sortable: true },
  { name: 'asignatura', label: 'Asignatura', field: r => r.asignatura?.nombre || '', align: 'left', sortable: true },
  { name: 'oa', label: 'OA', field: r => r.objetivo ? `${r.objetivo.codigo} ${r.objetivo.texto}` : '', align: 'left' },
  { name: 'publicado', label: 'Estado', field: 'publicado', align: 'center', sortable: true },
  { name: 'autor', label: 'Registrado por', field: r => r.autor?.name || '—', align: 'left' },
]

function nuevo () {
  archivo.value = null
  oas.value = []
  abrirNuevo()
}

function editar (row) {
  archivo.value = null
  oas.value = row.objetivo ? [{ ...row.objetivo }] : []
  abrirEditar(row)
  cargarOas(false)
}

/** OA vigentes del nivel y asignatura elegidos; al cambiarlos se suelta el OA si ya no corresponde. */
function cargarOas (limpiar = true) {
  const { nivel, asignatura_id: asignaturaId } = form.value
  if (!nivel || !asignaturaId) {
    oas.value = []
    if (limpiar) form.value.objetivo_aprendizaje_id = null
    return
  }
  cargandoOas.value = true
  proxy.$axios.get('admin/recursos/oa', { params: { nivel, asignatura_id: asignaturaId } })
    .then(res => {
      oas.value = res.data
      if (!res.data.some(o => o.id === form.value.objetivo_aprendizaje_id)) form.value.objetivo_aprendizaje_id = null
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { cargandoOas.value = false })
}

/** Multipart por el archivo: al editar va como POST con _method=PUT (PHP no lee archivos en PUT). */
function datosFormulario (f) {
  const datos = new FormData()
  for (const campo of ['titulo', 'tipo', 'descripcion', 'nivel', 'asignatura_id', 'objetivo_aprendizaje_id', 'url']) {
    datos.append(campo, f[campo] ?? '')
  }
  datos.append('publicado', f.publicado ? '1' : '0')
  if (archivo.value) datos.append('archivo', archivo.value)
  else if (f.quitar_archivo) datos.append('quitar_archivo', '1')
  if (f.id) datos.append('_method', 'PUT')
  return datos
}

function enviar () {
  const f = form.value
  saving.value = true
  proxy.$axios.post(f.id ? `admin/recursos/${f.id}` : 'admin/recursos', datosFormulario(f))
    .then(() => {
      proxy.$alert.success(f.id ? 'Recurso actualizado' : 'Recurso creado')
      dialog.value = false
      return cargar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
    .finally(() => { saving.value = false })
}

function alternarPublicado (row) {
  const f = { ...row, publicado: !row.publicado, quitar_archivo: false }
  archivo.value = null
  proxy.$axios.post(`admin/recursos/${row.id}`, datosFormulario(f))
    .then(() => {
      proxy.$alert.success(f.publicado ? 'Recurso publicado' : 'Recurso oculto de la biblioteca')
      cargar()
    })
    .catch(err => proxy.$alert.error(mensajeError(err)))
}

onMounted(() => {
  cargar()
  proxy.$axios.get('admin/asignaturas')
    .then(res => { asignaturas.value = res.data.filter(a => a.activo) })
    .catch(err => proxy.$alert.error(mensajeError(err)))
})
</script>

<style scoped>
.filtros { display: flex; gap: 10px; flex-wrap: wrap }
.recursoCard { width: 640px }
.tipoIcono {
  display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 9px;
  background: #eaf3fb; color: #0f4f85; flex: none;
}
.tipoIcono.video { background: #e8ecff; color: #4a4fbb }
.tipoIcono.presentation { background: #fff1e7; color: #c4621b }
.tipoIcono.didactic { background: #eaf8f1; color: #17703f }
.adjunto {
  display: flex; align-items: center; gap: 8px; padding: 7px 8px 7px 12px;
  border: 1px solid #d9e3ec; border-radius: 10px; background: #f5f9fc;
}
</style>
