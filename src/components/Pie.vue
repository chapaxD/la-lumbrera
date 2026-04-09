<template>
  <footer class="footer-slim">
    <div class="footer-inner">
      <!-- Izquierda: logo + teléfono -->
      <div class="footer-left">
        <img src="@/assets/LA LUMBRERA B.png" alt="logo" class="footer-logo">
        <span class="footer-phone">📞 {{ datosLocal.telefono || '+591 77376746' }}</span>
      </div>

      <!-- Centro: sello -->
      <div class="footer-center">
        <span class="sello">⚙ Sistema Interno — Uso Confidencial</span>
      </div>

      <!-- Derecha: autor -->
      <div class="footer-right">
        <span class="autor">Desarrollado por <strong>RogerAndiaDev</strong> &copy; {{ new Date().getFullYear() }}</span>
      </div>
    </div>
  </footer>
</template>
<script>
import HttpService from '../Servicios/HttpService'

export default {
  name: "Pie",

  data: () => ({
    datosLocal: {},
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
.footer-slim {
  background-color: var(--color-pie, #1a1a1a);
  padding: 0.6rem 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.08);
}

.footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Izquierda */
.footer-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.footer-logo {
  height: 32px;
  object-fit: contain;
}

.footer-phone {
  font-size: 12px;
  color: #aaaaaa;
}

/* Centro */
.footer-center {
  text-align: center;
}

.sello {
  display: inline-block;
  font-size: 10px;
  color: #f0c040;
  border: 1px solid #f0c040;
  border-radius: 4px;
  padding: 2px 8px;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  opacity: 0.8;
}

/* Derecha */
.footer-right {
  text-align: right;
}

.autor {
  font-size: 11px;
  color: #aaaaaa;
}

.autor strong {
  color: #ffffff;
}
</style>
