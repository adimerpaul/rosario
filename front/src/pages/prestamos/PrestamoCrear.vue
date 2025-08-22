<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section class="q-pa-xs">
        <div class="row">
          <!-- CLIENTES -->
          <div class="col-12 col-md-5">
            <q-card flat bordered>
              <q-card-section class="text-bold q-pa-xs">Seleccionar Cliente</q-card-section>
              <div class="row">
                <div class="col-12 col-md-8">
                  <q-input dense outlined clearable v-model="clienteFiltro" placeholder="Buscar cliente..."
                           :debounce="400" @update:model-value="getClientes">
                    <template #append><q-icon name="search" /></template>
                  </q-input>
                </div>
                <div class="col-12 col-md-3 text-right">
                  <q-btn label="Buscar" color="primary" @click="getClientes" :loading="loading" no-caps dense size="10px" icon="search"/>
                </div>
              </div>
              <div class="flex flex-center q-mb-sm">
                <div class="col-12 q-mt-sm" v-if="prestamo.cliente">
                  <q-banner class="bg-grey-2">
                    Cliente: <b>{{ prestamo.cliente.name }}</b> (CI: {{ prestamo.cliente.ci }})
                    <q-chip :color="prestamo.cliente.status==='Confiable'?'green':prestamo.cliente.status==='No Confiable'?'red':'orange'" text-color="white" dense size="10px" class="q-ml-sm">
                      {{ prestamo.cliente.status }}
                    </q-chip>
                  </q-banner>
                </div>
                <q-pagination size="10px" v-model="page" :max="totalPages" :max-pages="6" boundary-numbers @update:model-value="getClientes"/>
              </div>
              <q-markup-table flat bordered dense>
                <thead>
                <tr class="bg-primary text-white">
                  <th>Nombre</th><th>CI</th><th>Estado</th><th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="cli in clientes" :key="cli.id" :class="{ 'bg-blue-2': prestamo.cliente_id === cli.id }">
                  <td style="white-space: normal; max-width: 140px; word-break: break-word; font-size: 12px; line-height:.9">{{ cli.name }}</td>
                  <td>{{ cli.ci }}</td>
                  <td>
                    <q-chip :color="cli.status==='Confiable'?'green':cli.status==='No Confiable'?'red':'orange'" text-color="white" dense size="10px">
                      {{ cli.status }}
                    </q-chip>
                  </td>
                  <td>
                    <q-btn label="Seleccionar" color="primary" size="xs" dense no-caps icon="check" @click="seleccionarCliente(cli)" />
                  </td>
                </tr>
                </tbody>
              </q-markup-table>
            </q-card>
          </div>

          <!-- FORM -->
          <div class="col-12 col-md-7">
            <q-card flat bordered>
              <q-card-section class="text-bold q-pa-xs">
                Crear Préstamo
                <span class="text-grey text-caption">(Precio oro compra: {{ precioOro.value }})</span>
              </q-card-section>

              <q-card-section class="q-pa-xs">
                <q-form @submit.prevent="guardarPrestamo">
                  <div class="row q-col-gutter-sm">
                    <div class="col-6 col-md-4">
                      <q-input label="Fecha Límite" type="date" v-model="prestamo.fecha_limite" outlined dense/>
                    </div>
                    <div class="col-6 col-md-4">
                      <q-input label="Celular" v-model="prestamo.celular" outlined dense/>
                    </div>
                    <div class="col-6 col-md-4">
                      <q-input label="Peso (kg)" type="number" v-model.number="prestamo.peso" min="0" step="0.001" outlined dense
                               @update:model-value="calcularTotales"/>
                    </div>

                    <div class="col-6 col-md-4">
                      <q-input label="Valor Total (ref.)" v-model.number="prestamo.valor_total" type="number" outlined dense readonly/>
                    </div>
                    <div class="col-6 col-md-4">
                      <q-input label="Prestado (Bs)" v-model.number="prestamo.valor_prestado" type="number" outlined dense
                               @update:model-value="calcularSaldo"/>
                    </div>

                    <!-- PORCENTAJES -->
                    <div class="col-6 col-md-2">
                      <q-select label="Interés (%)" outlined dense v-model.number="prestamo.interes"
                                :options="[1,2,3]" @update:model-value="calcularSaldo" />
                    </div>
                    <div class="col-6 col-md-2">
                      <q-select label="Almacén (%)" outlined dense v-model.number="prestamo.almacen"
                                :options="[1,2,3]" @update:model-value="calcularSaldo" />
                    </div>

                    <!-- MONTOS CALCULADOS -->
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
                      <q-input label="Saldo" v-model.number="prestamo.saldo" type="number" outlined dense readonly/>
                    </div>

                    <div class="col-12">
                      <q-input label="Detalle" v-model="prestamo.detalle" type="textarea" outlined dense />
                    </div>

                    <div class="col-12 q-mt-sm" v-if="prestamo.cliente">
                      <q-banner class="bg-grey-2">
                        Cliente: <b>{{ prestamo.cliente.name }}</b> (CI: {{ prestamo.cliente.ci }})
                        <q-chip :color="prestamo.cliente.status==='Confiable'?'green':prestamo.cliente.status==='No Confiable'?'red':'orange'" text-color="white" dense size="10px" class="q-ml-sm">
                          {{ prestamo.cliente.status }}
                        </q-chip>
                      </q-banner>
                    </div>
                  </div>

                  <div class="q-mt-md text-right">
                    <q-btn label="Cancelar" color="negative" @click="$router.push('/prestamos')" class="q-mr-sm" :loading="loading"/>
                    <q-btn label="Guardar" type="submit" color="green" :loading="loading"/>
                  </div>
                </q-form>
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
  name: 'PrestamoCrearPage',
  data () {
    return {
      page: 1, totalPages: 1,
      clientes: [], clienteFiltro: '',
      loading: false,
      precioOro: { value: 0 },
      prestamo: {
        fecha_limite: moment().add(1,'months').format('YYYY-MM-DD'),
        peso: 0,
        valor_total: 0,
        valor_prestado: 0,
        interes: 1,      // % default
        almacen: 1,      // % default
        saldo: 0,
        celular: '',
        detalle: '',
        cliente_id: null,
        cliente: null
      },
      interesMonto: 0,
      almacenMonto: 0,
      totalPagar: 0
    }
  },
  async mounted () {
    await this.getClientes()
    const res = await this.$axios.get('cogs/1') // precio compra
    this.precioOro = res.data
    this.calcularTotales()
  },
  methods: {
    getClientes () {
      this.loading = true
      this.$axios.get('clients', { params: { search: this.clienteFiltro, page: this.page, per_page: 3 }})
        .then(res => { this.clientes = res.data.data; this.totalPages = res.data.last_page || 1 })
        .finally(() => { this.loading = false })
    },
    seleccionarCliente (cli) {
      this.prestamo.cliente = cli
      this.prestamo.cliente_id = cli.id
      this.prestamo.celular = cli.cellphone || ''
    },
    calcularTotales () {
      const total = (this.prestamo.peso || 0) * (this.precioOro.value || 0)
      this.prestamo.valor_total = total
      this.calcularSaldo()
    },
    calcularSaldo () {
      const vp = Number(this.prestamo.valor_prestado || 0)
      const i  = Number(this.prestamo.interes || 0)     // %
      const a  = Number(this.prestamo.almacen || 0)     // %
      this.interesMonto = +(vp * i / 100).toFixed(2)
      this.almacenMonto = +(vp * a / 100).toFixed(2)
      this.totalPagar   = +(vp + this.interesMonto + this.almacenMonto).toFixed(2)
      this.prestamo.saldo = this.totalPagar
    },
    guardarPrestamo () {
      if (!this.prestamo.cliente_id) { this.$alert?.error?.('Debe seleccionar un cliente'); return }
      this.loading = true
      this.$axios.post('prestamos', {
        ...this.prestamo,
        user_id: this.$store.user.id
      })
        .then(() => { this.$alert.success('Préstamo creado'); this.$router.push('/prestamos') })
        .catch(err => this.$alert.error(err.response?.data?.message || 'Error al guardar'))
        .finally(() => { this.loading = false })
    },
    money (v) { return Number(v || 0).toFixed(2) }
  }
}
</script>
