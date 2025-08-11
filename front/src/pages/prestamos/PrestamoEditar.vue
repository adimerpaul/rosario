<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section class="q-pa-xs">
        <div class="row">
          <!-- CLIENTES -->
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <div class="flex">
                <q-btn icon="arrow_back" @click="$router.push('/prestamos')" no-caps size="10px" color="primary" label="Atrás"/>
                <q-card-section class="text-bold q-pa-xs">Seleccionar Cliente</q-card-section>
              </div>
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
                <q-pagination v-model="page" :max="totalPages" :max-pages="6" boundary-numbers @update:model-value="getClientes"/>
              </div>
              <q-markup-table flat bordered dense>
                <thead>
                <tr class="bg-primary text-white"><th>Nombre</th><th>CI</th><th>Estado</th><th>Acción</th></tr>
                </thead>
                <tbody>
                <tr v-for="cli in clientes" :key="cli.id" :class="{ 'bg-blue-2': prestamo.cliente_id === cli.id }">
                  <td style="white-space: normal; max-width: 140px; word-break: break-word; font-size: 12px; line-height:.9">{{ cli.name }}</td>
                  <td>{{ cli.ci }}</td>
                  <td><q-chip :color="cli.status==='Confiable'?'green':cli.status==='No Confiable'?'red':'orange'" text-color="white" dense size="10px">{{ cli.status }}</q-chip></td>
                  <td><q-btn label="Seleccionar" color="primary" dense no-caps icon="check" @click="seleccionarCliente(cli)"/></td>
                </tr>
                </tbody>
              </q-markup-table>
            </q-card>
          </div>

          <!-- FORM -->
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section class="text-bold q-pa-xs">
                Actualizar Préstamo
                <span class="text-grey text-caption">(Precio oro compra: {{ precioOro.value }})</span>
                <q-chip :color="prestamo.estado==='Pendiente' ? 'orange' : prestamo.estado==='Pagado' ? 'green' : 'red'"
                        text-color="white" dense size="10px">{{ prestamo.estado }}</q-chip>
              </q-card-section>
              <q-card-section class="q-pa-xs">
                <q-form>
                  <div class="row q-col-gutter-sm">
                    <div class="col-6 col-md-4"><q-input label="Fecha Límite" type="date" v-model="prestamo.fecha_limite" outlined dense/></div>
                    <div class="col-6 col-md-4"><q-input label="Celular" v-model="prestamo.celular" outlined dense/></div>
                    <div class="col-6 col-md-4">
                      <q-input label="Peso (kg)" type="number" v-model.number="prestamo.peso" min="0" step="0.001" outlined dense
                               @update:model-value="calcularTotales"/>
                    </div>

                    <div class="col-6 col-md-4"><q-input label="Valor Total (ref.)" v-model.number="prestamo.valor_total" type="number" outlined dense readonly/></div>
                    <div class="col-6 col-md-4"><q-input label="Prestado (Bs)" v-model.number="prestamo.valor_prestado" type="number" outlined dense @update:model-value="calcularSaldo"/></div>
                    <div class="col-6 col-md-4"><q-input label="Interés (Bs)" v-model.number="prestamo.interes" type="number" outlined dense @update:model-value="calcularSaldo"/></div>
                    <div class="col-12 col-md-4"><q-input label="Saldo" v-model.number="prestamo.saldo" type="number" outlined dense readonly/></div>

                    <div class="col-12"><q-input label="Detalle" v-model="prestamo.detalle" type="textarea" outlined dense clearable/></div>

                    <div class="col-12 q-mt-sm" v-if="prestamo.cliente">
                      <q-banner class="bg-grey-2">
                        Cliente: <b>{{ prestamo.cliente.name }}</b> (CI: {{ prestamo.cliente.ci }})
                      </q-banner>
                    </div>
                  </div>

                  <div class="q-mt-md text-right">
                    <q-btn label="Cancelar" color="negative" @click="$router.push('/prestamos')" class="q-mr-sm" :loading="loading"/>
                    <q-btn label="Actualizar" color="orange" @click="actualizarPrestamo" :loading="loading"/>
                  </div>
                </q-form>
              </q-card-section>
            </q-card>
          </div>

          <!-- PAGOS -->
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section class="q-pa-none">
                <div class="text-bold q-pa-sm">Registrar Pago</div>
                <div class="row q-col-gutter-sm q-pa-sm">
                  <div class="col-6 col-md-6"><q-input dense outlined v-model.number="nuevoPago.monto" type="number" min="1" step="0.01" label="Monto"/></div>
                  <div class="col-6 col-md-6 flex flex-center">
                    <q-btn color="primary" label="Agregar Pago" icon="add" @click="agregarPago" :loading="loading" no-caps/>
                  </div>
                </div>

                <q-markup-table flat bordered dense class="q-mt-sm">
                  <thead>
                  <tr class="bg-primary text-white">
                    <th>Fecha</th><th>Monto</th><th>Usuario</th><th>Estado</th><th>Acción</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="p in pagos" :key="p.id">
                    <td>{{ p.fecha }}</td>
                    <td>{{ p.monto }}</td>
                    <td>{{ p.user?.name || 'N/A' }}</td>
                    <td><q-chip dense square :color="p.estado==='Activo'?'green':'grey'" text-color="white">{{ p.estado }}</q-chip></td>
                    <td>
                      <q-btn v-if="p.estado==='Activo'" class="q-mr-xs" icon="delete" color="negative" dense
                             @click="anularPago(p.id)" size="xs" label="Anular" no-caps/>
                    </td>
                  </tr>
                  <tr v-if="!pagos.length"><td colspan="5" class="text-center text-grey">Sin pagos registrados</td></tr>
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
      page: 1, totalPages: 1,
      clientes: [], clienteFiltro: '',
      loading: false,
      precioOro: { value: 0 },
      prestamo: {
        id: null, fecha_limite: null, peso: 0, valor_total: 0, valor_prestado: 0, interes: 0, saldo: 0,
        celular: '', detalle: '', estado: 'Pendiente', cliente_id: null, cliente: null
      },
      pagos: [],
      nuevoPago: { monto: null }
    }
  },
  async mounted () {
    const resOro = await this.$axios.get('cogs/1')
    this.precioOro = resOro.data
    await this.getPrestamo()
    await this.getClientes()
    await this.cargarPagos()
  },
  methods: {
    getClientes () {
      this.loading = true
      this.$axios.get('clients', { params: { search: this.clienteFiltro, page: this.page, per_page: 10 }})
        .then(res => { this.clientes = res.data.data; this.totalPages = res.data.last_page || 1 })
        .finally(() => { this.loading = false })
    },
    seleccionarCliente (cli) {
      this.prestamo.cliente = cli
      this.prestamo.cliente_id = cli.id
      this.prestamo.celular = cli.cellphone || ''
    },
    async getPrestamo () {
      this.loading = true
      try {
        const { data } = await this.$axios.get(`prestamos/${this.$route.params.id}`)
        this.prestamo = data
        if (this.prestamo.cliente_id) this.seleccionarCliente(this.prestamo.cliente)
      } finally { this.loading = false }
    },
    calcularTotales () {
      const total = (this.prestamo.peso || 0) * (this.precioOro.value || 0)
      this.prestamo.valor_total = total
      this.calcularSaldo()
    },
    calcularSaldo () {
      this.prestamo.saldo = (this.prestamo.valor_prestado || 0) + (this.prestamo.interes || 0) - 0 // recalcula en back con pagos
    },
    actualizarPrestamo () {
      this.loading = true
      this.$axios.put(`prestamos/${this.prestamo.id}`, {
        ...this.prestamo,
        user_id: this.$store.user.id
      })
        .then(() => { this.$alert.success('Préstamo actualizado'); this.$router.push('/prestamos') })
        .catch(e => this.$alert.error(e.response?.data?.message || 'Error al actualizar'))
        .finally(() => { this.loading = false })
    },
    async cargarPagos () {
      const { data } = await this.$axios.get(`prestamos/${this.$route.params.id}/pagos`)
      this.pagos = data
    },
    async agregarPago () {
      if (!this.nuevoPago.monto) { this.$alert.error('Monto requerido'); return }
      this.loading = true
      try {
        await this.$axios.post('prestamos/pagos', {
          prestamo_id: this.$route.params.id,
          monto: this.nuevoPago.monto,
          user_id: this.$store.user.id
        })
        this.nuevoPago.monto = null
        await this.cargarPagos()
        await this.getPrestamo()
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
        this.$alert.success('Pago anulado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al anular pago')
      } finally { this.loading = false }
    }
  }
}
</script>
