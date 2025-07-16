<template>
  <q-page class="q-pa-md">
    <q-table
      :rows="docentes"
      :columns="columns"
      flat bordered dense wrap-cells
      :rows-per-page-options="[0]"
      title="Docentes"
      :filter="filter"
    >
      <template v-slot:top-right>
        <q-btn label="Nuevo" icon="add_circle_outline" color="primary" @click="nuevoDocente" :loading="loading" no-caps />
        <q-input v-model="filter" label="Buscar" dense outlined class="q-ml-sm">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Acciones" color="primary" size="sm" no-caps>
            <q-list>
              <q-item clickable @click="editarDocente(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable @click="eliminarDocente(props.row.id)" v-close-popup>
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="width: 400px">
        <q-card-section>
          <div class="text-h6">{{ docente.id ? 'Editar' : 'Nuevo' }} Docente</div>
        </q-card-section>
        <q-card-section>
          <q-form @submit="guardarDocente">
            <q-input v-model="docente.nombre" label="Nombre" dense outlined class="q-mb-sm" :rules="[v => !!v || 'Requerido']" />
            <q-input v-model="docente.ci" label="CI" dense outlined class="q-mb-sm" :rules="[v => !!v || 'Requerido']" />
            <q-input v-model="docente.email" label="Email" dense outlined class="q-mb-sm" type="email" />
            <q-input v-model="docente.telefono" label="Teléfono" dense outlined class="q-mb-sm" />
            <div class="text-right">
              <q-btn flat label="Cancelar" color="negative" v-close-popup />
              <q-btn label="Guardar" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'DocentesPage',
  data() {
    return {
      docentes: [],
      docente: {},
      dialog: false,
      loading: false,
      filter: '',
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
        { name: 'ci', label: 'CI', field: 'ci', align: 'left' },
        { name: 'email', label: 'Email', field: 'email', align: 'left' },
        { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
      ]
    };
  },
  mounted() {
    this.obtenerDocentes();
  },
  methods: {
    obtenerDocentes() {
      this.loading = true;
      this.$axios.get('docentes').then(res => {
        this.docentes = res.data;
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al obtener docentes');
      }).finally(() => {
        this.loading = false;
      });
    },
    nuevoDocente() {
      this.docente = {};
      this.dialog = true;
    },
    editarDocente(docente) {
      this.docente = { ...docente };
      this.dialog = true;
    },
    guardarDocente() {
      this.loading = true;
      const req = this.docente.id
        ? this.$axios.put(`docentes/${this.docente.id}`, this.docente)
        : this.$axios.post('docentes', this.docente);
      req.then(() => {
        this.$alert.success('Docente guardado');
        this.dialog = false;
        this.obtenerDocentes();
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al guardar');
      }).finally(() => {
        this.loading = false;
      });
    },
    eliminarDocente(id) {
      this.$alert.dialog('¿Desea eliminar este docente?').onOk(() => {
        this.loading = true;
        this.$axios.delete(`docentes/${id}`).then(() => {
          this.$alert.success('Docente eliminado');
          this.obtenerDocentes();
        }).catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al eliminar');
        }).finally(() => {
          this.loading = false;
        });
      });
    }
  }
};
</script>
