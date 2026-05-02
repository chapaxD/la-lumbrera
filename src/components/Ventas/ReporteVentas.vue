<template>
  <section>
    <!-- Encabezado -->
    <nav class="level mb-4">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="cash-register" size="is-large" type="is-primary"></b-icon>
            Reporte de Ventas
          </p>
        </div>
      </div>
      <div class="level-right">
        <div class="level-item">
          <b-select v-model="perPage" size="is-small" @input="onCambiarLimite">
            <option :value="5">5 por página</option>
            <option :value="10">10 por página</option>
            <option :value="20">20 por página</option>
            <option :value="50">50 por página</option>
            <option :value="100">100 por página</option>
          </b-select>
        </div>
        <div class="level-item">
          <div class="buttons">
            <b-button type="is-primary" icon-left="refresh" @click="recargar">Recargar</b-button>
            <b-button type="is-primary" icon-left="filter-variant" @click="filtrar = !filtrar">Filtrar</b-button>
            <b-button type="is-danger" icon-left="file-pdf-box" @click="exportarPDF">PDF</b-button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Filtros -->
    <div class="box mb-4" v-if="filtrar">
      <b-field grouped group-multiline>
        <b-field label="Periodo de tiempo" expanded>
          <b-datepicker placeholder="Selecciona rango..." v-model="fechasSeleccionadas" @input="buscarEnFecha"
            icon="calendar-today" range></b-datepicker>
        </b-field>
        <b-field label="Vendedor" expanded>
          <b-select v-model="usuarioSeleccionado" @change.native="buscarEnFecha">
            <option value="">Todos</option>
            <option v-for="usuario in usuarios" :key="usuario.id" :value="usuario.id">
              {{ usuario.nombre }}
            </option>
          </b-select>
        </b-field>
      </b-field>
    </div>

    <!-- Estadísticas -->
    <div class="columns mb-2" v-if="totalRegistros > 0">
      <div class="column">
        <div class="notification is-light py-4 has-text-centered">
          <p class="heading has-text-grey">Total ventas</p>
          <p class="title is-3">{{ totalRegistros }}</p>
        </div>
      </div>
      <div class="column">
        <div class="notification is-info is-light py-4 has-text-centered">
          <p class="heading">Ventas locales</p>
          <p class="title is-3 has-text-info-dark">{{ ventasLocales }}</p>
        </div>
      </div>
      <div class="column">
        <div class="notification is-warning is-light py-4 has-text-centered">
          <p class="heading">Deliveries</p>
          <p class="title is-3 has-text-warning-dark">{{ ventasDelivery }}</p>
        </div>
      </div>
      <div class="column">
        <div class="notification is-primary is-light py-4 has-text-centered">
          <p class="heading">Total recaudado</p>
          <p class="title is-3 has-text-primary-dark">{{ Utiles.formatearDinero(totalVentas) }}</p>
        </div>
      </div>
    </div>



    <!-- Tabla principal -->
    <div class="box">
      <b-table :data="ventas" :total="totalRegistros" :per-page="perPage" :paginated="true" :bordered="true"
        :narrowed="true" :striped="true" :hoverable="true" :current-page.sync="currentPage"
        :pagination-simple="isPaginationSimple" :pagination-position="paginationPosition"
        :default-sort-direction="defaultSortDirection" :sort-icon="sortIcon" :sort-icon-size="sortIconSize"
        backend-pagination detailed detail-key="id" @page-change="onPageChange" aria-next-label="Siguiente"
        aria-previous-label="Anterior" aria-page-label="Página" aria-current-label="Página actual">
        <b-table-column field="id" label="#" numeric sortable v-slot="props">
          <strong>{{ props.row.id }}</strong>
        </b-table-column>

        <b-table-column field="tipo_orden" label="Tipo" v-slot="props">
          <b-tag :type="props.row.tipo_orden === 'DELIVERY' ? 'is-info' : 'is-light'">
            {{ props.row.tipo_orden || 'LOCAL' }}
          </b-tag>
        </b-table-column>

        <b-table-column field="idMesa" label="Mesa / Ref" v-slot="props">
          <span v-if="props.row.tipo_orden === 'DELIVERY'">Delivery</span>
          <span v-else>Mesa #{{ props.row.idMesa }}</span>
        </b-table-column>

        <b-table-column field="fecha" label="Fecha" searchable sortable v-slot="props">
          {{ props.row.fecha | formatFecha }}
        </b-table-column>

        <b-table-column field="atendio" label="Atendió" searchable v-slot="props">
          {{ props.row.atendio }}
        </b-table-column>

        <b-table-column field="cliente" label="Cliente" searchable v-slot="props">
          {{ props.row.cliente || '-' }}
        </b-table-column>

        <b-table-column field="metodoPago" label="Método" sortable v-slot="props">
          <b-tag type="is-info is-light" v-if="props.row.metodoPago === 'EFECTIVO'">{{ props.row.metodoPago }}</b-tag>
          <b-tag type="is-success is-light" v-else-if="props.row.metodoPago === 'TARJETA'">{{ props.row.metodoPago
            }}</b-tag>
          <b-tag type="is-warning is-light" v-else-if="props.row.metodoPago === 'QR'">{{ props.row.metodoPago }}</b-tag>
          <b-tag type="is-link is-light" v-else>{{ props.row.metodoPago }}</b-tag>
        </b-table-column>

        <b-table-column field="pagado" label="Pago" numeric v-slot="props">
          {{ Utiles.formatearDinero(props.row.pagado) }}
        </b-table-column>

        <b-table-column field="cambio" label="Cambio" numeric sortable v-slot="props">
          {{ Utiles.formatearDinero(props.row.pagado - props.row.total) }}
        </b-table-column>

        <b-table-column field="total" label="Total" numeric sortable v-slot="props">
          <strong>{{ Utiles.formatearDinero(props.row.total) }}</strong>
        </b-table-column>

        <b-table-column label="Acciones" v-slot="props">
          <div class="buttons">
            <b-button size="is-small" type="is-warning" icon-left="file-document-outline" title="Emitir nota de venta"
              @click="generarFactura(props.row)"></b-button>
          </div>
        </b-table-column>

        <!-- Detalle expandible: productos del pedido -->
        <template #detail="props">
          <b-table :data="props.row.insumos" narrowed class="is-size-7">
            <b-table-column field="codigo" label="Código" v-slot="p">{{ p.row.codigo }}</b-table-column>
            <b-table-column field="nombre" label="Producto" v-slot="p">{{ p.row.nombre }}</b-table-column>
            <b-table-column field="cantidad" label="Cant." numeric v-slot="p">{{ p.row.cantidad }}</b-table-column>
            <b-table-column field="precio" label="Precio unit." numeric v-slot="p">{{
              Utiles.formatearDinero(p.row.precio) }}</b-table-column>
            <b-table-column field="subtotal" label="Subtotal" numeric v-slot="p">
              <strong>{{ Utiles.formatearDinero(p.row.cantidad * p.row.precio) }}</strong>
            </b-table-column>
          </b-table>
          <div v-if="props.row.tipo_orden === 'DELIVERY'" class="mt-2 is-size-7 has-text-grey">
            <strong>Dirección:</strong> {{ props.row.direccion }} &nbsp;&nbsp;
            <strong>Tel:</strong> {{ props.row.telefono }}
          </div>
        </template>

        <template #empty>
          <div class="has-text-centered py-6 has-text-grey" style="opacity: 0.8;">
            <b-icon icon="cash-register" custom-size="fa-4x" style="font-size: 4rem;"></b-icon>
            <p class="title is-4 mt-4 has-text-grey-dark">Sin registros de venta</p>
            <p class="subtitle is-6 mt-1">Acá aparecerá el dinero recaudado de las mesas pagadas o deliveries.</p>
          </div>
        </template>
      </b-table>
    </div>

    <!-- Top 10 productos -->
    <div class="columns mt-4" v-if="topInsumos.length > 0">
      <div class="column is-12">
        <div class="box">
          <p class="title is-5 has-text-weight-bold has-text-grey mb-3">
            <b-icon icon="food-apple" type="is-warning"></b-icon>
            Top 10 Productos más vendidos del periodo
          </p>
          <b-table :data="topInsumos" narrowed bordered striped hoverable>
            <b-table-column field="nombre" label="Producto" v-slot="props">{{ props.row.nombre }}</b-table-column>
            <b-table-column field="categoria" label="Categoría" v-slot="props">{{ props.row.categoria
            }}</b-table-column>
            <b-table-column field="totalVendidos" label="Cant. vendida" centered numeric v-slot="props">
              {{ props.row.totalVendidos }}
            </b-table-column>
            <b-table-column field="totalDinero" label="Recaudado" numeric v-slot="props">
              <strong>{{ Utiles.formatearDinero(props.row.totalDinero) }}</strong>
            </b-table-column>
          </b-table>
        </div>
      </div>
    </div>

    <ticket @impreso="onImpreso" :venta="this.ventaSeleccionada" :insumos="insumosSeleccionados" :datosLocal="datos"
      :logo="logo" v-if="mostrarTicket"></ticket>
  </section>
</template>
<script>
import HttpService from "../../Servicios/HttpService";
import Utiles from "../../Servicios/Utiles";
import ReportesPdfService from "../../Servicios/ReportesPdfService";
import Ticket from "./Ticket.vue";

export default {
  name: "ReporteVentas",
  components: { Ticket },

  data: () => ({
    Utiles,
    usuarios: [],
    filtrar: false,
    datos: {},
    ventaSeleccionada: {},
    insumosSeleccionados: [],
    fechasSeleccionadas: [],
    usuarioSeleccionado: "",
    filtros: {},
    cargando: false,
    ventas: [],
    topInsumos: [],
    ventasLocales: 0,
    ventasDelivery: 0,
    totalVentas: 0,
    totalRegistros: 0,
    resumenPorDia: [],
    mostrarTicket: false,
    isPaginationSimple: false,
    isPaginationRounded: true,
    paginationPosition: "bottom",
    defaultSortDirection: "asc",
    sortIcon: "arrow-up",
    sortIconSize: "is-small",
    currentPage: 1,
    perPage: 20,
    logo: null,
  }),

  mounted() {
    this.obtenerVentas();
    this.obtenerDatos();
  },

  methods: {
    recargar() {
      this.fechasSeleccionadas = [];
      this.filtros = {};
      this.currentPage = 1;
      this.obtenerVentas();
    },

    onCambiarLimite() {
      this.currentPage = 1;
      this.obtenerVentas();
    },

    exportarPDF() {
      if (this.ventas.length === 0) return;
      let columnas = ["ID Ticket", "Tipo", "Mesa / Ref", "Fecha", "Atendio", "Cliente", "Método", "Total Comprado"];
      let filas = this.ventas.map(v => [
        "#" + v.id,
        v.tipo_orden || "LOCAL",
        v.tipo_orden === 'DELIVERY' ? (v.direccion || "Delivery") : ("Mesa " + v.idMesa),
        this.$options.filters.formatFecha(v.fecha),
        v.atendio,
        v.cliente || "-",
        v.metodoPago || "EFECTIVO",
        Utiles.formatearDinero(v.total)
      ]);
      ReportesPdfService.generar("Reporte Resumido de Ventas", columnas, filas, "Total Acumulado Generado: " + Utiles.formatearDinero(this.totalVentas));
    },

    onImpreso(resultado) {
      this.mostrarTicket = resultado;
    },

    generarFactura(venta) {
      sessionStorage.setItem('botanero_factura_prefill', JSON.stringify(venta))
      this.$router.push({ name: 'Factura' })
    },

    imprimirComprobante(venta) {
      this.ventaSeleccionada = {
        atendio: venta.atendio,
        cliente: venta.cliente,
        fecha: venta.fecha,
        total: venta.total,
        pagado: venta.pagado,
        metodoPago: venta.metodoPago,
        montoEfectivo: venta.montoEfectivo,
        montoTarjeta: venta.montoTarjeta,
        montoQR: venta.montoQR,
        adelanto: venta.adelanto,
        mesa: parseInt(venta.idMesa) > 0 ? parseInt(venta.idMesa) : null,
      };

      this.insumosSeleccionados = venta.insumos;
      this.mostrarTicket = true;
    },

    onPageChange(pagina) {
      this.currentPage = pagina;
      this.obtenerVentas();
    },

    buscarEnFecha() {
      const toFecha = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${dia}`;
      };
      this.filtros = {
        inicio: toFecha(this.fechasSeleccionadas[0]),
        fin: toFecha(this.fechasSeleccionadas[1]),
        idUsuario: this.usuarioSeleccionado,
      };
      this.currentPage = 1;
      this.obtenerVentas();
    },

    obtenerVentas() {
      this.cargando = true;
      const payload = {
        ...this.filtros,
        pagina: this.currentPage,
        limite: this.perPage
      };
      HttpService.obtenerConDatos(payload, "obtener_ventas.php").then(
        (resultado) => {
          this.ventas = resultado.ventas;
          this.totalRegistros = resultado.totalRegistros || 0;
          this.totalVentas = resultado.totalPeriodo || 0;
          this.ventasLocales = resultado.totalLocales || 0;
          this.ventasDelivery = resultado.totalDelivery || 0;
          this.topInsumos = resultado.topInsumos || [];
          this.usuarios = resultado.usuarios;
          this.resumenPorDia = resultado.resumenPorDia || [];
          this.cargando = false;
        }
      );
    },

    obtenerDatos() {
      HttpService.obtener("obtener_datos_local.php").then((resultado) => {
        this.datos = resultado;
        this.logo = Utiles.generarUrlImagen(this.datos.logo);
      });
    },
  },
};
</script>
