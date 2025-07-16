<template>
  <q-page class="q-pa-md">
    <q-table
      :rows="asignaciones"
      :columns="columns"
      flat
      bordered
      dense
      wrap-cells
      :rows-per-page-options="[0]"
      title="Asignaciones"
      :filter="filter"
    >
      <template v-slot:top-right>
        <q-btn label="Nueva" icon="add_circle_outline" color="primary" @click="nuevaAsignacion" :loading="loading" no-caps class="q-mr-md" />
        <q-btn icon="refresh" color="secondary" @click="obtenerAsignaciones" :loading="loading" no-caps />
        <q-input v-model="filter" label="Buscar" dense outlined class="q-ml-sm">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Acciones" color="primary" size="sm" no-caps>
            <q-list>
              <q-item clickable @click="editarAsignacion(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable @click="abrirAsignacionEstudiantes(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="group_add" /></q-item-section>
                <q-item-section>Agregar Estudiantes</q-item-section>
              </q-item>
              <q-item clickable @click="eliminarAsignacion(props.row.id)" v-close-popup>
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>
<!--    <pre>{{asignaciones}}</pre>-->

    <q-dialog v-model="dialog" persistent>
      <q-card style="width: 500px">
        <q-card-section>
          <div class="text-h6">{{ asignacion.id ? 'Editar' : 'Nueva' }} Asignación</div>
        </q-card-section>
        <q-card-section>
          <q-form @submit="guardarAsignacion">
<!--            <q-select v-model="asignacion.user_id" :options="usuarios" label="Usuario" option-label="name" option-value="id" outlined dense />-->
            <q-select v-model="asignacion.curso_id" :options="cursos" label="Curso" option-label="nombre" option-value="id" outlined dense hint=""
                      emit-value map-options
            />
            <q-select v-model="asignacion.docente_id" :options="docentes" label="Docente" option-label="nombre" option-value="id" outlined dense hint=""
                      emit-value map-options
            />
            <q-input v-model="asignacion.unidadEducativa" label="Unidad Educativa" dense outlined class="q-mb-sm" />
            <q-input v-model="asignacion.taller" label="Taller" dense outlined class="q-mb-sm" />
            <q-input v-model="asignacion.fases" label="Fases" dense outlined class="q-mb-sm" />
            <q-input v-model="asignacion.docentesEncargados" label="Docentes Encargados" dense outlined class="q-mb-sm" />
            <q-input v-model="asignacion.anioFormacion" label="Año de Formación" dense outlined class="q-mb-sm" />
<!--            <q-input v-model="asignacion.gestion" label="Gestión" dense outlined class="q-mb-sm" />-->
            <q-select v-model="asignacion.gestion" :options="gestiones" label="Gestión" option-label="label" option-value="label" outlined dense class="q-mb-sm"
                      emit-value map-options
            />

            <div class="text-right">
              <q-btn flat label="Cancelar" color="negative" v-close-popup />
              <q-btn label="Guardar" type="submit" color="primary" class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="dialogEstudiantes" persistent full-width full-height>
      <q-card style="width: 500px">
        <q-card-section class="row items-center">
          <div class="text-bold">Estudiantes de {{ asignacion.curso?.nombre }}</div>
          <q-space/>
          <q-btn icon="close" flat class="q-ml-auto" v-close-popup />
        </q-card-section>
        <q-card-section>
          <div class="row">
            <div class="col-12 col-md-6">
              <q-input v-model="estudianteFilter" label="Buscar Estudiante" dense outlined class="q-mb-sm" @update:model-value="filtrarEstudiantes">
                <template v-slot:append><q-icon name="search" /></template>
              </q-input>
              <q-markup-table bordered flat dense wrap-cells>
                <thead>
                <tr class="bg-primary text-white">
                  <th class="text-left">#</th>
                  <th class="text-left">Nombre</th>
                  <th class="text-left">Agregar</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(estudiante,i) in estudiantes" :key="estudiante.id">
                  <td>{{ i + 1 }}</td>
                  <td>{{ estudiante.nombre }}</td>
                  <td>
                    <q-btn
                      icon="add_circle_outline"
                      @click="agregarEstudiante(estudiante)"
                      :loading="loading"
                      color="primary" size="xs" dense label="Agregar" no-caps/>
                  </td>
                </tr>
                </tbody>
              </q-markup-table>
            </div>
            <div class="col-12 col-md-6">
              <q-markup-table bordered flat dense wrap-cells>
                <thead>
                <tr class="bg-grey-3">
                  <th class="text-left">#</th>
                  <th class="text-left">Nombre</th>
                  <th class="text-left">Quitar</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(estudiante, i) in estudiantesSeleccionados" :key="estudiante.id">
                  <td>{{ i + 1 }}</td>
                  <td>{{ estudiante.nombre }}</td>
                  <td>
                    <q-btn
                      icon="remove_circle_outline"
                      color="negative"
                      size="xs"
                      dense
                      @click="quitarEstudiante(estudiante.id)"
                      label="Quitar"
                      no-caps
                      :loading="loading"
                    />
                  </td>
                </tr>
                </tbody>
              </q-markup-table>
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="negative" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'AsignacionesPage',
  data() {
    return {
      asignaciones: [],
      asignacion: {},
      dialog: false,
      loading: false,
      filter: '',
      // usuarios: [],
      docentes: [],
      cursos: [],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'docente_id', label: 'Docente', field: row => row.docente?.nombre, align: 'left' },
        { name: 'curso_id', label: 'Curso', field: row => row.curso?.nombre, align: 'left' },
        { name: 'unidadEducativa', label: 'Unidad Educativa', field: 'unidadEducativa', align: 'left' },
        { name: 'taller', label: 'Taller', field: 'taller', align: 'left' },
        { name: 'fases', label: 'Fases', field: 'fases', align: 'left' },
        { name: 'docentesEncargados', label: 'Docentes Encargados', field: 'docentesEncargados', align: 'left' },
        { name: 'anioFormacion', label: 'Año de Formación', field: 'anioFormacion', align: 'left' },
        { name: 'gestion', label: 'Gestión', field: 'gestion', align: 'left' },
        { name: 'user_id', label: 'Usuario', field: row => row.user?.name, align: 'left' }
      ],
      gestiones: [],
      estudiantes: [],
      estudiantesAll: [],
      estudianteFilter: '',
      estudiantesSeleccionados: [],
      dialogEstudiantes: false,
    }
  },
  async mounted() {
    this.obtenerAsignaciones();
    this.$axios.get('cursos').then(res => this.cursos = res.data)
    this.$axios.get('docentes').then(res => this.docentes = res.data)
    await this.$axios.get('estudiantes').then(res => this.estudiantes = res.data)
    this.estudiantesAll = [...this.estudiantes];
    const currentYear = new Date().getFullYear();
    for (let i = currentYear - 3; i <= currentYear + 3; i++) {
      this.gestiones.push({ label: i.toString(), value: i })
    }
  },
  methods: {
    agregarEstudiante(estudiante) {
      this.loading=true
      this.$axios.post(`asignacion-estudiantes`, {
        estudiante_id: estudiante.id,
        asignacion_id: this.asignacion.id
      })
        .then(() => {
          this.$alert.success('Estudiante agregado a la asignación')
          this.estudiantesSeleccionados.push(estudiante)
          this.filtrarEstudiantes()
        })
        .catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al agregar estudiante')
        })
        .finally(() => {
          this.loading = false
        })
    },
    filtrarEstudiantes() {
      if (this.estudianteFilter === '') {
        this.estudiantes = [...this.estudiantesAll];
      } else {
        this.estudiantes = this.estudiantesAll.filter(estudiante =>
          estudiante.nombre.toLowerCase().includes(this.estudianteFilter.toLowerCase())
        );
      }
    },
    quitarEstudiante(estudiante_id) {
      this.loading = true;

      const relacion = this.asignacion.estudiantes?.find(e => e.id === estudiante_id || e.estudiante_id === estudiante_id);

      if (!relacion) {
        this.$alert.error('No se encontró el ID de la relación para eliminar');
        this.loading = false;
        return;
      }

      this.$axios.delete(`asignacion-estudiantes-by-id/${relacion.pivot?.id || relacion.id}`)
        .then(() => {
          this.$alert.success('Estudiante eliminado');
          this.estudiantesSeleccionados = this.estudiantesSeleccionados.filter(e => e.id !== estudiante_id);
          this.filtrarEstudiantes();
        })
        .catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al quitar estudiante');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    abrirAsignacionEstudiantes(asignacion) {
      this.estudiantesSeleccionados = asignacion.estudiantes || [];
      this.dialogEstudiantes = true;
      this.asignacion = asignacion;
    },
    obtenerAsignaciones() {
      this.loading = true
      this.$axios.get('asignaciones').then(res => {
        this.asignaciones = res.data
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al obtener asignaciones')
      }).finally(() => {
        this.loading = false
      })
    },
    nuevaAsignacion() {
      this.asignacion = {}
      this.dialog = true
    },
    editarAsignacion(asignacion) {
      this.asignacion = { ...asignacion }
      this.dialog = true
    },
    guardarAsignacion() {
      this.loading = true
      const req = this.asignacion.id
        ? this.$axios.put(`asignaciones/${this.asignacion.id}`, this.asignacion)
        : this.$axios.post('asignaciones', this.asignacion)
      req.then(() => {
        this.$alert.success('Asignación guardada')
        this.dialog = false
        this.obtenerAsignaciones()
      }).catch(err => {
        this.$alert.error(err.response?.data?.message || 'Error al guardar')
      }).finally(() => {
        this.loading = false
      })
    },
    eliminarAsignacion(id) {
      this.$alert.dialog('¿Desea eliminar esta asignación?').onOk(() => {
        this.loading = true
        this.$axios.delete(`asignaciones/${id}`).then(() => {
          this.$alert.success('Asignación eliminada')
          this.obtenerAsignaciones()
        }).catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al eliminar')
        }).finally(() => {
          this.loading = false
        })
      })
    }
  }
}
</script>
