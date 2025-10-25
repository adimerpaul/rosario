<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>

      <!-- Filtros / Acciones -->
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-2">
          <q-input type="date" v-model="fecha" label="Fecha" dense outlined @update:model-value="onChangeFecha" />
        </div>

        <div class="col-12 col-md-2">
<!--          <q-input v-model.number="openingAmount" type="number" min="0" step="0.01"-->
<!--                   dense outlined label="Caja inicial (Bs.)" @keyup.enter="guardarCaja" />-->
        </div>

        <div class="col-12 col-md-2">
<!--          <q-btn size="12px" dense color="primary" icon="save" label="Guardar caja" no-caps-->
<!--                 :loading="loadingSave" @click="guardarCaja" />-->
        </div>

        <div class="col-12 col-md-2">
          <q-select v-model="usuario"
                    :options="usuarios.map(u => u.username)"
                    label="Usuario" dense outlined clearable
                    v-if="$store.user?.role === 'Administrador'"
                    />
        </div>
        <div class="col-12 col-md-2">
          <q-select v-model="ingresoForm.metodo"
                    :options="['EFECTIVO','QR']"
                    label="Método pago" dense outlined clearable
                    />
        </div>

        <div class="col-12 col-md-2 flex items-center justify-end">
          <q-btn size="12px" dense color="grey" flat icon="refresh" label="Actualizar" no-caps
                 :loading="loading" @click="fetchDiario" />
          <q-separator vertical class="q-mx-sm" />
          <q-btn size="12px" dense color="positive" icon="add_circle" label="Registrar ingreso" no-caps
                 class="q-mr-sm" @click="openIngresoDialog" />
          <q-btn size="12px" dense color="negative" icon="remove_circle" label="Registrar egreso" no-caps
                 @click="openEgresoDialog" />
        </div>
      </q-card-section>

      <q-separator />

      <!-- Totales arriba (compacto) -->
      <q-card-section class="row q-col-gutter-sm">
        <div class="col-12 col-md-3">
          <div class="text-caption text-grey-7">TOTAL INGRESOS (incluye caja)</div>
          <div class="text-h6">{{ currency(totalIngresos) }}</div>
        </div>
        <div class="col-12 col-md-3">
          <div class="text-caption text-grey-7">TOTAL EGRESOS</div>
          <div class="text-h6">{{ currency(totalEgresos) }}</div>
        </div>
        <div class="col-12 col-md-3">
          <div class="text-caption text-grey-7">TOTAL CAJA (neto)</div>
          <div class="text-h6">{{ currency(totalCaja) }}</div>
        </div>
        <div class="col-12 col-md-3 flex items-center justify-end">
          <q-btn dense outline no-caps icon="visibility" label="Ver detalle"
                 @click="openDetalle('ingresos','EFECTIVO')" />
        </div>
      </q-card-section>

      <q-separator />

      <!-- DOS TABLAS: Ingresos y Egresos -->
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
<!--                th estado-->
                <th class="text-left">Estado</th>

                <th class="text-right"
                    v-if="$store.user?.role === 'Administrador'">Acciones</th>
              </tr>
              </thead>
              <tbody>
              <!-- Caja inicial como 1ra fila -->
              <tr>
                <td class="text-left">—</td>
                <td class="text-left">Caja inicial del día</td>
                <td class="text-right">{{ currency(openingAmount) }}</td>
                <td class="text-left">—</td>
                <td class="text-left">—</td>
                <td class="text-left">CAJA</td>
                <td class="text-left">
                  <q-chip dense color="blue-6" text-color="white">
                    Fijo
                  </q-chip>
                </td>
              </tr>

              <tr v-for="it in ingresosFlat" :key="`in-${it.key}`">
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
                  <q-btn v-if="(it.fuente==='INGRESO' && it.estado==='Activo')"
                         dense flat color="negative" icon="block" label="Anular"
                         @click="anularIngreso(it.id)" no-caps size="10px" />
                </td>
              </tr>

              <tr v-if="!ingresosFlat.length">
                <td colspan="6" class="text-center text-grey">Sin ingresos (solo caja inicial)</td>
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
                <th class="text-right"
                    v-if="$store.user?.role === 'Administrador'">Acciones</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="it in egresosFlat" :key="`eg-${it.key}`">
                <td class="text-left">{{ it.hora }}</td>
                <td class="text-left">{{ it.descripcion }}</td>
                <td class="text-right">{{ currency(it.monto) }}</td>
                <td class="text-left">{{ it.usuario }}</td>
                <td class="text-left">{{ it.metodo || 'EFECTIVO' }}</td>
                <td class="text-left">{{ it.fuente }}</td>
                <td class="text-left">
                  <q-chip dense :color="(it.estado || 'Activo')==='Activo' ? 'green-6' : 'grey-6'" text-color="white">
                    {{ it.estado || 'Activo' }}
                  </q-chip>
                </td>
                <td class="text-right" v-if="$store.user?.role === 'Administrador'">
                  <q-btn v-if="it.fuente==='EGRESO' && (it.estado || 'Activo')==='Activo'"
                         dense flat color="negative" icon="block" label="Anular"
                         @click="anularEgreso(it.id)" no-caps size="10px" />
                </td>
              </tr>
              <tr v-if="!egresosFlat.length">
                <td colspan="8" class="text-center text-grey">Sin egresos</td>
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

    <!-- Diálogo: Registrar Ingreso -->
    <q-dialog v-model="dlgIngreso" persistent>
      <q-card style="min-width: 420px;">
        <q-card-section class="text-h6">Registrar ingreso</q-card-section>
        <q-separator/>
        <q-card-section class="q-gutter-sm">
<!--          <q-input type="date" v-model="ingresoForm.fecha" label="Fecha" dense outlined />-->
          <q-input v-model="ingresoForm.descripcion" label="Descripción" dense outlined />
<!--          <q-select v-model="ingresoForm.metodo" :options="['EFECTIVO','QR']" label="Método" dense outlined />-->
<!--          en radio-->
          <q-radio v-model="ingresoForm.metodo" val="EFECTIVO" label="EFECTIVO" />
          <q-radio v-model="ingresoForm.metodo" val="QR" label="QR" />
          <q-input v-model.number="ingresoForm.monto" type="number" min="0" step="0.01" label="Monto (Bs.)" dense outlined />
          <q-input v-model="ingresoForm.nota" type="textarea" autogrow label="Nota (opcional)" dense outlined />
        </q-card-section>
        <q-separator/>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey" v-close-popup />
          <q-btn color="positive" icon="save" label="Guardar" :loading="loadingSave" @click="guardarIngreso" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Registrar Egreso -->
    <q-dialog v-model="dlgEgreso" persistent>
      <q-card style="min-width: 420px;">
        <q-card-section class="text-h6">Registrar egreso</q-card-section>
        <q-separator/>
        <q-card-section class="q-gutter-sm">
<!--          <q-input type="date" v-model="egresoForm.fecha" label="Fecha" dense outlined />-->
          <q-input v-model="egresoForm.descripcion" label="Descripción" dense outlined />
<!--          <q-select v-model="egresoForm.metodo" :options="['EFECTIVO','QR']" label="Método" dense outlined />-->
          <q-radio v-model="egresoForm.metodo" val="EFECTIVO" label="EFECTIVO" />
          <q-radio v-model="egresoForm.metodo" val="QR" label="QR" />
          <q-input v-model.number="egresoForm.monto" type="number" min="0" step="0.01" label="Monto (Bs.)" dense outlined />
          <q-input v-model="egresoForm.nota" type="textarea" autogrow label="Nota (opcional)" dense outlined />
        </q-card-section>
        <q-separator/>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey" v-close-popup />
          <q-btn color="negative" icon="save" label="Guardar" :loading="loadingSave" @click="guardarEgreso" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Detalle por método (conmutadores) -->
    <q-dialog v-model="dlgDetalle">
      <q-card style="min-width:720px;max-width:90vw">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-btn-toggle
              v-model="detalleContext.tipo"
              spread
              no-caps
              toggle-color="primary"
              :options="[
                {label:'Ingresos', value:'ingresos'},
                {label:'Egresos', value:'egresos'}
              ]"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-btn-toggle
              v-model="detalleContext.metodo"
              spread
              no-caps
              toggle-color="primary"
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
import {store} from "quasar/wrappers";

export default {
  name: 'LibroDiarioSimple',
  data () {
    return {
      fecha: moment().format('YYYY-MM-DD'),
      openingAmount: 0,

      ingresos: {
        ordenes: { total: 0, items: [] },
        pagos_ordenes: { total: 0, items: [] },
        pagos_prestamos: { total: 0, items: [] },
        otros: { total: 0, items: [] } // <- NUEVO
      },
      egresos: {
        prestamos: { total: 0, items: [] },
        otros:     { total: 0, items: [] }
      },

      totalIngresos: 0,
      totalEgresos: 0,
      totalCaja: 0,

      usuarios: [],
      usuario: '',

      loading: false,
      loadingSave: false,

      // dialogs
      dlgIngreso: false,
      ingresoForm: { fecha: '', descripcion: '', metodo: 'EFECTIVO', monto: null, nota: '' },

      dlgEgreso: false,
      egresoForm: { fecha: '', descripcion: '', metodo: 'EFECTIVO', monto: null, nota: '' },

      dlgDetalle: false,
      detalleContext: { tipo: 'ingresos', metodo: 'EFECTIVO' }
    }
  },
  computed: {
    ingresosFlat () {
      const o  = (this.ingresos.ordenes.items || []).map(x => ({ ...x, fuente: 'ORDEN',             key: `o-${x.id}` }))
      const po = (this.ingresos.pagos_ordenes.items || []).map(x => ({ ...x, fuente: 'PAGO ORDEN',      key: `po-${x.id}` }))
      const pp = (this.ingresos.pagos_prestamos.items || []).map(x => ({ ...x, fuente: 'PAGO PRÉSTAMO',  key: `pp-${x.id}` }))
      const ot = (this.ingresos.otros.items || []).map(x => ({ ...x, fuente: 'INGRESO',               key: `in-${x.id}` })) // NUEVO
      return [...o, ...po, ...pp, ...ot]
    },
    egresosFlat () {
      const pr = (this.egresos.prestamos.items || []).map(x => ({ ...x, fuente: 'PRÉSTAMO OTORGADO', key: `pr-${x.id}` }))
      const ot = (this.egresos.otros.items || []).map(x => ({ ...x, fuente: 'EGRESO',               key: `ot-${x.id}` }))
      return [...pr, ...ot]
    },
    detalleFiltrado () {
      const base = this.detalleContext.tipo === 'ingresos' ? this.ingresosFlat : this.egresosFlat
      return base.filter(x => (x.metodo || 'EFECTIVO') === this.detalleContext.metodo && (x.estado ?? 'Activo') === 'Activo')
    }
  },
  mounted () {
    this.egresoForm.fecha  = this.fecha
    this.ingresoForm.fecha = this.fecha
    this.fetchUsuarios()
    this.fetchDiario()
  },
  methods: {
    store,
    currency (n) { return `${Number(n || 0).toFixed(2)} Bs.` },

    async fetchUsuarios () {
      try {
        const { data } = await this.$axios.get('users')
        this.usuarios = data || []
      } catch { this.usuarios = [] }
    },

    onChangeFecha () {
      this.egresoForm.fecha  = this.fecha
      this.ingresoForm.fecha = this.fecha
      this.fetchDiario()
    },

    async fetchDiario () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('daily-cash', {
          params: {
            date: this.fecha,
            usuario: this.usuario,
            metodo_pago: this.ingresoForm.metodo
          }
        })

        this.openingAmount = Number(data.daily_cash?.opening_amount || 0)
        if (!this.openingAmount) await this.tryPreloadYesterday()

        this.ingresos = {
          ordenes:         data.ingresos?.ordenes         || { total: 0, items: [] },
          pagos_ordenes:   data.ingresos?.pagos_ordenes   || { total: 0, items: [] },
          pagos_prestamos: data.ingresos?.pagos_prestamos || { total: 0, items: [] },
          otros:           data.ingresos?.otros           || { total: 0, items: [] }, // NUEVO
        }
        this.egresos = {
          prestamos: data.egresos?.prestamos || { total: 0, items: [] },
          otros:     data.egresos?.otros     || { total: 0, items: [] }
        }

        this.totalIngresos = Number(data.total_ingresos || 0)
        this.totalEgresos  = Number(data.total_egresos  || 0)
        this.totalCaja     = Number(data.total_caja     || (this.totalIngresos - this.totalEgresos))
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al cargar el libro diario')
      } finally {
        this.loading = false
      }
    },

    async tryPreloadYesterday () {
      try {
        const ayer = moment(this.fecha).subtract(1, 'day').format('YYYY-MM-DD')
        const { data } = await this.$axios.get('daily-cash', { params: { date: ayer } })
        const prev = Number(data.daily_cash?.opening_amount || 0)
        if (prev > 0) this.openingAmount = prev
      } catch {/* ignore */}
    },

    async guardarCaja () {
      this.loadingSave = true
      try {
        await this.$axios.post('daily-cash', {
          date: this.fecha,
          opening_amount: Number(this.openingAmount || 0)
        })
        this.$alert?.success?.('Caja inicial guardada')
        this.fetchDiario()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al guardar caja')
      } finally {
        this.loadingSave = false
      }
    },

    /* ===== Ingresos manuales ===== */
    openIngresoDialog () {
      this.ingresoForm = { fecha: this.fecha, descripcion: '', metodo: 'EFECTIVO', monto: null, nota: '' }
      this.dlgIngreso = true
    },
    async guardarIngreso () {
      try {
        this.loadingSave = true
        await this.$axios.post('ingresos', this.ingresoForm)
        this.$alert?.success?.('Ingreso registrado')
        this.dlgIngreso = false
        this.fetchDiario()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al registrar ingreso')
      } finally {
        this.loadingSave = false
      }
    },

    /* ===== Egresos manuales ===== */
    openEgresoDialog () {
      this.egresoForm = { fecha: this.fecha, descripcion: '', metodo: 'EFECTIVO', monto: null, nota: '' }
      this.dlgEgreso = true
    },
    async guardarEgreso () {
      try {
        this.loadingSave = true
        await this.$axios.post('egresos', this.egresoForm)
        this.$alert?.success?.('Egreso registrado')
        this.dlgEgreso = false
        this.fetchDiario()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al registrar egreso')
      } finally {
        this.loadingSave = false
      }
    },
    anularIngreso(id) {
      this.$q.dialog({
        title: 'Confirmar',
        message: '¿Seguro que deseas anular este ingreso?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          this.loading = true
          await this.$axios.post(`ingresos/${id}/anular`)
          this.$alert?.success?.('Ingreso anulado')
          this.fetchDiario()
        } catch (e) {
          this.$alert?.error?.(e.response?.data?.message || 'No se pudo anular')
        } finally {
          this.loading = false
        }
      })
    },
    async anularEgreso (id) {
      this.$q.dialog({
        title: 'Confirmar',
        message: '¿Seguro que deseas anular este egreso?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          this.loading = true
          await this.$axios.post(`egresos/${id}/anular`)
          this.$alert?.success?.('Egreso anulado')
          this.fetchDiario()
        } catch (e) {
          this.$alert?.error?.(e.response?.data?.message || 'No se pudo anular')
        } finally {
          this.loading = false
        }
      })
    },

    /* ===== Detalle por método ===== */
    openDetalle (tipo, metodo) {
      this.detalleContext = { tipo, metodo }
      this.dlgDetalle = true
    }
  }
}
</script>
