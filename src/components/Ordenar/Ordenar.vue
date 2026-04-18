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

    <b-modal :active.sync="modalCombo" has-modal-card trap-focus aria-close-label="Cerrar">
      <div class="modal-card" style="width: 100%; max-width: 900px">
        <header class="modal-card-head">
          <p class="modal-card-title">Armar menú: {{ insumoComboPendiente ? insumoComboPendiente.nombre : '' }}</p>
        </header>
        <section class="modal-card-body" style="position: relative; min-height: 120px; overflow-x: hidden;">
          <b-loading :is-full-page="false" :active="cargandoPlantillaCombo"></b-loading>
          <template v-if="plantillaCombo && !cargandoPlantillaCombo">
            <b-field label="Cantidad de almuerzos a armar" class="mb-5">
              <b-numberinput v-model="comboNumMenus" :min="1" :max="50" controls-position="compact" @input="resetearEleccionesCombo" size="is-medium"></b-numberinput>
            </b-field>

            <div class="notification is-info is-light py-2 px-3 mb-4">
              <b-icon icon="information" size="is-small" class="mr-1"></b-icon>
              Selecciona el total de platos para los <strong>{{ comboNumMenus }}</strong> almuerzos.
            </div>

            <div v-for="slot in plantillaCombo.slots || []" :key="'sl' + slot.id" class="mb-5">
              <div class="is-flex is-justify-content-space-between is-align-items-center mb-3">
                <h3 class="title is-5 mb-0 has-text-primary">
                  {{ slot.etiqueta }}
                </h3>
                <b-tag :type="totalElegidoEnSlot(slot.id) === comboNumMenus ? 'is-success' : 'is-warning'" size="is-medium" rounded>
                   {{ totalElegidoEnSlot(slot.id) }} / {{ comboNumMenus }}
                </b-tag>
              </div>

              <div class="columns is-multiline">
                <div v-for="op in slot.opciones" :key="'op' + op.id" class="column is-6-tablet is-12-mobile">
                  <div class="box p-3 is-flex is-justify-content-space-between is-align-items-center" 
                       :style="obtenerCantidadElegida(slot.id, op.id_insumo) > 0 ? 'border: 2px solid #48c78e; background: #f6fffa;' : ''">
                    <div style="flex: 1;">
                      <p class="has-text-weight-bold is-size-6">{{ op.nombre_insumo }}</p>
                      <p class="is-size-7 has-text-grey">
                        Disp: {{ obtenerStockDisponible(op.id_insumo, op.stock, slot.id) }}
                      </p>
                    </div>
                    <div class="is-flex is-align-items-center">
                      <b-button size="is-small" icon-left="minus" 
                                :disabled="obtenerCantidadElegida(slot.id, op.id_insumo) <= 0"
                                @click="ajustarCantidadOpcion(slot.id, op, -1)"></b-button>
                      <span class="mx-3 has-text-weight-bold is-size-5" style="min-width: 20px; text-align: center;">
                        {{ obtenerCantidadElegida(slot.id, op.id_insumo) }}
                      </span>
                      <b-button type="is-primary" size="is-small" icon-left="plus"
                                :disabled="totalElegidoEnSlot(slot.id) >= comboNumMenus || (op.stock !== undefined && (op.stock - obtenerCantidadElegida(slot.id, op.id_insumo) <= 0))"
                                @click="ajustarCantidadOpcion(slot.id, op, 1)"></b-button>
                    </div>
                  </div>
                </div>
              </div>
              <hr class="my-4">
            </div>
          </template>
        </section>
        <footer class="modal-card-foot is-justify-content-flex-end">
          <b-button @click="modalCombo = false" size="is-medium">Cancelar</b-button>
          <b-button type="is-primary" icon-left="check" size="is-medium" :disabled="!plantillaCombo || cargandoPlantillaCombo" @click="confirmarComboAlPedido">Agregar al pedido</b-button>
        </footer>
      </div>
    </b-modal>

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
          <div
            class="card sugerencia-card"
            :class="{ 'sin-stock': !esInsumoCombo(insumo) && (!Number.isFinite(Number(insumo.stock)) || Number(insumo.stock) <= 0) }"
          >
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
                    :type="etiquetaStockSugerencia(insumo).tipo"
                    size="is-small"
                    rounded
                  >
                    {{ etiquetaStockSugerencia(insumo).texto }}
                  </b-tag>
                </div>
                <b-button
                  type="is-primary"
                  size="is-small"
                  icon-left="plus"
                  :disabled="!esInsumoCombo(insumo) && (!Number.isFinite(Number(insumo.stock)) || Number(insumo.stock) <= 0)"
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
    modalCombo: false,
    cargandoPlantillaCombo: false,
    insumoComboPendiente: null,
    plantillaCombo: null,
    comboNumMenus: 1,
    comboElecciones: [],
    eleccionesBulk: {}, // Estructura: { [slotId]: { [idInsumo]: cantidad, ... } }
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

    eliminar(lineKey) {
      this.insumosOrden = this.insumosOrden.filter((r) => (r._lineId || r.id) !== lineKey);
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
        // Combo: el stock del insumo padre no aplica; la validación es por componentes al armar el menú y en el servidor.
        if ((insumo.tipoVenta || 'NORMAL') === 'COMBO') {
          if (!insumo.idComboPlantilla) {
            this.$toast({ message: 'Este producto combo no tiene plantilla asignada', type: 'is-danger' })
            setTimeout(() => (this.nombre = ''), 10)
            return
          }
          this.iniciarModalCombo(insumo)
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
          this.insumosOrden.push({
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
        this.calcularTotal();
      }
    },

    async iniciarModalCombo(insumo) {
      this.insumoComboPendiente = insumo
      this.modalCombo = true
      this.cargandoPlantillaCombo = true
      this.plantillaCombo = null
      try {
        const pl = await HttpService.obtenerConDatos({ id: insumo.idComboPlantilla }, 'obtener_plantilla_combo_id.php')
        if (!pl || !pl.slots || !pl.slots.length) {
          this.$toast({ message: 'Plantilla de combo vacía o inexistente', type: 'is-danger' })
          this.modalCombo = false
          this.cargandoPlantillaCombo = false
          return
        }
        this.plantillaCombo = pl
        this.comboNumMenus = 1
        // Inicializar eleccionesBulk
        const bulk = {}
        pl.slots.forEach(s => {
          bulk[String(s.id)] = {}
        })
        this.eleccionesBulk = bulk
        // this.$nextTick(() => this.sincronizarMatricesCombo()) // Ya no es necesario
      } catch (e) {
        this.$toast({ message: 'No se pudo cargar la plantilla del combo', type: 'is-danger' })
        this.modalCombo = false
      }
      this.cargandoPlantillaCombo = false
    },

    resetearEleccionesCombo() {
      if (!this.plantillaCombo || !this.plantillaCombo.slots) return
      const bulk = {}
      this.plantillaCombo.slots.forEach(s => {
        bulk[String(s.id)] = {}
      })
      this.eleccionesBulk = bulk
    },

    obtenerStockDisponible(idInsumo, stockBase, slotId) {
      if (stockBase === undefined) return 999;
      let consumidoEnEsteId = 0;
      const slotChoices = this.eleccionesBulk[String(slotId)] || {};
      Object.keys(slotChoices).forEach(id => {
        if (String(id) === String(idInsumo)) consumidoEnEsteId = slotChoices[id];
      });
      return stockBase - consumidoEnEsteId;
    },

    ajustarCantidadOpcion(slotId, opcion, delta) {
      const sid = String(slotId);
      const oid = String(opcion.id_insumo);
      const actual = this.eleccionesBulk[sid][oid] || 0;
      const nuevo = actual + delta;
      
      if (nuevo < 0) return;
      if (delta > 0) {
        // Validar stock
        if (opcion.stock !== undefined && actual >= opcion.stock) return;
        // Validar que no pase del total de menus
        if (this.totalElegidoEnSlot(slotId) >= this.comboNumMenus) return;
      }
      
      this.$set(this.eleccionesBulk[sid], oid, nuevo);
    },

    totalElegidoEnSlot(slotId) {
      const choices = this.eleccionesBulk[String(slotId)] || {};
      return Object.values(choices).reduce((sum, val) => sum + val, 0);
    },

    obtenerCantidadElegida(slotId, idInsumo) {
      const slot = this.eleccionesBulk[String(slotId)];
      return (slot && slot[String(idInsumo)]) || 0;
    },

    confirmarComboAlPedido() {
      const ins = this.insumoComboPendiente
      const n = this.comboNumMenus
      if (!ins || !this.plantillaCombo) return
      const slots = this.plantillaCombo.slots || []
      
      // 1. Validar que todos los slots estén completos
      for (const s of slots) {
        if (this.totalElegidoEnSlot(s.id) !== n) {
          this.$buefy.toast.open({
            message: `Por favor completa la selección de ${s.etiqueta} (${this.totalElegidoEnSlot(s.id)}/${n})`,
            type: 'is-warning'
          })
          return
        }
      }

      // 2. Transformar eleccionesBulk en Array de Menús (Legado)
      // Generar listas planas de IDs por slot
      const listasPorSlot = {}
      slots.forEach(s => {
        const sid = String(s.id)
        const lista = []
        const choices = this.eleccionesBulk[sid] || {}
        Object.keys(choices).forEach(oid => {
          for (let i = 0; i < choices[oid]; i++) {
            lista.push(oid)
          }
        })
        listasPorSlot[sid] = lista
      })

      // Reconstruir comboElecciones
      const menusTraducidos = []
      for (let i = 0; i < n; i++) {
        const row = {}
        slots.forEach(s => {
          row[String(s.id)] = listasPorSlot[String(s.id)][i]
        })
        menusTraducidos.push(row)
      }

      // 3. Generar Resumen y Detalle
      const conteos = {}
      const ordenComponentes = []
      
      menusTraducidos.forEach((row) => {
        slots.forEach(s => {
          const val = row[String(s.id)]
          const op = s.opciones.find(o => String(o.id_insumo) === String(val))
          const nombre = op ? op.nombre_insumo : ('#' + val)
          if (!conteos[nombre]) {
            conteos[nombre] = 0
            ordenComponentes.push(nombre)
          }
          conteos[nombre]++
        })
      })

      const menus = menusTraducidos.map((row) => ({ slots: { ...row } }))
      const detalleJson = { menus }
      const resumenCombo = ordenComponentes.map(nom => `${conteos[nom]} ${nom}`).join('\n')

      this.insumosOrden.push({
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
      })
      this.modalCombo = false
      this.calcularTotal()
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
