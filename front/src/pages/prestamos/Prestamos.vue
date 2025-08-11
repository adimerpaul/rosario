<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section>
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-3">
            <q-input type="date" label="Fecha Inicio" dense outlined v-model="filters.fecha_inicio" @update:model-value="getPrestamos" />
          </div>
          <div class="col-12 col-md-3">
            <q-input type="date" label="Fecha Fin" dense outlined v-model="filters.fecha_fin" @update:model-value="getPrestamos" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="filters.user_id" :options="usuarios" option-label="name" option-value="id"
                      emit-value map-options dense outlined label="Usuario"
                      @update:model-value="getPrestamos" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="filters.estado" :options="estados" dense outlined label="Estado"
                      @update:model-value="getPrestamos" />
          </div>

          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn color="primary" label="Actualizar" icon="refresh" @click="getPrestamos" :loading="loading" no-caps size="10px" />
          </div>
          <div class="col-12 col-md-2 q-mt-sm">
            <q-btn color="green" label="Nuevo Préstamo" icon="add" @click="$router.push('/prestamos/crear')" no-caps size="10px" />
          </div>

          <div class="col-12 col-md-8 q-mt-sm">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-4">
                <q-card bordered class="bg-blue-1">
                  <q-card-section class="row items-center">
                    <q-icon name="savings" color="blue" size="lg" class="q-mr-sm"/>
                    <div>
                      <div class="text-subtitle1 text-weight-bold text-blue">Prestado</div>
                      <div class="text-h6">{{ resumen.prestado.toFixed(2) }}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12 col-md-4">
                <q-card bordered class="bg-orange-1">
                  <q-card-section class="row items-center">
                    <q-icon name="stacked_bar_chart" color="orange" size="lg" class="q-mr-sm"/>
                    <div>
                      <div class="text-subtitle1 text-weight-bold text-orange">Interés</div>
                      <div class="text-h6">{{ resumen.interes.toFixed(2) }}</div>
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
                <th>Peso</th>
                <th>Precio Oro</th>
                <th>Prestado</th>
                <th>Interés</th>
                <th>Saldo</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(p, index) in prestamos" :key="p.id">
                <td>{{ index + 1 }}</td>
                <td>
                  <q-btn-dropdown dense color="primary" no-caps label="Opciones" size="10px">
                    <q-list>
                      <q-item clickable @click="$router.push('/prestamos/editar/' + p.id)" v-close-popup>
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section>Editar</q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </td>
                <td>{{ p.numero }}</td>
                <td>{{ String(p.fecha_creacion).substring(0,10) }}</td>
                <td>{{ p.cliente?.name || 'N/A' }}</td>
                <td>{{ p.user?.name }}</td>
                <td><q-chip :color="colorEstado(p.estado)" text-color="white" dense>{{ p.estado }}</q-chip></td>
                <td>{{ p.peso }}</td>
                <td>{{ p.precio_oro }}</td>
                <td>{{ p.valor_prestado }}</td>
                <td>{{ p.interes }}</td>
                <td>{{ p.saldo }}</td>
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
import moment from 'moment'

export default {
  name: 'PrestamosPage',
  data () {
    return {
      prestamos: [],
      usuarios: [],
      estados: ['Todos','Pendiente','Pagado','Cancelado','Vencido'],
      filters: {
        fecha_inicio: moment().startOf('week').format('YYYY-MM-DD'),
        fecha_fin: moment().endOf('week').format('YYYY-MM-DD'),
        user_id: null,
        estado: 'Todos'
      },
      resumen: { prestado: 0, interes: 0, saldo: 0 },
      loading: false
    }
  },
  mounted () {
    this.getUsuarios()
    this.getPrestamos()
  },
  methods: {
    getUsuarios () {
      this.$axios.get('users').then(res => {
        this.usuarios = [{ id: null, name: 'Todos' }, ...res.data]
      })
    },
    getPrestamos () {
      this.loading = true
      this.$axios.get('prestamos', { params: this.filters })
        .then(res => {
          this.prestamos = res.data
          this.calcularResumen()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'Error al obtener préstamos'))
        .finally(() => { this.loading = false })
    },
    calcularResumen () {
      this.resumen.prestado = this.prestamos.reduce((s, x) => s + Number(x.valor_prestado), 0)
      this.resumen.interes  = this.prestamos.reduce((s, x) => s + Number(x.interes), 0)
      this.resumen.saldo    = this.prestamos.reduce((s, x) => s + Number(x.saldo), 0)
    },
    colorEstado (e) {
      if (e === 'Pendiente') return 'orange'
      if (e === 'Pagado') return 'green'
      if (e === 'Cancelado') return 'red'
      if (e === 'Vencido') return 'deep-orange'
      return 'grey'
    }
  }
}
</script>
