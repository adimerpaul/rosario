<template>
  <q-page class="joyas-page q-pa-md">
    <div class="joyas-shell">
      <q-card flat bordered class="joyas-table-card">
        <q-table
          :rows="joyas"
          :columns="columns"
          row-key="id"
          dense
          wrap-cells
          flat
          :rows-per-page-options="[12, 24, 48]"
          title="Joyas"
          :loading="loading"
        >
          <template v-slot:top>
            <div class="row items-center full-width q-col-gutter-sm">
              <div class="col-12 col-lg">
                <div class="text-h6 text-weight-bold">Inventario de joyas</div>
                <div class="text-caption text-grey-7">Administrador: alta, edicion, imagen y control de catalogo.</div>
              </div>
              <div class="col-12 col-md-auto">
                <div class="row q-gutter-sm">
                  <q-btn color="positive" label="Nueva joya" no-caps icon="add_circle_outline" :loading="loading" @click="joyaNueva" />
                  <q-btn color="primary" label="Actualizar" no-caps icon="refresh" :loading="loading" @click="joyasGet" />
                </div>
              </div>
              <div class="col-12 col-md-auto">
                <q-select
                  v-model="pagination.rowsPerPage"
                  :options="[12, 24, 48]"
                  label="Por pagina"
                  dense
                  outlined
                  style="min-width: 110px"
                  @update:model-value="changeRowsPerPage"
                />
              </div>
              <div class="col-12 col-md-5 col-lg-4 joyas-search-col">
                <q-input
                  v-model="filter"
                  label="Buscar por nombre, tipo, linea o estuche"
                  dense
                  outlined
                  clearable
                  debounce="400"
                  @update:model-value="onSearchChange"
                >
                  <template v-slot:append>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
            </div>
          </template>

          <template v-slot:bottom>
            <div class="row items-center justify-between full-width q-gutter-sm">
              <q-pagination
                v-model="pagination.page"
                :max="pagination.lastPage"
                max-pages="8"
                boundary-numbers
                direction-links
                @update:model-value="joyasGet"
              />
              <div class="text-caption text-grey-7">
                Mostrando {{ joyas.length }} de {{ pagination.rowsNumber }} joyas
              </div>
            </div>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
                <q-list>
                  <q-item clickable @click="joyaEditar(props.row)" v-close-popup>
                    <q-item-section avatar>
                      <q-icon name="edit" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Editar</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable @click="joyaEliminar(props.row)" v-close-popup>
                    <q-item-section avatar>
                      <q-icon name="delete" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Eliminar</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>

          <template v-slot:body-cell-imagen="props">
            <q-td :props="props">
              <q-avatar rounded size="60px">
                <q-img :src="imagenUrl(props.row.imagen)" />
              </q-avatar>
            </q-td>
          </template>

          <template v-slot:body-cell-tipo="props">
            <q-td :props="props">
              <q-chip dense text-color="white" :color="tipoColor(props.row.tipo)">
                {{ props.row.tipo }}
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-linea="props">
            <q-td :props="props">
              <q-chip dense outline color="primary">
                {{ props.row.linea }}
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-estuche="props">
            <q-td :props="props">
              {{ props.row.estuche_item?.columna?.vitrina?.nombre || '-' }} /
              {{ props.row.estuche_item?.columna?.codigo || '-' }} /
              {{ props.row.estuche || '-' }}
            </q-td>
          </template>

          <template v-slot:body-cell-monto_bs="props">
            <q-td :props="props">
              <span class="text-weight-bold text-primary">{{ money(props.row.monto_bs) }} Bs</span>
            </q-td>
          </template>

          <template v-slot:body-cell-vendido="props">
            <q-td :props="props">
              <q-chip dense :color="props.row.vendido ? 'negative' : 'positive'" text-color="white">
                {{ props.row.vendido ? 'Vendido' : 'Disponible' }}
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-user="props">
            <q-td :props="props">
              {{ props.row.user?.username || props.row.user?.name || '-' }}
            </q-td>
          </template>

          <template v-slot:body-cell-created_at="props">
            <q-td :props="props">
              {{ formatDate(props.row.created_at) }}
            </q-td>
          </template>
        </q-table>
      </q-card>
    </div>

    <q-dialog v-model="joyaDialog" persistent maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="joya-form-card">
        <q-card-section class="row items-center joya-form-header">
          <div>
            <div class="text-h6 text-weight-bold">{{ actionJoya }} joya</div>
            <div class="text-caption text-grey-7">Carga los datos principales, la ubicacion y la imagen del catalogo.</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense @click="cerrarDialogo" />
        </q-card-section>

        <q-separator />

        <q-card-section class="joya-form-body">
          <q-form @submit.prevent="guardarJoya">
            <div class="row q-col-gutter-lg">
              <div class="col-12 col-md-8">
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-md-6">
                    <q-input
                      v-model="joya.nombre"
                      label="Nombre"
                      dense
                      outlined
                      :rules="[val => !!val || 'Campo requerido']"
                      @update:model-value="joya.nombre = upper(joya.nombre)"
                    />
                  </div>
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="joya.tipo"
                      :options="tipos"
                      label="Tipo"
                      dense
                      outlined
                      :rules="[val => !!val || 'Campo requerido']"
                    />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model.number="joya.peso"
                      label="Peso (gr)"
                      type="number"
                      dense
                      outlined
                      min="0"
                      step="0.01"
                      :rules="[val => val >= 0 || 'Debe ser positivo']"
                    />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-select
                      v-model="joya.linea"
                      :options="lineas"
                      label="Linea"
                      dense
                      outlined
                      :rules="[val => !!val || 'Campo requerido']"
                    />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-input
                      v-model.number="joya.monto_bs"
                      label="Monto (Bs)"
                      type="number"
                      dense
                      outlined
                      min="0"
                      step="0.01"
                      :rules="[val => val >= 0 || 'Debe ser positivo']"
                    />
                  </div>
                  <div class="col-12">
                    <q-select
                      v-model="joya.estuche_id"
                      :options="estuchesDisponibles"
                      option-label="label"
                      option-value="id"
                      emit-value
                      map-options
                      label="Estuche"
                      clearable
                      dense
                      outlined
                    />
                  </div>
                  <div class="col-12 col-md-4">
                    <q-toggle
                      v-model="joya.vendido"
                      label="Joya vendida"
                      color="negative"
                      left-label
                    />
                  </div>
                </div>
              </div>

              <div class="col-12 col-md-4">
                <q-card flat bordered class="image-panel">
                  <q-card-section class="q-pb-sm">
                    <div class="text-subtitle1 text-weight-bold">Imagen</div>
                    <div class="text-caption text-grey-7">Vista previa de la joya.</div>
                  </q-card-section>
                  <q-card-section class="q-pt-none">
                    <div class="image-preview">
                      <q-img :src="imagenPreviewUrl" fit="cover" class="image-preview__img" />
                      <q-btn round color="primary" icon="edit" class="image-preview__edit" @click="$refs.fileInput.click()" />
                    </div>
                    <div class="q-mt-md text-caption text-grey-7">
                      {{ selectedImageFile ? selectedImageFile.name : 'Usando imagen por defecto: joya.png' }}
                    </div>
                    <div class="q-mt-sm">
                      <q-btn flat color="primary" no-caps icon="image" label="Cambiar imagen" @click="$refs.fileInput.click()" />
                    </div>
                    <input ref="fileInput" type="file" style="display: none;" accept="image/*" capture="environment" @change="onFileChange" />
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <div class="text-right q-mt-xl">
              <q-btn color="negative" label="Cancelar" no-caps :loading="loading" @click="cerrarDialogo" />
              <q-btn color="primary" label="Guardar" type="submit" no-caps class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'JoyasPage',
  data() {
    return {
      loading: false,
      filter: '',
      joyas: [],
      joyaDialog: false,
      actionJoya: 'Nueva',
      selectedImageFile: null,
      imagePreviewUrl: '',
      pagination: {
        page: 1,
        rowsPerPage: 12,
        rowsNumber: 0,
        lastPage: 1
      },
      estuches: [],
      tipos: ['Importada', 'Joya nacional', 'Plata'],
      lineas: ['Mama', 'Papa', 'Roger', 'Andreina'],
      joya: {
        nombre: '',
        tipo: 'Importada',
        peso: 0,
        linea: 'Mama',
        estuche_id: null,
        monto_bs: 0,
        vendido: false,
        imagen: 'joya.png'
      },
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'imagen', label: 'Imagen', align: 'left', field: 'imagen' },
        { name: 'nombre', label: 'Nombre', align: 'left', field: 'nombre' },
        { name: 'tipo', label: 'Tipo', align: 'left', field: 'tipo' },
        { name: 'peso', label: 'Peso (gr)', align: 'left', field: 'peso' },
        { name: 'linea', label: 'Linea', align: 'left', field: 'linea' },
        { name: 'estuche', label: 'Estuche', align: 'left', field: row => row.estuche },
        { name: 'user', label: 'Creado por', align: 'left', field: row => row.user?.username || row.user?.name || '-' },
        { name: 'created_at', label: 'Fecha', align: 'left', field: 'created_at' },
        { name: 'monto_bs', label: 'Monto', align: 'left', field: 'monto_bs' },
        { name: 'vendido', label: 'Estado venta', align: 'left', field: 'vendido' }
      ]
    }
  },
  computed: {
    imagenPreviewUrl() {
      return this.imagePreviewUrl || this.imagenUrl(this.joya.imagen)
    },
    estuchesDisponibles() {
      return this.estuches
    }
  },
  mounted() {
    this.getEstuches()
    this.joyasGet()
  },
  methods: {
    imagenUrl(imagen) {
      return `${this.$url}/../images/${imagen || 'joya.png'}`
    },
    upper(value) {
      return (value || '').toUpperCase()
    },
    money(value) {
      return Number(value || 0).toFixed(2)
    },
    formatDate(value) {
      if (!value) return '-'
      return new Date(value).toLocaleString()
    },
    tipoColor(tipo) {
      if (tipo === 'Importada') return 'deep-orange'
      if (tipo === 'Joya nacional') return 'brown'
      return 'blue-grey'
    },
    resetImageState() {
      this.selectedImageFile = null
      this.imagePreviewUrl = ''
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = null
      }
    },
    cerrarDialogo() {
      this.joyaDialog = false
      this.resetImageState()
    },
    getEstuches() {
      this.$axios.get('estuches/options')
        .then(res => {
          this.estuches = res.data
        })
        .catch(error => {
          this.$alert.error(error.response?.data?.message || 'Error al cargar estuches')
        })
    },
    joyaNueva() {
      this.actionJoya = 'Nueva'
      this.joya = {
        nombre: '',
        tipo: 'Importada',
        peso: 0,
        linea: 'Mama',
        estuche_id: null,
        monto_bs: 0,
        vendido: false,
        imagen: 'joya.png'
      }
      this.resetImageState()
      this.joyaDialog = true
    },
    joyaEditar(joya) {
      this.actionJoya = 'Editar'
      this.joya = {
        ...joya,
        estuche_id: joya.estuche_id || joya.estuche_item?.id || null,
        vendido: Boolean(joya.vendido),
        imagen: joya.imagen || 'joya.png'
      }
      this.resetImageState()
      this.joyaDialog = true
    },
    joyasGet() {
      this.loading = true
      this.$axios.get('joyas', {
        params: {
          page: this.pagination.page,
          per_page: this.pagination.rowsPerPage,
          search: this.filter || ''
        }
      })
        .then(res => {
          this.joyas = res.data.data
          this.pagination.page = res.data.current_page || 1
          this.pagination.rowsPerPage = res.data.per_page || this.pagination.rowsPerPage
          this.pagination.rowsNumber = res.data.total || 0
          this.pagination.lastPage = res.data.last_page || 1
        })
        .catch(error => {
          this.$alert.error(error.response?.data?.message || 'Error al cargar joyas')
        })
        .finally(() => {
          this.loading = false
        })
    },
    onSearchChange() {
      this.pagination.page = 1
      this.joyasGet()
    },
    async subirImagen(joyaId) {
      if (!this.selectedImageFile) {
        return
      }

      const formData = new FormData()
      formData.append('imagen', this.selectedImageFile)

      await this.$axios.post(`joyas/${joyaId}/imagen`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    },
    async guardarJoya() {
      this.loading = true
      try {
        let response

        if (this.joya.id) {
          response = await this.$axios.put(`joyas/${this.joya.id}`, this.joya)
        } else {
          response = await this.$axios.post('joyas', this.joya)
        }

        await this.subirImagen(response.data.id || this.joya.id)

        this.$alert.success(this.joya.id ? 'Joya actualizada' : 'Joya creada')
        this.cerrarDialogo()
        this.pagination.page = 1
        this.getEstuches()
        this.joyasGet()
      } catch (error) {
        this.$alert.error(error.response?.data?.message || 'Error al guardar joya')
      } finally {
        this.loading = false
      }
    },
    joyaEliminar(joya) {
      this.$alert.dialog(`¿Desea eliminar la joya ${joya.nombre}?`)
        .onOk(() => {
          this.loading = true
          this.$axios.delete(`joyas/${joya.id}`)
            .then(() => {
              this.$alert.success('Joya eliminada')
              this.getEstuches()
              this.joyasGet()
            })
            .catch(error => {
              this.$alert.error(error.response?.data?.message || 'Error al eliminar joya')
            })
            .finally(() => {
              this.loading = false
            })
        })
    },
    onFileChange(event) {
      const file = event.target.files[0]
      if (!file) {
        return
      }

      this.selectedImageFile = file

      const reader = new FileReader()
      reader.onload = e => {
        this.imagePreviewUrl = e.target?.result || ''
      }
      reader.readAsDataURL(file)
    },
    changeRowsPerPage() {
      this.pagination.page = 1
      this.joyasGet()
    }
  }
}
</script>

<style scoped>
.joyas-page {
  background:
    radial-gradient(circle at top right, rgba(227, 181, 89, 0.22), transparent 26%),
    linear-gradient(180deg, #f8f4ea 0%, #f1ede2 100%);
  min-height: 100%;
}

.joyas-shell {
  max-width: 1380px;
  margin: 0 auto;
}

.joyas-search-col {
  margin-left: auto;
}

.joyas-table-card {
  border-radius: 22px;
  box-shadow: 0 18px 55px rgba(110, 84, 32, 0.12);
}

.joya-form-card {
  background: linear-gradient(180deg, #fffdf8 0%, #f6f0e4 100%);
}

.joya-form-header {
  padding: 20px 24px;
}

.joya-form-body {
  padding: 24px;
}

.image-panel {
  border-radius: 20px;
  background: linear-gradient(180deg, #fff 0%, #faf3df 100%);
}

.image-preview {
  position: relative;
  border-radius: 20px;
  overflow: hidden;
  height: 320px;
  background: #efe7d4;
  box-shadow: inset 0 0 0 1px rgba(133, 101, 39, 0.08);
}

.image-preview__img {
  width: 100%;
  height: 100%;
}

.image-preview__edit {
  position: absolute;
  top: 12px;
  right: 12px;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
}
</style>
