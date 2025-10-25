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
                  <q-input
                    dense outlined clearable
                    v-model="clienteFiltro"
                    placeholder="Buscar cliente..."
                    :debounce="400"
                    @update:model-value="getClientes"
                  >
                    <template #append><q-icon name="search" /></template>
                  </q-input>
                </div>
                <div class="col-12 col-md-3 text-right">
                  <q-btn
                    label="Buscar" color="primary" icon="search"
                    @click="getClientes" :loading="loading"
                    no-caps dense size="10px"
                  />
                </div>
              </div>

              <div class="flex flex-center q-mb-sm">
                <div class="col-12 q-mt-sm" v-if="prestamo.cliente">
                  <q-banner class="bg-grey-2">
                    Cliente: <b>{{ prestamo.cliente.name }}</b> (CI: {{ prestamo.cliente.ci }})
                    <q-chip
                      :color="prestamo.cliente.status==='Confiable'?'green':prestamo.cliente.status==='No Confiable'?'red':'orange'"
                      text-color="white" dense size="10px" class="q-ml-sm"
                    >
                      {{ prestamo.cliente.status }}
                    </q-chip>
                  </q-banner>
                </div>
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
                  <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="cli in clientes" :key="cli.id" :class="{ 'bg-blue-2': prestamo.cliente_id === cli.id }">
                  <td style="white-space: normal; max-width: 140px; word-break: break-word; font-size: 12px; line-height:.9">{{ cli.name }}</td>
                  <td>{{ cli.ci }}</td>
                  <td>
                    <q-chip
                      :color="cli.status==='Confiable'?'green':cli.status==='No Confiable'?'red':'orange'"
                      text-color="white" dense size="10px"
                    >
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

          <!-- FORMULARIO -->
          <div class="col-12 col-md-7">
            <q-card flat bordered>
              <q-card-section class="text-bold q-pa-xs">
                Crear Préstamo
                <span class="text-grey text-caption">(Precio oro compra: {{ precioOro.value }})</span>
              </q-card-section>

              <q-card-section class="q-pa-xs">
                <q-form @submit.prevent="guardarPrestamo">
                  <div class="row q-col-gutter-sm">

                    <div class="col-6 col-md-2">
                      <q-input label="Fecha Límite" type="date" v-model="prestamo.fecha_limite" outlined dense readonly />
                    </div>

                    <div class="col-6 col-md-2">
                      <q-input label="Celular" v-model="prestamo.celular" outlined dense/>
                    </div>


                    <div class="col-6 col-md-2">
                      <q-input
                        label="Monto maximo" :model-value="money(prestamo.valor_total)"
                        type="text" outlined dense readonly
                      />
                    </div>
                    <div class="col-6 col-md-2">
                      <q-input
                        label="Peso en oro (kg)" :model-value="pesoNetoStr"
                        outlined dense readonly
                      />
                    </div>
                    <div class="col-6 col-md-2">
                      <q-input label="Cargo mensual" :model-value="money(interesMonto+almacenMonto)" outlined dense readonly/>
                    </div>

                    <div class="col-6 col-md-2">
                      <!--                      <q-input label="Valor de venta" :model-value="money(prestamo.saldo)" outlined dense readonly/>-->
                      <!--                      precioVenta * pesoNeto = {{ money(precioVenta.value * pesoNeto) }}-->
                      <q-input label="Precio venta (Bs)" :model-value="money(precioVenta.value * pesoNeto)" outlined dense readonly/>
                    </div>

                    <div class="col-6 col-md-2">
                      <q-input
                        label="Peso bruto (kg)" type="number" outlined dense
                        v-model.number="prestamo.peso" min="0" step="0.001"
                        @update:model-value="calcularTotales"
                      />
                    </div>

                    <div class="col-6 col-md-2">
                      <q-input
                        label="Merma/Piedra (kg)" type="number" outlined dense
                        v-model.number="prestamo.merma" min="0" step="0.001"
                        @update:model-value="calcularTotales"
                      />
                    </div>
                    <div class="col-6 col-md-2">
                      <q-input
                        label="Prestado (Bs)" v-model.number="prestamo.valor_prestado"
                        type="number" outlined dense
                        @update:model-value="calcularSaldo"
                      />
                    </div>




                    <!-- PORCENTAJES -->
                    <div class="col-6 col-md-2">
                      <q-select
                        label="Interés (%)" outlined dense
                        v-model.number="prestamo.interes"
                        :options="[1,2,3]"
                        @update:model-value="calcularSaldo"
                        :readonly="!isAdmin"
                      />
                    </div>

                    <div class="col-6 col-md-2">
                      <q-select
                        label="Almacén (%)" outlined dense
                        v-model.number="prestamo.almacen"
                        :options="[1,1.5,2,3]"
                        @update:model-value="calcularSaldo"
                        :readonly="!isAdmin"
                      />
                    </div>

                    <!-- MONTOS CALCULADOS -->
<!--                    <div class="col-6 col-md-4">-->
<!--                      <q-input label="Interés (Bs)" :model-value="money(interesMonto)" outlined dense readonly/>-->
<!--                    </div>-->
<!--                    <div class="col-6 col-md-4">-->
<!--                      <q-input label="Almacén (Bs)" :model-value="money(almacenMonto)" outlined dense readonly/>-->
<!--                    </div>-->

                    <div class="col-12">
                      <q-input label="Detalle" v-model="prestamo.detalle" type="textarea" outlined dense />
                    </div>

                    <div class="col-12 q-mt-sm" v-if="prestamo.cliente">
                      <q-banner class="bg-grey-2">
                        Cliente: <b>{{ prestamo.cliente.name }}</b> (CI: {{ prestamo.cliente.ci }})
                        <q-chip
                          :color="prestamo.cliente.status==='Confiable'?'green':prestamo.cliente.status==='No Confiable'?'red':'orange'"
                          text-color="white" dense size="10px" class="q-ml-sm"
                        >
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
      page: 1,
      totalPages: 1,
      clientes: [],
      clienteFiltro: '',
      loading: false,

      precioOro: { value: 0 },
      precioVenta: { value: 0 },

      prestamo: {
        fecha_limite: moment().add(1,'months').format('YYYY-MM-DD'),
        peso: 0,              // kg (bruto)
        merma: 0,             // kg
        valor_total: 0,       // Bs (neto * precio oro)
        valor_prestado: 0,    // Bs
        interes: 3,           // %
        almacen: 3,           // % (se autoajusta a 2% si >= 1000 cuando no es admin)
        saldo: 0,             // Bs (total a pagar)
        celular: '',
        detalle: '',
        cliente_id: null,
        cliente: null
      },

      pesoNeto: 0,        // kg (bruto - merma)
      interesMonto: 0,    // Bs
      almacenMonto: 0,    // Bs
      totalPagar: 0       // Bs
    }
  },

  computed: {
    isAdmin () {
      const r = (this.$store?.user?.role || '').toString().toLowerCase()
      return r.includes('admin') // "admin", "administrador", etc.
    },
    pesoNetoStr () {
      return this.pesoNeto.toFixed(3)
    }
  },

  async mounted () {
    await this.getClientes()
    let res = await this.$axios.get('cogs/3') // precio compra de oro
    this.precioOro = res.data
    res = await this.$axios.get('cogs/1') // precio compra de oro
    this.precioVenta = res.data
    this.calcularTotales()
  },

  watch: {
    precioOro: {
      deep: true,
      handler () { this.calcularTotales() }
    }
  },

  methods: {
    getClientes () {
      this.loading = true
      this.$axios.get('clients', {
        params: { search: this.clienteFiltro, page: this.page, per_page: 3 }
      })
        .then(res => {
          this.clientes   = res.data.data
          this.totalPages = res.data.last_page || 1
        })
        .finally(() => { this.loading = false })
    },

    seleccionarCliente (cli) {
      this.prestamo.cliente    = cli
      this.prestamo.cliente_id = cli.id
      this.prestamo.celular    = cli.cellphone || ''
    },

    calcularTotales () {
      const bruto = Number(this.prestamo.peso || 0)
      let   merma = Number(this.prestamo.merma || 0)

      if (merma > bruto) {
        merma = bruto
        this.prestamo.merma = bruto
        this.$alert?.warning?.('La merma no puede ser mayor que el peso bruto.')
      }

      const neto = +(bruto - merma).toFixed(3)
      this.pesoNeto = neto

      const precio = Number(this.precioOro?.value || 0)
      this.prestamo.valor_total = +(neto * precio).toFixed(2)

      this.calcularSaldo()
    },

    calcularSaldo () {
      const vp = Number(this.prestamo.valor_prestado || 0)

      // 1) Fijar % de almacén si NO es admin (regla: >=1000 => 2%, si no 3%)
      // this.prestamo.almacen = vp >= 1000 ? 2 : 3 pero si es admin puede cambiar
      if (!this.isAdmin) {
        this.prestamo.almacen = vp >= 1000 ? 2 : 3
      }

      // 2) Recalcular montos con los % vigentes
      const iPerc = Number(this.prestamo.interes || 0)  // %
      const aPerc = Number(this.prestamo.almacen || 0)  // %

      this.interesMonto = +(vp * iPerc / 100).toFixed(2)
      this.almacenMonto = +(vp * aPerc / 100).toFixed(2)

      this.totalPagar   = +(vp + this.interesMonto + this.almacenMonto).toFixed(2)
      this.prestamo.saldo = this.totalPagar
    },

    guardarPrestamo () {
      if (!this.prestamo.cliente_id) {
        this.$alert?.error?.('Debe seleccionar un cliente')
        return
      }

      this.loading = true
      this.$axios.post('prestamos', {
        ...this.prestamo,
        // OJO: el back espera "cliente_id" (no client_id)
        cliente_id: this.prestamo.cliente_id,
        user_id: this.$store.user.id
      })
        .then(() => {
          this.$alert.success('Préstamo creado')
          this.$router.push('/prestamos')
        })
        .catch(err => this.$alert.error(err.response?.data?.message || 'Error al guardar'))
        .finally(() => { this.loading = false })
    },

    money (v) { return Number(v || 0).toFixed(2) }
  }
}
</script>
