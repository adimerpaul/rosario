<template>
  <q-page class="q-pa-sm">
    <q-card flat bordered>
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-auto">
          <q-btn icon="arrow_back" @click="$router.push('/prestamos')" no-caps color="primary" label="Atrás" dense />
        </div>
        <div class="col">
          <div class="text-h6">Editar préstamo #{{ prestamo.numero }}</div>
          <div class="text-caption text-grey-7">Vista compacta del préstamo, deuda y pagos.</div>
        </div>
        <div class="col-auto">
          <q-chip :color="estadoColor" text-color="white" dense>
            {{ prestamo.estado }}
          </q-chip>
        </div>
        <div class="col-12 col-md-auto">
          <div class="row q-gutter-sm justify-end">
            <q-btn-dropdown color="secondary" icon="print" label="Imprimir" no-caps dense>
              <q-list dense style="min-width: 220px">
                <q-item clickable v-close-popup @click="imprimirPrestamo">
                  <q-item-section avatar><q-icon name="picture_as_pdf" /></q-item-section>
                  <q-item-section>Contrato PDF</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="imprimirPrestamoDirecto">
                  <q-item-section avatar><q-icon name="print" /></q-item-section>
                  <q-item-section>Contrato directo</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="imprimirCambioMonedaPdf">
                  <q-item-section avatar><q-icon name="currency_exchange" /></q-item-section>
                  <q-item-section>Cambio de moneda PDF</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="imprimirCambioMonedaDirecto">
                  <q-item-section avatar><q-icon name="local_printshop" /></q-item-section>
                  <q-item-section>Cambio de moneda directo</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
            <q-btn v-if="isAdmin" color="primary" icon="save" label="Actualizar" no-caps dense :loading="loading" @click="actualizarPrestamo" />
            <q-btn color="positive" icon="payments" label="Registrar pago" no-caps dense :loading="loading" @click="openPagoDialog" />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <div class="row q-col-gutter-sm">
          <div class="col-6 col-md-3">
            <q-card flat bordered class="bg-grey-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-grey-7">Deuda capital</div>
                <div class="text-subtitle1 text-weight-bold">{{ money(prestamo.total_deuda || prestamo.valor_prestado) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="bg-orange-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-grey-8">Deuda interés</div>
                <div class="text-subtitle1 text-weight-bold text-orange-9">{{ money(prestamo.deuda_interes) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="bg-red-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-grey-8">Saldo hoy</div>
                <div class="text-subtitle1 text-weight-bold text-red-9">{{ money(prestamo.saldo) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="bg-green-1">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-grey-8">Pagado</div>
                <div class="text-subtitle1 text-weight-bold text-green-9">{{ money(totalPagado) }}</div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-card-section class="q-pt-none q-pa-sm">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-lg-4">
            <q-card flat bordered>
              <q-card-section class="q-pa-sm text-weight-bold">Cliente</q-card-section>
              <q-separator />
              <q-card-section class="q-pa-sm">
                <div class="row q-col-gutter-sm">
                  <div class="col-12">
                    <q-input dense outlined label="Nombre" :model-value="prestamo.cliente?.name || 'N/A'" readonly />
                  </div>
                  <div class="col-12 col-sm-6 col-lg-12">
                    <q-input dense outlined label="CI" :model-value="prestamo.cliente?.ci || '—'" readonly />
                  </div>
                  <div class="col-12 col-sm-6 col-lg-12">
                    <q-input dense outlined label="Celular" v-model="prestamo.celular" />
                  </div>
                  <div class="col-12">
                    <q-input dense outlined label="Precio oro compra" :model-value="money(precioOro.value)" readonly />
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-lg-8">
            <q-card flat bordered>
              <q-card-section class="q-pa-sm text-weight-bold">Datos del préstamo</q-card-section>
              <q-separator />
              <q-card-section class="q-pa-sm">
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Fecha interes" type="date" v-model="prestamo.fecha_creacion" :readonly="!isAdmin" />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Fecha pago" type="date" v-model="prestamo.fecha_limite" :readonly="!isAdmin" />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Prestado (Bs)" type="number" v-model.number="prestamo.valor_prestado" min="0.01" step="0.01" readonly />
                  </div>

                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Peso bruto (kg)" type="number" v-model.number="prestamo.peso" readonly @update:model-value="recalcular" />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Merma/Piedra (kg)" type="number" v-model.number="prestamo.merma" readonly @update:model-value="recalcular" />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Peso en oro (kg)" :model-value="pesoNetoStr" readonly />
                  </div>

                  <div class="col-12 col-sm-6 col-md-4">
                    <q-select dense outlined label="Interes (%)" v-model.number="prestamo.interes" :options="[1,2,3]" :readonly="!isAdmin" @update:model-value="recalcular" />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-select dense outlined label="Almacén (%)" v-model.number="prestamo.almacen" :options="[2,3]" readonly @update:model-value="recalcular" />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Valor total ref." :model-value="money(prestamo.valor_total)" readonly />
                  </div>

                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Interés (Bs)" :model-value="money(interesMonto)" readonly />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Almacén (Bs)" :model-value="money(almacenMonto)" readonly />
                  </div>
                  <div class="col-12 col-sm-6 col-md-4">
                    <q-input dense outlined label="Total a pagar" :model-value="money(totalPagar)" readonly />
                  </div>

                  <div class="col-12">
                    <q-input dense outlined label="Detalle" v-model="prestamo.detalle" type="textarea" autogrow readonly />
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <div class="row items-center q-col-gutter-sm q-mb-sm">
          <div class="col">
            <div class="text-subtitle2 text-weight-bold">Pagos registrados</div>
            <div class="text-caption text-grey-7">Detalle compacto de movimientos activos y anulados.</div>
          </div>
          <div class="col-12 col-md-auto">
            <div class="row q-gutter-xs justify-end">
              <q-chip dense square color="orange" text-color="white">Interés: {{ money(totalInteresPagado) }}</q-chip>
              <q-chip dense square color="teal" text-color="white">Saldo: {{ money(totalSaldoPagado) }}</q-chip>
              <q-chip dense square color="primary" text-color="white">Total: {{ money(totalPagado) }}</q-chip>
            </div>
          </div>
        </div>

        <q-markup-table flat bordered dense>
          <thead>
          <tr class="bg-grey-2">
            <th>Fecha</th>
            <th>Monto</th>
            <th>Tipo</th>
            <th>Método</th>
            <th>Usuario</th>
            <th>Estado</th>
            <th class="text-right">Acción</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="p in pagos" :key="p.id">
            <td>{{ p.fecha }}</td>
            <td>{{ money(p.monto) }}</td>
            <td>
              <q-chip dense square :color="colorTipoPago(p.tipo_pago)" text-color="white">
                {{ p.tipo_pago || '—' }}
              </q-chip>
            </td>
            <td>{{ p.metodo || '—' }}</td>
            <td>{{ p.user?.name || 'N/A' }}</td>
            <td>
              <q-chip dense square :color="p.estado === 'Activo' ? 'green' : 'grey'" text-color="white">
                {{ p.estado }}
              </q-chip>
            </td>
            <td class="text-right">
              <div class="row q-gutter-xs justify-end no-wrap">
                <q-btn
                  dense
                  flat
                  color="secondary"
                  icon="print"
                  label="Imprimir"
                  no-caps
                  @click="imprimirPago(p.id)"
                />
                <q-btn
                  v-if="p.estado === 'Activo' && isAdmin"
                  flat
                  dense
                  color="negative"
                  icon="delete"
                  label="Anular"
                  no-caps
                  @click="confirmAnularPago(p.id)"
                />
              </div>
            </td>
          </tr>
          <tr v-if="!pagos.length">
            <td colspan="7" class="text-center text-grey">Sin pagos registrados</td>
          </tr>
          </tbody>
        </q-markup-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="pagoDialog" persistent>
      <q-card style="width: 460px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Registrar pago</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="pagoDialog = false" />
        </q-card-section>

        <q-card-section class="q-pt-sm">
          <q-banner class="bg-grey-2 q-mb-md">
            Saldo actual: <b>{{ money(prestamo.saldo) }}</b>
          </q-banner>
          <div class="row q-col-gutter-sm">
            <div class="col-12">
              <q-input dense outlined v-model.number="nuevoPago.monto" type="number" min="0.01" step="0.01" label="Monto" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select dense outlined v-model="nuevoPago.metodo" :options="metodosPago" label="Método" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select dense outlined v-model="nuevoPago.tipo_pago" :options="tiposPago" label="Tipo de pago" />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat color="negative" label="Cancelar" no-caps @click="pagoDialog = false" :loading="loading" />
          <q-btn color="primary" label="Guardar pago" no-caps @click="agregarPago" :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'
import { printCambioMonedaDirecto, printPrestamoDirecto } from 'src/utils/loanPrint'

export default {
  name: 'PrestamoEditarPage',
  data () {
    return {
      loading: false,
      pagoDialog: false,
      precioOro: { value: 0 },
      prestamo: {
        id: null,
        numero: '',
        fecha_creacion: moment().format('YYYY-MM-DD'),
        fecha_limite: null,
        peso: 0,
        merma: 0,
        valor_total: 0,
        valor_prestado: 0,
        interes: 3,
        almacen: 3,
        saldo: 0,
        celular: '',
        detalle: '',
        estado: 'Pendiente',
        cliente: null,
        cliente_id: null,
        deuda_interes: 0,
        total_deuda: 0
      },
      pagos: [],
      metodosPago: ['EFECTIVO', 'QR'],
      tiposPago: ['INTERES', 'SALDO', 'ADICIONAR CAPITAL'],
      nuevoPago: { monto: null, metodo: 'EFECTIVO', tipo_pago: 'INTERES' },
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
    pesoNetoStr () {
      return this.pesoNeto.toFixed(3)
    },
    totalInteresPagado () {
      return this.pagos.filter(p => p.estado === 'Activo' && p.tipo_pago === 'INTERES')
        .reduce((s, x) => s + Number(x.monto || 0), 0)
    },
    totalSaldoPagado () {
      return this.pagos.filter(p => p.estado === 'Activo' && ['SALDO', 'TOTAL'].includes(p.tipo_pago))
        .reduce((s, x) => s + Number(x.monto || 0), 0)
    },
    totalPagado () {
      return +(this.totalInteresPagado + this.totalSaldoPagado).toFixed(2)
    },
    estadoColor () {
      if (['Pagado', 'Entregado'].includes(this.prestamo.estado)) return 'green'
      if (['Cancelado', 'Fundido'].includes(this.prestamo.estado)) return 'red'
      return 'orange'
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
        if (data.fecha_creacion) data.fecha_creacion = String(data.fecha_creacion).substring(0, 10)
        if (data.fecha_limite) data.fecha_limite = String(data.fecha_limite).substring(0, 10)
        this.prestamo = data
      } finally {
        this.loading = false
      }
    },
    async cargarPagos () {
      const { data } = await this.$axios.get(`prestamos/${this.$route.params.id}/pagos`)
      this.pagos = data
    },
    recalcular () {
      if (!this.isAdmin) {
        const vp = Number(this.prestamo.valor_prestado || 0)
        this.prestamo.almacen = vp >= 1000 ? 2 : 3
      }

      const bruto = Number(this.prestamo.peso || 0)
      let merma = Number(this.prestamo.merma || 0)
      if (merma > bruto) {
        merma = bruto
        this.prestamo.merma = bruto
        this.$alert?.warning?.('La merma no puede ser mayor que el peso bruto.')
      }

      const neto = +(bruto - merma).toFixed(3)
      this.pesoNeto = neto

      const precio = Number(this.precioOro?.value || 0)
      this.prestamo.valor_total = +(neto * precio).toFixed(2)

      const vp = Number(this.prestamo.valor_prestado || 0)
      const i = Number(this.prestamo.interes || 0)
      const a = Number(this.prestamo.almacen || 0)
      this.interesMonto = +(vp * i / 100).toFixed(2)
      this.almacenMonto = +(vp * a / 100).toFixed(2)
      this.totalPagar = +(vp + this.interesMonto + this.almacenMonto).toFixed(2)
    },
    async actualizarPrestamo () {
      if (!this.isAdmin) {
        this.$alert.error('Solo el administrador puede modificar este prestamo')
        return
      }
      this.loading = true
      try {
        const payload = {
          fecha_creacion: this.prestamo.fecha_creacion,
          fecha_limite: this.prestamo.fecha_limite,
          interes: this.prestamo.interes,
        }
        const { data } = await this.$axios.put(`prestamos/${this.prestamo.id}`, payload)
        if (data.fecha_creacion) data.fecha_creacion = String(data.fecha_creacion).substring(0, 10)
        if (data.fecha_limite) data.fecha_limite = String(data.fecha_limite).substring(0, 10)
        this.prestamo = data
        await this.cargarPagos()
        this.recalcular()
        this.$alert.success('Préstamo actualizado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al actualizar')
      } finally {
        this.loading = false
      }
    },
    openPagoDialog () {
      this.nuevoPago = { monto: null, metodo: 'EFECTIVO', tipo_pago: 'INTERES' }
      this.pagoDialog = true
    },
    async agregarPago () {
      if (!this.nuevoPago.monto || this.nuevoPago.monto <= 0) {
        this.$alert.error('Monto requerido')
        return
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
        this.pagoDialog = false
        await this.cargarPagos()
        await this.getPrestamo()
        this.recalcular()
        this.$alert.success('Pago registrado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al registrar pago')
      } finally {
        this.loading = false
      }
    },
    async anularPago (id) {
      this.loading = true
      try {
        await this.$axios.put(`prestamos/pagos/${id}/anular`)
        await this.cargarPagos()
        await this.getPrestamo()
        this.recalcular()
        this.$alert.success('Pago anulado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al anular pago')
      } finally {
        this.loading = false
      }
    },
    confirmAnularPago (id) {
      this.$q.dialog({
        title: 'Anular pago',
        message: '¿Desea anular este pago?',
        cancel: true,
        persistent: true
      }).onOk(() => this.anularPago(id))
    },
    imprimirPago (id) {
      const url = this.$axios.defaults.baseURL + `/prestamos/pagos/${id}/pdf`
      window.open(url, '_blank')
    },
    colorTipoPago (tipo) {
      if (['INTERES', 'CARGOS', 'MENSUALIDAD'].includes(tipo)) return 'orange'
      if (tipo === 'ADICIONAR CAPITAL') return 'indigo'
      return 'teal'
    },
    imprimirPrestamo () {
      const url = this.$axios.defaults.baseURL + `/prestamos/${this.prestamo.id}/pdf`
      window.open(url, '_blank')
    },
    imprimirCambioMonedaPdf () {
      const url = this.$axios.defaults.baseURL + `/prestamos/${this.prestamo.id}/cambio/pdf`
      window.open(url, '_blank')
    },
    async imprimirPrestamoDirecto () {
      try {
        await printPrestamoDirecto(this.$axios, this.prestamo.id, this.prestamo)
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al imprimir el contrato directo')
      }
    },
    async imprimirCambioMonedaDirecto () {
      try {
        await printCambioMonedaDirecto(this.$axios, this.prestamo.id, this.prestamo)
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al imprimir el cambio de moneda directo')
      }
    },
    money (v) {
      return Number(v || 0).toFixed(2)
    }
  }
}
</script>



