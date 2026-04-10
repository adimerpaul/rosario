<template>
  <q-page class="venta-page q-pa-md">
    <div class="venta-shell">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-lg-7 venta-column venta-column--catalog">
          <q-card flat bordered class="panel-card">
            <q-card-section class="row items-center q-col-gutter-sm">
              <div class="col-12 col-md">
                <div class="text-h5 text-weight-bold">Vender joyas</div>
                <div class="text-caption text-grey-7">Selecciona una o varias joyas disponibles por vitrina, estuche, linea o nombre.</div>
              </div>
              <div class="col-12 col-md-auto">
                <div class="row q-gutter-sm">
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

            <q-separator />

            <q-card-section>
              <div class="row q-col-gutter-sm">
                <div class="col-12 col-md-3">
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
                <div class="col-12 col-md-3">
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
                <div class="col-12 col-md-3">
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
                <div class="col-12 col-md-3">
                  <q-input
                    v-model="filters.search"
                    outlined
                    dense
                    clearable
                    debounce="350"
                    label="Buscar joya"
                  >
                    <template #append>
                      <q-icon name="search" />
                    </template>
                  </q-input>
                </div>
              </div>
            </q-card-section>

            <q-card-section class="q-pt-none">
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
                  <q-img :src="imagenUrl(joya.imagen)" class="joya-card__image" fit="cover" />
                  <q-card-section class="q-pa-sm">
                    <div class="text-body2 text-weight-bold ellipsis-2-lines">{{ joya.nombre }}</div>
                    <div class="text-caption text-primary">Cod. {{ joya.codigo || '-' }}</div>
                    <div class="text-caption text-grey-7">{{ joya.tipo }}  {{ lineaLabel(joya.linea) }}</div>
                    <div class="text-caption text-grey-7">{{ joya.peso }} gr</div>
                    <div class="text-caption text-grey-7 ellipsis">{{ joya.vitrina_nombre || 'Sin vitrina' }} / {{ joya.estuche_nombre || 'Sin estuche' }}</div>
                    <div class="text-subtitle2 text-primary text-weight-bold q-mt-xs">{{ money(joya.precio_referencial) }} Bs</div>
                  </q-card-section>
                </q-card>
              </div>

              <div v-else class="empty-block">
                <q-icon name="diamond" size="44px" color="grey-5" />
                <div class="text-subtitle2 q-mt-sm">No hay joyas disponibles para venta</div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section>
              <div class="row items-center justify-between q-col-gutter-sm">
                <div class="col-12 col-sm-auto">
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
                <div class="col-12 col-sm-auto">
                  <q-pagination
                    v-model="joyasPagination.page"
                    :max="joyasPagination.lastPage"
                    :max-pages="6"
                    boundary-numbers
                    direction-links
                    @update:model-value="getJoyas"
                  />
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-lg-5 venta-column venta-column--form">
          <q-card flat bordered class="panel-card">
            <q-card-section>
              <div class="text-h6 text-weight-bold">Datos de la venta</div>
            </q-card-section>
            <q-separator />

            <q-card-section v-if="selectedVentaJoyas.length" class="q-pb-none">
              <div class="row items-center q-mb-sm">
                <div class="text-subtitle1 text-weight-bold">Joyas seleccionadas</div>
                <q-space />
                <q-chip dense color="primary" text-color="white">{{ selectedVentaJoyas.length }}</q-chip>
              </div>
              <div class="selected-joyas-list">
                <div v-for="item in selectedVentaJoyas" :key="`sel-${item.joya.id}`" class="selected-joya selected-joya--stacked">
                  <div class="selected-joya__top">
                    <q-img :src="imagenUrl(item.joya.imagen)" class="selected-joya__image" fit="cover" />
                    <div class="col">
                      <div class="text-subtitle2 text-weight-bold">{{ item.joya.nombre }}</div>
                      <div class="text-caption text-primary">Cod. {{ item.joya.codigo || '-' }}</div>
                      <div class="text-caption text-grey-7">{{ item.joya.tipo }}  {{ lineaLabel(item.joya.linea) }}</div>
                      <div class="text-caption text-grey-7">{{ item.joya.vitrina_nombre || 'Sin vitrina' }} / {{ item.joya.estuche_nombre || 'Sin estuche' }}</div>
                    </div>
                    <div class="text-right">
                      <div class="text-caption text-grey-7">{{ item.joya.peso }} gr</div>
                      <div class="text-subtitle2 text-primary text-weight-bold">{{ money(item.joya.precio_referencial) }} Bs</div>
                    </div>
                  </div>
                  <div class="row q-col-gutter-sm q-mt-sm">
                    <div class="col-12 col-md-4">
                      <q-input :model-value="item.venta.costo_total" label="Monto" type="number" outlined dense bg-color="white" @update:model-value="updateVentaItem(item.joya.id, 'costo_total', $event)">
                        <template #append>
                          <q-btn flat round dense icon="restart_alt" @click="restoreVentaItemReferential(item.joya.id)">
                            <q-tooltip>Usar monto de joya</q-tooltip>
                          </q-btn>
                        </template>
                        <template #hint>
                          Monto joya: {{ money(item.joya.monto_bs) }} Bs
                        </template>
                      </q-input>
                    </div>
                    <div class="col-12 col-md-3">
                      <q-input :model-value="item.venta.adelanto" label="Adelanto" type="number" outlined dense bg-color="white" @update:model-value="updateVentaItem(item.joya.id, 'adelanto', $event)" />
                    </div>
                    <div class="col-12 col-md-3">
                      <q-select :model-value="item.venta.tipo_pago" :options="['Efectivo', 'QR']" label="Pago" outlined dense bg-color="white" @update:model-value="updateVentaItem(item.joya.id, 'tipo_pago', $event)" />
                    </div>
                    <div class="col-12 col-md-2">
                      <div class="selected-joya__saldo">
                        <div class="text-caption text-grey-7">Saldo</div>
                        <div class="text-weight-bold">{{ money(itemSaldo(item.venta)) }}</div>
                        <div class="text-caption">{{ itemEstado(item.venta) }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <q-banner class="bg-blue-1 text-blue-10 q-mt-sm rounded-borders">
                <div class="text-body2 text-weight-bold">Resumen general</div>
                <div class="text-caption q-mb-sm">
                  {{ selectedVentaJoyas.length }} joya(s), peso total {{ money(form.peso) }} gr y precio referencial {{ money(referentialTotal) }} Bs.
                </div>
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-4">
                    <div class="summary-total-card">
                      <div class="text-caption text-blue-8">Total venta</div>
                      <div class="text-h6 text-weight-bold">{{ money(totalVenta) }} Bs</div>
                    </div>
                  </div>
                  <div class="col-12 col-md-4">
                    <div class="summary-total-card">
                      <div class="text-caption text-blue-8">Total adelanto</div>
                      <div class="text-h6 text-weight-bold">{{ money(totalAdelanto) }} Bs</div>
                    </div>
                  </div>
                  <div class="col-12 col-md-4">
                    <div class="summary-total-card">
                      <div class="text-caption text-blue-8">Saldo total</div>
                      <div class="text-h6 text-weight-bold">{{ money(totalSaldo) }} Bs</div>
                    </div>
                  </div>
                </div>
              </q-banner>
            </q-card-section>

            <q-card-section>
              <q-form @submit.prevent="guardarVenta">
                <div class="text-subtitle2 text-weight-bold q-mb-sm">Cliente</div>
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-8">
                    <q-input
                      v-model="clienteFiltro"
                      outlined
                      dense
                      clearable
                      debounce="350"
                      label="Buscar cliente"
                      @update:model-value="getClientes"
                    >
                      <template #append><q-icon name="search" /></template>
                    </q-input>
                  </div>
                  <div class="col-12 col-md-4">
                    <q-btn class="full-width" color="positive" no-caps icon="add" label="Nuevo" @click="openClientDialog" />
                  </div>
                </div>

                <div v-if="form.cliente" class="q-mt-sm selected-client">
                  Cliente: <b>{{ form.cliente.name }}</b>  CI {{ form.cliente.ci }}
                </div>

                <q-list bordered separator class="q-mt-sm client-list">
                  <q-item v-for="cliente in clientes" :key="cliente.id" clickable @click="seleccionarCliente(cliente)">
                    <q-item-section>
                      <q-item-label>{{ cliente.name }}</q-item-label>
                      <q-item-label caption>{{ cliente.ci }}  {{ cliente.cellphone || 'Sin celular' }}</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-chip dense :color="cliente.status === 'Confiable' ? 'green' : cliente.status === 'No Confiable' ? 'red' : 'orange'" text-color="white">
                        {{ cliente.status }}
                      </q-chip>
                    </q-item-section>
                  </q-item>
                </q-list>

                <div class="text-subtitle2 text-weight-bold q-mt-md q-mb-sm">Venta</div>
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-4">
                    <q-input v-model="form.fecha_entrega" type="date" label="Fecha" outlined dense />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input v-model="form.celular" label="Celular" outlined dense />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input :model-value="money(form.peso)" label="Peso total" outlined dense readonly />
                  </div>
                  <div class="col-12">
                    <q-input v-model="form.detalle" type="textarea" label="Detalle base" outlined dense autogrow />
                  </div>
                </div>

                <div class="text-right q-mt-md">
                  <q-btn flat color="negative" no-caps label="Cancelar" @click="$router.push('/ventas-joyas')" :loading="saving" />
                  <q-btn color="primary" no-caps label="Guardar venta" type="submit" class="q-ml-sm" :loading="saving" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

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

    <q-dialog v-model="reportDialog">
      <q-card style="width: 560px; max-width: 96vw;">
        <q-card-section class="row items-center q-pb-sm">
          <div class="text-h6">Impresiones</div>
          <q-space />
          <q-btn flat round dense icon="close" @click="reportDialog = false" />
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
                :options="estucheOptionsFull"
                emit-value
                map-options
                clearable
                outlined
                dense
                label="Estuche"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat color="negative" no-caps label="Cancelar" @click="reportDialog = false" />
          <q-btn color="primary" no-caps icon="print" label="Imprimir" @click="imprimirReporte" />
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
      clientLoading: false,
      joyas: [],
      joyasIndex: {},
      vitrinas: [],
      clientes: [],
      clienteFiltro: '',
      clientDialog: false,
      reportDialog: false,
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
      return joya && Object.prototype.hasOwnProperty.call(joya, 'monto_bs')
        ? this.toNumber(joya.monto_bs)
        : this.toNumber(joya?.precio_referencial)
    },
    buildVentaItem (joya, previous = null) {
      const referencia = this.joyaMontoVenta(joya)
      return {
        joya_id: joya.id,
        costo_total: previous ? this.toNumber(previous.costo_total) : referencia,
        adelanto: previous ? this.toNumber(previous.adelanto) : referencia,
        tipo_pago: previous?.tipo_pago || 'Efectivo'
      }
    },
    getVentaItem (joyaId) {
      return this.ventaItems.find(item => item.joya_id === joyaId) || null
    },
    openReportDialog (type) {
      this.reportForm.type = type
      this.reportDialog = true
    },
    getVitrinas () {
      this.$axios.get('vitrinas').then(({ data }) => {
        this.vitrinas = data
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
          return {
            ...item,
            costo_total: monto,
            adelanto: monto
          }
        }

        return {
          ...item,
          [field]: field === 'tipo_pago' ? (value || 'Efectivo') : this.toNumber(value)
        }
      })
    },
    restoreVentaItemReferential (joyaId) {
      const joya = this.selectedJoyas.find(item => item.id === joyaId)
      if (!joya) return
      const referencia = this.joyaMontoVenta(joya)
      this.ventaItems = this.ventaItems.map(item => item.joya_id === joyaId
        ? { ...item, costo_total: referencia, adelanto: referencia }
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
          tipo_pago: item.tipo_pago || 'Efectivo'
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

.venta-column--catalog {
  order: 2;
}

.venta-column--form {
  order: 1;
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
  padding: 10px 12px;
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
  max-height: 220px;
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

@media (min-width: 1024px) {
  .venta-column--catalog {
    order: 1;
  }

  .venta-column--form {
    order: 2;
  }
}
</style>






