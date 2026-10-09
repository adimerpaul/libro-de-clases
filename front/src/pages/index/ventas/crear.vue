<template>
  <q-page class="q-pa-sm ventas-compactas">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canCrear"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para crear ventas</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <div class="row items-center q-mb-sm">
        <q-btn v-if="canVerVentas" flat dense round icon="arrow_back" color="grey-7" class="q-mr-sm" :to="rutaVentas">
          <q-tooltip>{{ soloFarmacia ? 'Volver a ventas de farmacia' : 'Volver a ventas' }}</q-tooltip>
        </q-btn>
        <div>
          <div class="text-h6 text-weight-bold">
            {{ soloFarmacia ? 'Nueva venta de farmacia' : 'Nueva venta' }}
          </div>
          <div class="text-caption text-grey-6">
            {{ soloFarmacia
              ? 'Registro de una venta compuesta solo por productos de farmacia'
              : 'Registro de una nueva venta o proforma de pago' }}
          </div>
        </div>
      </div>
      <q-separator class="q-mb-sm" />

      <q-banner v-if="ultimaVenta" dense rounded class="bg-green-1 text-green-10 q-mb-sm">
        Venta #{{ ultimaVenta.id }} guardada{{ ultimaVenta.estado === 'PENDIENTE' ? ' pendiente de cobro' : '' }}.
        Puede volver a imprimir su comprobante sin registrar otra venta.
        <template v-slot:action>
          <q-btn flat dense no-caps icon="print" label="Imprimir última venta" @click="imprimirUltimaVenta" />
        </template>
      </q-banner>

      <q-banner v-if="cajaCerrada" dense rounded class="bg-orange-1 text-orange-10 q-mb-sm">
        <template v-slot:avatar><q-icon name="lock" color="orange-9" /></template>
        Su caja de hoy ya fue cerrada: no puede registrar más ventas hasta mañana.
        <template v-slot:action>
          <q-btn v-if="canVerVentas" flat dense no-caps color="orange-10" label="Ir a ventas" :to="rutaVentas" />
        </template>
      </q-banner>

      <div class="row q-col-gutter-md">

        <!-- Productos -->
        <div class="col-12 col-md-7">
          <div class="row items-center q-gutter-sm q-mb-sm">
            <span class="text-subtitle2 text-weight-bold text-grey-8">
              {{ soloFarmacia ? 'Productos de farmacia' : 'Productos' }}
            </span>
            <q-space />
            <!-- En modo farmacia el tipo está fijo: no se ofrece el selector. -->
            <q-select v-if="!soloFarmacia" v-model="filtroTipo" dense outlined clearable use-input
                      input-debounce="0" label="Tipo" style="width:180px"
                      :options="tiposProductoFiltrados" option-value="id" option-label="nombre"
                      emit-value map-options @filter="filtrarTiposProducto"
                      @update:model-value="onBuscarProducto">
              <template #prepend>
                <q-icon :name="iconoTipo(tipoProductoSeleccionado?.nombre)" color="primary" />
              </template>
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-icon :name="iconoTipo(scope.opt.nombre)" :style="{ color: scope.opt.color || '#607d8b' }" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                </q-item>
              </template>
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey-6">No se encontraron tipos</q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-btn dense outline no-caps color="primary" icon="refresh" label="Actualizar"
                   :loading="loadingProductos" @click="actualizarProductos">
              <q-tooltip>Actualizar productos y cantidades disponibles</q-tooltip>
            </q-btn>
            <q-input v-model="buscarProducto" placeholder="Buscar producto…" dense outlined rounded clearable
                     bg-color="white" style="width:220px" @update:model-value="onBuscarProducto">
              <template v-slot:prepend><q-icon name="search" /></template>
            </q-input>
          </div>

          <div class="tabla-wrap">
            <q-markup-table dense flat bordered separator="horizontal" class="tabla-fija full-width rounded-borders tabla-compacta">
              <thead>
                <tr class="bg-grey-1 text-grey-7 text-uppercase">
                  <th style="width:44px"></th>
                  <th class="text-left">Código</th>
                  <th class="text-left">Producto</th>
                  <th class="text-left">Tipo</th>
                  <th class="text-right">Cantidad</th>
                  <th class="text-right">Precio</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!productos.length && !loadingProductos">
                  <td colspan="6" class="text-center text-grey-5 q-pa-md">Sin productos</td>
                </tr>
                <tr v-for="p in productos" :key="p.id">
                  <td class="text-center">
                    <q-btn dense round unelevated size="sm" color="primary" icon="add"
                           :disable="esProductoFarmacia(p) && cantidadDisponibleProducto(p) <= 0"
                           @click="agregarProducto(p)">
                      <q-tooltip>
                        {{ !esProductoFarmacia(p) || cantidadDisponibleProducto(p) > 0 ? 'Agregar a la venta' : 'Sin lote disponible' }}
                      </q-tooltip>
                    </q-btn>
                  </td>
                  <td>{{ p.codigo || '—' }}</td>
                  <td>{{ p.nombre }}</td>
                  <td>
                    <q-badge v-if="p.tipo_producto" rounded :style="{ background: p.tipo_producto.color || '#607d8b' }">
                      {{ p.tipo_producto.nombre }}
                    </q-badge>
                    <span v-else>—</span>
                  </td>
                  <td class="text-right">
                    <q-badge v-if="esProductoFarmacia(p)"
                             :color="cantidadDisponibleProducto(p) > 0 ? 'green-1' : 'red-1'"
                             :text-color="cantidadDisponibleProducto(p) > 0 ? 'green-9' : 'negative'">
                      {{ cantidadDisponibleProducto(p).toFixed(2) }}
                    </q-badge>
                    <q-badge v-else color="blue-grey-1" text-color="blue-grey-8">
                      LIBRE
                    </q-badge>
                  </td>
                  <td class="text-right">{{ money(p.precio) }}</td>
                </tr>
              </tbody>
            </q-markup-table>
            <q-inner-loading :showing="loadingProductos" color="primary" />
          </div>

          <div class="row items-center justify-between q-mt-xs q-px-xs">
            <div class="text-caption text-grey-6">
              Total: {{ totalProductos }} | Página {{ pageProductos }} de {{ pagesProductos }}
            </div>
            <q-pagination v-model="pageProductos" :max="pagesProductos" :max-pages="6"
                          boundary-links direction-links size="sm" @update:model-value="loadProductos" />
          </div>
        </div>

        <!-- Carrito / datos de la venta -->
        <div class="col-12 col-md-5">
          <q-card flat bordered class="q-pa-sm rounded-borders">
            <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-sm">
              <q-icon name="shopping_cart" size="18px" class="q-mr-xs" />Detalle de la venta
            </div>

            <div>
              <q-markup-table dense flat bordered separator="horizontal" class="full-width q-mb-sm rounded-borders tabla-compacta tabla-carrito">
                <thead>
                  <tr class="bg-grey-1 text-grey-7 text-uppercase">
                    <th style="width:30px"></th>
                    <th class="text-left">Producto</th>
                    <th class="text-right" style="width:66px">Cant.</th>
                    <th class="text-right" style="width:84px">Precio</th>
                    <th class="text-right" style="width:84px">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!nueva.detalles.length">
                    <td colspan="5" class="text-center text-grey-5 q-pa-md">Agregue productos desde la izquierda</td>
                  </tr>
                  <tr v-for="(linea, idx) in nueva.detalles" :key="linea.uid">
                    <td class="text-center">
                      <q-btn flat dense round icon="delete" color="negative" size="sm" @click="quitarLinea(idx)" />
                    </td>
                    <td class="celda-producto">
                      <div class="producto-nombre">
                        {{ linea.nombre }}
                        <q-tooltip>{{ linea.nombre }}</q-tooltip>
                      </div>
                      <div v-if="linea.requiere_lote" class="producto-info text-grey-7">
                        Lote: <b>{{ linea.lote }}</b>
                        <span class="q-ml-xs">Vence: {{ linea.fecha_vencimiento || 'SIN FECHA' }}</span>
                        <span class="q-ml-xs text-positive">Disp: {{ Number(linea.cantidad_disponible).toFixed(2) }}</span>
                      </div>
                      <div v-else class="producto-info text-blue-grey-7">Sin control de lote</div>
                    </td>
                    <td class="celda-num"><q-input v-model.number="linea.cantidad" dense outlined type="number" step="1" min="0"
                                 input-class="text-right"
                                 :max="linea.requiere_lote ? linea.cantidad_disponible : undefined"
                                 @update:model-value="recalcularLinea(linea)" /></td>
                    <!-- Precio y total editables: cambiar el precio recalcula el
                         total, y escribir el total y pulsar Enter reparte el
                         monto en el precio unitario. Si el precio deja de ser el
                         de lista, la línea queda marcada con asterisco. -->
                    <td class="celda-num" :class="{ 'celda-num--mod': precioModificado(linea) }"
                        :title="precioModificado(linea) ? 'Precio modificado · lista ' + money(linea.precio_original) + ' Bs' : ''">
                      <q-input v-model.number="linea.precio" dense outlined type="number" step="0.01" min="0"
                               :input-class="precioModificado(linea) ? 'text-right text-orange-9 text-weight-bold' : 'text-right'"
                               @update:model-value="recalcularLinea(linea)"
                               @keyup.enter="recalcularLinea(linea)" />
                    </td>
                    <td class="celda-num" :class="{ 'celda-num--mod': precioModificado(linea) }"
                        :title="precioModificado(linea) ? 'Debería ser ' + money(totalOriginalLinea(linea)) + ' Bs' : ''">
                      <q-input v-model.number="linea.total" dense outlined type="number" step="0.01" min="0"
                               :input-class="precioModificado(linea) ? 'text-right text-orange-9 text-weight-bold' : 'text-right'"
                               @keyup.enter="recalcularDesdeTotal(linea)"
                               @blur="recalcularDesdeTotal(linea)" />
                    </td>
                  </tr>
                </tbody>
              </q-markup-table>

              <div class="row items-center justify-between q-gutter-sm">
                <div>
                  <div class="text-h6">Total: <span class="text-primary text-weight-bold">{{ money(totalNueva) }} Bs</span></div>
                  <div v-if="hayPreciosModificados" class="text-caption text-orange-9 text-weight-bold">
                    * Precios modificados · debería ser {{ money(totalOriginalNueva) }} Bs
                  </div>
                </div>
                <div class="q-gutter-sm">
                  <q-btn rounded unelevated color="primary" label="Registrar venta" icon-right="save" no-caps
                         :loading="registrando" :disable="!nueva.detalles.length || cajaCerrada"
                         @click="abrirDatosVenta()">
                    <q-tooltip v-if="cajaCerrada">Su caja de hoy ya fue cerrada</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </div>
          </q-card>
        </div>
      </div>

    </template>

    <!-- DIALOG DATOS DE LA VENTA -->
    <q-dialog v-model="dialogDatos" persistent>
      <q-card style="width:min(96vw,560px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon :name="cobrarLuego ? 'schedule' : 'point_of_sale'" size="20px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle2 text-weight-bold">
              {{ cobrarLuego ? 'Venta para cobrar luego' : 'Registrar venta' }}
            </div>
            <div class="text-caption">{{ nueva.detalles.length }} producto(s) · Total {{ money(totalNueva) }} Bs</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogDatos = false" />
        </q-card-section>

        <q-card-section class="q-pa-sm" style="max-height:70vh;overflow-y:auto">
          <q-form @submit.prevent="confirmarVenta">
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-12">
                <q-select v-model="nueva.paciente_id" label="Paciente" dense outlined clearable use-input
                          input-debounce="350" :options="opcionesPaciente"
                          option-value="id" option-label="nombre_completo" emit-value map-options
                          @filter="filtrarPacientes">
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                  </template>
                  <!-- Cada opción muestra CI y fecha de nacimiento del paciente -->
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps" dense>
                      <q-item-section>
                        <q-item-label>{{ scope.opt.nombre_completo }}</q-item-label>
                        <q-item-label caption>
                          CI: {{ scope.opt.ci || '—' }} ·
                          F. Nac: {{ fechaCorta(scope.opt.fecha_nacimiento) }} ({{ edadTexto(scope.opt.fecha_nacimiento) }})
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                  <template v-slot:after>
                    <q-btn flat round dense icon="add" color="primary" @click="pacQuick = true">
                      <q-tooltip>Nuevo paciente</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>

              <!-- Datos del paciente elegido -->
              <template v-if="pacienteElegido">
                <div class="col-4">
                  <q-input :model-value="pacienteElegido.ci || '—'" label="CI" dense outlined readonly />
                </div>
                <div class="col-4">
                  <q-input :model-value="fechaCorta(pacienteElegido.fecha_nacimiento)"
                           label="Fecha de nacimiento" dense outlined readonly />
                </div>
                <div class="col-4">
                  <q-input :model-value="edadTexto(pacienteElegido.fecha_nacimiento)"
                           label="Edad" dense outlined readonly />
                </div>

                <!-- Internaciones sin alta del paciente -->
                <div class="col-12">
                  <div class="internaciones-box">
                    <div class="row items-center no-wrap q-mb-xs">
                      <q-icon name="local_hotel" size="16px" color="teal-8" class="q-mr-xs" />
                      <span class="text-caption text-weight-bold text-grey-8">Internaciones sin alta</span>
                      <q-spinner v-if="loadingInternaciones" color="primary" size="14px" class="q-ml-sm" />
                      <q-space />
                      <q-btn v-if="canCrearInternacion && !internacionesAbiertas.length && !loadingInternaciones"
                             dense unelevated no-caps rounded size="sm" color="teal-8"
                             icon="add" label="Crear internación" @click="abrirIntQuick" />
                    </div>

                    <div v-if="!loadingInternaciones && !internacionesAbiertas.length"
                         class="text-caption text-grey-6">
                      El paciente no tiene ninguna internación abierta.
                    </div>

                    <q-list v-else dense separator class="rounded-borders bg-white">
                      <q-item v-for="int in internacionesAbiertas" :key="int.id" dense>
                        <q-item-section avatar style="min-width:28px">
                          <q-icon name="local_hotel" size="16px" color="teal-8" />
                        </q-item-section>
                        <q-item-section>
                          <q-item-label class="text-caption text-weight-bold">
                            Ingreso: {{ fechaCorta(int.fecha_ingreso) }}
                            <span v-if="int.tipo_paciente"> · {{ int.tipo_paciente }}</span>
                          </q-item-label>
                          <q-item-label caption>
                            Sala: {{ int.sala || '—' }} · H.C.: {{ int.codigo_hc || '—' }}
                            · {{ int.seguro?.nombre || 'PARTICULAR' }}
                            <span v-if="int.dias_internado"> · {{ int.dias_internado }} día(s)</span>
                          </q-item-label>
                        </q-item-section>
                        <q-item-section side>
                          <q-badge rounded color="orange-1" text-color="orange-9" class="text-weight-bold">
                            SIN ALTA
                          </q-badge>
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </div>
                </div>
              </template>
              <div class="col-12" v-if="!nueva.paciente_id">
                <q-input v-model="nueva.cliente" label="Cliente (si no es paciente)" dense outlined v-uppercase />
              </div>
              <div class="col-12">
                <q-select v-model="nueva.doctor_id" label="Doctor" dense outlined clearable use-input
                          input-debounce="350" :options="opcionesDoctor"
                          option-value="id" emit-value map-options
                          :option-label="d => d.nombre + (d.especialidades?.length ? ' — ' + d.especialidades.map(e => e.nombre).join(', ') : '')"
                          @filter="filtrarDoctores">
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                  </template>
                  <template v-slot:after>
                    <q-btn flat round dense icon="add" color="primary" @click="abrirDocQuick">
                      <q-tooltip>Nuevo doctor</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
              <div class="col-12">
                <q-select v-model="nueva.seguro_id" label="Seguro / Institución" dense outlined clearable
                          use-input input-debounce="0" :options="opcionesSeguro"
                          option-value="id" option-label="nombre"
                          emit-value map-options hint="Vacío = PARTICULAR"
                          @filter="filtrarSeguros">
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                  </template>
                </q-select>
              </div>
            </div>

            <q-separator class="q-mb-sm" />

            <!-- Cobrar luego: la venta queda PENDIENTE, sin registrar el pago -->
            <q-checkbox v-model="cobrarLuego" dense color="orange-8" class="q-mb-xs">
              <span class="text-weight-medium">Cobrar luego</span>
            </q-checkbox>
            <q-banner v-if="cobrarLuego" dense class="bg-orange-1 text-orange-9 q-mb-sm rounded-borders">
              <template v-slot:avatar><q-icon name="schedule" color="orange-8" /></template>
              La venta se guarda <b>pendiente de cobro</b> por {{ money(totalNueva) }} Bs.
              Se cobra después desde la lista de ventas, con la opción <b>Cobrar venta</b>.
            </q-banner>

            <div class="row q-col-gutter-sm items-center q-mb-sm">
              <div class="col-12">
                <q-input :model-value="money(totalNueva)" label="Total Bs" dense outlined readonly
                         input-class="text-right text-weight-bold" />
              </div>
              <div class="col-12" v-if="!cobrarLuego">
                <PagoVenta v-model="nueva.pago" :total="totalNueva" />
              </div>
              <div class="col-12">
                <q-input v-model="nueva.comentario" label="Comentario" dense outlined type="textarea" rows="1" />
              </div>
            </div>

            <div class="row items-center justify-between q-gutter-sm">
              <div>
                <div class="text-subtitle1">Total: <span class="text-primary text-weight-bold">{{ money(totalNueva) }} Bs</span></div>
                <div v-if="hayPreciosModificados" class="text-caption text-orange-9 text-weight-bold">
                  * Precios modificados · debería ser {{ money(totalOriginalNueva) }} Bs
                </div>
              </div>
              <div class="q-gutter-sm">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogDatos = false" />
                <q-btn rounded unelevated
                       :color="cobrarLuego ? 'orange-8' : 'primary'"
                       :label="cobrarLuego ? 'Guardar para cobrar luego' : 'Registrar venta'"
                       :icon-right="cobrarLuego ? 'schedule' : 'save'"
                       no-caps type="submit" :loading="registrando" />
              </div>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG SELECCIONAR LOTE -->
    <q-dialog v-model="dialogLotes">
      <q-card style="width:min(96vw,650px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="inventory_2" size="20px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle2 text-weight-bold">Seleccionar lote</div>
            <div class="text-caption">{{ productoParaLote?.nombre }}</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-sm">
          <q-list bordered separator>
            <q-item v-for="lote in lotesDisponibles" :key="lote.compra_detalle_id"
                    clickable v-ripple @click="seleccionarLote(lote)">
              <q-item-section avatar>
                <q-icon name="inventory" color="primary" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">Lote {{ lote.lote }}</q-item-label>
                <q-item-label caption>
                  Compra #{{ lote.compra_id }} · Vencimiento: {{ lote.fecha_vencimiento || 'SIN FECHA' }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-chip dense color="green-1" text-color="green-9">
                  Disponible: {{ Number(lote.cantidad_disponible).toFixed(2) }}
                </q-chip>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG PACIENTE RÁPIDO -->
    <q-dialog v-model="pacQuick" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo paciente rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="pacQuickSave">
            <q-input v-model="pacQ.nombre_completo" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-6">
                <q-input v-model="pacQ.ci" label="CI" dense outlined v-uppercase />
              </div>
              <div class="col-6">
                <q-select v-model="pacQ.sexo" label="Sexo" dense outlined clearable
                          :options="[{label:'Masculino',value:'M'},{label:'Femenino',value:'F'}]"
                          emit-value map-options />
              </div>
              <div class="col-6">
                <q-input v-model="pacQ.fecha_nacimiento" label="Fecha de nacimiento" dense outlined
                         type="date" clearable :max="hoyFecha" />
              </div>
              <div class="col-6">
                <q-input v-model="pacQ.telefono" label="Teléfono" dense outlined />
              </div>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="pacQuick = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingQuick" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG INTERNACIÓN RÁPIDA -->
    <q-dialog v-model="intQuick" persistent>
      <q-card style="width:min(96vw,440px)">
        <q-card-section class="bg-teal-8 text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nueva internación</span>
          <div class="text-caption">{{ pacienteElegido?.nombre_completo }}</div>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="intQuickSave">
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-6">
                <q-input v-model="intQ.fecha_ingreso" label="Fecha de ingreso *" dense outlined type="date"
                         :rules="[v => !!v || 'Requerido']" />
              </div>
              <div class="col-6">
                <q-input v-model="intQ.tipo_paciente" label="Tipo de paciente" dense outlined v-uppercase />
              </div>
              <div class="col-12">
                <q-select v-model="intQ.seguro_id" label="Seguro / Institución" dense outlined clearable
                          use-input input-debounce="0" :options="opcionesSeguro"
                          option-value="id" option-label="nombre"
                          emit-value map-options
                          hint="Sin seguro se registra como PARTICULAR"
                          @filter="filtrarSeguros">
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                  </template>
                </q-select>
              </div>
              <div class="col-6">
                <q-input v-model="intQ.sala" label="Sala" dense outlined v-uppercase />
              </div>
              <div class="col-6">
                <q-input v-model="intQ.codigo_hc" label="Código H.C." dense outlined v-uppercase />
              </div>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="intQuick = false" />
              <q-btn color="teal-8" label="Crear internación" type="submit" no-caps :loading="savingQuick" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG DOCTOR RÁPIDO -->
    <q-dialog v-model="docQuick" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo doctor rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="docQuickSave">
            <q-input v-model="docQ.nombre" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-select v-model="docQ.especialidad_ids" label="Especialidades" dense outlined class="q-mb-md"
                      multiple use-chips :options="especialidades"
                      option-value="id" option-label="nombre" emit-value map-options />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="docQuick = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingQuick" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance, nextTick } from 'vue'
import { imprimirVenta } from '../../../addons/ventaPrint'
import { pagoVacio, validarPago, payloadPago } from '../../../addons/pagoVenta'
import PagoVenta from '../../../components/PagoVenta.vue'

const { proxy } = getCurrentInstance()
const ultimaVenta = ref(null)

function imprimirUltimaVenta () {
  if (!ultimaVenta.value) return
  try {
    imprimirVenta(ultimaVenta.value)
  } catch {
    proxy.$alert.warning('La venta está guardada. No se pudo abrir la impresión; use Imprimir última venta para reintentar.')
  }
}

// soloFarmacia: la misma pantalla acotada a vender únicamente productos de farmacia.
const props = defineProps({
  soloFarmacia: { type: Boolean, default: false },
})

const soloFarmacia = computed(() => props.soloFarmacia)
const rutaVentas   = computed(() => props.soloFarmacia ? '/ventas-farmacia' : '/ventas')

// ── Permisos ───────────────────────────────────────────────────
const canCrear = computed(() => proxy.$store.hasPermission('Crear Ventas'))
// Quien solo vende (sin 'Ver Ventas') no ve el historial: se ocultan los enlaces a él.
const canVerVentas = computed(() => proxy.$store.hasPermission('Ver Ventas'))

function money (v) { return Number(v || 0).toFixed(2) }

function fechaCorta (fecha) {
  return fecha ? proxy.$filters.date(String(fecha).slice(0, 10)) : '—'
}

// Años cumplidos según la fecha de nacimiento del paciente.
function edadTexto (fecha) {
  if (!fecha) return '—'
  const nac = new Date(String(fecha).slice(0, 10) + 'T00:00:00')
  if (isNaN(nac)) return '—'
  const hoy = new Date()
  let edad = hoy.getFullYear() - nac.getFullYear()
  const meses = hoy.getMonth() - nac.getMonth()
  if (meses < 0 || (meses === 0 && hoy.getDate() < nac.getDate())) edad--
  return edad >= 0 ? edad + ' años' : '—'
}

// ── Pacientes (select con filtro) ──────────────────────────────
const opcionesPaciente = ref([])
const pacienteElegido = computed(() =>
  opcionesPaciente.value.find(p => p.id === nueva.value.paciente_id) || null
)

// ── Internaciones del paciente elegido ─────────────────────────
const canCrearInternacion = computed(() => proxy.$store.hasPermission('Crear Internaciones'))
const internaciones        = ref([])
const loadingInternaciones = ref(false)
const internacionesAbiertas = computed(() => internaciones.value.filter(i => !i.fecha_alta))

async function cargarInternaciones (pacienteId) {
  internaciones.value = []
  if (!pacienteId) return
  loadingInternaciones.value = true
  try {
    const res = await proxy.$axios.get('pacientes/' + pacienteId + '/internaciones', {
      params: { abiertas: 1 },
    })
    internaciones.value = res.data || []
  } catch (e) {
    internaciones.value = []
  } finally {
    loadingInternaciones.value = false
  }
}

async function filtrarPacientes (val, update) {
  try {
    const res = await proxy.$axios.get('pacientes', { params: { q: val, per_page: 20 } })
    update(() => { opcionesPaciente.value = res.data?.data || [] })
  } catch (e) {
    update(() => { opcionesPaciente.value = [] })
  }
}

// ── Doctores (select con filtro) ───────────────────────────────
const opcionesDoctor = ref([])
async function filtrarDoctores (val, update) {
  try {
    const res = await proxy.$axios.get('doctores', { params: { q: val, estado: 'ACTIVO', per_page: 20 } })
    update(() => { opcionesDoctor.value = res.data?.data || [] })
  } catch (e) {
    update(() => { opcionesDoctor.value = [] })
  }
}

// ── Seguros ────────────────────────────────────────────────────
const allSeguros = ref([])
async function loadSeguros () {
  try {
    const res = await proxy.$axios.get('seguros')
    allSeguros.value = res.data || []
    opcionesSeguro.value = allSeguros.value
  } catch (e) { /* silent */ }
}
const opcionesSeguro = ref([])
function filtrarSeguros (val, update) {
  update(() => {
    const q = (val || '').toLowerCase()
    opcionesSeguro.value = q
      ? allSeguros.value.filter(s => (s.nombre || '').toLowerCase().includes(q))
      : allSeguros.value
  })
}

// ── Especialidades (para doctor rápido) ────────────────────────
const especialidades = ref([])
async function loadEspecialidades () {
  try {
    const res = await proxy.$axios.get('especialidades')
    especialidades.value = res.data || []
  } catch (e) { /* silent */ }
}

// ── Productos ──────────────────────────────────────────────────
const productos        = ref([])
const loadingProductos = ref(false)
const buscarProducto   = ref('')
const filtroTipo       = ref(null)
const tiposProducto    = ref([])
const tiposProductoFiltrados = ref([])
const pageProductos    = ref(1)
const totalProductos   = ref(0)
const perProductos     = 10

const pagesProductos = computed(() => Math.max(1, Math.ceil(totalProductos.value / perProductos)))
const tipoProductoSeleccionado = computed(() => tiposProducto.value.find(tipo => tipo.id === filtroTipo.value))

const iconosTipoProducto = {
  AMBULANCIA: 'local_shipping',
  ECOGRAFIA: 'graphic_eq',
  'ESTUDIOS DIAGNOSTICOS': 'monitor_heart',
  FARMACIA: 'medication',
  INTERNACION: 'hotel',
  NEONATOLOGIA: 'child_care',
  'OXIGENO TERAPIA': 'air',
  'RAYOS X CONTRASTADOS': 'invert_colors',
  'RAYOS X SIN INFORME': 'camera_alt',
  'SALAS DE PROCEDIMIENTOS': 'meeting_room',
  'SERVICIO DE ENFERMERIA': 'vaccines',
  'SERVICIO MEDICO': 'medical_services',
  'TOMOGRAFIA EN C.D.': 'scanner',
  'U.T.I. ADULTOS': 'emergency',
  'USO DE QUIROFANO': 'health_and_safety',
  HEMATOLOGIA: 'bloodtype',
  COAGULOGRAMA: 'opacity',
  'BIOQUIMICA CLINICA': 'science',
  'IONOGRAMA / ELECTROLITOS': 'bolt',
  'CINETICA DE HIERRO': 'hardware',
  'PERFIL CARDIACO': 'favorite',
  CITOQUIMICOS: 'colorize',
  UROLOGIA: 'water_drop',
  COPROLOGIA: 'compost',
  SECRECIONES: 'bubble_chart',
  SEROLOGIAS: 'analytics',
  GASOMETRIAS: 'speed',
  'PERFIL TIROIDEO': 'device_thermostat',
  'FERTILIDAD - INMUNOLOGIA': 'favorite_border',
  'HORMONAS - INMUNOLOGIA': 'hub',
  'AUTOINMUNES - INMUNOLOGIA': 'security',
  'MARCADORES TUMORALES - INMUNOLOGIA': 'crisis_alert',
  'INFECCIOSOS (ELISA) - INMUNOLOGIA': 'coronavirus',
  'PESQUIZA NEONATAL': 'filter_vintage',
  VITAMINAS: 'wb_sunny',
  VARIOS: 'more_horiz',
  BACTERIOLOGIA: 'biotech',
  'DROGAS DE ABUSO': 'smoke_free',
  CITOLOGIA: 'spa',
  'BIOLOGIA MOLECULAR': 'account_tree',
  GENETICA: 'fingerprint',
}

function iconoTipo (nombre) {
  const normalizado = String(nombre || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toUpperCase()
    .trim()

  return iconosTipoProducto[normalizado] || 'category'
}

function cantidadDisponibleProducto (producto) {
  return Math.max(
    0,
    Number(producto.cantidad_con_lote || 0) -
      Number(producto.cantidad_vendida_lote || 0) -
      Number(producto.cantidad_baja_lote || 0),
  )
}

function esProductoFarmacia (producto) {
  return String(producto.tipo_producto?.nombre || '').toUpperCase() === 'FARMACIA'
}

let timerProducto = null
function onBuscarProducto () {
  clearTimeout(timerProducto)
  timerProducto = setTimeout(() => { pageProductos.value = 1; loadProductos() }, 350)
}

async function loadProductos () {
  loadingProductos.value = true
  try {
    const res = await proxy.$axios.get('productos', {
      params: {
        q: buscarProducto.value,
        tipo_producto_id: filtroTipo.value,
        // La venta de farmacia solo puede armarse con productos de ese tipo.
        tipo: props.soloFarmacia ? 'FARMACIA' : undefined,
        page: pageProductos.value,
        per_page: perProductos,
      },
    })
    productos.value = res.data?.data || []
    totalProductos.value = res.data?.total || 0
  } catch (e) {
    proxy.$alert.error('Error al cargar productos')
  } finally {
    loadingProductos.value = false
  }
}

async function loadTiposProducto () {
  try {
    const res = await proxy.$axios.get('tipo-productos')
    tiposProducto.value = res.data || []
    tiposProductoFiltrados.value = tiposProducto.value
  } catch (e) { /* silent */ }
}

function filtrarTiposProducto (valor, actualizar) {
  actualizar(() => {
    const texto = String(valor || '').trim().toLocaleUpperCase('es')
    tiposProductoFiltrados.value = texto
      ? tiposProducto.value.filter(tipo => String(tipo.nombre || '').toLocaleUpperCase('es').includes(texto))
      : tiposProducto.value
  })
}

async function actualizarProductos () {
  pageProductos.value = 1
  await Promise.all([loadProductos(), loadTiposProducto()])
}

// ── Nueva venta ────────────────────────────────────────────────
const registrando = ref(false)
const dialogLotes = ref(false)
const productoParaLote = ref(null)
const lotesDisponibles = ref([])
let lineaUid = 0

function nuevaVentaVacia () {
  return {
    paciente_id: null,
    doctor_id: null,
    seguro_id: null,
    cliente: '',
    comentario: '',
    pago: pagoVacio(),
    detalles: [],
  }
}
const nueva = ref(nuevaVentaVacia())

// Al cambiar de paciente se recargan sus internaciones sin alta.
watch(() => nueva.value.paciente_id, (id) => cargarInternaciones(id))

const totalNueva  = computed(() => nueva.value.detalles.reduce((acc, l) => acc + (Number(l.total) || 0), 0))

async function agregarProducto (p) {
  if (!esProductoFarmacia(p)) {
    agregarProductoSinLote(p)
    return
  }
  try {
    const res = await proxy.$axios.get('productos/' + p.id + '/lotes-disponibles')
    const lotes = res.data || []
    if (!lotes.length) {
      proxy.$alert.error('No se puede vender ' + p.nombre + ': no tiene un lote con stock disponible')
      return
    }
    if (lotes.length === 1) {
      agregarProductoConLote(p, lotes[0])
      return
    }
    productoParaLote.value = p
    lotesDisponibles.value = lotes
    dialogLotes.value = true
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al consultar los lotes disponibles')
  }
}

function agregarProductoSinLote (p) {
  const existente = nueva.value.detalles.find(l => (
    l.producto_id === p.id && !l.requiere_lote
  ))
  if (existente) {
    existente.cantidad = (Number(existente.cantidad) || 0) + 1
    recalcularLinea(existente)
    return
  }
  const linea = {
    uid: ++lineaUid,
    producto_id: p.id,
    nombre: p.nombre,
    requiere_lote: false,
    cantidad: 1,
    precio: Number(p.precio) || 0,
    // Referencia de cuánto debería costar: no cambia aunque se edite el precio.
    precio_original: Number(p.precio) || 0,
    total: 0,
  }
  recalcularLinea(linea)
  nueva.value.detalles.push(linea)
}

function seleccionarLote (lote) {
  agregarProductoConLote(productoParaLote.value, lote)
  dialogLotes.value = false
}

function agregarProductoConLote (p, lote) {
  const existente = nueva.value.detalles.find(l => (
    l.producto_id === p.id && l.compra_detalle_id === lote.compra_detalle_id
  ))
  if (existente) {
    if (Number(existente.cantidad) >= Number(existente.cantidad_disponible)) {
      proxy.$alert.error('No hay más unidades disponibles en el lote ' + lote.lote)
      return
    }
    existente.cantidad = (Number(existente.cantidad) || 0) + 1
    recalcularLinea(existente)
    return
  }
  const linea = {
    uid: ++lineaUid,
    producto_id: p.id,
    requiere_lote: true,
    compra_detalle_id: lote.compra_detalle_id,
    nombre: p.nombre,
    lote: lote.lote,
    fecha_vencimiento: lote.fecha_vencimiento,
    cantidad_disponible: Number(lote.cantidad_disponible),
    cantidad: 1,
    precio: Number(p.precio) || 0,
    // Referencia de cuánto debería costar: no cambia aunque se edite el precio.
    precio_original: Number(p.precio) || 0,
    total: 0,
  }
  recalcularLinea(linea)
  nueva.value.detalles.push(linea)
}

function quitarLinea (idx) {
  nueva.value.detalles.splice(idx, 1)
}

function recalcularLinea (linea) {
  if (linea.requiere_lote && Number(linea.cantidad) > Number(linea.cantidad_disponible)) {
    linea.cantidad = Number(linea.cantidad_disponible)
    proxy.$alert.error('La cantidad supera el stock disponible del lote ' + linea.lote)
  }
  linea.total = Math.round(((Number(linea.cantidad) || 0) * (Number(linea.precio) || 0)) * 100) / 100
  // Referencia del último total calculado: sirve para saber si el cajero
  // realmente escribió otro monto o solo pasó por el campo.
  linea.total_calculado = linea.total
}

/**
 * Se escribe el monto final de la línea y con Enter se reparte en el precio
 * unitario. El precio manda: después de repartirlo se vuelve a multiplicar, así
 * que un monto que no es divisible exacto queda ajustado al centavo más cercano.
 */
function recalcularDesdeTotal (linea) {
  const total = Number(linea.total) || 0
  if (Math.round(total * 100) === Math.round((Number(linea.total_calculado) || 0) * 100)) return

  const cantidad = Number(linea.cantidad) || 0
  if (cantidad > 0) {
    linea.precio = Math.round((total / cantidad) * 100) / 100
  }
  recalcularLinea(linea)
}

/* El precio de lista con el que entró la línea es la referencia: si el cajero
   lo cambia, la línea va con asterisco y el backend guarda cuánto debería
   haber costado. */
function precioModificado (linea) {
  const original = Number(linea.precio_original)
  if (!Number.isFinite(original)) return false
  return Math.round(original * 100) !== Math.round((Number(linea.precio) || 0) * 100)
}

function totalOriginalLinea (linea) {
  const original = Number(linea.precio_original)
  const base = Number.isFinite(original) ? original : (Number(linea.precio) || 0)
  return Math.round(base * (Number(linea.cantidad) || 0) * 100) / 100
}

const hayPreciosModificados = computed(() => nueva.value.detalles.some(precioModificado))
const totalOriginalNueva = computed(() =>
  Math.round(nueva.value.detalles.reduce((suma, l) => suma + totalOriginalLinea(l), 0) * 100) / 100)

// ── Diálogo de datos de la venta ───────────────────────────────
const dialogDatos = ref(false)
// Marcado: la venta se guarda PENDIENTE y se cobra después desde /ventas.
const cobrarLuego = ref(false)

function detallesValidos () {
  if (!nueva.value.detalles.length) {
    proxy.$alert.error('Agregue al menos un producto a la venta')
    return false
  }
  const lineaSinLote = nueva.value.detalles.find(l => (
    l.requiere_lote && (!l.compra_detalle_id || !l.lote)
  ))
  if (lineaSinLote) {
    proxy.$alert.error('Seleccione un lote para ' + lineaSinLote.nombre)
    return false
  }
  const lineaSinStock = nueva.value.detalles.find(l => (
    Number(l.cantidad) <= 0
    || (l.requiere_lote && Number(l.cantidad) > Number(l.cantidad_disponible))
  ))
  if (lineaSinStock) {
    proxy.$alert.error(
      lineaSinStock.requiere_lote
        ? 'Cantidad no disponible para el lote ' + lineaSinStock.lote
        : 'La cantidad de ' + lineaSinStock.nombre + ' debe ser mayor a cero',
    )
    return false
  }
  return true
}

function abrirDatosVenta () {
  if (!detallesValidos()) return
  cobrarLuego.value = false
  dialogDatos.value = true
}

function confirmarVenta () {
  registrarVenta(cobrarLuego.value ? 'PENDIENTE' : 'ACTIVO')
}

async function registrarVenta (estado = 'ACTIVO') {
  if (!detallesValidos()) return
  const errorPago = estado === 'PENDIENTE' ? null : validarPago(nueva.value.pago, totalNueva.value)
  if (errorPago) {
    proxy.$alert.error(errorPago)
    return
  }
  registrando.value = true
  try {
    const payload = {
      paciente_id: nueva.value.paciente_id,
      doctor_id: nueva.value.doctor_id,
      seguro_id: nueva.value.seguro_id,
      cliente: nueva.value.paciente_id ? null : nueva.value.cliente,
      comentario: nueva.value.comentario,
      // Pendiente: el tipo de pago se elige al cobrarla.
      ...(estado === 'PENDIENTE' ? {} : payloadPago(nueva.value.pago, totalNueva.value)),
      estado,
      detalles: nueva.value.detalles.map(l => ({
        producto_id: l.producto_id || null,
        compra_detalle_id: l.compra_detalle_id,
        nombre: l.nombre,
        lote: l.lote,
        cantidad: l.cantidad,
        precio: l.precio,
      })),
    }
    const res = await proxy.$axios.post('ventas', payload)
    ultimaVenta.value = res.data
    proxy.$alert.success(
      estado === 'PENDIENTE'
        ? 'Venta guardada — se cobra luego desde la lista de ventas'
        : 'Venta registrada',
    )
    dialogDatos.value = false
    cobrarLuego.value = false
    nueva.value = nuevaVentaVacia()
    await nextTick()
    imprimirUltimaVenta()
    // Se queda en la pantalla para seguir vendiendo: solo se refrescan los
    // productos, porque la venta ya descontó stock de los lotes.
    loadProductos()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al registrar la venta')
  } finally {
    registrando.value = false
  }
}

// ── Paciente / Doctor rápido ───────────────────────────────────
const savingQuick = ref(false)
const pacQuick = ref(false)
const hoyFecha = new Date().toISOString().slice(0, 10)
const pacQ = ref({ nombre_completo: '', ci: '', sexo: null, fecha_nacimiento: null, telefono: '' })
const docQuick = ref(false)
const docQ = ref({ nombre: '', especialidad_ids: [] })

const intQuick = ref(false)
const intQ = ref({ fecha_ingreso: '', tipo_paciente: '', seguro_id: null, sala: '', codigo_hc: '' })

function abrirIntQuick () {
  // El seguro de la internación arranca en el del paciente y, si no tiene,
  // en el que ya se eligió para la venta; siempre se puede cambiar o limpiar.
  intQ.value = {
    fecha_ingreso: hoyFecha,
    tipo_paciente: '',
    seguro_id: pacienteElegido.value?.seguro_id ?? nueva.value.seguro_id ?? null,
    sala: '',
    codigo_hc: '',
  }
  intQuick.value = true
}

async function intQuickSave () {
  savingQuick.value = true
  try {
    await proxy.$axios.post('internaciones', {
      paciente_id: nueva.value.paciente_id,
      ...intQ.value,
    })
    intQuick.value = false
    await cargarInternaciones(nueva.value.paciente_id)
    proxy.$alert.success('Internación creada')
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al crear la internación')
  } finally {
    savingQuick.value = false
  }
}

async function pacQuickSave () {
  savingQuick.value = true
  try {
    const res = await proxy.$axios.post('pacientes', pacQ.value)
    opcionesPaciente.value = [res.data, ...opcionesPaciente.value]
    nueva.value.paciente_id = res.data.id
    pacQuick.value = false
    pacQ.value = { nombre_completo: '', ci: '', sexo: null, fecha_nacimiento: null, telefono: '' }
    proxy.$alert.success('Paciente creado')
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al crear paciente')
  } finally {
    savingQuick.value = false
  }
}

function abrirDocQuick () {
  if (!especialidades.value.length) loadEspecialidades()
  docQuick.value = true
}

async function docQuickSave () {
  savingQuick.value = true
  try {
    const res = await proxy.$axios.post('doctores', docQ.value)
    opcionesDoctor.value = [res.data, ...opcionesDoctor.value]
    nueva.value.doctor_id = res.data.id
    docQuick.value = false
    docQ.value = { nombre: '', especialidad_ids: [] }
    proxy.$alert.success('Doctor creado')
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al crear doctor')
  } finally {
    savingQuick.value = false
  }
}

// ── Cierre de caja ─────────────────────────────────────────────
// Con la caja del día cerrada el backend rechaza la venta; aquí se avisa antes.
const cajaCerrada = ref(false)

async function cargarEstadoCaja () {
  if (!proxy.$store.hasPermission('Cerrar Caja')) return
  try {
    const { data } = await proxy.$axios.get('cierres-caja/estado')
    cajaCerrada.value = !!data.cerrada
  } catch {
    cajaCerrada.value = false
  }
}

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadProductos()
  loadTiposProducto()
  loadSeguros()
  loadEspecialidades()
  cargarEstadoCaja()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })
</script>

<style scoped>
.ventas-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.ventas-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}

.ventas-compactas :deep(.q-field--dense .q-field__native),
.ventas-compactas :deep(.q-field--dense .q-field__input),
.ventas-compactas :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}

.ventas-compactas :deep(.q-field--dense .q-field__append),
.ventas-compactas :deep(.q-field--dense .q-field__prepend) {
  height: 30px;
}

.ventas-compactas :deep(.q-textarea.q-field--dense .q-field__control) {
  min-height: 38px;
}

.ventas-compactas :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}

/* Carrito: ancho de columnas fijo para que un nombre largo se parta en varias
   líneas en lugar de estirar la tabla y empujar Cant./Precio/Total fuera de vista. */
.tabla-carrito {
  table-layout: fixed;
}

.tabla-carrito :deep(th),
.tabla-carrito :deep(td) {
  font-size: 10px;
  padding: 2px 5px;
}

.celda-producto {
  white-space: normal;
  overflow-wrap: anywhere;
  line-height: 1.15;
}

.celda-producto .producto-nombre {
  font-size: 10.5px;
  font-weight: 500;
}

.celda-producto .producto-info {
  font-size: 9px;
  line-height: 1.1;
}

/* Celdas de Cant. y Precio: el input aprovecha todo el ancho de la columna. */
.celda-num {
  padding: 2px 4px !important;
  position: relative;
}

/* Asterisco de precio modificado: va sobre la celda para no robarle ancho
   al campo, que ya es angosto. */
.celda-num--mod::before {
  content: '*';
  position: absolute;
  top: 0;
  left: 3px;
  z-index: 1;
  font-size: 15px;
  font-weight: 700;
  line-height: 1;
  color: #e65100;
  pointer-events: none;
}

.celda-num :deep(.q-field__control),
.celda-num :deep(.q-field__native) {
  padding: 0 6px;
}

.celda-num :deep(.q-field--dense .q-field__native) {
  font-size: 11px;
}

/* Las flechitas del type="number" se comían el último dígito al escribir. */
.celda-num :deep(input[type='number']) {
  appearance: textfield;
  -moz-appearance: textfield;
}

.celda-num :deep(input[type='number']::-webkit-outer-spin-button),
.celda-num :deep(input[type='number']::-webkit-inner-spin-button) {
  appearance: none;
  -webkit-appearance: none;
  margin: 0;
}

/* Contenedor de la tabla: el spinner se superpone en vez de reemplazar las filas */
.tabla-wrap {
  position: relative;
}

/* Bloque de internaciones del paciente dentro del diálogo de la venta */
.internaciones-box {
  border: 1px solid #e0e6e4;
  border-radius: 8px;
  background: #f6f9f8;
  padding: 6px 8px;
}

/* Altura fija: la tabla ya no crece ni se encoge al cargar/filtrar/paginar */
.tabla-fija {
  height: calc(100vh - 300px);
  min-height: 260px;
}

/* Cabecera fija al hacer scroll dentro de la tabla */
.tabla-fija :deep(thead tr) {
  background-color: #fafafa;
}

.tabla-fija :deep(thead tr th) {
  position: sticky;
  top: 0;
  z-index: 1;
  background-color: inherit;
}
</style>
