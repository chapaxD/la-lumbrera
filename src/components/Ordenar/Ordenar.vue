<template>
  <section>
    <p class="title is-1 has-text-weight-bold">
      <b-icon :icon="tipo_orden === 'DELIVERY' ? 'truck-delivery' : tipo_orden === 'LLEVAR' ? 'walk' : 'pen'"
        size="is-large" type="is-primary"> </b-icon>
      {{ tituloOrden }}
    </p>

    <div class="columns">
      <div class="column">
        <b-field label="Nombre del cliente (Opcional)">
          <b-autocomplete v-model="cliente" :data="sugerenciasClientes" placeholder="Ej. Don Paco" icon="account-search"
            field="nombre_completo" size="is-medium" :loading="buscandoCliente" @typing="buscarClienteOrden"
            @select="seleccionarClienteOrden" clearable>
            <template slot="empty">Sin resultados</template>
          </b-autocomplete>
        </b-field>
      </div>
      <div class="column" v-if="tipo_orden === 'DELIVERY'">
        <b-field label="Dirección de entrega">
          <b-input placeholder="Ej. Calle 123, Colonia Centro" v-model="direccion" size="is-medium"></b-input>
        </b-field>
      </div>
      <div class="column" v-if="tipo_orden === 'DELIVERY'">
        <b-field label="Teléfono">
          <b-input placeholder="Ej. 1234567890" v-model="telefono" size="is-medium"></b-input>
        </b-field>
      </div>
    </div>

    <div class="title is-3 has-text-weight-bold has-text-grey">
      <div class="is-pulled-right">
        <span class="has-text-weight-bold has-text-primary" style="font-size: 2.5em">
          ${{ total }}
        </span>
        <b-button type="is-primary" size="is-large" icon-left="basket-check" style="margin-top: 18px"
          @click="realizarOrden" v-if="!estaAgregandoInsumos">
          Ordenar
        </b-button>
        <b-button type="is-primary" size="is-large" icon-left="basket-check" style="margin-top: 18px"
          @click="editarOrden" v-if="estaAgregandoInsumos">
          Añadir
        </b-button>
      </div>
    </div>

    <b-field>
      <b-autocomplete size="is-large" v-model="nombre" placeholder="Nombre del platillo o bebida"
        :data="filteredDataObj" field="nombre" @input="buscarInsumo" @select="(option) => agregarInsumoAOrden(option)"
        :clearable="true" keep-first id="busqueda" open-on-focus>
      </b-autocomplete>
    </b-field>

    <modal-armar-combo
      :active.sync="modalCombo"
      :insumo="insumoComboPendiente"
      @confirmar="onConfirmarCombo"
    />
    <div class="columns is-desktop is-hidden-mobile">
      <div class="column" v-if="insumosOrden.length > 0 || insumosAnteriores.length > 0">
        <p class="has-text-primary size-is-4" v-if="insumosOrden.length > 0">
          <b-icon icon="plus"></b-icon>
          Insumos agregados
        </p>

        <productos-orden :lista="insumosOrden" :tipo="'nuevo'" @modificado="onProductoModificado" @quitar="eliminar"
          v-if="insumosOrden.length > 0" />

        <p class="has-text-primary size-is-4" v-if="insumosAnteriores.length > 0">
          <b-icon icon="basket"></b-icon>
          Insumos servidos
        </p>
        <productos-orden :lista="insumosAnteriores" :tipo="'entregado'" v-if="insumosAnteriores.length > 0" />

      </div>

    </div>

    <!-- Sugerencias del día en grilla 4 columnas -->
    <div class="mt-4 mb-3 is-flex is-justify-content-space-between is-align-items-center">
      <p class="title is-6 has-text-weight-bold has-text-grey mb-0">
        <b-icon icon="silverware-fork-knife" size="is-small" class="mr-1"></b-icon>
        Productos y sugerencias
      </p>
      <b-button type="is-info is-light" icon-left="refresh" @click="obtenerMenuHoy" :loading="cargandoMenu">
        Actualizar menú
      </b-button>
    </div>

    <!-- Pestañas de Categorías -->
    <div class="tabs is-toggle is-small is-fullwidth mb-4" v-if="listaCategorias.length > 0">
      <ul class="scroll-horizontal-tabs" style="border-bottom: none;">
        <li :class="{'is-active': categoriaSeleccionada === null}">
          <a @click="filtrarPorCategoria(null)">
            <b-icon icon="star" size="is-small"></b-icon>
            <span>Menú del Día</span>
          </a>
        </li>
        <li v-for="cat in listaCategorias" :key="cat.id" :class="{'is-active': categoriaSeleccionada === cat.id}">
          <a @click="filtrarPorCategoria(cat.id)">
            <b-icon :icon="cat.tipo === 'BEBIDA' ? 'glass-cocktail' : 'food'" size="is-small"></b-icon>
            <span>{{ cat.nombre }}</span>
          </a>
        </li>
      </ul>
    </div>
    <div class="columns is-multiline">
      <div class="column is-3-widescreen is-4-desktop is-6-tablet is-12-mobile" v-for="insumo in insumosFiltrados"
        :key="insumo.id">
        <div class="card sugerencia-card"
          :class="{ 'sin-stock': !esInsumoCombo(insumo) && (!Number.isFinite(Number(insumo.stock)) || Number(insumo.stock) <= 0) }">
            <div class="card-content p-3">
              <div class="is-flex is-justify-content-space-between is-align-items-flex-start">
                <div style="min-width:0; flex:1;">
                  <p class="has-text-weight-bold is-size-6" style="line-height:1.2; word-break:break-word;">
                    <b-icon size="is-small" icon="noodles" class="has-text-info mr-1"
                      v-if="insumo.tipo === 'PLATILLO'"></b-icon>
                    <b-icon icon="cup" size="is-small" class="has-text-success mr-1"
                      v-if="insumo.tipo === 'BEBIDA'"></b-icon>
                    {{ insumo.nombre }}
                  </p>
                  <p class="is-size-7 has-text-grey mt-1" v-if="insumo.descripcion">{{ insumo.descripcion }}</p>
                </div>
              </div>
              <div class="is-flex is-justify-content-space-between is-align-items-center mt-2">
                <div class="is-flex" style="gap:4px; flex-wrap:wrap;">
                  <span class="has-text-weight-bold has-text-primary is-size-5">${{ insumo.precio }}</span>
                  <b-tag :type="etiquetaStockSugerencia(insumo).tipo" size="is-small" rounded>
                    {{ etiquetaStockSugerencia(insumo).texto }}
                  </b-tag>
                </div>
                <b-button type="is-primary" size="is-small" icon-left="plus"
                  :disabled="!esInsumoCombo(insumo) && (!Number.isFinite(Number(insumo.stock)) || Number(insumo.stock) <= 0)"
                  @click="agregarInsumoAOrden(insumo)">Agregar</b-button>
              </div>
            </div>
          </div>
      </div>
    </div>

    <!-- Sidebar para carrito en móviles -->
    <b-sidebar
      type="is-light"
      :fullheight="true"
      :fullwidth="true"
      :overlay="true"
      :right="true"
      v-model="mostrarCarritoMobile"
      class="is-hidden-tablet"
    >
      <div class="p-4" style="height: 100%; display: flex; flex-direction: column;">
        <p class="title is-4 mb-4">
          <b-icon icon="basket"></b-icon> Carrito
        </p>
        
        <div style="flex: 1; overflow-y: auto;">
          <p class="has-text-primary size-is-4 mt-2" v-if="insumosOrden.length > 0">
            <b-icon icon="plus"></b-icon> Insumos agregados
          </p>
          <productos-orden :lista="insumosOrden" :tipo="'nuevo'" @modificado="onProductoModificado" @quitar="eliminar"
            v-if="insumosOrden.length > 0" />

          <p class="has-text-primary size-is-4 mt-4" v-if="insumosAnteriores.length > 0">
            <b-icon icon="basket"></b-icon> Insumos servidos
          </p>
          <productos-orden :lista="insumosAnteriores" :tipo="'entregado'" v-if="insumosAnteriores.length > 0" />
          
          <div v-if="insumosOrden.length === 0 && insumosAnteriores.length === 0" class="has-text-centered py-5">
            <p class="has-text-grey">El carrito está vacío</p>
          </div>
        </div>

        <div class="mt-auto pt-4" style="border-top: 1px solid #ddd; margin-bottom: 20px;">
          <div class="is-flex is-justify-content-space-between is-align-items-center mb-3">
            <span class="has-text-weight-bold is-size-4">Total:</span>
            <span class="has-text-weight-bold has-text-primary is-size-3">${{ total }}</span>
          </div>
          <b-button type="is-primary" size="is-large" expanded icon-left="basket-check"
            @click="realizarOrden(); mostrarCarritoMobile = false" v-if="!estaAgregandoInsumos" :disabled="insumosOrden.length === 0">
            Ordenar
          </b-button>
          <b-button type="is-primary" size="is-large" expanded icon-left="basket-check"
            @click="editarOrden(); mostrarCarritoMobile = false" v-if="estaAgregandoInsumos" :disabled="insumosOrden.length === 0">
            Añadir
          </b-button>
        </div>
      </div>
    </b-sidebar>

    <!-- Botón Flotante (FAB) para Móviles -->
    <div class="is-hidden-tablet" v-if="insumosOrden.length > 0 || insumosAnteriores.length > 0">
      <b-button 
        type="is-primary" 
        rounded 
        size="is-large" 
        class="fab-carrito"
        @click="mostrarCarritoMobile = true"
      >
        <b-icon icon="basket" size="is-medium"></b-icon>
        <span class="ml-2 has-text-weight-bold">${{ total }}</span>
        <span class="fab-badge">{{ insumosOrden.length + insumosAnteriores.length }}</span>
      </b-button>
    </div>
  </section>
</template>
<script>
import HttpService from "../../Servicios/HttpService";
import ProductosOrden from "./ProductosOrden.vue";
import ModalArmarCombo from "./ModalArmarCombo.vue";

export default {
  name: "Ordenar",
  components: { ProductosOrden, ModalArmarCombo },
  data: () => ({
    idMesa: "",
    idDelivery: null,
    tipo_orden: "LOCAL",
    insumos: [],
    nombre: "",
    insumosAnteriores: [],
    insumosOrden: [],
    cliente: "",
    direccion: "",
    telefono: "",
    estaAgregandoInsumos: false,
    meseroAsignado: null,
    sugerenciasClientes: [],
    buscandoCliente: false,
    _timerCliente: null,
    modalCombo: false,
    insumoComboPendiente: null,
    categoriaSeleccionada: null,
    listaCategorias: [],
    insumosMenuDia: [],
    cargandoMenu: false,
    mostrarCarritoMobile: false,
  }),

  mounted() {
    this.idMesa = this.$route.params.id;
    this.idDelivery = this.$route.params.idDelivery || null;
    this.tipo_orden = this.$route.params.tipo_orden || "LOCAL";
    this.cliente = this.$route.params.cliente || "";
    this.direccion = this.$route.params.direccion || "";
    this.telefono = this.$route.params.telefono || "";
    this.insumosAnteriores = this.$route.params.insumosEnLista || [];
    this.meseroAsignado = this.$route.params.meseroAsignado || null;

    if (this.insumosAnteriores.length > 0) {
      this.estaAgregandoInsumos = true;
    }
    this.obtenerCategorias();
    this.obtenerMenuHoy();

    // Autofocus en búsqueda después de cargar
    setTimeout(() => {
      const input = document.querySelector("#busqueda");
      if (input) input.focus();
    }, 500);

    window.addEventListener('keydown', this.manejarAtajos);
  },

  beforeDestroy() {
    window.removeEventListener('keydown', this.manejarAtajos);
  },

  methods: {
    esInsumoCombo(insumo) {
      return (insumo && (insumo.tipoVenta || '') === 'COMBO')
    },
    etiquetaStockSugerencia(insumo) {
      if (this.esInsumoCombo(insumo)) {
        return { tipo: 'is-info is-light', texto: 'Menú (por componentes)' }
      }
      const s = Number(insumo.stock)
      if (!Number.isFinite(s) || s <= 0) {
        return { tipo: 'is-danger', texto: 'Sin stock' }
      }
      return { tipo: 'is-success is-light', texto: 'Stock: ' + s }
    },
    manejarAtajos(e) {
      if (e.key === 'F2') {
        e.preventDefault();
        const input = document.querySelector("#busqueda");
        if (input) input.focus();
      } else if (e.key === 'Enter' && e.ctrlKey) {
        e.preventDefault();
        if (this.insumosOrden.length === 0) return;

        if (this.estaAgregandoInsumos) {
          this.editarOrden();
        } else {
          this.realizarOrden();
        }
      }
    },

    buscarClienteOrden(q) {
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
    seleccionarClienteOrden(cliente) {
      if (!cliente) return;
      this.cliente = cliente.nombre + (cliente.apellido ? ' ' + cliente.apellido : '');
      if (cliente.telefono && !this.telefono) this.telefono = cliente.telefono;
      if (cliente.direccion && !this.direccion) this.direccion = cliente.direccion;
    },
    onProductoModificado() {
      // El total se calcula con la computed property
    },
    editarOrden() {
      let insumos = this.insumosAnteriores.concat(this.insumosOrden);
      let payload = {
        id: this.idMesa,
        idDelivery: this.idDelivery,
        tipo_orden: this.tipo_orden,
        insumos: insumos,
        total: this.total,
        atiende: this.meseroAsignado ? this.meseroAsignado.nombre : localStorage.getItem("nombreUsuario"),
        idUsuario: this.meseroAsignado ? this.meseroAsignado.id : localStorage.getItem("idUsuario"),
        cliente: this.cliente,
        direccion: this.direccion,
        telefono: this.telefono,
        idUsuarioSolicitante: localStorage.getItem("idUsuario"),
        rolSolicitante: localStorage.getItem("rol")
      };

      const endpoint = (this.tipo_orden === 'DELIVERY' || this.tipo_orden === 'LLEVAR') ? "registrar_delivery.php" : "editar_mesa.php";

      HttpService.registrar(payload, endpoint).then((resultado) => {
        if (resultado === true || (resultado && resultado.status === true)) {
          this.$toast({
            message: "Pedido actualizado",
            type: "is-success",
          });
          const scrollId = (this.tipo_orden === 'DELIVERY' || this.tipo_orden === 'LLEVAR') ? this.idDelivery : this.idMesa;
          this.$router.push({ name: "RealizarOrden", query: { scrollId, tipo: this.tipo_orden } });
        } else if (resultado && resultado.error === "stock") {
          this.$toast({
            message: resultado.mensaje || "Stock insuficiente para completar el pedido.",
            type: "is-danger",
          });
        } else if (!resultado || resultado === false) {
          this.$toast({
            message: "No se pudo actualizar el pedido.",
            type: "is-danger",
          });
        }
      });
    },

    realizarOrden() {
      if (this.tipo_orden === 'DELIVERY' && (!this.cliente || !this.direccion)) {
        this.$toast({
          message: "Cliente y Dirección son obligatorios para Delivery",
          type: "is-warning"
        });
        return;
      }

      let payload = {
        id: this.idMesa,
        idDelivery: this.idDelivery,
        tipo_orden: this.tipo_orden,
        insumos: this.insumosOrden,
        total: this.total,
        atiende: this.meseroAsignado ? this.meseroAsignado.nombre : localStorage.getItem("nombreUsuario"),
        idUsuario: this.meseroAsignado ? this.meseroAsignado.id : localStorage.getItem("idUsuario"),
        cliente: this.cliente,
        direccion: this.direccion,
        telefono: this.telefono,
        idUsuarioSolicitante: localStorage.getItem("idUsuario"),
        rolSolicitante: localStorage.getItem("rol")
      };

      const endpoint = (this.tipo_orden === 'DELIVERY' || this.tipo_orden === 'LLEVAR') ? "registrar_delivery.php" : "ocupar_mesa.php";

      HttpService.registrar(payload, endpoint).then((resultado) => {
        const okMesa = resultado === true;
        const okDelivery = resultado && resultado.status === true;
        if (okMesa || okDelivery) {
          this.$toast({
            message: "Pedido registrado",
            type: "is-success",
          });
          const scrollId = (this.tipo_orden === 'DELIVERY' || this.tipo_orden === 'LLEVAR') ? (resultado.idDelivery || this.idDelivery) : this.idMesa;
          this.$router.push({ name: "RealizarOrden", query: { scrollId, tipo: this.tipo_orden } });
        } else if (resultado && resultado.error === "stock") {
          this.$toast({
            message: resultado.mensaje || "Stock insuficiente para completar el pedido.",
            type: "is-danger",
          });
        } else if (resultado && resultado.error === "MESA_RESERVADA") {
          this.$buefy.dialog.alert({
            title: 'Mesa reservada',
            message: `Esta mesa está reservada hoy para <b>${resultado.cliente}</b> a las <b>${resultado.hora}</b>.<br><br>Solo puede abrirse desde la sección de <b>Gestión de Reservas</b> para asegurar que sea el cliente correcto.`,
            type: 'is-danger',
            confirmText: 'Entendido'
          });
        } else if (!resultado || resultado === false || (resultado && resultado.status === false)) {
          this.$toast({
            message: "No se pudo registrar el pedido.",
            type: "is-danger",
          });
        }
      });
    },

    eliminar(lineKey) {
      this.insumosOrden = this.insumosOrden.filter((r) => (r._lineId || r.id) !== lineKey);
    },

    buscarInsumo() {
      if (this.nombre) {
        this.categoriaSeleccionada = null;
        HttpService.obtenerConDatos(
          { insumo: this.nombre, ajusteStockVenta: true },
          "obtener_insumo_nombre.php"
        ).then((resultado) => {
          this.insumos = resultado;
        });
      } else {
        this.insumos = this.insumosMenuDia;
      }
    },

    obtenerMenuHoy() {
      const hoy = new Date().getDay() + 1; // MySQL 1=Sun... 7=Sat
      this.cargandoMenu = true;
      HttpService.obtenerConDatos(
        { dia: hoy, ajusteStockVenta: true },
        "obtener_menu_dia.php"
      ).then((resultado) => {
        this.insumosMenuDia = resultado || [];
        this.insumos = this.insumosMenuDia;
        this.cargandoMenu = false;
      });
    },

    obtenerCategorias() {
      HttpService.obtener("obtener_categorias.php").then(cats => {
        this.listaCategorias = cats || [];
      });
    },

    filtrarPorCategoria(idCategoria) {
      if (idCategoria === null) {
        this.categoriaSeleccionada = null;
        this.insumos = this.insumosMenuDia;
        return;
      }
      this.categoriaSeleccionada = idCategoria;
      this.cargandoMenu = true;
      HttpService.obtenerConDatos(
        { categoria: idCategoria, tipo: "", nombre: "", ajusteStockVenta: true },
        "obtener_insumos.php"
      ).then(resultado => {
        this.insumos = resultado || [];
        this.cargandoMenu = false;
      });
    },

    agregarInsumoAOrden(insumo) {
      if (insumo) {
        // Combo: el stock del insumo padre no aplica; la validación es por componentes al armar el menú y en el servidor.
        if ((insumo.tipoVenta || 'NORMAL') === 'COMBO') {
          if (!insumo.idComboPlantilla) {
            this.$toast({ message: 'Este producto combo no tiene plantilla asignada', type: 'is-danger' })
            setTimeout(() => (this.nombre = ''), 10)
            return
          }
          this.insumoComboPendiente = insumo;
          this.modalCombo = true;
          setTimeout(() => (this.nombre = ''), 10)
          return
        }
        if (insumo.stock !== undefined && insumo.stock <= 0) {
          this.$toast({ message: `⚠ "${insumo.nombre}" no tiene stock disponible`, type: 'is-danger' })
          setTimeout(() => (this.nombre = ''), 10)
          return
        }
        let indice = this.verificarSiExisteEnLista(insumo);
        if (indice >= 0) {
          this.aumentarCantidad(indice);
        } else {
          this.insumosOrden.unshift({
            _lineId: 'L' + Date.now() + '-' + Math.random().toString(36).slice(2, 9),
            id: insumo.id,
            codigo: insumo.codigo,
            nombre: insumo.nombre,
            precio: insumo.precio,
            tipo: insumo.tipo,
            tipoVenta: insumo.tipoVenta || 'NORMAL',
            caracteristicas: "",
            cantidad: 1,
            estado: "pendiente",
            _stock: insumo.stock,
          });
        }
        setTimeout(() => (this.nombre = ""), 10);
      }
    },

    onConfirmarCombo(evento) {
      const { n, detalleJson, resumenCombo } = evento;
      const ins = this.insumoComboPendiente;

      this.insumosOrden.unshift({
        _lineId: 'L' + Date.now() + '-' + Math.random().toString(36).slice(2, 9),
        id: ins.id,
        codigo: ins.codigo,
        nombre: ins.nombre,
        precio: ins.precio,
        tipo: ins.tipo,
        tipoVenta: 'COMBO',
        detalleJson,
        resumenCombo,
        caracteristicas: '',
        cantidad: n,
        estado: 'pendiente',
        _stock: ins.stock,
      });
      this.modalCombo = false;
      this.insumoComboPendiente = null;
    },

    verificarSiExisteEnLista(insumo) {
      if ((insumo.tipoVenta || 'NORMAL') === 'COMBO') return -1
      let lista = this.insumosOrden
      const idInsumo = insumo.id
      for (let i = 0; i < lista.length; i++) {
        if (lista[i].id === idInsumo && (lista[i].tipoVenta || 'NORMAL') !== 'COMBO') return i
      }
      return -1
    },

    aumentarCantidad(indice) {
      let lista = this.insumosOrden;
      let insumo = lista[indice];
      if ((insumo.tipoVenta || 'NORMAL') === 'COMBO') return

      if (insumo._stock !== undefined && insumo._stock > 0 && insumo.cantidad >= insumo._stock) {
        this.$toast({ message: `⚠ Solo hay ${insumo._stock} unidades de "${insumo.nombre}" disponibles`, type: 'is-warning' })
        return
      }

      insumo.cantidad++;

      // Mover el elemento al principio de la lista para que se vea más rápido
      lista.splice(indice, 1);
      lista.unshift(insumo);

      this.insumosOrden = lista;
    },
  },

  computed: {
    total() {
      let calc = 0;
      if (this.insumosAnteriores && this.insumosAnteriores.length > 0) {
        this.insumosAnteriores.forEach((insumo) => {
          if (!insumo.pagado) calc += parseFloat(insumo.cantidad || 0) * parseFloat(insumo.precio || 0);
        });
      }
      if (this.insumosOrden && this.insumosOrden.length > 0) {
        this.insumosOrden.forEach((insumo) => {
          calc += parseFloat(insumo.cantidad || 0) * parseFloat(insumo.precio || 0);
        });
      }
      return calc;
    },
    filteredDataObj() {
      return this.insumos;
    },
    insumosFiltrados() {
      return this.insumos;
    },
    tituloOrden() {
      if (this.tipo_orden === 'DELIVERY') return 'Pedido para Delivery';
      if (this.tipo_orden === 'LLEVAR') return 'Pedido para Llevar';
      return 'Tomar orden para la mesa #' + this.idMesa;
    }
  },
};
</script>

<style>
.sugerencia-card {
  height: 100%;
  border: 1px solid #e8e8e8;
  transition: box-shadow 0.15s;
}

.sugerencia-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.sugerencia-card.sin-stock {
  opacity: 0.55;
  border-color: #e0e0e0;
}

/* FAB Styles */
.fab-carrito {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 40;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  transition: transform 0.2s ease;
}
.fab-carrito:active {
  transform: scale(0.95);
}
.fab-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background-color: #ff3860;
  color: white;
  border-radius: 50%;
  padding: 2px 8px;
  font-size: 0.8rem;
  font-weight: bold;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* Horizontal Scroll Tabs */
.scroll-horizontal-tabs {
  flex-wrap: nowrap !important;
  overflow-x: auto;
  white-space: nowrap;
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
  padding-bottom: 5px;
}
.scroll-horizontal-tabs::-webkit-scrollbar {
  display: none; /* Chrome, Safari and Opera */
}
.scroll-horizontal-tabs li {
  flex-shrink: 0;
  margin-bottom: 0 !important;
}
</style>
