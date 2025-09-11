<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section>
        <div class="row q-col-gutter-sm">
          <!-- Filtros -->
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

          <!-- GRID DE CARDS -->
          <div class="col-12 q-mt-sm">
            <div class="row q-col-gutter-sm">
              <div
                v-for="p in prestamos"
                :key="p.id"
                class="col-12 col-sm-6 col-md-4"
              >
                <q-card bordered flat class="loan-card">
                  <div class="status-strip" :style="{ backgroundColor: estadoColor(p) }"></div>

                  <q-card-section class="q-pb-xs">
                    <div class="row items-center no-wrap">
                      <q-avatar :icon="estadoIcon(p)" :color="estadoColor(p)" text-color="white" size="32px"/>
                      <div class="q-ml-sm">
                        <div class="text-weight-bold">#{{ p.numero }}</div>
                        <div class="text-caption text-grey-7">
                          {{ fmtFecha(p.fecha_creacion) }}
                          <span v-if="p.fecha_limite"> — vence: {{ fmtFecha(p.fecha_limite) }}</span>
                        </div>
                        <div class="q-mt-xs">
                          <q-chip dense square :style="{backgroundColor: estadoColor(p)}" text-color="white">
                            {{ estadoTexto(p) }}
                          </q-chip>
                          <q-chip dense outline color="primary" class="q-ml-xs">
                            {{ dias(p) }} días
                          </q-chip>
                          <q-chip dense outline color="teal" class="q-ml-xs">
                            {{ tasaMensual(p) }}%/mes
                          </q-chip>
                          <q-chip dense outline color="indigo" class="q-ml-xs">
                            {{ money(tasaDiaria(p)*100) }}%/día
                          </q-chip>
                        </div>
                      </div>
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
                        <div class="text-caption text-grey-7">Cargos estimados</div>
                        <div class="text-weight-medium">{{ money(cargosEstimados(p)) }}</div>
                        <div class="text-caption text-grey">{{ p.interes }}% + {{ p.almacen }}%</div>
                      </div>
                    </div>

                    <div class="row q-mt-sm">
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Cargo diario</div>
                        <div class="text-weight-medium">{{ money(cargoDiario(p)) }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Saldo HOY</div>
                        <div class="text-h6 text-weight-bold">
                          {{ money(p.saldo) }}
                          <q-badge v-if="esVencido(p)" color="negative" class="q-ml-xs">Vencido</q-badge>
                        </div>
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
      // Resume SOLO la página actual (coincide con lo que ves)
      const sum = (arr, fn) => arr.reduce((s, x) => s + fn(x), 0)
      const vp  = x => Number(x.valor_prestado || 0)
      const ci  = x => this.cargosEstimados(x) // usamos la misma fórmula de estimación
      this.resumen.prestado = sum(this.prestamos, vp)
      this.resumen.cargos   = sum(this.prestamos, ci)
      this.resumen.saldo    = sum(this.prestamos, x => Number(x.saldo || 0))
    },

    /* ========== UI helpers ========== */
    fmtFecha (f) { return f ? moment(f).format('YYYY-MM-DD') : '—' },
    money (v) { return Number(v || 0).toFixed(2) },

    estadoColor (p) {
      if (p.estado === 'Pagado') return '#21ba45'
      if (p.estado === 'Cancelado') return '#e53935'
      if (this.esVencido(p)) return '#f4511e'
      if (p.estado === 'Pendiente') return '#fb8c00'
      return '#9e9e9e'
    },
    estadoIcon (p) {
      if (p.estado === 'Pagado') return 'check_circle'
      if (p.estado === 'Cancelado') return 'block'
      if (this.esVencido(p)) return 'warning'
      if (p.estado === 'Pendiente') return 'hourglass_empty'
      return 'payments'
    },
    estadoTexto (p) {
      return this.esVencido(p) && p.estado === 'Pendiente' ? 'Vencido' : p.estado
    },
    esVencido (p) {
      if (!p.fecha_limite) return false
      const hoy = moment().startOf('day')
      return hoy.isAfter(moment(p.fecha_limite, 'YYYY-MM-DD').startOf('day')) && p.estado !== 'Pagado' && p.estado !== 'Cancelado'
    },

    /* ========== Cálculos “bonitos” para el card ========== */
    dias (p) {
      const ini = p.fecha_creacion ? moment(p.fecha_creacion, 'YYYY-MM-DD') : moment()
      return Math.max(0, moment().startOf('day').diff(ini.startOf('day'), 'days'))
    },
    tasaMensual (p) {
      return Number(p.interes || 0) + Number(p.almacen || 0) // % mensual total
    },
    tasaDiaria (p) {
      return this.tasaMensual(p) / 100 / 30 // simple/30
    },
    cargoDiario (p) {
      const vp = Number(p.valor_prestado || 0)
      return +(vp * this.tasaDiaria(p)).toFixed(2)
    },
    cargosEstimados (p) {
      return +(this.cargoDiario(p) * this.dias(p)).toFixed(2)
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
