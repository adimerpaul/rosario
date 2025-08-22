<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section class="q-pa-sm">
        <div class="row items-center">
          <q-btn
            icon="arrow_back"
            @click="$router.push('/prestamos')"
            no-caps
            size="10px"
            color="primary"
            label="Atrás"
          />
          <div class="text-h6 q-ml-sm">Editar Préstamo #{{ prestamo.numero }}</div>
          <q-space />
          <q-chip
            :color="prestamo.estado==='Pendiente' ? 'orange' : prestamo.estado==='Pagado' ? 'green' : 'red'"
            text-color="white"
            dense
            size="10px"
          >
            {{ prestamo.estado }}
          </q-chip>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <div class="row q-col-gutter-sm">
          <!-- Cliente: SOLO LECTURA -->
          <div class="col-12 col-md-4">
            <q-card flat bordered>
              <q-card-section class="q-pa-sm text-bold">Cliente</q-card-section>
              <q-separator />
              <q-card-section class="q-pa-sm">
                <q-field label="Nombre" stack-label outlined dense>
                  <template #control>
                    <div class="q-pl-sm q-pt-xs q-pb-xs">{{ prestamo.cliente?.name || 'N/A' }}</div>
                  </template>
                </q-field>
                <q-input
                  label="CI"
                  outlined dense
                  :model-value="prestamo.cliente?.ci || '—'"
                  readonly
                  class="q-mt-sm"
                />
                <q-input
                  label="Celular"
                  outlined dense
                  v-model="prestamo.celular"
                  class="q-mt-sm"
                />
              </q-card-section>
            </q-card>
          </div>

          <!-- Datos del préstamo -->
          <div class="col-12 col-md-8">
            <q-card flat bordered>
              <q-card-section class="q-pa-sm text-bold">
                Datos del préstamo
                <span class="text-grey text-caption">
                  (Precio oro compra: {{ precioOro.value }})
                </span>
              </q-card-section>
              <q-separator />
              <q-card-section class="q-pa-sm">
                <q-form @submit.prevent="actualizarPrestamo">
                  <div class="row q-col-gutter-sm">
                    <div class="col-6 col-md-4">
                      <q-input
                        label="Fecha Límite"
                        type="date"
                        v-model="prestamo.fecha_limite"
                        outlined dense
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input
                        label="Peso (kg)"
                        type="number"
                        v-model.number="prestamo.peso"
                        min="0" step="0.001"
                        outlined dense
                        :readonly="!isAdmin"
                        @update:model-value="recalcLocal"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input
                        label="Prestado (Bs)"
                        type="number"
                        v-model.number="prestamo.valor_prestado"
                        min="0.01" step="0.01"
                        outlined dense
                        :readonly="!isAdmin"
                        @update:model-value="recalcLocal"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input
                        label="Interés (%)"
                        type="number"
                        v-model.number="prestamo.interes"
                        min="1" max="3" step="0.1"
                        outlined dense
                        :readonly="!isAdmin"
                        @update:model-value="recalcLocal"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input
                        label="Almacén (%)"
                        type="number"
                        v-model.number="prestamo.almacen"
                        min="1" max="3" step="0.1"
                        outlined dense
                        :readonly="!isAdmin"
                        @update:model-value="recalcLocal"
                      />
                    </div>

                    <div class="col-12">
                      <q-input
                        label="Detalle"
                        v-model="prestamo.detalle"
                        type="textarea"
                        outlined dense clearable
                      />
                    </div>

                    <!-- Resumen compacto -->
                    <div class="col-12 q-mt-sm">
                      <div class="row q-col-gutter-sm">
                        <div class="col-6 col-md-3">
                          <q-card bordered class="bg-blue-1">
                            <q-card-section class="q-py-xs">
                              <div class="text-caption text-blue text-weight-bold">Principal</div>
                              <div class="text-subtitle1">{{ money(principalBs) }}</div>
                            </q-card-section>
                          </q-card>
                        </div>
                        <div class="col-6 col-md-3">
                          <q-card bordered class="bg-orange-1">
                            <q-card-section class="q-py-xs">
                              <div class="text-caption text-orange text-weight-bold">Cargos (Int+Alm)</div>
                              <div class="text-subtitle1">{{ money(cargosBs) }}</div>
                              <div class="text-caption text-grey">{{ prestamo.interes }}% + {{ prestamo.almacen }}%</div>
                            </q-card-section>
                          </q-card>
                        </div>
                        <div class="col-6 col-md-3">
                          <q-card bordered class="bg-green-1">
                            <q-card-section class="q-py-xs">
                              <div class="text-caption text-green text-weight-bold">Pagado Interés</div>
                              <div class="text-subtitle1">{{ money(pagadoInteres) }}</div>
                            </q-card-section>
                          </q-card>
                        </div>
                        <div class="col-6 col-md-3">
                          <q-card bordered class="bg-teal-1">
                            <q-card-section class="q-py-xs">
                              <div class="text-caption text-teal text-weight-bold">Pagado Saldo</div>
                              <div class="text-subtitle1">{{ money(pagadoSaldo) }}</div>
                            </q-card-section>
                          </q-card>
                        </div>
                        <div class="col-6 col-md-3">
                          <q-card bordered>
                            <q-card-section class="q-py-xs">
                              <div class="text-caption text-weight-bold">Pend. Interés</div>
                              <div class="text-subtitle1">{{ money(pendienteInteres) }}</div>
                            </q-card-section>
                          </q-card>
                        </div>
                        <div class="col-6 col-md-3">
                          <q-card bordered>
                            <q-card-section class="q-py-xs">
                              <div class="text-caption text-weight-bold">Pend. Principal</div>
                              <div class="text-subtitle1">{{ money(pendientePrincipal) }}</div>
                            </q-card-section>
                          </q-card>
                        </div>
                        <div class="col-12 col-md-6">
                          <q-card bordered class="bg-red-1">
                            <q-card-section class="q-py-xs">
                              <div class="text-caption text-red text-weight-bold">Saldo Total</div>
                              <div class="text-h6">{{ money(saldoTotal) }}</div>
                            </q-card-section>
                          </q-card>
                        </div>
                      </div>
                    </div>

                    <div class="col-12 text-right q-mt-sm">
                      <q-btn
                        label="Actualizar"
                        color="orange"
                        :loading="loading"
                        @click="actualizarPrestamo"
                      />
                    </div>
                  </div>
                </q-form>
              </q-card-section>
            </q-card>
          </div>

          <!-- PAGOS -->
          <div class="col-12">
            <q-card flat bordered class="q-mt-sm">
              <q-card-section class="q-pa-sm text-bold">Registrar Pago</q-card-section>
              <q-separator />
              <q-card-section class="q-pa-sm">
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-sm-3">
                    <q-input
                      dense outlined
                      v-model.number="nuevoPago.monto"
                      type="number" min="0.01" step="0.01"
                      label="Monto"
                    />
                  </div>
                  <div class="col-12 col-sm-3">
                    <q-select
                      dense outlined
                      v-model="nuevoPago.metodo"
                      :options="metodosPago"
                      label="Método"
                    />
                  </div>
                  <div class="col-12 col-sm-3">
                    <q-select
                      dense outlined
                      v-model="nuevoPago.tipo_pago"
                      :options="tiposPago"
                      label="Tipo de pago"
                    />
                  </div>
                  <div class="col-12 col-sm-3 flex items-end">
                    <q-btn
                      color="primary"
                      label="Agregar Pago"
                      icon="add"
                      @click="agregarPago"
                      :loading="loading"
                      no-caps
                      class="full-width"
                    />
                  </div>
                </div>

                <q-markup-table flat bordered dense class="q-mt-sm">
                  <thead>
                  <tr class="bg-primary text-white">
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Tipo</th>
                    <th>Método</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Acción</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="p in pagos" :key="p.id">
                    <td>{{ p.fecha }}</td>
                    <td>{{ money(p.monto) }}</td>
                    <td>
                      <q-chip dense square :color="p.tipo_pago === 'INTERES' ? 'orange' : 'teal'" text-color="white">
                        {{ p.tipo_pago || '—' }}
                      </q-chip>
                    </td>
                    <td>{{ p.metodo || '—' }}</td>
                    <td>{{ p.user?.name || 'N/A' }}</td>
                    <td>
                      <q-chip dense square :color="p.estado==='Activo'?'green':'grey'" text-color="white">
                        {{ p.estado }}
                      </q-chip>
                    </td>
                    <td>
                      <q-btn
                        v-if="p.estado==='Activo'"
                        class="q-mr-xs"
                        icon="delete" color="negative" dense
                        @click="anularPago(p.id)"
                        size="xs" label="Anular" no-caps
                      />
                    </td>
                  </tr>
                  <tr v-if="!pagos.length"><td colspan="7" class="text-center text-grey">Sin pagos registrados</td></tr>
                  </tbody>
                </q-markup-table>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'PrestamoEditarPage',
  data () {
    return {
      loading: false,
      precioOro: { value: 0 },
      prestamo: {
        id: null, numero: '', fecha_limite: null,
        peso: 0, valor_total: 0, valor_prestado: 0,
        interes: 1, almacen: 1,
        saldo: 0, celular: '', detalle: '',
        estado: 'Pendiente', cliente: null, cliente_id: null
      },
      pagos: [],
      metodosPago: ['EFECTIVO','QR'],
      tiposPago: ['INTERES','SALDO'],
      nuevoPago: { monto: null, metodo: 'EFECTIVO', tipo_pago: 'INTERES' }
    }
  },
  computed: {
    isAdmin () {
      const r = this.$store?.user?.role || this.$store?.state?.user?.role
      return ['Admin','Administrador','ADMIN','administrator'].includes(String(r || '').trim())
    },
    principalBs () {
      return Number(this.prestamo.valor_prestado || 0)
    },
    cargosBs () {
      const vp = this.principalBs
      return vp * Number(this.prestamo.interes || 0) / 100
        + vp * Number(this.prestamo.almacen || 0) / 100
    },
    pagadoInteres () {
      return this.pagos
        .filter(p => p.estado === 'Activo' && p.tipo_pago === 'INTERES')
        .reduce((s, x) => s + Number(x.monto || 0), 0)
    },
    pagadoSaldo () {
      return this.pagos
        .filter(p => p.estado === 'Activo' && p.tipo_pago === 'SALDO')
        .reduce((s, x) => s + Number(x.monto || 0), 0)
    },
    pendienteInteres () {
      return Math.max(this.cargosBs - this.pagadoInteres, 0)
    },
    pendientePrincipal () {
      return Math.max(this.principalBs - this.pagadoSaldo, 0)
    },
    saldoTotal () {
      return Number((this.pendienteInteres + this.pendientePrincipal).toFixed(2))
    }
  },
  async mounted () {
    const resOro = await this.$axios.get('cogs/1')
    this.precioOro = resOro.data
    await this.getPrestamo()
    await this.cargarPagos()
  },
  methods: {
    async getPrestamo () {
      this.loading = true
      try {
        const { data } = await this.$axios.get(`prestamos/${this.$route.params.id}`)
        this.prestamo = data
      } finally { this.loading = false }
    },
    recalcLocal () {
      const total = (Number(this.prestamo.peso || 0)) * (Number(this.precioOro.value || 0))
      this.prestamo.valor_total = total
      // saldo real lo recalcula el backend, pero mostramos el estimado:
      // (cargos + principal) - pagos por tipo
    },
    async actualizarPrestamo () {
      this.loading = true
      try {
        const payload = {
          fecha_limite: this.prestamo.fecha_limite,
          celular: this.prestamo.celular,
          detalle: this.prestamo.detalle,
          // solo admin puede tocar números base:
          ...(this.isAdmin ? {
            peso: this.prestamo.peso,
            valor_prestado: this.prestamo.valor_prestado,
            interes: this.prestamo.interes,
            almacen: this.prestamo.almacen
          } : {
            // para no sobreescribir en back, enviar los actuales:
            peso: this.prestamo.peso,
            valor_prestado: this.prestamo.valor_prestado,
            interes: this.prestamo.interes,
            almacen: this.prestamo.almacen
          })
        }
        const { data } = await this.$axios.put(`prestamos/${this.prestamo.id}`, payload)
        this.prestamo = data
        await this.cargarPagos()
        this.$alert.success('Préstamo actualizado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al actualizar')
      } finally { this.loading = false }
    },
    async cargarPagos () {
      const { data } = await this.$axios.get(`prestamos/${this.$route.params.id}/pagos`)
      this.pagos = data
    },
    async agregarPago () {
      if (!this.nuevoPago.monto || this.nuevoPago.monto <= 0) {
        this.$alert.error('Monto requerido'); return
      }
      this.loading = true
      try {
        await this.$axios.post('prestamos/pagos', {
          prestamo_id: this.$route.params.id,
          monto: this.nuevoPago.monto,
          metodo: this.nuevoPago.metodo,
          tipo_pago: this.nuevoPago.tipo_pago,
          user_id: this.$store.user.id
        })
        this.nuevoPago.monto = null
        await this.cargarPagos()
        await this.getPrestamo()
        this.$alert.success('Pago registrado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al registrar pago')
      } finally { this.loading = false }
    },
    async anularPago (id) {
      this.loading = true
      try {
        await this.$axios.put(`prestamos/pagos/${id}/anular`)
        await this.cargarPagos()
        await this.getPrestamo()
        this.$alert.success('Pago anulado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al anular pago')
      } finally { this.loading = false }
    },
    money (v) { return Number(v || 0).toFixed(2) }
  }
}
</script>
