<template>
  <q-page class="estuches-page q-pa-md">
    <div class="estuches-shell">
      <q-card flat bordered class="estuches-card">
        <q-card-section class="row items-center q-col-gutter-md">
          <div class="col-12 col-md">
            <div class="text-h5 text-weight-bold">Estuches y vitrinas</div>
            <div class="text-caption text-grey-7">Vista compacta de vitrinas, columnas, estuches y joyas.</div>
          </div>
          <div class="col-12 col-md-auto">
            <div class="row q-gutter-sm justify-end">
              <q-btn color="primary" label="Actualizar" no-caps icon="refresh" :loading="loading" @click="getVitrinas" />
              <q-btn v-if="isAdmin" color="positive" label="Nueva vitrina" no-caps icon="add" :loading="loading" @click="openVitrinaDialog()" />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section v-if="vitrinas.length" class="q-pa-sm q-pa-md-md">
          <q-tabs v-model="selectedVitrinaId" inline-label active-color="primary" indicator-color="primary" dense class="text-weight-bold">
            <q-tab v-for="vitrina in vitrinas" :key="vitrina.id" :name="vitrina.id" :label="vitrina.nombre" />
          </q-tabs>

          <q-separator class="q-my-sm" />

          <div v-if="selectedVitrina" class="vitrina-board">
            <div class="vitrina-board__header">
              <div>
                <div class="text-h6 text-weight-bold">{{ selectedVitrina.nombre }}</div>
                <div class="text-caption text-grey-7">{{ selectedVitrina.columnas?.length || 0 }} columnas</div>
              </div>
              <div class="row q-gutter-xs">
                <q-btn v-if="isAdmin" flat dense color="primary" no-caps icon="edit" label="Editar" @click="openVitrinaDialog(selectedVitrina)" />
                <q-btn v-if="isAdmin" flat dense color="positive" no-caps icon="view_column" label="Columna" @click="openColumnaDialog(selectedVitrina)" />
                <q-btn v-if="isAdmin" flat dense color="negative" no-caps icon="delete" label="Eliminar" @click="deleteVitrina(selectedVitrina)" />
              </div>
            </div>

            <div class="column-grid">
              <q-card v-for="columna in selectedVitrina.columnas" :key="columna.id" flat bordered class="column-card">
                <q-card-section class="column-card__header q-pa-sm">
                  <div>
                    <div class="text-subtitle2 text-weight-bold">{{ columna.codigo }}</div>
                    <div class="text-caption text-grey-7">Orden {{ columna.orden }}</div>
                  </div>
                  <div class="row q-gutter-xs">
                    <q-btn v-if="isAdmin" flat round dense size="sm" icon="edit" color="primary" @click="openColumnaDialog(selectedVitrina, columna)" />
                    <q-btn v-if="isAdmin" flat round dense size="sm" icon="add_box" color="positive" @click="openEstucheDialog(columna)" />
                    <q-btn v-if="isAdmin && !columna.estuches?.length" flat round dense size="sm" icon="delete" color="negative" @click="deleteColumna(columna)" />
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-section class="column-card__body q-pa-sm">
                  <div v-if="columna.estuches?.length" class="estuches-list">
                    <q-card v-for="estuche in columna.estuches" :key="estuche.id" flat bordered class="estuche-card">
                      <q-card-section class="row items-center no-wrap q-pa-sm">
                        <div class="col">
                          <div class="text-body2 text-weight-bold">{{ estuche.nombre }}</div>
                          <div class="text-caption text-grey-7">{{ estuche.joyas?.length || 0 }} joya(s)</div>
                        </div>
                        <div class="row q-gutter-xs">
                          <q-btn v-if="isAdmin" flat round dense size="sm" icon="add" color="positive" @click="openAgregarJoyaDialog(estuche)" />
                          <q-btn v-if="isAdmin" flat round dense size="sm" icon="edit" color="primary" @click="openEstucheDialog(columna, estuche)" />
                          <q-btn v-if="isAdmin && !(estuche.joyas?.length)" flat round dense size="sm" icon="delete" color="negative" @click="deleteEstuche(estuche)" />
                        </div>
                      </q-card-section>

                      <q-separator />

                      <q-card-section class="q-pa-xs">
                        <div v-if="estuche.joyas?.length" class="joyas-grid">
                          <div v-for="joya in estuche.joyas" :key="joya.id" class="joya-mini">
                            <div class="joya-mini__actions" v-if="isAdmin">
                              <q-btn flat round dense size="xs" icon="edit" color="primary" @click="openEditarJoyaDialog(joya)" />
                              <q-btn flat round dense size="xs" icon="close" color="negative" @click="quitarJoyaDeEstuche(joya)" />
                            </div>
                            <q-img :src="imagenUrl(joya.imagen)" class="joya-mini__img" fit="cover" />
                            <div class="joya-mini__text">
                              <div class="joya-mini__name">{{ joya.nombre }}</div>
                              <div class="joya-mini__meta">{{ money(joya.monto_bs) }} Bs</div>
                              <q-chip dense square :color="colorEstadoJoya(joya)" text-color="white" class="q-mt-xs">
                                {{ estadoJoya(joya) }}
                              </q-chip>
                            </div>
                          </div>
                        </div>
                        <div v-else class="empty-slot compact">
                          <q-icon name="inventory_2" size="20px" color="grey-5" />
                          <div class="text-caption text-grey-6">Sin joyas</div>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                  <div v-else class="empty-slot compact">
                    <q-icon name="disabled_visible" size="22px" color="grey-5" />
                    <div class="text-caption text-grey-6">Sin estuches</div>
                    <q-btn
                      v-if="isAdmin"
                      class="q-mt-xs"
                      flat
                      dense
                      color="positive"
                      no-caps
                      icon="add_box"
                      label="Crear estuche"
                      @click="openEstucheDialog(columna)"
                    />
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-card-section>

        <q-card-section v-else class="text-center q-py-xl">
          <q-icon name="inventory_2" size="44px" color="grey-5" />
          <div class="text-subtitle1 q-mt-sm">No hay vitrinas registradas</div>
        </q-card-section>
      </q-card>
    </div>

    <q-dialog v-model="vitrinaDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ vitrinaForm.id ? 'Editar vitrina' : 'Nueva vitrina' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="vitrinaDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="saveVitrina">
            <q-input v-model="vitrinaForm.nombre" label="Nombre" outlined dense :rules="[val => !!val || 'Campo requerido']" />
            <q-input v-model.number="vitrinaForm.orden" label="Orden" type="number" outlined dense class="q-mt-sm" :rules="[val => val > 0 || 'Debe ser positivo']" />
            <div class="text-right q-mt-md">
              <q-btn flat color="negative" label="Cancelar" no-caps @click="vitrinaDialog = false" :loading="loading" />
              <q-btn color="primary" label="Guardar" type="submit" no-caps class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="columnaDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ columnaForm.id ? 'Editar columna' : 'Nueva columna' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="columnaDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="saveColumna">
            <q-input v-model="columnaForm.codigo" label="Código" outlined dense :rules="[val => !!val || 'Campo requerido']" />
            <q-input v-model.number="columnaForm.orden" label="Orden" type="number" outlined dense class="q-mt-sm" :rules="[val => val > 0 || 'Debe ser positivo']" />
            <div class="text-right q-mt-md">
              <q-btn flat color="negative" label="Cancelar" no-caps @click="columnaDialog = false" :loading="loading" />
              <q-btn color="primary" label="Guardar" type="submit" no-caps class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="estucheDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ estucheForm.id ? 'Editar estuche' : 'Nuevo estuche' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="estucheDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="saveEstuche">
            <q-input
              v-model="estucheForm.nombre"
              label="Nombre"
              outlined
              dense
              :rules="[val => !!val || 'Campo requerido']"
              @update:model-value="estucheForm.nombre = upper(estucheForm.nombre)"
            />
            <q-input v-model.number="estucheForm.orden" label="Orden" type="number" outlined dense class="q-mt-sm" :rules="[val => val > 0 || 'Debe ser positivo']" />
            <div class="text-right q-mt-md">
              <q-btn flat color="negative" label="Cancelar" no-caps @click="estucheDialog = false" :loading="loading" />
              <q-btn color="primary" label="Guardar" type="submit" no-caps class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="agregarJoyaDialog" persistent>
      <q-card style="width: 460px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Agregar joya</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="agregarJoyaDialog = false" />
        </q-card-section>
        <q-card-section>
          <div class="text-caption text-grey-7 q-mb-sm">
            Estuche destino: <span class="text-weight-bold">{{ selectedEstuche?.nombre }}</span>
          </div>
          <q-select
            v-model="joyaSeleccionadaId"
            :options="joyasSinEstuche"
            option-label="nombre"
            option-value="id"
            emit-value
            map-options
            label="Joyas sin estuche"
            outlined
            dense
            use-input
            fill-input
            hide-selected
            input-debounce="0"
          >
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section avatar>
                  <q-avatar rounded size="38px">
                    <q-img :src="imagenUrl(scope.opt.imagen)" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                  <q-item-label caption>{{ scope.opt.tipo }} • {{ money(scope.opt.monto_bs) }} Bs</q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-select>
          <div class="text-right q-mt-md">
            <q-btn flat color="negative" label="Cancelar" no-caps @click="agregarJoyaDialog = false" :loading="loading" />
            <q-btn color="primary" label="Agregar" no-caps class="q-ml-sm" :loading="loading" @click="agregarJoyaAEstuche" />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="joyaDialog" persistent>
      <q-card style="width: 460px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Editar joya</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="joyaDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="saveJoya">
            <div class="row q-col-gutter-sm">
              <div class="col-12">
                <q-input v-model="joyaForm.nombre" label="Nombre" outlined dense @update:model-value="joyaForm.nombre = upper(joyaForm.nombre)" />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="joyaForm.tipo" :options="tipos" label="Tipo" outlined dense />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="joyaForm.linea" :options="lineas" label="Linea" outlined dense />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model.number="joyaForm.peso" label="Peso (gr)" type="number" min="0" step="0.01" outlined dense />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model.number="joyaForm.monto_bs" label="Monto (Bs)" type="number" min="0" step="0.01" outlined dense />
              </div>
            </div>
            <div class="text-right q-mt-md">
              <q-btn flat color="negative" label="Cancelar" no-caps @click="joyaDialog = false" :loading="loading" />
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
  name: 'EstuchesPage',
  data() {
    return {
      loading: false,
      vitrinas: [],
      selectedVitrinaId: null,
      vitrinaDialog: false,
      columnaDialog: false,
      estucheDialog: false,
      joyaDialog: false,
      agregarJoyaDialog: false,
      joyasSinEstuche: [],
      selectedEstuche: null,
      joyaSeleccionadaId: null,
      joyaForm: {
        id: null,
        nombre: '',
        tipo: 'Importada',
        peso: 0,
        linea: 'Mama',
        monto_bs: 0
      },
      tipos: ['Importada', 'Joya nacional', 'Plata'],
      lineas: ['Mama', 'Papa', 'Roger', 'Andreina'],
      vitrinaForm: {
        id: null,
        nombre: '',
        orden: 1
      },
      columnaForm: {
        id: null,
        vitrina_id: null,
        codigo: '',
        orden: 1
      },
      estucheForm: {
        id: null,
        vitrina_columna_id: null,
        nombre: '',
        orden: 1
      }
    }
  },
  computed: {
    isAdmin() {
      return this.$store.user.role === 'Administrador'
    },
    selectedVitrina() {
      return this.vitrinas.find(vitrina => vitrina.id === this.selectedVitrinaId) || null
    }
  },
  mounted() {
    this.getVitrinas()
  },
  methods: {
    upper(value) {
      return (value || '').toUpperCase()
    },
    money(value) {
      return Number(value || 0).toFixed(2)
    },
    imagenUrl(imagen) {
      return `${this.$url}/../images/${imagen || 'joya.png'}`
    },
    latestVenta(joya) {
      const ventas = [...(joya?.ventas || []), ...(joya?.ventas_items || [])]
      const unicas = ventas.filter((venta, index, array) => array.findIndex(item => item.id === venta.id) === index)
      return unicas.sort((a, b) => Number(b.id || 0) - Number(a.id || 0))[0] || null
    },
    estadoJoya(joya) {
      const venta = this.latestVenta(joya)
      if (!venta) return 'Disponible'
      if (venta.estado === 'Cancelada') return 'Disponible'
      if (venta.estado === 'Entregado' && Number(venta.saldo || 0) <= 0) return 'Vendido'
      return Number(venta.saldo || 0) > 0 ? 'Reservado' : 'Disponible'
    },
    colorEstadoJoya(joya) {
      const estado = this.estadoJoya(joya)
      if (estado === 'Reservado') return 'warning'
      if (estado === 'Vendido') return 'negative'
      return 'positive'
    },
    getVitrinas() {
      this.loading = true
      this.$axios.get('vitrinas')
        .then(res => {
          this.vitrinas = res.data
          if (!this.selectedVitrinaId && this.vitrinas.length) {
            this.selectedVitrinaId = this.vitrinas[0].id
          }
          if (this.selectedVitrinaId && !this.vitrinas.some(v => v.id === this.selectedVitrinaId) && this.vitrinas.length) {
            this.selectedVitrinaId = this.vitrinas[0].id
          }
        })
        .catch(error => {
          this.$alert.error(error.response?.data?.message || 'Error al cargar vitrinas')
        })
        .finally(() => {
          this.loading = false
        })
    },
    openVitrinaDialog(vitrina = null) {
      this.vitrinaForm = vitrina
        ? { id: vitrina.id, nombre: vitrina.nombre, orden: vitrina.orden }
        : { id: null, nombre: '', orden: (this.vitrinas.length || 0) + 1 }
      this.vitrinaDialog = true
    },
    openColumnaDialog(vitrina, columna = null) {
      this.columnaForm = columna
        ? { id: columna.id, vitrina_id: vitrina.id, codigo: columna.codigo, orden: columna.orden }
        : { id: null, vitrina_id: vitrina.id, codigo: '', orden: (vitrina.columnas?.length || 0) + 1 }
      this.columnaDialog = true
    },
    openEstucheDialog(columna, estuche = null) {
      this.estucheForm = estuche
        ? { id: estuche.id, vitrina_columna_id: columna.id, nombre: estuche.nombre, orden: estuche.orden }
        : { id: null, vitrina_columna_id: columna.id, nombre: `ESTUCHE ${columna.codigo}`, orden: (columna.estuches?.length || 0) + 1 }
      this.estucheDialog = true
    },
    openAgregarJoyaDialog(estuche) {
      this.selectedEstuche = estuche
      this.joyaSeleccionadaId = null
      this.loading = true
      this.$axios.get('joyas', {
        params: {
          sin_estuche: 1
        }
      }).then(res => {
        this.joyasSinEstuche = res.data
        this.agregarJoyaDialog = true
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al cargar joyas sin estuche')
      }).finally(() => {
        this.loading = false
      })
    },
    openEditarJoyaDialog(joya) {
      this.joyaForm = {
        id: joya.id,
        nombre: joya.nombre || '',
        tipo: joya.tipo || 'Importada',
        peso: Number(joya.peso || 0),
        linea: joya.linea || 'Mama',
        monto_bs: Number(joya.monto_bs || 0)
      }
      this.joyaDialog = true
    },
    saveJoya() {
      this.loading = true
      this.$axios.put(`joyas/${this.joyaForm.id}`, this.joyaForm)
        .then(() => {
          this.joyaDialog = false
          this.$alert.success('Joya actualizada')
          this.getVitrinas()
        })
        .catch(error => {
          this.$alert.error(error.response?.data?.message || 'Error al actualizar joya')
        })
        .finally(() => {
          this.loading = false
        })
    },
    agregarJoyaAEstuche() {
      if (!this.joyaSeleccionadaId || !this.selectedEstuche) {
        this.$alert.error('Debe seleccionar una joya')
        return
      }

      this.loading = true
      this.$axios.post(`joyas/${this.joyaSeleccionadaId}/asignar-estuche`, {
        estuche_id: this.selectedEstuche.id
      }).then(() => {
        this.agregarJoyaDialog = false
        this.$alert.success('Joya agregada al estuche')
        this.getVitrinas()
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al agregar joya al estuche')
      }).finally(() => {
        this.loading = false
      })
    },
    quitarJoyaDeEstuche(joya) {
      this.$alert.dialog(`¿Desea quitar ${joya.nombre} del estuche?`)
        .onOk(() => {
          this.loading = true
          this.$axios.post(`joyas/${joya.id}/quitar-estuche`)
            .then(() => {
              this.$alert.success('Joya retirada del estuche')
              this.getVitrinas()
            })
            .catch(error => {
              this.$alert.error(error.response?.data?.message || 'Error al retirar joya del estuche')
            })
            .finally(() => {
              this.loading = false
            })
        })
    },
    saveVitrina() {
      this.loading = true
      const request = this.vitrinaForm.id
        ? this.$axios.put(`vitrinas/${this.vitrinaForm.id}`, this.vitrinaForm)
        : this.$axios.post('vitrinas', this.vitrinaForm)

      request.then(() => {
        this.vitrinaDialog = false
        this.$alert.success(this.vitrinaForm.id ? 'Vitrina actualizada' : 'Vitrina creada')
        this.getVitrinas()
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al guardar vitrina')
      }).finally(() => {
        this.loading = false
      })
    },
    saveColumna() {
      this.loading = true
      const request = this.columnaForm.id
        ? this.$axios.put(`vitrina-columnas/${this.columnaForm.id}`, this.columnaForm)
        : this.$axios.post('vitrina-columnas', this.columnaForm)

      request.then(() => {
        this.columnaDialog = false
        this.$alert.success(this.columnaForm.id ? 'Columna actualizada' : 'Columna creada')
        this.getVitrinas()
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al guardar columna')
      }).finally(() => {
        this.loading = false
      })
    },
    saveEstuche() {
      this.loading = true
      const request = this.estucheForm.id
        ? this.$axios.put(`estuches/${this.estucheForm.id}`, this.estucheForm)
        : this.$axios.post('estuches', this.estucheForm)

      request.then(() => {
        this.estucheDialog = false
        this.$alert.success(this.estucheForm.id ? 'Estuche actualizado' : 'Estuche creado')
        this.getVitrinas()
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al guardar estuche')
      }).finally(() => {
        this.loading = false
      })
    },
    deleteVitrina(vitrina) {
      this.$alert.dialog(`¿Desea eliminar la vitrina ${vitrina.nombre}?`)
        .onOk(() => {
          this.loading = true
          this.$axios.delete(`vitrinas/${vitrina.id}`)
            .then(() => {
              this.$alert.success('Vitrina eliminada')
              this.getVitrinas()
            })
            .catch(error => {
              this.$alert.error(error.response?.data?.message || 'Error al eliminar vitrina')
            })
            .finally(() => {
              this.loading = false
            })
        })
    },
    deleteColumna(columna) {
      this.$alert.dialog(`¿Desea eliminar la columna ${columna.codigo}?`)
        .onOk(() => {
          this.loading = true
          this.$axios.delete(`vitrina-columnas/${columna.id}`)
            .then(() => {
              this.$alert.success('Columna eliminada')
              this.getVitrinas()
            })
            .catch(error => {
              this.$alert.error(error.response?.data?.message || 'Error al eliminar columna')
            })
            .finally(() => {
              this.loading = false
            })
        })
    },
    deleteEstuche(estuche) {
      this.$alert.dialog(`¿Desea eliminar el estuche ${estuche.nombre}?`)
        .onOk(() => {
          this.loading = true
          this.$axios.delete(`estuches/${estuche.id}`)
            .then(() => {
              this.$alert.success('Estuche eliminado')
              this.getVitrinas()
            })
            .catch(error => {
              this.$alert.error(error.response?.data?.message || 'Error al eliminar estuche')
            })
            .finally(() => {
              this.loading = false
            })
        })
    }
  }
}
</script>

<style scoped>
.estuches-page {
  background: linear-gradient(180deg, rgba(17, 83, 116, 0.96) 0%, rgba(23, 103, 138, 0.94) 100%);
  min-height: 100%;
}

.estuches-shell {
  max-width: 1500px;
  margin: 0 auto;
}

.estuches-card {
  border-radius: 22px;
  box-shadow: 0 24px 60px rgba(4, 24, 34, 0.28);
}

.vitrina-board__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.column-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.column-card {
  border-radius: 18px;
  background: linear-gradient(180deg, #fefefe 0%, #f4f7f8 100%);
  min-height: 250px;
}

.column-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.column-card__body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.estuches-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.estuche-card {
  border-radius: 14px;
  background: #fff;
}

.joyas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(88px, 1fr));
  gap: 6px;
}

.joya-mini {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  background: #f6f8fa;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.joya-mini__actions {
  position: absolute;
  top: 2px;
  right: 2px;
  z-index: 2;
  background: rgba(255, 255, 255, 0.92);
  border-radius: 999px;
}

.joya-mini__img {
  height: 72px;
}

.joya-mini__text {
  padding: 6px;
}

.joya-mini__name {
  font-size: 11px;
  font-weight: 700;
  line-height: 1.15;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.joya-mini__meta {
  font-size: 10px;
  color: #17678a;
  font-weight: 700;
  margin-top: 2px;
}

.empty-slot.compact {
  min-height: 86px;
  border: 1px dashed rgba(38, 110, 145, 0.24);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 8px;
  text-align: center;
}

@media (max-width: 768px) {
  .vitrina-board__header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
