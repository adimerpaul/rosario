<template>
  <q-page class="q-pa-md">
    <q-card flat bordered>
      <q-card-section class="row items-center q-gutter-sm">
        <div class="text-h6">Editar Orden #{{ form.numero }}</div>
        <q-space/>
        <q-btn flat icon="arrow_back" label="Volver" no-caps @click="$router.back()" />
        <q-btn flat icon="print" label="Imprimir Orden" no-caps @click="imprimirOrden" />
        <q-btn flat icon="assignment" label="Imprimir Garantía" no-caps @click="imprimirGarantia" />
        <q-btn color="primary" icon="save" label="Guardar" no-caps :loading="saving" @click="save"
               v-if="$store.user.role=='Administrador'" />
      </q-card-section>

      <q-separator/>

      <q-card-section>
        <div class="row q-col-gutter-md">
          <!-- CLIENTE (solo lectura) -->
          <div class="col-12 col-md-6">
            <q-field label="Cliente" stack-label outlined dense>
              <template #control>
                <div class="q-pl-sm q-pt-xs q-pb-xs">
                  <div class="text-body1">{{ form.cliente?.name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7" v-if="form.cliente?.ci">CI: {{ form.cliente.ci }}</div>
                </div>
              </template>
            </q-field>
          </div>

          <!-- USUARIO (solo lectura) -->
          <div class="col-12 col-md-6">
            <q-input label="Usuario" outlined dense :model-value="form.user?.name || '—'" readonly />
          </div>

          <!-- DETALLE -->
          <div class="col-12">
            <q-input
              type="textarea"
              label="Detalle"
              outlined dense
              v-model="form.detalle"
              autogrow
            />
          </div>

          <!-- COSTO / PESO / ESTADO -->
          <div class="col-12 col-sm-4">
            <q-input
              label="Costo total"
              outlined dense
              v-model.number="form.costo_total"
              type="number"
              :readonly="!isAdmin"
              :hint="!isAdmin ? 'Solo editable por administrador' : ''"
            />
          </div>
          <div class="col-12 col-sm-4">
            <q-input
              label="Peso (gr)"
              outlined dense
              v-model.number="form.peso"
              type="number"
              :readonly="!isAdmin"
            />
          </div>
          <div class="col-12 col-sm-4">
            <q-select
              label="Estado"
              outlined dense
              v-model="form.estado"
              :options="estados"
              :readonly="!isAdmin"
              :option-disable="opt => !isAdmin && opt !== form.estado"
              v-if="$store.user.role=='Administrador'"
            />
          </div>

          <!-- FECHAS -->
          <div class="col-12 col-sm-6">
            <q-input label="Fecha creación" outlined dense :model-value="formatDateTime(form.fecha_creacion)" readonly />
          </div>
          <div class="col-12 col-sm-6">
            <q-input label="Fecha entrega" outlined dense v-model="form.fecha_entrega" type="date" :readonly="!isAdmin"/>
          </div>

          <!-- MONTOS (solo lectura; salen de pagos) -->
          <div class="col-12 col-sm-4">
            <q-input label="Adelanto" outlined dense :model-value="money(form.adelanto)" readonly />
          </div>
          <div class="col-12 col-sm-4">
            <q-input label="Saldo" outlined dense :model-value="money(form.saldo)" readonly />
          </div>
          <div class="col-12 col-sm-4">
            <q-chip :style="{ backgroundColor: getEstadoColor(form.estado) }" text-color="white" class="q-mt-sm">
              {{ form.estado }}
            </q-chip>
<!--            btn cambiar estado a entregadp-->
            <q-btn
              v-if="isAdmin && form.estado !== 'Entregado'"
              flat dense icon="check_circle" color="green" label="Marcar como Entregado"
              class="q-ml-sm"
              no-caps
              @click="form.estado='Entregado'; save()"
            />
          </div>
        </div>
      </q-card-section>

      <q-separator/>

      <!-- PAGOS -->
      <q-card-section>
        <div class="row items-center q-gutter-sm">
          <div class="text-subtitle1">Pagos</div>
          <q-space/>
          <q-btn color="primary" icon="add" label="Agregar pago" no-caps dense @click="abrirDialogPago"/>
          <q-btn
            color="green"
            icon="payments"
            label="Pagar todo"
            no-caps dense
            @click="abrirDialogPagarTodo"
            :disable="Number(form.saldo) <= 0"
          />
        </div>

        <q-markup-table dense bordered flat class="q-mt-sm">
          <thead>
          <tr class="bg-grey-2">
            <th>#</th>
            <th>Fecha</th>
            <th>Método</th>
            <th>Monto</th>
            <th>Estado</th>
            <th class="text-right">Opciones</th>
          </tr>
          </thead>
          <tbody>
          <tr v-if="!pagos.length">
            <td colspan="6" class="text-center text-grey">Sin pagos registrados</td>
          </tr>
          <tr v-for="(p, i) in pagos" :key="p.id">
            <td>{{ i + 1 }}</td>
            <td>{{ formatDateTime(p.fecha) }}</td>
            <td>{{ p.metodo || '—' }}</td>
            <td>{{ money(p.monto) }}</td>
            <td>
              <q-chip :color="p.estado === 'Activo' ? 'green' : 'grey'" text-color="white" dense>
                {{ p.estado }}
              </q-chip>
            </td>
            <td class="text-right">
              <!-- ANULAR pago -->
              <q-btn
                v-if="p.estado === 'Activo'"
                flat dense icon="block" color="negative" label="Anular"
                @click="confirmAnularPago(p)"
              />
              <q-btn v-else flat dense icon="restore" disable label="Anulado" />
            </td>
          </tr>
          </tbody>
        </q-markup-table>
      </q-card-section>
    </q-card>

    <!-- DIALOG AGREGAR PAGO -->
    <q-dialog v-model="dlgPago">
      <q-card style="min-width: 360px">
        <q-card-section class="text-h6">Agregar pago</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input label="Monto" type="number" outlined dense v-model.number="pagoForm.monto" />
          <q-select label="Método" outlined dense v-model="pagoForm.metodo" :options="metodosPago" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup/>
          <q-btn color="primary" label="Guardar" :loading="savingPago" @click="guardarPago"/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG PAGAR TODO (elige método) -->
    <q-dialog v-model="dlgPagarTodo">
      <q-card style="min-width: 380px">
        <q-card-section class="text-h6">Pagar saldo total</q-card-section>
        <q-card-section class="q-gutter-sm">
          <div class="text-body2">
            Se registrará un pago por <b>{{ money(form.saldo) }}</b>.
          </div>
          <q-select
            label="Método de pago"
            outlined dense
            v-model="pagoTodoForm.metodo"
            :options="metodosPago"
          />
          <q-banner dense class="bg-grey-2 text-grey-9">
            Esta acción salda por completo la orden.
          </q-banner>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup/>
          <q-btn color="green" label="Confirmar pago" :loading="savingPagoTodo" @click="pagarTodo"/>
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'EditarOrden',
  data () {
    return {
      id: Number(this.$route.params.id),
      form: {
        id: null,
        numero: '',
        cliente: null,
        user: null,
        detalle: '',
        costo_total: 0,
        peso: 0,
        estado: 'Pendiente',
        fecha_creacion: null,
        fecha_entrega: null,
        adelanto: 0,
        saldo: 0
      },
      estados: ['Pendiente','Entregado','Cancelada'],
      pagos: [],
      metodosPago: ['EFECTIVO','QR'],
      // agregar pago individual
      dlgPago: false,
      pagoForm: { monto: null, metodo: 'EFECTIVO' },
      // pagar todo
      dlgPagarTodo: false,
      pagoTodoForm: { metodo: 'EFECTIVO' },

      loading: false,
      saving: false,
      savingPago: false,
      savingPagoTodo: false
    }
  },
  computed: {
    isAdmin () {
      const r = this.$store?.user?.role || this.$store?.state?.user?.role
      return ['Admin','Administrador','ADMIN','administrator'].includes(String(r || '').trim())
    }
  },
  mounted () {
    this.fetch()
  },
  methods: {
    imprimirOrden () {
      const url = `${this.$axios.defaults.baseURL}/ordenes/${this.id}/pdf`
      window.open(url, '_blank')
    },
    imprimirGarantia () {
      const url = `${this.$axios.defaults.baseURL}/ordenes/${this.id}/garantia`
      window.open(url, '_blank')
    },

    async fetch () {
      this.loading = true
      try {
        const { data } = await this.$axios.get(`ordenes/${this.id}`)
        Object.assign(this.form, data)
        // pagos
        try {
          const rp = await this.$axios.get(`ordenes/${this.id}/pagos`)
          this.pagos = rp.data || []
        } catch (e) { this.pagos = data.pagos || [] }
      } catch (e) {
        this.$alert?.error?.('No se pudo cargar la orden')
      } finally {
        this.loading = false
      }
    },

    async save () {
      this.saving = true
      try {
        const payload = {
          // cliente_id nunca se envía desde front
          detalle: this.form.detalle,
          estado: this.form.estado,
          fecha_entrega: this.form.fecha_entrega,
          // solo admin puede enviar costo_total y peso
          ...(this.isAdmin ? {
            costo_total: this.form.costo_total,
            peso: this.form.peso
          } : {})
        }
        const { data } = await this.$axios.put(`ordenes/${this.id}`, payload)
        Object.assign(this.form, data)
        this.$q.notify({ type: 'positive', message: 'Orden actualizada' })
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al guardar')
      } finally {
        this.saving = false
      }
    },

    abrirDialogPago () {
      this.pagoForm = { monto: null, metodo: 'EFECTIVO' }
      this.dlgPago = true
    },

    async guardarPago () {
      if (!this.pagoForm.monto || this.pagoForm.monto <= 0) {
        this.$q.notify({ type: 'warning', message: 'Ingresa un monto válido' })
        return
      }
      this.savingPago = true
      try {
        await this.$axios.post(`ordenes/${this.id}/pagos`, this.pagoForm)
        this.dlgPago = false
        await this.fetch()
        this.$q.notify({ type: 'positive', message: 'Pago registrado' })
        // this.$router.push('/ordenes')
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'No se pudo registrar el pago')
      } finally {
        this.savingPago = false
      }
    },

    confirmAnularPago (p) {
      this.$q.dialog({
        title: 'Anular pago',
        message: `¿Anular el pago de <b>${this.money(p.monto)}</b>?`,
        html: true,
        ok: { label: 'Sí, anular', color: 'negative' },
        cancel: { label: 'Cancelar' }
      }).onOk(() => this.anularPago(p))
    },

    async anularPago (p) {
      try {
        await this.$axios.post(`ordenes/pagos/${p.id}/anular`)
        await this.fetch()
        this.$q.notify({ type: 'positive', message: 'Pago anulado' })
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'No se pudo anular el pago')
      }
    },

    // ===== Pagar TODO con método =====
    abrirDialogPagarTodo () {
      if (Number(this.form.saldo) <= 0) {
        this.$q.notify({ type: 'info', message: 'No hay saldo pendiente' })
        return
      }
      this.pagoTodoForm = { metodo: 'EFECTIVO' }
      this.dlgPagarTodo = true
    },

    async pagarTodo () {
      this.savingPagoTodo = true
      try {
        const payload = { metodo: this.pagoTodoForm.metodo } // <-- se envía el método (EFECTIVO/QR)
        const { data } = await this.$axios.post(`ordenes/${this.id}/pagar-todo`, payload)
        Object.assign(this.form, data)
        const rp = await this.$axios.get(`ordenes/${this.id}/pagos`)
        this.pagos = rp.data || []
        this.dlgPagarTodo = false
        this.$q.notify({ type: 'positive', message: 'Pago total registrado' })
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'No se pudo pagar todo')
      } finally {
        this.savingPagoTodo = false
      }
    },

    // helpers
    money (v) { return Number(v || 0).toFixed(2) },
    formatDateTime (v) {
      if (!v) return '—'
      return String(v).length > 10
        ? moment(v).format('YYYY-MM-DD HH:mm')
        : moment(v, 'YYYY-MM-DD').format('YYYY-MM-DD')
    },
    getEstadoColor (estado) {
      switch (estado) {
        case 'Pendiente': return '#fb8c00'
        case 'Entregado': return '#21ba45'
        case 'Cancelada': return '#e53935'
        default: return '#9e9e9e'
      }
    }
  }
}
</script>
