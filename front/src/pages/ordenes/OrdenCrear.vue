<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section class="q-pa-xs">
        <div class="row">
          <div class="col-12 col-md-5">
            <q-card flat bordered>
              <q-card-section class="text-bold q-pa-xs">Seleccionar Cliente</q-card-section>
              <div class="row">
                <div class="col-12 col-md-8">
                  <q-input
                    v-model="clienteFiltro"
                    dense
                    outlined
                    clearable
                    debounce="400"
                    placeholder="Buscar cliente..."
                    class="q-mb-sm"
                    @update:model-value="getClientes"
                  >
                    <template v-slot:append>
                      <q-icon name="search" />
                    </template>
                  </q-input>
                </div>
                <div class="col-6 col-md-2 text-right">
                  <q-btn label="Buscar" color="primary" class="q-mb-sm" @click="getClientes" icon="search" :loading="loading" no-caps dense size="10px" />
                </div>
                <div class="col-6 col-md-2 text-right">
                  <q-btn label="Nuevo" color="positive" class="q-mb-sm" @click="openClientDialog" icon="add" :loading="clientLoading" no-caps dense size="10px" />
                </div>
              </div>
              <div class="col-12 q-mt-sm">
                <q-banner v-if="orden.cliente" class="bg-grey-2">
                  Cliente seleccionado: <b>{{ orden.cliente.name }}</b> (CI: {{ orden.cliente.ci }})
                  <q-chip :color="orden.cliente.status === 'Confiable' ? 'green' : orden.cliente.status === 'No Confiable' ? 'red' : 'orange'" text-color="white" dense size="10px">
                    {{ orden.cliente.status }}
                  </q-chip>
                </q-banner>
              </div>
              <div class="flex flex-center">
                <q-pagination
                  size="10px"
                  v-model="page"
                  :max="totalPages"
                  :max-pages="6"
                  boundary-numbers
                  @update:model-value="getClientes"
                />
              </div>
              <q-markup-table flat bordered dense>
                <thead>
                <tr class="bg-primary text-white">
                  <th>Nombre</th>
                  <th>CI</th>
                  <th>Estado</th>
                  <th>Accion</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="cli in clientes" :key="cli.id" :class="{ 'bg-blue-2': orden.cliente_id === cli.id }">
                  <td style="white-space: normal; max-width: 120px; word-break: break-word; font-size: 12px; line-height: 0.9;">
                    {{ cli.name }}
                  </td>
                  <td>{{ cli.ci }}</td>
                  <td>
                    <q-chip :color="cli.status === 'Confiable' ? 'green' : cli.status === 'No Confiable' ? 'red' : 'orange'" text-color="white" dense size="10px">
                      {{ cli.status }}
                    </q-chip>
                  </td>
                  <td>
                    <q-btn label="Seleccionar" color="primary" dense @click="seleccionarCliente(cli)" icon="check" size="10px" no-caps />
                  </td>
                </tr>
                </tbody>
              </q-markup-table>
            </q-card>
          </div>

          <div class="col-12 col-md-7">
            <q-card flat bordered>
              <q-card-section class="text-bold q-pa-xs">
                Crear Nueva Orden
                <span class="text-grey text-caption">
                  (Precio oro: {{ money(precioOro.value) }})
                </span>
              </q-card-section>

              <q-card-section class="q-pa-xs">
                <q-form @submit.prevent="guardarOrden">
                  <div class="row q-col-gutter-sm">
                    <div class="col-12 col-md-9">
                      <div class="row q-col-gutter-sm">
                        <div class="col-12 col-md-4">
                          <q-input label="Fecha de Entrega" type="date" v-model="orden.fecha_entrega" outlined dense />
                        </div>
                        <div class="col-12 col-md-4">
                          <q-input label="Celular" v-model="orden.celular" outlined dense />
                        </div>
<!--                        <div class="col-12 col-md-4">-->
<!--                          <q-input label="Precio oro" :model-value="money(precioOro.value)" outlined dense readonly />-->
<!--                        </div>-->
                        <div class="col-12 col-md-4">
                          <q-input
                            label="Peso (kg)"
                            v-model.number="orden.peso"
                            type="number"
                            outlined
                            dense
                            min="0.00"
                            step="0.01"
                            @update:model-value="calcularTotal"
                          />
                        </div>
<!--                        <div class="col-12 col-md-4">-->
<!--                          <q-input label="Costo oro" :model-value="money(costoOro)" outlined dense readonly />-->
<!--                        </div>-->
                        <div class="col-12 col-md-4">
                          <q-input label="Costo Total" v-model.number="orden.costo_total" type="number" outlined dense @update:model-value="validarCostoTotal" />
                        </div>
                        <div class="col-12 col-md-4">
                          <q-input label="Adelanto" v-model.number="orden.adelanto" type="number" outlined dense @update:model-value="calcularSaldo" />
                        </div>
                        <div class="col-12 col-md-4">
                          <q-input label="Saldo" v-model.number="orden.saldo" type="number" outlined dense readonly />
                        </div>
                        <div class="col-12 col-md-4 row items-center">
                          <q-checkbox v-model="check18Kilates" label="18 Kilates" dense :true-value="'18 Kilates'" :false-value="''" />
                        </div>
                        <div class="col-12 col-md-4">
                          <q-select v-model="orden.tipo_pago" :options="['Efectivo', 'QR']" label="Tipo de Pago" outlined dense />
                        </div>
                        <div class="col-12 col-md-8">
                          <q-input label="Detalle" v-model="orden.detalle" type="textarea" outlined dense autogrow>
                            <template v-slot:append>
                              <q-btn icon="mic" @click="iniciarReconocimiento('detalle')" flat dense />
                            </template>
                          </q-input>
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-3">
                      <q-card flat bordered class="foto-card">
                        <q-card-section class="q-pa-sm">
                          <div class="row items-start no-wrap">
                            <div class="col">
                              <div class="text-subtitle2 text-weight-bold">Foto de modelo</div>
                              <div class="text-caption text-grey-7">Opcional para la impresión.</div>
                            </div>
                            <div class="foto-actions">
                              <input
                                ref="fotoModeloInput"
                                type="file"
                                accept="image/*"
                                class="hidden-file-input"
                                @change="onFotoModeloPicked"
                              />
                              <q-btn round dense color="primary" icon="edit" @click="$refs.fotoModeloInput.click()" />
                            </div>
                          </div>
                        </q-card-section>
                        <q-card-section class="q-pt-none">
                          <q-img v-if="fotoModeloPreview" :src="fotoModeloPreview" class="foto-preview" fit="cover" />
                          <div v-else class="foto-placeholder">
                            <q-icon name="photo_camera" size="30px" color="grey-5" />
                            <div class="text-caption text-grey-6 q-mt-xs">Sin foto</div>
                          </div>
                        </q-card-section>
                      </q-card>
                    </div>

                    <div class="col-12 q-mt-sm">
                      <q-banner v-if="orden.cliente" class="bg-grey-2">
                        Cliente seleccionado: <b>{{ orden.cliente.name }}</b> (CI: {{ orden.cliente.ci }})
                        <q-chip :color="orden.cliente.status === 'Confiable' ? 'green' : orden.cliente.status === 'No Confiable' ? 'red' : 'orange'" text-color="white" dense size="10px">
                          {{ orden.cliente.status }}
                        </q-chip>
                      </q-banner>
                    </div>
                  </div>

                  <div class="q-mt-md text-right">
                    <q-btn label="Cancelar" type="button" color="negative" @click="$router.push('/ordenes')" class="q-mr-sm" :loading="loading" />
                    <q-btn label="Guardar" type="submit" color="green" :loading="loading" />
                  </div>
                </q-form>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="clientDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-sm">
          <div class="text-h6">Nuevo Cliente</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="clientDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit.prevent="crearClienteRapido">
            <q-input v-model="clientForm.name" label="Nombre" outlined dense :rules="[val => !!val || 'Campo requerido']" @update:model-value="clientForm.name = upper(clientForm.name)" />
            <q-input v-model="clientForm.ci" label="CI" outlined dense class="q-mt-sm" :rules="[val => !!val || 'Campo requerido']" @update:model-value="clientForm.ci = upper(clientForm.ci)" />
            <q-select v-model="clientForm.status" label="Estado" :options="statuses" outlined dense class="q-mt-sm" />
            <q-input v-model="clientForm.cellphone" label="Celular" outlined dense class="q-mt-sm" />

            <div class="q-mt-md text-right">
              <q-btn flat label="Cancelar" color="negative" @click="clientDialog = false" :loading="clientLoading" />
              <q-btn label="Guardar" type="submit" color="positive" class="q-ml-sm" :loading="clientLoading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'OrdenCrearPage',
  data () {
    return {
      page: 1,
      totalPages: 1,
      check18Kilates: '18 Kilates',
      orden: {
        numero: '',
        tipo_pago: 'Efectivo',
        fecha_entrega: moment().format('YYYY-MM-DD'),
        detalle: '',
        celular: '',
        costo_total: 0,
        adelanto: 0,
        saldo: 0,
        estado: 'Pendiente',
        peso: 0,
        nota: '',
        cliente_id: null,
        cliente: null,
        foto_modelo: null,
      },
      clientes: [],
      clienteFiltro: '',
      loading: false,
      clientLoading: false,
      precioOro: { value: 0 },
      costoBase: 0,
      reconocimiento: null,
      reconocimientoCampo: false,
      fotoModeloPreview: null,
      clientDialog: false,
      clientForm: {
        name: '',
        ci: '',
        status: 'Confiable',
        cellphone: '',
        address: '',
        observation: ''
      },
      statuses: ['Confiable', 'No Confiable', 'VIP'],
    }
  },
  computed: {
    costoOro () {
      return Number(this.orden.peso || 0) * Number(this.precioOro.value || 0)
    }
  },
  async mounted () {
    this.getClientes()
    const res = await this.$axios.get('cogs/2')
    this.precioOro = res.data

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
    if (SpeechRecognition) {
      this.reconocimiento = new SpeechRecognition()
      this.reconocimiento.lang = 'es-BO'
      this.reconocimiento.interimResults = false
      this.reconocimiento.continuous = false

      this.reconocimiento.onresult = (event) => {
        const resultado = event.results[0][0].transcript
        if (this.reconocimientoCampo === 'detalle') {
          this.orden.detalle = this.orden.detalle
            ? this.orden.detalle + ' ' + resultado
            : resultado
        }
      }
    }
  },
  methods: {
    upper (value) {
      return (value || '').toUpperCase()
    },
    normalizeClientPayload (client) {
      return {
        ...client,
        name: this.upper(client.name),
        ci: this.upper(client.ci),
        address: this.upper(client.address),
        observation: this.upper(client.observation),
      }
    },
    openClientDialog () {
      this.clientForm = {
        name: '',
        ci: '',
        status: 'Confiable',
        cellphone: '',
        address: '',
        observation: ''
      }
      this.clientDialog = true
    },
    crearClienteRapido () {
      this.clientLoading = true
      this.$axios.post('clients', this.normalizeClientPayload(this.clientForm)).then((res) => {
        const nuevoCliente = res.data
        this.clientDialog = false
        this.page = 1
        this.getClientes()
        this.seleccionarCliente(nuevoCliente)
        this.$alert.success('Cliente creado y seleccionado')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al crear cliente')
      }).finally(() => {
        this.clientLoading = false
      })
    },
    iniciarReconocimiento (campo) {
      if (!this.reconocimiento) {
        this.$alert.warning('Reconocimiento de voz no soportado en este navegador')
        return
      }
      this.reconocimientoCampo = campo
      this.reconocimiento.start()
    },
    getClientes () {
      this.loading = true
      this.$axios.get('clients', {
        params: {
          search: this.clienteFiltro,
          page: this.page,
          per_page: 3
        }
      }).then(res => {
        this.clientes = res.data.data
        this.totalPages = res.data.last_page
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al cargar los clientes')
      }).finally(() => {
        this.loading = false
      })
    },
    calcularSaldo () {
      this.orden.saldo = (this.orden.costo_total || 0) - (this.orden.adelanto || 0)
    },
    validarCostoTotal (val) {
      const minPermitido = this.costoBase - 20

      if (val < minPermitido) {
        this.$alert.warning(`El costo total solo puede modificarse ±20 Bs del valor calculado (${this.costoBase.toFixed(2)} Bs)`)
        this.orden.costo_total = this.costoBase
      }

      this.calcularSaldo()
    },
    calcularTotal () {
      const total = this.costoOro
      this.costoBase = total
      this.orden.costo_total = total
      this.calcularSaldo()
    },
    seleccionarCliente (cliente) {
      this.orden.cliente = cliente
      this.orden.cliente_id = cliente.id
      this.orden.celular = cliente.cellphone || ''
    },
    onFotoModeloSelected (file) {
      if (!file) {
        this.fotoModeloPreview = null
        return
      }
      this.fotoModeloPreview = URL.createObjectURL(file)
    },
    onFotoModeloPicked (event) {
      const file = event?.target?.files?.[0] || null
      this.orden.foto_modelo = file
      this.onFotoModeloSelected(file)
    },
    guardarOrden () {
      if (!this.orden.cliente_id) {
        this.$alert.error('Debe seleccionar un cliente')
        return
      }
      // if (Number(this.orden.peso || 0) <= 0) {
      //   this.$alert.error('El peso debe ser mayor a 0')
      //   return
      // }

      this.loading = true
      this.orden.kilates18 = this.check18Kilates

      const formData = new FormData()
      Object.entries({
        ...this.orden,
        tipo: 'Orden',
        user_id: this.$store.user.id
      }).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '' || key === 'cliente') {
          return
        }
        formData.append(key, value)
      })

      this.$axios.post('ordenes', formData).then(() => {
        this.$alert.success('Orden creada con éxito')
        this.$router.push('/ordenes')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al guardar la orden')
      }).finally(() => {
        this.loading = false
      })
    },
    money (value) {
      return `${Number(value || 0).toFixed(2)} Bs.`
    }
  }
}
</script>

<style scoped>
.foto-card {
  border-radius: 16px;
}

.foto-actions {
  display: flex;
  justify-content: flex-end;
}

.foto-preview {
  height: 120px;
  border-radius: 14px;
}

.foto-placeholder {
  height: 120px;
  border: 1px dashed #cfd8dc;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f8fafb;
}

.hidden-file-input {
  display: none;
}
</style>
