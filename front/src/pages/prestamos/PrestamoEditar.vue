<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section class="q-pa-sm">
        <div class="row items-center">
          <q-btn icon="arrow_back" @click="$router.push('/prestamos')" no-caps size="10px" color="primary" label="Atrás"/>
          <div class="text-h6 q-ml-sm">Editar Préstamo #{{ prestamo.numero }}</div>
          <q-space />
          <q-chip :color="prestamo.estado==='Pendiente' ? 'orange' : prestamo.estado==='Pagado' ? 'green' : 'red'" text-color="white" dense size="10px">
            {{ prestamo.estado }}
          </q-chip>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <div class="row q-col-gutter-sm">

          <!-- Cliente (solo lectura) -->
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
                <q-input label="CI" outlined dense :model-value="prestamo.cliente?.ci || '—'" readonly class="q-mt-sm" />
                <q-input label="Celular" outlined dense v-model="prestamo.celular" class="q-mt-sm" />
              </q-card-section>
            </q-card>
          </div>

          <!-- Formulario -->
          <div class="col-12 col-md-8">
            <q-card flat bordered>
              <q-card-section class="q-pa-sm text-bold">
                Datos del préstamo
                <span class="text-grey text-caption">(Precio oro compra: {{ precioOro.value }})</span>
              </q-card-section>
              <q-separator />
              <q-card-section class="q-pa-sm">
                <q-form @submit.prevent="actualizarPrestamo">
                  <div class="row q-col-gutter-sm">

                    <!-- Fechas -->
                    <div class="col-6 col-md-4">
                      <q-input
                        label="Fecha de Creación"
                        type="date"
                        v-model="prestamo.fecha_creacion"
                        outlined dense
                        :readonly="!isAdmin"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input
                        label="Fecha Límite"
                        type="date"
                        v-model="prestamo.fecha_limite"
                        outlined dense
                      />
                    </div>

                    <!-- Valores base -->
                    <div class="col-6 col-md-4">
                      <q-input label="Prestado (Bs)" type="number"
                               v-model.number="prestamo.valor_prestado"
                               min="0.01" step="0.01" outlined dense
                               :readonly="!isAdmin" @update:model-value="recalcular"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input label="Peso bruto (kg)" type="number"
                               v-model.number="prestamo.peso"
                               min="0" step="0.001" outlined dense
                               :readonly="!isAdmin" @update:model-value="recalcular"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input label="Merma/Piedra (kg)" type="number"
                               v-model.number="prestamo.merma"
                               min="0" step="0.001" outlined dense
                               :readonly="!isAdmin" @update:model-value="recalcular"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input label="Peso en oro (kg)" :model-value="pesoNetoStr" outlined dense readonly />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input label="Valor Total (ref.)" :model-value="money(prestamo.valor_total)" type="text" outlined dense readonly />
                    </div>

                    <!-- % -->
                    <div class="col-6 col-md-4">
                      <q-select label="Interés (%)" outlined dense
                                v-model.number="prestamo.interes" :options="[1,2,3]"
                                @update:model-value="recalcular" :readonly="!isAdmin"
                      />
                    </div>

                    <div class="col-6 col-md-4">
                      <q-select label="Almacén (%)" outlined dense
                                v-model.number="prestamo.almacen" :options="[2,3]"
                                @update:model-value="recalcular" :readonly="!isAdmin"
                      />
                    </div>

                    <!-- Montos calculados -->
                    <div class="col-6 col-md-4">
                      <q-input label="Interés (Bs)" :model-value="money(interesMonto)" outlined dense readonly/>
                    </div>
                    <div class="col-6 col-md-4">
                      <q-input label="Almacén (Bs)" :model-value="money(almacenMonto)" outlined dense readonly/>
                    </div>
                    <div class="col-6 col-md-4">
                      <q-input label="Total a pagar" :model-value="money(totalPagar)" outlined dense readonly/>
                    </div>

                    <div class="col-12 col-md-4">
                      <q-input label="Saldo (hoy)" :model-value="money(prestamo.saldo)" outlined dense readonly/>
                    </div>

                    <div class="col-12">
                      <q-input label="Detalle" v-model="prestamo.detalle" type="textarea" outlined dense />
                    </div>

                  </div>

                  <div class="q-mt-md text-right">
                    <q-btn label="Actualizar" color="orange" :loading="loading" @click="actualizarPrestamo"/>
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
                    <q-input dense outlined v-model.number="nuevoPago.monto" type="number" min="0.01" step="0.01" label="Monto"/>
                  </div>
                  <div class="col-12 col-sm-3">
                    <q-select dense outlined v-model="nuevoPago.metodo" :options="metodosPago" label="Método"/>
                  </div>
                  <div class="col-12 col-sm-3">
                    <q-select dense outlined v-model="nuevoPago.tipo_pago" :options="tiposPago" label="Tipo de pago"/>
                  </div>
                  <div class="col-12 col-sm-3 flex items-end">
                    <q-btn color="primary" label="Agregar Pago" icon="add" @click="agregarPago" :loading="loading" no-caps class="full-width"/>
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
                      <q-btn v-if="p.estado==='Activo'" class="q-mr-xs" icon="delete" color="negative" dense
                             @click="anularPago(p.id)" size="xs" label="Anular" no-caps />
                    </td>
                  </tr>
                  <tr v-if="!pagos.length"><td colspan="7" class="text-center text-grey">Sin pagos registrados</td></tr>
                  </tbody>

                  <!-- Footer de totales -->
                  <tfoot v-if="pagos.length">
                  <tr class="bg-grey-2">
                    <td class="text-right text-weight-bold" colspan="2">Total Pagado:</td>
                    <td colspan="5" class="text-weight-bold">
                      Interés: {{ money(totalInteresPagado) }} &nbsp; | &nbsp;
                      Saldo: {{ money(totalSaldoPagado) }} &nbsp; | &nbsp;
                      <span class="text-primary">Suma: {{ money(totalPagado) }}</span>
                    </td>
                  </tr>
                  </tfoot>
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
import moment from 'moment'

export default {
  name: 'PrestamoEditarPage',
  data () {
    return {
      loading: false,
      precioOro: { value: 0 }, // cogs/3 (precio compra)
      prestamo: {
        id: null, numero: '',
        fecha_creacion: moment().format('YYYY-MM-DD'),
        fecha_limite: null,
        peso: 0, merma: 0, valor_total: 0,
        valor_prestado: 0,
        interes: 3, almacen: 3,
        saldo: 0, celular: '', detalle: '',
        estado: 'Pendiente', cliente: null, cliente_id: null
      },
      pagos: [],
      metodosPago: ['EFECTIVO','QR'],
      tiposPago: ['INTERES','SALDO'],
      nuevoPago: { monto: null, metodo: 'EFECTIVO', tipo_pago: 'INTERES' },

      // auxiliares
      pesoNeto: 0,
      interesMonto: 0,
      almacenMonto: 0,
      totalPagar: 0
    }
  },
  computed: {
    isAdmin () {
      const r = (this.$store?.user?.role || '').toString().toLowerCase()
      return r.includes('admin')
    },
    pesoNetoStr () { return this.pesoNeto.toFixed(3) },

    // Totales de pagos (para el footer)
    totalInteresPagado () {
      return this.pagos.filter(p => p.estado === 'Activo' && p.tipo_pago === 'INTERES')
        .reduce((s, x) => s + Number(x.monto || 0), 0)
    },
    totalSaldoPagado () {
      return this.pagos.filter(p => p.estado === 'Activo' && p.tipo_pago === 'SALDO')
        .reduce((s, x) => s + Number(x.monto || 0), 0)
    },
    totalPagado () {
      return +(this.totalInteresPagado + this.totalSaldoPagado).toFixed(2)
    }
  },
  async mounted () {
    const resOro = await this.$axios.get('cogs/3')
    this.precioOro = resOro.data
    await this.getPrestamo()
    await this.cargarPagos()
    this.recalcular()
  },
  methods: {
    async getPrestamo () {
      this.loading = true
      try {
        const { data } = await this.$axios.get(`prestamos/${this.$route.params.id}`)
        if (data.fecha_creacion) data.fecha_creacion = String(data.fecha_creacion).substring(0,10)
        if (data.fecha_limite)   data.fecha_limite   = String(data.fecha_limite).substring(0,10)
        this.prestamo = data
      } finally { this.loading = false }
    },

    async cargarPagos () {
      const { data } = await this.$axios.get(`prestamos/${this.$route.params.id}/pagos`)
      this.pagos = data
    },

    recalcular () {
      // regla de almacén para no-admin: >=1000 => 2, sino 3
      if (!this.isAdmin) {
        const vp = Number(this.prestamo.valor_prestado || 0)
        this.prestamo.almacen = vp >= 1000 ? 2 : 3
      }

      const bruto = Number(this.prestamo.peso || 0)
      let   merma = Number(this.prestamo.merma || 0)
      if (merma > bruto) {
        merma = bruto
        this.prestamo.merma = bruto
        this.$alert?.warning?.('La merma no puede ser mayor que el peso bruto.')
      }
      const neto = +(bruto - merma).toFixed(3)
      this.pesoNeto = neto

      // valor total referencial
      const precio = Number(this.precioOro?.value || 0)
      this.prestamo.valor_total = +(neto * precio).toFixed(2)

      // montos por % sobre lo prestado
      const vp  = Number(this.prestamo.valor_prestado || 0)
      const i   = Number(this.prestamo.interes || 0)
      const a   = Number(this.prestamo.almacen || 0)
      this.interesMonto = +(vp * i / 100).toFixed(2)
      this.almacenMonto = +(vp * a / 100).toFixed(2)
      this.totalPagar   = +(vp + this.interesMonto + this.almacenMonto).toFixed(2)
    },

    async actualizarPrestamo () {
      this.loading = true
      try {
        const payload = {
          // Campos siempre editables (fecha_creacion solo admin será tomada por el back)
          fecha_creacion: this.prestamo.fecha_creacion,
          fecha_limite:   this.prestamo.fecha_limite,
          celular:        this.prestamo.celular,
          detalle:        this.prestamo.detalle,
          // sensibles (el back ignorará si no es admin)
          peso:            this.prestamo.peso,
          merma:           this.prestamo.merma,
          valor_prestado:  this.prestamo.valor_prestado,
          interes:         this.prestamo.interes,
          almacen:         this.prestamo.almacen,
        }
        const { data } = await this.$axios.put(`prestamos/${this.prestamo.id}`, payload)
        this.$router.push('/prestamos')
        if (data.fecha_creacion) data.fecha_creacion = String(data.fecha_creacion).substring(0,10)
        if (data.fecha_limite)   data.fecha_limite   = String(data.fecha_limite).substring(0,10)
        this.prestamo = data
        await this.cargarPagos()
        this.recalcular()
        this.$alert.success('Préstamo actualizado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al actualizar')
      } finally { this.loading = false }
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
        this.$router.push('/prestamos')
        // this.nuevoPago.monto = null
        // await this.cargarPagos()
        // await this.getPrestamo()
        // this.$alert.success('Pago registrado')
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

<style scoped>
/* estilos sobrios para mantenerlo parecido al crear */
</style>
