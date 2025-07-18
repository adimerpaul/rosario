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
