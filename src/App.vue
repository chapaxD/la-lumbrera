<template>
  <div id="app">
    <configuracion-inicial v-if="configurar"/>
    <login @logeado="onLog" v-if="!logeado && !configurar"></login>
    <cambiar-password v-if="cambiarPassword"></cambiar-password>
    <div v-if="logeado && !cambiarPassword">
      <encabezado @cerrar="onClose"/> 
      <div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
        <router-view/>
      </div> 
      <pie /> 
    </div>
  </div>
</template>

<script>
import Login from './components/Usuarios/Login.vue'
import CambiarPassword from './components/Usuarios/CambiarPassword.vue'
import ConfiguracionInicial from './components/Configuracion/ConfiguracionInicial.vue'
import Encabezado from './components/Encabezado.vue'
import Pie from './components/Pie.vue'
import HttpService from './Servicios/HttpService'
import TemaService from './Servicios/TemaService'

export default {
  components: { Encabezado, Pie, Login, CambiarPassword, ConfiguracionInicial },
  name: 'App',

  data: ()=> ({
    logeado: false,
    datos: "",
    cambiarPassword: false,
    configurar: false
  }),

  mounted(){
    TemaService.init()
    this.verificarInformacion()
    let logeado = this.verificarSesion()
    if(logeado) {
      this.logeado = true
    }
  },

  methods: {
    verificarInformacion(){
      HttpService.obtener("verificar_tablas.php")
      .then(resultado => {
        if(resultado.resultado > 0){
          this.configurar = false
          return
        }

        this.configurar = true
        return
      })
      .catch(err => {
        console.error('Error al verificar tablas:', err)
        this.configurar = false
      })
    },

    verificarSesion(){
      let token = localStorage.getItem('jwt_token')
      if(token) {
        return token
      }
      return false
    },

    onLog(logeado){
      if(logeado.resultado === "cambia"){
        this.cambiarPassword = true
        this.logeado = true
        localStorage.setItem('jwt_token', logeado.token)
        localStorage.setItem('nombreUsuario', logeado.datos.nombreUsuario)
        localStorage.setItem('idUsuario', logeado.datos.idUsuario)
        localStorage.setItem('rol', logeado.datos.rol)
        return
      }

      if(logeado.resultado){
        this.logeado = true
        localStorage.setItem('jwt_token', logeado.token)
        localStorage.setItem('nombreUsuario', logeado.datos.nombreUsuario)
        localStorage.setItem('idUsuario', logeado.datos.idUsuario)
        localStorage.setItem('rol', logeado.datos.rol)

        const rutas = { cocina: '/cocina', mesero: '/realizar-orden', admin: '/' }
        const destino = rutas[logeado.datos.rol] || '/'
        if (this.$router.currentRoute.path !== destino) {
          this.$router.push(destino)
        }
      }
      
    },

    onClose(logeado){
      this.logeado = logeado
    }
  }
}
</script>

<style>
:root {
  --color-primario: #7957d5;
  --color-primario-oscuro: #6248b5;
  --color-primario-claro: #9a7ee0;
  --color-navbar: #7957d5;
  --color-pie: #2e1a6e;
  --color-fondo: #f5f0ff;
}

body {
  background-color: var(--color-fondo) !important;
}

/* Navbar */
.navbar.is-primary {
  background-color: var(--color-navbar) !important;
}
.navbar.is-primary .navbar-brand .navbar-item,
.navbar.is-primary .navbar-brand .navbar-link,
.navbar.is-primary > .navbar-menu > .navbar-item,
.navbar.is-primary > .navbar-menu > .navbar-link,
.navbar.is-primary .navbar-start > .navbar-item,
.navbar.is-primary .navbar-start > .navbar-link,
.navbar.is-primary .navbar-end > .navbar-item,
.navbar.is-primary .navbar-end > .navbar-link {
  color: #fff !important;
}

/* Dropdown items: fondo blanco, texto oscuro */
.navbar.is-primary .navbar-dropdown .navbar-item {
  color: #363636 !important;
  background-color: transparent !important;
}
.navbar.is-primary .navbar-dropdown .navbar-item:hover,
.navbar.is-primary .navbar-dropdown .navbar-item:focus {
  background-color: var(--color-primario-claro) !important;
  color: #363636 !important;
}

/* Footer */
footer.fondo {
  background-color: var(--color-pie) !important;
}

/* Buttons */
.button.is-primary {
  background-color: var(--color-primario) !important;
  border-color: var(--color-primario) !important;
}
.button.is-primary:hover,
.button.is-primary:focus {
  background-color: var(--color-primario-oscuro) !important;
  border-color: var(--color-primario-oscuro) !important;
}

/* Icons / text */
.has-text-primary {
  color: var(--color-primario) !important;
}

/* Bulma menu-list (MenuDia sidebar) */
.menu-list a:hover {
  background-color: var(--color-primario-claro) !important;
  color: var(--color-primario) !important;
}
.menu-list a.is-active,
.menu-list a.is-active:hover {
  background-color: var(--color-primario) !important;
  color: #fff !important;
}

/* Tags / badges */
.tag.is-primary {
  background-color: var(--color-primario) !important;
  color: #fff !important;
}

/* Pagination */
.pagination-link.is-current {
  background-color: var(--color-primario) !important;
  border-color: var(--color-primario) !important;
}

/* Steps indicator */
.steps .step-item.is-active .step-marker {
  background-color: var(--color-primario) !important;
  border-color: var(--color-primario) !important;
}

/* Navbar en móvil: fondo coloreado para que el texto blanco sea visible */
@media screen and (max-width: 1023px) {
  .navbar.is-primary .navbar-menu {
    background-color: var(--color-navbar) !important;
  }
  .navbar.is-primary .navbar-menu .navbar-item,
  .navbar.is-primary .navbar-menu .navbar-link {
    color: #fff !important;
  }
  .navbar.is-primary .navbar-menu .navbar-item:hover,
  .navbar.is-primary .navbar-menu .navbar-link:hover,
  .navbar.is-primary .navbar-menu .has-dropdown > .navbar-link:hover,
  .navbar.is-primary .navbar-menu .has-dropdown > .navbar-link:focus,
  .navbar.is-primary .navbar-menu .has-dropdown.is-active > .navbar-link {
    background-color: rgba(0,0,0,0.15) !important;
    color: #fff !important;
  }
  .navbar.is-primary .navbar-menu .has-dropdown > .navbar-link {
    color: #fff !important;
  }
  .navbar.is-primary .navbar-menu .has-dropdown > .navbar-link::after {
    border-color: #fff !important;
  }
  .navbar.is-primary .navbar-dropdown {
    background-color: var(--color-primario-oscuro) !important;
    border-top: none !important;
    box-shadow: none !important;
  }
  .navbar.is-primary .navbar-dropdown .navbar-item {
    color: #fff !important;
    background-color: transparent !important;
  }
  .navbar.is-primary .navbar-dropdown .navbar-item:hover,
  .navbar.is-primary .navbar-dropdown .navbar-item:focus {
    background-color: rgba(0,0,0,0.15) !important;
    color: #fff !important;
  }
  .navbar.is-primary .navbar-dropdown hr.navbar-divider {
    background-color: rgba(255,255,255,0.2) !important;
  }

  /* Títulos de secciones más pequeños en móvil */
  .title.is-1 {
    font-size: 1.5rem !important;
  }
  .title.is-2 {
    font-size: 1.25rem !important;
  }
  .title.is-3 {
    font-size: 1.1rem !important;
  }
}
</style>

