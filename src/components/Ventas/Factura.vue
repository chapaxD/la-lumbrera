<template>
  <section>
    <!-- Encabezado de página -->
    <nav class="level">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="file-document-outline" size="is-large" type="is-primary"></b-icon>
            Facturación Bolivia
          </p>
        </div>
      </div>
      <div class="level-right">
        <div class="level-item">
          <b-button
            type="is-success"
            icon-left="printer"
            size="is-medium"
            :disabled="!puedeImprimir"
            @click="imprimirFactura"
          >Imprimir Factura</b-button>
        </div>
      </div>
    </nav>

    <!-- Modal selector de ventas -->
    <b-modal v-model="modalVentas" has-modal-card trap-focus :width="760">
      <div class="modal-card" style="width:760px;max-width:98vw">
        <header class="modal-card-head" style="background:var(--color-primario); box-shadow:none">
          <p class="modal-card-title" style="color:#fff">
            <b-icon icon="cart-outline" size="is-small" style="color:#fff"></b-icon>
            Seleccionar venta para facturar
          </p>
          <button class="delete" @click="modalVentas=false"></button>
        </header>
        <section class="modal-card-body">
          <!-- Filtros -->
          <b-field grouped>
            <b-field label="Desde" expanded>
              <b-datepicker v-model="filtroVentas.inicio_obj" size="is-small" icon="calendar" :date-formatter="d => `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`"></b-datepicker>
            </b-field>
            <b-field label="Hasta" expanded>
              <b-datepicker v-model="filtroVentas.fin_obj" size="is-small" icon="calendar" :date-formatter="d => `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`"></b-datepicker>
            </b-field>
            <b-field label="Cliente" expanded>
              <b-input v-model="filtroVentas.busqueda" size="is-small" placeholder="Filtrar por cliente..." icon="magnify"></b-input>
            </b-field>
            <b-field label="Acción">
              <b-button size="is-small" type="is-primary" icon-left="magnify" :loading="cargandoVentas" @click="cargarVentas">Buscar</b-button>
            </b-field>
          </b-field>

          <!-- Opción de carga -->
          <b-field>
            <b-radio-button v-model="modoVenta" native-value="reemplazar" size="is-small" type="is-warning">
              <b-icon icon="swap-horizontal"></b-icon>
              <span>Reemplazar ítems actuales</span>
            </b-radio-button>
            <b-radio-button v-model="modoVenta" native-value="agregar" size="is-small" type="is-success">
              <b-icon icon="plus"></b-icon>
              <span>Agregar a ítems actuales</span>
            </b-radio-button>
          </b-field>

          <!-- Tabla de ventas -->
          <b-table
            :data="ventasFiltradas"
            :loading="cargandoVentas"
            hoverable
            narrowed
            bordered
            :selected.sync="ventaSeleccionada"
            focusable
            :row-class="() => 'is-clickable'"
            @click="seleccionarVenta"
          >
            <b-table-column label="#" width="60" numeric v-slot="props">{{ props.row.id }}</b-table-column>
            <b-table-column label="Fecha" width="150" v-slot="props">{{ props.row.fecha }}</b-table-column>
            <b-table-column label="Cliente" v-slot="props">{{ props.row.cliente || '—' }}</b-table-column>
            <b-table-column label="Atendió" v-slot="props">{{ props.row.atendio }}</b-table-column>
            <b-table-column label="Total (Bs)" numeric width="110" v-slot="props">
              <strong>{{ formatNum(props.row.total) }}</strong>
            </b-table-column>
            <b-table-column label="Ítems" width="60" numeric v-slot="props">{{ (props.row.insumos || []).length }}</b-table-column>

            <template #empty>
              <div class="has-text-centered has-text-grey py-3">{{ cargandoVentas ? 'Cargando...' : 'Sin resultados. Ajusta los filtros y busca.' }}</div>
            </template>
          </b-table>
        </section>
        <footer class="modal-card-foot">
          <b-button @click="modalVentas=false">Cancelar</b-button>
          <p class="is-size-7 has-text-grey ml-3">Haz clic en una fila para cargar sus ítems en la factura.</p>
        </footer>
      </div>
    </b-modal>

    <!-- Panel de configuración fiscal -->
    <div class="box mb-4">
      <div
        class="is-flex is-justify-content-space-between is-align-items-center"
        style="cursor:pointer"
        @click="configAbierto = !configAbierto"
      >
        <p class="has-text-weight-bold">
          <b-icon icon="domain" size="is-small"></b-icon>
          Datos del Emisor (Configuración Fiscal)
          <b-tag v-if="!config.nit" type="is-warning" size="is-small" class="ml-2">¡Configura tu NIT!</b-tag>
        </p>
        <b-icon :icon="configAbierto ? 'chevron-up' : 'chevron-down'"></b-icon>
      </div>

      <div v-if="configAbierto" class="mt-4">
        <b-field grouped group-multiline>
          <b-field label="NIT Emisor" expanded>
            <b-input v-model="config.nit" placeholder="Ej: 123456789" icon="identifier"></b-input>
          </b-field>
          <b-field label="Razón Social" expanded>
            <b-input v-model="config.razonSocial" placeholder="Nombre registrado en SIN" icon="office-building"></b-input>
          </b-field>
        </b-field>
        <b-field grouped group-multiline>
          <b-field label="Actividad Económica" expanded>
            <b-input v-model="config.actividad" placeholder="Ej: SERVICIOS DE RESTAURANTE Y SIMILARES"></b-input>
          </b-field>
        </b-field>
        <b-field grouped group-multiline>
          <b-field label="Dirección" expanded>
            <b-input v-model="config.direccion" placeholder="Dirección del local" icon="map-marker"></b-input>
          </b-field>
          <b-field label="Ciudad" expanded>
            <b-input v-model="config.ciudad" placeholder="Ej: Cochabamba" icon="city"></b-input>
          </b-field>
          <b-field label="Teléfono" expanded>
            <b-input v-model="config.telefono" placeholder="Número de contacto" icon="phone"></b-input>
          </b-field>
        </b-field>
        <b-field grouped group-multiline>
          <b-field label="N° de Autorización SIN" expanded>
            <b-input v-model="config.numAutorizacion" placeholder="Número de autorización de dosificación" icon="key"></b-input>
          </b-field>
          <b-field label="Fecha Límite de Emisión" expanded>
            <b-input v-model="config.fechaLimite" type="date" icon="calendar"></b-input>
          </b-field>
        </b-field>
        <b-button type="is-primary" icon-left="content-save" @click="guardarConfig">
          Guardar Configuración Fiscal
        </b-button>
      </div>
    </div>

    <!-- Formulario de la factura -->
    <div class="columns">
      <!-- Columna izquierda: datos y detalle -->
      <div class="column is-8">

        <!-- Datos del documento y del comprador -->
        <div class="box">
          <p class="has-text-weight-bold mb-3 is-size-6">
            <b-icon icon="file-edit-outline" size="is-small"></b-icon>
            Datos de la Factura
          </p>
          <b-field grouped>
            <b-field label="N° Factura" style="min-width:140px">
              <b-input v-model.number="factura.numero" type="number" min="1" icon="pound"></b-input>
            </b-field>
            <b-field label="Fecha de Emisión">
              <b-datepicker v-model="factura.fecha_obj" icon="calendar" :date-formatter="d => `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`" @input="sincronizarFechaHora"></b-datepicker>
            </b-field>
            <b-field label="Hora">
              <b-timepicker v-model="factura.hora_obj" icon="clock-outline" @input="sincronizarFechaHora"></b-timepicker>
            </b-field>
          </b-field>
          <b-field grouped>
            <b-field label="NIT / CI del Comprador" style="min-width:200px">
              <b-input
                v-model="factura.nitComprador"
                placeholder="99001 = anónimo"
                icon="card-account-details-outline"
              ></b-input>
            </b-field>
            <b-field label="Nombre / Razón Social del Comprador" expanded>
              <b-autocomplete
                v-model="factura.nombreComprador"
                :data="sugerenciasClientes"
                placeholder="SIN NOMBRE"
                icon="account-outline"
                field="nombre_completo"
                :loading="buscandoCliente"
                @typing="buscarClienteFactura"
                @select="seleccionarClienteFactura"
                clearable
              >
                <template slot="empty">Sin resultados (se registrará como nuevo)</template>
              </b-autocomplete>
            </b-field>
          </b-field>
        </div>

        <!-- Detalle de ítems -->
        <div class="box">
          <div class="is-flex is-justify-content-space-between is-align-items-center mb-3">
            <p class="has-text-weight-bold is-size-6">
              <b-icon icon="format-list-bulleted" size="is-small"></b-icon>
              Detalle de Productos / Servicios
            </p>
            <div class="buttons">
              <b-button size="is-small" type="is-info" icon-left="cart-outline" @click="abrirSelectorVentas">
                Desde venta
              </b-button>
              <b-button size="is-small" type="is-primary" icon-left="plus" @click="agregarLinea">
                Línea en blanco
              </b-button>
            </div>
          </div>

          <b-table :data="factura.items" narrowed bordered>
            <b-table-column label="Cant." width="80" v-slot="props">
              <b-input
                v-model.number="props.row.cantidad"
                type="number"
                min="1"
                size="is-small"
                @input="calcularTotales"
              ></b-input>
            </b-table-column>

            <b-table-column label="Descripción" v-slot="props">
              <b-input
                v-model="props.row.descripcion"
                size="is-small"
                placeholder="Producto o servicio"
              ></b-input>
            </b-table-column>

            <b-table-column label="P. Unit. (Bs)" width="130" v-slot="props">
              <b-input
                v-model.number="props.row.precioUnitario"
                type="number"
                min="0"
                step="0.01"
                size="is-small"
                @input="calcularTotales"
              ></b-input>
            </b-table-column>

            <b-table-column label="Desc. (Bs)" width="110" v-slot="props">
              <b-input
                v-model.number="props.row.descuento"
                type="number"
                min="0"
                step="0.01"
                size="is-small"
                @input="calcularTotales"
              ></b-input>
            </b-table-column>

            <b-table-column label="Subtotal (Bs)" width="130" numeric v-slot="props">
              <strong>{{ formatNum(subtotalLinea(props.row)) }}</strong>
            </b-table-column>

            <b-table-column label="" width="44" v-slot="props">
              <b-button
                size="is-small"
                type="is-danger is-light"
                icon-left="delete"
                @click="eliminarLinea(props.index)"
                :disabled="factura.items.length <= 1"
              ></b-button>
            </b-table-column>
          </b-table>
        </div>

      </div>

      <!-- Columna derecha: totales y acciones -->
      <div class="column is-4">
        <div class="box">
          <p class="has-text-weight-bold mb-3 is-size-6">
            <b-icon icon="calculator" size="is-small"></b-icon>
            Totales
          </p>

          <table class="table is-fullwidth is-narrow is-size-6">
            <tbody>
              <tr>
                <td>Subtotal bruto</td>
                <td class="has-text-right">Bs {{ formatNum(totales.subtotal) }}</td>
              </tr>
              <tr v-if="totales.descuentos > 0">
                <td class="has-text-danger">Descuentos</td>
                <td class="has-text-right has-text-danger">- Bs {{ formatNum(totales.descuentos) }}</td>
              </tr>
              <tr>
                <td class="has-text-grey">Base Cred. Fiscal (÷1.13)</td>
                <td class="has-text-right has-text-grey">Bs {{ formatNum(totales.baseCredito) }}</td>
              </tr>
              <tr>
                <td class="has-text-grey">IVA 13%</td>
                <td class="has-text-right has-text-grey">Bs {{ formatNum(totales.iva) }}</td>
              </tr>
              <tr class="has-background-success-light">
                <td><strong>TOTAL A PAGAR</strong></td>
                <td class="has-text-right">
                  <strong class="is-size-5">Bs {{ formatNum(totales.total) }}</strong>
                </td>
              </tr>
            </tbody>
          </table>

          <b-field label="Código de Control (opcional)" class="mt-3">
            <b-input
              v-model="factura.codigoControl"
              placeholder="Código de control SIN"
              size="is-small"
              icon="barcode"
            ></b-input>
          </b-field>

          <b-field label="Notas u Observaciones">
            <b-input
              v-model="factura.nota"
              type="textarea"
              rows="2"
              placeholder="Observaciones para el comprador..."
              size="is-small"
            ></b-input>
          </b-field>

          <b-button
            expanded
            type="is-success"
            icon-left="printer"
            :disabled="!puedeImprimir"
            @click="imprimirFactura"
            class="mb-2"
          >
            Imprimir Factura
          </b-button>

          <b-button
            expanded
            type="is-light"
            icon-left="file-plus-outline"
            @click="nuevaFactura"
          >
            Nueva Factura
          </b-button>

          <!-- Aviso IVA -->
          <div class="notification is-info is-light mt-3 p-3">
            <p class="is-size-7">
              <b-icon icon="information-outline" size="is-small"></b-icon>
              Los precios ingresados se consideran <strong>con IVA incluido</strong>.
              El sistema extrae el IVA 13% del total (Total ÷ 1.13).
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

const COUNTER_KEY = 'botanero_factura_counter'

function nowDatetimeLocal() {
  const now = new Date()
  const pad = n => String(n).padStart(2, '0')
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
}

export default {
  name: 'Factura',

  data() {
    const lastNum = parseInt(localStorage.getItem(COUNTER_KEY) || '0')

    return {
      configAbierto: false,
      config: {
        nit: '',
        razonSocial: '',
        actividad: '',
        direccion: '',
        ciudad: '',
        telefono: '',
        numAutorizacion: '',
        fechaLimite: '',
      },
      factura: {
        numero: lastNum + 1,
        fechaHora: nowDatetimeLocal(),
        fecha_obj: new Date(),
        hora_obj: new Date(),
        nitComprador: '99001',
        nombreComprador: 'SIN NOMBRE',
        codigoControl: '',
        nota: '',
        items: [{ cantidad: 1, descripcion: '', precioUnitario: 0, descuento: 0 }],
      },
      totales: {
        subtotal: 0,
        descuentos: 0,
        baseCredito: 0,
        iva: 0,
        total: 0,
      },
      // Selector de ventas
      modalVentas: false,
      cargandoVentas: false,
      ventas: [],
      ventaSeleccionada: null,
      modoVenta: 'reemplazar',
      filtroVentas: {
        inicio_obj: new Date(),
        fin_obj: new Date(),
        busqueda: '',
      },
      // Autocomplete clientes
      sugerenciasClientes: [],
      buscandoCliente: false,
      _timerCliente: null,
    }
  },

  computed: {
    ventasFiltradas() {
      const q = this.filtroVentas.busqueda.trim().toLowerCase()
      if (!q) return this.ventas
      return this.ventas.filter(v =>
        (v.cliente || '').toLowerCase().includes(q) ||
        String(v.id).includes(q)
      )
    },

    puedeImprimir() {
      return this.factura.items.some(
        i => i.descripcion && i.descripcion.trim() && i.cantidad > 0 && i.precioUnitario > 0
      )
    },
  },

  mounted() {
    this.calcularTotales()
    // Obtener el próximo número de factura desde BD
    HttpService.registrar({}, 'obtener_facturas.php')
      .then(lista => {
        if (lista && lista.length > 0) {
          const maxNum = Math.max(...lista.map(f => parseInt(f.numero) || 0))
          this.factura.numero = maxNum + 1
          localStorage.setItem(COUNTER_KEY, String(maxNum))
        }
      })
      .catch(() => {})
    // Leer prefill desde ReporteVentas
    const prefill = sessionStorage.getItem('botanero_factura_prefill')
    if (prefill) {
      try {
        const venta = JSON.parse(prefill)
        sessionStorage.removeItem('botanero_factura_prefill')
        this.cargarDesdeVenta(venta)
      } catch (e) {}
    }
    // Cargar configuración fiscal desde BD
    HttpService.obtener('obtener_datos_local.php')
      .then(datos => {
        if (datos) {
          this.config.nit            = datos.nit_emisor          || ''
          this.config.razonSocial    = datos.razon_social        || datos.nombre || ''
          this.config.actividad      = datos.actividad           || ''
          this.config.direccion      = datos.direccion           || ''
          this.config.ciudad         = datos.ciudad              || ''
          this.config.telefono       = datos.telefono            || ''
          this.config.numAutorizacion = datos.num_autorizacion   || ''
          this.config.fechaLimite    = datos.fecha_limite_emision || ''
          this.configAbierto = !this.config.nit
        }
      })
      .catch(() => {})
  },

  methods: {
    sincronizarFechaHora() {
      const fo = this.factura.fecha_obj
      const ho = this.factura.hora_obj
      if (!fo || !ho) return
      const pad = n => String(n).padStart(2, '0')
      this.factura.fechaHora = `${fo.getFullYear()}-${pad(fo.getMonth()+1)}-${pad(fo.getDate())}T${pad(ho.getHours())}:${pad(ho.getMinutes())}`
    },

    buscarClienteFactura(q) {
      clearTimeout(this._timerCliente);
      if (!q || q.length < 2) { this.sugerenciasClientes = []; return; }
      this._timerCliente = setTimeout(() => {
        this.buscandoCliente = true;
        HttpService.obtener('obtener_clientes.php?q=' + encodeURIComponent(q)).then(datos => {
          this.sugerenciasClientes = (datos || []).map(c => ({
            ...c,
            nombre_completo: c.nombre + (c.apellido ? ' ' + c.apellido : '')
          }));
          this.buscandoCliente = false;
        });
      }, 350);
    },
    seleccionarClienteFactura(cliente) {
      if (!cliente) return;
      this.factura.nombreComprador = cliente.nombre_completo || (cliente.nombre + (cliente.apellido ? ' ' + cliente.apellido : ''));
      this.factura.nitComprador = (cliente.nit && String(cliente.nit).trim()) ? String(cliente.nit).trim() : '99001';
    },
    cargarDesdeVenta(venta) {
      // Ítems desde los insumos del ticket
      if (venta.insumos && venta.insumos.length > 0) {
        this.factura.items = venta.insumos.map(i => ({
          cantidad: parseInt(i.cantidad) || 1,
          descripcion: i.nombre || '',
          precioUnitario: parseFloat(i.precio) || 0,
          descuento: 0,
        }))
      }
      // Nombre del comprador y NIT
      this.factura.nombreComprador = venta.cliente || 'SIN NOMBRE'
      this.factura.nitComprador = '99001'
      if (venta.cliente) {
        const primerNombre = venta.cliente.trim().split(/\s+/)[0]
        HttpService.obtener('obtener_clientes.php?q=' + encodeURIComponent(primerNombre)).then(datos => {
          const coincide = (datos || []).find(c => {
            const nombre = c.nombre + (c.apellido ? ' ' + c.apellido : '')
            return nombre.toLowerCase() === venta.cliente.toLowerCase()
          })
          if (coincide && coincide.nit) {
            this.factura.nitComprador = coincide.nit
          }
        })
      }
      // Fecha y hora desde la venta (formato "YYYY-MM-DD HH:mm:ss")
      if (venta.fecha) {
        const d = new Date(venta.fecha.replace(' ', 'T'))
        if (!isNaN(d)) {
          this.factura.fecha_obj = new Date(d.getFullYear(), d.getMonth(), d.getDate())
          this.factura.hora_obj = new Date(1970, 0, 1, d.getHours(), d.getMinutes())
          this.sincronizarFechaHora()
        }
      }
      this.calcularTotales()
    },

    guardarConfig() {
      HttpService.registrar(this.config, 'guardar_config_fiscal.php')
        .then(() => {
          this.$toast({ message: 'Configuración fiscal guardada correctamente', type: 'is-success' })
          this.configAbierto = false
        })
        .catch(() => {
          this.$toast({ message: 'Error al guardar la configuración', type: 'is-danger' })
        })
    },

    subtotalLinea(item) {
      return Math.max(0, (item.cantidad || 0) * (item.precioUnitario || 0) - (item.descuento || 0))
    },

    calcularTotales() {
      const bruto = this.factura.items.reduce(
        (acc, i) => acc + (i.cantidad || 0) * (i.precioUnitario || 0),
        0
      )
      const descuentos = this.factura.items.reduce((acc, i) => acc + (i.descuento || 0), 0)
      const total = bruto - descuentos
      const baseCredito = total / 1.13
      const iva = total - baseCredito
      this.totales = { subtotal: bruto, descuentos, baseCredito, iva, total }
    },

    agregarLinea() {
      this.factura.items.push({ cantidad: 1, descripcion: '', precioUnitario: 0, descuento: 0 })
    },

    abrirSelectorVentas() {
      this.modalVentas = true
      if (this.ventas.length === 0) this.cargarVentas()
    },

    fechaLocalStr(d) {
      const pad = n => String(n).padStart(2, '0')
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`
    },

    cargarVentas() {
      this.cargandoVentas = true
      HttpService.obtenerConDatos(
        { inicio: this.fechaLocalStr(this.filtroVentas.inicio_obj), fin: this.fechaLocalStr(this.filtroVentas.fin_obj), limite: 100, pagina: 1 },
        'obtener_ventas.php'
      )
        .then(resultado => {
          this.ventas = resultado.ventas || []
        })
        .catch(() => {
          this.$buefy.toast.open({ message: 'No se pudieron cargar las ventas.', type: 'is-danger' })
        })
        .finally(() => { this.cargandoVentas = false })
    },

    seleccionarVenta(venta) {
      const items = (venta.insumos || []).map(i => ({
        cantidad: parseInt(i.cantidad) || 1,
        descripcion: i.nombre || '',
        precioUnitario: parseFloat(i.precio) || 0,
        descuento: 0,
      }))
      if (items.length === 0) {
        this.$buefy.toast.open({ message: 'Esta venta no tiene ítems registrados.', type: 'is-warning' })
        return
      }
      if (this.modoVenta === 'reemplazar') {
        this.factura.items = items
      } else {
        const tieneVacia = this.factura.items.length === 1 &&
          !this.factura.items[0].descripcion && this.factura.items[0].precioUnitario === 0
        if (tieneVacia) {
          this.factura.items = items
        } else {
          this.factura.items.push(...items)
        }
      }
      // Siempre actualizar nombre del comprador desde la venta
      this.factura.nombreComprador = venta.cliente || 'SIN NOMBRE'
      this.factura.nitComprador = '99001'
      // Si hay cliente con nombre, buscar su NIT en la BD
      if (venta.cliente) {
        const primerNombre = venta.cliente.trim().split(/\s+/)[0]
        HttpService.obtener('obtener_clientes.php?q=' + encodeURIComponent(primerNombre)).then(datos => {
          const coincide = (datos || []).find(c => {
            const nombre = c.nombre + (c.apellido ? ' ' + c.apellido : '')
            return nombre.toLowerCase() === venta.cliente.toLowerCase()
          })
          if (coincide && coincide.nit) {
            this.factura.nitComprador = coincide.nit
          }
        })
      }
      // Actualizar fecha y hora desde la venta
      if (venta.fecha) {
        const d = new Date(venta.fecha.replace(' ', 'T'))
        if (!isNaN(d)) {
          this.factura.fecha_obj = new Date(d.getFullYear(), d.getMonth(), d.getDate())
          this.factura.hora_obj = new Date(1970, 0, 1, d.getHours(), d.getMinutes())
          this.sincronizarFechaHora()
        }
      }
      this.calcularTotales()
      this.modalVentas = false
      this.$buefy.toast.open({ message: `${items.length} ítem(s) cargados desde venta #${venta.id}`, type: 'is-success' })
    },

    eliminarLinea(index) {
      this.factura.items.splice(index, 1)
      this.calcularTotales()
    },

    nuevaFactura() {
      const last = parseInt(localStorage.getItem(COUNTER_KEY) || '0')
      this.factura = {
        numero: last + 1,
        fechaHora: nowDatetimeLocal(),
        fecha_obj: new Date(),
        hora_obj: new Date(),
        nitComprador: '99001',
        nombreComprador: 'SIN NOMBRE',
        codigoControl: '',
        nota: '',
        items: [{ cantidad: 1, descripcion: '', precioUnitario: 0, descuento: 0 }],
      }
      this.calcularTotales()
    },

    formatNum(n) {
      return parseFloat(n || 0).toFixed(2)
    },

    async imprimirFactura() {
      // Guardar en BD primero
      const payload = {
        numero: this.factura.numero,
        fechaHora: this.factura.fechaHora.replace('T', ' ') + ':00',
        nitComprador: this.factura.nitComprador || '99001',
        nombreComprador: this.factura.nombreComprador || 'SIN NOMBRE',
        codigoControl: this.factura.codigoControl || null,
        subtotal: this.totales.subtotal,
        descuentos: this.totales.descuentos,
        baseCredito: this.totales.baseCredito,
        iva: this.totales.iva,
        total: this.totales.total,
        nota: this.factura.nota || null,
        idVenta: this.factura.idVenta || null,
        idUsuario: parseInt(localStorage.getItem('idUsuario')),
        items: this.factura.items.filter(i => i.descripcion && i.descripcion.trim() && i.cantidad > 0),
      }

      const resultado = await HttpService.registrar(payload, 'guardar_factura.php')
      if (!resultado || !resultado.ok) {
        this.$buefy.dialog.alert({
          title: 'Error al guardar',
          message: resultado && resultado.error ? resultado.error : 'No se pudo guardar la factura en la base de datos.',
          type: 'is-danger',
          hasIcon: true,
        })
        return
      }

      // Actualizar correlativo local
      localStorage.setItem(COUNTER_KEY, String(this.factura.numero))

      const c = this.config
      const f = this.factura
      const t = this.totales

      // Formatear fecha/hora
      const dt = new Date(f.fechaHora)
      const pad = n => String(n).padStart(2, '0')
      const fechaDisplay = `${pad(dt.getDate())}/${pad(dt.getMonth() + 1)}/${dt.getFullYear()}`
      const horaDisplay = `${pad(dt.getHours())}:${pad(dt.getMinutes())}`
      const numFactura = String(f.numero).padStart(7, '0')

      // Filas de detalle
      const filas = f.items
        .filter(i => i.descripcion && i.descripcion.trim() && i.cantidad > 0)
        .map(i => `
          <tr>
            <td class="center">${i.cantidad}</td>
            <td>${this.escapeHtml(i.descripcion)}</td>
            <td class="right">${this.formatNum(i.precioUnitario)}</td>
            <td class="right">${i.descuento > 0 ? this.formatNum(i.descuento) : '—'}</td>
            <td class="right"><strong>${this.formatNum(this.subtotalLinea(i))}</strong></td>
          </tr>
        `)
        .join('')

      const html = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Factura N° ${numFactura}</title>
  <style>
    @page { size: A4; margin: 12mm 15mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; }

    .factura { max-width: 180mm; margin: 0 auto; border: 2px solid #000; padding: 8mm; }

    /* Encabezado */
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5mm; }
    .empresa h1 { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
    .empresa p { margin: 2px 0; font-size: 10px; }
    .empresa .nit { font-size: 13px; font-weight: bold; margin: 4px 0; }

    .doc-box { border: 2px solid #000; padding: 5mm; text-align: center; min-width: 70mm; }
    .doc-box .label-fact { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #555; }
    .doc-box .titulo { font-size: 22px; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; margin: 2px 0; }
    .doc-box .num { font-size: 18px; font-weight: bold; color: #000; margin: 4px 0; }
    .doc-box .aut { font-size: 8px; margin-top: 5px; }
    .doc-box p { font-size: 10px; margin: 2px 0; }

    /* Separador */
    .sep { border-top: 1px solid #000; margin: 4mm 0; }
    .sep-doble { border-top: 2px solid #000; margin: 4mm 0; }

    /* Comprador */
    .comprador { margin-bottom: 4mm; }
    .comprador .row { display: flex; gap: 5mm; align-items: center; margin-bottom: 3px; }
    .comprador .campo { font-weight: bold; white-space: nowrap; }
    .comprador .valor { border-bottom: 1px solid #aaa; flex: 1; min-height: 14px; padding-bottom: 1px; }

    /* Detalle */
    .detalle table { width: 100%; border-collapse: collapse; }
    .detalle thead tr { background: #000; color: #fff; }
    .detalle th { padding: 3px 5px; font-size: 10px; text-align: left; }
    .detalle td { border: 1px solid #ccc; padding: 3px 5px; font-size: 11px; vertical-align: top; }
    .center { text-align: center; }
    .right { text-align: right; }

    /* Totales */
    .totales { margin-top: 4mm; }
    .totales-table { margin-left: auto; width: 80mm; border-collapse: collapse; }
    .totales-table td { padding: 2px 5px; font-size: 11px; }
    .totales-table .linea-total td {
      font-size: 14px; font-weight: bold;
      border-top: 2px solid #000; padding-top: 4px; margin-top: 2px;
    }

    /* Leyenda */
    .leyenda {
      margin-top: 5mm; font-size: 8px; text-align: center;
      font-style: italic; border-top: 1px dashed #000; padding-top: 3mm;
      color: #333;
    }
    .nota { font-size: 10px; margin-top: 3mm; }

    @media print {
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>
<div class="factura">

  <!-- ENCABEZADO -->
  <div class="header">
    <div class="empresa">
      <h1>${this.escapeHtml(c.razonSocial || 'NOMBRE DEL NEGOCIO')}</h1>
      <p class="nit">NIT: ${this.escapeHtml(c.nit || '—————')}</p>
      <p><strong>Actividad:</strong> ${this.escapeHtml(c.actividad || '—')}</p>
      <p><strong>Dirección:</strong> ${this.escapeHtml(c.direccion || '—')}</p>
      <p><strong>Ciudad:</strong> ${this.escapeHtml(c.ciudad || '—')}</p>
      ${c.telefono ? `<p><strong>Tel.:</strong> ${this.escapeHtml(c.telefono)}</p>` : ''}
    </div>
    <div class="doc-box">
      <div class="label-fact">Documento Fiscal</div>
      <div class="titulo">FACTURA</div>
      <div class="num">N° ${numFactura}</div>
      ${c.numAutorizacion ? `<div class="aut"><strong>Autorización:</strong> ${this.escapeHtml(c.numAutorizacion)}</div>` : ''}
      ${c.fechaLimite ? `<p style="font-size:8px">Límite emisión: ${c.fechaLimite}</p>` : ''}
      <p><strong>Fecha:</strong> ${fechaDisplay}</p>
      <p><strong>Hora:</strong> ${horaDisplay}</p>
      ${f.codigoControl ? `<p style="font-size:8px;margin-top:3px"><strong>Cod. Control:</strong> ${this.escapeHtml(f.codigoControl)}</p>` : ''}
    </div>
  </div>

  <div class="sep-doble"></div>

  <!-- DATOS DEL COMPRADOR -->
  <div class="comprador">
    <div class="row">
      <span class="campo">NIT / CI:</span>
      <span class="valor">${this.escapeHtml(f.nitComprador || '99001')}</span>
      <span class="campo" style="margin-left:5mm">Nombre / Razón Social:</span>
      <span class="valor">${this.escapeHtml(f.nombreComprador || 'SIN NOMBRE')}</span>
    </div>
  </div>

  <div class="sep"></div>

  <!-- DETALLE -->
  <div class="detalle">
    <table>
      <thead>
        <tr>
          <th style="width:12mm" class="center">Cant.</th>
          <th>Descripción</th>
          <th style="width:25mm" class="right">P. Unit. (Bs)</th>
          <th style="width:22mm" class="right">Descuento</th>
          <th style="width:25mm" class="right">Subtotal (Bs)</th>
        </tr>
      </thead>
      <tbody>
        ${filas}
      </tbody>
    </table>
  </div>

  <!-- TOTALES -->
  <div class="totales">
    <table class="totales-table">
      <tr>
        <td>Subtotal bruto:</td>
        <td class="right">Bs ${this.formatNum(t.subtotal)}</td>
      </tr>
      ${t.descuentos > 0 ? `<tr><td>Descuentos:</td><td class="right" style="color:#c00">- Bs ${this.formatNum(t.descuentos)}</td></tr>` : ''}
      <tr style="color:#555">
        <td>Importe Base Créd. Fiscal:</td>
        <td class="right">Bs ${this.formatNum(t.baseCredito)}</td>
      </tr>
      <tr style="color:#555">
        <td>IVA (13%):</td>
        <td class="right">Bs ${this.formatNum(t.iva)}</td>
      </tr>
      <tr class="linea-total">
        <td>TOTAL A PAGAR:</td>
        <td class="right">Bs ${this.formatNum(t.total)}</td>
      </tr>
    </table>
  </div>

  ${f.nota ? `<div class="sep"></div><p class="nota"><strong>Nota:</strong> ${this.escapeHtml(f.nota)}</p>` : ''}

  <!-- LEYENDA BOLIVIANA -->
  <div class="leyenda">
    <p>"El IVA incluido en esta factura, podrá ser acreditado siempre que los datos del comprador se encuentren registrados en su declaración de impuestos conforme a la Ley 843."</p>
    <p style="margin-top:2mm">${this.escapeHtml(c.ciudad || 'Bolivia')} — Este documento es válido para crédito fiscal</p>
  </div>

</div>
<script>window.onload = function(){ window.print(); }<\/script>
</body>
</html>`

      const w = window.open('', '_blank', 'width=850,height=1000')
      if (w) {
        w.document.write(html)
        w.document.close()
      }

      // Limpiar formulario para la siguiente factura
      this.nuevaFactura()
    },

    escapeHtml(str) {
      return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
    },
  },
}
</script>
