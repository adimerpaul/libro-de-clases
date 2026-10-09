<template>
  <q-page class="q-pa-sm paciente-dense">
    <div v-if="loading" class="text-center q-pa-lg">
      <q-spinner color="primary" size="32px" />
    </div>

    <template v-else-if="paciente.id">
      <!-- CABECERA COMPACTA -->
      <q-card flat bordered class="q-pa-xs q-mb-xs rounded-borders">
        <div class="row items-center q-gutter-x-xs no-wrap">
          <q-btn flat dense round icon="arrow_back" size="sm" color="grey-7" to="/pacientes">
            <q-tooltip>Volver a pacientes</q-tooltip>
          </q-btn>
          <q-avatar size="34px" color="primary" text-color="white" class="text-weight-bold">
            {{ initials(paciente.nombre_completo) }}
          </q-avatar>
          <div class="col" style="min-width:160px">
            <div class="text-subtitle2 text-weight-bold ellipsis">{{ paciente.nombre_completo }}</div>
            <div class="text-caption text-grey-7 ellipsis">
              <q-icon name="badge" size="13px" /> {{ paciente.ci || '—' }}
              <span class="q-mx-xs">·</span>
              <q-icon name="wc" size="13px" /> {{ sexoLabel }}
              <span class="q-mx-xs">·</span>
              <q-icon name="phone" size="13px" /> {{ paciente.telefono || '—' }}
              <span class="q-mx-xs">·</span>
              <q-icon name="verified_user" size="13px" /> {{ paciente.seguro?.nombre || 'PARTICULAR' }}
              <span class="q-mx-xs">·</span>
              <q-icon name="home" size="13px" /> {{ paciente.direccion || '—' }}
            </div>
          </div>
          <q-chip dense square :color="estadoChip.bg" :text-color="estadoChip.text"
                  class="q-ma-none text-weight-bold">
            {{ estadoLabel(paciente.estado_internacion) }}
          </q-chip>
          <q-btn-dropdown dense outline no-caps size="sm" color="blue-grey-8"
                          icon="print" label="Imprimir pendientes" :loading="!!printingCuenta">
            <q-list dense style="min-width:260px">
              <q-item clickable v-close-popup @click="imprimirEstadoCuenta('ventas')">
                <q-item-section avatar><q-icon name="receipt_long" color="blue-grey-8" /></q-item-section>
                <q-item-section>
                  <q-item-label>Por ventas</q-item-label>
                  <q-item-label caption>Internaciones y ventas pendientes, una por una</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="imprimirEstadoCuenta('categoria')">
                <q-item-section avatar><q-icon name="account_tree" color="deep-purple" /></q-item-section>
                <q-item-section>
                  <q-item-label>Por categoría</q-item-label>
                  <q-item-label caption>Cargos agrupados por tipo padre (Laboratorio, Farmacia…)</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
          <q-btn v-if="canCrearVenta" dense unelevated no-caps size="sm" color="positive"
                 icon="payments" label="Cobrar todo" :disable="!hayDeuda" @click="cobrarTodoNew">
            <q-tooltip v-if="hayDeuda">
              Cobra todas las ventas pendientes existentes
            </q-tooltip>
            <q-tooltip v-else>El paciente no tiene ventas pendientes de cobro</q-tooltip>
          </q-btn>
          <q-btn v-if="canEditar" dense outline no-caps size="sm" color="grey-7"
                 icon="edit" label="Editar" @click="pacEdit" />
          <q-btn v-if="canEliminar" dense outline no-caps size="sm" color="negative"
                 icon="delete" label="Eliminar" @click="pacDelete" />
          <q-btn v-if="canCrearInt" dense unelevated no-caps size="sm" color="primary"
                 icon="add" label="Internación" @click="intNew" />
        </div>

        <!-- Montos -->
        <div class="row items-center q-gutter-xs q-mt-xs">
          <q-chip dense square color="blue-1" text-color="blue-9" icon="hotel" class="q-ma-none">
            Cargos internación <b class="q-ml-xs">{{ formatMoney(totalCargos) }} Bs</b>
          </q-chip>
          <q-chip dense square color="green-1" text-color="green-9" icon="point_of_sale" class="q-ma-none">
            Ventas cobradas <b class="q-ml-xs">{{ formatMoney(resumenVentas.total_ventas) }} Bs</b>
          </q-chip>
          <q-chip v-if="hayPendientes" square color="orange-8" text-color="white" icon="schedule"
                  class="q-ma-none text-weight-bold chip-pendiente" clickable @click="verPendientes">
            Pendiente de cobro <b class="q-ml-xs">{{ formatMoney(resumenVentas.total_pendientes) }} Bs</b>
            <q-badge color="white" text-color="orange-9" class="q-ml-xs">
              {{ resumenVentas.cantidad_pendientes || 0 }}
            </q-badge>
            <q-tooltip>Ver las ventas pendientes de este paciente</q-tooltip>
          </q-chip>
          <q-chip v-else dense square color="grey-3" text-color="grey-8" icon="check_circle" class="q-ma-none">
            Sin pagos pendientes
          </q-chip>
          <q-space />
          <q-chip dense square color="red-1" text-color="negative" icon="payments"
                  class="q-ma-none text-weight-bold">
            Total a cobrar <b class="q-ml-xs">{{ formatMoney(totalACobrar) }} Bs</b>
            <q-tooltip>Cargos de internación + ventas pendientes de pago</q-tooltip>
          </q-chip>
        </div>
      </q-card>

      <!-- TABS -->
      <q-tabs v-model="tab" dense align="left" no-caps
              active-color="primary" indicator-color="primary" class="text-grey-7">
        <q-tab name="internaciones" icon="local_hospital"
               :label="'Internaciones (' + paciente.internaciones.length + ')'" />
        <q-tab name="ventas" icon="receipt_long"
               :label="'Ventas (' + (resumenVentas.cantidad || 0) + ')'" />
      </q-tabs>
      <q-separator class="q-mb-xs" />

      <q-tab-panels v-model="tab" animated class="bg-transparent">

        <!-- INTERNACIONES -->
        <q-tab-panel name="internaciones" class="q-pa-none">
          <q-card v-if="!paciente.internaciones.length" flat bordered
                  class="column items-center q-pa-md rounded-borders">
            <q-icon name="event_busy" size="32px" color="grey-4" />
            <div class="text-caption text-grey-6 q-mt-xs">Sin internaciones registradas</div>
          </q-card>

          <q-card v-for="(int, idx) in paciente.internaciones" :key="int.id"
                  flat bordered class="q-mb-xs rounded-borders overflow-hidden">
            <div class="row items-center q-gutter-xs bg-blue-grey-1 q-px-sm q-py-xs">
              <q-avatar rounded size="20px" color="primary" text-color="white"
                        class="text-caption text-weight-bold">{{ idx + 1 }}</q-avatar>
              <q-chip v-if="int.dias_internado != null" dense square color="white" text-color="primary"
                      icon="hotel" class="q-ma-none">
                {{ int.dias_internado }} {{ int.dias_internado === 1 ? 'día' : 'días' }}
              </q-chip>
              <q-chip v-if="int.fecha_ingreso" dense square color="white" text-color="grey-8"
                      icon="login" class="q-ma-none">
                {{ int.fecha_ingreso }}
                <q-tooltip>Fecha de ingreso</q-tooltip>
              </q-chip>
              <q-chip v-if="int.fecha_alta" dense square color="white" text-color="grey-8"
                       icon="logout" class="q-ma-none">
                Alta · {{ int.fecha_alta }}
                <q-tooltip>Fecha de alta</q-tooltip>
              </q-chip>
              <q-chip v-else dense square color="orange-1" text-color="orange-9" icon="pending" class="q-ma-none">
                Sin alta
              </q-chip>
              <q-chip v-if="int.pagada" dense square color="green-1" text-color="green-9"
                      icon="paid" class="q-ma-none text-weight-bold">
                Pagada
                <q-tooltip>
                  {{ formatMoney(int.monto_pagado) }} Bs · {{ int.pago_tipo || 'EFECTIVO' }}
                  <template v-if="int.pagado_por"> · {{ int.pagado_por.name }}</template>
                </q-tooltip>
              </q-chip>
              <q-chip v-if="int.tipo_paciente" dense square color="amber-1" text-color="orange-9" class="q-ma-none">
                {{ int.tipo_paciente }}
              </q-chip>
              <q-chip dense square color="cyan-1" text-color="cyan-10" icon="verified_user" class="q-ma-none">
                {{ int.seguro?.nombre || 'PARTICULAR' }}
              </q-chip>
              <q-chip v-if="int.sala" dense square color="white" text-color="grey-8"
                      icon="meeting_room" class="q-ma-none">{{ int.sala }}</q-chip>
              <q-chip v-if="int.codigo_hc" dense square color="white" text-color="grey-8"
                      icon="qr_code_2" class="q-ma-none">{{ int.codigo_hc }}</q-chip>
              <q-space />
              <q-chip dense square color="primary" text-color="white" class="q-ma-none text-weight-bold">
                {{ formatMoney(totalItems(int)) }} Bs
              </q-chip>
              <q-btn dense flat round size="sm" icon="print" color="grey-7"
                     :loading="printingId === int.id" @click="imprimir(int.id)">
                <q-tooltip>Imprimir proforma</q-tooltip>
              </q-btn>
              <q-btn v-if="canCrearVenta && !int.pagada && int.items && int.items.length" dense flat round size="sm"
                     icon="paid" color="green-8" @click="pagoNew(int)">
                <q-tooltip>Cobrar internación</q-tooltip>
              </q-btn>
              <q-btn v-if="canCrearInt && !int.pagada" dense flat round size="sm" icon="add_circle" color="positive"
                     @click="itemNew(int)">
                <q-tooltip>Agregar cargo</q-tooltip>
              </q-btn>
              <q-btn v-if="canEditarInt && !int.pagada" dense flat round size="sm" icon="edit" color="grey-7"
                     @click="intEdit(int)">
                <q-tooltip>Editar internación</q-tooltip>
              </q-btn>
              <q-btn v-if="canEliminarInt && !int.pagada" dense flat round size="sm" icon="delete" color="negative"
                     @click="intDelete(int.id)">
                <q-tooltip>Eliminar internación</q-tooltip>
              </q-btn>
            </div>

            <q-markup-table dense flat separator="horizontal" class="tabla-compacta">
              <thead>
                <tr class="bg-grey-1 text-grey-7 text-uppercase">
                  <th class="text-left">Ítem</th>
                  <th class="text-right" style="width:70px">Cant.</th>
                  <th class="text-right" style="width:80px">Precio</th>
                  <th class="text-right" style="width:90px">Total</th>
                  <th class="text-left" style="width:120px">Registrado por</th>
                  <th class="text-left" style="width:60px">Hora</th>
                  <th style="width:60px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!int.items || !int.items.length">
                  <td colspan="7" class="text-center text-grey-5 q-pa-sm">Sin cargos registrados</td>
                </tr>
                <tr v-for="item in int.items" :key="item.id">
                  <td class="text-weight-medium">{{ item.nombre }}</td>
                  <td class="text-right">{{ formatCantidad(item.cantidad) }}</td>
                  <td class="text-right">{{ formatMoney(item.precio) }}</td>
                  <td class="text-right text-weight-bold">{{ formatMoney(item.total) }}</td>
                  <td class="text-grey-6">{{ item.user?.name || '—' }}</td>
                  <td>{{ formatHora(item.created_at) }}</td>
                  <td class="text-right">
                    <q-btn v-if="canEditarInt && !int.pagada" dense flat round icon="edit" size="xs" color="grey-7" @click="itemEdit(int, item)" />
                    <q-btn v-if="canEliminarInt && !int.pagada" dense flat round icon="delete" size="xs" color="negative" @click="itemDelete(int, item.id)" />
                  </td>
                </tr>
              </tbody>
              <tfoot v-if="int.items && int.items.length">
                <tr class="bg-grey-1">
                  <td colspan="3" class="text-right text-weight-bold">TOTAL</td>
                  <td class="text-right text-weight-bold text-primary">{{ formatMoney(totalItems(int)) }}</td>
                  <td colspan="3"></td>
                </tr>
              </tfoot>
            </q-markup-table>
          </q-card>
        </q-tab-panel>

        <!-- VENTAS -->
        <q-tab-panel name="ventas" class="q-pa-none">
          <div class="row items-center q-gutter-xs q-mb-xs">
            <span class="text-caption text-grey-7">Ventas registradas a este paciente</span>
            <q-space />
            <q-btn dense outline no-caps size="sm" color="grey-7" icon="refresh" label="Actualizar"
                   :loading="loadingVentas" @click="fetchVentas" />
            <q-btn v-if="canCrearVenta" dense unelevated no-caps size="sm" color="primary"
                   icon="point_of_sale" label="Nueva venta" to="/ventas/crear" />
          </div>

          <div class="tabla-wrap">
            <q-markup-table dense flat bordered separator="horizontal" class="full-width tabla-compacta">
              <thead>
                <tr class="bg-grey-1 text-grey-7 text-uppercase">
                  <th style="width:28px"></th>
                  <th class="text-left" style="width:60px">N.º</th>
                  <th class="text-left" style="width:120px">Fecha</th>
                  <th class="text-left">Doctor</th>
                  <th class="text-left">Seguro</th>
                  <th class="text-left" style="width:120px">Usuario</th>
                  <th class="text-right" style="width:60px">Ítems</th>
                  <th class="text-left" style="width:90px">Pago</th>
                  <th class="text-center" style="width:90px">Estado</th>
                  <th class="text-right" style="width:90px">Total</th>
                  <th class="text-right" style="width:110px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!ventas.length && !loadingVentas">
                  <td colspan="11" class="text-center text-grey-5 q-pa-md">Sin ventas registradas</td>
                </tr>
                <template v-for="v in ventas" :key="v.id">
                  <tr :class="[
                        v.estado === 'PENDIENTE' && !v.fecha_hora_cobro ? 'bg-orange-1' : '',
                        canDetalleVenta ? 'cursor-pointer' : '',
                      ]"
                      @click="canDetalleVenta && toggleVenta(v.id)">
                    <td class="text-center">
                      <q-icon v-if="canDetalleVenta"
                              :name="expandidas.includes(v.id) ? 'expand_less' : 'expand_more'"
                              size="16px" color="grey-7" />
                    </td>
                    <td class="text-weight-bold">#{{ v.id }}</td>
                    <td>{{ formatFecha(v.fecha_hora) }}</td>
                    <td>{{ v.doctor?.nombre || '—' }}</td>
                    <td>{{ v.seguro?.nombre || 'PARTICULAR' }}</td>
                    <!-- Quien registró la venta; si otro la cobró, se ve al desplegar la fila -->
                    <td class="ellipsis" :title="v.user?.name || ''">{{ v.user?.name || '—' }}</td>
                    <td class="text-right">{{ v.detalles_count ?? (v.detalles?.length || 0) }}</td>
                    <td>{{ v.tipo_pago || '—' }}</td>
                    <td class="text-center">
                      <q-badge :color="estadoVentaColor(v).bg" :text-color="estadoVentaColor(v).text">
                        {{ estadoVentaLabel(v) }}
                      </q-badge>
                    </td>
                    <td class="text-right text-weight-bold">{{ formatMoney(v.total) }}</td>
                    <td class="text-right no-wrap">
                      <q-btn v-if="v.estado === 'PENDIENTE' && !v.fecha_hora_cobro && canCrearVenta" dense unelevated size="xs"
                             color="positive" icon="payments" no-caps label="Cobrar"
                             @click.stop="abrirCobrar(v)" />
                      <q-btn v-if="canDetalleVenta" dense flat round size="xs" color="grey-8" icon="print"
                             class="q-ml-xs" :loading="reimprimiendoId === v.id"
                             @click.stop="imprimirVentaFila(v)">
                        <q-tooltip>Reimprimir venta #{{ v.id }}</q-tooltip>
                      </q-btn>
                    </td>
                  </tr>
                  <tr v-if="expandidas.includes(v.id)" class="bg-grey-1">
                    <td colspan="11">
                      <div v-for="d in v.detalles || []" :key="d.id" class="row items-center no-wrap">
                        <div class="col ellipsis">
                          {{ d.nombre }}
                          <span v-if="d.lote" class="text-grey-6">· Lote {{ d.lote }}</span>
                        </div>
                        <div class="col-auto text-grey-7 q-mr-md">
                          {{ formatCantidad(d.cantidad) }} × {{ formatMoney(d.precio) }}
                        </div>
                        <div class="col-auto text-weight-bold text-right" style="width:80px">
                          {{ formatMoney(d.total) }} Bs
                        </div>
                      </div>
                      <div v-if="!v.detalles?.length" class="text-grey-6">Sin ítems registrados</div>
                      <div class="text-caption text-grey-7 q-mt-xs">
                        Registrada por {{ v.user?.name || '—' }} el {{ formatFecha(v.fecha_hora) }}
                      </div>
                      <div v-if="v.fecha_hora_cobro" class="text-caption text-positive q-mt-xs">
                        Cobrado por {{ v.cobrado_por?.name || '—' }} el {{ formatFecha(v.fecha_hora_cobro) }}
                      </div>
                      <div class="q-mt-xs">
                        <q-btn dense outline no-caps size="sm" color="blue-grey-8" icon="print" label="Reimprimir"
                               :loading="reimprimiendoId === v.id" @click.stop="imprimirVentaFila(v)" />
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </q-markup-table>
            <q-inner-loading :showing="loadingVentas" color="primary" />
          </div>

          <div class="row items-center justify-between q-mt-xs q-px-xs">
            <div class="text-caption text-grey-6">
              Total: {{ totalVentas }} | Página {{ pageVentas }} de {{ pagesVentas }}
            </div>
            <q-pagination v-model="pageVentas" :max="pagesVentas" :max-pages="6"
                          boundary-links direction-links size="sm" @update:model-value="fetchVentas" />
          </div>
        </q-tab-panel>
      </q-tab-panels>
    </template>

    <!-- DIALOG EDITAR PACIENTE -->
    <q-dialog v-model="dialogPac" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="badge" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Editar paciente</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogPac = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="pacSave">
            <q-input v-model="pacForm.nombre_completo" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase>
              <template v-slot:prepend><q-icon name="person" /></template>
            </q-input>
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-select v-model="pacForm.sexo" label="Sexo" dense outlined clearable
                          :options="[{label:'Masculino',value:'M'},{label:'Femenino',value:'F'}]"
                          emit-value map-options>
                  <template v-slot:prepend><q-icon name="wc" /></template>
                </q-select>
              </div>
              <div class="col-6">
                <q-input v-model="pacForm.ci" label="CI" dense outlined v-uppercase>
                  <template v-slot:prepend><q-icon name="badge" /></template>
                </q-input>
              </div>
            </div>
            <q-select v-model="pacForm.seguro_id" label="Seguro" dense outlined clearable class="q-mb-sm"
                      :options="seguros" option-label="nombre" option-value="id"
                      emit-value map-options>
              <template v-slot:prepend><q-icon name="verified_user" /></template>
            </q-select>
            <q-input v-model="pacForm.estado" label="Estado" dense outlined class="q-mb-sm" v-uppercase>
              <template v-slot:prepend><q-icon name="toggle_on" /></template>
            </q-input>
            <q-input v-model="pacForm.direccion" label="Dirección" dense outlined class="q-mb-sm" v-uppercase>
              <template v-slot:prepend><q-icon name="home" /></template>
            </q-input>
            <q-input v-model="pacForm.telefono" label="Teléfono" dense outlined class="q-mb-md" v-uppercase>
              <template v-slot:prepend><q-icon name="phone" /></template>
            </q-input>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogPac = false" />
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="savingPac" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG INTERNACION -->
    <q-dialog v-model="dialogInt" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="local_hospital" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ actionInt }} internación</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogInt = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="intSave">
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-input v-model="int.fecha_ingreso" label="Fecha de ingreso" dense outlined type="date">
                  <template v-slot:prepend><q-icon name="login" /></template>
                </q-input>
              </div>
              <div class="col-6">
                <q-input v-model="int.fecha_alta" label="Fecha de alta" dense outlined type="date" clearable
                         hint="La internación se cierra al cobrarla, no al dar el alta">
                  <template v-slot:prepend><q-icon name="logout" /></template>
                </q-input>
              </div>
            </div>
            <q-input v-model="int.tipo_paciente" label="Tipo de paciente" dense outlined class="q-mb-sm" v-uppercase>
              <template v-slot:prepend><q-icon name="category" /></template>
            </q-input>
            <q-select v-model="int.seguro_id" label="Seguro de la internación" dense outlined clearable
                      class="q-mb-sm" :options="seguros" option-value="id" option-label="nombre"
                      emit-value map-options>
              <template v-slot:prepend><q-icon name="verified_user" /></template>
              <template v-slot:no-option>
                <q-item><q-item-section class="text-grey">No hay seguros registrados</q-item-section></q-item>
              </template>
            </q-select>
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-6">
                <q-input v-model="int.codigo_hc" label="Código H.C." dense outlined v-uppercase>
                  <template v-slot:prepend><q-icon name="qr_code_2" /></template>
                </q-input>
              </div>
              <div class="col-6">
                <q-input v-model="int.sala" label="Sala" dense outlined v-uppercase>
                  <template v-slot:prepend><q-icon name="meeting_room" /></template>
                </q-input>
              </div>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn v-if="int.id && !int.fecha_alta" outline color="primary" label="Dar de alta hoy"
                     icon="logout" no-caps :loading="closingInt" @click="intClose" />
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogInt = false" />
              <q-btn color="primary" :label="int.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingInt" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG COBRO DE LA INTERNACION -->
    <q-dialog v-model="dialogPago" persistent>
      <q-card style="width:min(96vw,440px)">
        <q-card-section class="row items-center bg-green-8 text-white q-py-sm">
          <q-icon name="paid" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Cobrar internación</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogPago = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-banner dense class="bg-amber-1 text-orange-10 q-mb-sm" style="font-size:11px">
            <template v-slot:avatar><q-icon name="lock" color="orange-9" size="18px" /></template>
            Al cobrarla se genera una venta y la internación queda cerrada: no admite más cargos ni cambios.
          </q-banner>
          <q-form @submit.prevent="pagoSave">
            <div class="row items-center q-mb-sm">
              <div class="text-caption text-grey-7">Total de cargos</div>
              <q-space />
              <div class="text-h6 text-weight-bold text-green-9">{{ formatMoney(pagoTotal) }} Bs</div>
            </div>
            <q-select v-model="pago.tipo_pago" label="Tipo de pago" dense outlined class="q-mb-sm"
                      :options="['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'QR']">
              <template v-slot:prepend><q-icon name="account_balance_wallet" /></template>
            </q-select>
            <q-input v-model.number="pago.pago" label="Monto recibido" dense outlined type="number"
                     min="0" step="0.01" class="q-mb-sm" :rules="[pagoSuficiente]">
              <template v-slot:prepend><q-icon name="payments" /></template>
            </q-input>
            <div class="row items-center q-mb-sm">
              <div class="text-caption text-grey-7">Cambio</div>
              <q-space />
              <div class="text-weight-bold" :class="pagoCambio < 0 ? 'text-negative' : 'text-primary'">
                {{ formatMoney(pagoCambio) }} Bs
              </div>
            </div>
            <q-input v-model="pago.observacion" label="Observación" dense outlined class="q-mb-md" maxlength="255">
              <template v-slot:prepend><q-icon name="notes" /></template>
            </q-input>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogPago = false" />
              <q-btn color="green-8" label="Cobrar" type="submit" no-caps
                     :loading="savingPago" icon-right="paid" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG COBRO DE TODA LA CUENTA -->
    <q-dialog v-model="dialogTodo" persistent>
      <q-card style="width:min(96vw,460px)">
        <q-card-section class="row items-center bg-green-8 text-white q-py-sm">
          <q-icon name="payments" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Cobrar toda la cuenta</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogTodo = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-banner dense class="bg-amber-1 text-orange-10 q-mb-sm" style="font-size:11px">
            <template v-slot:avatar><q-icon name="lock" color="orange-9" size="18px" /></template>
            Las ventas existentes se marcan cobradas por su importe exacto.
            Las internaciones quedan pendientes. La acción no se puede deshacer.
          </q-banner>

          <q-markup-table dense flat bordered separator="horizontal" class="q-mb-sm tabla-compacta">
            <tbody>
              <tr>
                <td><q-icon name="local_hospital" size="14px" color="primary" /> Internaciones pendientes
                  ({{ internacionesPorCobrar.length }})</td>
                <td class="text-right text-weight-bold">{{ formatMoney(totalCargos) }} Bs</td>
              </tr>
              <tr>
                <td><q-icon name="medication" size="14px" color="orange-9" /> Ventas pendientes
                  ({{ resumenVentas.cantidad_pendientes || 0 }})</td>
                <td class="text-right text-weight-bold">
                  {{ formatMoney(resumenVentas.total_pendientes) }} Bs
                </td>
              </tr>
            </tbody>
          </q-markup-table>

          <div class="row items-center q-mb-sm">
            <div class="text-caption text-grey-7">Total a cobrar</div>
            <q-space />
            <div class="text-h6 text-weight-bold text-green-9">{{ formatMoney(totalACobrar) }} Bs</div>
          </div>

          <q-form @submit.prevent="cobrarTodoSave">
            <q-select v-model="cobroTodo.tipo_pago" label="Tipo de pago" dense outlined class="q-mb-sm"
                      :options="['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'QR']">
              <template v-slot:prepend><q-icon name="account_balance_wallet" /></template>
            </q-select>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogTodo = false" />
              <q-btn color="green-8" label="Cobrar todo" type="submit" no-caps
                     :loading="cobrandoTodo" icon-right="payments" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG CARGO (ITEM) -->
    <q-dialog v-model="dialogItem" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ actionItem }} cargo</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogItem = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="itemSave">
            <q-select v-model="filtroTipoProducto" label="Categoría" dense outlined clearable
                      class="q-mb-sm" use-input input-debounce="0"
                      :options="tipoProductoOptions" option-value="id" option-label="nombre"
                      emit-value map-options @filter="filterTipoProductos" @update:model-value="onFiltroTipoChange">
              <template v-slot:prepend><q-icon name="category" /></template>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-badge :color="scope.opt.color || 'primary'" style="width:16px;height:16px" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-select v-model="item.producto_id" label="Producto del catálogo (opcional)" dense outlined
                      class="q-mb-sm" clearable use-input input-debounce="300"
                      :options="productoOptions" option-value="id" option-label="nombre"
                      emit-value map-options @filter="filterProductos" @update:model-value="onProductoSelected">
              <template v-slot:prepend><q-icon name="inventory_2" /></template>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                    <q-item-label caption>
                      <q-badge v-if="scope.opt.tipo_producto" :color="scope.opt.tipo_producto.color || 'primary'" class="q-mr-xs">
                        {{ scope.opt.tipo_producto.nombre }}
                      </q-badge>
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <div class="text-weight-bold text-primary">{{ formatMoney(scope.opt.precio) }} Bs.</div>
                  </q-item-section>
                </q-item>
              </template>
              <template v-slot:no-option>
                <q-item><q-item-section class="text-grey">Sin resultados — puede escribir un ítem libre abajo</q-item-section></q-item>
              </template>
            </q-select>
            <q-input v-model="item.nombre" label="Nombre del ítem *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase>
              <template v-slot:prepend><q-icon name="label" /></template>
            </q-input>
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-input v-model.number="item.cantidad" label="Cantidad" dense outlined type="number" step="0.01" min="0.01">
                  <template v-slot:prepend><q-icon name="numbers" /></template>
                </q-input>
              </div>
              <div class="col-6">
                <q-input v-model.number="item.precio" label="Precio unitario (Bs.)" dense outlined type="number" step="0.01" min="0">
                  <template v-slot:prepend><q-icon name="attach_money" /></template>
                </q-input>
              </div>
            </div>
            <div class="text-right text-subtitle2 text-weight-bold text-primary q-mb-md">
              Total: {{ formatMoney((item.cantidad || 0) * (item.precio || 0)) }} Bs.
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogItem = false" />
              <q-btn color="primary" :label="item.id ? 'Guardar' : 'Agregar'"
                     type="submit" no-caps :loading="savingItem" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <!-- DIALOG COBRAR VENTA PENDIENTE -->
    <q-dialog v-model="dialogCobrar" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Cobrar venta #{{ ventaCobrar?.id }}</span>
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="cobrarVenta">
            <div class="text-subtitle1 q-mb-sm">
              Total: <span class="text-primary text-weight-bold">{{ formatMoney(ventaCobrar?.total) }} Bs</span>
            </div>
            <q-input v-model.number="cobrarPago" label="Pago Bs *" dense outlined type="number" step="0.01" min="0"
                     class="q-mb-xs" autofocus input-class="text-right" />
            <div class="text-body2 q-mb-md">Cambio:
              <span class="text-weight-bold" :class="cobrarCambio < 0 ? 'text-negative' : 'text-positive'">
                {{ formatMoney(cobrarCambio) }} Bs
              </span>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogCobrar = false" />
              <q-btn color="primary" label="Cobrar e imprimir" icon-right="payments" type="submit"
                     no-caps :loading="cobrando" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { formatBoliviaDateTime } from '../../../addons/dateTime'
import { imprimirVenta } from '../../../addons/ventaPrint'

const { proxy } = getCurrentInstance()

const canEditar      = computed(() => proxy.$store.hasPermission('Editar Pacientes'))
const canEliminar    = computed(() => proxy.$store.hasPermission('Eliminar Pacientes'))
const canCrearInt    = computed(() => proxy.$store.hasPermission('Crear Internaciones'))
const canEditarInt   = computed(() => proxy.$store.hasPermission('Editar Internaciones'))
const canEliminarInt = computed(() => proxy.$store.hasPermission('Eliminar Internaciones'))
const canVerVentas   = computed(() => proxy.$store.hasPermission('Ver Ventas'))
const canCrearVenta  = computed(() => proxy.$store.hasPermission('Crear Ventas'))
// Detalle de la venta y reimpresión del comprobante: permiso aparte.
const canDetalleVenta = computed(() => proxy.$store.hasPermission('Ver Detalle Ventas'))

const tab = ref('internaciones')

const loading  = ref(false)
const paciente = ref({ internaciones: [] })
const seguros  = ref([])

function initials (name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function estadoLabel (estado) {
  return { INTERNADO: 'Internado', ALTA: 'Alta', NO_INTERNADO: 'No internado' }[estado] || estado
}

const estadoChip = computed(() => ({
  INTERNADO: { bg: 'orange-1', text: 'orange-9' },
  ALTA: { bg: 'green-1', text: 'green-9' },
  NO_INTERNADO: { bg: 'grey-3', text: 'grey-8' },
}[paciente.value.estado_internacion] || { bg: 'grey-3', text: 'grey-8' }))

const sexoLabel = computed(() => (
  { M: 'Masculino', F: 'Femenino' }[paciente.value.sexo] || '—'
))

function estadoVentaLabel (venta) {
  return venta?.estado === 'PENDIENTE' && venta?.fecha_hora_cobro ? 'COBRADO' : venta?.estado
}

function estadoVentaColor (venta) {
  if (venta?.estado === 'ANULADO') return { bg: 'red-1', text: 'negative' }
  if (venta?.estado === 'PENDIENTE' && !venta?.fecha_hora_cobro) return { bg: 'orange-1', text: 'orange-9' }
  return { bg: 'green-1', text: 'positive' }
}

function formatFecha (v) { return formatBoliviaDateTime(v) }

function formatMoney (v) {
  return Number(v || 0).toFixed(2)
}
function formatCantidad (v) {
  const n = Number(v || 0)
  return Number.isInteger(n) ? String(n) : n.toFixed(2)
}
function formatHora (dt) {
  if (!dt) return '—'
  const d = new Date(dt.replace(' ', 'T'))
  return isNaN(d) ? '—' : d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}
function totalItems (int) {
  return (int.items || []).reduce((s, it) => s + Number(it.total || 0), 0)
}

// Una internación pagada ya viajó a ventas: sumarla aquí la contaría dos veces.
const internacionesPorCobrar = computed(() => (
  (paciente.value.internaciones || []).filter(int => !int.pagada && totalItems(int) > 0)
))

const totalCargos = computed(() => (
  internacionesPorCobrar.value.reduce((s, int) => s + totalItems(int), 0)
))

// Lo que el paciente aun debe: cargos de internacion + ventas sin cobrar
const totalACobrar = computed(() => (
  totalCargos.value + Number(resumenVentas.value.total_pendientes || 0)
))

// Ventas del paciente
const ventas        = ref([])
const loadingVentas = ref(false)
const resumenVentas = ref({ total_ventas: 0, total_pendientes: 0, total_anuladas: 0, cantidad: 0, cantidad_pendientes: 0 })
const pageVentas    = ref(1)
const perVentas     = 15
const totalVentas   = ref(0)
const expandidas    = ref([])

const pagesVentas = computed(() => Math.max(1, Math.ceil(totalVentas.value / perVentas)))

const hayPendientes = computed(() => Number(resumenVentas.value.total_pendientes || 0) > 0)

function verPendientes () {
  tab.value = 'ventas'
}

function toggleVenta (id) {
  const i = expandidas.value.indexOf(id)
  if (i === -1) expandidas.value.push(id)
  else expandidas.value.splice(i, 1)
}

async function fetchVentas () {
  if (!canVerVentas.value) return
  loadingVentas.value = true
  try {
    const res = await proxy.$axios.get('ventas', {
      params: {
        paciente_id: proxy.$route.params.id,
        page: pageVentas.value,
        per_page: perVentas,
      },
    })
    const data = res.data || {}
    resumenVentas.value = data.resumen || resumenVentas.value
    ventas.value = data.ventas?.data || []
    totalVentas.value = data.ventas?.total || 0
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar las ventas del paciente')
  } finally {
    loadingVentas.value = false
  }
}

function fetchPaciente () {
  loading.value = true
  proxy.$axios.get('pacientes/' + proxy.$route.params.id).then(res => {
    paciente.value = res.data
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

async function fetchSeguros () {
  try {
    const res = await proxy.$axios.get('seguros')
    seguros.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar los seguros')
  }
}

let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) { fetched = true; fetchPaciente(); fetchVentas(); fetchSeguros() }
}, { immediate: true })

// ── Editar paciente ────────────────────────────────────────────
const dialogPac = ref(false)
const savingPac = ref(false)
const pacForm   = ref({})

function pacEdit () { pacForm.value = { ...paciente.value }; dialogPac.value = true }

async function pacSave () {
  savingPac.value = true
  try {
    await proxy.$axios.put('pacientes/' + paciente.value.id, pacForm.value)
    proxy.$alert.success('Paciente actualizado')
    dialogPac.value = false
    fetchPaciente()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingPac.value = false
  }
}

function pacDelete () {
  proxy.$alert.dialog('¿Desea eliminar el paciente?').onOk(() => {
    proxy.$axios.delete('pacientes/' + paciente.value.id).then(() => {
      proxy.$alert.success('Paciente eliminado')
      proxy.$router.push('/pacientes')
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}

// ── CRUD Internaciones ───────────────────────────────────────
const dialogInt = ref(false)
const savingInt = ref(false)
const closingInt = ref(false)
const actionInt = ref('Nueva')
const int       = ref({})

function hoy () { return new Date().toLocaleDateString('sv-SE') }

function intNew ()     { int.value = { paciente_id: paciente.value.id, seguro_id: paciente.value.seguro_id || null, fecha_ingreso: hoy(), tipo_paciente: '', fecha_alta: '', codigo_hc: '', sala: '' }; actionInt.value = 'Nueva';  dialogInt.value = true }
function intEdit (row) { int.value = { ...row }; actionInt.value = 'Editar'; dialogInt.value = true }

async function intSave () {
  savingInt.value = true
  try {
    if (int.value.id) {
      await proxy.$axios.put('internaciones/' + int.value.id, int.value)
      proxy.$alert.success('Internación actualizada')
    } else {
      await proxy.$axios.post('internaciones', int.value)
      proxy.$alert.success('Internación creada')
    }
    dialogInt.value = false
    fetchPaciente()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingInt.value = false
  }
}

function intClose () {
  proxy.$alert.dialog(
    '¿Dar de alta al paciente hoy?',
    'La internación sigue editable: se cierra recién cuando se cobre.'
  ).onOk(async () => {
    closingInt.value = true
    try {
      await proxy.$axios.put('internaciones/' + int.value.id + '/cerrar')
      proxy.$alert.success('Paciente dado de alta')
      dialogInt.value = false
      await fetchPaciente()
    } catch (err) {
      proxy.$alert.error(err.response?.data?.message || 'Error al cerrar la internación')
    } finally {
      closingInt.value = false
    }
  })
}

function intDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar la internación?').onOk(() => {
    proxy.$axios.delete('internaciones/' + id).then(() => {
      proxy.$alert.success('Internación eliminada')
      fetchPaciente()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}

// ── Cobro total de la internación ────────────────────────────
// Cobrar es lo que cierra la internación: genera la venta y la congela.
const dialogPago = ref(false)
const savingPago = ref(false)
const pago       = ref({})

const pagoTotal  = computed(() => totalItems(pago.value.internacion || {}))
const pagoCambio = computed(() => Number(pago.value.pago || 0) - pagoTotal.value)

function pagoSuficiente (v) {
  return Number(v || 0) >= pagoTotal.value || 'El monto no puede ser menor al total'
}

function pagoNew (row) {
  pago.value = { internacion: row, tipo_pago: 'EFECTIVO', pago: totalItems(row), observacion: '' }
  dialogPago.value = true
}

async function pagoSave () {
  savingPago.value = true
  try {
    await proxy.$axios.post('internaciones/' + pago.value.internacion.id + '/pagar-total', {
      tipo_pago: pago.value.tipo_pago,
      pago: pago.value.pago,
      observacion: pago.value.observacion || null,
    })
    proxy.$alert.success('Internación cobrada')
    dialogPago.value = false
    await fetchPaciente()
    fetchVentas()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al cobrar la internación')
  } finally {
    savingPago.value = false
  }
}

// ── Cobro de toda la cuenta ──────────────────────────────────
// Cobro de ventas e internaciones pendientes del paciente.
const dialogTodo    = ref(false)
const cobrandoTodo  = ref(false)
const cobroTodo     = ref({ tipo_pago: 'EFECTIVO' })

const hayDeuda = computed(() => internacionesPorCobrar.value.length > 0 || Number(resumenVentas.value.cantidad_pendientes || 0) > 0)

function cobrarTodoNew () {
  cobroTodo.value = { tipo_pago: 'EFECTIVO' }
  dialogTodo.value = true
}

async function cobrarTodoSave () {
  cobrandoTodo.value = true
  try {
    const res = await proxy.$axios.post('pacientes/' + paciente.value.id + '/cobrar-todo', {
      tipo_pago: cobroTodo.value.tipo_pago,
    })
    const { ventas_cobradas: vtas, internaciones_cobradas: ints, total } = res.data
    proxy.$alert.success(
      `Cobrado ${formatMoney(total)} Bs · ${vtas} venta(s) y ${ints} internaci?n(es)`
    )
    dialogTodo.value = false
    await fetchPaciente()
    fetchVentas()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al cobrar la cuenta del paciente')
  } finally {
    cobrandoTodo.value = false
  }
}

// ── Imprimir proforma ────────────────────────────────────────
const printingId = ref(null)
const printingCuenta = ref(null)

/**
 * Estado de cuenta: todas las internaciones y productos que siguen pendientes,
 * por ventas (una por una) o por categoría (agrupado por tipo de producto padre).
 */
async function imprimirEstadoCuenta (modo = 'ventas') {
  printingCuenta.value = modo
  const ruta = modo === 'categoria' ? 'estado-cuenta-categoria-pdf' : 'estado-cuenta-pdf'
  try {
    const res = await proxy.$axios.get('pacientes/' + proxy.$route.params.id + '/' + ruta, {
      responseType: 'blob',
    })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch (err) {
    proxy.$alert.error('Error al generar el estado de cuenta')
  } finally {
    printingCuenta.value = null
  }
}

async function imprimir (internacionId) {
  printingId.value = internacionId
  try {
    const res = await proxy.$axios.get('internaciones/' + internacionId + '/pdf', { responseType: 'blob' })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch (err) {
    proxy.$alert.error('Error al generar el PDF')
  } finally {
    printingId.value = null
  }
}

// ── Cargos (items) ────────────────────────────────────────────
const dialogItem      = ref(false)
const savingItem      = ref(false)
const actionItem      = ref('Nuevo')
const item            = ref({})
const currentInt      = ref(null)
const productoOptions = ref([])
const allTipoProductos = ref([])
const tipoProductoSearch = ref('')
const tipoProductoOptions = computed(() => {
  const query = normalizarBusqueda(tipoProductoSearch.value)
  return allTipoProductos.value.filter(tipo => normalizarBusqueda(tipo.nombre).includes(query))
})
const filtroTipoProducto = ref(null)
let lastProductoFilter = ''

function normalizarBusqueda (value) {
  return (value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()
}

function filterTipoProductos (val, update) {
  update(() => { tipoProductoSearch.value = val })
}

function fetchTipoProductos () {
  proxy.$axios.get('tipo-productos').then(res => {
    allTipoProductos.value = res.data
  }).catch(() => {})
}

function itemNew (intRow) {
  currentInt.value = intRow
  item.value = { producto_id: null, nombre: '', cantidad: 1, precio: 0 }
  actionItem.value = 'Nuevo'
  productoOptions.value = []
  filtroTipoProducto.value = null
  if (!allTipoProductos.value.length) fetchTipoProductos()
  dialogItem.value = true
}

function itemEdit (intRow, row) {
  currentInt.value = intRow
  item.value = { ...row }
  actionItem.value = 'Editar'
  productoOptions.value = []
  filtroTipoProducto.value = null
  if (!allTipoProductos.value.length) fetchTipoProductos()
  dialogItem.value = true
}

function filterProductos (val, update) {
  lastProductoFilter = val
  proxy.$axios.get('productos', {
    params: { q: val, tipo_producto_id: filtroTipoProducto.value, per_page: 30 },
  }).then(res => {
    update(() => { productoOptions.value = res.data.data || [] })
  }).catch(() => update(() => { productoOptions.value = [] }))
}

function onFiltroTipoChange () {
  filterProductos(lastProductoFilter, (cb) => cb())
}

function onProductoSelected (id) {
  const prod = productoOptions.value.find(p => p.id === id)
  if (prod) {
    item.value.nombre = prod.nombre
    item.value.precio = Number(prod.precio || 0)
  }
}

async function itemSave () {
  savingItem.value = true
  try {
    if (item.value.id) {
      await proxy.$axios.put('internacion-items/' + item.value.id, item.value)
      proxy.$alert.success('Cargo actualizado')
    } else {
      await proxy.$axios.post('internaciones/' + currentInt.value.id + '/items', item.value)
      proxy.$alert.success('Cargo agregado')
    }
    dialogItem.value = false
    fetchPaciente()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingItem.value = false
  }
}

// Cobrar una venta pendiente sin salir de la ficha del paciente
const dialogCobrar = ref(false)
const ventaCobrar  = ref(null)
const cobrarPago   = ref(0)
const cobrando     = ref(false)

const cobrarCambio = computed(() => {
  const pago = Number(cobrarPago.value) || 0
  return Math.round((pago - Number(ventaCobrar.value?.total || 0)) * 100) / 100
})

function abrirCobrar (row) {
  ventaCobrar.value = row
  cobrarPago.value = Number(row.total)
  dialogCobrar.value = true
}

async function cobrarVenta () {
  if (Number(cobrarPago.value) < Number(ventaCobrar.value?.total || 0)) {
    proxy.$alert.error('El pago no puede ser menor al total')
    return
  }
  cobrando.value = true
  try {
    const res = await proxy.$axios.put('ventas/' + ventaCobrar.value.id + '/completar', { pago: cobrarPago.value })
    proxy.$alert.success('Venta cobrada')
    dialogCobrar.value = false
    fetchVentas()
    imprimirVenta(res.data)
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al cobrar')
  } finally {
    cobrando.value = false
  }
}

/** Reimprime el comprobante de una venta ya registrada (cobrada, pendiente o anulada). */
const reimprimiendoId = ref(null)

async function imprimirVentaFila (venta) {
  if (reimprimiendoId.value) return
  reimprimiendoId.value = venta.id
  try {
    const res = await proxy.$axios.get('ventas/' + venta.id)
    imprimirVenta(res.data)
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al reimprimir')
  } finally {
    reimprimiendoId.value = null
  }
}

function itemDelete (intRow, id) {
  proxy.$alert.dialog('¿Desea eliminar el cargo?').onOk(() => {
    proxy.$axios.delete('internacion-items/' + id).then(() => {
      proxy.$alert.success('Cargo eliminado')
      fetchPaciente()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}
</script>

<style scoped>
/* Chips y tabs compactos en toda la ficha */
.paciente-dense :deep(.q-chip) {
  font-size: 11px;
}

.paciente-dense :deep(.q-tab) {
  min-height: 34px;
  padding: 0 12px;
}

.paciente-dense :deep(.q-tab__icon) {
  font-size: 18px;
}

/* El chip de pendientes no se pierde entre los demas */
.chip-pendiente {
  font-size: 12px;
}

/* Tablas densas */
.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 2px 6px;
}

/* El spinner se superpone: la tabla no cambia de tamano al cargar */
.tabla-wrap {
  position: relative;
  min-height: 120px;
}
</style>
