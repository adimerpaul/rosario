<template>
  <q-layout view="hHh Lpr lFf">
    <q-header class="bg-white text-primary" bordered>
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title>
          <div class="header-brand">
            <div class="header-brand__eyebrow">Joyeria Rosario</div>
            <div class="header-brand__title">Panel operativo</div>
          </div>
        </q-toolbar-title>

        <div>
          <q-btn-group flat>
            <q-btn no-caps icon="o_account_circle" dense :label="$store.user.username || $store.user.name">
              <q-menu>
                <q-list>
                  <q-item clickable>
                    <q-item-section avatar>
                      <q-icon name="account_circle" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>
                        {{ $store.user.username || $store.user.name }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ $store.user.role }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable @click="logout" v-close-popup>
                    <q-item-section avatar>
                      <q-icon name="exit_to_app" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Salir</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-btn-group>
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :width="220"
      :breakpoint="700"
      class="drawer-shell text-white"
    >
      <div class="drawer-content">
        <div class="drawer-profile">
          <div class="drawer-profile__avatar">
            <q-img
              src="/logo-light.png"
              width="28px"
              height="28px"
              fit="contain"
              no-spinner
            />
          </div>
          <div class="drawer-profile__info">
            <div class="drawer-profile__name">
              {{ $store.user.username || $store.user.name }}
            </div>
            <q-chip dense text-color="white" color="red-4" class="drawer-profile__role">
              {{ $store.user.role }}
            </q-chip>
          </div>
        </div>

        <div class="drawer-section-label">
          Navegacion
        </div>

        <q-list dense class="drawer-list">
          <template v-for="section in visibleSections" :key="section.title">
            <q-expansion-item
              dense
              dense-toggle
              expand-separator
              :default-opened="sectionIsActive(section)"
              :icon="section.icon"
              :label="section.title"
              :header-class="sectionIsActive(section) ? 'drawer-group drawer-group--active' : 'drawer-group'"
              class="drawer-expansion"
            >
              <q-list dense class="q-px-xs q-pb-xs">
                <q-item
                  v-for="link in section.links"
                  :key="link.title"
                  clickable
                  :to="link.link"
                  exact
                  class="drawer-link"
                  :active="linkIsActive(link)"
                  active-class="menu"
                >
                  <q-item-section avatar class="drawer-link__avatar">
                    <q-icon
                      :name="linkIsActive(link) ? link.activeIcon || link.icon : link.icon"
                      :class="linkIsActive(link) ? 'text-white' : 'text-red-1'"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label :class="linkIsActive(link) ? 'text-white text-weight-bold' : 'text-red-1'">
                      {{ link.title }}
                    </q-item-label>
                    <q-item-label caption :class="linkIsActive(link) ? 'text-red-1' : 'text-red-2'">
                      {{ link.caption }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-expansion-item>
          </template>
        </q-list>

        <q-item clickable class="drawer-logout" @click="logout">
          <q-item-section avatar>
            <q-icon name="exit_to_app" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Salir</q-item-label>
          </q-item-section>
        </q-item>
      </div>
    </q-drawer>

    <q-page-container class="bg-grey-3">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
export default {
  name: 'MainLayout',
  data () {
    return {
      leftDrawerOpen: true,
      menuSections: [
        {
          title: 'Principal',
          icon: 'space_dashboard',
          links: [
            { title: 'Inicio', caption: 'Resumen principal', icon: 'dashboard', activeIcon: 'dashboard', link: '/', can: 'Todos' },
            { title: 'Usuarios', caption: 'Gestion del sistema', icon: 'group', activeIcon: 'group', link: '/usuarios', can: 'Administrador' },
            { title: 'Clientes', caption: 'Registro de clientes', icon: 'badge', activeIcon: 'badge', link: '/clientes', can: ['Administrador', 'Vendedor'] },
            { title: 'Configuracion', caption: 'Parametros generales', icon: 'settings', activeIcon: 'settings', link: '/configuraciones', can: 'Administrador' },
            { title: 'Reportes', caption: 'Consultas del negocio', icon: 'print', activeIcon: 'print', link: '/reportes', can: 'Administrador' }
          ]
        },
        {
          title: 'Ordenes',
          icon: 'receipt_long',
          links: [
            { title: 'Crear orden', caption: 'Nueva orden de joya', icon: 'edit_square', activeIcon: 'edit_square', link: '/ordenes/crear', can: ['Administrador', 'Vendedor'] },
            { title: 'Lista de ordenes', caption: 'Seguimiento y control', icon: 'list_alt', activeIcon: 'list_alt', link: '/ordenes', can: ['Administrador', 'Vendedor'] },
            { title: 'Ordenes retrasadas', caption: 'Ordenes con atraso', icon: 'pending_actions', activeIcon: 'pending_actions', link: '/ordenes-retrasadas', can: 'Administrador' }
          ]
        },
        {
          title: 'Prestamos',
          icon: 'account_balance_wallet',
          links: [
            { title: 'Crear prestamo', caption: 'Registrar prestamo', icon: 'credit_score', activeIcon: 'credit_score', link: '/prestamos/crear', can: ['Administrador', 'Vendedor'] },
            { title: 'Lista de prestamos', caption: 'Pagos y seguimiento', icon: 'payments', activeIcon: 'payments', link: '/prestamos', can: ['Administrador', 'Vendedor'] },
            { title: 'Prestamos retrasados', caption: 'Prestamos vencidos', icon: 'event_busy', activeIcon: 'event_busy', link: '/prestamos-retrasados', can: 'Administrador' }
          ]
        },
        {
          title: 'Inventario y ventas',
          icon: 'diamond',
          links: [
            { title: 'Ingresar joyas', caption: 'Catalogo de joyas', icon: 'diamond', activeIcon: 'diamond', link: '/joyas', can: ['Administrador', 'Vendedor'] },
            { title: 'Estuches', caption: 'Control de estuches', icon: 'inventory_2', activeIcon: 'inventory_2', link: '/estuches', can: ['Administrador', 'Vendedor'] },
            { title: 'Crear venta', caption: 'Nueva venta', icon: 'point_of_sale', activeIcon: 'point_of_sale', link: '/ventas-joyas/crear', can: ['Administrador', 'Vendedor'] },
            { title: 'Lista de ventas', caption: 'Historial de ventas', icon: 'sell', activeIcon: 'sell', link: '/ventas-joyas', can: ['Administrador', 'Vendedor'] },
            { title: 'Almacen', caption: 'Movimientos de almacen', icon: 'store', activeIcon: 'store', link: '/almacen', can: ['Administrador', 'Vendedor'] }
          ]
        },
        {
          title: 'Libro diario',
          icon: 'menu_book',
          links: [
            { title: 'Libro diario', caption: 'Control diario de caja', icon: 'auto_stories', activeIcon: 'auto_stories', link: '/libro-diario', can: ['Administrador', 'Vendedor'] }
          ]
        }
      ]
    }
  },
  computed: {
    visibleSections () {
      return this.menuSections
        .map(section => ({
          ...section,
          links: section.links.filter(this.canAccess)
        }))
        .filter(section => section.links.length > 0)
    }
  },
  methods: {
    canAccess (link) {
      if (!link || !link.can) return false
      if (link.can === 'Todos') return true
      if (Array.isArray(link.can)) {
        return link.can.includes(this.$store.user.role)
      }
      return this.$store.user.role === link.can
    },
    linkIsActive (link) {
      return this.$route.path === link.link
    },
    sectionIsActive (section) {
      return section.links.some(link => this.linkIsActive(link))
    },
    logout () {
      this.$alert.dialog('¿Desea salir del sistema?')
        .onOk(() => {
          this.$axios.post('/logout')
            .then(() => {
              this.$store.isLogged = false
              this.$store.user = {}
              localStorage.removeItem('tokenAsistencia')
              this.$router.push('/login')
            })
            .catch(error => {
              console.error('Error al cerrar sesion:', error)
              this.$alert.error('Error al cerrar sesion. Intente nuevamente.')
            })
        })
    },
    toggleLeftDrawer () {
      this.leftDrawerOpen = !this.leftDrawerOpen
    }
  }
}
</script>

<style>
.header-brand {
  line-height: 1.1;
}

.header-brand__eyebrow {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #c62828;
}

.header-brand__title {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.drawer-shell {
  background: linear-gradient(180deg, #9f1239 0%, #7f1d1d 100%);
}

.drawer-shell .q-drawer__content {
  background: linear-gradient(180deg, #9f1239 0%, #7f1d1d 100%);
}

.drawer-content {
  min-height: 100%;
  padding: 12px 10px 16px;
}

.drawer-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  margin-bottom: 10px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(6px);
}

.drawer-profile__avatar {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.14);
  flex-shrink: 0;
}

.drawer-profile__info {
  min-width: 0;
}

.drawer-profile__name {
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 4px;
  color: #fff5f5;
}

.drawer-profile__role {
  margin-left: 0;
}

.drawer-section-label {
  padding: 2px 8px 8px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 235, 238, 0.76);
}

.drawer-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.drawer-expansion .q-expansion-item__container {
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.08);
}

.drawer-expansion .q-item,
.drawer-expansion .q-item__label,
.drawer-expansion .q-icon {
  color: #fff5f5;
}

.drawer-expansion .q-expansion-item__toggle-icon {
  color: rgba(255, 235, 238, 0.9);
}

.drawer-expansion .q-expansion-item__content {
  background: rgba(255, 255, 255, 0.04);
}

.drawer-group {
  margin: 0;
  color: white;
  font-weight: 600;
}

.drawer-group--active {
  background: rgba(255, 255, 255, 0.12);
}

.drawer-link {
  min-height: 42px;
  border-radius: 10px;
  margin: 2px 0;
}

.drawer-link__avatar {
  min-width: 34px;
}

.menu {
  background-color: #ef4444;
  border-radius: 10px;
}

.drawer-logout {
  margin-top: 12px;
  border-radius: 12px;
  background: rgba(244, 67, 54, 0.18);
  color: #fff5f5;
}
</style>
