<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>

      <!-- Filtros / Acciones -->
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-3">
          <q-input type="date" v-model="fecha" label="Fecha" dense outlined @update:model-value="fetchDiario" />
        </div>

        <div class="col-12 col-md-3">
          <q-input v-model.number="openingAmount" type="number" min="0" step="0.01"
                   dense outlined label="Caja inicial (Bs.)" @keyup.enter="guardarCaja" />
        </div>

        <div class="col-12 col-md-3 flex items-center">
          <q-btn color="primary" icon="save" label="Guardar caja" no-caps :loading="loadingSave" @click="guardarCaja" />
          <q-btn color="grey" flat icon="refresh" label="Actualizar"
                 class="q-ml-sm" no-caps :loading="loading" @click="fetchDiario" />
        </div>

        <div class="col-12 col-md-3">
          <q-card bordered class="bg-blue-1">
            <q-card-section class="q-pa-sm">
              <div class="text-weight-bold text-blue">TOTAL CAJA (neto)</div>
              <div class="text-h6">{{ currency(totalCaja) }}</div>
              <div class="text-caption">= Caja + Ingresos – Egresos</div>
            </q-card-section>
          </q-card>
        </div>
      </q-card-section>

      <q-separator />

      <!-- Resumen superior -->
      <q-card-section>
        <div class="row q-col-gutter-sm">

          <div class="col-12 col-md-3">
            <q-card bordered class="bg-green-1">
              <q-card-section>
                <div class="text-weight-bold text-green-10">ÓRDENES (adelantos)</div>
                <div class="text-h6">{{ currency(ingresos.ordenes.total) }}</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-3">
            <q-card bordered class="bg-green-2">
              <q-card-section>
                <div class="text-weight-bold text-green-10">PAGOS ÓRDENES</div>
                <div class="text-h6">{{ currency(ingresos.pagos_ordenes.total) }}</div>
                <div class="text-caption">
                  Efec: {{ currency(sumMetodo(ingresos.pagos_ordenes.items,'EFECTIVO')) }} ·
                  QR: {{ currency(sumMetodo(ingresos.pagos_ordenes.items,'QR')) }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-3">
            <q-card bordered class="bg-teal-1">
              <q-card-section>
                <div class="text-weight-bold text-teal-10">PAGOS PRÉSTAMOS</div>
                <div class="text-h6">{{ currency(ingresos.pagos_prestamos.total) }}</div>
                <div class="text-caption">
                  Efec: {{ currency(sumMetodo(ingresos.pagos_prestamos.items,'EFECTIVO')) }} ·
                  QR: {{ currency(sumMetodo(ingresos.pagos_prestamos.items,'QR')) }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-3">
            <q-card bordered class="bg-red-1">
              <q-card-section>
                <div class="text-weight-bold text-red-10">EGRESOS (préstamos otorgados)</div>
                <div class="text-h6">{{ currency(egresos.prestamos.total) }}</div>
                <div class="text-caption">
                  Efec: {{ currency(sumMetodo(egresos.prestamos.items,'EFECTIVO')) }} ·
                  QR: {{ currency(sumMetodo(egresos.prestamos.items,'QR')) }}
                </div>
              </q-card-section>
            </q-card>
          </div>

        </div>
      </q-card-section>

      <q-separator />

      <!-- Caja inicial -->
      <q-card-section>
        <q-card bordered class="q-mb-sm">
          <q-card-section class="row items-center">
            <div class="col-12 col-md-8 text-weight-bold">Caja inicial del día</div>
            <div class="col-12 col-md-4 text-right text-h6">{{ currency(openingAmount) }}</div>
          </q-card-section>
        </q-card>

        <!-- Órdenes (adelantos) -->
        <q-card bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <div class="col-12 col-md-8 text-weight-bold">Órdenes de Trabajo (adelantos)</div>
            <div class="col-12 col-md-4 text-right text-h6">{{ currency(ingresos.ordenes.total) }}</div>
          </q-card-section>

          <q-markup-table flat bordered dense>
            <thead>
            <tr class="bg-green-6 text-white">
              <th class="text-left">Hora</th>
              <th class="text-left">Descripción</th>
              <th class="text-left">Usuario</th>
              <th class="text-right">Monto (Bs.)</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="it in ingresos.ordenes.items" :key="`o-${it.id}`">
              <td class="text-left">{{ it.hora }}</td>
              <td class="text-left">{{ it.descripcion }}</td>
              <td class="text-left">{{ it.usuario }}</td>
              <td class="text-right">{{ currency(it.monto) }}</td>
            </tr>
            <tr v-if="!ingresos.ordenes.items.length">
              <td colspan="4" class="text-center text-grey">Sin ingresos por órdenes</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card>

        <!-- Pagos de Órdenes -->
        <q-card bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <div class="col-12 col-md-8 text-weight-bold">Pagos de Órdenes</div>
            <div class="col-12 col-md-4 text-right text-h6">{{ currency(ingresos.pagos_ordenes.total) }}</div>
          </q-card-section>

          <q-markup-table flat bordered dense>
            <thead>
            <tr class="bg-green-3 text-black">
              <th class="text-left">Hora</th>
              <th class="text-left">Descripción</th>
              <th class="text-left">Usuario</th>
              <th class="text-left">Método</th>
              <th class="text-right">Monto (Bs.)</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="it in ingresos.pagos_ordenes.items" :key="`po-${it.id}`">
              <td class="text-left">{{ it.hora }}</td>
              <td class="text-left">{{ it.descripcion }}</td>
              <td class="text-left">{{ it.usuario }}</td>
              <td class="text-left">{{ it.metodo || '—' }}</td>
              <td class="text-right">{{ currency(it.monto) }}</td>
            </tr>
            <tr v-if="!ingresos.pagos_ordenes.items.length">
              <td colspan="5" class="text-center text-grey">Sin pagos registrados</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card>

        <!-- Pagos de Préstamos -->
        <q-card bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <div class="col-12 col-md-8 text-weight-bold">Pagos de Préstamos</div>
            <div class="col-12 col-md-4 text-right text-h6">{{ currency(ingresos.pagos_prestamos.total) }}</div>
          </q-card-section>

          <q-markup-table flat bordered dense>
            <thead>
            <tr class="bg-teal-3 text-black">
              <th class="text-left">Hora</th>
              <th class="text-left">Descripción</th>
              <th class="text-left">Usuario</th>
              <th class="text-left">Método</th>
              <th class="text-left">Tipo</th>
              <th class="text-right">Monto (Bs.)</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="it in ingresos.pagos_prestamos.items" :key="`pp-${it.id}`">
              <td class="text-left">{{ it.hora }}</td>
              <td class="text-left">{{ it.descripcion }}</td>
              <td class="text-left">{{ it.usuario }}</td>
              <td class="text-left">{{ it.metodo || '—' }}</td>
              <td class="text-left">{{ it.tipo_pago || '—' }}</td>
              <td class="text-right">{{ currency(it.monto) }}</td>
            </tr>
            <tr v-if="!ingresos.pagos_prestamos.items.length">
              <td colspan="6" class="text-center text-grey">Sin pagos de préstamos</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card>

        <!-- EGRESOS: Préstamos otorgados -->
        <q-card bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <div class="col-12 col-md-8 text-weight-bold">Egresos — Préstamos Otorgados</div>
            <div class="col-12 col-md-4 text-right text-h6">{{ currency(egresos.prestamos.total) }}</div>
          </q-card-section>

          <q-markup-table flat bordered dense>
            <thead>
            <tr class="bg-red-3 text-black">
              <th class="text-left">Hora</th>
              <th class="text-left">Descripción</th>
              <th class="text-left">Usuario</th>
              <th class="text-left">Método</th>
              <th class="text-right">Monto (Bs.)</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="it in egresos.prestamos.items" :key="`eg-${it.id}`">
              <td class="text-left">{{ it.hora }}</td>
              <td class="text-left">{{ it.descripcion }}</td>
              <td class="text-left">{{ it.usuario }}</td>
              <td class="text-left">{{ it.metodo || 'EFECTIVO' }}</td>
              <td class="text-right">{{ currency(it.monto) }}</td>
            </tr>
            <tr v-if="!egresos.prestamos.items.length">
              <td colspan="5" class="text-center text-grey">Sin egresos de préstamos</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card>

        <!-- Totales finales -->
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-3">
            <q-card bordered class="bg-green-1">
              <q-card-section>
                <div class="text-weight-bold text-green-10">TOTAL INGRESOS (con caja)</div>
                <div class="text-h6">{{ currency(totalIngresos) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered class="bg-red-1">
              <q-card-section>
                <div class="text-weight-bold text-red-10">TOTAL EGRESOS</div>
                <div class="text-h6">{{ currency(totalEgresos) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-3">
            <q-card bordered class="bg-blue-1">
              <q-card-section>
                <div class="text-weight-bold text-blue-10">TOTAL CAJA (neto)</div>
                <div class="text-h6">{{ currency(totalCaja) }}</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Totales por método -->
        <div class="row q-col-gutter-sm q-mt-sm">
          <div class="col-12 col-md-4">
            <q-card bordered>
              <q-card-section>
                <div class="text-weight-bold">Ingresos por método</div>
                <div class="row q-mt-xs">
                  <div class="col-6">EFECTIVO</div>
                  <div class="col-6 text-right">{{ currency(totalesMetodo.ingresos.EFECTIVO) }}</div>
                </div>
                <div class="row">
                  <div class="col-6">QR</div>
                  <div class="col-6 text-right">{{ currency(totalesMetodo.ingresos.QR) }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-4">
            <q-card bordered>
              <q-card-section>
                <div class="text-weight-bold">Egresos por método</div>
                <div class="row q-mt-xs">
                  <div class="col-6">EFECTIVO</div>
                  <div class="col-6 text-right">{{ currency(totalesMetodo.egresos.EFECTIVO) }}</div>
                </div>
                <div class="row">
                  <div class="col-6">QR</div>
                  <div class="col-6 text-right">{{ currency(totalesMetodo.egresos.QR) }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-4">
            <q-card bordered>
              <q-card-section>
                <div class="text-weight-bold">Neto por método</div>
                <div class="row q-mt-xs">
                  <div class="col-6">EFECTIVO</div>
                  <div class="col-6 text-right">{{ currency(totalesMetodo.neto.EFECTIVO) }}</div>
                </div>
                <div class="row">
                  <div class="col-6">QR</div>
                  <div class="col-6 text-right">{{ currency(totalesMetodo.neto.QR) }}</div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner size="32px" />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'LibroDiario',
  data () {
    return {
      fecha: moment().format('YYYY-MM-DD'),
      openingAmount: 0,

      ingresos: {
        ordenes: { total: 0, items: [] },
        pagos_ordenes: { total: 0, items: [] },
        pagos_prestamos: { total: 0, items: [] }
      },

      egresos: {
        prestamos: { total: 0, items: [] }
      },

      totalesMetodo: {
        ingresos: { EFECTIVO: 0, QR: 0 },
        egresos:  { EFECTIVO: 0, QR: 0 },
        neto:     { EFECTIVO: 0, QR: 0 }
      },

      totalIngresos: 0, // incluye caja inicial (compat)
      totalEgresos: 0,
      totalCaja: 0,     // caja + ingresos - egresos

      loading: false,
      loadingSave: false
    }
  },
  mounted () { this.fetchDiario() },
  methods: {
    currency (n) { return `${Number(n || 0).toFixed(2)} Bs.` },
    sumMetodo (items, metodo) {
      return (items || []).reduce((s, x) => s + (x.metodo === metodo ? Number(x.monto || 0) : 0), 0)
    },
    async fetchDiario () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('daily-cash', { params: { date: this.fecha } })

        this.openingAmount = Number(data.daily_cash?.opening_amount || 0)

        // Normaliza claves esperadas (ingresos y egresos)
        this.ingresos = {
          ordenes:         data.ingresos?.ordenes         || { total: 0, items: [] },
          pagos_ordenes:   data.ingresos?.pagos_ordenes   || { total: 0, items: [] },
          pagos_prestamos: data.ingresos?.pagos_prestamos || { total: 0, items: [] }
        }
        this.egresos = {
          prestamos: data.egresos?.prestamos || { total: 0, items: [] }
        }

        // Totales globales
        this.totalIngresos = Number(data.total_ingresos || 0)
        this.totalEgresos  = Number(data.total_egresos  || 0)
        this.totalCaja     = Number(data.total_caja     || (this.totalIngresos - this.totalEgresos))

        // Totales por método
        const tm = data.totales_metodo || {}
        this.totalesMetodo = {
          ingresos: { EFECTIVO: tm.ingresos?.EFECTIVO || 0, QR: tm.ingresos?.QR || 0 },
          egresos:  { EFECTIVO: tm.egresos?.EFECTIVO  || 0, QR: tm.egresos?.QR  || 0 },
          neto:     { EFECTIVO: tm.neto?.EFECTIVO     || 0, QR: tm.neto?.QR     || 0 }
        }
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al cargar el libro diario')
      } finally {
        this.loading = false
      }
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
    }
  }
}
</script>
