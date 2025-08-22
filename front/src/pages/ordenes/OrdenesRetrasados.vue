<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>

      <!-- Filtros -->
      <q-card-section class="row items-end q-col-gutter-sm">
        <div class="col-12 col-md-4">
          <q-input
            dense outlined clearable
            v-model="filters.search"
            label="Buscar por N° / cliente / detalle…"
            :debounce="400"
            @update:model-value="fetchData"
          >
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>

        <div class="col-6 col-md-3">
          <q-select
            dense outlined
            v-model="filters.user_id"
            :options="usuarios"
            option-label="name" option-value="id"
            emit-value map-options
            label="Usuario"
            @update:model-value="fetchData"
          />
        </div>

        <div class="col-6 col-md-3">
          <q-input
            dense outlined type="number" min="1" step="1"
            v-model.number="filters.dias"
            label="Mín. días de retraso"
            @keyup.enter="fetchData"
          />
        </div>

        <div class="col-12 col-md-2">
          <div class="row justify-end">
            <q-btn color="primary" no-caps icon="refresh" label="Actualizar"
                   :loading="loading" @click="fetchData"/>
          </div>
        </div>
      </q-card-section>

      <!-- Resumen -->
      <q-card-section class="q-pt-none">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-card bordered class="bg-orange-1">
              <q-card-section class="row items-center">
                <q-icon name="schedule" color="orange" size="lg" class="q-mr-sm"/>
                <div>
                  <div class="text-subtitle2 text-orange-9 text-weight-bold">Total retrasadas</div>
                  <div class="text-h6">{{ resumen.total }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-4">
            <q-card bordered class="bg-red-1">
              <q-card-section class="row items-center">
                <q-icon name="account_balance_wallet" color="red" size="lg" class="q-mr-sm"/>
                <div>
                  <div class="text-subtitle2 text-red-9 text-weight-bold">Saldo pendiente</div>
                  <div class="text-h6">{{ money(resumen.saldo) }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-4">
            <q-card bordered class="bg-blue-1">
              <q-card-section class="row items-center">
                <q-icon name="bar_chart" color="blue" size="lg" class="q-mr-sm"/>
                <div>
                  <div class="text-subtitle2 text-blue-9 text-weight-bold">Prom. días retraso</div>
                  <div class="text-h6">{{ resumen.promDias }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <!-- Grid de tarjetas -->
      <q-card-section class="q-pt-sm">
        <div class="row q-col-gutter-sm">
          <div v-for="o in ordenes" :key="o.id" class="col-12 col-md-4">
            <q-card bordered flat class="ord-card">
              <div class="status-strip" :style="{ backgroundColor: colorDias(o.dias_retraso) }"></div>

              <q-card-section class="q-pb-xs">
                <div class="row items-center no-wrap">
                  <q-avatar :icon="'warning'" :color="colorDias(o.dias_retraso)" text-color="white" size="32px"/>
                  <div class="q-ml-sm">
                    <div class="text-weight-bold">#{{ o.numero }}</div>
                    <div class="text-caption text-grey-7">Entrega: {{ date(o.fecha_entrega) }}</div>
                  </div>
                  <q-space/>
                  <q-chip dense square text-color="white" :style="{backgroundColor: colorDias(o.dias_retraso)}">
                    {{ o.dias_retraso }} d.
                  </q-chip>
                </div>
              </q-card-section>

              <q-separator spaced />

              <q-card-section class="q-pt-none">
                <div class="row items-center">
                  <q-icon name="person" size="18px" class="q-mr-xs"/>
                  <div class="text-body2 ellipsis-2-lines">{{ o.cliente?.name || 'N/A' }}</div>
                </div>

                <div class="row q-mt-xs">
                  <div class="col-12">
                    <div class="text-caption text-grey-7">Detalle</div>
                    <div class="ellipsis-2-lines">{{ o.detalle || '—' }}</div>
                  </div>
                </div>

                <div class="row q-mt-sm">
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Saldo</div>
                    <div class="text-weight-medium">{{ money(o.saldo) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Estado</div>
                    <q-chip dense square text-color="white" :style="{backgroundColor: estadoColor(o.estado)}">
                      {{ o.estado }}
                    </q-chip>
                  </div>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-actions align="between" class="q-pa-sm">
                <div class="row items-center">
                  <q-btn dense flat icon="call" :href="telHref(o.celular)" :disable="!o.celular" title="Llamar"/>
                  <q-btn dense flat icon="whatsapp" :href="waHref(o)" target="_blank" title="WhatsApp"/>
                  <q-btn dense flat icon="print" @click="imprimir(o.id)" title="Imprimir orden"/>
                </div>
                <q-btn dense flat icon="edit" label="Editar" @click="$router.push('/ordenes/editar/' + o.id)"/>
              </q-card-actions>
            </q-card>
          </div>

          <div v-if="!ordenes.length && !loading" class="col-12">
            <q-banner class="bg-grey-2 text-grey-8">No hay órdenes retrasadas con los filtros aplicados.</q-banner>
          </div>
        </div>

        <!-- Paginación -->
        <div class="row justify-center q-mt-sm" v-if="totalPages > 1">
          <q-pagination
            v-model="page"
            :max="totalPages"
            :max-pages="6"
            boundary-numbers
            @update:model-value="fetchData"
          />
        </div>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner size="32px" />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'OrdenesRetrasados',
  data () {
    return {
      ordenes: [],
      usuarios: [{ id: null, name: 'Todos' }],
      filters: {
        search: '',
        user_id: null,
        dias: 1
      },
      page: 1,
      perPage: 24,
      totalPages: 1,
      resumen: { total: 0, saldo: 0, promDias: 0 },
      loading: false
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
    async fetchData () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('ordenesRetrasadas', {
          params: {
            ...this.filters,
            page: this.page,
            per_page: this.perPage
          }
        })
        this.ordenes    = data.data || data
        this.totalPages = data.last_page || 1
        this.page       = data.current_page || this.page
        this.calcularResumen()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al obtener órdenes retrasadas')
      } finally {
        this.loading = false
      }
    },
    calcularResumen () {
      const n = this.ordenes.length || 1
      const sum = (arr, fn) => arr.reduce((s, x) => s + fn(x), 0)
      this.resumen.total   = this.ordenes.length
      this.resumen.saldo   = sum(this.ordenes, x => Number(x.saldo || 0))
      this.resumen.promDias = (sum(this.ordenes, x => Number(x.dias_retraso || 0)) / n).toFixed(1)
    },
    money (v) { return Number(v || 0).toFixed(2) },
    date (v) { return v ? moment(v).format('YYYY-MM-DD') : '—' },
    colorDias (d) {
      if (d >= 8) return '#e53935'         // rojo
      if (d >= 4) return '#fb8c00'         // naranja fuerte
      return '#fdd835'                     // amarillo
    },
    estadoColor (e) {
      if (e === 'Pendiente') return '#fb8c00'
      if (e === 'Entregado') return '#21ba45'
      if (e === 'Cancelada') return '#e53935'
      return '#9e9e9e'
    },
    telHref (cel) {
      const c = String(cel || '').trim()
      return c ? `tel:${c}` : null
    },
    waHref (o) {
      const phone = String(o.celular || '').replace(/\D/g,'')
      const msg = encodeURIComponent(
        `Hola ${o.cliente?.name || ''}, le recordamos su Orden ${o.numero} ` +
        `con fecha de entrega ${this.date(o.fecha_entrega)}. ` +
        `Actualmente presenta ${o.dias_retraso} día(s) de retraso. Saldo: Bs. ${this.money(o.saldo)}.`
      )
      return phone ? `https://wa.me/${phone}?text=${msg}` : null
    },
    imprimir (id) {
      const url = `${this.$axios.defaults.baseURL}/ordenes/${id}/pdf`
      window.open(url, '_blank')
    }
  }
}
</script>

<style scoped>
.ord-card {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  transition: transform .12s ease, box-shadow .12s ease;
}
.ord-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(0,0,0,.08); }
.status-strip { position: absolute; top:0; left:0; width:100%; height:4px; }
.ellipsis-2-lines {
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
</style>
