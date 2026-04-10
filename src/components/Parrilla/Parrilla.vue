<template>
  <section class="section" style="min-height: 70vh;" @click="desbloquearAudioSiHaceFalta">
    <div class="columns is-mobile is-multiline is-vcentered mb-4">
      <div class="column is-12-mobile">
        <p class="title is-1 has-text-weight-bold">
          <b-icon icon="fire" size="is-large" type="is-danger"></b-icon>
          Pantalla Parrilla
        </p>
      </div>
      <div class="column is-12-mobile has-text-right-tablet">
        <div class="buttons is-right">
          <b-button type="is-warning" icon-left="alert-circle-outline" @click="abrirModalReporte">
            Reportar faltante
          </b-button>
        </div>
        <p class="is-size-7 mt-1" :class="conectado ? 'has-text-success' : 'has-text-danger'">
          <b-icon :icon="conectado ? 'wifi' : 'wifi-off'" size="is-small" class="mr-1"></b-icon>
          {{ conectado ? 'Conectado (6s)' : 'Reconectando...' }}
        </p>
        <p v-if="!audioListo" class="is-size-7 has-text-info mt-1">
          Tocá la pantalla una vez (cualquier botón) para activar el sonido de nuevas comandas.
        </p>
      </div>

    </div>
    <!-- Sin órdenes (Empty State) -->
    <div v-if="!cargando && ordenes.length === 0" class="has-text-centered py-6 is-flex is-flex-direction-column is-align-items-center" style="animation: fadeIn 0.5s;">
      <div class="mb-4 p-5" style="border-radius: 50%; background: rgba(255, 221, 87, 0.2); display: inline-block;">
        <b-icon icon="grill" type="is-warning" custom-size="fa-5x" style="font-size: 5rem; opacity: 0.9;"></b-icon>
      </div>
      <p class="title is-3 mt-3 has-text-warning-dark">¡Parrilla despejada!</p>
      <p class="subtitle is-5 has-text-grey mt-2">Aprovecha para avivar las brasas y cuchillos. 🔥<br><br><small>Esperando nuevas carnes...</small></p>
    </div>
    <div class="columns is-multiline">
      <div class="column is-4-widescreen is-6-tablet is-12-mobile" v-for="orden in ordenes"
        :key="orden.tipo + '-' + orden.id">
        <div class="card cocina-card" :class="claseUrgencia(minutosEspera(orden.horaInicio), orden.todoListo)">
          <div class="cocina-card-header">
            <div class="cocina-card-titulo">
              <b-icon :icon="orden.tipo === 'LOCAL' ? 'table-chair' : orden.tipo === 'LLEVAR' ? 'walk' : 'moped'"
                size="is-medium" class="mr-2 cocina-icono-tipo">
              </b-icon>
              <div class="cocina-titulo-texto">
                <span class="is-size-5 has-text-weight-bold">
                  <span v-if="orden.tipo === 'LOCAL'">Mesa {{ orden.id }}</span>
                  <span v-else-if="orden.tipo === 'LLEVAR'">Para llevar #{{ orden.id }}</span>
                  <span v-else>Delivery #{{ orden.id }}</span>
                </span>
                <span class="is-size-6 has-text-grey cocina-cliente" v-if="orden.cliente && orden.cliente !== 'S/N'">
                  {{ orden.cliente }}
                </span>
              </div>
            </div>
            <div class="cocina-card-tags">
              <b-tag v-if="orden.horaInicio" :type="colorEspera(minutosEspera(orden.horaInicio))" rounded>
                <b-icon icon="clock-outline" size="is-small" class="mr-1"></b-icon>
                {{ minutosEspera(orden.horaInicio) }}m
              </b-tag>
              <b-tag :type="orden.todoListo ? 'is-success' : 'is-danger'" rounded>
                {{ orden.pendientes }} pendiente{{ orden.pendientes !== 1 ? 's' : '' }}
              </b-tag>
            </div>
          </div>
          <div class="card-content p-3">
            <div v-for="insumo in orden.insumos" :key="insumo._originalIndex" class="box p-3 mb-2" :class="{
              'has-background-danger-light': insumo.estado === 'pendiente',
              'has-background-success-light': insumo.estado === 'listo'
            }">
              <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px; flex-wrap:wrap;">
                <b-icon :icon="insumo.estado === 'listo' ? 'check-circle' : 'clock-alert-outline'"
                  :type="insumo.estado === 'listo' ? 'is-success' : 'is-danger'" style="flex-shrink:0;">
                </b-icon>
                <span class="has-text-weight-bold is-size-5" style="flex:1; min-width:0; word-break:break-word;">
                  {{ insumo.cantidad }}x {{ insumo.nombre }}
                </span>
                <div style="flex-shrink:0; margin-left:4px;">
                  <b-button v-if="insumo.estado === 'pendiente'" type="is-success" size="is-small" icon-left="check"
                    :loading="insumo.cargando" @click="marcarListo(orden, insumo)">
                    Carne Lista
                  </b-button>
                  <b-tag v-else type="is-success is-light">Carne Lista ✓</b-tag>
                </div>
              </div>
              <div v-if="insumo.acompanamiento_listo === 1" class="mb-2">
                <b-tag type="is-info is-light" size="is-small" icon="silverware-fork-knife">
                  Acompañamiento: LISTO
                </b-tag>
              </div>
              <p v-if="insumo.caracteristicas && insumo.caracteristicas.trim() !== ''"
                class="is-size-6 has-text-dark ml-5 mt-1" style="border-left: 3px solid #f5a623; padding-left: 8px;">
                <b-icon icon="note-text-outline" size="is-small"></b-icon>
                {{ insumo.caracteristicas }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Reportar faltante -->
    <b-modal v-model="modalReporte" has-modal-card trap-focus :destroy-on-hide="false">
      <div class="modal-card" style="min-width: 420px; max-width: 500px;">
        <header class="modal-card-head">
          <p class="modal-card-title">
            <b-icon icon="alert-circle-outline" type="is-warning"></b-icon>
            Reportar faltante o problema
          </p>
          <button class="delete" @click="modalReporte = false"></button>
        </header>
        <section class="modal-card-body">
          <b-field label="Insumo (opcional — escribí el nombre)">
            <b-autocomplete v-model="reporte.nombreInsumo" :data="insumosFiltrados" field="nombre"
              placeholder="Ej: Chorizo parrillero..." @typing="buscarInsumo" @select="seleccionarInsumo" clearable>
            </b-autocomplete>
          </b-field>
          <b-field label="Tipo de problema">
            <b-select v-model="reporte.tipo" expanded>
              <option value="FALTANTE">Faltante — se agotó</option>
              <option value="BAJO_STOCK">Bajo stock — queda poco</option>
              <option value="VENCIDO">Vencido / en mal estado</option>
              <option value="OTRO">Otro</option>
            </b-select>
          </b-field>
          <b-field label="Nota adicional">
            <b-input v-model="reporte.nota" type="textarea" rows="3"
              placeholder="Ej: Se terminaron las brasas, reponer por favor...">
            </b-input>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button type="is-warning" icon-left="send" @click="enviarReporte" :loading="enviandoReporte">
            Enviar reporte
          </b-button>
          <b-button type="is-dark" @click="modalReporte = false">Cancelar</b-button>
        </footer>
      </div>
    </b-modal>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
  name: 'Parrilla',
  data: () => ({
    ordenes: [],
    cargando: true,
    ahora: Date.now(),
    intervaloPolling: null,
    intervaloReloj: null,
    modalReporte: false,
    enviandoReporte: false,
    conectado: false,
    insumosFiltrados: [],
    ultimoConteoPendientes: 0,
    pollSonidoInicialHecha: false,
    audioCampana: null,
    audioListo: false,
    reporte: {
      idInsumo: null,
      nombreInsumo: '',
      tipo: 'FALTANTE',
      nota: ''
    }
  }),
  mounted() {
    this.cargarOrdenes()
    this.intervaloPolling = setInterval(() => {
      this.ahora = Date.now()
      this.cargarOrdenes()
    }, 6000)
    this.intervaloReloj = setInterval(() => { this.ahora = Date.now() }, 30000)
  },
  beforeDestroy() {
    clearInterval(this.intervaloPolling)
    clearInterval(this.intervaloReloj)
  },
  methods: {
    getAudioCampana() {
      if (!this.audioCampana) {
        this.audioCampana = new Audio('/static/campana.ogg')
      }
      return this.audioCampana
    },
    desbloquearAudioSiHaceFalta() {
      if (this.audioListo) return
      const a = this.getAudioCampana()
      const vol = a.volume
      a.volume = 0.01
      a.play()
        .then(() => {
          a.pause()
          a.currentTime = 0
          a.volume = 0.6
          this.audioListo = true
        })
        .catch(() => {
          a.volume = vol
        })
    },
    reproducirSonido() {
      try {
        const audio = this.getAudioCampana()
        audio.currentTime = 0
        audio.volume = 0.6
        audio.play().catch(e => console.warn('Sonido bloqueado por navegador:', e))
      } catch (e) {
        console.warn('No se pudo reproducir alerta:', e)
      }
    },
    async cargarOrdenes() {
      try {
        const [mesas, deliveries] = await Promise.all([
          HttpService.obtener('obtener_mesas.php'),
          HttpService.obtener('obtener_deliveries.php')
        ])
        this.conectado = true
        this.ordenes = this.procesarDatos(mesas, deliveries)
        
        const pendientesActuales = this.ordenes.reduce((total, orden) => total + orden.pendientes, 0)
        const subieronPendientes = pendientesActuales > this.ultimoConteoPendientes
        if (this.pollSonidoInicialHecha && subieronPendientes) {
          this.reproducirSonido()
        }
        this.ultimoConteoPendientes = pendientesActuales
        this.pollSonidoInicialHecha = true
      } catch (e) {
        this.conectado = false
      } finally {
        this.cargando = false
      }
    },
    procesarDatos(mesas, deliveries) {
      // Solo insumos de categoría Carnes
      const filtrarCarnes = insumos => (insumos || []).filter(i => (i.categoria || '').toLowerCase() === 'carnes')
      const ordenesLocales = (mesas || [])
        .filter(m => m.mesa && (m.mesa.estado === 'ocupada' || m.mesa.estado === 'pagada'))
        .map(m => {
          // enviamos todos los insumos
          const insumos = (m.insumos || [])
            .map((insumo, idx) => ({ ...insumo, _originalIndex: idx, cargando: false }))
            .filter(i => (i.categoria || '').toLowerCase() === 'carnes' && i.estado !== 'entregado')
          return {
            id: m.mesa.idMesa,
            tipo: 'LOCAL',
            cliente: m.mesa.cliente,
            horaInicio: m.mesa.created_at || null,
            pagada: m.mesa.estado === 'pagada',
            insumos,
            pendientes: insumos.filter(i => i.estado === 'pendiente').length,
            todoListo: insumos.length > 0 && insumos.every(i => i.estado === 'listo')
          }
        })
        .filter(o => o.insumos.length > 0)
      const ordenesDelivery = (deliveries || []).map(d => {
        const insumos = (d.insumos || [])
          .map((insumo, idx) => ({ ...insumo, _originalIndex: idx, cargando: false }))
          .filter(i => (i.categoria || '').toLowerCase() === 'carnes' && i.estado !== 'entregado')
        return {
          id: d.delivery.idDelivery,
          tipo: 'DELIVERY',
          cliente: d.delivery.cliente,
          horaInicio: d.delivery.created_at || null,
          pagada: d.delivery.estado_orden === 'pagada',
          insumos,
          pendientes: insumos.filter(i => i.estado === 'pendiente').length,
          todoListo: insumos.length > 0 && insumos.every(i => i.estado === 'listo')
        }
      }).filter(o => o.insumos.length > 0)
      return [...ordenesLocales, ...ordenesDelivery]
    },
    async marcarListo(orden, insumo) {
      insumo.cargando = true
      try {
        await HttpService.registrar({
          tipo: orden.tipo,
          id: orden.id,
          indiceInsumo: insumo._originalIndex,
          soloAcompanamiento: false
        }, 'marcar_listo_cocina.php')
        insumo.estado = 'listo'
        orden.pendientes = orden.insumos.filter(i => i.estado === 'pendiente').length
        orden.todoListo = orden.insumos.length > 0 && orden.insumos.every(i => i.estado === 'listo')
      } finally {
        insumo.cargando = false
      }
    },
    minutosEspera(horaInicio) {
      if (!horaInicio) return 0
      // Normalizamos el formato para evitar problemas de interpretación UTC vs Local
      let fechaStr = horaInicio.replace(' ', 'T')
      const inicio = new Date(fechaStr).getTime()
      let minutos = Math.floor((this.ahora - inicio) / 60000)

      // Si la diferencia es muy grande o negativa (ej. -240 min), compensamos zona horaria
      if (minutos < -5 || minutos > 720) {
        const offsetMin = new Date().getTimezoneOffset()
        // Intentamos corregir el desfase restando el offset del navegador
        minutos = Math.floor((this.ahora - (inicio - offsetMin * 60000)) / 60000)
      }

      return minutos < 0 ? 0 : minutos
    },
    colorEspera(mins) {
      if (mins < 10) return 'is-success'
      if (mins < 20) return 'is-warning'
      return 'is-danger'
    },

    claseUrgencia(mins, todoListo) {
      if (todoListo) return 'cocina-pagada-lista'
      if (mins >= 20) return 'urgencia-roja'
      if (mins >= 10) return 'urgencia-naranja'
      return 'urgencia-verde'
    },
    abrirModalReporte() {
      this.reporte = { idInsumo: null, nombreInsumo: '', tipo: 'FALTANTE', nota: '' }
      this.insumosFiltrados = []
      this.modalReporte = true
    },
    async buscarInsumo(texto) {
      if (!texto || texto.length < 2) {
        this.insumosFiltrados = []
        return
      }
      const resultado = await HttpService.registrar({ insumo: texto }, 'obtener_insumo_nombre.php')
      this.insumosFiltrados = resultado || []
    },
    seleccionarInsumo(insumo) {
      if (insumo) {
        this.reporte.idInsumo = insumo.id
        this.reporte.nombreInsumo = insumo.nombre
      }
    },
    async enviarReporte() {
      if (!this.reporte.nombreInsumo.trim() && !this.reporte.nota.trim()) {
        this.$buefy.toast.open({ message: 'Indicá el insumo o describí el problema', type: 'is-warning' })
        return
      }
      this.enviandoReporte = true
      try {
        await HttpService.registrar({
          ...this.reporte,
          idUsuario: localStorage.getItem('idUsuario')
        }, 'registrar_reporte_cocina.php')
        this.$buefy.toast.open({ message: '✓ Reporte enviado al administrador', type: 'is-success', duration: 4000 })
        this.modalReporte = false
      } finally {
        this.enviandoReporte = false
      }
    }
  }
}
</script>

<style scoped>
.urgencia-verde {
    background-color: #f5fff5;
    border-top: 5px solid #48c774;
}
.urgencia-naranja {
    background-color: #fff9f0;
    border-top: 5px solid #ffdd57;
}
.urgencia-roja {
    background-color: #fff0f0;
    border-top: 5px solid #f14668;
    animation: pulso-rojo 2s infinite;
}
@keyframes pulso-rojo {
    0% { box-shadow: 0 0 0 0 rgba(241, 70, 104, 0.4); }
    70% { box-shadow: 0 0 0 15px rgba(241, 70, 104, 0); }
    100% { box-shadow: 0 0 0 0 rgba(241, 70, 104, 0); }
}
</style>
