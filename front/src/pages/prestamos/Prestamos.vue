<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section>
        <div class="row q-col-gutter-sm">
          <!-- Filtros -->
<!--          <div class="col-12 col-md-3">-->
<!--            <q-input-->
<!--              type="date"-->
<!--              label="Fecha Inicio"-->
<!--              dense outlined-->
<!--              v-model="filters.fecha_inicio"-->
<!--              @update:model-value="resetAndFetch"-->
<!--            />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-3">-->
<!--            <q-input-->
<!--              type="date"-->
<!--              label="Fecha Fin"-->
<!--              dense outlined-->
<!--              v-model="filters.fecha_fin"-->
<!--              @update:model-value="resetAndFetch"-->
<!--            />-->
<!--          </div>-->
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.user_id"
              :options="usuarios"
              option-label="name"
              option-value="id"
              emit-value map-options
              dense outlined
              label="Usuario"
              @update:model-value="resetAndFetch"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.estado"
              :options="estados"
              dense outlined
              label="Estado"
              @update:model-value="resetAndFetch"
            />
          </div>

          <!-- Búsqueda + por página -->
          <div class="col-12 col-md-6">
            <q-input
              v-model="filters.search"
              dense outlined
              placeholder="Buscar por Nº, nombre o CI…"
              :debounce="400"
              @update:model-value="resetAndFetch"
            >
              <template #append><q-icon name="search" /></template>
            </q-input>
          </div>

          <div class="col-12 col-md-2">
            <q-select
              dense outlined
              v-model="perPage"
              :options="perPageOptions"
              label="Por página"
              emit-value map-options
              @update:model-value="resetAndFetch"
            />
          </div>

          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn
              color="primary"
              label="Actualizar"
              icon="refresh"
              :loading="loading"
              no-caps
              @click="getPrestamos"
            />
          </div>
          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn
              color="green"
              label="Nuevo Préstamo"
              icon="add"
              no-caps
              @click="$router.push('/prestamos/crear')"
            />
          </div>

          <!-- Resumen -->
<!--          <div class="col-12 col-md-8 q-mt-sm">-->
<!--            <div class="row q-col-gutter-sm">-->
<!--              <div class="col-12 col-md-4">-->
<!--                <q-card bordered class="bg-blue-1">-->
<!--                  <q-card-section class="row items-center">-->
<!--                    <q-icon name="savings" color="blue" size="lg" class="q-mr-sm"/>-->
<!--                    <div>-->
<!--                      <div class="text-subtitle1 text-weight-bold text-blue">Prestado</div>-->
<!--                      <div class="text-h6">{{ money(resumen.prestado) }}</div>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
<!--              <div class="col-12 col-md-4">-->
<!--                <q-card bordered class="bg-orange-1">-->
<!--                  <q-card-section class="row items-center">-->
<!--                    <q-icon name="stacked_bar_chart" color="orange" size="lg" class="q-mr-sm"/>-->
<!--                    <div>-->
<!--                      <div class="text-subtitle1 text-weight-bold text-orange">Cargos (Int+Alm)</div>-->
<!--                      <div class="text-h6">{{ money(resumen.cargos) }}</div>-->
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
<!--                      <div class="text-h6">{{ money(resumen.saldo) }}</div>-->
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
                v-for="p in prestamos"
                :key="p.id"
                class="col-12 col-sm-6 col-md-4"
              >
                <q-card bordered flat class="loan-card">
                  <div class="status-strip" :style="{ backgroundColor: getEstadoColor(p.estado) }"></div>

                  <q-card-section class="q-pb-xs">
                    <div class="row items-center no-wrap">
                      <q-avatar :icon="getEstadoIcon(p.estado)" :color="getEstadoColor(p.estado)" text-color="white" size="32px"/>
                      <div class="q-ml-sm">
                        <div class="text-weight-bold">#{{ p.numero }}</div>
                        <div class="text-caption text-grey-7">{{ String(p.fecha_creacion).substring(0,10) }}</div>
                      </div>
                      <q-space/>
                      <q-chip dense square text-color="white" :style="{backgroundColor: getEstadoColor(p.estado)}">
                        {{ p.estado }}
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
                        <div class="text-weight-medium">{{ money(cargosTotales(p)) }}</div>
                        <div class="text-caption text-grey">{{ p.interes }}% + {{ p.almacen }}%</div>
                      </div>
                    </div>

                    <div class="row q-mt-sm">
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Peso (kg)</div>
                        <div class="text-weight-medium">{{ Number(p.peso || 0).toFixed(3) }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Saldo</div>
                        <div class="text-weight-medium">{{ money(p.saldo) }}</div>
                      </div>
                    </div>

                    <div class="row items-center q-mt-sm text-caption text-grey">
                      <q-icon name="account_circle" size="16px" class="q-mr-xs"/>
                      {{ p.user?.name || '—' }}
                    </div>
                  </q-card-section>

                  <q-separator />

                  <q-card-actions align="between" class="q-pa-sm">
                    <q-btn dense flat icon="edit" label="Editar" @click="$router.push('/prestamos/editar/' + p.id)" />
                  </q-card-actions>
                </q-card>
              </div>
            </div>

            <!-- Paginación + resumen -->
            <div class="row items-center justify-between q-mt-sm" v-if="totalPages > 1">
              <div class="col-auto text-caption text-grey-8 q-ml-sm">
                Mostrando {{ from }}–{{ to }} de {{ totalItems }}
              </div>
              <div class="col-auto">
                <q-pagination
                  v-model="page"
                  :max="totalPages"
                  :max-pages="6"
                  boundary-numbers
                  color="primary"
                  :disable="loading"
                  @update:model-value="getPrestamos"
                />
              </div>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'PrestamosPage',
  data () {
    return {
      prestamos: [],
      usuarios: [],
      estados: ['Todos','Pendiente','Pagado','Cancelado','Vencido'],
      filters: {
        fecha_inicio: moment().startOf('week').format('YYYY-MM-DD'),
        fecha_fin: moment().endOf('week').format('YYYY-MM-DD'),
        user_id: null,
        estado: 'Todos',
        search: ''
      },
      page: 1,
      perPage: 12,
      perPageOptions: [6, 12, 24, 36, 60].map(v => ({ label: String(v), value: v })),
      totalPages: 1,
      totalItems: 0,
      from: 0,
      to: 0,
      resumen: { prestado: 0, cargos: 0, saldo: 0 },
      loading: false
    }
  },
  mounted () {
    this.getUsuarios()
    this.getPrestamos()
  },
  methods: {
    resetAndFetch () {
      this.page = 1
      this.getPrestamos()
    },
    getUsuarios () {
      this.$axios.get('users').then(res => {
        this.usuarios = [{ id: null, name: 'Todos' }, ...res.data]
      })
    },
    getPrestamos () {
      this.loading = true
      this.$axios.get('prestamos', {
        params: { ...this.filters, page: this.page, per_page: this.perPage }
      })
        .then(res => {
          const r = res.data
          this.prestamos   = r.data || r
          this.totalPages  = r.last_page || 1
          this.page        = r.current_page || 1
          this.totalItems  = r.total || (r.data ? r.data.length : this.prestamos.length)
          this.from        = r.from || ((this.page - 1) * this.perPage + (this.totalItems ? 1 : 0))
          this.to          = r.to || Math.min(this.page * this.perPage, this.totalItems)
          this.calcularResumen()
        })
        .catch(e => {
          this.$alert?.error?.(e.response?.data?.message || 'Error al obtener préstamos')
        })
        .finally(() => { this.loading = false })
    },
    calcularResumen () {
      // Nota: resume SOLO los préstamos de la página actual
      const sum = (arr, fn) => arr.reduce((s, x) => s + fn(x), 0)
      const vp  = x => Number(x.valor_prestado || 0)
      const ci  = x => vp(x) * Number(x.interes || 0) / 100
      const ca  = x => vp(x) * Number(x.almacen || 0) / 100

      this.resumen.prestado = sum(this.prestamos, vp)
      this.resumen.cargos   = sum(this.prestamos, x => ci(x) + ca(x))
      this.resumen.saldo    = sum(this.prestamos, x => Number(x.saldo || 0))
    },
    cargosTotales (p) {
      const vp = Number(p.valor_prestado || 0)
      return vp * Number(p.interes || 0) / 100 + vp * Number(p.almacen || 0) / 100
    },
    getEstadoColor (e) {
      if (e === 'Pendiente') return '#fb8c00'
      if (e === 'Pagado')    return '#21ba45'
      if (e === 'Cancelado') return '#e53935'
      if (e === 'Vencido')   return '#f4511e'
      return '#9e9e9e'
    },
    getEstadoIcon (e) {
      if (e === 'Pendiente') return 'hourglass_empty'
      if (e === 'Pagado')    return 'check_circle'
      if (e === 'Cancelado') return 'block'
      if (e === 'Vencido')   return 'warning'
      return 'payments'
    },
    money (v) { return Number(v || 0).toFixed(2) }
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
