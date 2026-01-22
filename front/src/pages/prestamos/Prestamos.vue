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
                      <div class="">
                        <div class="text-weight-bold">#{{ p.numero }}</div>
                        <div class="text-caption text-grey-7">
                          {{ fmtFecha(p.fecha_creacion) }} <br>
                          <span v-if="p.fecha_limite"> Mes Cance: {{ fmtFecha(p.fecha_limite) }}</span> <br>
                          <span v-if="p.fecha_limite"> V: {{ fmtFecha(p.fecha_cancelacion) }}</span> <br>
                        </div>
                        <div class="q-mt-xs">
                          <q-chip dense square :style="{backgroundColor: estadoColor(p)}" text-color="white">
                            {{ estadoTexto(p) }}
                          </q-chip>
                          <q-chip dense outline color="primary" class="q-ml-xs">
                            {{ p.dias_transcurridos }} días
                          </q-chip>
                          <q-chip dense outline color="teal" class="q-ml-xs">
                            {{ tasaMensual(p) }}%/mes
                          </q-chip>
                          <q-chip dense outline color="indigo" class="q-ml-xs">
<!--                            {{ money(tasaDiaria(p)*100) }}%/día-->
                            {{ p.cargo_diario }} /día
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
                      <div><span class="text-bold">Detalle: </span>{{p.detalle}}</div>
                    </div>

                    <div class="row q-mt-sm">
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Prestado - deuda</div>
                        <div class="text-weight-medium">
                          {{ money(p.valor_prestado) }}
                          <span class="text-caption text-red">{{ money(p.total_deuda) }}</span>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Cargo mensual</div>
<!--                        <div class="text-weight-medium">{{ money(cargosEstimados(p)) }}</div>-->
<!--                        <div class="text-caption text-grey">{{ p.interes }}% + {{ p.almacen }}%</div>-->
                        <div class="text-weight-medium">{{ p.total_deuda * (parseFloat(p.interes || 0) + parseFloat(p.almacen || 0)) / 100 | money }}</div>
                        <div class="text-caption text-grey">{{ p.interes }}% + {{ p.almacen }}%</div>
                      </div>
                    </div>

                    <div class="row q-mt-sm">
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Intereses</div>
<!--                        <div class="text-weight-medium">{{ money(cargoDiario(p)) }}</div>-->
<!--                        <div class="text-weight-medium">{{ p.saldo - p.valor_prestado | money }}aa</div>-->
                        <div class="text-weight-medium">{{ p.deuda_interes }}</div>
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
                      {{p.detalle}}
                    </div>
                  </q-card-section>

                  <q-separator />

                  <q-card-actions align="between" class="q-pa-sm">

                    <q-btn dense flat icon="edit" label="Editar" @click="$router.push('/prestamos/editar/' + p.id)" no-caps
                            v-if="p.estado == 'Activo'"
                    />

                    <q-btn-dropdown dense no-caps color="primary" label="Más">
                      <q-list>
                        <q-item clickable v-ripple @click="openMensualidad(p)" v-close-popup
                                v-if="p.dias_transcurridos >= 30 "
                        >
                          <q-item-section avatar><q-icon name="payment"/></q-item-section>
                          <q-item-section>Pagar mensualidad</q-item-section>
                        </q-item>

                        <q-item clickable v-ripple @click="openCargos(p)" v-close-popup
                                v-if="p.dias_transcurridos > 0"
                        >
                          <q-item-section avatar><q-icon name="attach_money"/></q-item-section>
                          <q-item-section>Pagar cargos</q-item-section>
                        </q-item>

                        <q-item clickable v-ripple @click="openTotal(p)" v-close-popup v-if="p.estado !== 'Fundido'">
                          <q-item-section avatar><q-icon name="money_off"/></q-item-section>
                          <q-item-section>Pagar todo</q-item-section>
                        </q-item>
                        <q-item clickable v-ripple @click="imprimir(p)" v-close-popup>
                          <q-item-section avatar><q-icon name="print"/></q-item-section>
                          <q-item-section>Imprimir Contrato</q-item-section>
                        </q-item>
                        <q-item clickable v-ripple @click="imprimirCambiodeMoneda(p)" v-close-popup>
                          <q-item-section avatar><q-icon name="picture_as_pdf"/></q-item-section>
                          <q-item-section>Imprimir Cambio de Moneda</q-item-section>
                        </q-item>
<!--                        btn fundir-->
                        <q-item clickable v-ripple @click="fundirPrestamo(p)" v-close-popup v-if="p.estado !== 'Fundido'">
                          <q-item-section avatar><q-icon name="merge_type"/></q-item-section>
                          <q-item-section>Fundir Préstamo</q-item-section>
                        </q-item>
                      </q-list>
                    </q-btn-dropdown>

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
    <!-- DIALOG: Pagar Mensualidad -->
    <q-dialog v-model="dlgMensualidad.open" persistent>
      <q-card style="min-width:420px">
        <q-card-section class="row items-center q-gutter-sm">
          <q-icon name="payment" size="28px" color="primary"/>
          <div class="text-h6">Pagar mensualidad (30 días)</div>
        </q-card-section>
        <q-separator/>

        <q-card-section>
          <div class="text-caption text-grey-7">Préstamo #{{ dlgMensualidad.p?.numero }}</div>
          <div class="row q-col-gutter-md q-mt-xs">
            <div class="col-6">
              <div class="text-caption">Cargo diario</div>
              <div class="text-body1">{{ money(cargoDiarioPreview) }}</div>
            </div>
            <div class="col-6">
              <div class="text-caption">Saldo actual</div>
              <div class="text-body1">{{ money(saldoPreview) }}</div>
            </div>
            <div class="col-6">
              <div class="text-caption">Monto a pagar (30 días)</div>
              <div class="text-h6">{{ dlgMensualidad.p?.total_deuda * (parseFloat(dlgMensualidad.p?.interes || 0) + parseFloat(dlgMensualidad.p?.almacen || 0)) / 100 | money }}</div>
            </div>
            <div class="col-6">
              <q-select dense outlined v-model="dlgMensualidad.metodo" :options="metodoOptions" label="Método de pago"/>
            </div>
            <div class="col-12 text-grey-7 text-caption">
              * Al confirmar, la <b>fecha límite</b> se moverá <b>+1 mes</b>.
            </div>
          </div>
        </q-card-section>

        <q-separator/>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup/>
          <q-btn color="primary" :loading="loading" label="Confirmar" @click="confirmMensualidad"/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG: Pagar Cargos -->
    <q-dialog v-model="dlgCargos.open" persistent>
      <q-card style="min-width:420px">
        <q-card-section class="row items-center q-gutter-sm">
          <q-icon name="attach_money" size="28px" color="teal"/>
          <div class="text-h6">Pagar cargos acumulados</div>
        </q-card-section>
        <q-separator/>

        <q-card-section>
          <div class="text-caption text-grey-7">Préstamo #{{ dlgCargos.p?.numero }}</div>
          <div class="row q-col-gutter-md q-mt-xs">
            <div class="col-6">
              <div class="text-caption">Días transcurridos</div>
              <div class="text-body1">
                  {{ dlgCargos.p?.dias_transcurridos }}
              </div>
            </div>
            <div class="col-6">
              <div class="text-caption">Cargos acumulados</div>
              <div class="text-body1">
<!--                {{ money(cargosAcumuladosPreview) }}-->
<!--                deuda_interes-->
                {{ money(dlgCargos.p?.deuda_interes) }}
              </div>
            </div>
            <div class="col-6">
              <div class="text-caption">Saldo actual</div>
              <div class="text-body1">{{ money(saldoPreview) }}</div>
            </div>
            <div class="col-6">
              <div class="text-caption">Monto a pagar (sólo cargos)</div>
              <div class="text-h6">
<!--                {{ money(montoCargosPreview) }}-->
                {{ money(dlgCargos.p?.deuda_interes) }}
              </div>
            </div>
            <div class="col-12">
              <q-select dense outlined v-model="dlgCargos.metodo" :options="metodoOptions" label="Método de pago"/>
            </div>
            <div class="col-12 text-grey-7 text-caption">
              * Al confirmar, la <b>fecha límite</b> se ajustará a <b>hoy</b>.
            </div>
          </div>
        </q-card-section>

        <q-separator/>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup/>
          <q-btn color="teal" :loading="loading" label="Confirmar" @click="confirmCargos"/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG: Pagar Todo -->
    <q-dialog v-model="dlgTotal.open" persistent>
      <q-card style="min-width:420px">
        <q-card-section class="row items-center q-gutter-sm">
          <q-icon name="money_off" size="28px" color="negative"/>
          <div class="text-h6">Pagar TODO</div>
        </q-card-section>
        <q-separator/>

        <q-card-section>
          <div class="text-caption text-grey-7">Préstamo #{{ dlgTotal.p?.numero }}</div>
          <div class="row q-col-gutter-md q-mt-xs">
            <div class="col-2">
              <div class="text-caption">
                <q-toggle v-if="dlgTotal.p?.dias_transcurridos< 7"
                          v-model="dlgTotal.p.omitir_cargos"
                          label="Incluir cargos"
                          left-label
                          dense/>
              </div>
            </div>
            <div class="col-5">
              <div class="text-caption">Saldo total a liquidar</div>
              <div class="text-h6">{{ money(saldoPreview) }}</div>
            </div>
            <div class="col-5">
              <q-select dense outlined v-model="dlgTotal.metodo" :options="metodoOptions" label="Método de pago"/>
            </div>
            <div class="col-12 text-grey-7 text-caption">
              * El préstamo quedará en estado <b>Pagado</b>.
            </div>
          </div>
        </q-card-section>

        <q-separator/>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup/>
          <q-btn color="negative" :loading="loading" label="Confirmar" @click="confirmTotal"/>
        </q-card-actions>
      </q-card>
    </q-dialog>
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
      estados: ['Todos','Pendiente','Pagado','Cancelado','Vencido','Activo'],
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
      loading: false,
      metodoOptions: ['Efectivo','Transferencia','Tarjeta','QR'],

      dlgMensualidad: { open: false, p: null, metodo: 'Efectivo', preview: null },
      dlgCargos:      { open: false, p: null, metodo: 'Efectivo', preview: null },
      dlgTotal:       { open: false, p: null, metodo: 'Efectivo', preview: null },
    }
  },
  mounted () {
    this.getUsuarios()
    this.getPrestamos()
  },
  computed: {
    // cálculo local para mostrar PREVIEW (no es el pago final; el server recalcula)
    cargoDiarioPreview () {
      const p = this._dlgAny()?.p
      if (!p) return 0
      const tasaMensual = Number(p.interes || 0) + Number(p.almacen || 0)
      const tasaDiaria  = tasaMensual / 100 / 30
      return +(Number(p.valor_prestado || 0) * tasaDiaria).toFixed(2)
    },
    diasPreview () {
      const p = this._dlgAny()?.p
      if (!p) return 0
      const ini = p.fecha_creacion ? moment(p.fecha_creacion, 'YYYY-MM-DD') : moment()
      return Math.max(0, moment().startOf('day').diff(ini.startOf('day'), 'days'))
    },
    cargosAcumuladosPreview () {
      return +(this.cargoDiarioPreview * this.diasPreview).toFixed(2)
    },
    saldoPreview () {
      const p = this._dlgAny()?.p
      if (!p) return 0
      if (p.omitir_cargos) {
        const capital = Number(p.valor_prestado || 0)
        return Math.min(capital, Number(p.saldo || 0))
      }
      return Number(p.saldo || 0)
    },
    montoMensualidadPreview () {
      return Math.min(this.saldoPreview, +(this.cargoDiarioPreview * 30).toFixed(2))
    },
    montoCargosPreview () {
      // cargos - pagado ≈ saldo - capital
      const p = this._dlgAny()?.p
      if (!p) return 0
      const capital = Number(p.valor_prestado || 0)
      return Math.max(0, +(this.saldoPreview - capital).toFixed(2))
    },
  },
  methods: {
    _dlgAny () {
      if (this.dlgMensualidad.open) return this.dlgMensualidad
      if (this.dlgCargos.open)      return this.dlgCargos
      if (this.dlgTotal.open)       return this.dlgTotal
      return null
    },
    openMensualidad (p) {
      this.dlgMensualidad = { open: true, p, metodo: 'Efectivo', preview: null }
    },
    openCargos (p) {
      this.dlgCargos = { open: true, p, metodo: 'Efectivo', preview: null }
    },
    openTotal (p) {
      p.omitir_cargos = false // reset
      this.dlgTotal = { open: true, p, metodo: 'Efectivo', preview: null }
    },
    imprimir(p) {
      const url = this.$axios.defaults.baseURL + `/prestamos/${p.id}/pdf`
      window.open(url, '_blank')
    },
    fundirPrestamo(p) {
      // this.loading = true
      // this.$axios.post(`prestamos/${p.id}/fundir`).then(() => {
      //   this.$q.notify({ type:'positive', message:'Préstamo fundido exitosamente' })
      //   this.getPrestamos()
      // }).catch(e => {
      //   this.$alert?.error?.(e.response?.data?.message || 'Error al fundir préstamo')
      // }).finally(() => { this.loading = false })
      this.$q.dialog({
        title: 'Fundir Préstamo',
        message: `¿Estás seguro de fundir el préstamo #${p.numero}? Esta acción no se puede deshacer.`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.loading = true
        this.$axios.post(`prestamos/${p.id}/fundir`).then(() => {
          this.$q.notify({ type:'positive', message:'Préstamo fundido exitosamente' })
          this.getPrestamos()
        }).catch(e => {
          this.$alert?.error?.(e.response?.data?.message || 'Error al fundir préstamo')
        }).finally(() => { this.loading = false })
      })
    },
    imprimirCambiodeMoneda(p) {
      const url = this.$axios.defaults.baseURL + `/prestamos/${p.id}/cambio/pdf`
      window.open(url, '_blank')
    },
    async confirmMensualidad () {
      const p = this.dlgMensualidad.p
      try {
        this.loading = true
        await this.$axios.post(`prestamos/${p.id}/pagar-mensualidad`, { metodo: this.dlgMensualidad.metodo })
        this.$q.notify({ type:'positive', message:'Mensualidad pagada' })
        this.dlgMensualidad.open = false
        this.getPrestamos()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al pagar mensualidad')
      } finally { this.loading = false }
    },
    async confirmCargos () {
      const p = this.dlgCargos.p
      try {
        this.loading = true
        await this.$axios.post(`prestamos/${p.id}/pagar-cargos`, {
          metodo: this.dlgCargos.metodo,
          monto: this.dlgCargos.p?.deuda_interes
        })
        this.$q.notify({ type:'positive', message:'Cargos pagados' })
        this.dlgCargos.open = false
        this.getPrestamos()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al pagar cargos')
      } finally { this.loading = false }
    },
    async confirmTotal () {
      const p = this.dlgTotal.p
      try {
        this.loading = true
        await this.$axios.post(`prestamos/${p.id}/pagar-todo`, {
          metodo: this.dlgTotal.metodo,
          monto: this.saldoPreview,
          omitir_cargos: this.dlgTotal.p.omitir_cargos || false,
        })
        this.$q.notify({ type:'positive', message:'Deuda liquidada' })
        this.dlgTotal.open = false
        this.getPrestamos()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al pagar todo')
      } finally { this.loading = false }
    },
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
          // this.calcularResumen()
        })
        .catch(e => {
          this.$alert?.error?.(e.response?.data?.message || 'Error al obtener préstamos')
        })
        .finally(() => { this.loading = false })
    },
    // calcularResumen () {
    //   // Resume SOLO la página actual (coincide con lo que ves)
    //   const sum = (arr, fn) => arr.reduce((s, x) => s + fn(x), 0)
    //   const vp  = x => Number(x.valor_prestado || 0)
    //   const ci  = x => this.cargosEstimados(x) // usamos la misma fórmula de estimación
    //   this.resumen.prestado = sum(this.prestamos, vp)
    //   this.resumen.cargos   = sum(this.prestamos, ci)
    //   this.resumen.saldo    = sum(this.prestamos, x => Number(x.saldo || 0))
    // },

    /* ========== UI helpers ========== */
    fmtFecha (f) { return f ? moment(f).format('YYYY-MM-DD') : '—' },
    money (v) { return Number(v || 0).toFixed(2) },

    estadoColor (p) {
      if (p.estado === 'Activo') return '#21ba45'
      if (p.estado === 'Cancelado') return '#e53935'
      if (this.esVencido(p)) return '#f4511e'
      if (p.estado === 'Entregado') return '#fb8c00'
      return '#9e9e9e'
    },
    estadoIcon (p) {
      if (p.estado === 'Activo') return 'check_circle'
      if (p.estado === 'Cancelado') return 'block'
      if (this.esVencido(p)) return 'warning'
      if (p.estado === 'Entregado') return 'hourglass_empty'
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
    // dias (p) {
    //   const ini = p.fecha_creacion ? moment(p.fecha_creacion, 'YYYY-MM-DD') : moment()
    //   return Math.max(0, moment().startOf('day').diff(ini.startOf('day'), 'days'))
    // },
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
    // cargosEstimados (p) {
    //   return +(this.cargoDiario(p) * this.dias(p)).toFixed(2)
    // }
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
