<template>
  <q-page class="q-pa-xs">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Productos</div>
            <div class="text-h6 text-teal text-weight-bold">{{ resumen.productos }}</div>
          </q-card>
        </div>
        <div class="col">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Fabricantes</div>
            <div class="text-h6 text-deep-orange text-weight-bold">{{ resumen.fabricantes }}</div>
          </q-card>
        </div>
        <div class="col">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Unidades</div>
            <div class="text-h6 text-purple text-weight-bold">{{ resumen.unidades }}</div>
          </q-card>
        </div>
        <div class="col">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Tipos</div>
            <div class="text-h6 text-indigo text-weight-bold">{{ resumen.tipos }}</div>
          </q-card>
        </div>
        <div class="col">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Tipos padre</div>
            <div class="text-h6 text-deep-purple text-weight-bold">{{ resumen.padres }}</div>
          </q-card>
        </div>
      </div>

      <q-tabs v-model="tab" dense align="left"
              active-color="primary" indicator-color="primary"
              class="q-mb-xs">
        <q-tab name="productos"   icon="medication"  label="Productos" no-caps />
        <q-tab name="fabricantes" icon="factory"     label="Fabricantes" no-caps />
        <q-tab name="unidades"    icon="straighten"  label="Unidades" no-caps />
        <q-tab name="padres"      icon="account_tree" label="Tipos padre" no-caps />
        <q-tab name="tipos"       icon="category"    label="Tipos de producto" no-caps />
      </q-tabs>
      <q-separator class="q-mb-xs" />

      <!-- ══ TAB PRODUCTOS ══════════════════════════════════════════ -->
      <div v-show="tab === 'productos'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Productos farmacia</span>
          <q-space />
          <q-select v-model="filterTipoProducto" label="Categoría" dense outlined clearable
                     :options="allTipoProductos" option-value="id" option-label="nombre"
                     emit-value map-options style="width:180px" @update:model-value="onFilterProd">
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section avatar>
                  <q-badge v-bind="colorAttrs(scope.opt.color)" style="width:16px;height:16px" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                  <q-item-label v-if="scope.opt.padre" caption>{{ scope.opt.padre.nombre }}</q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-select>
          <q-input v-model="filterProd" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterProd">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="prodNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense :loading="exportingPdf" :disable="exportingPdf"
                 @click="exportPdf('productos')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense :loading="exportingExcel" :disable="exportingExcel"
                 @click="exportExcel('productos')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <div class="tabla-wrap">
          <q-markup-table dense flat bordered separator="cell" class="tabla-fija full-width">
            <thead>
              <tr class="bg-grey-2">
                <th class="text-left" style="width:64px"></th>
                <th class="text-left">Nombre</th>
                <th class="text-left">Tipo</th>
                <th class="text-right">Precio (Bs.)</th>
                <th class="text-right">Stock</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!productos.length && !loadingProd">
                <td colspan="5" class="text-center text-grey-5 q-pa-md">Sin datos</td>
              </tr>
              <tr v-for="row in productos" :key="row.id">
                <td class="q-pa-xs">
                  <q-btn-dropdown
                    label="Opciones"
                    no-caps
                    size="10px"
                    dense
                    color="primary"
                  >
                    <q-list>
                      <q-item clickable v-close-popup @click="prodHistorial(row)">
                        <q-item-section avatar><q-icon name="history" color="teal" /></q-item-section>
                        <q-item-section><q-item-label>Historial de compras y ventas</q-item-label></q-item-section>
                      </q-item>
                      <q-separator />
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
                <td>{{ row.nombre }}</td>
                <td>
                  <q-badge v-if="row.tipo_producto" v-bind="colorAttrs(row.tipo_producto.color)">
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
          <q-inner-loading :showing="loadingProd" color="primary" />
        </div>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalProd }} | Página {{ pageProd }} de {{ pagesProd }}
          </div>
          <q-pagination v-model="pageProd" :max="pagesProd" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadProductos" />
        </div>
      </div>

      <!-- ══ TAB FABRICANTES ════════════════════════════════════════ -->
      <div v-show="tab === 'fabricantes'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Fabricantes</span>
          <q-space />
          <q-input v-model="filterFab" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterFab">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="fabNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense :loading="exportingPdf" :disable="exportingPdf"
                 @click="exportPdf('fabricantes')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense :loading="exportingExcel" :disable="exportingExcel"
                 @click="exportExcel('fabricantes')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <div class="tabla-wrap">
          <q-markup-table dense flat bordered separator="cell" class="tabla-fija full-width">
            <thead>
              <tr class="bg-grey-2">
                <th class="text-left" style="width:64px"></th>
                <th class="text-left">Nombre</th>
                <th class="text-left">País</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!fabricantes.length && !loadingFab">
                <td colspan="3" class="text-center text-grey-5 q-pa-md">Sin datos</td>
              </tr>
              <tr v-for="row in fabricantes" :key="row.id">
                <td class="q-pa-xs">
                  <q-btn-dropdown
                    v-if="canEditar || canEliminar"
                    label="Opciones"
                    no-caps
                    size="10px"
                    dense
                    color="primary"
                  >
                    <q-list>
                      <q-item v-if="canEditar" clickable v-close-popup @click="fabEdit(row)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                      </q-item>
                      <q-item v-if="canEliminar" clickable v-close-popup @click="fabDelete(row.id)">
                        <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                        <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </td>
                <td>{{ row.nombre }}</td>
                <td>{{ row.pais || '—' }}</td>
              </tr>
            </tbody>
          </q-markup-table>
          <q-inner-loading :showing="loadingFab" color="deep-orange" />
        </div>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalFab }} | Página {{ pageFab }} de {{ pagesFab }}
          </div>
          <q-pagination v-model="pageFab" :max="pagesFab" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadFabricantes" />
        </div>
      </div>

      <!-- ══ TAB UNIDADES ══════════════════════════════════════════ -->
      <div v-show="tab === 'unidades'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Unidades de medida</span>
          <q-space />
          <q-input v-model="filterUnid" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterUnid">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="unidNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense :loading="exportingPdf" :disable="exportingPdf"
                 @click="exportPdf('unidades')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense :loading="exportingExcel" :disable="exportingExcel"
                 @click="exportExcel('unidades')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <div class="tabla-wrap">
          <q-markup-table dense flat bordered separator="cell" class="tabla-fija full-width">
            <thead>
              <tr class="bg-grey-2">
                <th class="text-left" style="width:64px"></th>
                <th class="text-left">Nombre</th>
                <th class="text-left">Abreviatura</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!unidades.length && !loadingUnid">
                <td colspan="3" class="text-center text-grey-5 q-pa-md">Sin datos</td>
              </tr>
              <tr v-for="row in unidades" :key="row.id">
                <td class="q-pa-xs">
                  <q-btn-dropdown
                    v-if="canEditar || canEliminar"
                    label="Opciones"
                    no-caps
                    size="10px"
                    dense
                    color="primary"
                  >
                    <q-list>
                      <q-item v-if="canEditar" clickable v-close-popup @click="unidEdit(row)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                      </q-item>
                      <q-item v-if="canEliminar" clickable v-close-popup @click="unidDelete(row.id)">
                        <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                        <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </td>
                <td>{{ row.nombre }}</td>
                <td>{{ row.abreviatura || '—' }}</td>
              </tr>
            </tbody>
          </q-markup-table>
          <q-inner-loading :showing="loadingUnid" color="purple" />
        </div>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalUnid }} | Página {{ pageUnid }} de {{ pagesUnid }}
          </div>
          <q-pagination v-model="pageUnid" :max="pagesUnid" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadUnidades" />
        </div>
      </div>

      <!-- ══ TAB TIPOS DE PRODUCTO PADRE ═══════════════════════════ -->
      <div v-show="tab === 'padres'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Tipos de producto padre (agrupan tipos de producto)</span>
          <q-space />
          <q-input v-model="filterPadre" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterPadre">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="padreNew" />
        </div>

        <div class="tabla-wrap">
          <q-markup-table dense flat bordered separator="cell" class="tabla-fija full-width">
            <thead>
              <tr class="bg-grey-2">
                <th class="text-left" style="width:64px"></th>
                <th class="text-left">Nombre</th>
                <th class="text-left">Vista</th>
                <th class="text-center" style="width:100px">Laboratorio</th>
                <th class="text-right" style="width:70px">Orden</th>
                <th class="text-right" style="width:90px">Tipos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!padres.length && !loadingTipo">
                <td colspan="6" class="text-center text-grey-5 q-pa-md">Sin datos</td>
              </tr>
              <tr v-for="row in padres" :key="row.id">
                <td class="q-pa-xs">
                  <q-btn-dropdown
                    v-if="canEditar || canEliminar || canVer"
                    label="Opciones"
                    no-caps
                    size="10px"
                    dense
                    color="primary"
                  >
                    <q-list>
                      <q-item clickable v-close-popup @click="padreVerTipos(row)">
                        <q-item-section avatar><q-icon name="category" color="indigo" /></q-item-section>
                        <q-item-section><q-item-label>Ver tipos</q-item-label></q-item-section>
                      </q-item>
                      <q-item v-if="canEditar" clickable v-close-popup @click="padreEdit(row)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                      </q-item>
                      <q-item v-if="canEliminar" clickable v-close-popup @click="padreDelete(row.id)">
                        <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                        <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </td>
                <td>
                  <q-icon :name="row.icono || 'category'" size="18px" class="q-mr-xs" v-bind="iconAttrs(row.color)" />
                  {{ row.nombre }}
                </td>
                <td>
                  <q-chip dense square size="12px" text-color="white"
                          :icon="row.icono || 'category'" v-bind="colorAttrs(row.color)">
                    {{ row.nombre }}
                  </q-chip>
                </td>
                <td class="text-center">
                  <q-icon v-if="row.es_laboratorio" name="check_circle" color="positive" size="18px" />
                  <span v-else class="text-grey-5">—</span>
                </td>
                <td class="text-right">{{ row.orden }}</td>
                <td class="text-right">{{ row.tipos_count ?? 0 }}</td>
              </tr>
            </tbody>
          </q-markup-table>
          <q-inner-loading :showing="loadingTipo" color="deep-purple" />
        </div>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalPadre }} | Página {{ pagePadre }} de {{ pagesPadre }}
          </div>
          <q-pagination v-model="pagePadre" :max="pagesPadre" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadFarmaciaData" />
        </div>
      </div>

      <!-- ══ TAB TIPOS DE PRODUCTO ═════════════════════════════════ -->
      <div v-show="tab === 'tipos'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Tipos de producto (categorías)</span>
          <q-space />
          <q-select v-model="filterTipoPadre" label="Tipo padre" dense outlined clearable
                    :options="allTipoProductoPadres" option-value="id" option-label="nombre"
                    emit-value map-options style="width:200px" @update:model-value="onFilterTipo">
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section avatar>
                  <q-icon :name="scope.opt.icono || 'category'" v-bind="iconAttrs(scope.opt.color)" />
                </q-item-section>
                <q-item-section>{{ scope.opt.nombre }}</q-item-section>
              </q-item>
            </template>
          </q-select>
          <q-input v-model="filterTipo" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterTipo">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="tipoNew" />
        </div>

        <div class="tabla-wrap">
          <q-markup-table dense flat bordered separator="cell" class="tabla-fija full-width">
            <thead>
              <tr class="bg-grey-2">
                <th class="text-left" style="width:64px"></th>
                <th class="text-left">Nombre</th>
                <th class="text-left">Tipo padre</th>
                <th class="text-left">Tipo</th>
                <th class="text-right" style="width:90px">Productos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!tipos.length && !loadingTipo">
                <td colspan="5" class="text-center text-grey-5 q-pa-md">Sin datos</td>
              </tr>
              <tr v-for="row in tipos" :key="row.id">
                <td class="q-pa-xs">
                  <q-btn-dropdown
                    v-if="canEditar || canEliminar"
                    label="Opciones"
                    no-caps
                    size="10px"
                    dense
                    color="primary"
                  >
                    <q-list>
                      <q-item v-if="canEditar" clickable v-close-popup @click="tipoEdit(row)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                      </q-item>
                      <q-item v-if="canEliminar" clickable v-close-popup @click="tipoDelete(row.id)">
                        <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                        <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </td>
                <td>{{ row.nombre }}</td>
                <td>
                  <q-chip v-if="row.padre" dense square size="12px" text-color="white"
                          :icon="row.padre.icono || 'category'" v-bind="colorAttrs(row.padre.color)">
                    {{ row.padre.nombre }}
                  </q-chip>
                  <span v-else class="text-grey-5">Sin padre</span>
                </td>
                <td><q-badge v-bind="colorAttrs(row.color)">{{ row.nombre }}</q-badge></td>
                <td class="text-right">{{ row.productos_count ?? 0 }}</td>
              </tr>
            </tbody>
          </q-markup-table>
          <q-inner-loading :showing="loadingTipo" color="indigo" />
        </div>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalTipo }} | Página {{ pageTipo }} de {{ pagesTipo }}
          </div>
          <q-pagination v-model="pageTipo" :max="pagesTipo" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadTipos" />
        </div>
      </div>

    </template>

    <!-- ═══ DIALOG PRODUCTO ══════════════════════════════════════ -->
    <q-dialog v-model="dialogProd" persistent>
      <q-card style="width:min(96vw,620px)">
        <q-card-section class="row items-center bg-teal text-white q-py-sm">
          <q-icon name="medication" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ prodAction }} producto</span>
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
                        <q-badge v-bind="colorAttrs(scope.opt.color)" style="width:16px;height:16px" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                        <q-item-label v-if="scope.opt.padre" caption>{{ scope.opt.padre.nombre }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="indigo"
                           @click="tipoQuick = true">
                      <q-tooltip>Nuevo tipo de producto</q-tooltip>
                    </q-btn>
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
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="deep-orange"
                           @click="fabQuick = true">
                      <q-tooltip>Nuevo fabricante</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.unidad_id" label="Unidad de medida" dense outlined
                          :options="allUnidades" option-value="id"
                          :option-label="u => u.abreviatura ? u.nombre + ' (' + u.abreviatura + ')' : u.nombre"
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="purple"
                           @click="unidQuick = true">
                      <q-tooltip>Nueva unidad</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
            </div>
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogProd = false" />
              <q-btn color="teal" :label="prod.id ? 'Guardar cambios' : 'Crear producto'"
                     type="submit" no-caps :loading="savingProd" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG HISTORIAL DEL PRODUCTO -->
    <q-dialog v-model="dialogHistorial">
      <q-card style="width:min(96vw,1000px);max-width:1000px">
        <q-card-section class="row items-center bg-teal text-white q-py-sm">
          <q-icon name="history" size="22px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle1 text-weight-bold">Historial de compras y ventas</div>
            <div class="text-caption">{{ historialProducto.codigo || 'SIN CÓDIGO' }} · {{ historialProducto.nombre }}</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-sm">
          <div class="row items-center q-gutter-sm q-mb-sm">
            <q-chip color="blue-1" text-color="blue-9" icon="shopping_cart" square>
              Comprado: <strong class="q-ml-xs">{{ cantidadComprada.toFixed(2) }}</strong>
            </q-chip>
            <q-chip color="teal-1" text-color="teal-9" icon="point_of_sale" square>
              Vendido: <strong class="q-ml-xs">{{ cantidadVendida.toFixed(2) }}</strong>
            </q-chip>
            <q-chip color="green-1" text-color="green-9" icon="inventory_2" square>
              Saldo: <strong class="q-ml-xs">{{ saldoHistorial.toFixed(2) }}</strong>
            </q-chip>
          </div>

          <q-tabs v-model="tabHistorial" dense align="left" no-caps
                  active-color="primary" indicator-color="primary" class="text-grey-7">
            <q-tab name="compras" icon="shopping_cart"
                   :label="'Compras (' + comprasHistorial.length + ')'" />
            <q-tab name="ventas" icon="point_of_sale"
                   :label="'Ventas (' + ventasHistorial.length + ')'" />
          </q-tabs>
          <q-separator class="q-mb-sm" />

          <div class="tabla-wrap">
            <q-markup-table class="tabla-fija tabla-fija-sm" dense flat bordered separator="cell">
              <thead>
                <tr class="bg-grey-2">
                  <th class="text-left">Fecha</th>
                  <th class="text-left">Documento</th>
                  <th class="text-left">{{ tabHistorial === 'compras' ? 'Proveedor' : 'Cliente' }}</th>
                  <th class="text-left">Lote</th>
                  <th class="text-left">Vencimiento</th>
                  <th class="text-right">{{ tabHistorial === 'compras' ? 'Comprada' : 'Cantidad' }}</th>
                  <th v-if="tabHistorial === 'compras'" class="text-right">Vendida</th>
                  <th v-if="tabHistorial === 'compras'" class="text-right">Saldo</th>
                  <th class="text-right">Precio (Bs.)</th>
                  <th class="text-right">Total (Bs.)</th>
                  <th class="text-center">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!movimientosTabHistorial.length && !loadingHistorial">
                  <td :colspan="tabHistorial === 'compras' ? 11 : 9" class="text-center text-grey-6 q-pa-lg">
                    No hay {{ tabHistorial }} registradas para este producto.
                  </td>
                </tr>
                <tr v-for="(mov, index) in movimientosTabHistorial" :key="mov.tipo + '-' + mov.id + '-' + index">
                  <td>{{ formatFechaHistorial(mov.fecha_hora) }}</td>
                  <td>{{ mov.documento }}</td>
                  <td>{{ mov.tercero }}</td>
                  <td>
                    {{ mov.lote || 'SIN LOTE' }}
                    <EditarLoteHistorial :producto="historialProducto" :movimiento="mov"
                                        @actualizado="movimientosHistorial = $event.movimientos" />
                  </td>
                  <td>{{ mov.fecha_vencimiento || 'SIN FECHA' }}</td>
                  <td class="text-right">{{ Number(mov.cantidad).toFixed(2) }}</td>
                  <td v-if="tabHistorial === 'compras'" class="text-right text-teal-8 text-weight-bold">
                    {{ Number(mov.cantidad_vendida || 0).toFixed(2) }}
                  </td>
                  <td v-if="tabHistorial === 'compras'" class="text-right">
                    <q-badge :color="Number(mov.saldo) > 0 ? 'green-1' : 'red-1'"
                             :text-color="Number(mov.saldo) > 0 ? 'green-9' : 'negative'">
                      {{ Number(mov.saldo || 0).toFixed(2) }}
                    </q-badge>
                  </td>
                  <td class="text-right">{{ Number(mov.precio).toFixed(2) }}</td>
                  <td class="text-right text-weight-bold">{{ Number(mov.total).toFixed(2) }}</td>
                  <td class="text-center">
                    <q-badge :color="mov.estado === 'ANULADO' ? 'negative' : (mov.estado === 'PENDIENTE' ? 'warning' : 'positive')">
                      {{ mov.estado }}
                    </q-badge>
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <q-inner-loading :showing="loadingHistorial" color="teal" />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG FABRICANTE -->
    <q-dialog v-model="dialogFab" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-deep-orange text-white q-py-sm">
          <q-icon name="factory" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ fabAction }} fabricante</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogFab = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="fabSave">
            <q-input v-model="fab.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-input v-model="fab.pais" label="País" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogFab = false" />
              <q-btn color="deep-orange" :label="fab.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingFab" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG UNIDAD -->
    <q-dialog v-model="dialogUnid" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-purple text-white q-py-sm">
          <q-icon name="straighten" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ unidAction }} unidad</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogUnid = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="unidSave">
            <q-input v-model="unid.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-input v-model="unid.abreviatura" label="Abreviatura (ej: mg, ml, un)"
                     dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogUnid = false" />
              <q-btn color="purple" :label="unid.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingUnid" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG TIPO DE PRODUCTO -->
    <q-dialog v-model="dialogTipo" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-indigo text-white q-py-sm">
          <q-icon name="category" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ tipoAction }} tipo de producto</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogTipo = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="tipoSave">
            <q-input v-model="tipoItem.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-select v-model="tipoItem.tipo_producto_padre_id" label="Tipo padre" dense outlined clearable class="q-mb-sm"
                      :options="allTipoProductoPadres" option-value="id" option-label="nombre"
                      emit-value map-options>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-icon :name="scope.opt.icono || 'category'" v-bind="iconAttrs(scope.opt.color)" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-select v-model="tipoItem.color" label="Color" dense outlined class="q-mb-md"
                      :options="quasarColors" emit-value map-options>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-badge :color="scope.opt.value" style="width:16px;height:16px" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.label }}</q-item-section>
                </q-item>
              </template>
              <template v-slot:selected-item="scope">
                <q-badge :color="scope.opt.value" class="q-mr-xs" style="width:12px;height:12px" />
                {{ scope.opt.label }}
              </template>
            </q-select>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogTipo = false" />
              <q-btn color="indigo" :label="tipoItem.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingTipo" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG TIPO DE PRODUCTO PADRE -->
    <q-dialog v-model="dialogPadre" persistent>
      <q-card style="width:min(96vw,440px)">
        <q-card-section class="row items-center bg-deep-purple text-white q-py-sm">
          <q-icon name="account_tree" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ padreAction }} tipo de producto padre</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogPadre = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="padreSave">
            <q-input v-model="padreItem.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-select v-model="padreItem.color" label="Color" dense outlined
                          :options="quasarColors" emit-value map-options>
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar>
                        <q-badge :color="scope.opt.value" style="width:16px;height:16px" />
                      </q-item-section>
                      <q-item-section>{{ scope.opt.label }}</q-item-section>
                    </q-item>
                  </template>
                  <template v-slot:selected-item="scope">
                    <q-badge :color="scope.opt.value" class="q-mr-xs" style="width:12px;height:12px" />
                    {{ scope.opt.label }}
                  </template>
                </q-select>
              </div>
              <div class="col-6">
                <q-select v-model="padreItem.icono" label="Ícono" dense outlined
                          :options="iconosPadre" use-input fill-input hide-selected
                          input-debounce="0" new-value-mode="add-unique"
                          @input-value="v => { if (v) padreItem.icono = v }">
                  <template v-slot:prepend>
                    <q-icon :name="padreItem.icono || 'category'" v-bind="iconAttrs(padreItem.color)" />
                  </template>
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar><q-icon :name="scope.opt" /></q-item-section>
                      <q-item-section>{{ scope.opt }}</q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>
            </div>
            <div class="row items-center q-col-gutter-sm q-mb-md">
              <div class="col-6">
                <q-input v-model.number="padreItem.orden" label="Orden" type="number" min="0" dense outlined />
              </div>
              <div class="col-6">
                <q-toggle v-model="padreItem.es_laboratorio" label="Es laboratorio" color="deep-purple" />
              </div>
              <div class="col-12 text-caption text-grey-7">
                Los tipos de producto de este padre heredan la marca de laboratorio.
              </div>
            </div>
            <div class="q-mb-md">
              <span class="text-caption text-grey-7 q-mr-sm">Vista previa:</span>
              <q-chip dense square size="12px" text-color="white"
                      :icon="padreItem.icono || 'category'" v-bind="colorAttrs(padreItem.color)">
                {{ padreItem.nombre || 'NOMBRE' }}
              </q-chip>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogPadre = false" />
              <q-btn color="deep-purple" :label="padreItem.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingPadre" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick tipo de producto -->
    <q-dialog v-model="tipoQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-indigo text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo tipo de producto rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="tipoQuickSave">
            <q-input v-model="tipoQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-select v-model="tipoQPadre" label="Tipo padre" dense outlined clearable class="q-mb-sm"
                      :options="allTipoProductoPadres" option-value="id" option-label="nombre"
                      emit-value map-options>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-icon :name="scope.opt.icono || 'category'" v-bind="iconAttrs(scope.opt.color)" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-select v-model="tipoQColor" label="Color" dense outlined class="q-mb-md"
                      :options="quasarColors" emit-value map-options>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-badge :color="scope.opt.value" style="width:16px;height:16px" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.label }}</q-item-section>
                </q-item>
              </template>
              <template v-slot:selected-item="scope">
                <q-badge :color="scope.opt.value" class="q-mr-xs" style="width:12px;height:12px" />
                {{ scope.opt.label }}
              </template>
            </q-select>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="tipoQuick = false" />
              <q-btn color="indigo" label="Crear" type="submit" no-caps :loading="savingTipo" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick fabricante -->
    <q-dialog v-model="fabQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-deep-orange text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo fabricante rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="fabQuickSave">
            <q-input v-model="fabQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="fabQPais" label="País" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="fabQuick = false" />
              <q-btn color="deep-orange" label="Crear" type="submit" no-caps :loading="savingFab" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick unidad -->
    <q-dialog v-model="unidQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-purple text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nueva unidad rápida</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="unidQuickSave">
            <q-input v-model="unidQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="unidQAbrev" label="Abreviatura" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="unidQuick = false" />
              <q-btn color="purple" label="Crear" type="submit" no-caps :loading="savingUnid" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import EditarLoteHistorial from '../../../components/EditarLoteHistorial.vue'

const { proxy } = getCurrentInstance()

// ── Colores Quasar disponibles ──────────────────────────────────
const quasarColors = [
  'primary', 'secondary', 'accent', 'positive', 'negative', 'info', 'warning', 'dark',
  'red', 'pink', 'purple', 'deep-purple', 'indigo', 'blue', 'light-blue', 'cyan', 'teal',
  'green', 'light-green', 'lime', 'yellow', 'amber', 'orange', 'deep-orange', 'brown',
  'grey', 'blue-grey',
].map(c => ({ label: c, value: c }))

// Las áreas de laboratorio guardan colores hex (#rrggbb), que la prop color de
// Quasar no entiende: esos se aplican como estilo.
function colorAttrs (color) {
  const c = color || 'primary'
  return c.startsWith('#') ? { style: { backgroundColor: c, color: '#fff' } } : { color: c }
}

function iconAttrs (color) {
  const c = color || 'primary'
  return c.startsWith('#') ? { style: { color: c } } : { color: c }
}

const iconosPadre = [
  'science', 'biotech', 'bloodtype', 'medication', 'local_pharmacy', 'vaccines',
  'medical_services', 'local_hospital', 'emergency', 'monitor_heart', 'healing',
  'health_and_safety', 'airport_shuttle', 'child_care', 'visibility', 'psychology',
  'inventory_2', 'category',
]

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Productos'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Productos'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Productos'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Productos'))

// ── Estado general ─────────────────────────────────────────────
const tab     = ref('productos')
const resumen = ref({ productos: 0, fabricantes: 0, unidades: 0, tipos: 0, padres: 0 })
const exportingPdf   = ref(false)
const exportingExcel = ref(false)

// ── Productos ──────────────────────────────────────────────────
const productos   = ref([])
const loadingProd = ref(false)
const savingProd  = ref(false)
const dialogProd  = ref(false)
const prodAction  = ref('Nuevo')
const filterProd  = ref('')
const filterTipoProducto = ref(null)
const pageProd    = ref(1)
const totalProd   = ref(0)
const perProd     = 15
const prod        = ref({})
const dialogHistorial = ref(false)
const loadingHistorial = ref(false)
const historialProducto = ref({})
const movimientosHistorial = ref([])
const tabHistorial = ref('compras')
const comprasHistorial = computed(() => movimientosHistorial.value.filter(mov => mov.tipo === 'COMPRA'))
const ventasHistorial = computed(() => movimientosHistorial.value.filter(mov => mov.tipo === 'VENTA'))
const movimientosTabHistorial = computed(() => (
  tabHistorial.value === 'compras' ? comprasHistorial.value : ventasHistorial.value
))
const cantidadComprada = computed(() => comprasHistorial.value
  .filter(mov => mov.estado === 'ACTIVO')
  .reduce((total, mov) => total + Number(mov.cantidad || 0), 0))
const cantidadVendida = computed(() => ventasHistorial.value
  .filter(mov => mov.estado === 'ACTIVO')
  .reduce((total, mov) => total + Number(mov.cantidad || 0), 0))
const saldoHistorial = computed(() => cantidadComprada.value - cantidadVendida.value)
let timerProd     = null

const pagesProd = computed(() => Math.max(1, Math.ceil(totalProd.value / perProd)))

// ── Fabricantes ────────────────────────────────────────────────
const fabricantes    = ref([])
const allFabricantes = ref([])
const loadingFab     = ref(false)
const savingFab      = ref(false)
const dialogFab      = ref(false)
const fabAction      = ref('Nuevo')
const filterFab      = ref('')
const pageFab        = ref(1)
const totalFab       = ref(0)
const perFab         = 15
const fab            = ref({})
const fabQuick       = ref(false)
const fabQNombre     = ref('')
const fabQPais       = ref('')
let timerFab         = null

const pagesFab = computed(() => Math.max(1, Math.ceil(totalFab.value / perFab)))

// ── Unidades ───────────────────────────────────────────────────
const unidades    = ref([])
const allUnidades = ref([])
const loadingUnid = ref(false)
const savingUnid  = ref(false)
const dialogUnid  = ref(false)
const unidAction  = ref('Nuevo')
const filterUnid  = ref('')
const pageUnid    = ref(1)
const totalUnid   = ref(0)
const perUnid     = 15
const unid        = ref({})
const unidQuick   = ref(false)
const unidQNombre = ref('')
const unidQAbrev  = ref('')
let timerUnid     = null

const pagesUnid = computed(() => Math.max(1, Math.ceil(totalUnid.value / perUnid)))

// ── Tipos de producto ────────────────────────────────────────────
const tipos           = ref([])
const allTipoProductos = ref([])
const loadingTipo     = ref(false)
const savingTipo      = ref(false)
const dialogTipo      = ref(false)
const tipoAction      = ref('Nuevo')
const filterTipo      = ref('')
const pageTipo        = ref(1)
const totalTipo       = ref(0)
const perTipo         = 15
const tipoItem        = ref({})
const tipoQuick       = ref(false)
const tipoQNombre     = ref('')
const tipoQColor      = ref('primary')
const tipoQPadre      = ref(null)
const filterTipoPadre = ref(null)
let timerTipo         = null

const pagesTipo = computed(() => Math.max(1, Math.ceil(totalTipo.value / perTipo)))

// ── Tipos de producto padre ──────────────────────────────────────
const padres                = ref([])
const allTipoProductoPadres = ref([])
const savingPadre           = ref(false)
const dialogPadre           = ref(false)
const padreAction           = ref('Nuevo')
const filterPadre           = ref('')
const pagePadre             = ref(1)
const totalPadre            = ref(0)
const perPadre              = 15
const padreItem             = ref({})
let timerPadre              = null

const pagesPadre = computed(() => Math.max(1, Math.ceil(totalPadre.value / perPadre)))

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadFarmaciaData()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })

async function loadFarmaciaData () {
  loadingProd.value = true
  loadingFab.value = true
  loadingUnid.value = true
  loadingTipo.value = true

  try {
    const res = await proxy.$axios.get('farmacia/datos', {
      params: {
        page_prod: pageProd.value,
        page_fab: pageFab.value,
        page_unid: pageUnid.value,
        page_tipo: pageTipo.value,
        page_padre: pagePadre.value,
        per_page: perProd,
        q_prod: filterProd.value,
        q_fab: filterFab.value,
        q_unid: filterUnid.value,
        q_tipo: filterTipo.value,
        q_padre: filterPadre.value,
        tipo_producto_padre_id: filterTipoPadre.value,
        tipo_producto_id: filterTipoProducto.value,
      },
    })

    const data = res.data || {}
    resumen.value = data.resumen || { productos: 0, fabricantes: 0, unidades: 0, tipos: 0, padres: 0 }

    productos.value = data.productos?.data || []
    totalProd.value = data.productos?.total || 0

    fabricantes.value = data.fabricantes?.data || []
    totalFab.value = data.fabricantes?.total || 0

    unidades.value = data.unidades?.data || []
    totalUnid.value = data.unidades?.total || 0

    tipos.value = data.tipos?.data || []
    totalTipo.value = data.tipos?.total || 0

    padres.value = data.padres?.data || []
    totalPadre.value = data.padres?.total || 0

    allFabricantes.value = data.allFabricantes || []
    allUnidades.value = data.allUnidades || []
    allTipoProductos.value = data.allTipoProductos || []
    allTipoProductoPadres.value = data.allTipoProductoPadres || []
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar')
  } finally {
    loadingProd.value = false
    loadingFab.value = false
    loadingUnid.value = false
    loadingTipo.value = false
  }
}

function loadProductos () { return loadFarmaciaData() }
function loadFabricantes () { return loadFarmaciaData() }
function loadUnidades () { return loadFarmaciaData() }
function loadTipos () { return loadFarmaciaData() }

function onFilterProd () {
  clearTimeout(timerProd)
  timerProd = setTimeout(() => { pageProd.value = 1; loadProductos() }, 350)
}

function prodNew () {
  prod.value = { codigo: '', nombre: '', descripcion: '', marca: '', fabricante_id: null, unidad_id: null, tipo_producto_id: null, precio: 0 }
  prodAction.value = 'Nuevo'
  dialogProd.value = true
}

function prodEdit (row) {
  prod.value = { ...row, fabricante_id: row.fabricante?.id || null, unidad_id: row.unidad?.id || null, tipo_producto_id: row.tipo_producto?.id || null }
  prodAction.value = 'Editar'
  dialogProd.value = true
}

async function prodHistorial (row) {
  historialProducto.value = row
  movimientosHistorial.value = []
  tabHistorial.value = 'compras'
  dialogHistorial.value = true
  loadingHistorial.value = true
  try {
    const res = await proxy.$axios.get('productos/' + row.id + '/historial')
    historialProducto.value = res.data?.producto || row
    movimientosHistorial.value = res.data?.movimientos || []
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar el historial')
  } finally {
    loadingHistorial.value = false
  }
}

function formatFechaHistorial (fecha) {
  if (!fecha) return '—'
  return new Intl.DateTimeFormat('es-BO', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(fecha))
}

async function prodSave () {
  savingProd.value = true
  try {
    const payload = { ...prod.value }
    if (prod.value.id) {
      await proxy.$axios.put('productos/' + prod.value.id, payload)
      proxy.$alert.success('Producto actualizado')
    } else {
      await proxy.$axios.post('productos', payload)
      proxy.$alert.success('Producto creado')
    }
    dialogProd.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingProd.value = false
  }
}

function prodDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el producto?').onOk(() => {
    proxy.$axios.delete('productos/' + id)
      .then(() => { proxy.$alert.success('Producto eliminado'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

function onFilterFab () {
  clearTimeout(timerFab)
  timerFab = setTimeout(() => { pageFab.value = 1; loadFabricantes() }, 350)
}

function fabNew ()     { fab.value = { nombre: '', pais: '' }; fabAction.value = 'Nuevo'; dialogFab.value = true }
function fabEdit (row) { fab.value = { ...row }; fabAction.value = 'Editar'; dialogFab.value = true }

async function fabSave () {
  savingFab.value = true
  try {
    if (fab.value.id) {
      await proxy.$axios.put('fabricantes/' + fab.value.id, fab.value)
      proxy.$alert.success('Fabricante actualizado')
    } else {
      await proxy.$axios.post('fabricantes', fab.value)
      proxy.$alert.success('Fabricante creado')
    }
    dialogFab.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingFab.value = false
  }
}

function fabDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el fabricante?').onOk(() => {
    proxy.$axios.delete('fabricantes/' + id)
      .then(() => { proxy.$alert.success('Fabricante eliminado'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function fabQuickSave () {
  savingFab.value = true
  try {
    const res = await proxy.$axios.post('fabricantes', { nombre: fabQNombre.value, pais: fabQPais.value })
    loadFarmaciaData()
    prod.value.fabricante_id = res.data.id
    fabQuick.value = false
    fabQNombre.value = ''
    fabQPais.value = ''
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingFab.value = false
  }
}

function onFilterUnid () {
  clearTimeout(timerUnid)
  timerUnid = setTimeout(() => { pageUnid.value = 1; loadUnidades() }, 350)
}

function unidNew ()     { unid.value = { nombre: '', abreviatura: '' }; unidAction.value = 'Nuevo'; dialogUnid.value = true }
function unidEdit (row) { unid.value = { ...row }; unidAction.value = 'Editar'; dialogUnid.value = true }

async function unidSave () {
  savingUnid.value = true
  try {
    if (unid.value.id) {
      await proxy.$axios.put('unidades/' + unid.value.id, unid.value)
      proxy.$alert.success('Unidad actualizada')
    } else {
      await proxy.$axios.post('unidades', unid.value)
      proxy.$alert.success('Unidad creada')
    }
    dialogUnid.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingUnid.value = false
  }
}

function unidDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar la unidad?').onOk(() => {
    proxy.$axios.delete('unidades/' + id)
      .then(() => { proxy.$alert.success('Unidad eliminada'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function unidQuickSave () {
  savingUnid.value = true
  try {
    const res = await proxy.$axios.post('unidades', { nombre: unidQNombre.value, abreviatura: unidQAbrev.value })
    loadFarmaciaData()
    prod.value.unidad_id = res.data.id
    unidQuick.value = false
    unidQNombre.value = ''
    unidQAbrev.value  = ''
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingUnid.value = false
  }
}

function onFilterTipo () {
  clearTimeout(timerTipo)
  timerTipo = setTimeout(() => { pageTipo.value = 1; loadTipos() }, 350)
}

function tipoNew ()     { tipoItem.value = { nombre: '', color: 'primary', tipo_producto_padre_id: filterTipoPadre.value }; tipoAction.value = 'Nuevo'; dialogTipo.value = true }
function tipoEdit (row) { tipoItem.value = { ...row, tipo_producto_padre_id: row.tipo_producto_padre_id ?? null }; tipoAction.value = 'Editar'; dialogTipo.value = true }

async function tipoSave () {
  savingTipo.value = true
  try {
    if (tipoItem.value.id) {
      await proxy.$axios.put('tipo-productos/' + tipoItem.value.id, tipoItem.value)
      proxy.$alert.success('Tipo de producto actualizado')
    } else {
      await proxy.$axios.post('tipo-productos', tipoItem.value)
      proxy.$alert.success('Tipo de producto creado')
    }
    dialogTipo.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingTipo.value = false
  }
}

function tipoDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el tipo de producto?').onOk(() => {
    proxy.$axios.delete('tipo-productos/' + id)
      .then(() => { proxy.$alert.success('Tipo de producto eliminado'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function tipoQuickSave () {
  savingTipo.value = true
  try {
    const res = await proxy.$axios.post('tipo-productos', {
      nombre: tipoQNombre.value,
      color: tipoQColor.value,
      tipo_producto_padre_id: tipoQPadre.value,
    })
    loadFarmaciaData()
    prod.value.tipo_producto_id = res.data.id
    tipoQuick.value = false
    tipoQNombre.value = ''
    tipoQColor.value = 'primary'
    tipoQPadre.value = null
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingTipo.value = false
  }
}

function onFilterPadre () {
  clearTimeout(timerPadre)
  timerPadre = setTimeout(() => { pagePadre.value = 1; loadFarmaciaData() }, 350)
}

function padreNew () {
  padreItem.value = { nombre: '', color: 'primary', icono: 'category', es_laboratorio: false, orden: null }
  padreAction.value = 'Nuevo'
  dialogPadre.value = true
}

function padreEdit (row) {
  padreItem.value = { ...row }
  padreAction.value = 'Editar'
  dialogPadre.value = true
}

function padreVerTipos (row) {
  filterTipoPadre.value = row.id
  filterTipo.value = ''
  pageTipo.value = 1
  tab.value = 'tipos'
  loadFarmaciaData()
}

async function padreSave () {
  savingPadre.value = true
  try {
    const cuerpo = { ...padreItem.value }
    if (cuerpo.orden === null || cuerpo.orden === '') delete cuerpo.orden
    if (cuerpo.id) {
      await proxy.$axios.put('tipo-producto-padres/' + cuerpo.id, cuerpo)
      proxy.$alert.success('Tipo de producto padre actualizado')
    } else {
      await proxy.$axios.post('tipo-producto-padres', cuerpo)
      proxy.$alert.success('Tipo de producto padre creado')
    }
    dialogPadre.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingPadre.value = false
  }
}

function padreDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el tipo de producto padre?').onOk(() => {
    proxy.$axios.delete('tipo-producto-padres/' + id)
      .then(() => { proxy.$alert.success('Tipo de producto padre eliminado'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

// ── Exportar ───────────────────────────────────────────────────
function exportParams (recurso) {
  if (recurso === 'productos') {
    return { tipo_producto_id: filterTipoProducto.value, q: filterProd.value }
  }
  if (recurso === 'fabricantes') {
    return { q: filterFab.value }
  }
  if (recurso === 'unidades') {
    return { q: filterUnid.value }
  }
  return {}
}

async function exportPdf (recurso) {
  exportingPdf.value = true
  try {
    const params = exportParams(recurso)
    const res = await proxy.$axios.get(recurso + '/export-pdf', { params, responseType: 'blob' })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch (e) {
    proxy.$alert.error('Error al generar PDF')
  } finally {
    exportingPdf.value = false
  }
}

async function exportExcel (recurso) {
  exportingExcel.value = true
  try {
    const params = exportParams(recurso)
    const res = await proxy.$axios.get(recurso + '/export-excel', { params, responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = recurso + '_' + new Date().toISOString().slice(0, 10) + '.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    proxy.$alert.error('Error al generar Excel')
  } finally {
    exportingExcel.value = false
  }
}
</script>

<style scoped>
/* Contenedor de la tabla: el spinner se superpone en vez de reemplazar las filas */
.tabla-wrap {
  position: relative;
}

/* Altura fija: la tabla ya no crece ni se encoge al cargar/filtrar/paginar */
.tabla-fija {
  height: calc(100vh - 330px);
  min-height: 260px;
}

.tabla-fija-sm {
  height: 46vh;
  min-height: 220px;
}

/* Cabecera fija al hacer scroll dentro de la tabla */
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
