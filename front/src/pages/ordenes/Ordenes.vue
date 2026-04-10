<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section>
        <div class="row q-col-gutter-sm">
          <!-- Filtros -->
<!--          <div class="col-12 col-md-3">-->
<!--            <q-input label="Fecha Inicio" type="date" dense outlined v-model="filters.fecha_inicio" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-3">-->
<!--            <q-input label="Fecha Fin" type="date" dense outlined v-model="filters.fecha_fin" />-->
<!--          </div>-->
          <div class="col-12 col-md-3">
            <q-select v-model="filters.user_id" :options="usuarios" option-label="name" option-value="id"
                      label="Usuario" dense outlined emit-value map-options/>
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="filters.estado" :options="estados" label="Estado" dense outlined/>
          </div>

          <!-- Búsqueda -->
          <div class="col-12 col-md-6">
            <q-input
              v-model="filters.search"
              dense
              outlined
              placeholder="Buscar por Nº orden, nombre o CI..."
              :debounce="400"
              @keyup.enter="getOrdenes"
            >
              <template #append><q-icon name="search" /></template>
            </q-input>
          </div>
          <div class="col-6 col-md-2">
            <q-card class="items-center bg-info">
              <q-card-section class="row q-pa-none">
                <q-icon name="payments" color="white" size="lg" class="q-mr-sm"/>
                <div>
                  <div class="text-subtitle1 text-weight-bold text-white">Total QR</div>
                  <div class="text-h6 text-white">
                    {{ totalQr }} Bs
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-2">
            <q-card class="items-center bg-secondary">
              <q-card-section class="row q-pa-none">
                <q-icon name="account_balance_wallet" color="white" size="lg" class="q-mr-sm"/>
                <div>
                  <div class="text-subtitle1 text-weight-bold text-white">Total Efectivo</div>
                  <div class="text-h6 text-white">
                    {{ totalEfectivo }} Bs
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
<!--          carde de total d qr-->
          <div class="col-12"></div>

          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn color="primary" label="Actualizar" icon="refresh" @click="getOrdenes"
                   :loading="loading" no-caps />
          </div>
          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn color="green" label="Crear Orden" icon="add" @click="$router.push('/ordenes/crear')" no-caps />
          </div>

          <!-- Resumen -->
<!--          <div class="col-12 col-md-8 q-mt-sm">-->
<!--            <div class="row q-col-gutter-sm">-->
<!--              <div class="col-12 col-md-4">-->
<!--                <q-card bordered class="bg-blue-1">-->
<!--                  <q-card-section class="row items-center">-->
<!--                    <q-icon name="payments" color="blue" size="lg" class="q-mr-sm"/>-->
<!--                    <div>-->
<!--                      <div class="text-subtitle1 text-weight-bold text-blue">Total</div>-->
<!--                      <div class="text-h6">{{ resumen.total.toFixed(2) }}</div>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
<!--              <div class="col-12 col-md-4">-->
<!--                <q-card bordered class="bg-green-1">-->
<!--                  <q-card-section class="row items-center">-->
<!--                    <q-icon name="trending_up" color="green" size="lg" class="q-mr-sm"/>-->
<!--                    <div>-->
<!--                      <div class="text-subtitle1 text-weight-bold text-green">Adelanto</div>-->
<!--                      <div class="text-h6">{{ resumen.adelanto.toFixed(2) }}</div>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
<!--              <div class="col-12 col-md-4">-->
<!--                <q-card bordered class="bg-red-1">-->
<!--                  <q-card-section class="row items-center">-->
<!--                    <q-icon name="account_balance_wallet" color="red" size="lg" class="q-mr-sm"/>-->
<!--                    <div>-->
<!--                      <div class="text-subtitle1 text-weight-bold text-red">Saldo</div>-->
<!--                      <div class="text-h6">{{ resumen.saldo.toFixed(2) }}</div>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->

          <!-- GRID DE CARDS -->
          <div class="col-12 q-mt-sm">
            <div class="row q-col-gutter-sm">
              <div
                v-for="orden in ordenes"
                :key="orden.id"
                class="col-12 col-md-4"
              >
                <q-card bordered flat class="order-card">
                  <!-- franja de color por estado -->
                  <div class="status-strip" :style="{ backgroundColor: getEstadoColor(orden.estado) }"></div>

                  <q-card-section class="q-pb-xs card-head-section">
                    <div class="row items-center no-wrap order-card-header">
                      <div class="order-main-meta">
                        <div class="text-weight-bold order-number-line">#{{ orden.numero }}</div>
                        <div class="text-caption order-date-lines">
                          <div>Creado: {{ formatDateTime(orden.fecha_creacion) }}</div>
                          <div>
                            Entrega:
                            <span v-if="orden.fecha_entrega">
                              {{ formatDateTime(orden.fecha_entrega) }}
                            </span>
                            <span v-else class="muted">—</span>
                          </div>
                        </div>
                      </div>
                      <q-space/>
                      <div class="text-right order-status-chip-wrap">
                        <q-chip
                          dense
                          square
                          text-color="white"
                          :style="{ backgroundColor: getEstadoColor(orden.estado) }"
                          class="order-status-chip"
                        >
                          {{ orden.estado }}
                        </q-chip>
                      </div>
                    </div>
                  </q-card-section>

                  <q-card-section class="q-pt-none card-body-section">
                    <div class="client-line">
                      <div class="client-line__item client-line__item--name">
                        <q-icon name="person" size="16px" class="q-mr-xs"/>
                        <div class="text-body2 single-line-text">{{ orden.cliente?.name || 'N/A' }}</div>
                      </div>
                      <div class="client-line__item client-line__item--phone">
                        <q-icon name="phone" size="16px" class="q-mr-xs"/>
                        <div class="text-body2 single-line-text">{{ orden.cliente?.cellphone || 'N/A' }}</div>
                      </div>
                    </div>
                    <div class="order-detail-block">
                      <div><span class="text-bold">Detalle:</span> {{ orden.detalle }}</div>
                      <div><span class="text-bold">Peso:</span> {{ orden.peso }} g</div>
                    </div>

                    <div class="row q-mt-xs">
                      <div class="col-4">
                        <div class="text-caption text-grey-7">Total</div>
                        <div class="text-weight-medium">{{ money(orden.costo_total) }}</div>
                      </div>
                      <div class="col-4">
                        <div class="text-caption text-grey-7">Adelanto</div>
                        <div class="text-weight-medium">
                          {{ parseInt(orden.adelanto) + parseInt(orden.totalPagos) }}
                          <span class="muted text-red">
                            {{orden.tipo_pago }}
                          </span>
<!--                          <pre>{{orden.tipo_pago}}</pre>-->
                        </div>
<!--                        <pre>{{orden.totalPagos}}</pre>-->
                      </div>
                      <div class="col-4">
                        <div class="text-caption text-grey-7">Saldo</div>
                        <div class="text-weight-medium">{{ money(orden.saldo) }}</div>
                      </div>
                    </div>

                    <div class="row items-center q-mt-sm text-caption text-grey">
                      <q-icon name="account_circle" size="16px" class="q-mr-xs"/>
                      {{ orden.user?.name || '—' }}
                    </div>
                  </q-card-section>

                  <q-separator />

                  <q-card-actions align="between" class="q-pa-sm">
                    <q-btn dense flat no-caps icon="edit" label="Editar" @click="$router.push('/ordenes/editar/' + orden.id)" />
                    <div>
                      <q-btn dense flat no-caps icon="payment" @click="pagarTodo(orden)" />
                      <q-btn-dropdown dense flat no-caps dropdown-icon="arrow_drop_down" icon="print">
                        <q-list dense style="min-width: 220px">
                          <q-item clickable v-close-popup @click="imprimirOrdenPdf(orden.id)">
                            <q-item-section avatar><q-icon name="picture_as_pdf" /></q-item-section>
                            <q-item-section>Orden de trabajo PDF</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup @click="imprimirGarantiaPdf(orden.id)">
                            <q-item-section avatar><q-icon name="assignment" /></q-item-section>
                            <q-item-section>Garantia PDF</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup @click="imprimirOrdenDirecto(orden.id)">
                            <q-item-section avatar><q-icon name="print" /></q-item-section>
                            <q-item-section>Orden de trabajo directo</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup @click="imprimirGarantiaDirecto(orden.id)">
                            <q-item-section avatar><q-icon name="local_printshop" /></q-item-section>
                            <q-item-section>Garantia directa</q-item-section>
                          </q-item>
                        </q-list>
                      </q-btn-dropdown>
                      <q-btn
                        v-if="orden.estado !== 'Cancelada'"
                        dense
                        flat
                        no-caps
                        color="negative"
                        icon="block"
                        label=""
                        @click="anularOrden(orden)"
                      />
                    </div>
                  </q-card-actions>
                </q-card>
              </div>
            </div>
          </div>

          <!-- CONTROLES DE PAGINACIÓN -->
          <div class="col-12 q-mt-md">
            <div class="row items-center justify-between">
              <div class="col-12 col-sm-6 q-mb-sm">
                <div class="row items-center">
                  <div class="q-mr-sm text-caption text-grey-7">Por página</div>
                  <q-select
                    dense outlined emit-value map-options
                    :options="perPageOptions"
                    v-model="pagination.rowsPerPage"
                    @update:model-value="onChangePerPage"
                    style="width: 110px"
                  />
                  <div class="q-ml-md text-caption">
                    Mostrando {{ ordenes.length }} de {{ pagination.rowsNumber }} registros
                  </div>
                </div>
              </div>

              <div class="col-12 col-sm-6">
                <div class="row justify-end">
                  <q-pagination
                    v-model="pagination.page"
                    :max="pagination.lastPage"
                    :max-pages="7"
                    boundary-numbers
                    direction-links
                    size="sm"
                    @update:model-value="getOrdenes"
                  />
                </div>
              </div>
            </div>
          </div>

        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dlgPagarTodo">
      <q-card class="pago-total-dialog">
        <q-card-section class="q-pb-sm">
          <div class="text-subtitle1 text-weight-bold">Registrar pago total</div>
          <div class="text-caption text-grey-7">
            Orden #{{ pagoTodoOrden?.numero || '-' }}
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none q-gutter-md">
          <div class="pago-total-resumen">
            <div class="text-caption text-grey-7">Monto pendiente</div>
            <div class="text-h5 text-weight-bold text-green-8">
              {{ money(pagoTodoOrden?.saldo) }} Bs
            </div>
          </div>

          <q-select
            v-model="pagoTodoForm.metodo"
            :options="metodosPagoDialog"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Método de pago"
            outlined
            dense
          />
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md q-pt-none">
          <q-btn flat label="Cancelar" v-close-popup no-caps />
          <q-btn
            color="green"
            label="Registrar pago"
            no-caps
            :loading="savingPagoTodo"
            @click="confirmarPagarTodo"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'
import { printGarantiaDirecta, printOrdenTrabajoDirecto } from 'src/utils/orderPrint'

export default {
  name: 'OrdenesPage',
  data () {
    return {
      ordenes: [],
      usuarios: [],
      estados: ['Todos', 'Pendiente', 'Entregado', 'Cancelada'],
      filters: {
        fecha_inicio: null,
        fecha_fin: null,
        user_id: null,
        estado: 'Todos',
        search: ''
      },
      resumen: { total: 0, adelanto: 0, saldo: 0 },
      loading: false,
      dlgPagarTodo: false,
      savingPagoTodo: false,
      pagoTodoOrden: null,
      pagoTodoForm: { metodo: 'EFECTIVO' },
      metodosPagoDialog: [
        { label: 'Efectivo', value: 'EFECTIVO' },
        { label: 'Pago QR', value: 'QR' }
      ],

      // Paginación
      pagination: {
        page: 1,
        rowsPerPage: 12,
        rowsNumber: 0,
        lastPage: 1
      },
      perPageOptions: [12, 24, 48, 96, 100]
    }
  },
  computed : {
    totalQr() {
      let total = 0;
      this.ordenes.forEach(orden => {
        if (orden.tipo_pago === 'QR') {
          total += Number(orden.adelanto || 0);
        }
      });
      return total.toFixed(2);
    },
    totalEfectivo() {
      let total = 0;
      this.ordenes.forEach(orden => {
        if (orden.tipo_pago === 'Efectivo') {
          total += Number(orden.adelanto || 0);
        }
      });
      return total.toFixed(2);
    }
  },
  mounted () {
    this.getUsuarios()
    this.getOrdenes()
  },
  methods: {
    resetToFirst () { this.pagination.page = 1 },

    pagarTodoLegacy (orden) {
      const saldo = Number(orden.saldo || 0)
      this.$q.dialog({
        title: 'Confirmar Pago',
        html: true,
        message: `¿Confirma registrar el pago total de <b style="color: red">${this.money(saldo)}</b> Bs para la orden #${orden.numero}?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.post(`/ordenes/${orden.id}/pagar-todo`)
          .then(() => {
            this.$alert?.success('Pago registrado correctamente')
            this.getOrdenes()
          })
          .catch(err => {
            this.$alert?.error(err.response?.data?.message || 'Error al registrar el pago')
          })
      })
    },
    anularOrden(orden) {
      this.$q.dialog({
        title: 'Anular orden',
        message: `Desea anular la orden ${orden.numero}?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.post(`/ordenes/${orden.id}/cancelar`, {
          anular_pagos: true
        })
          .then(() => {
            this.$alert?.success('Orden anulada correctamente')
            this.getOrdenes()
          })
          .catch(err => {
            this.$alert?.error(err.response?.data?.message || 'Error al anular la orden')
          })
      })
    },
    async imprimirOrdenPdf (id) {
      try {
        const response = await this.$axios.get(`ordenes/${id}/pdf`, {
          responseType: 'blob'
        })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const fileName = response.headers['content-disposition']?.match(/filename="?([^"]+)"?/)?.[1] || `orden_trabajo_${id}.pdf`
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (err) {
        this.$alert?.error(err.response?.data?.message || 'Error al descargar la orden')
      }
    },
    imprimirGarantiaPdf (id) {
      const url = `${this.$axios.defaults.baseURL}/ordenes/${id}/garantia`
      window.open(url, '_blank')
    },
    async imprimirOrdenDirecto (id) {
      this.$q.loading.show({ message: 'Generando impresion...' })
      try {
        await printOrdenTrabajoDirecto(this.$axios, id)
      } catch (err) {
        this.$alert?.error(err.response?.data?.message || 'Error al imprimir directo la orden')
      } finally {
        this.$q.loading.hide()
      }
    },
    async imprimirGarantiaDirecto (id) {
      try {
        await printGarantiaDirecta(this.$axios, id)
      } catch (err) {
        this.$alert?.error(err.response?.data?.message || 'Error al imprimir directo la garantia')
      }
    },
    getUsuarios () {
      this.$axios.get('users').then(res => {
        this.usuarios = [{ id: null, name: 'Todos' }, ...res.data]
      })
    },

    getOrdenes () {
      this.loading = true
      this.$axios.get('ordenes', {
        params: {
          ...this.filters,
          tipo: 'Orden',
          page: this.pagination.page,
          per_page: this.pagination.rowsPerPage
        }
      })
        .then(res => {
          const p = res.data
          this.ordenes = p.data || []
          // actualizar paginación desde Laravel
          this.pagination.page        = p.current_page || 1
          this.pagination.rowsPerPage = p.per_page || this.pagination.rowsPerPage
          this.pagination.rowsNumber  = p.total || this.ordenes.length
          this.pagination.lastPage    = p.last_page || 1
          this.calcularResumen()
        })
        .catch(err => {
          this.$alert?.error?.(err.response?.data?.message || 'Error al obtener órdenes')
        })
        .finally(() => { this.loading = false })
    },

    onChangePerPage () {
      this.pagination.page = 1
      this.getOrdenes()
    },

    calcularResumen () {
      this.resumen.total = this.ordenes
        .filter(o => o.estado !== 'Cancelada')
        .reduce((s, o) => s + Number(o.costo_total || 0), 0)
      this.resumen.adelanto = this.ordenes
        .filter(o => o.estado !== 'Cancelada')
        .reduce((s, o) => s + Number(o.adelanto || 0), 0)
      this.resumen.saldo = this.ordenes
        .filter(o => o.estado !== 'Cancelada')
        .reduce((s, o) => s + Number(o.saldo || 0), 0)
    },

    getEstadoColor (estado) {
      switch (estado) {
        case 'Cancelada':  return '#fb8c00'
        case 'Entregado':  return '#e53935'
        case 'Pendiente':  return '#21ba45'
        // case '':  return '#e53935'
        default:           return '#9e9e9e'
      }
    },
    getEstadoIcon (estado) {
      switch (estado) {
        case 'Pendiente':  return 'hourglass_empty'
        case 'Entregado':  return 'check_circle'
        case 'Cancelada':  return 'cancel'
        default:           return 'work'
      }
    },
    pagarTodo (orden) {
      const saldo = Number(orden.saldo || 0)
      if (saldo <= 0) {
        this.$alert?.error('La orden no tiene saldo pendiente')
        return
      }
      this.pagoTodoOrden = orden
      this.pagoTodoForm = { metodo: 'EFECTIVO' }
      this.dlgPagarTodo = true
    },
    confirmarPagarTodo () {
      if (!this.pagoTodoOrden) return
      this.savingPagoTodo = true
      this.$axios.post(`/ordenes/${this.pagoTodoOrden.id}/pagar-todo`, {
        metodo: this.pagoTodoForm.metodo
      })
        .then(() => {
          this.$alert?.success('Pago registrado correctamente')
          this.dlgPagarTodo = false
          this.pagoTodoOrden = null
          this.getOrdenes()
        })
        .catch(err => {
          this.$alert?.error(err.response?.data?.message || 'Error al registrar el pago')
        })
        .finally(() => {
          this.savingPagoTodo = false
        })
    },
    money (v) { return Number(v || 0).toFixed(2) },
    formatDateTime (v) {
      if (!v) return '—'
      return v.length > 10
        ? moment(v).format('YYYY-MM-DD HH:mm')
        : moment(v, 'YYYY-MM-DD').format('YYYY-MM-DD')
    }
  },
  watch: {
    // cuando cambian filtros, volvemos a la página 1 y recargamos
    'filters.fecha_inicio' () { this.resetToFirst(); this.getOrdenes() },
    'filters.fecha_fin'    () { this.resetToFirst(); this.getOrdenes() },
    'filters.user_id'      () { this.resetToFirst(); this.getOrdenes() },
    'filters.estado'       () { this.resetToFirst(); this.getOrdenes() },
    'filters.search'       () { this.resetToFirst(); this.getOrdenes() }
  }
}
</script>

<style scoped>
.order-card {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  transition: transform .12s ease, box-shadow .12s ease;
}
.card-head-section {
  padding: 8px 10px 4px 10px;
}
.card-body-section {
  padding: 2px 10px 8px 10px !important;
}
.order-card-header {
  gap: 0;
}
.order-main-meta {
  min-width: 0;
  line-height: 1.05;
  padding-left: 0;
  margin-left: 0 !important;
}
.order-number-line {
  margin-bottom: 1px;
  line-height: 1.05;
}
.order-status-chip-wrap {
  display: flex;
  justify-content: flex-end;
  align-items: flex-start;
  min-width: fit-content;
}
.order-status-chip {
  margin: 0;
}
.order-date-lines {
  line-height: 1.1;
}
.order-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0,0,0,.08);
}
.status-strip {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 4px;
}
.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.client-line {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  margin-bottom: 4px;
  flex-wrap: nowrap;
}
.client-line__item {
  display: flex;
  align-items: center;
  min-width: 0;
}
.client-line__item--name {
  flex: 1 1 auto;
}
.client-line__item--phone {
  flex: 0 0 auto;
  max-width: 42%;
}
.single-line-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.15;
}
.order-detail-block {
  line-height: 1.18;
  margin-bottom: 2px;
}
.muted { font-size: .8em; color: #666; }
.pago-total-dialog {
  min-width: 360px;
}
.pago-total-resumen {
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
  border: 1px solid #c8e6c9;
}
</style>
