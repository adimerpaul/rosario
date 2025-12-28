<template>
  <q-page class="q-pa-md bg-grey-2">
    <!-- Header -->
    <div class="row items-center q-col-gutter-sm q-mb-md">
      <div class="col">
        <div class="text-h5 text-weight-bold">Dashboard</div>
        <div class="text-caption text-grey-7">
          Resumen por día y mejoras por semanas.
        </div>
      </div>

      <div class="col-auto">
        <q-input
          dense outlined
          v-model="selectedDate"
          type="date"
          label="Fecha"
          style="min-width: 190px"
          @input="load"
        />
      </div>

      <div class="col-auto">
        <q-btn
          color="primary"
          icon="refresh"
          label="Actualizar"
          no-caps
          :loading="loading"
          @click="load"
        />
      </div>
    </div>

    <!-- KPI Cards (SIN total invertido) -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-4">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col-auto">
              <q-avatar color="green-6" text-color="white" icon="trending_up" />
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Ingresos (día)</div>
              <div class="text-h6">{{ money(kpi.dayIngresos) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col-auto">
              <q-avatar color="red-6" text-color="white" icon="trending_down" />
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Egresos (día)</div>
              <div class="text-h6">{{ money(kpi.dayEgresos) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col-auto">
              <q-avatar color="blue-6" text-color="white" icon="account_balance_wallet" />
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Caja (día)</div>
              <div class="text-h6">{{ money(kpi.dayCaja) }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Retrasos + Donut -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-6">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center">
            <div class="col">
              <div class="text-subtitle1 text-weight-bold">Retrasos</div>
              <div class="text-caption text-grey-7">Órdenes y Préstamos con atraso</div>
            </div>
            <div class="col-auto">
              <q-chip color="red-6" text-color="white" dense>
                Órdenes retrasadas: {{ kpi.ordenesRetrasadas }}
              </q-chip>
              <q-chip color="deep-orange-6" text-color="white" dense class="q-ml-sm">
                Préstamos retrasados: {{ kpi.prestamosRetrasados }}
              </q-chip>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center">
            <div class="col">
              <div class="text-subtitle1 text-weight-bold">Método de pago (día)</div>
              <div class="text-caption text-grey-7">
                Distribución de ingresos EFECTIVO/QR para la fecha seleccionada
              </div>
            </div>
            <div class="col-auto">
              <q-badge outline color="grey-8">
                {{ selectedDate }}
              </q-badge>
            </div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart
              type="donut"
              height="260"
              :options="donutOptions"
              :series="donutSeries"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Charts -->
    <div class="row q-col-gutter-md">
      <!-- Daily line -->
      <div class="col-12 col-lg-7">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center">
            <div class="col">
              <div class="text-subtitle1 text-weight-bold">Ingresos vs Egresos (diario)</div>
              <div class="text-caption text-grey-7">Últimos 14 días</div>
            </div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart
              type="line"
              height="320"
              :options="lineOptions"
              :series="lineSeries"
            />
          </q-card-section>
        </q-card>
      </div>

      <!-- Weekly bars -->
      <div class="col-12 col-lg-5">
        <q-card flat bordered class="shadow-1">
          <q-card-section class="row items-center">
            <div class="col">
              <div class="text-subtitle1 text-weight-bold">Mejoras por semana</div>
              <div class="text-caption text-grey-7">Últimas 8 semanas (Neto semanal)</div>
            </div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart
              type="bar"
              height="320"
              :options="barOptions"
              :series="barSeries"
            />
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
  name: 'IndexPage',
  components: {
    apexchart: VueApexCharts
  },
  data () {
    return {
      loading: false,
      selectedDate: moment().format('YYYY-MM-DD'),

      // payload completo del dashboard
      dashboard: null,

      // KPIs
      kpi: {
        dayIngresos: 0,
        dayEgresos: 0,
        dayCaja: 0,
        ordenesRetrasadas: 0,
        prestamosRetrasados: 0
      },

      // Charts
      lineSeries: [
        { name: 'Ingresos', data: [] },
        { name: 'Egresos', data: [] }
      ],
      lineOptions: {
        chart: { toolbar: { show: false } },
        stroke: { width: 3, curve: 'smooth' },
        dataLabels: { enabled: false },
        xaxis: { categories: [] },
        yaxis: { labels: { formatter: val => Number(val || 0).toFixed(0) } },
        tooltip: { y: { formatter: val => `${Number(val || 0).toFixed(2)} Bs.` } }
      },

      barSeries: [{ name: 'Neto semanal', data: [] }],
      barOptions: {
        chart: { toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: [] },
        yaxis: { labels: { formatter: val => Number(val || 0).toFixed(0) } },
        tooltip: { y: { formatter: val => `${Number(val || 0).toFixed(2)} Bs.` } }
      },

      donutSeries: [0, 0],
      donutOptions: {
        labels: ['EFECTIVO', 'QR'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true },
        tooltip: { y: { formatter: val => `${Number(val || 0).toFixed(2)} Bs.` } }
      }
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    money (n) {
      return `${Number(n || 0).toFixed(2)} Bs.`
    },

    async load () {
      this.loading = true
      try {
        // ✅ UNA SOLA LLAMADA
        const { data } = await this.$axios.get('dashboard', {
          params: { date: this.selectedDate, days: 56 }
        })

        this.dashboard = data

        // KPIs
        this.kpi.dayIngresos = Number(data?.kpi?.dayIngresos || 0)
        this.kpi.dayEgresos = Number(data?.kpi?.dayEgresos || 0)
        this.kpi.dayCaja = Number(data?.kpi?.dayCaja || 0)
        this.kpi.ordenesRetrasadas = Number(data?.kpi?.ordenesRetrasadas || 0)
        this.kpi.prestamosRetrasados = Number(data?.kpi?.prestamosRetrasados || 0)

        // Donut
        this.donutSeries = Array.isArray(data?.donut?.series)
          ? data.donut.series.map(x => Number(x || 0))
          : [0, 0]

        // Charts
        this.buildLineChart(14)
        this.buildWeeklyBars(8)
      } catch (e) {
        this.$q.notify({
          type: 'negative',
          message: e.response?.data?.message || 'Error al cargar dashboard'
        })
      } finally {
        this.loading = false
      }
    },

    buildLineChart (days = 14) {
      const list = Array.isArray(this.dashboard?.days) ? this.dashboard.days : []
      const last = list.slice(-days)

      const categories = last.map(x => moment(x.date).format('DD/MM'))
      const ingresos = last.map(x => Number(x.total_ingresos || 0))
      const egresos = last.map(x => Number(x.total_egresos || 0))

      this.lineOptions = {
        ...this.lineOptions,
        xaxis: { ...this.lineOptions.xaxis, categories }
      }

      this.lineSeries = [
        { name: 'Ingresos', data: ingresos },
        { name: 'Egresos', data: egresos }
      ]
    },

    buildWeeklyBars (weeks = 8) {
      const list = Array.isArray(this.dashboard?.days) ? this.dashboard.days : []

      // agrupar por semana ISO: neto = ingresos - egresos
      const mapWeek = {}
      list.forEach(d => {
        const key = moment(d.date).format('GGGG-[W]WW')
        const net = Number(d.total_ingresos || 0) - Number(d.total_egresos || 0)
        mapWeek[key] = (mapWeek[key] || 0) + net
      })

      const keys = Object.keys(mapWeek).sort()
      const lastKeys = keys.slice(-weeks)

      const categories = lastKeys.map(k => {
        const w = k.split('W')[1]
        const y = k.split('-')[0]
        return `W${w} ${y}`
      })

      const data = lastKeys.map(k => Number(mapWeek[k] || 0))

      this.barOptions = {
        ...this.barOptions,
        xaxis: { ...this.barOptions.xaxis, categories }
      }
      this.barSeries = [{ name: 'Neto semanal', data }]
    }
  }
}
</script>

<style scoped>
.shadow-1 {
  box-shadow: 0 8px 20px rgba(0,0,0,.06);
}
</style>
