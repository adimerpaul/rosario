<template>
  <q-page class="dash q-pa-sm">
    <!-- Barra superior: periodo -->
    <div class="dash-top row items-center no-wrap q-gutter-x-sm q-mb-sm">
      <div class="col-auto gt-xs">
        <div class="text-subtitle1 text-weight-bold line-1">Panel de ventas</div>
        <div class="text-caption text-grey-7 line-1">{{ rangoTexto }}</div>
      </div>
      <q-space class="gt-xs" />
      <div class="dash-periodos col-auto">
        <q-btn-toggle
          v-model="periodo"
          dense no-caps unelevated
          toggle-color="primary" color="white" text-color="grey-9"
          class="dash-toggle"
          :options="periodoOptions"
        />
      </div>
      <template v-if="periodo === 'rango'">
        <q-input v-model="desde" type="date" dense outlined class="dash-date" />
        <q-input v-model="hasta" type="date" dense outlined class="dash-date" />
      </template>
      <q-btn dense flat round icon="refresh" color="primary" :loading="loading" @click="loadAll">
        <q-tooltip>Actualizar</q-tooltip>
      </q-btn>
    </div>
    <div class="text-caption text-grey-7 q-mb-xs lt-sm">{{ rangoTexto }}</div>

    <!-- KPIs -->
    <div class="row q-col-gutter-sm q-mb-sm">
      <div class="col-6 col-sm-4 col-md-2">
        <div class="kpi">
          <div class="kpi__label">Ventas {{ periodoLabel }}</div>
          <div class="kpi__value">{{ moneyShort(v.kpi.monto) }}</div>
          <div class="kpi__sub">
            <template v-if="v.kpi.variacion !== null">
              <q-icon :name="v.kpi.variacion >= 0 ? 'arrow_upward' : 'arrow_downward'" size="12px" :class="v.kpi.variacion >= 0 ? 'text-positive' : 'text-negative'" />
              <span :class="v.kpi.variacion >= 0 ? 'text-positive' : 'text-negative'">{{ Math.abs(v.kpi.variacion) }}%</span>
              vs anterior ({{ moneyShort(v.kpi.monto_anterior) }})
            </template>
            <template v-else>Anterior: {{ moneyShort(v.kpi.monto_anterior) }}</template>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-md-2">
        <div class="kpi">
          <div class="kpi__label">N° de ventas</div>
          <div class="kpi__value">{{ v.kpi.cantidad }}</div>
          <div class="kpi__sub">Ticket prom. {{ moneyShort(v.kpi.ticket) }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-md-2">
        <div class="kpi">
          <div class="kpi__label">Cobrado en ventas</div>
          <div class="kpi__value">{{ moneyShort(cobradoTotal) }}</div>
          <div class="split-bar" :title="`Efectivo ${money(v.kpi.cobrado_efectivo)} · QR ${money(v.kpi.cobrado_qr)}`">
            <div class="split-bar__seg split-bar__seg--efe" :style="{ width: pctEfectivo + '%' }" />
            <div class="split-bar__seg split-bar__seg--qr" :style="{ width: (100 - pctEfectivo) + '%' }" />
          </div>
          <div class="kpi__sub row no-wrap justify-between">
            <span><i class="dot dot--efe" />Efec. {{ moneyShort(v.kpi.cobrado_efectivo) }}</span>
            <span><i class="dot dot--qr" />QR {{ moneyShort(v.kpi.cobrado_qr) }}</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-md-2">
        <div class="kpi">
          <div class="kpi__label">Órdenes de trabajo</div>
          <div class="kpi__value">{{ v.kpi.ordenes_cantidad }}</div>
          <div class="kpi__sub">Monto {{ moneyShort(v.kpi.ordenes_monto) }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-md-2">
        <div class="kpi">
          <div class="kpi__label">Caja {{ cajaFechaLabel }}</div>
          <div class="kpi__value" :class="kpiCaja.dayCaja < 0 ? 'text-negative' : ''">{{ moneyShort(kpiCaja.dayCaja) }}</div>
          <div class="kpi__sub">Ing. {{ moneyShort(kpiCaja.dayIngresos) }} · Egr. {{ moneyShort(kpiCaja.dayEgresos) }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-md-2">
        <div class="kpi kpi--link">
          <div class="kpi__label">Retrasos</div>
          <div class="row no-wrap q-gutter-x-md">
            <div class="cursor-pointer" @click="$router.push('/ordenes-retrasadas')">
              <div class="kpi__value">{{ kpiCaja.ordenesRetrasadas }}</div>
              <div class="kpi__sub">Órdenes</div>
            </div>
            <div class="cursor-pointer" @click="$router.push('/prestamos-retrasados')">
              <div class="kpi__value">{{ kpiCaja.prestamosRetrasados }}</div>
              <div class="kpi__sub">Préstamos</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Ventas en el tiempo + vendedores -->
    <div class="row q-col-gutter-sm q-mb-sm">
      <div class="col-12 col-md-8">
        <q-card flat bordered class="panel">
          <div class="panel__head row items-center no-wrap q-gutter-x-sm">
            <div class="col">
              <div class="panel__title">Ventas por {{ granularidadLabel }}</div>
              <div class="text-caption text-grey-7 line-1">{{ metrica === 'monto' ? 'Monto vendido (Bs.)' : 'Cantidad de ventas' }}</div>
            </div>
            <q-btn-toggle
              v-model="metrica"
              dense no-caps unelevated size="sm"
              toggle-color="grey-9" color="grey-2" text-color="grey-9"
              :options="[{ label: 'Bs.', value: 'monto' }, { label: 'Cant.', value: 'cantidad' }]"
            />
            <q-btn-toggle
              v-model="vista"
              dense no-caps unelevated size="sm"
              toggle-color="grey-9" color="grey-2" text-color="grey-9"
              :options="[{ icon: 'bar_chart', value: 'grafico' }, { icon: 'table_rows', value: 'tabla' }]"
            />
          </div>
          <div class="panel__body">
            <apexchart
              v-if="vista === 'grafico'"
              type="bar"
              height="250"
              :options="ventasOptions"
              :series="ventasSeries"
            />
            <div v-else class="tabla-wrap">
              <q-markup-table flat dense separator="horizontal" class="tabla">
                <thead>
                  <tr>
                    <th class="text-left">{{ granularidadLabel === 'hora' ? 'Hora' : granularidadLabel === 'día' ? 'Fecha' : 'Mes' }}</th>
                    <th class="text-right">Ventas</th>
                    <th class="text-right">Monto (Bs.)</th>
                    <th class="text-left gt-xs" style="width: 40%"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in v.serie" :key="r.key" :class="{ 'text-grey-5': !r.cantidad }">
                    <td class="text-left">{{ r.label }}</td>
                    <td class="text-right">{{ r.cantidad }}</td>
                    <td class="text-right text-weight-medium">{{ money(r.monto) }}</td>
                    <td class="gt-xs">
                      <div class="mini-bar"><div :style="{ width: barPct(r.monto) + '%' }" /></div>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="text-weight-bold">
                    <td>Total</td>
                    <td class="text-right">{{ v.kpi.cantidad }}</td>
                    <td class="text-right">{{ money(v.kpi.monto) }}</td>
                    <td class="gt-xs" />
                  </tr>
                </tfoot>
              </q-markup-table>
            </div>
          </div>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div class="panel__title">{{ esAdmin ? 'Mejores vendedores' : 'Mis ventas' }}</div>
            <div class="text-caption text-grey-7 line-1">{{ rangoTexto }}</div>
          </div>
          <div class="panel__body">
            <div v-if="topVendedor" class="top-seller row items-center no-wrap q-mb-sm">
              <q-avatar size="40px" color="amber-7" text-color="white" class="q-mr-sm">
                <q-icon name="emoji_events" />
              </q-avatar>
              <div class="col" style="min-width: 0">
                <div class="text-caption text-grey-7 line-1">Mejor vendedor</div>
                <div class="text-subtitle2 text-weight-bold ellipsis">{{ topVendedor.nombre }}</div>
              </div>
              <div class="text-right">
                <div class="text-subtitle2 text-weight-bold">{{ moneyShort(topVendedor.monto) }}</div>
                <div class="text-caption text-grey-7">{{ topVendedor.cantidad }} ventas</div>
              </div>
            </div>

            <div v-for="(s, i) in v.vendedores" :key="s.user_id ?? i" class="seller">
              <div class="row no-wrap items-baseline">
                <span class="seller__rank">{{ i + 1 }}</span>
                <span class="col ellipsis">{{ s.nombre }}</span>
                <span class="text-caption text-grey-7 q-mx-sm">{{ s.cantidad }}</span>
                <span class="text-weight-medium">{{ money(s.monto) }}</span>
              </div>
              <div class="mini-bar"><div :style="{ width: sellerPct(s.monto) + '%' }" /></div>
            </div>

            <div v-if="!v.vendedores.length" class="text-center text-grey-6 q-py-lg">
              <q-icon name="storefront" size="32px" />
              <div class="text-caption">Sin ventas en el periodo</div>
            </div>
          </div>
        </q-card>
      </div>
    </div>

    <!-- Caja: tendencias -->
    <div class="row q-col-gutter-sm">
      <div class="col-12 col-md-7">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div class="panel__title">Caja: ingresos y egresos</div>
            <div class="text-caption text-grey-7 line-1">Últimos 14 días hasta {{ fechaCorta(cajaFecha) }}</div>
          </div>
          <div class="panel__body">
            <apexchart type="line" height="200" :options="lineOptions" :series="lineSeries" />
          </div>
        </q-card>
      </div>
      <div class="col-12 col-md-5">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div class="panel__title">Neto semanal de caja</div>
            <div class="text-caption text-grey-7 line-1">Últimas 8 semanas</div>
          </div>
          <div class="panel__body">
            <apexchart type="bar" height="200" :options="barOptions" :series="barSeries" />
          </div>
        </q-card>
      </div>
    </div>

    <q-inner-loading :showing="loading">
      <q-spinner size="34px" color="primary" />
    </q-inner-loading>
  </q-page>
</template>

<script>
import moment from 'moment'
import VueApexCharts from 'vue3-apexcharts'

const C = {
  serie: '#2a78d6',
  serie2: '#eb6834',
  grid: '#ececea',
  axis: '#52514e'
}

const compact = (n) => {
  const x = Number(n || 0)
  if (Math.abs(x) >= 1000000) return `${(x / 1000000).toFixed(1)}M`
  if (Math.abs(x) >= 1000) return `${(x / 1000).toFixed(x >= 10000 ? 0 : 1)}k`
  return x.toFixed(0)
}

const baseChart = {
  chart: { toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit', animations: { speed: 250 } },
  dataLabels: { enabled: false },
  grid: { borderColor: C.grid, strokeDashArray: 0, padding: { left: 4, right: 4, top: -10 } },
  xaxis: {
    categories: [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: C.axis, fontSize: '10px' }, rotate: 0, hideOverlappingLabels: true }
  },
  yaxis: { labels: { style: { colors: C.axis, fontSize: '10px' }, formatter: compact } },
  legend: { show: false }
}

export default {
  name: 'IndexPage',
  components: { apexchart: VueApexCharts },

  data () {
    return {
      loading: false,
      periodo: 'hoy',
      desde: moment().startOf('month').format('YYYY-MM-DD'),
      hasta: moment().format('YYYY-MM-DD'),
      metrica: 'monto',
      vista: 'grafico',
      periodoOptions: [
        { label: 'Hoy', value: 'hoy' },
        { label: 'Ayer', value: 'ayer' },
        { label: 'Mes', value: 'mes' },
        { label: 'Año', value: 'anio' },
        { label: 'Rango', value: 'rango' }
      ],

      v: {
        desde: null,
        hasta: null,
        granularidad: 'hora',
        kpi: {
          monto: 0, cantidad: 0, ticket: 0, monto_anterior: 0, variacion: null,
          cobrado_efectivo: 0, cobrado_qr: 0, ordenes_cantidad: 0, ordenes_monto: 0
        },
        serie: [],
        vendedores: []
      },

      dashboard: null,
      kpiCaja: { dayIngresos: 0, dayEgresos: 0, dayCaja: 0, ordenesRetrasadas: 0, prestamosRetrasados: 0 },

      lineSeries: [{ name: 'Ingresos', data: [] }, { name: 'Egresos', data: [] }],
      lineOptions: {
        ...baseChart,
        colors: [C.serie, C.serie2],
        stroke: { width: 2, curve: 'straight' },
        markers: { size: 0, hover: { size: 5 } },
        legend: { show: true, position: 'top', horizontalAlign: 'right', fontSize: '11px', markers: { size: 5 }, itemMargin: { horizontal: 6, vertical: 0 } },
        tooltip: { shared: true, intersect: false, y: { formatter: val => `${Number(val || 0).toFixed(2)} Bs.` } }
      },

      barSeries: [{ name: 'Neto semanal', data: [] }],
      barOptions: {
        ...baseChart,
        colors: [C.serie],
        plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '55%' } },
        tooltip: { y: { formatter: val => `${Number(val || 0).toFixed(2)} Bs.` } }
      }
    }
  },

  computed: {
    esAdmin () {
      return this.$store?.user?.role === 'Administrador'
    },
    periodoLabel () {
      return { hoy: 'hoy', ayer: 'ayer', mes: 'del mes', anio: 'del año', rango: 'del rango' }[this.periodo]
    },
    granularidadLabel () {
      return { hora: 'hora', dia: 'día', mes: 'mes' }[this.v.granularidad] || 'día'
    },
    rangoTexto () {
      if (!this.v.desde) return ''
      const d = moment(this.v.desde)
      const h = moment(this.v.hasta)
      const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
      if (d.isSame(h, 'day')) return `${dias[d.day()]} ${d.format('DD/MM/YYYY')}`
      return `${d.format('DD/MM/YYYY')} — ${h.format('DD/MM/YYYY')}`
    },
    cajaFecha () {
      // Caja del dia: el ultimo dia del periodo
      return this.v.hasta || moment().format('YYYY-MM-DD')
    },
    cajaFechaLabel () {
      const f = moment(this.cajaFecha)
      if (f.isSame(moment(), 'day')) return 'hoy'
      if (f.isSame(moment().subtract(1, 'day'), 'day')) return 'ayer'
      return f.format('DD/MM')
    },
    cobradoTotal () {
      return Number(this.v.kpi.cobrado_efectivo || 0) + Number(this.v.kpi.cobrado_qr || 0)
    },
    pctEfectivo () {
      if (!this.cobradoTotal) return 50
      return Math.round((Number(this.v.kpi.cobrado_efectivo || 0) / this.cobradoTotal) * 100)
    },
    topVendedor () {
      return this.esAdmin ? (this.v.vendedores[0] || null) : null
    },
    maxSerie () {
      return Math.max(1, ...this.v.serie.map(r => Number(r.monto || 0)))
    },
    ventasSeries () {
      return [{
        name: this.metrica === 'monto' ? 'Monto' : 'Ventas',
        data: this.v.serie.map(r => Number(r[this.metrica] || 0))
      }]
    },
    ventasOptions () {
      const esMonto = this.metrica === 'monto'
      const muchas = this.v.serie.length > 16
      return {
        ...baseChart,
        colors: [C.serie],
        plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: muchas ? '75%' : '55%' } },
        xaxis: {
          ...baseChart.xaxis,
          categories: this.v.serie.map(r => r.label)
        },
        yaxis: {
          ...baseChart.yaxis,
          forceNiceScale: true,
          labels: { ...baseChart.yaxis.labels, formatter: esMonto ? compact : (val) => Number(val || 0).toFixed(0) }
        },
        tooltip: {
          y: {
            formatter: (val, { dataPointIndex }) => {
              const r = this.v.serie[dataPointIndex] || {}
              return `${Number(r.monto || 0).toFixed(2)} Bs. · ${r.cantidad || 0} venta(s)`
            },
            title: { formatter: () => '' }
          }
        }
      }
    }
  },

  watch: {
    periodo () { this.loadAll() },
    desde () { if (this.periodo === 'rango') this.loadVentas() },
    hasta () { if (this.periodo === 'rango') this.loadAll() }
  },

  mounted () {
    this.loadAll()
  },

  methods: {
    money (n) {
      return `${Number(n || 0).toFixed(2)}`
    },
    moneyShort (n) {
      const x = Number(n || 0)
      return `${x.toLocaleString('es-BO', { minimumFractionDigits: x >= 10000 ? 0 : 2, maximumFractionDigits: x >= 10000 ? 0 : 2 })} Bs`
    },
    fechaCorta (f) {
      return f ? moment(f).format('DD/MM') : ''
    },
    barPct (monto) {
      return Math.round((Number(monto || 0) / this.maxSerie) * 100)
    },
    sellerPct (monto) {
      const top = Number(this.v.vendedores[0]?.monto || 0)
      return top ? Math.round((Number(monto || 0) / top) * 100) : 0
    },

    async loadAll () {
      this.loading = true
      try {
        await this.loadVentas()
        await this.loadCaja()
      } finally {
        this.loading = false
      }
    },

    async loadVentas () {
      try {
        const params = { periodo: this.periodo }
        if (this.periodo === 'rango') {
          params.desde = this.desde
          params.hasta = this.hasta
        }
        const { data } = await this.$axios.get('dashboard/ventas', { params })
        this.v = {
          ...this.v,
          ...data,
          kpi: { ...this.v.kpi, ...(data.kpi || {}) },
          serie: data.serie || [],
          vendedores: data.vendedores || []
        }
      } catch (e) {
        this.$q.notify({ type: 'negative', message: e.response?.data?.message || 'Error al cargar ventas' })
      }
    },

    async loadCaja () {
      try {
        const { data } = await this.$axios.get('dashboard', { params: { date: this.cajaFecha, days: 56 } })
        this.dashboard = data
        const k = data?.kpi || {}
        this.kpiCaja = {
          dayIngresos: Number(k.dayIngresos || 0),
          dayEgresos: Number(k.dayEgresos || 0),
          dayCaja: Number(k.dayCaja || 0),
          ordenesRetrasadas: Number(k.ordenesRetrasadas || 0),
          prestamosRetrasados: Number(k.prestamosRetrasados || 0)
        }
        this.buildLineChart(14)
        this.buildWeeklyBars(8)
      } catch (e) {
        this.$q.notify({ type: 'negative', message: e.response?.data?.message || 'Error al cargar caja' })
      }
    },

    buildLineChart (days = 14) {
      const list = Array.isArray(this.dashboard?.days) ? this.dashboard.days : []
      const last = list.slice(-days)
      this.lineOptions = {
        ...this.lineOptions,
        xaxis: { ...this.lineOptions.xaxis, categories: last.map(x => moment(x.date).format('DD/MM')) }
      }
      this.lineSeries = [
        { name: 'Ingresos', data: last.map(x => Number(x.total_ingresos || 0)) },
        { name: 'Egresos', data: last.map(x => Number(x.total_egresos || 0)) }
      ]
    },

    buildWeeklyBars (weeks = 8) {
      const list = Array.isArray(this.dashboard?.days) ? this.dashboard.days : []
      const mapWeek = {}
      list.forEach(d => {
        const key = moment(d.date).format('GGGG-[W]WW')
        mapWeek[key] = (mapWeek[key] || 0) + Number(d.total_ingresos || 0) - Number(d.total_egresos || 0)
      })
      const keys = Object.keys(mapWeek).sort().slice(-weeks)
      this.barOptions = {
        ...this.barOptions,
        xaxis: { ...this.barOptions.xaxis, categories: keys.map(k => `S${k.split('W')[1]}`) }
      }
      this.barSeries = [{ name: 'Neto semanal', data: keys.map(k => Number(mapWeek[k] || 0)) }]
    }
  }
}
</script>

<style scoped>
.dash {
  background: #f4f5f7;
}

.line-1 {
  line-height: 1.2;
}

.dash-top {
  flex-wrap: wrap;
  row-gap: 6px;
}

.dash-periodos {
  max-width: 100%;
  overflow-x: auto;
}

.dash-toggle {
  border: 1px solid #dcdcdc;
  border-radius: 8px;
  overflow: hidden;
}

.dash-toggle :deep(.q-btn) {
  padding: 2px 12px;
  min-height: 30px;
}

.dash-date {
  width: 140px;
}

/* KPI tiles */
.kpi {
  height: 100%;
  background: #fff;
  border: 1px solid #e6e6e3;
  border-radius: 10px;
  padding: 8px 10px;
}

.kpi__label {
  font-size: 11px;
  color: #52514e;
  text-transform: uppercase;
  letter-spacing: .03em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.kpi__value {
  font-size: 20px;
  font-weight: 700;
  line-height: 1.25;
  color: #0b0b0b;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.kpi__sub {
  font-size: 11px;
  color: #6b6a66;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.split-bar {
  display: flex;
  gap: 2px;
  height: 6px;
  margin: 3px 0 2px;
}

.split-bar__seg {
  border-radius: 3px;
  min-width: 2px;
}

.split-bar__seg--efe,
.dot--efe {
  background: #2a78d6;
}

.split-bar__seg--qr,
.dot--qr {
  background: #eb6834;
}

.dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  margin-right: 3px;
  vertical-align: middle;
}

/* Paneles */
.panel {
  border-radius: 10px;
  border-color: #e6e6e3;
  height: 100%;
}

.panel__head {
  padding: 8px 12px 0;
}

.panel__title {
  font-size: 14px;
  font-weight: 700;
  line-height: 1.2;
}

.panel__body {
  padding: 4px 8px 8px;
}

.tabla-wrap {
  max-height: 250px;
  overflow: auto;
}

.tabla {
  font-variant-numeric: tabular-nums;
}

.tabla :deep(thead th) {
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 1;
}

.mini-bar {
  height: 5px;
  background: #f0f0ee;
  border-radius: 3px;
  overflow: hidden;
}

.mini-bar > div {
  height: 100%;
  background: #2a78d6;
  border-radius: 3px;
}

.top-seller {
  background: #fff8e1;
  border-radius: 8px;
  padding: 6px 8px;
}

.seller {
  padding: 4px 2px;
  font-size: 12.5px;
}

.seller__rank {
  width: 18px;
  color: #8a8984;
  font-size: 11px;
}

@media (max-width: 599px) {
  .kpi__value {
    font-size: 17px;
  }

  .dash-date {
    width: calc(50% - 30px);
  }
}
</style>
