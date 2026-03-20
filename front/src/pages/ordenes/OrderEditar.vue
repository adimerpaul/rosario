<template>
  <q-page class="q-pa-sm order-edit-page">
    <q-card flat bordered class="editor-card">
      <q-card-section class="row items-center q-gutter-sm q-pb-sm">
        <div class="text-subtitle1 text-weight-bold">Editar Orden #{{ form.numero }}</div>
        <q-space />
        <q-btn flat dense icon="arrow_back" label="Volver" no-caps style="font-size: 10px;" @click="$router.back()" />
        <q-btn-dropdown flat dense icon="print" label="Imprimir" no-caps style="font-size: 10px;">
          <q-list dense style="min-width: 220px">
            <q-item clickable v-close-popup @click="imprimirOrdenPdf">
              <q-item-section avatar><q-icon name="picture_as_pdf" /></q-item-section>
              <q-item-section>Orden de trabajo PDF</q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="imprimirGarantiaPdf">
              <q-item-section avatar><q-icon name="assignment" /></q-item-section>
              <q-item-section>Garantia PDF</q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="imprimirOrden">
              <q-item-section avatar><q-icon name="print" /></q-item-section>
              <q-item-section>Orden de trabajo directo</q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="imprimirGarantia">
              <q-item-section avatar><q-icon name="local_printshop" /></q-item-section>
              <q-item-section>Garantia directa</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
        <q-btn color="primary" dense icon="save" label="Guardar" no-caps style="font-size: 10px;" :loading="saving" @click="save" />
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pt-sm">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="photo-card">
              <q-card-section class="q-pa-sm">
                <div class="text-caption text-weight-bold q-mb-xs">Foto de referencia</div>
                <q-img :src="photoPreview" :ratio="1" class="order-photo" spinner-color="grey-5" />
                <q-file
                  v-model="fotoModelo"
                  dense
                  outlined
                  accept="image/*"
                  label="Cambiar foto"
                  class="q-mt-sm"
                  @update:model-value="onPhotoSelected"
                />
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-8">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-field label="Cliente" stack-label outlined dense>
                  <template #control>
                    <div class="q-pl-sm q-pt-xs q-pb-xs">
                      <div class="text-body2">{{ form.cliente?.name || 'N/A' }}</div>
                      <div class="text-caption text-grey-7">CI: {{ form.cliente?.ci || '-' }}</div>
                    </div>
                  </template>
                </q-field>
              </div>

              <div class="col-12 col-sm-6">
                <q-input label="Usuario" outlined dense :model-value="form.user?.name || '-'" readonly />
              </div>

              <div class="col-12">
                <q-input type="textarea" label="Detalle" outlined dense v-model="form.detalle" autogrow />
              </div>

              <div class="col-6 col-sm-3">
                <q-input label="Costo" outlined dense v-model.number="form.costo_total" type="number" :readonly="!isAdmin" />
              </div>

              <div class="col-6 col-sm-3">
                <q-input
                  label="Peso"
                  outlined dense
                  v-model.number="form.peso"
                  type="number"
                  :readonly="!isAdmin"
                  @update:model-value="val => syncCostByWeight(val)"
                />
              </div>

              <div class="col-6 col-sm-3">
                <q-select label="Estado" outlined dense v-model="form.estado" :options="estados" :readonly="!isAdmin" />
              </div>

              <div class="col-6 col-sm-3">
                <q-input label="Entrega" outlined dense v-model="form.fecha_entrega" type="date" :readonly="!isAdmin" />
              </div>

              <div class="col-4">
                <q-input label="Adelanto" outlined dense v-model="form.adelanto" readonly />
              </div>
              <div class="col-4">
                <q-input label="Saldo" outlined dense v-model="form.saldo" readonly />
              </div>
              <div class="col-4 flex flex-center">
                <q-chip :style="{ backgroundColor: getEstadoColor(form.estado) }" text-color="white" dense>
                  {{ form.estado }}
                </q-chip>
              </div>

              <div class="col-6">
                <q-input label="Creada" outlined dense :model-value="formatDateTime(form.fecha_creacion)" readonly />
              </div>
              <div class="col-6">
                <q-input label="Numero" outlined dense :model-value="form.numero" readonly />
              </div>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pt-sm">
        <div class="row items-center q-gutter-sm q-mb-sm">
          <div class="text-subtitle2 text-weight-bold">Pagos</div>
          <q-space />
          <q-btn color="primary" dense icon="add" label="Agregar pago" no-caps style="font-size: 10px;" @click="abrirDialogPago" />
          <q-btn color="green" dense icon="payments" label="Pagar todo" no-caps style="font-size: 10px;" @click="abrirDialogPagarTodo" :disable="Number(form.saldo) <= 0" />
        </div>

        <q-markup-table dense flat bordered separator="cell" class="payments-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Fecha</th>
              <th>Metodo</th>
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
              <td>{{ p.metodo || '-' }}</td>
              <td>{{ money(p.monto) }}</td>
              <td>
                <q-chip :color="p.estado === 'Activo' ? 'green' : 'grey'" text-color="white" dense>
                  {{ p.estado }}
                </q-chip>
              </td>
              <td class="text-right">
                <q-btn v-if="p.estado === 'Activo'" flat dense icon="block" color="negative" label="Anular" style="font-size: 10px;" @click="confirmAnularPago(p)" />
              </td>
            </tr>
          </tbody>
        </q-markup-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dlgPago">
      <q-card style="min-width: 320px">
        <q-card-section class="text-subtitle1">Agregar pago</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input label="Monto" type="number" outlined dense v-model.number="pagoForm.monto" />
          <q-select label="Metodo" outlined dense v-model="pagoForm.metodo" :options="metodosPago" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup style="font-size: 10px;" />
          <q-btn color="primary" label="Guardar" :loading="savingPago" style="font-size: 10px;" @click="guardarPago" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dlgPagarTodo">
      <q-card style="min-width: 340px">
        <q-card-section class="text-subtitle1">Pagar saldo total</q-card-section>
        <q-card-section class="q-gutter-sm">
          <div class="text-body2">Se registrara un pago por <b>{{ money(form.saldo) }}</b>.</div>
          <q-select label="Metodo de pago" outlined dense v-model="pagoTodoForm.metodo" :options="metodosPago" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup style="font-size: 10px;" />
          <q-btn color="green" label="Confirmar pago" :loading="savingPagoTodo" style="font-size: 10px;" @click="pagarTodo" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'
import { printGarantiaDirecta, printOrdenTrabajoDirecto } from 'src/utils/orderPrint'

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
        saldo: 0,
        foto_modelo: null
      },
      fotoModelo: null,
      photoPreview: '/images/defaultJoya.png',
      estados: ['Pendiente', 'Entregado', 'Cancelada'],
      pagos: [],
      metodosPago: ['EFECTIVO', 'QR'],
      dlgPago: false,
      pagoForm: { monto: null, metodo: 'EFECTIVO' },
      dlgPagarTodo: false,
      pagoTodoForm: { metodo: 'EFECTIVO' },
      loading: false,
      saving: false,
      savingPago: false,
      savingPagoTodo: false,
      precioOro: 0
    }
  },
  computed: {
    isAdmin () {
      const r = this.$store?.user?.role || this.$store?.state?.user?.role
      return ['Admin', 'Administrador', 'ADMIN', 'administrator'].includes(String(r || '').trim())
    }
  },
  async mounted () {
    await this.fetch()
    const res = await this.$axios.get('cogs/2')
    this.precioOro = res.data
  },
  methods: {
    updatePhotoPreview () {
      this.photoPreview = this.form.foto_modelo
        ? `${this.$url}/../images/${this.form.foto_modelo}`
        : `${this.$url}/../images/defaultJoya.png`
    },
    onPhotoSelected (file) {
      if (!file) {
        this.updatePhotoPreview()
        return
      }
      this.photoPreview = URL.createObjectURL(file)
    },
    syncCostByWeight (val) {
      if (!this.isAdmin) return
      const precio = Number(this.precioOro?.value || 0)
      this.form.costo_total = Number(val || 0) * precio
    },
    async imprimirOrdenPdf () {
      try {
        const response = await this.$axios.get(`ordenes/${this.id}/pdf`, { responseType: 'blob' })
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const fileName = response.headers['content-disposition']?.match(/filename="?([^"]+)"?/)?.[1] || `orden_trabajo_${this.id}.pdf`
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (err) {
        this.$alert?.error?.(err.response?.data?.message || 'Error al descargar la orden')
      }
    },
    imprimirGarantiaPdf () {
      const url = `${this.$axios.defaults.baseURL}/ordenes/${this.id}/garantia`
      window.open(url, '_blank')
    },
    async imprimirOrden () {
      try {
        await printOrdenTrabajoDirecto(this.$axios, this.id, this.form)
      } catch (err) {
        this.$alert?.error?.(err.response?.data?.message || 'Error al imprimir la orden')
      }
    },
    async imprimirGarantia () {
      try {
        await printGarantiaDirecta(this.$axios, this.id, this.form)
      } catch (err) {
        this.$alert?.error?.(err.response?.data?.message || 'Error al imprimir la garantia')
      }
    },
    async fetch () {
      this.loading = true
      try {
        const { data } = await this.$axios.get(`ordenes/${this.id}`)
        Object.assign(this.form, data)
        this.updatePhotoPreview()
        try {
          const rp = await this.$axios.get(`ordenes/${this.id}/pagos`)
          this.pagos = rp.data || []
        } catch (e) {
          this.pagos = data.pagos || []
        }
      } catch (e) {
        this.$alert?.error?.('No se pudo cargar la orden')
      } finally {
        this.loading = false
      }
    },
    async save () {
      this.saving = true
      try {
        const payload = new FormData()
        payload.append('detalle', this.form.detalle || '')
        payload.append('estado', this.form.estado || 'Pendiente')
        payload.append('fecha_entrega', this.form.fecha_entrega || '')
        payload.append('adelanto', this.form.adelanto || 0)
        payload.append('saldo', this.form.saldo || 0)
        payload.append('_method', 'PUT')

        if (this.isAdmin) {
          payload.append('costo_total', this.form.costo_total || 0)
          payload.append('peso', this.form.peso || 0)
        }

        if (this.fotoModelo) {
          payload.append('foto_modelo', this.fotoModelo)
        }

        const { data } = await this.$axios.post(`ordenes/${this.id}`, payload, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        Object.assign(this.form, data)
        this.fotoModelo = null
        this.updatePhotoPreview()
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
        this.$q.notify({ type: 'warning', message: 'Ingresa un monto valido' })
        return
      }
      this.savingPago = true
      try {
        await this.$axios.post(`ordenes/${this.id}/pagos`, this.pagoForm)
        this.dlgPago = false
        await this.fetch()
        this.$q.notify({ type: 'positive', message: 'Pago registrado' })
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'No se pudo registrar el pago')
      } finally {
        this.savingPago = false
      }
    },
    confirmAnularPago (p) {
      this.$q.dialog({
        title: 'Anular pago',
        message: `Anular el pago de <b>${this.money(p.monto)}</b>?`,
        html: true,
        ok: { label: 'Si, anular', color: 'negative' },
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
        const { data } = await this.$axios.post(`ordenes/${this.id}/pagar-todo`, { metodo: this.pagoTodoForm.metodo })
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
    money (v) { return Number(v || 0).toFixed(2) },
    formatDateTime (v) {
      if (!v) return '-'
      return String(v).length > 10 ? moment(v).format('YYYY-MM-DD HH:mm') : moment(v, 'YYYY-MM-DD').format('YYYY-MM-DD')
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

<style scoped>
.order-edit-page {
  background: #f5f6f8;
}

.editor-card,
.photo-card {
  border-radius: 10px;
}

.order-photo {
  border-radius: 8px;
  overflow: hidden;
}

.payments-table th,
.payments-table td {
  padding: 6px 8px;
  font-size: 12px;
}
</style>
