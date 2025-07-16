<template>
  <q-page class="q-pa-md">
    <q-table :rows="estudiantes" :columns="columns" flat bordered dense wrap-cells :rows-per-page-options="[0]" title="Estudiantes" :filter="filter">
      <template v-slot:top-right>
        <q-btn label="Nuevo" icon="add_circle_outline" color="primary" @click="nuevoEstudiante" :loading="loading" no-caps />
        <q-input v-model="filter" label="Buscar" dense outlined class="q-ml-sm">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Acciones" color="primary" size="sm" no-caps>
            <q-list>
              <q-item clickable @click="editarEstudiante(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable @click="eliminarEstudiante(props.row.id)" v-close-popup>
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
          <div class="text-h6">{{ estudiante.id ? 'Editar' : 'Nuevo' }} Estudiante</div>
        </q-card-section>
        <q-card-section>
          <q-form @submit="guardarEstudiante">
            <q-input v-model="estudiante.nombre" label="Nombre" dense outlined class="q-mb-sm" :rules="[v => !!v || 'Requerido']" />
            <q-input v-model="estudiante.ci" label="CI" dense outlined class="q-mb-sm" :rules="[v => !!v || 'Requerido']" />
            <q-input v-model="estudiante.email" label="Email" dense outlined class="q-mb-sm" type="email" />
            <q-input v-model="estudiante.telefono" label="Teléfono" dense outlined class="q-mb-sm" />
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
  name: 'EstudiantesPage',
  data() {
    return {
      estudiantes: [],
      estudiante: {},
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
    this.obtenerEstudiantes();
  },
  methods: {
    obtenerEstudiantes() {
      this.loading = true;
      this.$axios.get('estudiantes').then(res => {
        this.estudiantes = res.data;
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al obtener estudiantes');
      }).finally(() => {
        this.loading = false;
      });
    },
    nuevoEstudiante() {
      this.estudiante = {};
      this.dialog = true;
    },
    editarEstudiante(estudiante) {
      this.estudiante = { ...estudiante };
      this.dialog = true;
    },
    guardarEstudiante() {
      this.loading = true;
      const req = this.estudiante.id
        ? this.$axios.put(`estudiantes/${this.estudiante.id}`, this.estudiante)
        : this.$axios.post('estudiantes', this.estudiante);
      req.then(() => {
        this.$alert.success('Estudiante guardado');
        this.dialog = false;
        this.obtenerEstudiantes();
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al guardar');
      }).finally(() => {
        this.loading = false;
      });
    },
    eliminarEstudiante(id) {
      this.$alert.dialog('¿Desea eliminar este estudiante?').onOk(() => {
        this.loading = true;
        this.$axios.delete(`estudiantes/${id}`).then(() => {
          this.$alert.success('Estudiante eliminado');
          this.obtenerEstudiantes();
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
