<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section class="q-pa-xs">
        <div class="row">
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section class="text-bold">Seleccionar Cliente</q-card-section>
              <div class="row">
                <div class="col-12 col-md-8">
                  <q-input dense outlined debounce="300" v-model="clienteFiltro" placeholder="Buscar cliente..."
                           class="q-mb-sm" clearable>
                    <template v-slot:append>
                      <q-icon name="search"/>
                    </template>
                  </q-input>
                </div>
                <div class="col-12 col-md-2 text-right">
                  <q-btn label="Buscar" color="primary" class="q-mb-sm" @click="getClientes" icon="search"
                         :loading="loading" no-caps dense size="10px"/>
                </div>
              </div>
              <q-markup-table flat bordered dense>
                <thead>
                <tr class="bg-primary text-white">
<!--                  <th>#</th>-->
                  <th>Nombre</th>
                  <th>CI</th>
                  <th>Estado</th>
                  <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(cli, index) in clientes" :key="cli.id">
                  <!--                  <td>{{ index + 1 }}</td>-->
                  <td style="white-space: normal; max-width: 120px; word-break: break-word; font-size: 12px; line-height: 0.9;">
                    {{ cli.name }}
                  </td>
                  <td>{{ cli.ci }}</td>
                  <td>
                    <q-chip :color="cli.status === 'Confiable' ? 'green' : cli.status === 'No Confiable' ? 'red' : 'orange'"
                            text-color="white" dense size="10px">
                      {{ cli.status }}
                    </q-chip>
                  </td>
                  <td>
                    <q-btn label="Seleccionar" color="primary" dense @click="seleccionarCliente(cli)" icon="check"
                           size="10px" no-caps/>
                  </td>
                </tr>
                </tbody>
              </q-markup-table>
            </q-card>
          </div>
          <div class="col-12 col-md-6">
            <q-form @submit.prevent="guardarOrden">
              <div class="row q-col-gutter-sm">
<!--                <div class="col-12 col-md-4">-->
<!--                  <q-input label="Nro Orden" v-model="orden.numero" outlined dense-->
<!--                           :rules="[val => !!val || 'Requerido']"/>-->
<!--                </div>-->
<!--                <div class="col-12 col-md-4">-->
<!--                  <q-input label="Fecha de Creación" type="date" v-model="orden.fecha_creacion" outlined dense-->
<!--                           :rules="[val => !!val || 'Requerido']"/>-->
<!--                </div>-->
                <div class="col-12 col-md-4">
                  <q-input label="Fecha de Entrega" type="date" v-model="orden.fecha_entrega" outlined dense/>
                </div>
                <div class="col-12 col-md-6">
                  <q-input label="Detalle" v-model="orden.detalle" type="textarea" outlined dense/>
                </div>
                <div class="col-12 col-md-6">
                  <q-input label="Celular" v-model="orden.celular" outlined dense/>
                </div>
                <div class="col-6 col-md-4">
                  <q-input label="Costo Total" v-model.number="orden.costo_total" type="number" outlined dense
                           @update:model-value="calcularSaldo"/>
                </div>
                <div class="col-6 col-md-4">
                  <q-input label="Adelanto" v-model.number="orden.adelanto" type="number" outlined dense
                           @update:model-value="calcularSaldo"/>
                </div>
                <div class="col-12 col-md-4">
                  <q-input label="Saldo" v-model.number="orden.saldo" type="number" outlined dense readonly/>
                </div>
                <div class="col-12 col-md-4">
                  <q-input label="Peso (kg)" v-model.number="orden.peso" type="number" outlined dense/>
                </div>
                <div class="col-12 col-md-8">
                  <q-input label="Nota" v-model="orden.nota" type="textarea" outlined dense/>
                </div>
                <div class="col-12">
                  <q-select label="Estado" v-model="orden.estado" :options="estados" outlined dense/>
                </div>
                <div class="col-12">
                </div>
                <div class="col-12 q-mt-sm">
                  <q-banner v-if="orden.cliente" class="bg-grey-2">
                    Cliente seleccionado: <b>{{ orden.cliente.name }}</b> (CI: {{ orden.cliente.ci }})
                  </q-banner>
                </div>
              </div>
              <div class="q-mt-md text-right">
                <q-btn label="Guardar" type="submit" color="positive" :loading="loading"/>
              </div>
            </q-form>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import moment from "moment";

export default {
  name: 'OrdenCrearPage',
  data() {
    return {
      orden: {
        numero: '',
        // fecha_creacion: new Date().toISOString().substr(0, 10),
        fecha_entrega: moment().add(1, 'weeks').format('YYYY-MM-DD'),
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
      },
      estados: ['Pendiente', 'Entregado', 'Cancelada'],
      clientes: [],
      clienteFiltro: '',
      loading: false
    }
  },
  computed: {
    // clientesFiltrados() {
    //   return this.clientes.filter(cli =>
    //     cli.name.toLowerCase().includes(this.clienteFiltro.toLowerCase())
    //   ).slice(0, 20);
    // }
  },
  mounted() {
    this.getClientes();
  },
  methods: {
    getClientes() {
      this.loading = true;
      this.$axios.get('clients',{
        params: {
          search: this.clienteFiltro,
          page: 1,
          per_page: 20
        }
      }).then(res => {
        this.clientes = res.data.data;
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al cargar los clientes');
      }).finally(() => {
        this.loading = false;
      });
    },
    calcularSaldo() {
      this.orden.saldo = (this.orden.costo_total || 0) - (this.orden.adelanto || 0);
    },
    seleccionarCliente(cliente) {
      this.orden.cliente = cliente;
      this.orden.cliente_id = cliente.id;
    },
    guardarOrden() {
      if (!this.orden.cliente_id) {
        this.$alert.error('Debe seleccionar un cliente');
        return;
      }
      this.loading = true;
      this.$axios.post('ordenes', {
        ...this.orden,
        user_id: this.$store.user.id
      }).then(res => {
        this.$alert.success('Orden creada con éxito');
        this.$router.push('/ordenes');
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al guardar la orden');
      }).finally(() => {
        this.loading = false;
      });
    }
  }
}
</script>
