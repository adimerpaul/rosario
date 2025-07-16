<template>
  <q-page class="q-pa-md">
    <div class="text-h5 text-primary text-bold q-mb-md">
      Mis Cursos Asignados
<!--      boton actulizar-->
      <q-btn
        icon="refresh"
        color="primary"
        flat
        round
        @click="obtenerMisCursos"
        class="q-ml-sm"
        :loading="loading"
        aria-label="Actualizar cursos"
      />
    </div>

    <div v-if="loading" class="q-pa-md flex flex-center">
      <q-spinner-dots color="primary" size="40px" />
    </div>

    <div v-else class="row">
      <q-card
        v-for="asignacion in cursos"
        :key="asignacion.id"
        @click="verCurso(asignacion.id)"
        class="cursor-pointer col-xs-12 col-sm-6 col-md-4 col-lg-3 bg-white shadow-2"
        flat
        bordered
      >
        <q-item>
          <q-item-section avatar>
            <q-icon :name="asignacion.curso?.icono || 'fa-solid fa-book'" size="32px" color="primary" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1 text-bold text-primary">
              {{ asignacion.curso?.nombre }}
            </q-item-label>
            <q-item-label caption>{{ asignacion.curso?.descripcion }}</q-item-label>
          </q-item-section>
        </q-item>

        <q-separator />

        <q-card-section>
          <q-badge color="orange-6" outline class="q-mr-sm">
            Formación: {{ asignacion.curso?.formacion }}
          </q-badge>
          <q-badge color="teal" outline>
            Tipo: {{ asignacion.curso?.tipo }}
          </q-badge>
          <div class="text-caption q-mt-sm">
            Unidad Educativa: <strong>{{ asignacion.unidadEducativa }}</strong><br>
            Gestión: <strong>{{ asignacion.gestion }}</strong><br>
            Año de Formación: <strong>{{ asignacion.anioFormacion }}</strong>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Para depuración -->
<!--    <div class="q-mt-md">-->
<!--      <pre>{{ cursos }}</pre>-->
<!--    </div>-->
  </q-page>
</template>

<script>
export default {
  name: 'MisCursosPage',
  data () {
    return {
      cursos: [],
      loading: true
    }
  },
  mounted () {
    this.obtenerMisCursos()
  },
  methods: {
    verCurso(id) {
      this.$router.push('/curso/' + id)
    },
    obtenerMisCursos () {
      this.loading = true
      this.$axios.get('/misCursos')
        .then(res => {
          this.cursos = res.data
        })
        .catch(err => {
          this.$alert.error(err.response?.data?.message || 'Error al obtener cursos asignados')
        })
        .finally(() => {
          this.loading = false
        })
    }
  }
}
</script>
