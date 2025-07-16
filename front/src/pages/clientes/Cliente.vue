<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <q-card-section>
        <div class="row">
          <div class="col-12 col-md-3">
            <q-btn color="positive" label="Nuevo Cliente" @click="clientNew" icon="add" no-caps :loading="loading"/>
          </div>
          <div class="col-12 col-md-3 text-right"></div>
          <div class="col-12 col-md-4">
            <q-input dense outlined debounce="300" v-model="filter" placeholder="Buscar" clearable>
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-2 text-right">
            <q-btn color="primary" label="Actualizar" @click="clientsGet" icon="refresh" no-caps :loading="loading"/>
          </div>
          <div class="col-12 flex flex-center">
            <q-pagination v-model="pagination.page"
                          :max="Math.ceil(pagination.rowsNumber / pagination.rowsPerPage)"
                          :rows-per-page-options="[10, 25, 50, 100]"
                          :rows-per-page.sync="pagination.rowsPerPage"
                          @update:model-value="clientsGet"
            />
          </div>
          <div class="col-12">
            <q-markup-table dense flat bordered>
              <q-tr class="bg-primary text-white">
                <q-th v-for="col in columns" :key="col.name" :align="col.align">{{ col.label }}</q-th>
              </q-tr>
              <q-tr v-for="client in clients" :key="client.id" :class="rowColorClass(client)">
                <q-td align="center">
                  <q-btn-dropdown color="primary" label="Opciones" dense no-caps size="10px" :loading="loading">
                    <q-list>
                      <q-item clickable @click="clientEdit(client)" v-close-popup>
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section>Editar</q-item-section>
                      </q-item>
                      <q-item clickable @click="clientDelete(client.id)" v-close-popup>
                        <q-item-section avatar><q-icon name="delete" /></q-item-section>
                        <q-item-section>Eliminar</q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </q-td>
                <q-td>{{ client.name }}</q-td>
                <q-td>{{ client.ci }}</q-td>
                <q-td>{{ client.cellphone }}</q-td>
                <q-td>
                  <q-chip :color="client.status === 'Confiable' ? 'green' : client.status === 'No Confiable' ? 'red' : 'orange'"
                          text-color="white" dense size="10px">
                    {{ client.status }}
                  </q-chip>
                </q-td>
              </q-tr>
            </q-markup-table>
          </div>
        </div>
<!--        <q-btn color="primary" label="Actualizar" @click="clientsGet" icon="refresh" no-caps :loading="loading"/>-->
<!--        <q-input dense outlined debounce="300" v-model="filter" placeholder="Buscar">-->
<!--          <template v-slot:append>-->
<!--            <q-icon name="search" />-->
<!--          </template>-->
<!--        </q-input>-->

      </q-card-section>
    </q-card>
    <q-dialog v-model="clientDialog" persistent>
      <q-card style="width: 500px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ actionClient }} Cliente</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="clientDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="client.id ? clientPut() : clientPost()">
            <q-input v-model="client.name" label="Nombre" outlined dense :rules="[val => !!val || 'Campo requerido']" />
            <q-input v-model="client.ci" label="CI" outlined dense :rules="[val => !!val || 'Campo requerido']" />
<!--            <q-input v-model="client.carnet" label="Carnet" outlined dense />-->
            <q-select v-model="client.status" label="Estado" :options="statuses" outlined dense />
            <q-input v-model="client.cellphone" label="Celular" outlined dense />
            <q-input v-model="client.address" label="Dirección" outlined dense />
            <q-input v-model="client.observation" label="Observación" outlined dense type="textarea" />

            <div class="q-gutter-sm q-mt-md text-right">
              <q-btn flat label="Cancelar" color="negative" @click="clientDialog = false" :loading="loading"/>
              <q-btn label="Guardar" type="submit" color="primary" :loading="loading"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'ClientesPage',
  data() {
    return {
      loading: false,
      clients: [],
      client: {},
      clientDialog: false,
      actionClient: '',
      filter: '',
      statuses: ['Confiable', 'No Confiable', 'VIP'],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'name', label: 'Nombre', field: 'name', align: 'left' },
        { name: 'ci', label: 'CI', field: 'ci', align: 'left' },
        { name: 'cellphone', label: 'Celular', field: 'cellphone', align: 'left' },
        { name: 'status', label: 'Estado', field: 'status', align: 'left' },
      ],
      pagination: {
        page: 1,
        rowsPerPage: 100,
        rowsNumber: 0
      },
    }
  },
  mounted() {
    this.clientsGet()
  },
  methods: {
    rowColorClass(row) {
      switch (row.status) {
        case 'Confiable':
          return 'bg-green-1';
        case 'No Confiable':
          return 'bg-orange-1';
        case 'VIP':
          return 'bg-yellow-2 text-bold';
        default:
          return '';
      }
    },
    clientsGet() {
      this.loading = true;
      this.$axios.get('clients', {
        params: {
          page: this.pagination.page,
          per_page: this.pagination.rowsPerPage,
          search: this.filter
        }
      }).then(res => {
        this.clients = res.data.data;
        this.pagination.rowsNumber = res.data.total;
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al obtener clientes');
      }).finally(() => {
        this.loading = false;
      });
    },
    clientNew() {
      this.client = {
        name: '',
        ci: '',
        // carnet: '',
        status: 'Confiable',
        cellphone: '',
        address: '',
        observation: ''
      }
      this.actionClient = 'Nuevo'
      this.clientDialog = true
    },
    clientEdit(client) {
      this.client = { ...client }
      this.actionClient = 'Editar'
      this.clientDialog = true
    },
    clientPost() {
      this.loading = true
      this.$axios.post('clients', this.client).then(() => {
        this.clientDialog = false
        this.clientsGet()
        this.$alert.success('Cliente creado')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al crear cliente')
      }).finally(() => {
        this.loading = false
      })
    },
    clientPut() {
      this.loading = true
      this.$axios.put('clients/' + this.client.id, this.client).then(() => {
        this.clientDialog = false
        this.clientsGet()
        this.$alert.success('Cliente actualizado')
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al actualizar cliente')
      }).finally(() => {
        this.loading = false
      })
    },
    clientDelete(id) {
      this.$alert.dialog('¿Desea eliminar este cliente?')
        .onOk(() => {
          this.$axios.delete('clients/' + id).then(() => {
            this.clientsGet()
            this.$alert.success('Cliente eliminado')
          }).catch(err => {
            this.$alert.error(err.response?.data?.message || 'Error al eliminar cliente')
          })
        })
    }
  }
}
</script>
