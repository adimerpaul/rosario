<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row items-center q-col-gutter-sm q-mb-md">
      <div class="col">
        <div class="text-h5 text-weight-bold">Reportes</div>
        <div class="text-caption text-grey-7">Ingresos/Egresos por fechas y usuarios.</div>
      </div>

      <div class="col-12 col-md-3">
        <q-input dense outlined type="date" v-model="filters.date_from" label="Desde" />
      </div>
      <div class="col-12 col-md-3">
        <q-input dense outlined type="date" v-model="filters.date_to" label="Hasta" />
      </div>

      <div class="col-12 col-md-3">
        <q-select
          dense outlined
          v-model="filters.users"
          :options="usersOptions"
          label="Usuarios"
          multiple
          emit-value
          map-options
          use-chips
          clearable
        />
      </div>

      <div class="col-auto">
        <q-btn color="primary" icon="search" label="Buscar" no-caps :loading="loading" @click="load" />
      </div>
    </div>

    <!-- KPIs -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-4">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col-auto"><q-avatar color="green-6" text-color="white" icon="trending_up" /></div>
            <div class="col">
              <div class="text-caption text-grey-7">Ingresos</div>
              <div class="text-h6">{{ money(totals.ingresos) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col-auto"><q-avatar color="red-6" text-color="white" icon="trending_down" /></div>
            <div class="col">
              <div class="text-caption text-grey-7">Egresos</div>
              <div class="text-h6">{{ money(totals.egresos) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col-auto"><q-avatar color="blue-6" text-color="white" icon="account_balance_wallet" /></div>
            <div class="col">
              <div class="text-caption text-grey-7">Neto</div>
              <div class="text-h6">{{ money(totals.neto) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Charts -->
    <div class="row q-col-gutter-md">
      <div class="col-12 col-lg-7">
        <q-card flat bordered class="shadow-1">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Diario</div>
            <div class="text-caption text-grey-7">Ingresos / Egresos / Neto</div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart type="line" height="320" :options="lineOptions" :series="lineSeries" />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-5">
        <q-card flat bordered class="shadow-1">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Métodos de pago</div>
            <div class="text-caption text-grey-7">Solo ingresos</div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart type="donut" height="320" :options="donutOptions" :series="donutSeries" />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12">
        <q-card flat bordered class="shadow-1">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Ingresos por usuario</div>
            <div class="text-caption text-grey-7">Ranking del periodo</div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart type="bar" height="340" :options="barOptions" :series="barSeries" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-inner-loading :showing="loading">
      <q-spinner size="34px" />
    </q-inner-loading>
  </q-page>
</template>

<script>
import moment from 'moment'
import VueApexCharts from 'vue3-apexcharts'

export default {
  name: 'ReportesPage',
  components: { apexchart: VueApexCharts },
  data () {
    return {
      loading: false,
      usersOptions: [],
      filters: {
        date_from: moment().startOf('month').format('YYYY-MM-DD'),
        date_to: moment().format('YYYY-MM-DD'),
        users: []
      },

      totals: { ingresos: 0, egresos: 0, neto: 0 },

      lineSeries: [
        { name: 'Ingresos', data: [] },
        { name: 'Egresos', data: [] },
        { name: 'Neto', data: [] }
      ],
      lineOptions: {
        chart: { toolbar: { show: false } },
        stroke: { width: 3, curve: 'smooth' },
        dataLabels: { enabled: false },
        xaxis: { categories: [] },
        tooltip: { y: { formatter: v => `${Number(v || 0).toFixed(2)} Bs.` } }
      },

      donutSeries: [],
      donutOptions: {
        labels: [],
        legend: { position: 'bottom' },
        tooltip: { y: { formatter: v => `${Number(v || 0).toFixed(2)} Bs.` } }
      },

      barSeries: [{ name: 'Ingresos', data: [] }],
      barOptions: {
        chart: { toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: [] },
        tooltip: { y: { formatter: v => `${Number(v || 0).toFixed(2)} Bs.` } }
      }
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    money (n) { return `${Number(n || 0).toFixed(2)} Bs.` },

    async load () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('dashboard/reportes', {
          params: {
            date_from: this.filters.date_from,
            date_to: this.filters.date_to,
            users: this.filters.users
          }
        })

        // users
        this.usersOptions = (data?.users || []).map(u => ({ label: u.name, value: u.id }))

        // totals
        this.totals.ingresos = Number(data?.totals?.ingresos || 0)
        this.totals.egresos = Number(data?.totals?.egresos || 0)
        this.totals.neto = Number(data?.totals?.neto || 0)

        // line days
        const days = Array.isArray(data?.days) ? data.days : []
        this.lineOptions = {
          ...this.lineOptions,
          xaxis: { categories: days.map(d => moment(d.date).format('DD/MM')) }
        }
        this.lineSeries = [
          { name: 'Ingresos', data: days.map(d => Number(d.ingresos || 0)) },
          { name: 'Egresos', data: days.map(d => Number(d.egresos || 0)) },
          { name: 'Neto', data: days.map(d => Number(d.neto || 0)) }
        ]

        // donut metodos
        const mp = Array.isArray(data?.metodos_pago) ? data.metodos_pago : []
        this.donutOptions = { ...this.donutOptions, labels: mp.map(x => x.metodo) }
        this.donutSeries = mp.map(x => Number(x.total || 0))

        // bar ingresos por usuario
        const iu = Array.isArray(data?.ingresos_por_usuario) ? data.ingresos_por_usuario : []
        this.barOptions = { ...this.barOptions, xaxis: { categories: iu.map(x => x.user_name) } }
        this.barSeries = [{ name: 'Ingresos', data: iu.map(x => Number(x.total || 0)) }]
      } catch (e) {
        this.$q.notify({ type: 'negative', message: e.response?.data?.message || 'Error al cargar reportes' })
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.shadow-1 { box-shadow: 0 8px 20px rgba(0,0,0,.06); }
</style>
