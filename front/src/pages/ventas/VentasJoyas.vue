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
                    <span class="q-ml-sm"> {{ venta.tipo_pago || 'Efectivo' }}</span>
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
                      v-if="venta.estado_joya === 'RESERVADO' && venta.venta_id"
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
                :options="estucheOptions"
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
  name: 'VentasJoyasPage',
  data () {
    return {
      loading: false,
      ventas: [],
      usuarios: [],
      vitrinas: [],
      reportDialog: false,
      filters: {
        user_id: null,
        vitrina_id: null,
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
      this.reportDialog = true
    },
    getUsuarios () {
      this.$axios.get('users').then(({ data }) => {
        this.usuarios = [{ id: null, name: 'Todos' }, ...data]
      })
    },
    getVitrinas () {
      this.$axios.get('vitrinas').then(({ data }) => {
        this.vitrinas = data
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
    async imprimirVenta (id) {
      try {
        await this.descargarPdf(`ordenes/${id}/pdf`)
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'Error al imprimir la venta')
      }
    },
    pagarTodo (venta) {
      this.$q.dialog({
        title: 'Pagar venta',
        message: `Registrar pago total de ${this.money(venta.saldo)} para ${venta.numero}?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.post(`ordenes/${venta.venta_id}/pagar-todo`)
          .then(() => {
            this.$alert.success('Pago registrado')
            this.getVentas()
          })
          .catch(err => {
            this.$alert.error(err.response?.data?.message || 'Error al pagar la venta')
          })
      })
    },
    anularVenta (venta) {
      this.$q.dialog({
        title: 'Anular venta',
        message: `Se anulara ${venta.numero} y se revertiran sus pagos activos. Desea continuar?`,
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
