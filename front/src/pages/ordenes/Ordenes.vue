<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section>
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-3">
            <q-input label="Fecha Inicio" type="date" dense outlined v-model="filters.fecha_inicio" @update:model-value="getOrdenes" />
          </div>
          <div class="col-12 col-md-3">
            <q-input label="Fecha Fin" type="date" dense outlined v-model="filters.fecha_fin" @update:model-value="getOrdenes" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="filters.user_id" :options="usuarios" option-label="name" option-value="id" label="Usuario" dense outlined @update:model-value="getOrdenes"
                      emit-value map-options
            />
<!--            <pre>{{filters.user_id}}</pre>-->
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="filters.estado" :options="estados" label="Estado" dense outlined @update:model-value="getOrdenes" />
          </div>

          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn color="primary" label="Actualizar" icon="refresh" @click="getOrdenes" :loading="loading" no-caps size="10px" />
          </div>
          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn color="green" label="Crear Orden" icon="add" @click="$router.push('/ordenes/crear')" no-caps size="10px" />
          </div>
          <div class="col-12 col-md-8 q-mt-sm">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-4">
                <q-card bordered class="bg-blue-1">
                  <q-card-section class="row items-center">
                    <q-icon name="payments" color="blue" size="lg" class="q-mr-sm"/>
                    <div>
                      <div class="text-subtitle1 text-weight-bold text-blue">Total</div>
                      <div class="text-h6">{{ resumen.total.toFixed(2) }}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12 col-md-4">
                <q-card bordered class="bg-green-1">
                  <q-card-section class="row items-center">
                    <q-icon name="trending_up" color="green" size="lg" class="q-mr-sm"/>
                    <div>
                      <div class="text-subtitle1 text-weight-bold text-green">Adelanto</div>
                      <div class="text-h6">{{ resumen.adelanto.toFixed(2) }}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12 col-md-4">
                <q-card bordered class="bg-red-1">
                  <q-card-section class="row items-center">
                    <q-icon name="account_balance_wallet" color="red" size="lg" class="q-mr-sm"/>
                    <div>
                      <div class="text-subtitle1 text-weight-bold text-red">Saldo</div>
                      <div class="text-h6">{{ resumen.saldo.toFixed(2) }}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>

          <div class="col-12 q-mt-sm">
            <q-markup-table dense flat bordered>
              <thead>
              <tr class="bg-primary text-white">
                <th>#</th>
                <th>Opciones</th>
                <th>Nro</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>Estado</th>
                <th>Costo</th>
                <th>Adelanto</th>
                <th>Saldo</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(orden, index) in ordenes" :key="orden.id">
                <td>{{ index + 1 }}</td>
                <td>
<!--                  <q-btn dense flat icon="edit" color="primary" @click="$router.push('/ordenes/editar/' + orden.id)" />-->
                  <q-btn-dropdown dense color="primary" no-caps label="Opciones" size="10px">
                    <q-list>
                      <q-item clickable @click="$router.push('/ordenes/editar/' + orden.id)" v-close-popup>
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section>Editar</q-item-section>
                      </q-item>
                      <q-item clickable @click="imprimirOrden(orden.id)" v-close-popup>
                        <q-item-section avatar><q-icon name="print" /></q-item-section>
                        <q-item-section>Imprimir</q-item-section>
                      </q-item>
                      <q-item clickable @click="imprimirGarantia(orden.id)" v-close-popup>
                        <q-item-section avatar><q-icon name="assignment" /></q-item-section>
                        <q-item-section>Imprimir Garantía</q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </td>
                <td>{{ orden.numero }}</td>
                <td>{{ orden.fecha_creacion.substring(0,10) }}</td>
                <td>{{ orden.cliente?.name || 'N/A' }}</td>
                <td>{{ orden.user?.name }}</td>
                <td>
                  <q-chip :color="getEstadoColor(orden.estado)" text-color="white" dense>{{ orden.estado }}</q-chip>
                </td>
                <td>{{ orden.costo_total }}</td>
                <td>{{ orden.adelanto }}</td>
                <td>{{ orden.saldo }}</td>
              </tr>
              </tbody>
            </q-markup-table>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import moment from "moment";

export default {
  name: 'OrdenesPage',
  data() {
    return {
      ordenes: [],
      usuarios: [],
      estados: ['Todos', 'Pendiente', 'Entregado', 'Cancelada'],
      filters: {
        fecha_inicio: moment().startOf('week').format('YYYY-MM-DD'),
        fecha_fin: moment().endOf('week').format('YYYY-MM-DD'),
        user_id: null,
        estado: 'Todos',
      },
      resumen: {
        total: 0,
        adelanto: 0,
        saldo: 0
      },
      loading: false
    }
  },
  mounted() {
    this.getUsuarios();
    this.getOrdenes();
  },
  methods: {
    imprimirOrden(id) {
      const url = `${this.$axios.defaults.baseURL}/ordenes/${id}/pdf`;
      window.open(url, '_blank'); // abre en nueva pestaña
    },
    imprimirGarantia(id) {
      const url = `${this.$axios.defaults.baseURL}/ordenes/${id}/garantia`;
      window.open(url, '_blank');
    },
    getUsuarios() {
      this.$axios.get('users').then(res => {
        this.usuarios = [{ id: null, name: 'Todos' }, ...res.data];
      });
    },
    getOrdenes() {
      this.loading = true;
      this.$axios.get('ordenes', {
        params: this.filters
      }).then(res => {
        this.ordenes = res.data.data || res.data;
        this.calcularResumen();
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al obtener órdenes');
      }).finally(() => {
        this.loading = false;
      });
    },
    calcularResumen() {
      this.resumen.total = this.ordenes.reduce((sum, o) => sum + Number(o.costo_total), 0);
      this.resumen.adelanto = this.ordenes.reduce((sum, o) => sum + Number(o.adelanto), 0);
      this.resumen.saldo = this.ordenes.reduce((sum, o) => sum + Number(o.saldo), 0);
    },
    getEstadoColor(estado) {
      switch (estado) {
        case 'Pendiente': return 'orange';
        case 'Entregado': return 'green';
        case 'Cancelada': return 'red';
        default: return 'grey';
      }
    }
  }
}
</script>
