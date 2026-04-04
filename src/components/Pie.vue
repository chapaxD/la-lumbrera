<template>
  <footer class="footer fondo">
    <div class="content has-text-centered">

      <!-- Logo -->
      <img 
        src="@/assets/LA LUMBRERA B.png" 
        alt="logo" 
        style="max-height: 80px;"
      >

      <!-- Nombre del negocio (dinámico) -->
      <!-- <p class="has-text-white has-text-weight-bold mt-2">
        {{ datosLocal.nombre || 'LA LUMBRERA' }}
      </p> -->

      <!-- Teléfono -->
      <p class="has-text-white">
        📞 {{ datosLocal.telefono || '+591 77376746' }} 
      </p>

      <!-- Sello del sistema -->
      <div class="mt-3">
        <span class="sello">⚙ Sistema Interno — Uso Confidencial</span>
      </div>

      <!-- Autor -->
      <p class="autor">
        Desarrollado por <strong>RogerAndiaDev</strong> &copy; {{ new Date().getFullYear() }}
      </p>

    </div>
  </footer>
</template>
<script>
import HttpService from '../Servicios/HttpService'

export default {
  name: "Pie",

  data: () => ({
    datosLocal: {},
    anio: new Date().getFullYear()
  }),

  mounted() {
    this.obtenerDatos()
  },

  methods: {
    obtenerDatos() {
      HttpService.obtener("obtener_datos_local.php")
        .then(resultado => {
          this.datosLocal = resultado
        })
        .catch(() => {
          console.warn("No se pudieron cargar los datos")
        })
    }
  }
}
</script>
<style scoped>
.fondo {
  background-color: #1a1a1a;
  padding: 1.5rem 1rem;
}

/* Sello tipo confidencial */
.sello {
  display: inline-block;
  font-size: 11px;
  color: #f0c040;
  border: 1px solid #f0c040;
  border-radius: 4px;
  padding: 2px 10px;
  margin-top: 12px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  opacity: 0.85;
}

/* Autor */
.autor {
  font-size: 12px;
  color: #aaaaaa;
  margin-top: 8px;
}

.autor strong {
  color: #ffffff;
}
</style>
