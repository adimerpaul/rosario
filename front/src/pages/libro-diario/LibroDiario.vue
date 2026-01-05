<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>

      <!-- Filtros / Acciones -->
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-2">
          <q-input
            type="date"
            v-model="fecha"
            label="Fecha"
            dense
            outlined
          />
        </div>

        <div class="col-12 col-md-2">
          <q-select
            v-model="usuario"
            :options="usuarios.map(u => u.username)"
            label="Usuario"
            dense
            outlined
            clearable
            v-if="$store.user?.role === 'Administrador'"
          />
        </div>

        <div class="col-12 col-md-2">
          <q-select
            v-model="metodo"
            :options="['EFECTIVO','QR']"
            label="Método pago"
            dense
            outlined
            clearable
          />
        </div>

        <div class="col-12 col-md-6 flex items-center justify-end q-gutter-sm">
          <q-btn
            style="width: 150px"
            size="12px"
            dense
            color="grey"
            icon="refresh"
            label="Actualizar"
            no-caps
            :loading="loading"
            @click="fetchDiario"
          />

          <q-btn
            style="width: 170px"
            size="12px"
            dense
            color="positive"
            icon="add_circle"
            label="Registrar ingreso"
            no-caps
            @click="openIngresoDialog"
          />

          <q-btn
            style="width: 170px"
            size="12px"
            dense
            color="negative"
            icon="remove_circle"
            label="Registrar egreso"
            no-caps
            @click="openEgresoDialog"
          />
        </div>
      </q-card-section>

      <q-separator />

      <!-- Totales arriba -->
      <q-card-section class="row q-col-gutter-sm">
        <div class="col-12 col-md-3">
          <div class="text-caption text-grey-7">TOTAL CAJA (neto)</div>
          <div class="text-h6">{{ currency(totalCaja) }}</div>
        </div>

        <div class="col-12 col-md-3 bg-green text-white">
          <div class="text-caption text-white">TOTAL INGRESOS (incluye caja)</div>
          <div class="text-h6">{{ currency(totalIngresos) }}</div>
        </div>

        <div class="col-12 col-md-3 bg-red text-white">
          <div class="text-caption text-white">TOTAL EGRESOS</div>
          <div class="text-h6">{{ currency(totalEgresos) }}</div>
        </div>

        <div class="col-12 col-md-3 flex items-center justify-end">
          <q-btn dense outline no-caps icon="visibility" label="Ver detalle"
                 @click="openDetalle('ingresos','EFECTIVO')" />
        </div>
      </q-card-section>

      <q-separator />

      <!-- DOS TABLAS -->
      <q-card-section>
        <div class="row q-col-gutter-md">

          <!-- INGRESOS -->
          <div class="col-12 col-md-6">
            <div class="row items-center q-mb-xs">
              <div class="col text-subtitle1 text-weight-bold">Ingresos</div>
              <div class="col-auto text-weight-medium">{{ currency(totalIngresos) }}</div>
            </div>

            <q-markup-table flat bordered dense>
              <thead>
              <tr class="bg-green-2">
                <th class="text-left">Hora</th>
                <th class="text-left">Descripción</th>
                <th class="text-right">Monto (Bs.)</th>
                <th class="text-left">Usuario</th>
                <th class="text-left">Método</th>
                <th class="text-left">Fuente</th>
                <th class="text-left">Estado</th>
                <th class="text-right" v-if="$store.user?.role === 'Administrador'">Acciones</th>
              </tr>
              </thead>

              <tbody>
              <!-- Caja inicial -->
              <tr>
                <td class="text-left">—</td>
                <td class="text-left">Caja inicial del día</td>
                <td class="text-right">{{ currency(openingAmount) }}</td>
                <td class="text-left">—</td>
                <td class="text-left">—</td>
                <td class="text-left">CAJA</td>
                <td class="text-left">
                  <q-chip dense color="blue-6" text-color="white">Fijo</q-chip>
                </td>
                <td v-if="$store.user?.role === 'Administrador'"></td>
              </tr>

              <tr v-for="it in ingresosActivos" :key="`in-${it.key}`">
                <td class="text-left">{{ it.hora }}</td>
                <td class="text-left">{{ it.descripcion }}</td>
                <td class="text-right">{{ currency(it.monto) }}</td>
                <td class="text-left">{{ it.usuario }}</td>
                <td class="text-left">{{ it.metodo || '—' }}</td>
                <td class="text-left">{{ it.fuente }}</td>
                <td class="text-left">
                  <q-chip dense :color="it.estado==='Activo' ? 'green-6' : 'grey-6'" text-color="white">
                    {{ it.estado }}
                  </q-chip>
                </td>

                <td class="text-right" v-if="$store.user?.role === 'Administrador'">
                  <q-btn
                    v-if="it.fuente==='INGRESO' && it.estado==='Activo'"
                    dense flat color="negative" icon="block" label="Anular"
                    no-caps size="10px"
                    @click="anularIngreso(it.id)"
                  />
                </td>
              </tr>

              <tr v-if="!ingresosActivos.length">
                <td :colspan="$store.user?.role === 'Administrador' ? 8 : 7"
                    class="text-center text-grey">
                  Sin ingresos (solo caja inicial)
                </td>
              </tr>
              </tbody>
            </q-markup-table>
          </div>

          <!-- EGRESOS -->
          <div class="col-12 col-md-6">
            <div class="row items-center q-mb-xs">
              <div class="col text-subtitle1 text-weight-bold">Egresos</div>
              <div class="col-auto text-weight-medium">{{ currency(totalEgresos) }}</div>
            </div>

            <q-markup-table flat bordered dense>
              <thead>
              <tr class="bg-red-2">
                <th class="text-left">Hora</th>
                <th class="text-left">Descripción</th>
                <th class="text-right">Monto (Bs.)</th>
                <th class="text-left">Usuario</th>
                <th class="text-left">Método</th>
                <th class="text-left">Fuente</th>
                <th class="text-left">Estado</th>
                <th class="text-right" v-if="$store.user?.role === 'Administrador'">Acciones</th>
              </tr>
              </thead>

              <tbody>
              <tr v-for="it in egresosActivos" :key="`eg-${it.key}`">
                <td class="text-left">{{ it.hora }}</td>
                <td class="text-left">{{ it.descripcion }}</td>
                <td class="text-right">{{ currency(it.monto) }}</td>
                <td class="text-left">{{ it.usuario }}</td>
                <td class="text-left">{{ it.metodo || 'EFECTIVO' }}</td>
                <td class="text-left">{{ it.fuente }}</td>
                <td class="text-left">
                  <q-chip dense :color="it.estado==='Activo' ? 'green-6' : 'grey-6'" text-color="white">
                    {{ it.estado }}
                  </q-chip>
                </td>

                <td class="text-right" v-if="$store.user?.role === 'Administrador'">
                  <q-btn
                    v-if="it.fuente==='EGRESO' && it.estado==='Activo'"
                    dense flat color="negative" icon="block" label="Anular"
                    no-caps size="10px"
                    @click="anularEgreso(it.id)"
                  />
                </td>
              </tr>

              <tr v-if="!egresosActivos.length">
                <td :colspan="$store.user?.role === 'Administrador' ? 8 : 7"
                    class="text-center text-grey">
                  Sin egresos
                </td>
              </tr>
              </tbody>
            </q-markup-table>
          </div>

        </div>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner size="32px" />
      </q-inner-loading>
    </q-card>

    <!-- Dialog Ingreso -->
    <q-dialog v-model="dlgIngreso" persistent>
      <q-card style="min-width: 420px;">
        <q-card-section class="text-h6">Registrar ingreso</q-card-section>
        <q-separator/>
        <q-card-section class="q-gutter-sm">
          <q-input v-model.number="ingresoForm.monto" type="number" min="0" step="0.01" label="Monto (Bs.)" dense outlined />
          <q-input v-model="ingresoForm.descripcion" label="Descripción" dense outlined />
          <div class="row q-col-gutter-sm">
            <div class="col">
              <q-radio v-model="ingresoForm.metodo" val="EFECTIVO" label="EFECTIVO" />
            </div>
            <div class="col">
              <q-radio v-model="ingresoForm.metodo" val="QR" label="QR" />
            </div>
          </div>
        </q-card-section>
        <q-separator/>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey" v-close-popup />
          <q-btn color="positive" icon="save" label="Guardar" :loading="loadingSave" @click="guardarIngreso" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog Egreso -->
    <q-dialog v-model="dlgEgreso" persistent>
      <q-card style="min-width: 420px;">
        <q-card-section class="text-h6">Registrar egreso</q-card-section>
        <q-separator/>
        <q-card-section class="q-gutter-sm">
          <q-input v-model.number="egresoForm.monto" type="number" min="0" step="0.01" label="Monto (Bs.)" dense outlined />
          <q-input v-model="egresoForm.descripcion" label="Descripción" dense outlined />
          <div class="row q-col-gutter-sm">
            <div class="col">
              <q-radio v-model="egresoForm.metodo" val="EFECTIVO" label="EFECTIVO" />
            </div>
            <div class="col">
              <q-radio v-model="egresoForm.metodo" val="QR" label="QR" />
            </div>
          </div>
        </q-card-section>
        <q-separator/>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey" v-close-popup />
          <q-btn color="negative" icon="save" label="Guardar" :loading="loadingSave" @click="guardarEgreso" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog Detalle -->
    <q-dialog v-model="dlgDetalle">
      <q-card style="min-width:720px;max-width:90vw">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-btn-toggle
              v-model="detalleContext.tipo"
              spread no-caps toggle-color="primary"
              :options="[
                {label:'Ingresos', value:'ingresos'},
                {label:'Egresos', value:'egresos'}
              ]"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-btn-toggle
              v-model="detalleContext.metodo"
              spread no-caps toggle-color="primary"
              :options="[
                {label:'EFECTIVO', value:'EFECTIVO'},
                {label:'QR', value:'QR'}
              ]"
            />
          </div>
          <div class="col-12 col-md-2 flex justify-end">
            <q-btn flat dense icon="close" v-close-popup />
          </div>
        </q-card-section>

        <q-separator/>

        <q-card-section>
          <q-markup-table flat bordered dense>
            <thead>
            <tr>
              <th class="text-left">Hora</th>
              <th class="text-right">Monto (Bs.)</th>
              <th class="text-left">Descripción</th>
              <th class="text-left">Usuario</th>
              <th class="text-left">Fuente</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="it in detalleFiltrado" :key="`dt-${it.key}`">
              <td class="text-left">{{ it.hora }}</td>
              <td class="text-right">{{ currency(it.monto) }}</td>
              <td class="text-left">{{ it.descripcion }}</td>
              <td class="text-left">{{ it.usuario }}</td>
              <td class="text-left">{{ it.fuente }}</td>
            </tr>
            <tr v-if="!detalleFiltrado.length">
              <td colspan="5" class="text-center text-grey">Sin movimientos</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-separator/>

        <q-card-section class="text-right text-weight-medium">
          Total: {{ currency(detalleFiltrado.reduce((s,x)=>s+Number(x.monto||0),0)) }}
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'LibroDiarioSimple',

  data () {
    return {
      fecha: moment().format('YYYY-MM-DD'),
      usuario: null,
      metodo: null, // EFECTIVO / QR (opcional)

      usuarios: [],

      // data simple
      openingAmount: 0,
      items_ingresos: [],
      items_egresos: [],

      totalIngresos: 0,
      totalEgresos: 0,
      totalCaja: 0,

      loading: false,
      loadingSave: false,

      // dialogs
      dlgIngreso: false,
      ingresoForm: { fecha: '', descripcion: '', metodo: 'EFECTIVO', monto: null },

      dlgEgreso: false,
      egresoForm: { fecha: '', descripcion: '', metodo: 'EFECTIVO', monto: null },

      dlgDetalle: false,
      detalleContext: { tipo: 'ingresos', metodo: 'EFECTIVO' }
    }
  },

  computed: {
    queryParams () {
      // ✅ UN SOLO OBJETO PARA PARAMS
      return {
        date: this.fecha,
        usuario: this.usuario || null,
        metodo_pago: this.metodo || null
      }
    },

    ingresosActivos () {
      return (this.items_ingresos || []).filter(x => (x.estado || 'Activo') === 'Activo')
    },

    egresosActivos () {
      return (this.items_egresos || []).filter(x => (x.estado || 'Activo') === 'Activo')
    },

    detalleFiltrado () {
      const base = this.detalleContext.tipo === 'ingresos' ? this.items_ingresos : this.items_egresos
      return (base || []).filter(x =>
        (x.estado || 'Activo') === 'Activo' &&
        (x.metodo || 'EFECTIVO') === this.detalleContext.metodo
      )
    }
  },

  watch: {
    // ✅ super simple: cuando cambie algo, recarga
    fecha () { this.fetchDiario() },
    usuario () { this.fetchDiario() },
    metodo () { this.fetchDiario() }
  },

  mounted () {
    this.ingresoForm.fecha = this.fecha
    this.egresoForm.fecha = this.fecha
    this.fetchUsuarios()
    this.fetchDiario()
  },

  methods: {
    currency (n) { return `${Number(n || 0).toFixed(2)} Bs.` },

    fetchUsuarios () {
      this.$axios.get('users')
        .then(({ data }) => { this.usuarios = data || [] })
        .catch(() => { this.usuarios = [] })
    },

    fetchDiario () {
      this.loading = true

      this.$axios.get('daily-cash', { params: this.queryParams })
        .then(({ data }) => {
          // caja inicial (si es 0, usa sugerido de ayer pero SIN otro GET)
          const opening = Number(data.daily_cash?.opening_amount || 0)
          const suggested = Number(data.suggested_opening_amount || 0)
          this.openingAmount = opening > 0 ? opening : suggested

          this.items_ingresos = data.items_ingresos || []
          this.items_egresos = data.items_egresos || []

          this.totalIngresos = Number(data.total_ingresos || 0)
          this.totalEgresos  = Number(data.total_egresos || 0)
          this.totalCaja     = Number(data.total_caja || 0)

          // mantener fechas en forms
          this.ingresoForm.fecha = this.fecha
          this.egresoForm.fecha = this.fecha
        })
        .catch((e) => {
          this.$alert?.error?.(e.response?.data?.message || 'Error al cargar el libro diario')
        })
        .finally(() => {
          this.loading = false
        })
    },

    openIngresoDialog () {
      this.ingresoForm = { fecha: this.fecha, descripcion: '', metodo: 'EFECTIVO', monto: null }
      this.dlgIngreso = true
    },

    guardarIngreso () {
      this.loadingSave = true
      this.$axios.post('ingresos', this.ingresoForm)
        .then(() => {
          this.$alert?.success?.('Ingreso registrado')
          this.dlgIngreso = false
          this.fetchDiario()
        })
        .catch((e) => {
          this.$alert?.error?.(e.response?.data?.message || 'Error al registrar ingreso')
        })
        .finally(() => { this.loadingSave = false })
    },

    openEgresoDialog () {
      this.egresoForm = { fecha: this.fecha, descripcion: '', metodo: 'EFECTIVO', monto: null }
      this.dlgEgreso = true
    },

    guardarEgreso () {
      this.loadingSave = true
      this.$axios.post('egresos', this.egresoForm)
        .then(() => {
          this.$alert?.success?.('Egreso registrado')
          this.dlgEgreso = false
          this.fetchDiario()
        })
        .catch((e) => {
          this.$alert?.error?.(e.response?.data?.message || 'Error al registrar egreso')
        })
        .finally(() => { this.loadingSave = false })
    },

    anularIngreso (id) {
      this.$q.dialog({
        title: 'Confirmar',
        message: '¿Seguro que deseas anular este ingreso?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.loading = true
        this.$axios.post(`ingresos/${id}/anular`)
          .then(() => {
            this.$alert?.success?.('Ingreso anulado')
            this.fetchDiario()
          })
          .catch((e) => {
            this.$alert?.error?.(e.response?.data?.message || 'No se pudo anular')
          })
          .finally(() => { this.loading = false })
      })
    },

    anularEgreso (id) {
      this.$q.dialog({
        title: 'Confirmar',
        message: '¿Seguro que deseas anular este egreso?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.loading = true
        this.$axios.post(`egresos/${id}/anular`)
          .then(() => {
            this.$alert?.success?.('Egreso anulado')
            this.fetchDiario()
          })
          .catch((e) => {
            this.$alert?.error?.(e.response?.data?.message || 'No se pudo anular')
          })
          .finally(() => { this.loading = false })
      })
    },

    openDetalle (tipo, metodo) {
      this.detalleContext = { tipo, metodo }
      this.dlgDetalle = true
    }
  }
}
</script>
