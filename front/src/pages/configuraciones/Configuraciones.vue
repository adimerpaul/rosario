<template>
  <q-page class="q-pa-md">
    <q-card flat bordered>
      <q-card-section class="text-h6 text-primary">
        Configuración de Valores
      </q-card-section>
      <q-separator />
      <q-card-section>
        <div class="row q-col-gutter-sm">
          <div v-for="cog in cogsOrdenados" :key="cog.id" class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-subtitle2">{{ cog.name }}</div>
                <div class="text-caption text-grey">{{ cog.description }}</div>
                <q-input
                  v-model.number="cog.value"
                  type="number"
                  dense outlined
                  class="q-mt-sm"
                  :suffix="'Bs'"
                  :debounce="600"
                  @update:model-value="actualizarCog(cog)"
                />
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'ConfiguracionesPage',
  data() {
    return {
      cogs: [],
      loading: false
    };
  },
  computed: {
    cogsOrdenados() {
      const orden = [
        'Precio Compra',
        'Precio Venta',
        'Venta plata',
        'Prestamos para comprar',
        'Tipo de cambio',
        'Joya importada'
      ];

      return [...this.cogs].sort((a, b) => {
        const indiceA = orden.indexOf(a.name);
        const indiceB = orden.indexOf(b.name);
        const ordenA = indiceA === -1 ? Number.MAX_SAFE_INTEGER : indiceA;
        const ordenB = indiceB === -1 ? Number.MAX_SAFE_INTEGER : indiceB;

        if (ordenA !== ordenB) {
          return ordenA - ordenB;
        }

        return a.id - b.id;
      });
    }
  },
  mounted() {
    this.getCogs();
  },
  methods: {
    getCogs() {
      this.loading = true;
      this.$axios.get('cogs')
        .then(res => {
          this.cogs = res.data;
        })
        .catch(() => {
          this.$alert.error('Error al obtener los valores');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    actualizarCog(cog) {
      this.$axios.put(`cogs/${cog.id}`, { value: cog.value })
        .then(() => {
          this.$alert.success(`${cog.name} actualizado`);
        })
        .catch(() => {
          this.$alert.error(`Error al actualizar ${cog.name}`);
        });
    }
  }
};
</script>
