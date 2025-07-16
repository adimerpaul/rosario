<template>
  <q-page class="q-pa-md">
    <q-table :rows="cursos" :columns="columns" flat bordered dense wrap-cells :rows-per-page-options="[0]" title="Cursos" :filter="filter">
      <template v-slot:top-right>
        <q-btn label="Nuevo" icon="add_circle_outline" color="primary" @click="nuevoCurso" :loading="loading" no-caps />
        <q-input v-model="filter" label="Buscar" dense outlined class="q-ml-sm">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
      </template>
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
<!--          <q-btn size="sm" flat icon="edit" color="primary" @click="editarCurso(props.row)" />-->
<!--          <q-btn size="sm" flat icon="delete" color="negative" @click="eliminarCurso(props.row.id)" />-->
          <q-btn-dropdown label="Acciones" color="primary" size="sm" no-caps>
            <q-list>
              <q-item clickable @click="editarCurso(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="edit" size="md" />
                </q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable @click="eliminarCurso(props.row.id)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="delete" size="md" />
                </q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
<!--              <q-item clickable @click="estudianteRoute(props.row.id)" v-close-popup>-->
<!--                <q-item-section avatar>-->
<!--                  <q-icon name="people" size="md" />-->
<!--                </q-item-section>-->
<!--                <q-item-section>Estudiantes</q-item-section>-->
<!--              </q-item>-->
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
      <template v-slot:body-cell-icono="props">
        <q-td :props="props">
          <q-icon :name="props.row.icono" size="md" />
        </q-td>
      </template>
    </q-table>
    <q-dialog v-model="dialog" persistent>
      <q-card style="width: 400px">
        <q-card-section>
          <div class="text-h6">{{ curso.id ? 'Editar' : 'Nuevo' }} Curso</div>
        </q-card-section>
        <q-card-section>
          <q-form @submit="guardarCurso">
            <q-input v-model="curso.nombre" label="Nombre" dense outlined class="q-mb-sm" :rules="[v => !!v || 'Requerido']" />
            <q-input v-model="curso.descripcion" label="Descripción" dense outlined type="textarea" hint="" />
            <q-select
              v-model="curso.icono"
              :options="iconos"
              label="Icono"
              dense
              outlined
              option-label="label"
              option-value="icon"
              emit-value
              map-options
              use-input
              fill-input
              input-debounce="0"
              :rules="[v => !!v || 'Requerido']"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-icon :name="scope.opt.icon" />
<!--                    <q-icon :name="'fa-solid fa-flask-vial'" />-->
                  </q-item-section>
                  <q-item-section>{{ scope.opt.label }}</q-item-section>
                </q-item>
              </template>
              <template v-slot:selected-item="scope">
<!--                <q-item>-->
<!--                  <q-item-section avatar>-->
                    <q-icon :name="scope.opt.icon" />
<!--                  </q-item-section>-->
<!--&lt;!&ndash;                  <q-item-section>{{ scope.opt.label }}</q-item-section>&ndash;&gt;-->
<!--                </q-item>-->
              </template>
            </q-select>
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
  name: 'CursosPage',
  data() {
    return {
      cursos: [],
      curso: {},
      dialog: false,
      loading: false,
      filter: '',
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
        { name: 'descripcion', label: 'Descripción', field: 'descripcion', align: 'left' },
        { name: 'icono', label: 'Icono', field: 'icono', align: 'center' },
        { name: 'formacion', label: 'Formación', field: 'formacion', align: 'left' },
      ],
      iconos:[
        { label: 'Quimica', icon: 'fa-solid fa-flask-vial' },
        { label: 'Matemáticas', icon: 'fa-solid fa-book-open-reader' },
        { label: 'Física', icon: 'fa-solid fa-chalkboard-user' },
        { label: 'Biología', icon: 'fa-solid fa-graduation-cap' },
        { label: 'Programación', icon: 'fa-solid fa-laptop-code' },
        { label: 'Robótica', icon: 'fa-solid fa-robot' },
        { label: 'Redes', icon: 'fa-solid fa-network-wired' },
        { label: 'Base de Datos', icon: 'fa-solid fa-database' },
        { label: 'Lenguajes', icon: 'fa-solid fa-language' },
        { label: 'Historia', icon: 'fa-solid fa-landmark' },
        { label: 'Geografía', icon: 'fa-solid fa-globe' },
        { label: 'Arte', icon: 'fa-solid fa-paintbrush' },
        { label: 'Música', icon: 'fa-solid fa-music' },
        { label: 'Educación Física', icon: 'fa-solid fa-running' },
        { label: 'Literatura', icon: 'fa-solid fa-book' },
        { label: 'Ciencias Sociales', icon: 'fa-solid fa-users' },
        { label: 'Ciencias Naturales', icon: 'fa-solid fa-leaf' },
        { label: 'Computación', icon: 'fa-solid fa-desktop' },
        { label: 'Aymara', icon: 'fa-solid fa-comments' },
        { label: 'Lenguaje', icon: 'fa-solid fa-comment-dots' },
        { label: 'Quechua', icon: 'fa-solid fa-comments' },
        { label: 'Otros', icon: 'fa-solid fa-ellipsis' }
      ]
    };
  },
  mounted() {
    this.obtenerCursos();
  },
  methods: {
    obtenerCursos() {
      this.loading = true;
      this.$axios.get('cursos').then(res => {
        this.cursos = res.data;
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al obtener cursos');
      }).finally(() => {
        this.loading = false;
      });
    },
    nuevoCurso() {
      this.curso = {};
      this.dialog = true;
    },
    editarCurso(curso) {
      this.curso = { ...curso };
      this.dialog = true;
    },
    guardarCurso() {
      this.loading = true;
      this.curso.tipo = this.iconos.find(icon => icon.icon === this.curso.icono)?.label || 'Otros';
      const req = this.curso.id
        ? this.$axios.put(`cursos/${this.curso.id}`, this.curso)
        : this.$axios.post('cursos', this.curso);
      req.then(() => {
        this.$alert.success('Curso guardado');
        this.dialog = false;
        this.obtenerCursos();
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al guardar');
      }).finally(() => {
        this.loading = false;
      });
    },
    // estudianteRoute(id) {
    //   this.$router.push(`/estudiantes/${id}`);
    // },
    eliminarCurso(id) {
      this.$alert.dialog('¿Desea eliminar este curso?').onOk(() => {
        this.loading = true;
        this.$axios.delete(`cursos/${id}`).then(() => {
          this.$alert.success('Curso eliminado');
          this.obtenerCursos();
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
