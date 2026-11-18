<template>
  <q-page class="q-pa-md ventas-page">
    <div class="ventas-shell">
      <q-card flat bordered class="ventas-card">
        <q-card-section class="row q-col-gutter-sm items-center">
          <div class="col-12 col-md">
            <div class="text-h5 text-weight-bold">Venta de joyas</div>
            <div class="text-caption text-grey-7">Control de joyas de vitrina con estado de venta y disponibilidad.</div>
          </div>
          <div class="col-12 col-md-auto">
            <div class="row q-gutter-sm">
              <q-btn color="primary" no-caps icon="refresh" label="Actualizar" :loading="loading" @click="getVentas" />
              <q-btn-dropdown color="dark" no-caps icon="print" label="Impresiones">
                <q-list dense>
                  <q-item clickable v-close-popup @click="openReportDialog('ventas_detalle')">
                    <q-item-section avatar><q-icon name="receipt_long" /></q-item-section>
                    <q-item-section>Detalle de ventas</q-item-section>
                  </q-item>
<!--                  <q-item clickable v-close-popup @click="openReportDialog('ventas_todas')">-->
<!--                    <q-item-section avatar><q-icon name="list_alt" /></q-item-section>-->
<!--                    <q-item-section>Todas las ventas</q-item-section>-->
<!--                  </q-item>-->
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
              <q-btn color="positive" no-caps icon="add" label="Crear venta" @click="$router.push('/ventas-joyas/crear')" />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.user_id"
                :options="usuarios"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                outlined
                dense
                label="Usuario"
              />
            </div>
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
              <q-select v-model="filters.estado_joya" :options="estadoOptions" outlined dense label="Estado joya" />
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
              <q-input v-model="filters.fecha" type="date" outlined dense clearable label="Fecha" />
            </div>
            <div class="col-12 col-md-6">
              <q-input v-model="filters.search" outlined dense debounce="350" clearable label="Buscar por codigo, joya o cliente">
                <template #append><q-icon name="search" /></template>
              </q-input>
            </div>
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-sm">
            <div v-for="venta in ventas" :key="venta.id" class="col-12 col-sm-6 col-lg-4 col-xl-3">
              <q-card flat bordered class="venta-card">
                <div class="venta-card__strip" :class="`venta-card__strip--${colorClass(venta.estado_joya)}`"></div>
                <q-card-section class="q-pb-xs venta-card__header">
                  <div class="row items-start no-wrap">
                    <q-avatar rounded size="60px">
                      <q-img :src="imagenUrl(venta.joya?.imagen)" />
                    </q-avatar>
                    <div class="q-ml-sm col">
                      <div class="row items-center no-wrap">
                        <div class="text-subtitle2 text-weight-bold">{{ venta.numero }}</div>
                        <q-space />
                        <q-chip dense :color="estadoColor(venta.estado_joya)" text-color="white">{{ venta.estado_joya }}</q-chip>
                      </div>
                      <div class="text-body2 text-weight-bold ellipsis-2-lines">{{ venta.joya?.nombre || 'Sin joya' }}</div>
                      <div class="text-caption text-grey-7">{{ venta.joya?.tipo || 'Sin tipo' }}  {{ lineaLabel(venta.joya?.linea) }}</div>
                      <div class="text-caption text-grey-7">{{ formatDateTime(venta.fecha_referencia || venta.fecha_creacion || venta.fecha_vitrina) }}</div>
                      <div class="text-caption text-grey-7">{{ venta.cliente?.name || 'Disponible en vitrina' }}</div>
                      <div class="text-caption text-grey-7 ellipsis">{{ venta.joya?.estuche_item?.columna?.vitrina?.nombre || 'Sin vitrina' }} / {{ venta.joya?.estuche_item?.nombre || 'Sin estuche' }}</div>
                    </div>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-section class="q-pt-sm venta-card__body">
                  <div class="text-body2 q-mb-sm ellipsis-2-lines">{{ venta.detalle }}</div>
                  <div class="row q-col-gutter-sm">
                    <div class="col-4">
                      <div class="text-caption text-grey-7">Total</div>
                      <div class="text-weight-bold">{{ money(venta.costo_total) }}</div>
                    </div>
                    <div class="col-4">
                      <div class="text-caption text-grey-7">Adelanto</div>
                      <div class="text-weight-bold">{{ money(Number(venta.adelanto || 0) + Number(venta.totalPagos || 0)) }}</div>
                    </div>
                    <div class="col-4">
                      <div class="text-caption text-grey-7">Saldo</div>
                      <div class="text-weight-bold">{{ money(venta.saldo) }}</div>
                    </div>
                  </div>
                  <div class="row items-center q-mt-sm text-caption text-grey-7">
                    <q-icon name="account_circle" size="16px" class="q-mr-xs" />
                    {{ venta.user?.name || 'Sin usuario' }}
                    <span class="q-ml-sm">{{ metodoPagoTexto(venta) }}</span>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-actions align="between" class="q-pa-sm">
                  <q-btn
                    v-if="Number(venta.saldo || 0) > 0 && venta.estado_joya === 'RESERVADO' && venta.venta_id"
                    dense
                    flat
                    icon="payment"
                    label="Pagar todo"
                    no-caps
                    @click="pagarTodo(venta)"
                  />
                  <div v-else></div>
                  <div class="row q-gutter-xs">
                    <q-btn
                      v-if="venta.venta_id"
                      dense
                      flat
                      color="primary"
                      icon="print"
                      label="Imprimir"
                      no-caps
                      @click="imprimirVenta(venta.venta_id)"
                    />
                    <q-btn
                      v-if="isAdmin && venta.venta_id && ['RESERVADO', 'VENDIDO'].includes(venta.estado_joya)"
                      dense
                      flat
                      color="orange-8"
                      icon="edit"
                      label="Modificar"
                      no-caps
                      @click="openEditDialog(venta)"
                    />
                    <q-btn
                      v-if="venta.venta_id && (venta.estado_joya === 'RESERVADO' || (isAdmin && venta.estado_joya === 'VENDIDO'))"
                      dense
                      flat
                      color="negative"
                      icon="block"
                      label="Anular"
                      no-caps
                      @click="anularVenta(venta)"
                    />
                  </div>
                </q-card-actions>
              </q-card>
            </div>
          </div>

          <div v-if="!ventas.length && !loading" class="empty-block">
            <q-icon name="sell" size="44px" color="grey-5" />
            <div class="text-subtitle2 q-mt-sm">No hay joyas para los filtros seleccionados</div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row items-center justify-between">
            <div class="col-12 col-sm-auto q-mb-sm">
              <div class="row items-center q-gutter-sm">
                <div class="text-caption text-grey-7">Por pagina</div>
                <q-select
                  v-model="pagination.rowsPerPage"
                  :options="perPageOptions"
                  outlined
                  dense
                  emit-value
                  map-options
                  style="width: 110px"
                  @update:model-value="onChangePerPage"
                />
              </div>
            </div>
            <div class="col-12 col-sm-auto">
              <q-pagination
                v-model="pagination.page"
                :max="pagination.lastPage"
                :max-pages="7"
                boundary-numbers
                direction-links
                @update:model-value="getVentas"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <q-dialog v-model="editDialog" persistent>
      <q-card style="width: 520px; max-width: 96vw;">
        <q-card-section class="row items-center q-pb-sm">
          <div>
            <div class="text-h6">Modificar venta</div>
            <div class="text-caption text-grey-7">{{ editForm.numero }} - {{ editForm.joya }}</div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" :disable="editLoading" @click="editDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-6">
              <q-input v-model.number="editForm.costo_total" type="number" min="0" step="0.01" outlined dense label="Precio de venta (Bs)" @update:model-value="onEditPrecio" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model.number="editForm.adelanto_efectivo" type="number" min="0" step="0.01" outlined dense label="Monto efectivo (Bs)" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model.number="editForm.adelanto_qr" type="number" min="0" step="0.01" outlined dense label="Monto QR (Bs)" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input :model-value="money(editSaldo)" readonly outlined dense label="Saldo resultante" />
            </div>
            <div class="col-12">
              <q-input v-model="editForm.detalle" type="textarea" autogrow outlined dense label="Detalle" />
            </div>
            <div v-if="editForm.totalPagos > 0" class="col-12 text-caption text-grey-7">
              Pagos registrados aparte: {{ money(editForm.totalPagos) }}
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat color="negative" no-caps label="Cancelar" :disable="editLoading" @click="editDialog = false" />
          <q-btn color="primary" no-caps icon="save" label="Guardar" :loading="editLoading" @click="guardarEdicion" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="pagoDialog" persistent>
      <q-card style="width: 420px; max-width: 96vw;">
        <q-card-section class="row items-center q-pb-sm">
          <div>
            <div class="text-h6">Pagar venta</div>
            <div class="text-caption text-grey-7">{{ pagoForm.numero }}</div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" :disable="pagoLoading" @click="pagoDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="text-body2 q-mb-sm">
            Saldo a pagar: <b class="text-green-8">{{ money(pagoForm.saldo) }} Bs</b>
          </div>
          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-input v-model.number="pagoForm.monto_efectivo" type="number" min="0" step="0.01" outlined dense label="Monto efectivo (Bs)" @update:model-value="onPagoEfectivo" />
            </div>
            <div class="col-6">
              <q-input v-model.number="pagoForm.monto_qr" type="number" min="0" step="0.01" outlined dense label="Monto QR (Bs)" @update:model-value="onPagoQr" />
            </div>
          </div>
          <div class="row q-gutter-xs q-mt-xs">
            <q-btn dense outline no-caps size="sm" label="Todo efectivo" @click="setPago(pagoForm.saldo, 0)" />
            <q-btn dense outline no-caps size="sm" label="Todo QR" @click="setPago(0, pagoForm.saldo)" />
          </div>
          <div v-if="!pagoCuadra" class="text-negative text-caption q-mt-sm">
            La suma ({{ money(pagoSuma) }}) debe ser igual al saldo ({{ money(pagoForm.saldo) }})
          </div>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat color="negative" no-caps label="Cancelar" :disable="pagoLoading" @click="pagoDialog = false" />
          <q-btn color="green" no-caps icon="payment" label="Registrar pago" :disable="!pagoCuadra" :loading="pagoLoading" @click="confirmarPago" />
        </q-card-actions>
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
import { printOrdenTrabajoDirecto } from 'src/utils/orderPrint'

export default {
  name: 'VentasJoyasPage',
  data () {
    return {
      loading: false,
      ventas: [],
      usuarios: [],
      vitrinas: [],
      reportLoading: false,
      reportDialog: false,
      reportEstucheOptions: [],
      pagoDialog: false,
      pagoLoading: false,
      pagoForm: { id: null, numero: '', saldo: 0, monto_efectivo: 0, monto_qr: 0 },
      editDialog: false,
      editLoading: false,
      editForm: {
        id: null,
        numero: '',
        joya: '',
        costo_total: 0,
        adelanto_efectivo: 0,
        adelanto_qr: 0,
        detalle: '',
        totalPagos: 0,
        syncAdelanto: false
      },
      filters: {
        user_id: null,
        vitrina_id: null,
        estuche_id: null,
        estado_joya: 'Todos',
        linea: null,
        fecha: '',
        search: ''
      },
      reportForm: {
        type: 'ventas_detalle',
        fecha_inicio: moment().startOf('month').format('YYYY-MM-DD'),
        fecha_fin: moment().endOf('month').format('YYYY-MM-DD'),
        linea: null,
        estuche_id: null
      },
      pagination: {
        page: 1,
        rowsPerPage: 12,
        rowsNumber: 0,
        lastPage: 1
      },
      perPageOptions: [12, 24, 48, 96]
    }
  },
  computed: {
    pagoSuma () {
      return this.round2(Number(this.pagoForm.monto_efectivo || 0) + Number(this.pagoForm.monto_qr || 0))
    },
    pagoCuadra () {
      return Math.abs(this.pagoSuma - Number(this.pagoForm.saldo || 0)) <= 0.01 &&
        Number(this.pagoForm.monto_efectivo || 0) >= 0 &&
        Number(this.pagoForm.monto_qr || 0) >= 0
    },
    isAdmin () {
      return this.$store.user?.role === 'Administrador'
    },
    editSaldo () {
      const f = this.editForm
      return Math.max(0, Number(f.costo_total || 0) - this.editAdelanto - Number(f.totalPagos || 0))
    },
    editAdelanto () {
      return Number(this.editForm.adelanto_efectivo || 0) + Number(this.editForm.adelanto_qr || 0)
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
    estadoOptions () {
      return ['Todos', 'EN VITRINA', 'RESERVADO', 'VENDIDO', 'ANULADO']
    },
    vitrinaOptions () {
      return this.vitrinas.map(vitrina => ({ label: vitrina.nombre, value: vitrina.id }))
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
    }
  },
  mounted () {
    this.getUsuarios()
    this.getVitrinas()
    this.getVentas()
  },
  methods: {
    money (value) {
      return `${Number(value || 0).toFixed(2)} Bs`
    },
    imagenUrl (imagen) {
      return `${this.$url}/../images/${imagen || 'joya.png'}`
    },
    estadoColor (estado) {
      if (estado === 'VENDIDO') return 'positive'
      if (estado === 'ANULADO') return 'negative'
      if (estado === 'RESERVADO') return 'warning'
      return 'primary'
    },
    colorClass (estado) {
      if (estado === 'VENDIDO') return 'success'
      if (estado === 'ANULADO') return 'danger'
      if (estado === 'RESERVADO') return 'warning'
      return 'info'
    },
    formatDateTime (value) {
      if (!value) return 'Sin fecha'
      return moment(value).format('YYYY-MM-DD HH:mm')
    },
    lineaLabel (value) {
      return value === 'Andreina' ? 'Reina' : (value || 'Sin linea')
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
    getUsuarios () {
      this.$axios.get('users').then(({ data }) => {
        this.usuarios = [{ id: null, name: 'Todos' }, ...data]
      })
    },
    getVitrinas () {
      this.$axios.get('vitrinas').then(({ data }) => {
        this.vitrinas = data
        this.reportEstucheOptions = this.estucheOptionsFull
      })
    },
    getVentas () {
      this.loading = true
      this.$axios.get('ordenes/joyas-vitrina', {
        params: {
          ...this.filters,
          page: this.pagination.page,
          per_page: this.pagination.rowsPerPage
        }
      }).then(({ data }) => {
        this.ventas = data.data || []
        this.pagination.page = data.current_page || 1
        this.pagination.rowsPerPage = data.per_page || this.pagination.rowsPerPage
        this.pagination.rowsNumber = data.total || this.ventas.length
        this.pagination.lastPage = data.last_page || 1
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al cargar joyas de vitrina')
      }).finally(() => {
        this.loading = false
      })
    },
    onChangePerPage () {
      this.pagination.page = 1
      this.getVentas()
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
    async imprimirVenta (id) {
      try {
        await printOrdenTrabajoDirecto(this.$axios, id)
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'Error al imprimir la venta')
      }
    },
    round2 (n) {
      return Math.round(Number(n || 0) * 100) / 100
    },
    pagarTodo (venta) {
      const saldo = this.round2(venta.saldo)
      this.pagoForm = {
        id: venta.venta_id,
        numero: venta.numero,
        saldo,
        monto_efectivo: saldo,
        monto_qr: 0
      }
      this.pagoDialog = true
    },
    // Al escribir en un campo, el otro se completa con el resto del saldo
    onPagoEfectivo (val) {
      this.pagoForm.monto_qr = this.round2(Math.max(0, this.pagoForm.saldo - Number(val || 0)))
    },
    onPagoQr (val) {
      this.pagoForm.monto_efectivo = this.round2(Math.max(0, this.pagoForm.saldo - Number(val || 0)))
    },
    setPago (efectivo, qr) {
      this.pagoForm.monto_efectivo = this.round2(efectivo)
      this.pagoForm.monto_qr = this.round2(qr)
    },
    confirmarPago () {
      if (!this.pagoCuadra) return
      this.pagoLoading = true
      this.$axios.post(`ordenes/${this.pagoForm.id}/pagar-todo`, {
        monto_efectivo: this.round2(this.pagoForm.monto_efectivo),
        monto_qr: this.round2(this.pagoForm.monto_qr)
      })
        .then(() => {
          this.$alert.success('Pago registrado')
          this.pagoDialog = false
          this.getVentas()
        })
        .catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al pagar la venta')
        })
        .finally(() => {
          this.pagoLoading = false
        })
    },
    openEditDialog (venta) {
      const costo = Number(venta.costo_total || 0)
      const adelanto = Number(venta.adelanto || 0)
      const totalPagos = Number(venta.totalPagos || 0)
      const { efectivo, qr } = this.desgloseAdelanto(venta)
      this.editForm = {
        id: venta.venta_id,
        numero: venta.numero,
        joya: venta.joya?.nombre || '',
        costo_total: costo,
        adelanto_efectivo: efectivo,
        adelanto_qr: qr,
        detalle: venta.detalle || '',
        totalPagos,
        // Si la venta estaba pagada completa con el adelanto, el adelanto sigue al precio
        syncAdelanto: totalPagos === 0 && Math.abs(adelanto - costo) < 0.005
      }
      this.editDialog = true
    },
    desgloseAdelanto (venta) {
      let efectivo = Number(venta.adelanto_efectivo || 0)
      let qr = Number(venta.adelanto_qr || 0)
      if (efectivo + qr <= 0) {
        const adelanto = Number(venta.adelanto || 0)
        const esQr = String(venta.tipo_pago || '').toUpperCase() === 'QR'
        efectivo = esQr ? 0 : adelanto
        qr = esQr ? adelanto : 0
      }
      return { efectivo, qr }
    },
    metodoPagoTexto (venta) {
      const { efectivo, qr } = this.desgloseAdelanto(venta)
      if (efectivo > 0 && qr > 0) return `Efectivo ${this.money(efectivo)} + QR ${this.money(qr)}`
      if (qr > 0) return `QR ${this.money(qr)}`
      if (efectivo > 0) return `Efectivo ${this.money(efectivo)}`
      return venta.tipo_pago || 'Efectivo'
    },
    onEditPrecio (value) {
      // Si estaba pagada completa, el monto cobrado sigue al precio en el metodo principal
      if (this.editForm.syncAdelanto) {
        const precio = Number(value || 0)
        if (Number(this.editForm.adelanto_qr || 0) > 0 && Number(this.editForm.adelanto_efectivo || 0) <= 0) {
          this.editForm.adelanto_qr = precio
        } else {
          this.editForm.adelanto_efectivo = Math.max(0, precio - Number(this.editForm.adelanto_qr || 0))
        }
      }
    },
    guardarEdicion () {
      const f = this.editForm
      if (Number(f.costo_total) < 0 || Number(f.adelanto_efectivo) < 0 || Number(f.adelanto_qr) < 0) {
        this.$alert.error('Los montos no pueden ser negativos')
        return
      }
      if (this.editAdelanto + Number(f.totalPagos || 0) > Number(f.costo_total || 0) + 0.005) {
        this.$alert.error('Efectivo + QR mas los pagos no puede superar el precio de venta')
        return
      }
      this.editLoading = true
      this.$axios.put(`ordenes/${f.id}/venta`, {
        costo_total: Number(f.costo_total || 0),
        adelanto_efectivo: Number(f.adelanto_efectivo || 0),
        adelanto_qr: Number(f.adelanto_qr || 0),
        detalle: f.detalle
      }).then(() => {
        this.$alert.success('Venta modificada')
        this.editDialog = false
        this.getVentas()
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al modificar la venta')
      }).finally(() => {
        this.editLoading = false
      })
    },
    anularVenta (venta) {
      this.$q.dialog({
        title: 'Anular venta',
        message: `Se anulara ${venta.numero}${venta.estado_joya === 'VENDIDO' ? ' (venta ya pagada)' : ''}, se revertiran sus pagos y la joya volvera a vitrina. Desea continuar?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.post(`ordenes/${venta.venta_id}/cancelar`, {
          anular_pagos: true
        }).then(() => {
          this.$alert.success('Venta anulada')
          this.getVentas()
        }).catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al anular la venta')
        })
      })
    }
  },
  watch: {
    'filters.user_id' () {
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.estado_joya' () {
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.vitrina_id' () {
      this.filters.estuche_id = null
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.estuche_id' () {
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.linea' () {
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.fecha' () {
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.search' () {
      this.pagination.page = 1
      this.getVentas()
    }
  }
}
</script>

<style scoped>
.ventas-page {
  background: linear-gradient(180deg, #f2f5f7 0%, #ebe6dd 100%);
  min-height: 100%;
}

.ventas-shell {
  max-width: 1500px;
  margin: 0 auto;
}

.ventas-card {
  border-radius: 22px;
  box-shadow: 0 22px 50px rgba(31, 63, 82, 0.12);
}

.venta-card {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  min-height: 100%;
}

.venta-card__header {
  min-height: 124px;
}

.venta-card__body {
  min-height: 128px;
}

.venta-card__strip {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
}

.venta-card__strip--success {
  background: #21ba45;
}

.venta-card__strip--danger {
  background: #c62828;
}

.venta-card__strip--warning {
  background: #f9a825;
}

.venta-card__strip--info {
  background: #1976d2;
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
</style>
