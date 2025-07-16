<template>
  <q-page class="q-pa-md">
    <div class="text-h5 text-bold text-primary q-mb-md">Detalle del Curso</div>

    <div v-if="loading" class="flex flex-center">
      <q-spinner-dots color="primary" size="40px" />
    </div>

    <div v-else>
      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <div class="text-h6">{{ asignacion.curso.nombre }}</div>
          <div class="text-subtitle2">{{ asignacion.curso.descripcion }}</div>
          <div class="q-mt-sm">
            <q-badge color="orange" outline class="q-mr-sm">Formación: {{ asignacion.curso.formacion }}</q-badge>
            <q-badge color="teal" outline>Tipo: {{ asignacion.curso.tipo }}</q-badge>
          </div>
          <div class="text-caption q-mt-sm">
            Unidad Educativa: <strong>{{ asignacion.unidadEducativa }}</strong> |
            Gestión: <strong>{{ asignacion.gestion }}</strong> |
            Año: <strong>{{ asignacion.anioFormacion }}</strong>
          </div>
        </q-card-section>
      </q-card>

      <q-table
        :rows="asignacion.estudiantes"
        :columns="columns"
        row-key="id"
        title="Lista de Estudiantes"
        flat
        bordered
        dense
        :rows-per-page-options="[0]"
      >
<!--        <template v-slot:body-cell-asistencia="props">-->
<!--          <q-td :props="props">-->
<!--            <q-select-->
<!--              outlined-->
<!--              dense-->
<!--              emit-value-->
<!--              map-options-->
<!--              v-model="asistencias[props.row.id]"-->
<!--              :options="[-->
<!--                { label: 'Presente', value: 'Presente' },-->
<!--                { label: 'Ausente', value: 'Ausente' },-->
<!--                { label: 'Tarde', value: 'Tarde' }-->
<!--              ]"-->
<!--              @input="guardarAsistencia(props.row.id)"-->
<!--              placeholder="Seleccionar"-->
<!--              :disable="loadingAsistencia"-->
<!--            />-->
<!--          </q-td>-->
<!--        </template>-->
      </q-table>
    </div>
  </q-page>
</template>

<script>
export default {
  name: 'DetalleCursoPage',
  data () {
    return {
      asignacion: null,
      loading: true,
      loadingAsistencia: false,
      asistencias: {}, // { estudiante_id: 'Presente' }
      columns: [
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
        { name: 'ci', label: 'CI', field: 'ci', align: 'left' },
        // { name: 'asistencia', label: 'Asistencia', align: 'center' }
      ]
    }
  },
  mounted () {
    this.obtenerDetalle()
  },
  methods: {
    obtenerDetalle () {
      this.loading = true
      this.$axios.get(`/asignaciones/${this.$route.params.id}`)
        .then(res => {
          this.asignacion = res.data
          // Opcional: precargar asistencias si ya existen
          res.data.estudiantes.forEach(e => {
            this.asistencias[e.id] = null
          })
        })
        .catch(() => {
          this.$alert.error('Error al obtener datos del curso')
        })
        .finally(() => {
          this.loading = false
        })
    },
    guardarAsistencia (estudianteId) {
      const valor = this.asistencias[estudianteId]
      if (!valor) return

      this.loadingAsistencia = true
      this.$axios.post('/asistencias', {
        asignacion_id: this.$route.params.id,
        estudiante_id: estudianteId,
        asistencia: valor
      }).then(() => {
        this.$alert.success('Asistencia registrada')
      }).catch(() => {
        this.$alert.error('Error al registrar asistencia')
      }).finally(() => {
        this.loadingAsistencia = false
      })
    }
  }
}
</script>
