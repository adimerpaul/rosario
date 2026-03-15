<template>
  <q-page class="venta-page q-pa-md">
    <div class="venta-shell">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-lg-7">
          <q-card flat bordered class="panel-card">
            <q-card-section class="row items-center q-col-gutter-sm">
              <div class="col-12 col-md">
                <div class="text-h5 text-weight-bold">Vender joya</div>
                <div class="text-caption text-grey-7">Selecciona una joya disponible por vitrina, estuche o nombre.</div>
              </div>
              <div class="col-12 col-md-auto">
                <q-btn color="primary" no-caps icon="refresh" label="Actualizar" :loading="loadingJoyas" @click="getJoyas" />
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section>
              <div class="row q-col-gutter-sm">
                <div class="col-12 col-md-4">
                  <q-select
                    v-model="filters.vitrina_id"
                    :options="vitrinaOptions"
                    option-label="label"
                    option-value="value"
                    emit-value
                    map-options
                    outlined
                    dense
                    clearable
                    label="Vitrina"
                  />
                </div>
                <div class="col-12 col-md-4">
                  <q-select
                    v-model="filters.estuche_id"
                    :options="estucheOptions"
                    option-label="label"
                    option-value="value"
                    emit-value
                    map-options
                    outlined
                    dense
                    clearable
                    label="Estuche"
                  />
                </div>
                <div class="col-12 col-md-4">
                  <q-input
                    v-model="filters.search"
                    outlined
                    dense
                    clearable
                    debounce="350"
                    label="Buscar joya"
                  >
                    <template #append>
                      <q-icon name="search" />
                    </template>
                  </q-input>
                </div>
              </div>
            </q-card-section>

            <q-card-section class="q-pt-none">
              <div v-if="joyas.length" class="joyas-grid">
                <q-card
                  v-for="joya in joyas"
                  :key="joya.id"
                  flat
                  bordered
                  class="joya-card cursor-pointer"
                  :class="{ 'joya-card--active': form.joya_id === joya.id }"
                  @click="selectJoya(joya)"
                >
                  <q-img :src="imagenUrl(joya.imagen)" class="joya-card__image" fit="cover" />
                  <q-card-section class="q-pa-sm">
                    <div class="text-body2 text-weight-bold ellipsis">{{ joya.nombre }}</div>
                    <div class="text-caption text-grey-7">{{ joya.tipo }} · {{ joya.peso }} gr</div>
                    <div class="text-caption text-grey-7">{{ joya.vitrina_nombre || 'Sin vitrina' }} / {{ joya.estuche_nombre || 'Sin estuche' }}</div>
                    <div class="text-subtitle2 text-primary text-weight-bold q-mt-xs">{{ money(joya.precio_referencial) }} Bs</div>
                  </q-card-section>
                </q-card>
              </div>

              <div v-else class="empty-block">
                <q-icon name="diamond" size="44px" color="grey-5" />
                <div class="text-subtitle2 q-mt-sm">No hay joyas disponibles para venta</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-lg-5">
          <q-card flat bordered class="panel-card">
            <q-card-section>
              <div class="text-h6 text-weight-bold">Datos de la venta</div>
            </q-card-section>
            <q-separator />

            <q-card-section v-if="selectedJoya" class="q-pb-none">
              <div class="selected-joya">
                <q-img :src="imagenUrl(selectedJoya.imagen)" class="selected-joya__image" fit="cover" />
                <div>
                  <div class="text-subtitle1 text-weight-bold">{{ selectedJoya.nombre }}</div>
                  <div class="text-caption text-grey-7">{{ selectedJoya.tipo }} · {{ selectedJoya.linea }}</div>
                  <div class="text-caption text-grey-7">{{ selectedJoya.vitrina_nombre || 'Sin vitrina' }} / {{ selectedJoya.estuche_nombre || 'Sin estuche' }}</div>
                </div>
              </div>
              <q-banner class="bg-blue-1 text-blue-10 q-mt-sm rounded-borders">
                <div class="text-body2 text-weight-bold">{{ selectedJoya.precio_configuracion_nombre }}</div>
                <div class="text-caption">
                  Precio base: {{ money(selectedJoya.precio_configuracion_valor) }} Bs por gramo.
                  Calculo actual: {{ selectedJoya.peso }} gr x {{ money(selectedJoya.precio_configuracion_valor) }} = {{ money(selectedJoya.precio_referencial) }} Bs
                </div>
              </q-banner>
            </q-card-section>

            <q-card-section>
              <q-form @submit.prevent="guardarVenta">
                <div class="text-subtitle2 text-weight-bold q-mb-sm">Cliente</div>
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-8">
                    <q-input
                      v-model="clienteFiltro"
                      outlined
                      dense
                      clearable
                      debounce="350"
                      label="Buscar cliente"
                      @update:model-value="getClientes"
                    >
                      <template #append><q-icon name="search" /></template>
                    </q-input>
                  </div>
                  <div class="col-12 col-md-4">
                    <q-btn class="full-width" color="positive" no-caps icon="add" label="Nuevo" @click="openClientDialog" />
                  </div>
                </div>

                <div v-if="form.cliente" class="q-mt-sm selected-client">
                  Cliente: <b>{{ form.cliente.name }}</b> · CI {{ form.cliente.ci }}
                </div>

                <q-list bordered separator class="q-mt-sm client-list">
                  <q-item v-for="cliente in clientes" :key="cliente.id" clickable @click="seleccionarCliente(cliente)">
                    <q-item-section>
                      <q-item-label>{{ cliente.name }}</q-item-label>
                      <q-item-label caption>{{ cliente.ci }} · {{ cliente.cellphone || 'Sin celular' }}</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-chip dense :color="cliente.status === 'Confiable' ? 'green' : cliente.status === 'No Confiable' ? 'red' : 'orange'" text-color="white">
                        {{ cliente.status }}
                      </q-chip>
                    </q-item-section>
                  </q-item>
                </q-list>

                <div class="text-subtitle2 text-weight-bold q-mt-md q-mb-sm">Venta</div>
                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-md-4">
                    <q-input v-model="form.fecha_entrega" type="date" label="Fecha" outlined dense />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input v-model="form.celular" label="Celular" outlined dense />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input v-model.number="form.peso" label="Peso" type="number" outlined dense readonly />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input v-model.number="form.costo_total" label="Costo total" type="number" outlined dense @update:model-value="updateSaldo" />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input v-model.number="form.adelanto" label="Adelanto" type="number" outlined dense @update:model-value="updateSaldo" />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input :model-value="money(form.saldo)" label="Saldo" outlined dense readonly />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input :model-value="estadoPrevisto" label="Estado" outlined dense readonly />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-select v-model="form.tipo_pago" :options="['Efectivo', 'QR']" label="Tipo pago" outlined dense />
                  </div>
                  <div class="col-12">
                    <q-input v-model="form.detalle" type="textarea" label="Detalle" outlined dense autogrow />
                  </div>
                </div>

                <div class="text-right q-mt-md">
                  <q-btn flat color="negative" no-caps label="Cancelar" @click="$router.push('/ventas-joyas')" :loading="saving" />
                  <q-btn color="primary" no-caps label="Guardar venta" type="submit" class="q-ml-sm" :loading="saving" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <q-dialog v-model="clientDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-sm">
          <div class="text-h6">Nuevo cliente</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="clientDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit.prevent="crearClienteRapido">
            <q-input v-model="clientForm.name" label="Nombre" outlined dense :rules="[val => !!val || 'Campo requerido']" @update:model-value="clientForm.name = upper(clientForm.name)" />
            <q-input v-model="clientForm.ci" label="CI" outlined dense class="q-mt-sm" :rules="[val => !!val || 'Campo requerido']" @update:model-value="clientForm.ci = upper(clientForm.ci)" />
            <q-select v-model="clientForm.status" :options="statuses" label="Estado" outlined dense class="q-mt-sm" />
            <q-input v-model="clientForm.cellphone" label="Celular" outlined dense class="q-mt-sm" />

            <div class="text-right q-mt-md">
              <q-btn flat color="negative" no-caps label="Cancelar" @click="clientDialog = false" :loading="clientLoading" />
              <q-btn color="positive" no-caps label="Guardar" type="submit" class="q-ml-sm" :loading="clientLoading" />
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
  name: 'VentaJoyaCrearPage',
  data () {
    return {
      loadingJoyas: false,
      saving: false,
      clientLoading: false,
      joyas: [],
      vitrinas: [],
      clientes: [],
      clienteFiltro: '',
      clientDialog: false,
      filters: {
        vitrina_id: null,
        estuche_id: null,
        search: ''
      },
      form: {
        joya_id: null,
        cliente_id: null,
        cliente: null,
        fecha_entrega: moment().format('YYYY-MM-DD'),
        celular: '',
        peso: 0,
        costo_total: 0,
        adelanto: 0,
        saldo: 0,
        tipo_pago: 'Efectivo',
        detalle: ''
      },
      clientForm: {
        name: '',
        ci: '',
        status: 'Confiable',
        cellphone: '',
        address: '',
        observation: ''
      },
      statuses: ['Confiable', 'No Confiable', 'VIP']
    }
  },
  computed: {
    vitrinaOptions () {
      return this.vitrinas.map(vitrina => ({ label: vitrina.nombre, value: vitrina.id }))
    },
    estucheOptions () {
      return this.vitrinas
        .filter(vitrina => !this.filters.vitrina_id || vitrina.id === this.filters.vitrina_id)
        .flatMap(vitrina => (vitrina.columnas || []).flatMap(columna => (columna.estuches || []).map(estuche => ({
          label: `${vitrina.nombre} / ${columna.codigo} / ${estuche.nombre}`,
          value: estuche.id
        }))))
    },
    selectedJoya () {
      return this.joyas.find(joya => joya.id === this.form.joya_id) || null
    },
    estadoPrevisto () {
      return Number(this.form.saldo || 0) <= 0 ? 'Entregado' : 'Pendiente'
    }
  },
  mounted () {
    this.getVitrinas()
    this.getJoyas()
    this.getClientes()
  },
  methods: {
    upper (value) {
      return (value || '').toUpperCase()
    },
    money (value) {
      return Number(value || 0).toFixed(2)
    },
    imagenUrl (imagen) {
      return `${this.$url}/../images/${imagen || 'joya.png'}`
    },
    getVitrinas () {
      this.$axios.get('vitrinas').then(({ data }) => {
        this.vitrinas = data
      })
    },
    getJoyas () {
      this.loadingJoyas = true
      this.$axios.get('ordenes/joyas-disponibles', {
        params: this.filters
      }).then(({ data }) => {
        this.joyas = data || []
        if (this.form.joya_id && !this.joyas.some(joya => joya.id === this.form.joya_id)) {
          this.form.joya_id = null
        }
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al cargar joyas')
      }).finally(() => {
        this.loadingJoyas = false
      })
    },
    selectJoya (joya) {
      this.form.joya_id = joya.id
      this.form.peso = Number(joya.peso || 0)
      this.form.costo_total = Number(joya.precio_referencial || 0)
      this.form.adelanto = Number(joya.precio_referencial || 0)
      this.form.detalle = `VENTA DIRECTA: ${joya.nombre} | ${joya.tipo} | ${joya.peso} GR`
      this.updateSaldo()
    },
    updateSaldo () {
      this.form.saldo = Math.max(0, Number(this.form.costo_total || 0) - Number(this.form.adelanto || 0))
    },
    getClientes () {
      this.$axios.get('clients', {
        params: {
          search: this.clienteFiltro,
          page: 1,
          per_page: 6
        }
      }).then(({ data }) => {
        this.clientes = data.data || data
      })
    },
    seleccionarCliente (cliente) {
      this.form.cliente = cliente
      this.form.cliente_id = cliente.id
      this.form.celular = cliente.cellphone || ''
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
      this.$axios.post('clients', {
        ...this.clientForm,
        name: this.upper(this.clientForm.name),
        ci: this.upper(this.clientForm.ci),
        address: this.upper(this.clientForm.address),
        observation: this.upper(this.clientForm.observation)
      }).then(({ data }) => {
        this.clientDialog = false
        this.seleccionarCliente(data)
        this.getClientes()
        this.$alert.success('Cliente creado y seleccionado')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al crear cliente')
      }).finally(() => {
        this.clientLoading = false
      })
    },
    guardarVenta () {
      if (!this.form.joya_id) {
        this.$alert.error('Debe seleccionar una joya')
        return
      }

      if (!this.form.cliente_id) {
        this.$alert.error('Debe seleccionar un cliente')
        return
      }

      this.saving = true
      this.$axios.post('ordenes', {
        ...this.form,
        tipo: 'Venta directa'
      }).then(() => {
        this.$alert.success('Venta registrada correctamente')
        this.$router.push('/ventas-joyas')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al registrar la venta')
      }).finally(() => {
        this.saving = false
      })
    }
  },
  watch: {
    'filters.vitrina_id' () {
      this.filters.estuche_id = null
      this.getJoyas()
    },
    'filters.estuche_id' () {
      this.getJoyas()
    },
    'filters.search' () {
      this.getJoyas()
    }
  }
}
</script>

<style scoped>
.venta-page {
  background: linear-gradient(180deg, #e8f0f4 0%, #f5f1ea 100%);
  min-height: 100%;
}

.venta-shell {
  max-width: 1600px;
  margin: 0 auto;
}

.panel-card {
  border-radius: 22px;
  box-shadow: 0 22px 50px rgba(31, 63, 82, 0.12);
}

.joyas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.joya-card {
  border-radius: 18px;
  overflow: hidden;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}

.joya-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
}

.joya-card--active {
  border-color: #1976d2;
  box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.12);
}

.joya-card__image {
  height: 150px;
}

.selected-joya {
  display: grid;
  grid-template-columns: 92px 1fr;
  gap: 12px;
  align-items: center;
}

.selected-joya__image {
  border-radius: 16px;
  height: 92px;
}

.selected-client {
  padding: 10px 12px;
  border-radius: 12px;
  background: #f3f7f9;
}

.client-list {
  max-height: 220px;
  overflow: auto;
  border-radius: 14px;
}

.empty-block {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}
</style>
