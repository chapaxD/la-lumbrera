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
import Compras from '../components/Compras/Compras'
import HistorialStock from '../components/Insumos/HistorialStock'
import HistorialCajas from '@/components/Caja/HistorialCajas.vue'
import Cocina from '../components/Cocina/Cocina'
import ReporteCancelaciones from '../components/Ventas/ReporteCancelaciones'
import Factura from '../components/Ventas/Factura'
import HistorialFacturas from '../components/Ventas/HistorialFacturas'
import Clientes from '../components/Clientes/Clientes'

Vue.use(Router)

const router = new Router({
  routes: [
    {
      path: '/',
      name: 'Inicio',
      component: Inicio
    },
    {
      path: '/insumos',
      name: 'Insumos',
      component: Insumos
    },
    {
      path: '/configurar',
      name: 'Configurar',
      component: Configurar
    },
    {
      path: '/realizar-orden',
      name: 'RealizarOrden',
      component: RealizarOrden
    },
    {
      path: '/ordenar/:id',
      name: 'Ordenar',
      component: Ordenar,
      props: true
    },

    {
      path: '/registrar-insumo',
      name: 'RegistrarInsumo',
      component: RegistrarInsumo
    },
    {
      path: '/categorias',
      name: 'Categorias',
      component: Categorias
    },
    {
      path: '/editar-insumo/:id',
      name: 'EditarInsumo',
      component: EditarInsumo,
    },
    {
      path: '/usuarios',
      name: 'Usuarios',
      component: Usuarios
    },
    {
      path: '/perfil',
      name: 'Perfil',
      component: Perfil
    },
    {
      path: '/cambiar-password',
      name: 'CambiarPassword',
      component: CambiarPassword
    },
    {
      path: '/registrar-usuario',
      name: 'RegistrarUsuario',
      component: RegistrarUsuario
    },
    {
      path: '/editar-usuario/:id',
      name: 'EditarUsuario',
      component: EditarUsuario,
    },
    {
      path: '/reporte-ventas',
      name: 'ReporteVentas',
      component: ReporteVentas
    },
    {
      path: '/compras',
      name: 'Compras',
      component: Compras
    },
    {
      path: '/historial-stock',
      name: 'HistorialStock',
      component: HistorialStock
    },
    {
      path: '/historial-cajas',
      name: 'HistorialCajas',
      component: HistorialCajas
    },
    {
      path: '/cancelaciones',
      name: 'ReporteCancelaciones',
      component: ReporteCancelaciones
    },
    {
      path: '/factura',
      name: 'Factura',
      component: Factura
    },
    {
      path: '/historial-facturas',
      name: 'HistorialFacturas',
      component: HistorialFacturas
    },
    {
      path: '/clientes',
      name: 'Clientes',
      component: Clientes
    },
    {
      path: '/menu-dia',
      name: 'MenuDia',
      component: () => import('../components/MenuDia/MenuDia.vue')
    },
    {
      path: '/reservas',
      name: 'Reservas',
      component: () => import('@/components/Reservas/Reservas.vue')
    },
    {
      path: '/cocina',
      name: 'Cocina',
      component: Cocina
    }
  ]
})

router.beforeEach((to, from, next) => {
  const rol = localStorage.getItem('rol')
  const adminRoutes = [
    '/insumos',
    '/reporte-ventas',
    '/compras',
    '/historial-stock',
    '/historial-cajas',
    '/cancelaciones',
    '/factura',
    '/historial-facturas',
    '/configurar',
    '/usuarios',
    '/categorias',
    '/agregar-insumo',
    '/editar-insumo',
    '/registrar-usuario',
    '/editar-usuario',
    '/menu-dia'
  ]
  const rutasCompartidas = ['/perfil', '/cambiar-password']

  if (rol === 'cocina') {
    // Cocina solo puede ver su pantalla, perfil y cambiar password
    if (to.path !== '/cocina' && !rutasCompartidas.some(r => to.path.startsWith(r))) {
      next('/cocina')
    } else {
      next()
    }
  } else if (adminRoutes.some(route => to.path.startsWith(route)) && rol !== 'admin') {
    next('/')
  } else {
    next()
  }
})

export default router;
