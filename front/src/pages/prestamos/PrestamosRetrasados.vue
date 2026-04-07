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
            label="Min. dias retraso"
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
              color="accent"
              icon="edit_note"
              label="Editar mensajes"
              no-caps
              outline
              @click="abrirDialogoMensajes"
            />
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
                <div class="text-caption text-orange-9 text-weight-bold">Prestamos retrasados</div>
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
                <div class="text-caption text-grey-8 text-weight-bold">Prom / Max retraso</div>
                <div class="text-h6">{{ summary.prom_dias }} / {{ summary.max_dias }} dias</div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <q-table
          v-model:pagination="pagination"
          flat
          :rows="prestamos"
          :columns="columns"
          row-key="id"
          :loading="loading"
          binary-state-sort
          rows-per-page-label="Filas por pagina"
          @request="onRequest"
        >
          <template #body-cell-cliente="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.cliente?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">CI: {{ props.row.cliente?.ci || '-' }}</div>
            </q-td>
          </template>

          <template #body-cell-fecha_limite="props">
            <q-td :props="props">
              <div>{{ date(props.row.fecha_limite) }}</div>
              <div class="text-caption text-orange-9 text-weight-bold">
                {{ props.row.dias_retraso }} dia(s)
              </div>
            </q-td>
          </template>

          <template #body-cell-valor_prestado="props">
            <q-td :props="props">
              {{ money(props.row.valor_prestado) }}
            </q-td>
          </template>

          <template #body-cell-peso="props">
            <q-td :props="props">
              {{ money(props.row.peso) }}
            </q-td>
          </template>

          <template #body-cell-precio_oro="props">
            <q-td :props="props">
              {{ money(props.row.precio_oro) }}
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
                label="Accion"
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
                    <q-item-section>WhatsApp fundicion</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>

          <template #no-data>
            <div class="full-width row flex-center q-gutter-sm q-pa-md text-grey-7">
              <q-icon name="info" />
              <span>No hay prestamos retrasados con los filtros aplicados.</span>
            </div>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dialogMensajes">
      <q-card style="width: 900px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Editar mensajes de regularizacion y fundicion</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <div class="text-caption text-grey-7 q-mb-md">
            Variables disponibles: {{ placeholders.join(' ') }}
          </div>

          <div class="q-mb-md">
            <div class="text-subtitle2 q-mb-sm">Mensaje de regularizacion</div>
            <q-input
              v-model="mensajesForm.prestamo_regularizacion"
              type="textarea"
              autogrow
              outlined
            />
          </div>

          <div>
            <div class="text-subtitle2 q-mb-sm">Mensaje de fundicion</div>
            <q-input
              v-model="mensajesForm.prestamo_fundicion"
              type="textarea"
              autogrow
              outlined
            />
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" no-caps v-close-popup />
          <q-btn
            color="primary"
            label="Guardar en base de datos"
            no-caps
            :loading="savingMensajes"
            @click="guardarMensajes"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

const MENSAJES_DEFAULT = {
  prestamo_regularizacion: 'Hola #NOMBRE#, su prestamo #PRESTAMO# ya esta fuera de tiempo. Vencio el #FECHA# y tiene #DIAS_RETRASO# dia(s) de retraso. Su saldo actual es Bs. #SALDO#. Por favor regularice hoy mismo su pago.',
  prestamo_fundicion: 'Hola #NOMBRE#, su prestamo #PRESTAMO# continua retrasado. Vencio el #FECHA# y tiene #DIAS_RETRASO# dia(s) de retraso. Su saldo actual es Bs. #SALDO#. Si no regulariza el pago a la brevedad, la joya pasara a fundicion.'
}

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
        { name: 'acciones', label: 'Accion', field: 'acciones', align: 'left' },
        { name: 'numero', label: 'N°', field: 'numero', align: 'left' },
        { name: 'cliente', label: 'Cliente', field: row => row.cliente?.name || '', align: 'left' },
        { name: 'celular', label: 'Celular', field: 'celular', align: 'left' },
        { name: 'fecha_limite', label: 'Vencimiento', field: 'fecha_limite', align: 'left' },
        { name: 'peso', label: 'Peso', field: 'peso', align: 'right' },
        { name: 'precio_oro', label: 'Precio oro', field: 'precio_oro', align: 'right' },
        { name: 'valor_prestado', label: 'Prestado', field: 'valor_prestado', align: 'right' },
        { name: 'saldo', label: 'Saldo', field: 'saldo', align: 'right' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
        { name: 'user', label: 'Usuario', field: row => row.user?.name || '', align: 'left' }
      ],
      dialogMensajes: false,
      loadingMensajes: false,
      savingMensajes: false,
      placeholders: ['#NOMBRE#', '#PRESTAMO#', '#FECHA#', '#DIAS_RETRASO#', '#SALDO#'],
      mensajes: { ...MENSAJES_DEFAULT },
      mensajesForm: { ...MENSAJES_DEFAULT },
      loading: false,
      exporting: false
    }
  },
  mounted () {
    this.getUsuarios()
    this.fetchMensajes()
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
        this.$alert?.error?.(e.response?.data?.message || 'Error al obtener prestamos retrasados')
      } finally {
        this.loading = false
      }
    },
    async fetchMensajes () {
      this.loadingMensajes = true
      try {
        const { data } = await this.$axios.get('prestamosRetrasados/mensajes')
        const templates = {}
        ;(data.data || []).forEach(item => {
          templates[item.clave] = item.contenido || ''
        })
        this.mensajes = {
          ...this.mensajes,
          ...templates
        }
        this.placeholders = data.placeholders || this.placeholders
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al obtener mensajes configurados')
      } finally {
        this.loadingMensajes = false
      }
    },
    abrirDialogoMensajes () {
      this.mensajesForm = {
        prestamo_regularizacion: this.mensajes.prestamo_regularizacion || '',
        prestamo_fundicion: this.mensajes.prestamo_fundicion || ''
      }
      this.dialogMensajes = true
    },
    async guardarMensajes () {
      this.savingMensajes = true
      try {
        const { data } = await this.$axios.put('prestamosRetrasados/mensajes', {
          mensajes: this.mensajesForm
        })

        const templates = {}
        ;(data.data || []).forEach(item => {
          templates[item.clave] = item.contenido || ''
        })
        this.mensajes = {
          ...this.mensajes,
          ...templates
        }
        this.dialogMensajes = false
        this.$alert?.success?.(data.message || 'Mensajes actualizados correctamente')
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al guardar mensajes')
      } finally {
        this.savingMensajes = false
      }
    },
    renderMensaje (template, prestamo) {
      const replacements = {
        '#NOMBRE#': prestamo.cliente?.name || '',
        '#PRESTAMO#': prestamo.numero || '',
        '#FECHA#': this.date(prestamo.fecha_limite),
        '#DIAS_RETRASO#': String(prestamo.dias_retraso || 0),
        '#SALDO#': this.money(prestamo.saldo)
      }

      return Object.entries(replacements).reduce((message, [key, value]) => {
        return message.split(key).join(value)
      }, template || '')
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

        const blob = new Blob([response.data], { type: 'application/vnd.ms-excel;charset=utf-8;' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `prestamos-retrasados-${moment().format('YYYYMMDD-HHmmss')}.xls`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al exportar prestamos retrasados')
      } finally {
        this.exporting = false
      }
    },
    waHrefRegularizar (prestamo) {
      const phone = String(prestamo.celular || '').replace(/\D/g, '')
      const msg = encodeURIComponent(this.renderMensaje(this.mensajes.prestamo_regularizacion, prestamo))

      return phone ? `https://wa.me/${phone}?text=${msg}` : null
    },
    waHrefFundicion (prestamo) {
      const phone = String(prestamo.celular || '').replace(/\D/g, '')
      const msg = encodeURIComponent(this.renderMensaje(this.mensajes.prestamo_fundicion, prestamo))

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
    money (v) {
      return Number(v || 0).toFixed(2)
    },
    date (v) {
      return v ? moment(v).format('DD/MM/YYYY') : '-'
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
