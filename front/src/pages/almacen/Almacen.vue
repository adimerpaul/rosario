<template>
  <q-page class="almacen-page q-pa-sm">
    <div class="row q-col-gutter-sm">
      <div class="col-12 col-lg-6">
        <q-card flat bordered class="warehouse-panel full-height">
          <q-card-section class="compact-header">
            <div class="panel-title">ALMACEN</div>
            <q-btn dense flat round color="primary" icon="refresh" :loading="loading" @click="fetchData" />
          </q-card-section>

          <q-card-section class="q-pt-none q-pb-sm">
            <div class="summary-strip">
              <div class="summary-item">
                <div class="summary-value">{{ summary.cantidad_actual }}</div>
                <div class="summary-label">En caja</div>
              </div>
              <div class="summary-item">
                <div class="summary-value text-positive">{{ summary.entradas_hoy }}</div>
                <div class="summary-label">Entradas</div>
              </div>
              <div class="summary-item">
                <div class="summary-value text-negative">{{ summary.salidas_hoy }}</div>
                <div class="summary-label">Salidas</div>
              </div>
            </div>

            <div class="stock-box q-mt-sm">
              <div v-if="!actuales.length" class="empty-mini">Sin ordenes en almacen</div>

              <div v-else class="stock-grid">
                <div v-for="item in actuales" :key="item.id" class="mini-order">
                  <q-img :src="orderImage(item)" :ratio="1" class="mini-photo" spinner-color="grey-5" />
                  <div class="mini-code">{{ item.orden_numero }}</div>
                  <div class="mini-time">{{ item.tiempo_en_almacen }}</div>
                  <div class="mini-note" :title="item.observacion || ''">{{ item.observacion || '-' }}</div>
                  <q-btn dense flat color="negative" icon="logout" style="font-size: 10px;" @click="openSalida(item)" />
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-6">
        <q-card flat bordered class="report-panel">
          <q-card-section class="compact-header q-pb-sm">
            <div>
              <div class="panel-title">REPORTE</div>
              <div class="panel-notes">
                <div>* Que joyas hay en almacen</div>
                <div>* Movimientos de almacen</div>
              </div>
            </div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="report-range">{{ summary.cantidad_actual }} - {{ summary.entradas_hoy }} - {{ summary.salidas_hoy }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12">
        <q-card flat bordered class="search-panel">
          <q-card-section class="q-pb-xs q-pt-sm">
            <div class="row q-col-gutter-sm items-center">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="search"
                  dense
                  outlined
                  debounce="400"
                  placeholder="Buscar por codigo, cliente o detalle"
                  @update:model-value="fetchData"
                >
                  <template #prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
              <div class="col-4 col-md-2">
                <q-btn dense no-caps outline color="primary" icon="cleaning_services" label="Limpiar" class="full-width" style="font-size: 10px;" @click="resetSearch" />
              </div>
              <div class="col-4 col-md-2">
                <q-btn dense no-caps color="primary" icon="refresh" label="Actualizar" class="full-width" style="font-size: 10px;" :loading="loading" @click="fetchData" />
              </div>
              <div class="col-4 col-md-2">
                <q-btn-dropdown dense no-caps color="dark" icon="summarize" label="Reportes" class="full-width" style="font-size: 10px;">
                  <q-list dense style="min-width: 230px">
                    <q-item clickable v-close-popup @click="printEnCaja">
                      <q-item-section avatar><q-icon name="inventory_2" /></q-item-section>
                      <q-item-section>Imprimir en caja</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="openRetirosDialog">
                      <q-item-section avatar><q-icon name="event_note" /></q-item-section>
                      <q-item-section>Retiros por fechas</q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-6">
        <q-card flat bordered class="section-panel full-height">
          <q-card-section class="q-pb-xs q-pt-sm">
            <div class="dense-title">Ordenes para ingresar</div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <q-markup-table flat dense separator="cell" class="dense-table">
              <thead>
                <tr>
                  <th class="text-left photo-col">Foto</th>
                  <th class="text-left">Orden</th>
                  <th class="text-left">Cliente</th>
                  <th class="text-left">Detalle</th>
                  <th class="text-left">Creada</th>
                  <th class="text-right">Accion</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!paginatedDisponibles.length">
                  <td colspan="6" class="text-center text-grey-6">No hay ordenes disponibles</td>
                </tr>
                <tr v-for="item in paginatedDisponibles" :key="item.id">
                  <td class="photo-col">
                    <q-img :src="orderImage(item)" class="table-photo" :ratio="1" spinner-color="grey-5" />
                  </td>
                  <td>{{ item.numero }}</td>
                  <td>{{ item.cliente || '-' }}</td>
                  <td class="cell-detail">{{ item.detalle || '-' }}</td>
                  <td>{{ formatDate(item.fecha_creacion) }}</td>
                  <td class="text-right">
                    <q-btn dense no-caps color="positive" icon="add" label="Agregar" style="font-size: 10px;" @click="openEntrada(item)" />
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <div class="table-footer" v-if="disponiblesPages > 1">
              <q-pagination v-model="disponiblesPage" :max="disponiblesPages" :max-pages="5" boundary-numbers direction-links size="sm" />
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-6">
        <q-card flat bordered class="section-panel full-height">
          <q-card-section class="q-pb-xs q-pt-sm">
            <div class="dense-title">Historial de almacen</div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <q-markup-table flat dense separator="cell" class="dense-table">
              <thead>
                <tr>
                  <th class="text-left">Mov.</th>
                  <th class="text-left">Orden</th>
                  <th class="text-left">Cliente</th>
                  <th class="text-left">Usuario</th>
                  <th class="text-left">Observacion</th>
                  <th class="text-left">Fecha</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!paginatedHistorial.length">
                  <td colspan="6" class="text-center text-grey-6">Sin movimientos</td>
                </tr>
                <tr v-for="item in paginatedHistorial" :key="item.id">
                  <td>
                    <q-badge :color="item.tipo_movimiento === 'ENTRADA' ? 'positive' : 'negative'">
                      {{ item.tipo_movimiento }}
                    </q-badge>
                  </td>
                  <td>{{ item.orden_numero }}</td>
                  <td>{{ item.cliente || '-' }}</td>
                  <td>{{ item.usuario || '-' }}</td>
                  <td class="cell-observacion">{{ item.observacion || '-' }}</td>
                  <td>{{ formatDateTime(item.fecha_movimiento) }}</td>
                </tr>
              </tbody>
            </q-markup-table>
            <div class="table-footer" v-if="historialPages > 1">
              <q-pagination v-model="historialPage" :max="historialPages" :max-pages="5" boundary-numbers direction-links size="sm" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="entradaDialog.open" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-gutter-sm q-pb-sm">
          <q-icon name="inventory_2" color="positive" size="22px" />
          <div class="text-subtitle2 text-weight-bold">Ingresar orden</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="dialog-order">
            <q-img :src="orderImage(entradaDialog.item)" class="dialog-photo" :ratio="1" spinner-color="grey-5" />
            <div>
              <div class="text-body1 text-weight-bold">{{ entradaDialog.item?.numero }}</div>
              <div class="text-caption text-grey-7">{{ entradaDialog.item?.cliente }}</div>
              <div class="q-mt-xs text-body2">{{ entradaDialog.item?.detalle }}</div>
            </div>
          </div>
          <q-input v-model="entradaDialog.observacion" class="q-mt-md" type="textarea" outlined dense autogrow label="Observacion" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" no-caps v-close-popup style="font-size: 10px;" />
          <q-btn color="positive" label="Registrar entrada" no-caps :loading="saving" style="font-size: 10px;" @click="registrarEntrada" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="salidaDialog.open" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-gutter-sm q-pb-sm">
          <q-icon name="logout" color="negative" size="22px" />
          <div class="text-subtitle2 text-weight-bold">Registrar salida</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="dialog-order">
            <q-img :src="orderImage(salidaDialog.item)" class="dialog-photo" :ratio="1" spinner-color="grey-5" />
            <div>
              <div class="text-body1 text-weight-bold">{{ salidaDialog.item?.orden_numero }}</div>
              <div class="text-caption text-grey-7">{{ salidaDialog.item?.cliente }}</div>
              <div class="q-mt-xs text-body2">{{ salidaDialog.item?.detalle }}</div>
              <div class="q-mt-xs text-caption text-grey-7">Tiempo en almacen: {{ salidaDialog.item?.tiempo_en_almacen }}</div>
              <div class="q-mt-xs text-caption text-grey-7">Obs. entrada: {{ salidaDialog.item?.observacion || '-' }}</div>
            </div>
          </div>
          <q-input v-model="salidaDialog.observacion" class="q-mt-md" type="textarea" outlined dense autogrow label="Observacion de salida" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" no-caps v-close-popup style="font-size: 10px;" />
          <q-btn color="negative" label="Registrar salida" no-caps :loading="saving" style="font-size: 10px;" @click="registrarSalida" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="retirosDialog.open" persistent>
      <q-card style="width: 360px; max-width: 95vw;">
        <q-card-section class="row items-center q-gutter-sm q-pb-sm">
          <q-icon name="event_note" color="dark" size="22px" />
          <div class="text-subtitle2 text-weight-bold">Retiros por fechas</div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-input v-model="retirosDialog.desde" type="date" outlined dense label="Desde" />
          <q-input v-model="retirosDialog.hasta" type="date" outlined dense label="Hasta" class="q-mt-sm" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" no-caps v-close-popup style="font-size: 10px;" />
          <q-btn color="dark" label="Imprimir" no-caps style="font-size: 10px;" @click="printRetirosByDate" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'
import Printd from 'printd'

const PRINT_STYLES = `
  body { font-family: Arial, sans-serif; color: #111827; padding: 24px; }
  h1 { font-size: 18px; margin: 0 0 8px; }
  .meta { font-size: 12px; color: #4b5563; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; text-align: left; vertical-align: top; }
  th { background: #f3f4f6; }
  .empty { margin-top: 16px; font-size: 12px; color: #6b7280; }
`

export default {
  name: 'AlmacenPage',
  data () {
    return {
      loading: false,
      saving: false,
      search: '',
      disponiblesPage: 1,
      historialPage: 1,
      perPage: 6,
      summary: {
        cantidad_actual: 0,
        entradas_hoy: 0,
        salidas_hoy: 0
      },
      actuales: [],
      disponibles: [],
      historial: [],
      entradaDialog: {
        open: false,
        item: null,
        observacion: ''
      },
      salidaDialog: {
        open: false,
        item: null,
        observacion: ''
      },
      retirosDialog: {
        open: false,
        desde: moment().startOf('month').format('YYYY-MM-DD'),
        hasta: moment().format('YYYY-MM-DD')
      }
    }
  },
  computed: {
    disponiblesPages () {
      return Math.max(1, Math.ceil(this.disponibles.length / this.perPage))
    },
    historialPages () {
      return Math.max(1, Math.ceil(this.historial.length / this.perPage))
    },
    paginatedDisponibles () {
      const start = (this.disponiblesPage - 1) * this.perPage
      return this.disponibles.slice(start, start + this.perPage)
    },
    paginatedHistorial () {
      const start = (this.historialPage - 1) * this.perPage
      return this.historial.slice(start, start + this.perPage)
    }
  },
  mounted () {
    this.fetchData()
  },
  methods: {
    async fetchData () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('almacen', {
          params: {
            search: this.search || undefined
          }
        })
        this.summary = data.summary || this.summary
        this.actuales = data.actuales || []
        this.disponibles = data.disponibles || []
        this.historial = data.historial || []
        this.disponiblesPage = 1
        this.historialPage = 1
      } catch (error) {
        this.$alert?.error(error.response?.data?.message || 'No se pudo cargar el almacen')
      } finally {
        this.loading = false
      }
    },
    resetSearch () {
      this.search = ''
      this.fetchData()
    },
    openEntrada (item) {
      this.entradaDialog = {
        open: true,
        item,
        observacion: ''
      }
    },
    openSalida (item) {
      this.salidaDialog = {
        open: true,
        item,
        observacion: ''
      }
    },
    openRetirosDialog () {
      this.retirosDialog.open = true
    },
    async registrarEntrada () {
      if (!this.entradaDialog.item) return

      this.saving = true
      try {
        await this.$axios.post('almacen/entradas', {
          orden_id: this.entradaDialog.item.id,
          observacion: this.entradaDialog.observacion || null
        })
        this.$alert?.success('Entrada registrada correctamente')
        this.entradaDialog.open = false
        this.fetchData()
      } catch (error) {
        this.$alert?.error(error.response?.data?.message || 'No se pudo registrar la entrada')
      } finally {
        this.saving = false
      }
    },
    async registrarSalida () {
      if (!this.salidaDialog.item) return

      this.saving = true
      try {
        await this.$axios.post('almacen/salidas', {
          orden_id: this.salidaDialog.item.orden_id,
          observacion: this.salidaDialog.observacion || null
        })
        this.$alert?.success('Salida registrada correctamente')
        this.salidaDialog.open = false
        this.fetchData()
      } catch (error) {
        this.$alert?.error(error.response?.data?.message || 'No se pudo registrar la salida')
      } finally {
        this.saving = false
      }
    },
    formatDate (value) {
      if (!value) return '-'
      return moment(value).format('DD-MM-YY')
    },
    formatDateTime (value) {
      if (!value) return '-'
      return moment(value).format('DD-MM-YY HH:mm')
    },
    orderImage (item) {
      return `${this.$url}/../images/${item?.imagen || 'defaultJoya.png'}`
    },
    printHtml (html) {
      const printer = new Printd()
      const root = document.createElement('div')
      root.innerHTML = html
      printer.print(root, [PRINT_STYLES])
    },
    printEnCaja () {
      const rows = this.actuales.map(item => `
        <tr>
          <td>${item.orden_numero || '-'}</td>
          <td>${item.cliente || '-'}</td>
          <td>${item.detalle || '-'}</td>
          <td>${item.tiempo_en_almacen || '-'}</td>
          <td>${item.observacion || '-'}</td>
        </tr>
      `).join('')

      this.printHtml(`
        <h1>Joyas en caja</h1>
        <div class="meta">Fecha: ${moment().format('DD/MM/YYYY HH:mm')} | Total: ${this.actuales.length}</div>
        ${this.actuales.length ? `
          <table>
            <thead>
              <tr>
                <th>Orden</th>
                <th>Cliente</th>
                <th>Detalle</th>
                <th>Tiempo</th>
                <th>Observacion</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        ` : '<div class="empty">No hay joyas en caja.</div>'}
      `)
    },
    printRetirosByDate () {
      const { desde, hasta } = this.retirosDialog
      if (!desde || !hasta) {
        this.$alert?.error('Debes seleccionar desde y hasta')
        return
      }

      const retiros = this.historial.filter(item => {
        if (item.tipo_movimiento !== 'SALIDA' || !item.fecha_movimiento) return false
        const fecha = moment(item.fecha_movimiento)
        return fecha.isSameOrAfter(moment(desde).startOf('day')) && fecha.isSameOrBefore(moment(hasta).endOf('day'))
      })

      const rows = retiros.map(item => `
        <tr>
          <td>${item.orden_numero || '-'}</td>
          <td>${item.cliente || '-'}</td>
          <td>${item.usuario || '-'}</td>
          <td>${item.observacion || '-'}</td>
          <td>${this.formatDateTime(item.fecha_movimiento)}</td>
        </tr>
      `).join('')

      this.printHtml(`
        <h1>Retiros de ordenes</h1>
        <div class="meta">Desde: ${moment(desde).format('DD/MM/YYYY')} | Hasta: ${moment(hasta).format('DD/MM/YYYY')} | Total: ${retiros.length}</div>
        ${retiros.length ? `
          <table>
            <thead>
              <tr>
                <th>Orden</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>Observacion</th>
                <th>Fecha retiro</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        ` : '<div class="empty">No hubo retiros en ese rango.</div>'}
      `)

      this.retirosDialog.open = false
    }
  }
}
</script>

<style scoped>
.almacen-page {
  background: #f4f1ea;
  min-height: auto;
}

.warehouse-panel,
.report-panel,
.search-panel,
.section-panel {
  border-radius: 10px;
  background: #fff;
}

.full-height {
  height: 100%;
}

.compact-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  padding-bottom: 8px;
}

.panel-title {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.panel-notes {
  margin-top: 4px;
  padding-left: 10px;
  border-left: 3px solid #a23b2a;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.4;
}

.report-range {
  font-size: 11px;
  font-weight: 700;
  text-align: center;
}

.summary-strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.summary-item {
  border: 1px solid #ddd6c8;
  border-radius: 8px;
  padding: 6px 8px;
  text-align: center;
}

.summary-value {
  font-size: 18px;
  font-weight: 800;
  line-height: 1;
}

.summary-label {
  margin-top: 2px;
  font-size: 10px;
  color: #6b7280;
  text-transform: uppercase;
}

.stock-box {
  min-height: 154px;
  border: 3px solid #202020;
  border-radius: 4px;
  padding: 10px;
  background: #fff;
}

.stock-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: flex-start;
}

.mini-order {
  width: 90px;
  text-align: center;
}

.mini-photo,
.table-photo,
.dialog-photo {
  border: 1px solid #cfcfcf;
  border-radius: 2px;
  overflow: hidden;
}

.table-photo {
  width: 34px;
  min-width: 34px;
}

.dialog-photo {
  width: 72px;
  min-width: 72px;
}

.mini-code {
  margin-top: 4px;
  font-size: 10px;
  font-weight: 700;
}

.mini-time,
.mini-note {
  font-size: 9px;
  color: #6b7280;
}

.mini-note {
  min-height: 20px;
  white-space: normal;
  line-height: 1.1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 2px;
}

.dense-title {
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.dense-table {
  font-size: 11px;
}

.dense-table th,
.dense-table td {
  padding: 4px 6px;
}

.photo-col {
  width: 42px;
}

.cell-detail,
.cell-observacion {
  max-width: 180px;
  white-space: normal;
}

.table-footer {
  display: flex;
  justify-content: center;
  margin-top: 8px;
}

.dialog-order {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.empty-mini {
  height: 100%;
  min-height: 126px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: #6b7280;
}

@media (max-width: 768px) {
  .mini-order {
    width: 74px;
  }

  .dense-table {
    display: block;
    overflow-x: auto;
  }
}
</style>
