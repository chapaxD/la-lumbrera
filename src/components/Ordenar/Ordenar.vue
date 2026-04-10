<template>
  <section>
    <p class="title is-1 has-text-weight-bold">
      <b-icon :icon="tipo_orden === 'DELIVERY' ? 'truck-delivery' : tipo_orden === 'LLEVAR' ? 'walk' : 'pen'" size="is-large" type="is-primary"> </b-icon>
      {{ tipo_orden === 'DELIVERY' ? 'Pedido para Delivery' : tipo_orden === 'LLEVAR' ? 'Pedido para Llevar' : 'Tomar orden para la mesa #' + idMesa }}
    </p>

    <div class="columns">
      <div class="column">
        <b-field label="Nombre del cliente (Opcional)">
          <b-autocomplete
            v-model="cliente"
            :data="sugerenciasClientes"
            placeholder="Ej. Don Paco"
            icon="account-search"
            field="nombre_completo"
            size="is-medium"
            :loading="buscandoCliente"
            @typing="buscarClienteOrden"
            @select="seleccionarClienteOrden"
            clearable
          >
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
        <span
          class="has-text-weight-bold has-text-primary"
          style="font-size: 2.5em"
        >
          ${{ total }}
        </span>
        <b-button
          type="is-primary"
          size="is-large"
          icon-left="basket-check"
          style="margin-top: 18px"
          @click="realizarOrden"
          v-if="!estaAgregandoInsumos"
        >
          Ordenar
        </b-button>
        <b-button
          type="is-primary"
          size="is-large"
          icon-left="basket-check"
          style="margin-top: 18px"
          @click="editarOrden"
          v-if="estaAgregandoInsumos"
        >
          Añadir
        </b-button>
      </div>
    </div>

    <b-field>
      <b-autocomplete
        size="is-large"
        v-model="nombre"
        placeholder="Nombre del platillo o bebida"
        :data="filteredDataObj"
        field="nombre"
        @input="buscarInsumo"
        @select="(option) => agregarInsumoAOrden(option)"
        :clearable="true"
        keep-first
        id="busqueda"
      >
      </b-autocomplete>
    </b-field>

    <div class="columns is-desktop">
      <div
        class="column"
        v-if="insumosOrden.length > 0 || insumosAnteriores.length > 0"
      >
        <p class="has-text-primary size-is-4" v-if="insumosOrden.length > 0">
          <b-icon icon="plus"></b-icon>
          Insumos agregados
        </p>

        <productos-orden 
        :lista="insumosOrden" 
        :tipo="'nuevo'" 
        @modificado="onProductoModificado" 
        @quitar="eliminar"
        v-if="insumosOrden.length > 0"/> 

        <p
          class="has-text-primary size-is-4"
          v-if="insumosAnteriores.length > 0"
        >
          <b-icon icon="basket"></b-icon>
          Insumos servidos
        </p>
        <productos-orden :lista="insumosAnteriores" :tipo="'entregado'" v-if="insumosAnteriores.length > 0"/>
       
      </div>

    </div>

    <!-- Sugerencias del día en grilla 4 columnas -->
    <div v-if="insumos.length > 0" class="mt-4">
      <p class="title is-6 has-text-weight-bold has-text-grey mb-3">
        <b-icon icon="silverware-fork-knife" size="is-small" class="mr-1"></b-icon>
        Sugerencias del día
      </p>
      <div class="columns is-multiline">
        <div
          class="column is-3-widescreen is-4-desktop is-6-tablet is-12-mobile"
          v-for="insumo in insumos"
          :key="insumo.id"
        >
          <div class="card sugerencia-card" :class="{ 'sin-stock': insumo.stock <= 0 }">
            <div class="card-content p-3">
              <div class="is-flex is-justify-content-space-between is-align-items-flex-start">
                <div style="min-width:0; flex:1;">
                  <p class="has-text-weight-bold is-size-6" style="line-height:1.2; word-break:break-word;">
                    <b-icon
                      size="is-small"
                      icon="noodles"
                      class="has-text-info mr-1"
                      v-if="insumo.tipo === 'PLATILLO'"
                    ></b-icon>
                    <b-icon
                      icon="cup"
                      size="is-small"
                      class="has-text-success mr-1"
                      v-if="insumo.tipo === 'BEBIDA'"
                    ></b-icon>
                    {{ insumo.nombre }}
                  </p>
                  <p class="is-size-7 has-text-grey mt-1" v-if="insumo.descripcion">{{ insumo.descripcion }}</p>
                </div>
              </div>
              <div class="is-flex is-justify-content-space-between is-align-items-center mt-2">
                <div class="is-flex" style="gap:4px; flex-wrap:wrap;">
                  <span class="has-text-weight-bold has-text-primary is-size-5">${{ insumo.precio }}</span>
                  <b-tag
                    :type="insumo.stock <= 0 ? 'is-danger' : 'is-success is-light'"
                    size="is-small"
                    rounded
                  >
                    {{ insumo.stock <= 0 ? 'Sin stock' : 'Stock: ' + insumo.stock }}
                  </b-tag>
                </div>
                <b-button
                  type="is-primary"
                  size="is-small"
                  icon-left="plus"
                  :disabled="insumo.stock <= 0"
                  @click="agregarInsumoAOrden(insumo)"
                >Agregar</b-button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
<script>
import HttpService from "../../Servicios/HttpService";
import ProductosOrden from "./ProductosOrden.vue";
export default {
  name: "Ordenar",
  components: { ProductosOrden },
  data: () => ({
    idMesa: "",
    idDelivery: null,
    tipo_orden: "LOCAL",
    insumos: [],
    nombre: "",
    insumosAnteriores: [],
    insumosOrden: [],
    total: 0,
    cliente: "",
    direccion: "",
    telefono: "",
    estaAgregandoInsumos: false,
    meseroAsignado: null,
    sugerenciasClientes: [],
    buscandoCliente: false,
    _timerCliente: null,
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
      this.calcularTotal();
      this.estaAgregandoInsumos = true;
    }
    this.obtenerMenuHoy();
    
    // Autofocus en búsqueda después de cargar
    setTimeout(() => {
        const input = document.querySelector("#busqueda");
        if(input) input.focus();
    }, 500);

    window.addEventListener('keydown', this.manejarAtajos);
  },

  beforeDestroy() {
    window.removeEventListener('keydown', this.manejarAtajos);
  },

  methods: {
    manejarAtajos(e) {
      if (e.key === 'F2') {
        e.preventDefault();
        const input = document.querySelector("#busqueda");
        if(input) input.focus();
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
      if (cliente.telefono && !this.telefono)   this.telefono  = cliente.telefono;
      if (cliente.direccion && !this.direccion) this.direccion = cliente.direccion;
    },
    onProductoModificado(){
      this.calcularTotal()
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
          this.$router.push({ name: "RealizarOrden" });
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
          this.$router.push({ name: "RealizarOrden" });
        } else if (resultado && resultado.error === "stock") {
          this.$toast({
            message: resultado.mensaje || "Stock insuficiente para completar el pedido.",
            type: "is-danger",
          });
        } else if (!resultado || resultado === false || (resultado && resultado.status === false)) {
          this.$toast({
            message: "No se pudo registrar el pedido.",
            type: "is-danger",
          });
        }
      });
    },

    eliminar(idInsumo) {
      let lista = this.insumosOrden;
      for (let i = 0; i < lista.length; i++) {
        if (lista[i].id === idInsumo) {
          lista.splice(i, 1);
        }
      }
      this.insumosOrden = lista;
      this.calcularTotal();
    },

    calcularTotal() {
      let total = 0;
      let totalAnterior = 0;
      if (this.insumosAnteriores.length > 0) {
        this.insumosAnteriores.forEach((insumo) => {
          // No sumar ítems ya pagados previamente
          if (!insumo.pagado) {
            totalAnterior +=
              parseFloat(insumo.cantidad) * parseFloat(insumo.precio);
          }
        });
      }
      this.insumosOrden.forEach((insumo) => {
        total += parseFloat(insumo.cantidad) * parseFloat(insumo.precio);
      });
      this.total = total + totalAnterior;
    },

    buscarInsumo() {
      if (this.nombre) {
        HttpService.obtenerConDatos(
          { insumo: this.nombre, ajusteStockVenta: true },
          "obtener_insumo_nombre.php"
        ).then((resultado) => {
          this.insumos = resultado;
        });
      } else {
        this.obtenerMenuHoy();
      }
    },

    obtenerMenuHoy() {
      const hoy = new Date().getDay() + 1; // MySQL 1=Sun... 7=Sat
      HttpService.obtenerConDatos(
        { dia: hoy, ajusteStockVenta: true },
        "obtener_menu_dia.php"
      ).then((resultado) => {
        this.insumos = resultado || [];
      });
    },

    agregarInsumoAOrden(insumo) {
      if (insumo) {
        if (insumo.stock !== undefined && insumo.stock <= 0) {
          this.$toast({ message: `⚠ "${insumo.nombre}" no tiene stock disponible`, type: 'is-danger' })
          setTimeout(() => (this.nombre = ''), 10)
          return
        }
        let indice = this.verificarSiExisteEnLista(insumo.id);
        if (indice >= 0) {
          this.aumentarCantidad(indice);
        } else {
          this.insumosOrden.push({
            id: insumo.id,
            codigo: insumo.codigo,
            nombre: insumo.nombre,
            precio: insumo.precio,
            tipo: insumo.tipo,
            caracteristicas: "",
            cantidad: 1,
            estado: "pendiente",
            _stock: insumo.stock,
          });
        }
        setTimeout(() => (this.nombre = ""), 10);
        this.calcularTotal();
      }
    },

    verificarSiExisteEnLista(idInsumo) {
      let lista = this.insumosOrden;
      for (let i = 0; i < lista.length; i++) {
        if (lista[i].id === idInsumo) return i;
      }
      return -1;
    },

    aumentarCantidad(indice) {
      let lista = this.insumosOrden;
      let insumo = lista[indice];

      if (insumo._stock !== undefined && insumo._stock > 0 && insumo.cantidad >= insumo._stock) {
        this.$toast({ message: `⚠ Solo hay ${insumo._stock} unidades de "${insumo.nombre}" disponibles`, type: 'is-warning' })
        return
      }

      insumo.cantidad++;

      lista[indice] = insumo;
      this.insumosOrden = lista;
    },
  },

  computed: {
    filteredDataObj() {
      // El servidor ya filtra por nombre; retornamos directo para evitar
      // que el doble filtrado muestre vacío mientras llega la respuesta async
      return this.insumos;
    },
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
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.sugerencia-card.sin-stock {
  opacity: 0.55;
  border-color: #e0e0e0;
}
</style>
