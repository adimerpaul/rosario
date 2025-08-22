<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>

      <!-- Filtros -->
      <q-card-section class="row items-end q-col-gutter-sm">
        <div class="col-12 col-md-4">
          <q-input
            dense outlined clearable
            v-model="filters.search"
            label="Buscar por Nº / cliente / detalle…"
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
                  <div class="text-subtitle2 text-orange-9 text-weight-bold">Total retrasados</div>
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
          <div v-for="p in prestamos" :key="p.id" class="col-12 col-md-4">
            <q-card bordered flat class="loan-card">
              <div class="status-strip" :style="{ backgroundColor: colorDias(p.dias_retraso) }"></div>

              <q-card-section class="q-pb-xs">
                <div class="row items-center no-wrap">
                  <q-avatar icon="warning" :color="colorDias(p.dias_retraso)" text-color="white" size="32px"/>
                  <div class="q-ml-sm">
                    <div class="text-weight-bold">#{{ p.numero }}</div>
                    <div class="text-caption text-grey-7">Límite: {{ date(p.fecha_limite) }}</div>
                  </div>
                  <q-space/>
                  <q-chip dense square text-color="white" :style="{backgroundColor: colorDias(p.dias_retraso)}">
                    {{ p.dias_retraso }} d.
                  </q-chip>
                </div>
              </q-card-section>

              <q-separator spaced />

              <q-card-section class="q-pt-none">
                <div class="row items-center">
                  <q-icon name="person" size="18px" class="q-mr-xs"/>
                  <div class="text-body2 ellipsis-2-lines">{{ p.cliente?.name || 'N/A' }}</div>
                </div>

                <div class="row q-mt-sm">
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Prestado</div>
                    <div class="text-weight-medium">{{ money(p.valor_prestado) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Cargos</div>
                    <div class="text-weight-medium">
                      {{ money(cargos(p)) }}
                    </div>
                    <div class="text-caption text-grey">{{ p.interes }}% + {{ p.almacen }}%</div>
                  </div>
                </div>

                <div class="row q-mt-sm">
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Saldo</div>
                    <div class="text-weight-medium">{{ money(p.saldo) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Estado</div>
                    <q-chip dense square text-color="white" :style="{backgroundColor: estadoColor(p.estado)}">
                      {{ p.estado }}
                    </q-chip>
                  </div>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-actions align="between" class="q-pa-sm">
                <div class="row items-center">
                  <q-btn dense flat icon="call" :href="telHref(p.celular)" :disable="!p.celular" title="Llamar"/>
                  <q-btn dense flat icon="whatsapp" :href="waHref(p)" target="_blank" title="WhatsApp"/>
                </div>
                <q-btn dense flat icon="edit" label="Editar / Pagar" @click="$router.push('/prestamos/editar/' + p.id)"/>
              </q-card-actions>
            </q-card>
          </div>

          <div v-if="!prestamos.length && !loading" class="col-12">
            <q-banner class="bg-grey-2 text-grey-8">No hay préstamos retrasados con los filtros aplicados.</q-banner>
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
        const { data } = await this.$axios.get('prestamosRetrasados', {
          params: {
            ...this.filters,
            page: this.page,
            per_page: this.perPage
          }
        })
        this.prestamos  = data.data || data
        this.totalPages = data.last_page || 1
        this.page       = data.current_page || this.page
        this.calcularResumen()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al obtener préstamos retrasados')
      } finally {
        this.loading = false
      }
    },
    calcularResumen () {
      const n   = this.prestamos.length || 1
      const sum = (arr, fn) => arr.reduce((s, x) => s + fn(x), 0)
      const vp  = x => Number(x.valor_prestado || 0)
      const ci  = x => vp(x) * Number(x.interes || 0) / 100
      const ca  = x => vp(x) * Number(x.almacen || 0) / 100

      this.resumen.total    = this.prestamos.length
      this.resumen.saldo    = sum(this.prestamos, x => Number(x.saldo || 0))
      this.resumen.promDias = (sum(this.prestamos, x => Number(x.dias_retraso || 0)) / n).toFixed(1)
    },
    cargos (p) {
      const vp = Number(p.valor_prestado || 0)
      return vp * Number(p.interes || 0) / 100 + vp * Number(p.almacen || 0) / 100
    },
    money (v) { return Number(v || 0).toFixed(2) },
    date (v) { return v ? moment(v).format('YYYY-MM-DD') : '—' },
    colorDias (d) {
      if (d >= 8) return '#e53935'   // rojo
      if (d >= 4) return '#fb8c00'   // naranja fuerte
      return '#fdd835'               // amarillo
    },
    estadoColor (e) {
      if (e === 'Pendiente') return '#fb8c00'
      if (e === 'Pagado')    return '#21ba45'
      if (e === 'Cancelado') return '#e53935'
      if (e === 'Vencido')   return '#f4511e'
      return '#9e9e9e'
    },
    telHref (cel) {
      const c = String(cel || '').trim()
      return c ? `tel:${c}` : null
    },
    waHref (p) {
      const phone = String(p.celular || '').replace(/\D/g,'')
      const msg = encodeURIComponent(
        `Hola ${p.cliente?.name || ''}, le recordamos su préstamo ${p.numero}. ` +
        `Fecha límite: ${this.date(p.fecha_limite)}. ` +
        `Presenta ${p.dias_retraso} día(s) de retraso. Saldo: Bs. ${this.money(p.saldo)}.`
      )
      return phone ? `https://wa.me/${phone}?text=${msg}` : null
    }
  }
}
</script>

<style scoped>
.loan-card {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  transition: transform .12s ease, box-shadow .12s ease;
}
.loan-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(0,0,0,.08); }
.status-strip { position: absolute; top:0; left:0; width:100%; height:4px; }
.ellipsis-2-lines {
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
</style>
