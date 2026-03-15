<template>
  <q-page class="q-pa-md ventas-page">
    <div class="ventas-shell">
      <q-card flat bordered class="ventas-card">
        <q-card-section class="row q-col-gutter-sm items-center">
          <div class="col-12 col-md">
            <div class="text-h5 text-weight-bold">Venta de joyas</div>
            <div class="text-caption text-grey-7">Ventas directas registradas en la misma tabla de ordenes.</div>
          </div>
          <div class="col-12 col-md-auto">
            <div class="row q-gutter-sm">
              <q-btn color="primary" no-caps icon="refresh" label="Actualizar" :loading="loading" @click="getVentas" />
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
              <q-select v-model="filters.estado" :options="estados" outlined dense label="Estado" />
            </div>
            <div class="col-12 col-md-3">
              <q-input v-model="filters.fecha_inicio" type="date" outlined dense label="Desde" />
            </div>
            <div class="col-12 col-md-3">
              <q-input v-model="filters.fecha_fin" type="date" outlined dense label="Hasta" />
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
            <div v-for="venta in ventas" :key="venta.id" class="col-12 col-md-6 col-xl-4">
              <q-card flat bordered class="venta-card">
                <div class="venta-card__strip" :class="`venta-card__strip--${colorClass(venta.estado)}`"></div>
                <q-card-section class="q-pb-sm">
                  <div class="row items-start no-wrap">
                    <q-avatar rounded size="76px">
                      <q-img :src="imagenUrl(venta.joya?.imagen)" />
                    </q-avatar>
                    <div class="q-ml-md col">
                      <div class="row items-center no-wrap">
                        <div class="text-subtitle1 text-weight-bold">{{ venta.numero }}</div>
                        <q-space />
                        <q-chip dense :color="estadoColor(venta.estado)" text-color="white">{{ venta.estado }}</q-chip>
                      </div>
                      <div class="text-body2 text-weight-bold">{{ venta.joya?.nombre || 'Sin joya' }}</div>
                      <div class="text-caption text-grey-7">{{ venta.joya?.tipo || 'Sin tipo' }} · {{ formatDateTime(venta.fecha_creacion) }}</div>
                      <div class="text-caption text-grey-7">{{ venta.cliente?.name || 'Sin cliente' }}</div>
                      <div class="text-caption text-grey-7">{{ venta.joya?.estuche_item?.columna?.vitrina?.nombre || 'Sin vitrina' }} / {{ venta.joya?.estuche_item?.nombre || 'Sin estuche' }}</div>
                    </div>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-section class="q-pt-sm">
                  <div class="text-body2 q-mb-sm">{{ venta.detalle }}</div>
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
                    <span class="q-ml-sm">· {{ venta.tipo_pago || 'Efectivo' }}</span>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-actions align="between" class="q-pa-sm">
                  <q-btn
                    v-if="Number(venta.saldo || 0) > 0 && venta.estado !== 'Cancelada'"
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
                      dense
                      flat
                      color="primary"
                      icon="print"
                      label="Imprimir"
                      no-caps
                      @click="imprimirVenta(venta.id)"
                    />
                    <q-btn
                      v-if="venta.estado !== 'Cancelada'"
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
            <div class="text-subtitle2 q-mt-sm">No hay ventas de joyas registradas</div>
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
      estados: ['Todos', 'Pendiente', 'Entregado', 'Cancelada'],
      filters: {
        user_id: null,
        estado: 'Todos',
        fecha_inicio: moment().startOf('month').format('YYYY-MM-DD'),
        fecha_fin: moment().endOf('month').format('YYYY-MM-DD'),
        search: ''
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
  mounted () {
    this.getUsuarios()
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
      if (estado === 'Entregado') return 'positive'
      if (estado === 'Cancelada') return 'negative'
      return 'warning'
    },
    colorClass (estado) {
      if (estado === 'Entregado') return 'success'
      if (estado === 'Cancelada') return 'danger'
      return 'warning'
    },
    formatDateTime (value) {
      if (!value) return 'Sin fecha'
      return moment(value).format('YYYY-MM-DD HH:mm')
    },
    getUsuarios () {
      this.$axios.get('users').then(({ data }) => {
        this.usuarios = [{ id: null, name: 'Todos' }, ...data]
      })
    },
    getVentas () {
      this.loading = true
      this.$axios.get('ordenes', {
        params: {
          ...this.filters,
          tipo: 'Venta directa',
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
        this.$alert.error(err.response?.data?.message || 'Error al cargar ventas')
      }).finally(() => {
        this.loading = false
      })
    },
    onChangePerPage () {
      this.pagination.page = 1
      this.getVentas()
    },
    async imprimirVenta (id) {
      try {
        const response = await this.$axios.get(`ordenes/${id}/pdf`, {
          responseType: 'blob'
        })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const fileName = response.headers['content-disposition']?.match(/filename=\"?([^\"]+)\"?/)?.[1] || `venta_${id}.pdf`
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
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
        this.$axios.post(`ordenes/${venta.id}/pagar-todo`)
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
        this.$axios.post(`ordenes/${venta.id}/cancelar`, {
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
    'filters.estado' () {
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.fecha_inicio' () {
      this.pagination.page = 1
      this.getVentas()
    },
    'filters.fecha_fin' () {
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

.empty-block {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}
</style>
