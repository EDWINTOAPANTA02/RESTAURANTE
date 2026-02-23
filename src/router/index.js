import Vue from 'vue'
import Router from 'vue-router'
import HelloWorld from '@/components/HelloWorld'
import Insumos from '../components/Insumos/Insumos'
import Categorias from '../components/Categorias/Categorias'
import RegistrarInsumo from '../components/Insumos/RegistrarInsumo'
import EditarInsumo from '../components/Insumos/EditarInsumo'
import Configurar from '../components/Configuracion/Configurar'
import RealizarOrden from '../components/Ordenar/RealizarOrden'
import Ordenar from '../components/Ordenar/Ordenar'
import Usuarios from '../components/Usuarios/Usuarios'
import RegistrarUsuario from '../components/Usuarios/RegistrarUsuario'
import EditarUsuario from '../components/Usuarios/EditarUsuario'
import Login from '../components/Usuarios/Login'
import Perfil from '../components/Usuarios/Perfil'
import CambiarPassword from '../components/Usuarios/CambiarPassword'
import Inicio from '../components/Inicio'
import ReporteVentas from '../components/Ventas/ReporteVentas'
import Clientes from '../components/Clientes/Clientes'
import RegistrarCliente from '../components/Clientes/RegistrarCliente'
import EditarCliente from '../components/Clientes/EditarCliente'

Vue.use(Router)

const ROLES = {
  ADMIN: 'ADMINISTRADOR',
  CAJERO: 'CAJERO',
  MESERO: 'MESERO'
}

const TODOS = [ROLES.ADMIN, ROLES.CAJERO, ROLES.MESERO]
const SOLO_ADMIN = [ROLES.ADMIN]
const ADMIN_CAJERO = [ROLES.ADMIN, ROLES.CAJERO]
const ADMIN_MESERO = [ROLES.ADMIN, ROLES.MESERO]

const router = new Router({
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: Login,
      meta: { publica: true }
    },
    {
      path: '/',
      name: 'Inicio',
      component: Inicio,
      meta: { roles: TODOS }
    },
    {
      path: '/insumos',
      name: 'Insumos',
      component: Insumos,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/configurar',
      name: 'Configurar',
      component: Configurar,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/realizar-orden',
      name: 'RealizarOrden',
      component: RealizarOrden,
      meta: { roles: TODOS }
    },
    {
      path: '/ordenar/:id',
      name: 'Ordenar',
      component: Ordenar,
      props: true,
      meta: { roles: ADMIN_MESERO }
    },
    {
      path: '/registrar-insumo',
      name: 'RegistrarInsumo',
      component: RegistrarInsumo,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/categorias',
      name: 'Categorias',
      component: Categorias,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/editar-insumo/:id',
      name: 'EditarInsumo',
      component: EditarInsumo,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/usuarios',
      name: 'Usuarios',
      component: Usuarios,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/perfil',
      name: 'Perfil',
      component: Perfil,
      meta: { roles: TODOS }
    },
    {
      path: '/cambiar-password',
      name: 'CambiarPassword',
      component: CambiarPassword,
      meta: { roles: TODOS }
    },
    {
      path: '/registrar-usuario',
      name: 'RegistrarUsuario',
      component: RegistrarUsuario,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/editar-usuario/:id',
      name: 'EditarUsuario',
      component: EditarUsuario,
      meta: { roles: SOLO_ADMIN }
    },
    {
      path: '/clientes',
      name: 'Clientes',
      component: Clientes,
      meta: { roles: ADMIN_CAJERO }
    },
    {
      path: '/registrar-cliente',
      name: 'RegistrarCliente',
      component: RegistrarCliente,
      meta: { roles: ADMIN_CAJERO }
    },
    {
      path: '/editar-cliente/:id',
      name: 'EditarCliente',
      component: EditarCliente,
      props: true,
      meta: { roles: ADMIN_CAJERO }
    },
    {
      path: '/reporte-ventas',
      name: 'ReporteVentas',
      component: ReporteVentas,
      meta: { roles: ADMIN_CAJERO }
    },
  ]
})

// ── Guardia global de navegación ──────────────────────────────────────────────
router.beforeEach((to, from, next) => {
  // Rutas públicas (ej. /login) no necesitan verificación
  if (to.meta && to.meta.publica) {
    return next()
  }

  const logeado = localStorage.getItem('logeado')
  const rol = localStorage.getItem('rol')

  // Si no hay sesión, dejar que App.vue maneje la pantalla de login
  if (!logeado) {
    return next('/')
  }

  // Si la ruta no tiene roles definidos, permitir acceso
  if (!to.meta || !to.meta.roles) {
    return next()
  }

  // Verificar si el rol del usuario está permitido para esta ruta
  if (to.meta.roles.includes(rol)) {
    return next()
  }

  // Rol no autorizado → redirigir al inicio con aviso
  Vue.prototype.$buefy && Vue.prototype.$buefy.toast && Vue.prototype.$buefy.toast.open({
    message: 'No tienes permiso para acceder a esa página.',
    type: 'is-danger',
    duration: 4000
  })
  return next('/')
})

export default router
