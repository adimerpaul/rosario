<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <!-- Filtros / Acciones -->
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-3">
          <q-input
            type="date"
            v-model="fecha"
            label="Fecha"
            dense outlined
            @update:model-value="fetchDiario"
          />
        </div>

        <div class="col-12 col-md-3">
          <q-input
            v-model.number="openingAmount"
            type="number" min="0" step="0.01"
            dense outlined
            label="Caja inicial (Bs.)"
            @keyup.enter="guardarCaja"
          />
        </div>

        <div class="col-12 col-md-3 flex items-center">
          <q-btn
            color="primary" icon="save" label="Guardar caja"
            no-caps :loading="loadingSave" @click="guardarCaja"
          />
          <q-btn
            color="grey" flat icon="refresh" label="Actualizar"
            class="q-ml-sm" no-caps :loading="loading" @click="fetchDiario"
          />
        </div>

        <div class="col-12 col-md-3">
          <q-card bordered class="bg-blue-1">
            <q-card-section class="q-pa-sm">
              <div class="text-weight-bold text-blue">TOTAL INGRESOS</div>
              <div class="text-h6">{{ currency(totalIngresos) }}</div>
            </q-card-section>
          </q-card>
        </div>
      </q-card-section>

      <q-separator />

      <!-- INGRESOS -->
      <q-card-section>

        <!-- Caja inicial -->
        <q-card bordered class="q-mb-sm">
          <q-card-section class="row items-center">
            <div class="col-12 col-md-8 text-weight-bold">Caja inicial del día</div>
            <div class="col-12 col-md-4 text-right text-h6">{{ currency(openingAmount) }}</div>
          </q-card-section>
        </q-card>

        <!-- Órdenes de Trabajo -->
        <q-card bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <div class="col-12 col-md-8 text-weight-bold">Órdenes de Trabajo</div>
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
              <th class="text-right">Monto (Bs.)</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="it in ingresos.pagos_ordenes.items" :key="`p-${it.id}`">
              <td class="text-left">{{ it.hora }}</td>
              <td class="text-left">{{ it.descripcion }}</td>
              <td class="text-left">{{ it.usuario }}</td>
              <td class="text-right">{{ currency(it.monto) }}</td>
            </tr>
            <tr v-if="!ingresos.pagos_ordenes.items.length">
              <td colspan="4" class="text-center text-grey">Sin pagos registrados</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card>

        <!-- Totales -->
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-card bordered class="bg-green-1">
              <q-card-section>
                <div class="text-weight-bold text-green-10">TOTAL ÓRDENES</div>
                <div class="text-h6">{{ currency(ingresos.ordenes.total) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-4">
            <q-card bordered class="bg-green-2">
              <q-card-section>
                <div class="text-weight-bold text-green-10">TOTAL PAGOS ÓRDENES</div>
                <div class="text-h6">{{ currency(ingresos.pagos_ordenes.total) }}</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-12 col-md-4">
            <q-card bordered class="bg-blue-1">
              <q-card-section>
                <div class="text-weight-bold text-blue-10">TOTAL INGRESOS (con caja)</div>
                <div class="text-h6">{{ currency(totalIngresos) }}</div>
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
        pagos_ordenes: { total: 0, items: [] }
      },
      totalIngresos: 0,
      loading: false,
      loadingSave: false
    }
  },
  mounted () {
    this.fetchDiario()
  },
  methods: {
    currency (n) {
      const v = Number(n || 0).toFixed(2)
      return `${v} Bs.`
    },
    async fetchDiario () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('daily-cash', { params: { date: this.fecha } })
        this.openingAmount = Number(data.daily_cash?.opening_amount || 0)
        // asegúrate de que siempre existan las claves
        this.ingresos = {
          ordenes: data.ingresos?.ordenes || { total: 0, items: [] },
          pagos_ordenes: data.ingresos?.pagos_ordenes || { total: 0, items: [] }
        }
        this.totalIngresos = Number(data.total_ingresos || 0)
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
