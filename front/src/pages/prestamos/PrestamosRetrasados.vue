<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <q-card-section class="row q-col-gutter-sm items-end">
        <div class="col-12 col-md-4">
          <q-input
            v-model="filters.search"
            dense
            outlined
            clearable
            label="Buscar por N° / cliente / CI / detalle"
            debounce="400"
            @update:model-value="applyFilters"
          >
            <template #append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>

        <div class="col-6 col-md-2">
          <q-select
            v-model="filters.user_id"
            :options="usuarios"
            option-label="name"
            option-value="id"
            emit-value
            map-options
            dense
            outlined
            label="Usuario"
            @update:model-value="applyFilters"
          />
        </div>

        <div class="col-6 col-md-2">
          <q-input
            v-model.number="filters.dias"
            dense
            outlined
            type="number"
            min="1"
            step="1"
            label="Mín. días retraso"
            @update:model-value="applyFilters"
          />
        </div>

        <div class="col-6 col-md-2">
          <q-select
            v-model="pagination.rowsPerPage"
            :options="[10, 20, 50, 100]"
            dense
            outlined
            label="Filas"
            @update:model-value="applyFilters"
          />
        </div>

        <div class="col-12 col-md-2">
          <div class="row q-gutter-sm justify-end">
            <q-btn
              color="secondary"
              icon="download"
              label="Excel"
              no-caps
              :loading="exporting"
              @click="exportar"
            />
            <q-btn
              color="primary"
              icon="refresh"
              label="Actualizar"
              no-caps
              :loading="loading"
              @click="fetchData"
            />
          </div>
        </div>
      </q-card-section>

      <q-card-section class="q-pt-none">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-3">
            <q-card bordered flat class="bg-orange-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-orange-9 text-weight-bold">Préstamos retrasados</div>
                <div class="text-h6">{{ summary.total }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat class="bg-red-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-red-9 text-weight-bold">Saldo pendiente</div>
                <div class="text-h6">{{ money(summary.saldo) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat class="bg-blue-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-blue-9 text-weight-bold">Capital invertido</div>
                <div class="text-h6">{{ money(summary.capital_invertido) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered flat class="bg-grey-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-grey-8 text-weight-bold">Prom / Máx retraso</div>
                <div class="text-h6">{{ summary.prom_dias }} / {{ summary.max_dias }} días</div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <q-table
          flat
          :rows="prestamos"
          :columns="columns"
          row-key="id"
          :loading="loading"
          v-model:pagination="pagination"
          binary-state-sort
          @request="onRequest"
          rows-per-page-label="Filas por página"
        >
          <template #body-cell-cliente="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.cliente?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">CI: {{ props.row.cliente?.ci || '—' }}</div>
            </q-td>
          </template>

          <template #body-cell-fecha_limite="props">
            <q-td :props="props">
              <div>{{ date(props.row.fecha_limite) }}</div>
              <div class="text-caption text-orange-9 text-weight-bold">
                {{ props.row.dias_retraso }} día(s)
              </div>
            </q-td>
          </template>

          <template #body-cell-valor_prestado="props">
            <q-td :props="props">
              {{ money(props.row.valor_prestado) }}
            </q-td>
          </template>

          <template #body-cell-saldo="props">
            <q-td :props="props">
              <span class="text-weight-bold text-red-9">{{ money(props.row.saldo) }}</span>
            </q-td>
          </template>

          <template #body-cell-estado="props">
            <q-td :props="props">
              <q-chip dense square text-color="white" :color="estadoColor(props.row.estado)">
                {{ props.row.estado }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-user="props">
            <q-td :props="props">
              {{ props.row.user?.name || 'N/A' }}
            </q-td>
          </template>

          <template #body-cell-acciones="props">
            <q-td :props="props">
              <q-btn-dropdown
                dense
                size="10px"
                color="primary"
                icon="more_vert"
                label="Acción"
                no-caps
              >
                <q-list dense>
                  <q-item clickable v-close-popup :disable="!props.row.celular" @click="abrirWhatsappRegularizar(props.row)">
                    <q-item-section avatar>
                      <q-icon name="fa-brands fa-whatsapp" color="positive" />
                    </q-item-section>
                    <q-item-section>WhatsApp regularizar</q-item-section>
                  </q-item>

                  <q-item clickable v-close-popup :disable="!props.row.celular" @click="abrirWhatsappFundicion(props.row)">
                    <q-item-section avatar>
                      <q-icon name="warning" color="orange" />
                    </q-item-section>
                    <q-item-section>WhatsApp fundición</q-item-section>
                  </q-item>

                  <q-item clickable v-close-popup @click="confirmFundir(props.row)">
                    <q-item-section avatar>
                      <q-icon name="local_fire_department" color="negative" />
                    </q-item-section>
                    <q-item-section>Fundir</q-item-section>
                  </q-item>

                  <q-separator />

                  <q-item clickable v-close-popup @click="$router.push('/prestamos/editar/' + props.row.id)">
                    <q-item-section avatar>
                      <q-icon name="edit" color="primary" />
                    </q-item-section>
                    <q-item-section>Editar / pagar</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>

          <template #no-data>
            <div class="full-width row flex-center q-gutter-sm q-pa-md text-grey-7">
              <q-icon name="info" />
              <span>No hay préstamos retrasados con los filtros aplicados.</span>
            </div>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'PrestamosRetrasados',
  data () {
    return {
      prestamos: [],
      usuarios: [{ id: null, name: 'Todos' }],
      filters: {
        search: '',
        user_id: null,
        dias: 1
      },
      summary: {
        total: 0,
        saldo: 0,
        capital_invertido: 0,
        prom_dias: 0,
        max_dias: 0
      },
      pagination: {
        sortBy: 'fecha_limite',
        descending: false,
        page: 1,
        rowsPerPage: 20,
        rowsNumber: 0
      },
      columns: [
        { name: 'acciones', label: 'Acción', field: 'acciones', align: 'left' },
        { name: 'numero', label: 'N°', field: 'numero', align: 'left' },
        { name: 'cliente', label: 'Cliente', field: row => row.cliente?.name || '', align: 'left' },
        { name: 'celular', label: 'Celular', field: 'celular', align: 'left' },
        { name: 'fecha_limite', label: 'Vencimiento', field: 'fecha_limite', align: 'left' },
        { name: 'valor_prestado', label: 'Prestado', field: 'valor_prestado', align: 'right' },
        { name: 'saldo', label: 'Saldo', field: 'saldo', align: 'right' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
        { name: 'user', label: 'Usuario', field: row => row.user?.name || '', align: 'left' }
      ],
      loading: false,
      exporting: false
    }
  },
  mounted () {
    this.getUsuarios()
    this.fetchData()
  },
  methods: {
    async getUsuarios () {
      try {
        const { data } = await this.$axios.get('users')
        this.usuarios = [{ id: null, name: 'Todos' }, ...data]
      } catch (e) {}
    },
    buildParams () {
      return {
        ...this.filters,
        page: this.pagination.page,
        per_page: this.pagination.rowsPerPage
      }
    },
    applyFilters () {
      this.pagination.page = 1
      this.fetchData()
    },
    async fetchData () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('prestamosRetrasados', {
          params: this.buildParams()
        })

        this.prestamos = data.data || []
        this.summary = data.summary || this.summary
        this.pagination.page = data.meta?.current_page || 1
        this.pagination.rowsPerPage = data.meta?.per_page || this.pagination.rowsPerPage
        this.pagination.rowsNumber = data.meta?.total || 0
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al obtener préstamos retrasados')
      } finally {
        this.loading = false
      }
    },
    onRequest (props) {
      this.pagination = {
        ...this.pagination,
        page: props.pagination.page,
        rowsPerPage: props.pagination.rowsPerPage
      }
      this.fetchData()
    },
    async exportar () {
      this.exporting = true
      try {
        const response = await this.$axios.get('prestamosRetrasados/export', {
          params: {
            ...this.filters
          },
          responseType: 'blob'
        })

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `prestamos-retrasados-${moment().format('YYYYMMDD-HHmmss')}.csv`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al exportar préstamos retrasados')
      } finally {
        this.exporting = false
      }
    },
    waHrefRegularizar (prestamo) {
      const phone = String(prestamo.celular || '').replace(/\D/g, '')
      const msg = encodeURIComponent(
        `Hola ${prestamo.cliente?.name || ''}, su préstamo ${prestamo.numero} ya está fuera de tiempo. ` +
        `Venció el ${this.date(prestamo.fecha_limite)} y tiene ${prestamo.dias_retraso} día(s) de retraso. ` +
        `Su saldo actual es Bs. ${this.money(prestamo.saldo)}. ` +
        'Por favor regularice hoy mismo su pago.'
      )

      return phone ? `https://wa.me/${phone}?text=${msg}` : null
    },
    waHrefFundicion (prestamo) {
      const phone = String(prestamo.celular || '').replace(/\D/g, '')
      const msg = encodeURIComponent(
        `Hola ${prestamo.cliente?.name || ''}, su préstamo ${prestamo.numero} continúa retrasado. ` +
        `Venció el ${this.date(prestamo.fecha_limite)} y tiene ${prestamo.dias_retraso} día(s) de retraso. ` +
        `Su saldo actual es Bs. ${this.money(prestamo.saldo)}. ` +
        'Si no regulariza el pago a la brevedad, la joya pasará a fundición.'
      )

      return phone ? `https://wa.me/${phone}?text=${msg}` : null
    },
    abrirWhatsappRegularizar (prestamo) {
      const url = this.waHrefRegularizar(prestamo)
      if (url) window.open(url, '_blank')
    },
    abrirWhatsappFundicion (prestamo) {
      const url = this.waHrefFundicion(prestamo)
      if (url) window.open(url, '_blank')
    },
    confirmFundir (prestamo) {
      this.$q.dialog({
        title: 'Fundir préstamo',
        message: `¿Desea marcar como fundido el préstamo ${prestamo.numero}?`,
        cancel: true,
        persistent: true
      }).onOk(() => this.fundir(prestamo.id))
    },
    async fundir (id) {
      try {
        await this.$axios.post(`prestamos/${id}/fundir`)
        this.$alert?.success?.('Préstamo marcado como fundido')
        this.fetchData()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al fundir préstamo')
      }
    },
    money (v) {
      return Number(v || 0).toFixed(2)
    },
    date (v) {
      return v ? moment(v).format('DD/MM/YYYY') : '—'
    },
    estadoColor (estado) {
      if (estado === 'Pendiente') return 'orange'
      if (estado === 'Pagado') return 'positive'
      if (estado === 'Fundido') return 'negative'
      return 'grey'
    }
  }
}
</script>
