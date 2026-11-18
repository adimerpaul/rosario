<template>
  <q-page class="venta-page" :class="isMobile ? 'q-pa-xs venta-page--mobile' : 'q-pa-md'">
    <div class="venta-shell">
      <div class="row" :class="isMobile ? 'q-col-gutter-xs' : 'q-col-gutter-md'">
        <div class="col-12 col-lg-7 venta-column venta-column--catalog">
          <q-card flat bordered class="panel-card">
            <q-card-section v-if="!isMobile" class="row items-center no-wrap q-col-gutter-sm">
              <div class="col">
                <div class="text-weight-bold text-h5">Vender joyas</div>
                <div class="text-caption text-grey-7">Selecciona una o varias joyas disponibles por vitrina, estuche, linea o nombre.</div>
              </div>
              <div class="col-auto">
                <div class="row no-wrap q-gutter-sm">
                  <q-btn color="primary" no-caps icon="refresh" label="Actualizar" :loading="loadingJoyas" @click="getJoyas" />
                  <q-btn-dropdown color="dark" no-caps icon="print" label="Impresiones">
                    <q-list dense>
                      <q-item clickable v-close-popup @click="openReportDialog('ventas_detalle')">
                        <q-item-section avatar><q-icon name="receipt_long" /></q-item-section>
                        <q-item-section>Detalle de ventas</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="openReportDialog('ventas_todas')">
                        <q-item-section avatar><q-icon name="list_alt" /></q-item-section>
                        <q-item-section>Todas las ventas</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="openReportDialog('inventario_movimientos')">
                        <q-item-section avatar><q-icon name="swap_horiz" /></q-item-section>
                        <q-item-section>Movimientos al inventario</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="openReportDialog('inventario_existencias')">
                        <q-item-section avatar><q-icon name="inventory_2" /></q-item-section>
                        <q-item-section>Existencias de inventario</q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </div>
              </div>
            </q-card-section>

            <q-separator v-if="!isMobile" />

            <q-card-section :class="isMobile ? 'q-pa-xs' : ''">
              <div class="row" :class="isMobile ? 'q-col-gutter-xs' : 'q-col-gutter-sm'">
                <div class="col-12 col-md-3">
                  <div class="row no-wrap items-center q-gutter-xs">
                    <q-input
                      v-model="filters.search"
                      class="col"
                      outlined
                      dense
                      clearable
                      debounce="350"
                      :label="isMobile ? 'Buscar joya / codigo' : 'Buscar joya'"
                    >
                      <template #append>
                        <q-icon name="search" />
                      </template>
                    </q-input>
                    <template v-if="isMobile">
                      <q-btn
                        dense flat round icon="tune"
                        :color="filtrosActivos ? 'primary' : 'grey-8'"
                        @click="showFiltros = !showFiltros"
                      >
                        <q-badge v-if="filtrosActivos" floating color="red" rounded>{{ filtrosActivos }}</q-badge>
                      </q-btn>
                      <q-btn dense flat round icon="refresh" color="primary" :loading="loadingJoyas" @click="getJoyas" />
                      <q-btn dense flat round icon="print" color="grey-8">
                        <q-menu>
                          <q-list dense>
                            <q-item clickable v-close-popup @click="openReportDialog('ventas_detalle')">
                              <q-item-section>Detalle de ventas</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup @click="openReportDialog('ventas_todas')">
                              <q-item-section>Todas las ventas</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup @click="openReportDialog('inventario_movimientos')">
                              <q-item-section>Movimientos al inventario</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup @click="openReportDialog('inventario_existencias')">
                              <q-item-section>Existencias de inventario</q-item-section>
                            </q-item>
                          </q-list>
                        </q-menu>
                      </q-btn>
                    </template>
                  </div>
                </div>
                <div v-show="!isMobile || showFiltros" class="col-4 col-md-3">
                  <q-select
                    v-model="filters.vitrina_id"
                    :options="vitrinaOptions"
                    option-label="label"
                    option-value="value"
                    emit-value
                    map-options
                    outlined
                    dense
                    clearable
                    label="Vitrina"
                  />
                </div>
                <div v-show="!isMobile || showFiltros" class="col-4 col-md-3">
                  <q-select
                    v-model="filters.estuche_id"
                    :options="estucheOptions"
                    option-label="label"
                    option-value="value"
                    emit-value
                    map-options
                    outlined
                    dense
                    clearable
                    label="Estuche"
                  />
                </div>
                <div v-show="!isMobile || showFiltros" class="col-4 col-md-3">
                  <q-select
                    v-model="filters.linea"
                    :options="lineaOptions"
                    emit-value
                    map-options
                    outlined
                    dense
                    label="Linea"
                  />
                </div>
              </div>
            </q-card-section>

            <q-card-section class="q-pt-none" :class="isMobile ? 'q-px-xs q-pb-xs' : ''">
              <div v-if="joyas.length" class="joyas-grid">
                <q-card
                  v-for="joya in joyas"
                  :key="joya.id"
                  flat
                  bordered
                  class="joya-card cursor-pointer"
                  :class="{ 'joya-card--active': form.joya_ids.includes(joya.id) }"
                  @click="selectJoya(joya)"
                >
                  <q-img :src="imagenUrl(joya.imagen)" class="joya-card__image" fit="cover">
                    <q-icon
                      v-if="form.joya_ids.includes(joya.id)"
                      name="check_circle"
                      color="primary"
                      size="22px"
                      class="joya-card__check"
                    />
                  </q-img>
                  <div class="joya-card__body">
                    <div class="joya-card__name text-weight-bold ellipsis-2-lines">{{ joya.nombre }}</div>
                    <div class="joya-card__meta text-primary">Cod. {{ joya.codigo || '-' }} · {{ joya.peso }} gr</div>
                    <div v-if="!isMobile" class="joya-card__meta text-grey-7 ellipsis">{{ joya.tipo }} {{ lineaLabel(joya.linea) }}</div>
                    <div v-if="!isMobile" class="joya-card__meta text-grey-7 ellipsis">{{ joya.vitrina_nombre || 'Sin vitrina' }} / {{ joya.estuche_nombre || 'Sin estuche' }}</div>
                    <div class="joya-card__price text-primary text-weight-bold">{{ money(joya.precio_referencial) }} Bs</div>
                  </div>
                </q-card>
              </div>

              <div v-else class="empty-block">
                <q-icon name="diamond" size="44px" color="grey-5" />
                <div class="text-subtitle2 q-mt-sm">No hay joyas disponibles para venta</div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section :class="isMobile ? 'q-pa-xs' : ''">
              <div class="row items-center justify-between q-col-gutter-sm">
                <div v-if="!isMobile" class="col-12 col-sm-auto">
                  <div class="row items-center q-gutter-sm">
                    <div class="text-caption text-grey-7">Por pagina</div>
                    <q-select
                      v-model="joyasPagination.rowsPerPage"
                      :options="joyasPerPageOptions"
                      outlined
                      dense
                      emit-value
                      map-options
                      style="width: 110px"
                      @update:model-value="onChangeJoyasPerPage"
                    />
                    <div class="text-caption text-grey-7">
                      Mostrando {{ joyas.length }} de {{ joyasPagination.rowsNumber }} joyas
                    </div>
                  </div>
                </div>
                <div class="col-12 col-sm-auto row justify-center">
                  <q-pagination
                    v-model="joyasPagination.page"
                    :max="joyasPagination.lastPage"
                    :max-pages="isMobile ? 4 : 6"
                    :size="isMobile ? 'sm' : 'md'"
                    boundary-numbers
                    direction-links
                    @update:model-value="getJoyas"
                  />
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div ref="formColumn" class="col-12 col-lg-5 venta-column venta-column--form">
          <q-card flat bordered class="panel-card">
            <q-card-section v-if="!isMobile">
              <div class="text-weight-bold text-h6">Datos de la venta</div>
            </q-card-section>
            <q-separator v-if="!isMobile" />

            <q-card-section v-if="selectedVentaJoyas.length" class="q-pb-none" :class="isMobile ? 'q-pa-xs' : ''">
              <div class="row items-center" :class="isMobile ? 'q-mb-xs' : 'q-mb-sm'">
                <div class="text-weight-bold" :class="isMobile ? 'text-subtitle2' : 'text-subtitle1'">Joyas seleccionadas</div>
                <q-space />
                <q-chip dense color="primary" text-color="white">{{ selectedVentaJoyas.length }}</q-chip>
              </div>
              <div class="selected-joyas-list">
                <div v-for="item in selectedVentaJoyas" :key="`sel-${item.joya.id}`" class="selected-joya selected-joya--stacked">
                  <div class="selected-joya__top">
                    <q-img :src="imagenUrl(item.joya.imagen)" class="selected-joya__image" fit="cover" />
                    <div class="col" style="min-width: 0">
                      <div class="text-weight-bold ellipsis" :class="isMobile ? 'text-caption' : 'text-subtitle2'">{{ item.joya.nombre }}</div>
                      <div class="text-caption text-primary">Cod. {{ item.joya.codigo || '-' }}<span v-if="isMobile"> · {{ item.joya.peso }} gr</span></div>
                      <template v-if="!isMobile">
                        <div class="text-caption text-grey-7">{{ item.joya.tipo }}  {{ lineaLabel(item.joya.linea) }}</div>
                        <div class="text-caption text-grey-7">{{ item.joya.vitrina_nombre || 'Sin vitrina' }} / {{ item.joya.estuche_nombre || 'Sin estuche' }}</div>
                      </template>
                    </div>
                    <div class="text-right">
                      <div v-if="!isMobile" class="text-caption text-grey-7">{{ item.joya.peso }} gr</div>
                      <div class="row items-center justify-end no-wrap q-gutter-xs q-mt-xs">
                        <div class="text-subtitle2 text-primary text-weight-bold">{{ money(joyaMontoVenta(item.joya)) }} Bs</div>
                        <q-btn
                          flat
                          round
                          dense
                          color="negative"
                          icon="delete"
                          @click="removeSelectedJoya(item.joya.id)"
                        >
                          <q-tooltip>Quitar de la seleccion</q-tooltip>
                        </q-btn>
                      </div>
                    </div>
                  </div>
                  <div class="row q-col-gutter-xs" :class="isMobile ? '' : 'q-mt-xs'">
                    <div class="col-4 col-md-4">
                      <q-input
                        :model-value="item.venta.costo_total"
                        label="Monto"
                        type="number"
                        outlined
                        dense
                        bg-color="white"
                        :hide-bottom-space="isMobile"
                        :error="ventaMontoMenorReferencia(item)"
                        :error-message="ventaMontoMenorReferencia(item) ? mensajeMontoMinimo(item.joya) : ''"
                        @update:model-value="updateVentaItem(item.joya.id, 'costo_total', $event)"
                      >
                        <template v-if="!isMobile" #append>
                          <q-btn flat round dense icon="restart_alt" @click="restoreVentaItemReferential(item.joya.id)">
                            <q-tooltip>Usar monto de joya</q-tooltip>
                          </q-btn>
                        </template>
                        <template v-if="!isMobile" #hint>
                          Monto venta sugerido: {{ money(joyaMontoVenta(item.joya)) }} Bs
                        </template>
                      </q-input>
                    </div>
                    <div class="col-4 col-md-3">
                      <q-input :model-value="item.venta.adelanto_efectivo" :label="isMobile ? 'Efectivo' : 'Monto efectivo'" type="number" min="0" outlined dense bg-color="white" :hide-bottom-space="isMobile" :hint="isMobile ? undefined : `Cobrado: ${money(item.venta.adelanto)} Bs`" @update:model-value="updateVentaItem(item.joya.id, 'adelanto_efectivo', $event)" />
                    </div>
                    <div class="col-4 col-md-3">
                      <q-input :model-value="item.venta.adelanto_qr" :label="isMobile ? 'QR' : 'Monto QR'" type="number" min="0" outlined dense bg-color="white" :hide-bottom-space="isMobile" @update:model-value="updateVentaItem(item.joya.id, 'adelanto_qr', $event)" />
                    </div>
                    <div v-if="isMobile" class="col-12 text-caption text-grey-8">
                      Cobrado {{ money(item.venta.adelanto) }} · Saldo <b>{{ money(itemSaldo(item.venta)) }}</b> · {{ itemEstado(item.venta) }}
                    </div>
                    <div v-else class="col-6 col-md-2">
                      <div class="selected-joya__saldo">
                        <div class="text-caption text-grey-7">Saldo</div>
                        <div class="text-weight-bold">{{ money(itemSaldo(item.venta)) }}</div>
                        <div class="text-caption">{{ itemEstado(item.venta) }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="isMobile" class="mobile-resumen row no-wrap justify-between q-mt-xs">
                <div>Total <b>{{ money(totalVenta) }}</b></div>
                <div>Cobrado <b>{{ money(totalAdelanto) }}</b></div>
                <div>Saldo <b>{{ money(totalSaldo) }}</b></div>
              </div>
              <q-banner v-else class="bg-blue-1 text-blue-10 q-mt-sm rounded-borders">
                <div class="text-body2 text-weight-bold">Resumen general</div>
                <div class="text-caption q-mb-xs">
                  {{ selectedVentaJoyas.length }} joya(s), peso total {{ money(form.peso) }} gr y precio referencial {{ money(referentialTotal) }} Bs.
                </div>
                <div class="row q-col-gutter-xs">
                  <div class="col-4">
                    <div class="summary-total-card">
                      <div class="text-caption text-blue-8">Total venta</div>
                      <div class="text-weight-bold" :class="isMobile ? 'text-body2' : 'text-h6'">{{ money(totalVenta) }} Bs</div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="summary-total-card">
                      <div class="text-caption text-blue-8">Adelanto</div>
                      <div class="text-weight-bold" :class="isMobile ? 'text-body2' : 'text-h6'">{{ money(totalAdelanto) }} Bs</div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="summary-total-card">
                      <div class="text-caption text-blue-8">Saldo</div>
                      <div class="text-weight-bold" :class="isMobile ? 'text-body2' : 'text-h6'">{{ money(totalSaldo) }} Bs</div>
                    </div>
                  </div>
                </div>
              </q-banner>
            </q-card-section>

            <q-card-section :class="isMobile ? 'q-pa-xs' : ''">
              <q-form @submit.prevent="guardarVenta">
                <div v-if="!isMobile" class="text-subtitle2 text-weight-bold q-mb-xs">Cliente</div>

                <div v-if="form.cliente" class="selected-client row items-center no-wrap">
                  <q-icon name="person" color="primary" size="20px" class="q-mr-sm" />
                  <div class="col ellipsis">
                    <b>{{ form.cliente.name }}</b>
                    <div class="text-caption text-grey-7">CI {{ form.cliente.ci }}</div>
                  </div>
                  <q-btn flat dense no-caps color="primary" icon="swap_horiz" label="Cambiar" @click="cambiarCliente" />
                </div>

                <template v-else>
                  <div class="row no-wrap q-gutter-xs items-center">
                    <q-input
                      v-model="clienteFiltro"
                      class="col"
                      outlined
                      dense
                      clearable
                      debounce="350"
                      label="Buscar cliente"
                      @update:model-value="getClientes"
                    >
                      <template #append><q-icon name="search" /></template>
                    </q-input>
                    <q-btn color="positive" no-caps dense icon="person_add" :label="isMobile ? undefined : 'Nuevo'" class="client-new-btn" @click="openClientDialog" />
                  </div>

                  <q-list bordered separator dense class="q-mt-xs client-list">
                    <q-item v-for="cliente in clientes" :key="cliente.id" clickable dense @click="seleccionarCliente(cliente)">
                      <q-item-section>
                        <q-item-label class="ellipsis">{{ cliente.name }}</q-item-label>
                        <q-item-label caption>{{ cliente.ci }} · {{ cliente.cellphone || 'Sin celular' }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <q-badge :color="cliente.status === 'Confiable' ? 'green' : cliente.status === 'No Confiable' ? 'red' : 'orange'">
                          {{ cliente.status }}
                        </q-badge>
                      </q-item-section>
                    </q-item>
                    <q-item v-if="!clientes.length" dense>
                      <q-item-section class="text-caption text-grey-7">Sin resultados</q-item-section>
                    </q-item>
                  </q-list>
                </template>

                <div v-if="!isMobile" class="text-subtitle2 text-weight-bold q-mt-sm q-mb-xs">Venta</div>
                <div class="row q-col-gutter-xs" :class="{ 'q-mt-xs': isMobile }">
                  <div class="col-5 col-md-4">
                    <q-input v-model="form.fecha_entrega" type="date" label="Fecha" outlined dense />
                  </div>
                  <div class="col-4 col-md-4">
                    <q-input v-model="form.celular" label="Celular" outlined dense />
                  </div>
                  <div class="col-3 col-md-4">
                    <q-input :model-value="money(form.peso)" :label="isMobile ? 'Gr' : 'Peso total'" outlined dense readonly />
                  </div>
                  <div class="col-12">
                    <q-expansion-item
                      v-if="isMobile"
                      dense dense-toggle
                      icon="notes"
                      label="Detalle"
                      header-class="text-caption q-px-xs"
                    >
                      <q-input v-model="form.detalle" type="textarea" outlined dense autogrow />
                    </q-expansion-item>
                    <q-input v-else v-model="form.detalle" type="textarea" label="Detalle base" outlined dense autogrow />
                  </div>
                </div>

                <div class="row q-mt-sm q-gutter-xs" :class="isMobile ? 'no-wrap' : 'justify-end'">
                  <q-btn flat color="negative" no-caps label="Cancelar" :class="{ col: isMobile }" @click="$router.push('/ventas-joyas')" :loading="saving" />
                  <q-btn color="primary" no-caps icon="save" label="Guardar venta" type="submit" :class="{ col: isMobile }" :loading="saving" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Barra inferior en movil: resumen y acceso rapido al formulario -->
    <q-page-sticky v-if="isMobile && selectedVentaJoyas.length" position="bottom" expand class="mobile-bar-wrap">
      <div class="mobile-bar row items-center no-wrap full-width">
        <div class="col">
          <div class="text-caption">{{ selectedVentaJoyas.length }} joya(s)</div>
          <div class="text-subtitle2 text-weight-bold">{{ money(totalVenta) }} Bs</div>
        </div>
        <q-btn color="white" text-color="primary" no-caps unelevated icon-right="arrow_downward" label="Continuar" @click="irAFormulario" />
      </div>
    </q-page-sticky>

    <q-dialog v-model="clientDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-sm">
          <div class="text-h6">Nuevo cliente</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="clientDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit.prevent="crearClienteRapido">
            <q-input v-model="clientForm.name" label="Nombre" outlined dense :rules="[val => !!val || 'Campo requerido']" @update:model-value="clientForm.name = upper(clientForm.name)" />
            <q-input v-model="clientForm.ci" label="CI" outlined dense class="q-mt-sm" :rules="[val => !!val || 'Campo requerido']" @update:model-value="clientForm.ci = upper(clientForm.ci)" />
            <q-select v-model="clientForm.status" :options="statuses" label="Estado" outlined dense class="q-mt-sm" />
            <q-input v-model="clientForm.cellphone" label="Celular" outlined dense class="q-mt-sm" />

            <div class="text-right q-mt-md">
              <q-btn flat color="negative" no-caps label="Cancelar" @click="clientDialog = false" :loading="clientLoading" />
              <q-btn color="positive" no-caps label="Guardar" type="submit" class="q-ml-sm" :loading="clientLoading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="reportDialog" persistent>
      <q-card style="width: 560px; max-width: 96vw;">
        <q-card-section class="row items-center q-pb-sm">
          <div class="text-h6">Impresiones</div>
          <q-space />
          <q-btn flat round dense icon="close" :disable="reportLoading" @click="reportDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-sm">
            <div class="col-12">
              <q-select
                v-model="reportForm.type"
                :options="reportOptions"
                emit-value
                map-options
                outlined
                dense
                label="Tipo de impresion"
              />
            </div>
            <div v-if="reportUsesDates" class="col-12 col-sm-6">
              <q-input v-model="reportForm.fecha_inicio" type="date" outlined dense label="Fecha inicio" />
            </div>
            <div v-if="reportUsesDates" class="col-12 col-sm-6">
              <q-input v-model="reportForm.fecha_fin" type="date" outlined dense label="Fecha final" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="reportForm.linea"
                :options="lineaOptions"
                emit-value
                map-options
                outlined
                dense
                label="Linea"
              />
            </div>
            <div v-if="reportUsesEstuche" class="col-12 col-sm-6">
              <q-select
                v-model="reportForm.estuche_id"
                :options="reportEstucheOptions"
                emit-value
                map-options
                use-input
                fill-input
                input-debounce="0"
                @filter="filterReportEstuches"
                clearable
                outlined
                dense
                label="Estuche"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat color="negative" no-caps label="Cancelar" :disable="reportLoading" @click="reportDialog = false" />
          <q-btn color="primary" no-caps icon="print" label="Imprimir" :loading="reportLoading" :disable="reportLoading" @click="imprimirReporte" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'VentaJoyaCrearPage',
  data () {
    return {
      loadingJoyas: false,
      saving: false,
      reportLoading: false,
      clientLoading: false,
      reportEstucheOptions: [],
      joyas: [],
      joyasIndex: {},
      vitrinas: [],
      clientes: [],
      clienteFiltro: '',
      clientDialog: false,
      reportDialog: false,
      showFiltros: false,
      filters: {
        vitrina_id: null,
        estuche_id: null,
        linea: null,
        search: ''
      },
      form: {
        joya_id: null,
        joya_ids: [],
        cliente_id: null,
        cliente: null,
        fecha_entrega: moment().format('YYYY-MM-DD'),
        celular: '',
        peso: 0,
        detalle: ''
      },
      ventaItems: [],
      reportForm: {
        type: 'ventas_detalle',
        fecha_inicio: moment().startOf('month').format('YYYY-MM-DD'),
        fecha_fin: moment().endOf('month').format('YYYY-MM-DD'),
        linea: null,
        estuche_id: null
      },
      joyasPagination: {
        page: 1,
        rowsPerPage: 18,
        rowsNumber: 0,
        lastPage: 1
      },
      joyasPerPageOptions: [12, 18, 24, 36],
      clientForm: {
        name: '',
        ci: '',
        status: 'Confiable',
        cellphone: '',
        address: '',
        observation: ''
      },
      statuses: ['Confiable', 'No Confiable', 'VIP']
    }
  },
  computed: {
    isMobile () {
      return this.$q.screen.lt.md
    },
    filtrosActivos () {
      return [this.filters.vitrina_id, this.filters.estuche_id, this.filters.linea].filter(v => v !== null && v !== undefined && v !== '').length
    },
    vitrinaOptions () {
      return this.vitrinas.map(vitrina => ({ label: vitrina.nombre, value: vitrina.id }))
    },
    lineaOptions () {
      return [
        { label: 'Todos', value: null },
        { label: 'Mama', value: 'Mama' },
        { label: 'Papa', value: 'Papa' },
        { label: 'Roger', value: 'Roger' },
        { label: 'Reina', value: 'Andreina' }
      ]
    },
    estucheOptions () {
      return this.vitrinas
        .filter(vitrina => !this.filters.vitrina_id || vitrina.id === this.filters.vitrina_id)
        .flatMap(vitrina => (vitrina.columnas || []).flatMap(columna => (columna.estuches || []).map(estuche => ({
          label: `${vitrina.nombre} / ${columna.codigo} / ${estuche.nombre}`,
          value: estuche.id
        }))))
    },
    estucheOptionsFull () {
      return this.vitrinas.flatMap(vitrina => (vitrina.columnas || []).flatMap(columna => (columna.estuches || []).map(estuche => ({
        label: `${vitrina.nombre} / ${columna.codigo} / ${estuche.nombre}`,
        value: estuche.id
      }))))
    },
    reportOptions () {
      return [
        { label: 'Detalle de ventas', value: 'ventas_detalle' },
        { label: 'Todas las ventas', value: 'ventas_todas' },
        { label: 'Movimientos al inventario', value: 'inventario_movimientos' },
        { label: 'Existencias de inventario', value: 'inventario_existencias' }
      ]
    },
    reportUsesDates () {
      return this.reportForm.type !== 'inventario_existencias'
    },
    reportUsesEstuche () {
      return this.reportForm.type === 'inventario_movimientos' || this.reportForm.type === 'inventario_existencias'
    },
    selectedJoyas () {
      return this.form.joya_ids
        .map(id => this.joyasIndex[id])
        .filter(Boolean)
    },
    selectedVentaJoyas () {
      return this.selectedJoyas.map(joya => ({
        joya,
        venta: this.getVentaItem(joya.id)
      })).filter(item => item.venta)
    },
    referentialTotal () {
      return this.selectedJoyas.reduce((sum, joya) => sum + Number(joya.precio_referencial || 0), 0)
    },
    totalVenta () {
      return this.ventaItems.reduce((sum, item) => sum + Number(item.costo_total || 0), 0)
    },
    totalAdelanto () {
      return this.ventaItems.reduce((sum, item) => sum + Number(item.adelanto || 0), 0)
    },
    totalSaldo () {
      return this.ventaItems.reduce((sum, item) => sum + this.itemSaldo(item), 0)
    }
  },
  mounted () {
    this.getVitrinas()
    this.getJoyas()
    this.getClientes()
  },
  methods: {
    upper (value) {
      return (value || '').toUpperCase()
    },
    money (value) {
      return Number(value || 0).toFixed(2)
    },
    toNumber (value) {
      const parsed = Number(value)
      return Number.isFinite(parsed) ? parsed : 0
    },
    imagenUrl (imagen) {
      return `${this.$url}/../images/${imagen || 'joya.png'}`
    },
    lineaLabel (value) {
      return value === 'Andreina' ? 'Reina' : (value || 'Sin linea')
    },
    itemSaldo (venta) {
      return Math.max(0, this.toNumber(venta?.costo_total) - this.toNumber(venta?.adelanto))
    },
    itemEstado (venta) {
      return this.itemSaldo(venta) <= 0 ? 'Entregado' : 'Pendiente'
    },
    joyaMontoVenta (joya) {
      const monto = this.toNumber(joya?.monto_bs)
      return monto > 0 ? monto : this.toNumber(joya?.precio_referencial)
    },
    ventaMontoMenorReferencia (item) {
      return this.toNumber(item?.venta?.costo_total) < this.joyaMontoVenta(item?.joya)
    },
    mensajeMontoMinimo (joya) {
      return `El monto no puede ser menor a ${this.money(this.joyaMontoVenta(joya))} Bs.`
    },
    buildVentaItem (joya, previous = null) {
      if (previous) return { ...previous }
      const referencia = this.joyaMontoVenta(joya)
      return this.withPagoCompleto({ joya_id: joya.id }, referencia)
    },
    // Venta cobrada completa en efectivo (se puede repartir luego con QR)
    withPagoCompleto (item, monto) {
      return {
        ...item,
        costo_total: monto,
        adelanto: monto,
        adelanto_efectivo: monto,
        adelanto_qr: 0
      }
    },
    getVentaItem (joyaId) {
      return this.ventaItems.find(item => item.joya_id === joyaId) || null
    },
    openReportDialog (type) {
      this.reportForm.type = type
      this.reportEstucheOptions = this.estucheOptionsFull
      this.reportDialog = true
    },
    filterReportEstuches (value, update) {
      const needle = (value || '').toLowerCase().trim()
      update(() => {
        this.reportEstucheOptions = !needle
          ? this.estucheOptionsFull
          : this.estucheOptionsFull.filter(option => option.label.toLowerCase().includes(needle))
      })
    },
    getVitrinas () {
      this.$axios.get('vitrinas').then(({ data }) => {
        this.vitrinas = data
        this.reportEstucheOptions = this.estucheOptionsFull
      })
    },
    getJoyas () {
      this.loadingJoyas = true
      this.$axios.get('ordenes/joyas-disponibles', {
        params: {
          ...this.filters,
          page: this.joyasPagination.page,
          per_page: this.joyasPagination.rowsPerPage
        }
      }).then(({ data }) => {
        this.joyas = data.data || []
        const nextIndex = { ...this.joyasIndex }
        this.joyas.forEach(joya => {
          nextIndex[joya.id] = joya
        })
        this.joyasIndex = nextIndex
        this.joyasPagination.page = data.current_page || 1
        this.joyasPagination.rowsPerPage = data.per_page || this.joyasPagination.rowsPerPage
        this.joyasPagination.rowsNumber = data.total || this.joyas.length
        this.joyasPagination.lastPage = data.last_page || 1
        this.form.joya_id = this.form.joya_ids[0] || null
        this.syncSelectedJoyas()
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al cargar joyas')
      }).finally(() => {
        this.loadingJoyas = false
      })
    },
    onChangeJoyasPerPage () {
      this.joyasPagination.page = 1
      this.getJoyas()
    },
    selectJoya (joya) {
      this.joyasIndex = {
        ...this.joyasIndex,
        [joya.id]: joya
      }
      if (this.form.joya_ids.includes(joya.id)) {
        this.form.joya_ids = this.form.joya_ids.filter(id => id !== joya.id)
      } else {
        this.form.joya_ids = [...this.form.joya_ids, joya.id]
      }
      this.form.joya_id = this.form.joya_ids[0] || null
      this.syncSelectedJoyas()
    },
    removeSelectedJoya (joyaId) {
      this.form.joya_ids = this.form.joya_ids.filter(id => id !== joyaId)
      this.form.joya_id = this.form.joya_ids[0] || null
      this.syncSelectedJoyas()
    },
    syncSelectedJoyas () {
      if (!this.selectedJoyas.length) {
        this.form.peso = 0
        this.form.detalle = ''
        this.ventaItems = []
        return
      }
      const existentes = new Map(this.ventaItems.map(item => [item.joya_id, item]))
      this.ventaItems = this.selectedJoyas.map(joya => this.buildVentaItem(joya, existentes.get(joya.id)))
      const pesoTotal = this.selectedJoyas.reduce((sum, joya) => sum + Number(joya.peso || 0), 0)
      this.form.peso = Number(pesoTotal.toFixed(2))
      this.form.detalle = `VENTA DIRECTA: ${this.selectedJoyas.map(joya => `${joya.nombre} | ${joya.tipo} | ${joya.peso} GR`).join(' || ')}`
    },
    updateVentaItem (joyaId, field, value) {
      this.ventaItems = this.ventaItems.map(item => {
        if (item.joya_id !== joyaId) return item

        if (field === 'costo_total') {
          const monto = this.toNumber(value)
          const joya = this.selectedJoyas.find(selected => selected.id === joyaId)
          const referencia = this.joyaMontoVenta(joya)

          if (monto < referencia) {
            this.$alert.warning(this.mensajeMontoMinimo(joya))
            return this.withPagoCompleto(item, referencia)
          }

          return this.withPagoCompleto(item, monto)
        }

        const next = {
          ...item,
          [field]: Math.max(0, this.toNumber(value))
        }
        next.adelanto = this.toNumber(next.adelanto_efectivo) + this.toNumber(next.adelanto_qr)
        return next
      })
    },
    restoreVentaItemReferential (joyaId) {
      const joya = this.selectedJoyas.find(item => item.id === joyaId)
      if (!joya) return
      const referencia = this.joyaMontoVenta(joya)
      this.ventaItems = this.ventaItems.map(item => item.joya_id === joyaId
        ? this.withPagoCompleto(item, referencia)
        : item)
    },
    getClientes () {
      this.$axios.get('clients', {
        params: {
          search: this.clienteFiltro,
          page: 1,
          per_page: 6
        }
      }).then(({ data }) => {
        this.clientes = data.data || data
      })
    },
    seleccionarCliente (cliente) {
      this.form.cliente = cliente
      this.form.cliente_id = cliente.id
      this.form.celular = cliente.cellphone || ''
    },
    cambiarCliente () {
      this.form.cliente = null
      this.form.cliente_id = null
    },
    irAFormulario () {
      this.$refs.formColumn?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    },
    openClientDialog () {
      this.clientForm = {
        name: '',
        ci: '',
        status: 'Confiable',
        cellphone: '',
        address: '',
        observation: ''
      }
      this.clientDialog = true
    },
    crearClienteRapido () {
      this.clientLoading = true
      this.$axios.post('clients', {
        ...this.clientForm,
        name: this.upper(this.clientForm.name),
        ci: this.upper(this.clientForm.ci),
        address: this.upper(this.clientForm.address),
        observation: this.upper(this.clientForm.observation)
      }).then(({ data }) => {
        this.clientDialog = false
        this.seleccionarCliente(data)
        this.getClientes()
        this.$alert.success('Cliente creado y seleccionado')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al crear cliente')
      }).finally(() => {
        this.clientLoading = false
      })
    },
    async descargarPdf (url, params = {}) {
      const response = await this.$axios.get(url, {
        params,
        responseType: 'blob'
      })
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const fileName = response.headers['content-disposition']?.match(/filename=\"?([^\"]+)\"?/)?.[1] || 'reporte.pdf'
      const href = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = href
      link.download = fileName
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(href)
    },
    async imprimirReporte () {
      this.reportLoading = true
      try {
        const routeMap = {
          ventas_detalle: 'reportes/ventas/pdf',
          ventas_todas: 'reportes/ventas/pdf',
          inventario_movimientos: 'reportes/inventario/movimientos/pdf',
          inventario_existencias: 'reportes/inventario/existencias/pdf'
        }

        await this.descargarPdf(routeMap[this.reportForm.type], {
          fecha_inicio: this.reportUsesDates ? this.reportForm.fecha_inicio : null,
          fecha_fin: this.reportUsesDates ? this.reportForm.fecha_fin : null,
          linea: this.reportForm.linea,
          estuche_id: this.reportUsesEstuche ? this.reportForm.estuche_id : null,
          tipo_reporte: this.reportForm.type === 'ventas_todas' ? 'todas' : 'detalle'
        })
        this.reportDialog = false
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'Error al imprimir el reporte')
      } finally {
        this.reportLoading = false
      }
    },
    guardarVenta () {
      if (!this.form.joya_ids.length) {
        this.$alert.error('Debe seleccionar al menos una joya')
        return
      }

      if (!this.form.cliente_id) {
        this.$alert.error('Debe seleccionar un cliente')
        return
      }

      if (this.ventaItems.some(item => this.toNumber(item.costo_total) <= 0)) {
        this.$alert.error('Cada joya debe tener un monto mayor a cero')
        return
      }

      const ventaInvalida = this.selectedVentaJoyas.find(item => this.ventaMontoMenorReferencia(item))
      if (ventaInvalida) {
        this.$alert.error(this.mensajeMontoMinimo(ventaInvalida.joya))
        return
      }

      if (this.ventaItems.some(item => this.toNumber(item.adelanto) > this.toNumber(item.costo_total) + 0.005)) {
        this.$alert.error('Monto efectivo + monto QR no puede superar el monto de la venta')
        return
      }

      this.saving = true
      this.$axios.post('ordenes', {
        cliente_id: this.form.cliente_id,
        celular: this.form.celular,
        fecha_entrega: this.form.fecha_entrega,
        detalle: this.form.detalle,
        tipo: 'Venta directa',
        ventas: this.ventaItems.map(item => ({
          joya_id: item.joya_id,
          costo_total: this.toNumber(item.costo_total),
          adelanto: this.toNumber(item.adelanto),
          adelanto_efectivo: this.toNumber(item.adelanto_efectivo),
          adelanto_qr: this.toNumber(item.adelanto_qr),
          tipo_pago: this.toNumber(item.adelanto_qr) > this.toNumber(item.adelanto_efectivo) ? 'QR' : 'Efectivo'
        }))
      }).then(() => {
        this.$alert.success('Venta registrada correctamente')
        this.$router.push('/ventas-joyas')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al registrar la venta')
      }).finally(() => {
        this.saving = false
      })
    }
  },
  watch: {
    'filters.vitrina_id' () {
      this.filters.estuche_id = null
      this.joyasPagination.page = 1
      this.getJoyas()
    },
    'filters.estuche_id' () {
      this.joyasPagination.page = 1
      this.getJoyas()
    },
    'filters.linea' () {
      this.joyasPagination.page = 1
      this.getJoyas()
    },
    'filters.search' () {
      this.joyasPagination.page = 1
      this.getJoyas()
    }
  }
}
</script>

<style scoped>
.venta-page {
  background: linear-gradient(180deg, #e8f0f4 0%, #f5f1ea 100%);
  min-height: 100%;
}

.venta-shell {
  max-width: 1600px;
  margin: 0 auto;
}

.venta-page--mobile {
  padding-bottom: 72px !important;
}

.panel-card {
  border-radius: 22px;
  box-shadow: 0 22px 50px rgba(31, 63, 82, 0.12);
}

.joyas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.joya-card {
  border-radius: 18px;
  overflow: hidden;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}

.joya-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
}

.joya-card--active {
  border-color: #1976d2;
  box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.12);
}

.joya-card__image {
  height: 118px;
}

.joya-card__check {
  position: absolute;
  top: 4px;
  right: 4px;
  background: #fff;
  border-radius: 50%;
}

.joya-card__body {
  padding: 8px;
  line-height: 1.25;
}

.joya-card__name {
  font-size: 13px;
}

.joya-card__meta {
  font-size: 11px;
}

.joya-card__price {
  font-size: 14px;
  margin-top: 2px;
}

.client-new-btn {
  height: 40px;
}

.mobile-bar-wrap {
  z-index: 10;
}

.mobile-bar {
  background: #1976d2;
  color: #fff;
  padding: 8px 12px;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.15);
}

.selected-joyas-list {
  display: grid;
  gap: 10px;
}

.selected-joya {
  padding: 10px 12px;
  border-radius: 14px;
  background: #f7fafc;
}

.selected-joya--stacked {
  display: grid;
  gap: 8px;
}

.selected-joya__top {
  display: grid;
  grid-template-columns: 92px 1fr auto;
  gap: 12px;
  align-items: center;
}

.selected-joya__image {
  border-radius: 16px;
  height: 92px;
}

.selected-joya__saldo {
  height: 100%;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(25, 118, 210, 0.12);
}
.selected-client {
  padding: 6px 10px;
  border-radius: 12px;
  background: #f3f7f9;
}

.summary-total-card {
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(25, 118, 210, 0.18);
}

.client-list {
  max-height: 180px;
  overflow: auto;
  border-radius: 14px;
}

.empty-block {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Movil: catalogo compacto en 3 columnas */
@media (max-width: 1023px) {
  .panel-card {
    border-radius: 10px;
    box-shadow: none;
  }

  .joyas-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 4px;
  }

  .joya-card {
    border-radius: 8px;
  }

  .joya-card:hover {
    transform: none;
  }

  .joya-card__image {
    height: 70px;
  }

  .joya-card__body {
    padding: 3px 5px 4px;
    line-height: 1.15;
  }

  .joya-card__name {
    font-size: 10.5px;
  }

  .joya-card__meta {
    font-size: 9.5px;
  }

  .joya-card__price {
    font-size: 11.5px;
    margin-top: 1px;
  }

  .selected-joyas-list {
    gap: 4px;
  }

  .selected-joya {
    padding: 5px 6px;
    border-radius: 10px;
  }

  .selected-joya--stacked {
    gap: 4px;
  }

  .selected-joya__top {
    grid-template-columns: 40px 1fr auto;
    gap: 6px;
  }

  .selected-joya__image {
    height: 40px;
    border-radius: 8px;
  }

  .mobile-resumen {
    font-size: 12px;
    padding: 5px 8px;
    border-radius: 8px;
    background: #e3f2fd;
    color: #0d47a1;
  }

  .selected-client {
    padding: 4px 8px;
  }

  .client-list {
    max-height: 150px;
  }

  /* Campos mas bajos en movil */
  .venta-page--mobile :deep(.q-field--dense .q-field__control),
  .venta-page--mobile :deep(.q-field--dense .q-field__marginal) {
    height: 36px;
    min-height: 36px;
  }

  .venta-page--mobile :deep(.q-field--dense.q-textarea .q-field__control) {
    height: auto;
  }

  .venta-page--mobile :deep(.q-field--dense .q-field__native),
  .venta-page--mobile :deep(.q-field--dense .q-field__input) {
    font-size: 13px;
  }

  .selected-joya__saldo,
  .summary-total-card {
    padding: 4px 8px;
  }

  .empty-block {
    min-height: 160px;
  }
}

/* Tablet / movil horizontal: mas columnas */
@media (min-width: 600px) and (max-width: 1023px) {
  .joyas-grid {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}

@media (max-width: 330px) {
  .joyas-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>



