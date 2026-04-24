<template>
  <section class="section">
    <div class="container is-fluid">

      <!-- Encabezado -->
      <nav class="level mb-4">
        <div class="level-left">
          <div class="level-item">
            <p class="title is-1 has-text-weight-bold">
              <b-icon icon="history" size="is-large" type="is-primary"></b-icon>
              Historial de Cajas
            </p>
          </div>
        </div>
        <div class="level-right">
          <div class="level-item">
            <b-select v-model="perPage" size="is-small">
              <option value="5">5 por página</option>
              <option value="10">10 por página</option>
              <option value="15">15 por página</option>
              <option value="20">20 por página</option>
            </b-select>
          </div>
          <div class="level-item">
            <b-button type="is-danger" icon-left="file-pdf-box" @click="exportarPDF">Exportar PDF</b-button>
          </div>
          <div class="level-item">
            <b-button type="is-primary" icon-left="filter-variant" @click="filtrar = !filtrar">Filtrar</b-button>
          </div>
        </div>
      </nav>

      <!-- Filtros desplegables -->
      <div class="box mb-4" v-if="filtrar">
        <b-field grouped group-multiline>
          <b-field label="Desde" expanded>
            <b-datepicker v-model="filtros.desde" placeholder="Fecha inicio" icon="calendar" :max-date="hoy" clearable></b-datepicker>
          </b-field>
          <b-field label="Hasta" expanded>
            <b-datepicker v-model="filtros.hasta" placeholder="Fecha fin" icon="calendar" :max-date="hoy" clearable></b-datepicker>
          </b-field>
          <b-field label="Estado" expanded>
            <b-select v-model="filtros.estado" expanded>
              <option value="">Todos</option>
              <option value="CERRADA">Cerradas</option>
              <option value="ABIERTA">Abiertas</option>
            </b-select>
          </b-field>
          <b-field class="is-align-self-flex-end">
            <b-button type="is-light" icon-left="close" @click="limpiarFiltros">Limpiar</b-button>
          </b-field>
        </b-field>
      </div>

      <div class="box">
        <b-table
          :data="cajasFiltradas"
          :loading="cargando"
          :paginated="true"
          :per-page="perPage"
          :current-page.sync="currentPage"
          default-sort="fechaApertura"
          default-sort-direction="desc"
          :bordered="true"
          :striped="true"
        >
          <b-table-column field="id" label="Turno" sortable v-slot="props">
            #{{ props.row.id }}
          </b-table-column>

          <b-table-column field="fechaApertura" label="Apertura" sortable v-slot="props">
            {{ props.row.fechaApertura | formatFecha }}<br>
            <small class="has-text-grey">por: {{ props.row.usuarioApertura }}</small>
          </b-table-column>

          <b-table-column field="fechaCierre" label="Cierre" sortable v-slot="props">
            <span v-if="props.row.estado === 'CERRADA'">
              {{ props.row.fechaCierre | formatFecha }}<br>
              <small class="has-text-grey">por: {{ props.row.usuarioCierre }}</small>
            </span>
            <b-tag type="is-success" v-else>ABIERTA</b-tag>
          </b-table-column>

          <b-table-column field="montoApertura" label="Fondo Inicial" sortable v-slot="props">
            Bs. {{ Math.round(props.row.montoApertura) }}
          </b-table-column>

          <b-table-column field="ventasAcumuladas" label="Ingresos" v-slot="props">
            <div v-if="props.row.estado === 'CERRADA'">
              <p class="is-size-7">Total Ventas: <b>Bs. {{ Math.round(props.row.ventasTotales) }}</b></p>
              <p class="is-size-7 has-text-success">EFECTIVO: <b>Bs. {{ Math.round(parseFloat(props.row.ventasTotales) - parseFloat(props.row.ventasTarjeta || 0) - parseFloat(props.row.ventasQR || 0)) }}</b></p>
              <p class="is-size-7">TARJETA: <b>Bs. {{ Math.round(props.row.ventasTarjeta || 0) }}</b></p>
              <p class="is-size-7">QR: <b>Bs. {{ Math.round(props.row.ventasQR || 0) }}</b></p>
            </div>
            <span v-else class="has-text-grey">--</span>
          </b-table-column>

          <b-table-column field="gastosTotales" label="Egresos (Gastos)" sortable v-slot="props">
             <span v-if="props.row.estado === 'CERRADA'" class="has-text-danger">-Bs. {{ Math.round(props.row.gastosTotales || 0) }}</span>
             <span v-else class="has-text-grey">--</span>
          </b-table-column>
          
          <b-table-column field="diferencia" label="Cuadre" v-slot="props">
            <template v-if="props.row.estado === 'CERRADA'">
              <p class="is-size-7">Físico Registrado: <b>Bs. {{ Math.round(props.row.montoCierre) }}</b></p>
              <p class="mt-1">
                <b-tag :type="calcularDiferencia(props.row) >= 0 ? 'is-success' : 'is-danger'">
                    Diferencia: Bs. {{ Math.round(calcularDiferencia(props.row)) }}
                </b-tag>
              </p>
            </template>
            <span v-else class="has-text-grey">--</span>
          </b-table-column>

          <b-table-column label="Imprimir" v-slot="props">
            <b-button
              v-if="props.row.estado === 'CERRADA'"
              type="is-light"
              size="is-small"
              icon-left="printer"
              :loading="reimprimiendoId === props.row.id"
              @click="reimprimirTicket(props.row)">
              Ticket
            </b-button>
          </b-table-column>

        </b-table>
        <b-loading :is-full-page="false" v-model="cargando"></b-loading>
      </div>
    </div>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'
import ReportesPdfService from '../../Servicios/ReportesPdfService'

export default {
  name: 'HistorialCajas',
  data() {
    return {
      cargando: false,
      cajas: [],
      reimprimiendoId: null,
      perPage: 10,
      currentPage: 1,
      filtrar: false,
      hoy: new Date(),
      filtros: {
        desde: null,
        hasta: null,
        estado: ''
      }
    }
  },
  computed: {
    cajasFiltradas() {
      return this.cajas.filter(c => {
        if (this.filtros.estado && c.estado !== this.filtros.estado) return false
        if (this.filtros.desde) {
          let fecha = new Date(c.fechaApertura)
          let desde = new Date(this.filtros.desde)
          desde.setHours(0, 0, 0, 0)
          if (fecha < desde) return false
        }
        if (this.filtros.hasta) {
          let fecha = new Date(c.fechaApertura)
          let hasta = new Date(this.filtros.hasta)
          hasta.setHours(23, 59, 59, 999)
          if (fecha > hasta) return false
        }
        return true
      })
    }
  },
  mounted() {
    this.obtenerHistorial()
  },
  methods: {
    obtenerHistorial() {
      this.cargando = true
      HttpService.obtener("obtener_historial_cajas.php")
        .then(resultado => {
          this.cajas = resultado || []
          this.cargando = false
        })
        .catch(() => {
          this.cajas = []
          this.cargando = false
          this.$toast({ message: 'Error cargando historial', type: 'is-danger' })
        })
    },
    limpiarFiltros() {
      this.filtros.desde = null
      this.filtros.hasta = null
      this.filtros.estado = ''
    },
    calcularDiferencia(caja) {
      let ingresosEfectivo = parseFloat(caja.ventasTotales) - parseFloat(caja.ventasTarjeta || 0) - parseFloat(caja.ventasQR || 0);
      let esperadoFisico = parseFloat(caja.montoApertura) + ingresosEfectivo - parseFloat(caja.gastosTotales || 0);
      return parseFloat(caja.montoCierre) - esperadoFisico;
    },
    async reimprimirTicket(caja) {
      this.reimprimiendoId = caja.id
      try {
        const gastos = await HttpService.registrar({ idCaja: caja.id }, 'obtener_gastos_caja.php')
        const ventasEfectivo = parseFloat(caja.ventasTotales) - parseFloat(caja.ventasTarjeta || 0) - parseFloat(caja.ventasQR || 0)
        ReportesPdfService.generarCierreCaja({
          fechaApertura:  caja.fechaApertura,
          fechaCierre:    caja.fechaCierre,
          montoApertura:  caja.montoApertura,
          montoCierre:    caja.montoCierre,
          ventasTotales:  caja.ventasTotales,
          ventasEfectivo: ventasEfectivo.toFixed(2),
          ventasTarjeta:  caja.ventasTarjeta || '0.00',
          ventasQR:       caja.ventasQR || '0.00',
          gastosTotal:    caja.gastosTotales || '0.00',
          gastos:         gastos || [],
          usuarioCierre:  caja.usuarioCierre || ''
        })
      } finally {
        this.reimprimiendoId = null
      }
    },
    exportarPDF() {
      if(this.cajas.length === 0) return;
      let columnas = ["Turno #", "Apertura (User)", "Cierre (User)", "Fondo Inicial", "Ventas Declaradas", "Egresos", "Cuadre / Diferencia"];
      let filas = this.cajas.map(c => [
        "#" + c.id,
        `${this.$options.filters.formatFecha(c.fechaApertura)}\n(${c.usuarioApertura})`,
        c.estado === 'CERRADA' ? `${this.$options.filters.formatFecha(c.fechaCierre)}\n(${c.usuarioCierre})` : "AÚN ABIERTA",
        "Bs. " + Math.round(c.montoApertura),
        c.estado === 'CERRADA' ? "Bruto: Bs. " + Math.round(c.ventasTotales) : "--",
        c.estado === 'CERRADA' ? "-Bs. " + Math.round(c.gastosTotales || 0) : "--",
        c.estado === 'CERRADA' ? (this.calcularDiferencia(c) === 0 ? "EXACTO: Bs. 0" : "Diferencia: Bs. " + Math.round(this.calcularDiferencia(c))) : "--"
      ]);
      ReportesPdfService.generar("Auditoria de Cortes de Caja (Arqueos)", columnas, filas);
    }
  }
}
</script>
