const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {path: '', component: () => import('pages/IndexPage.vue'), meta: {requiresAuth: true}},
      {path: '/usuarios', component: () => import('pages/usuarios/Usuarios.vue'), meta: {requiresAuth: true}},
      {path: '/clientes', component: () => import('pages/clientes/Cliente.vue'), meta: {requiresAuth: true}},
      {path: '/ordenes', component: () => import('pages/ordenes/Ordenes.vue'), meta: {requiresAuth: true}},
      {path: '/ordenes/crear', name: 'crearOrden', component: () => import('pages/ordenes/OrdenCrear.vue'), meta: {requiresAuth: true}},
      {
        path: '/ordenes/editar/:id',
        component: () => import('pages/ordenes/OrderEditar.vue'),
        meta: {requiresAuth: true},
      },
      {path: '/configuraciones', component: () => import('pages/configuraciones/Configuraciones.vue'), meta: {requiresAuth: true}},
      {path: '/libro-diario', component: () => import('pages/libro-diario/LibroDiario.vue'), meta: {requiresAuth: true}},
      {
        path: '/prestamos',
        component: () => import('pages/prestamos/Prestamos.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: '/prestamos/crear',
        component: () => import('pages/prestamos/PrestamoCrear.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: '/prestamos/editar/:id',
        name: 'editarPrestamo',
        component: () => import('pages/prestamos/PrestamoEditar.vue'),
        meta: { requiresAuth: true }
      },
      // OrdenesRetrasados.vue
      {
        path: '/ordenes-retrasadas',
        component: () => import('pages/ordenes/OrdenesRetrasados.vue'),
        meta: { requiresAuth: true }
      },
      // reportes
      {
        path: '/reportes',
        component: () => import('pages/reportes/Reportes.vue'),
        meta: { requiresAuth: true }
      },
      // PrestamosRetrasados.vue
      {
        path: '/prestamos-retrasados',
        component: () => import('pages/prestamos/PrestamosRetrasados.vue'),
        meta: { requiresAuth: true }
      }
    ]
  },
  {
    path: '/login',
    component: () => import('layouts/Login.vue'),
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
