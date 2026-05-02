<template>
    <section @click="desbloquearAudioSiHaceFalta">
        <div class="columns is-mobile is-multiline mb-4">
            <div class="column is-12-mobile is-6-tablet">
                <p class="title is-1 has-text-weight-bold">
                    <b-icon icon="order-bool-ascending-variant" size="is-large" type="is-primary"></b-icon>
                    Realizar orden
                </p>
                <div class="field mt-2 is-flex is-align-items-center" style="gap: 1rem; flex-wrap: wrap;">
                    <b-field class="mb-0">
                        <b-radio-button v-model="vistaActual" native-value="mapa" type="is-primary is-light">
                            <b-icon icon="view-module"></b-icon>
                            <span>Mapa de Mesas</span>
                        </b-radio-button>
                        <b-radio-button v-model="vistaActual" native-value="lista" type="is-primary is-light">
                            <b-icon icon="view-list"></b-icon>
                            <span>Lista de Pedidos</span>
                        </b-radio-button>
                    </b-field>
                    <b-button v-if="rol !== 'mesero'" type="is-success is-light" icon-left="cash-register" @click="mostrarModalCaja = true">
                        Estado de Caja
                    </b-button>
                </div>
                <p v-if="!audioListo" class="is-size-7 has-text-info mt-2 mb-0">
                    Tocá la pantalla una vez para activar el sonido cuando cocina marque un plato listo.
                </p>
            </div>
            <div class="column is-12-mobile is-6-tablet has-text-right-tablet">
                <div class="buttons is-right">
                    <b-button type="is-success" icon-left="table-plus" size="is-medium" class="is-responsive"
                        @click="abrirMesaRapido">Mesa</b-button>
                    <b-button type="is-warning" icon-left="walk" size="is-medium" class="is-responsive"
                        @click="nuevoParaLlevar">Llevar</b-button>
                    <b-button type="is-info" icon-left="truck-delivery" size="is-medium" class="is-responsive"
                        @click="nuevoDelivery">Delivery</b-button>
                </div>
            </div>
        </div>

        <div class="columns is-multiline" v-if="vistaActual === 'mapa'">
            <!-- Skeleton Screens para la primera carga -->
            <template v-if="cargando">
                <div class="column is-4-desktop is-6-tablet" v-for="i in 6" :key="'skel-mesa-' + i">
                    <div class="box">
                        <div class="is-flex is-justify-content-space-between mb-4">
                            <b-skeleton width="45%" height="32px" animated></b-skeleton>
                            <b-skeleton width="25%" height="32px" animated></b-skeleton>
                        </div>
                        <b-skeleton width="80%" animated></b-skeleton>
                        <b-skeleton width="60%" animated></b-skeleton>
                        <b-skeleton width="100%" height="45px" class="mt-4" animated></b-skeleton>
                    </div>
                </div>
            </template>

            <template v-else>
                <!-- Mesas Locales -->
                <div class="column is-4-desktop is-6-tablet" v-for="mesa in mesas" :key="'mesa-' + mesa.mesa.idMesa"
                    :id="'mesa-' + mesa.mesa.idMesa">

                    <tarjeta-mesa
                        :mesa="mesa"
                        :puede-acceder="puedeAccederOrden(mesa.mesa.idUsuario)"
                        :checked-rows="checkedRowsMap[mesa.mesa.idMesa] || []"
                        @update:checked-rows="$set(checkedRowsMap, mesa.mesa.idMesa, $event)"
                        :checkbox-position="checkboxPosition"
                        :checkbox-type="checkboxType"
                        :rol="rol"
                        :datos="datos"
                        @ocuparMesa="ocuparMesa"
                        @cobrar="cobrar"
                        @imprimirPrecuenta="imprimirPrecuenta"
                        @compartirMesa="compartirMesa"
                        @marcarInsumosEntregados="marcarInsumosEntregados"
                        @cancelarOrden="cancelarOrden"
                        @imprimirComandaMesa="imprimirComandaMesa"
                        @solicitarCambioMesa="solicitarCambioMesa"
                        @entregarOrdenPagada="entregarOrdenPagada"
                        @liberarMesa="liberarMesa"
                        @imprimirComprobanteDesdeMesa="imprimirComprobanteDesdeMesa"
                    />
                </div>

                <!-- Deliveries Activos -->
                <div class="column is-4-desktop is-6-tablet" v-for="del in deliveriesNormales"
                    :key="'delivery-' + del.delivery.idDelivery" :id="'delivery-' + del.delivery.idDelivery">

                    <tarjeta-delivery
                        :del="del"
                        :puede-acceder="puedeAccederOrden(del.delivery.idUsuario)"
                        :rol="rol"
                        tipo="DELIVERY"
                        @cobrarDelivery="cobrarDelivery"
                        @imprimirPrecuenta="imprimirPrecuenta"
                        @editarDelivery="editarDelivery"
                        @cancelarDelivery="cancelarDelivery"
                        @imprimirComandaDelivery="imprimirComandaDelivery"
                        @solicitarCambioMesa="solicitarCambioMesa"
                        @entregarOrdenPagada="entregarOrdenPagada"
                    />
                </div>

                <!-- Para llevar activos -->
                <div class="column is-4-desktop is-6-tablet" v-for="del in paraLlevar"
                    :key="'llevar-' + del.delivery.idDelivery" :id="'llevar-' + del.delivery.idDelivery">
                    <tarjeta-delivery
                        :del="del"
                        :puede-acceder="puedeAccederOrden(del.delivery.idUsuario)"
                        :rol="rol"
                        tipo="LLEVAR"
                        @cobrarDelivery="cobrarDelivery"
                        @imprimirPrecuenta="imprimirPrecuenta"
                        @editarParaLlevar="editarParaLlevar"
                        @imprimirComprobanteDesdeDelivery="imprimirComprobanteDesdeDelivery"
                        @cancelarDelivery="cancelarDelivery"
                        @imprimirComandaDelivery="imprimirComandaDelivery"
                        @solicitarCambioMesa="solicitarCambioMesa"
                        @entregarOrdenPagada="entregarOrdenPagada"
                    />
                </div>
            </template>
        </div>

        <!-- Vista de Lista (Componente Extraído) -->
        <div v-else-if="vistaActual === 'lista' && !cargando && ordenesUnificadas.length === 0"
            class="has-text-centered py-6 is-flex is-flex-direction-column is-align-items-center" style="animation: fadeIn 0.5s;">
            <div class="mb-4 p-5" style="border-radius: 50%; background: rgba(200, 230, 255, 0.3); display: inline-block;">
                <b-icon icon="clipboard-text-off-outline" type="is-info" custom-size="fa-5x" style="font-size: 5rem; opacity: 0.7;"></b-icon>
            </div>
            <p class="title is-3 mt-3 has-text-info-dark">¡Sin pedidos activos!</p>
            <p class="subtitle is-5 has-text-grey mt-2">Todas las mesas están libres.<br><small>Usá los botones de arriba para abrir una mesa, llevar o delivery.</small></p>
        </div>
        <lista-ordenes v-else-if="vistaActual === 'lista'" :ordenes="ordenesUnificadas" :cargando="cargando" :rol="rol"
            :idUsuarioActual="idUsuarioActual" :datos="datos" @entregar="o => entregarOrdenPagada(o.tipoRef, o.idRef)"
            @servir="servirMesaCompleta" @liberar="o => liberarMesa(o.tipoRef, o.idRef)"
            @cobrar="o => o.tipoRef === 'LOCAL' ? cobrar(o.dataOriginal) : cobrarDelivery(o.dataOriginal)"
            @comanda="o => o.tipoRef === 'LOCAL' ? imprimirComandaMesa(o.dataOriginal) : imprimirComandaDelivery(o.dataOriginal)"
            @ticket="o => o.tipoRef === 'LOCAL' ? imprimirComprobanteDesdeMesa(o.dataOriginal) : imprimirComprobanteDesdeDelivery(o.dataOriginal)"
            @detalle="o => imprimirPrecuenta(o.dataOriginal, o.tipoRef)"
            @agregar="o => o.tipoRef === 'LOCAL' ? ocuparMesa(o.dataOriginal) : (o.tipoRef === 'LLEVAR' ? editarParaLlevar(o.dataOriginal) : editarDelivery(o.dataOriginal))"
            @compartir="o => compartirMesa(o.dataOriginal)"
            @cambiar="o => solicitarCambioMesa(o.dataOriginal, o.tipoRef)"
            @cancelar="o => o.tipoRef === 'LOCAL' ? cancelarOrden(o.idRef) : cancelarDelivery(o.idRef)"></lista-ordenes>
        <ticket @impreso="onImpreso" :venta="this.ventaSeleccionada" :insumos="insumosSeleccionados" :datosLocal="datos"
            :logo="logo" v-if="mostrarTicket"></ticket>

        <modal-ticket-detalle :active="mostrarTicketMesero" modo="TICKET" :nombreLocal="datos.nombre"
            :venta="ventaSeleccionada" :insumos="insumosSeleccionados"
            @close="mostrarTicketMesero = false"></modal-ticket-detalle>

        <modal-ticket-detalle :active="mostrarComandaMesero" modo="COMANDA" :comanda="comandaSeleccionada"
            @close="mostrarComandaMesero = false"></modal-ticket-detalle>

        <modal-cobro :active="mostrarModalCobro" :elemento="elementoCobro" :tipoCobro="tipoCobro"
            @close="mostrarModalCobro = false" @confirmar="onConfirmarPago"></modal-cobro>

        <modal-cliente :active="mostrarModalCliente" :tipoOrden="pendingTipoOrden" @close="mostrarModalCliente = false"
            @confirmar="onConfirmarCliente"></modal-cliente>

        <modal-mesero :active="mostrarModalMesero" :meseros="meseros" @close="mostrarModalMesero = false"
            @confirmar="onConfirmarMesero"></modal-mesero>

        <b-modal :active.sync="mostrarModalCaja" has-modal-card trap-focus>
            <widget-caja></widget-caja>
        </b-modal>
    </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'
import Ticket from '../Ventas/Ticket.vue'
import Utiles from '../../Servicios/Utiles'
import ListaOrdenes from './ListaOrdenes.vue'
import ModalCobro from './ModalCobro.vue'
import ModalCliente from './ModalCliente.vue'
import ModalMesero from './ModalMesero.vue'
import ModalTicketDetalle from './ModalTicketDetalle.vue'
import WidgetCaja from '../Caja/WidgetCaja.vue'
import TarjetaMesa from './TarjetaMesa.vue'
import TarjetaDelivery from './TarjetaDelivery.vue'

export default {
    name: "RealizarOrden",
    components: { Ticket, ListaOrdenes, ModalCobro, ModalCliente, ModalMesero, ModalTicketDetalle, WidgetCaja, TarjetaMesa, TarjetaDelivery },

    data() {
        return {
            Utiles,
            datos: {},
            logo: null,
            checkboxPosition: 'left',
            checkboxType: 'is-primary',
            checkedRowsMap: {},
            mesas: [],
            deliveries: [],
            cargando: false,
            mostrarTicket: false,
            ventaSeleccionada: {},
            insumosSeleccionados: [],
            mostrarModalCobro: false,
            mostrarModalCaja: false,
            imprimirAlFinal: false,
            elementoCobro: null, // Puede ser mesa o delivery
            tipoCobro: 'LOCAL',
            rol: localStorage.getItem('rol') || '',
            idUsuarioActual: localStorage.getItem('idUsuario') || '',
            pago: {
                metodo: 'EFECTIVO',
                montoEfectivo: 0,
                montoTarjeta: 0,
                montoQR: 0,
                montoRecibido: 0
            },
            cajaAbierta: false,
            timer: null,
            meseros: [],
            mostrarModalMesero: false,
            meseroAsignadoId: null,
            _pendingOcuparMesa: null,
            _pendingOcuparParams: null,
            // Modal cliente para llevar / delivery
            mostrarModalCliente: false,
            pendingTipoOrden: 'LLEVAR',
            _timerCliente: null,
            audioCampanaListo: null,
            audioListo: false,
            _debounceSonidoListo: null,
            ultimoIdInteractuado: null,
            vistaActual: localStorage.getItem('pos_vista_preferida') || 'mapa',
            mostrarTicketMesero: false,
            mostrarComandaMesero: false,
            comandaSeleccionada: null,
        }
    },

    computed: {
        deliveriesNormales() {
            let filtrada = this.deliveries.filter(d => (d.delivery.tipo_orden || 'DELIVERY') === 'DELIVERY')
            if (this.rol === 'mesero') {
                filtrada = filtrada.filter(d => this.puedeAccederOrden(d.delivery.idUsuario))
            }
            return filtrada
        },
        paraLlevar() {
            let filtrada = this.deliveries.filter(d => d.delivery.tipo_orden === 'LLEVAR')
            if (this.rol === 'mesero') {
                filtrada = filtrada.filter(d => this.puedeAccederOrden(d.delivery.idUsuario))
            }
            return filtrada
        },

        ordenesUnificadas() {
            const getPrioridad = (o) => {
                const insumos = o.insumos || []
                const esBebida = (i) => {
                    const t = (i.tipo || '').toUpperCase()
                    const c = (i.categoria || '').toUpperCase()
                    return t === 'BEBIDA' || c === 'BEBIDA' || c === 'BEBIDAS'
                }
                // El estado de la ORDEN tiene precedencia sobre el estado de los ítems
                // Prioridad 0: Entregada pero sin cobrar (urgente)
                if (o.estado === 'entregada' && !o.esPagada) return 0
                // Prioridad 2: Pagada (por despachar/entregar)
                if (o.estado === 'pagada') return 2
                // Prioridad 4: Entregada y cobrada — ya terminó
                if (o.estado === 'entregada') return 4

                // Solo para órdenes activas (ocupada / pendiente): revisar ítems
                const itemsCocina = insumos.filter(i => !esBebida(i))
                const hayListoCocina = itemsCocina.some(i => i.estado === 'listo')
                const soloBebidasListas = itemsCocina.length === 0 && insumos.length > 0
                // Prioridad 1: Comida lista en cocina (o pedido solo de bebidas)
                if (hayListoCocina || soloBebidasListas) return 1
                // Prioridad 3: En preparación
                if (insumos.some(i => i.estado === 'pendiente')) return 3
                return 4
            }

            const lista = []

                // Procesar Mesas
                ; (Array.isArray(this.mesas) ? this.mesas : []).forEach(m => {
                    if (!m || !m.mesa || m.mesa.estado === 'libre') return
                    const obj = {
                        idUnique: 'mesa-' + m.mesa.idMesa,
                        tipoRef: 'LOCAL',
                        idRef: m.mesa.idMesa,
                        cliente: m.mesa.cliente,
                        atiende: m.mesa.atiende,
                        idUsuario: m.mesa.idUsuario,
                        total: m.mesa.total,
                        estado: m.mesa.estado,
                        created_at: m.mesa.created_at,
                        insumos: m.insumos || [],
                        esPagada: (m.insumos || []).length > 0 && (m.insumos || []).every(i => i.pagado == 1),
                        dataOriginal: m
                    }
                    obj.prioridad = getPrioridad(obj)
                    lista.push(obj)
                })

                // Procesar Deliveries y Para Llevar
                ; (Array.isArray(this.deliveries) ? this.deliveries : []).forEach(d => {
                    if (!d || !d.delivery) return
                    const obj = {
                        idUnique: (d.delivery.tipo_orden === 'LLEVAR' ? 'llevar-' : 'delivery-') + d.delivery.idDelivery,
                        tipoRef: d.delivery.tipo_orden || 'DELIVERY',
                        idRef: d.delivery.idDelivery,
                        cliente: d.delivery.cliente,
                        atiende: d.delivery.atiende,
                        idUsuario: d.delivery.idUsuario,
                        total: d.delivery.total,
                        estado: d.delivery.estado_orden || 'pendiente',
                        created_at: d.delivery.created_at,
                        insumos: d.insumos || [],
                        esPagada: (d.insumos || []).length > 0 && (d.insumos || []).every(i => i.pagado == 1),
                        dataOriginal: d
                    }
                    obj.prioridad = getPrioridad(obj)
                    lista.push(obj)
                })

            // Filtrar si es mesero para que no vea órdenes ajenas en la lista
            let filtrada = lista;
            if (this.rol === 'mesero') {
                filtrada = lista.filter(o => this.puedeAccederOrden(o.idUsuario));
            }

            return filtrada.sort((a, b) => {
                if (a.prioridad !== b.prioridad) return a.prioridad - b.prioridad
                const fechaA = new Date(a.created_at || 0)
                const fechaB = new Date(b.created_at || 0)
                return fechaA - fechaB
            })
        }
    },

    watch: {
        vistaActual(v) {
            localStorage.setItem('pos_vista_preferida', v)
        }
    },

    mounted() {
        this.verificarCaja()
        this.cargarDatos()
        this.obtenerDatos()

        if (this.$route.query.scrollId) {
            const sid = this.$route.query.scrollId;
            const tipo = this.$route.query.tipo;
            if (tipo === 'LLEVAR') this.ultimoIdInteractuado = 'llevar-' + sid;
            else if (tipo === 'DELIVERY' || sid.includes('-') || String(sid).length > 5) this.ultimoIdInteractuado = 'delivery-' + sid;
            else this.ultimoIdInteractuado = 'mesa-' + sid;
        }
        
        this.iniciarPolling();
        window.addEventListener('keydown', this.manejarAtajos);
        document.addEventListener('visibilitychange', this.manejarVisibilidad);
    },

    beforeDestroy() {
        this.detenerPolling();
        if (this._debounceSonidoListo) clearTimeout(this._debounceSonidoListo)
        window.removeEventListener('keydown', this.manejarAtajos)
        document.removeEventListener('visibilitychange', this.manejarVisibilidad)
    },

    methods: {
        iniciarPolling() {
            if (!this.timer) {
                this.timer = setInterval(() => {
                    this.cargarDatos(true);
                    this.verificarCaja();
                }, 5000);
            }
        },
        detenerPolling() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        manejarVisibilidad() {
            if (document.hidden) {
                this.detenerPolling();
            } else {
                this.cargarDatos(true);
                this.verificarCaja();
                this.iniciarPolling();
            }
        },
        ordenarInsumos(insumos) {
            if (!insumos || !Array.isArray(insumos)) return [];
            return [...insumos].sort((a, b) => {
                const catA = (a.categoria || '').toLowerCase() === 'carnes';
                const catB = (b.categoria || '').toLowerCase() === 'carnes';
                if (catA && !catB) return -1;
                if (!catA && catB) return 1;
                return 0;
            });
        },
        servirMesaCompleta(orden) {
            const insumosNuevos = orden.insumos.map(i => {
                // Marcar absolutamente todo como entregado
                return { ...i, estado: 'entregado' };
            });

            this.cargando = true;
            let payload = {
                id: orden.idRef,
                insumos: insumosNuevos,
                total: orden.total,
                atiende: orden.atiende,
                idUsuario: orden.idUsuario,
                cliente: orden.cliente,
                ...this.payloadAcceso()
            }

            const api = (orden.tipoRef === 'LOCAL') ? "editar_mesa.php" : "editar_delivery.php";

            HttpService.registrar(payload, api)
                .then(resultado => {
                    if (resultado) {
                        // Después de marcar los ítems como entregados, actualizamos el estado global de la mesa a 'entregada' (Azul)
                        // Enviamos el payload como POST JSON tal como lo espera el PHP
                        return HttpService.registrar({ tipo: orden.tipoRef, id: orden.idRef }, "entregar_orden_pagada.php")
                    }
                })
                .then(() => {
                    this.$toast({ message: 'Mesa servida y marcada como Entregada', type: 'is-success' })
                    this.cargarDatos(true)
                    this.cargando = false
                })
                .catch(() => { this.cargando = false; this.$toast({ message: 'Error al actualizar', type: 'is-danger' }) })
        },
        abrirMesaRapido() {
            this.$buefy.dialog.prompt({
                title: 'Nueva Orden en Mesa',
                message: '¿En qué número de mesa están?',
                inputAttrs: {
                    type: 'text',
                    placeholder: 'Ej: 5 o 10-B',
                    value: ''
                },
                confirmText: 'Abrir',
                cancelText: 'Cancelar',
                trapFocus: true,
                onConfirm: (value) => {
                    if (!value) return;
                    // Buscar si la mesa ya existe en el sistema
                    const mesaExistente = this.mesas.find(m => String(m.mesa.idMesa).toUpperCase() === String(value).toUpperCase());

                    if (mesaExistente) {
                        this.ocuparMesa(mesaExistente);
                    } else {
                        // Crear objeto de mesa virtual si no existe en el mapa base
                        this.ocuparMesa({
                            mesa: {
                                idMesa: value,
                                estado: 'libre',
                                cliente: '',
                                atiende: this.usuario && this.usuario.nombre ? this.usuario.nombre : ''
                            },
                            insumos: []
                        });
                    }
                }
            })
        },
        manejarAtajos(e) {
            if (e.key === 'F2') {
                e.preventDefault()
                // Si no hay modales abiertos, F2 abre "Nuevo para llevar" rápidamente
                if (!this.mostrarModalCobro && !this.mostrarModalCliente && !this.mostrarModalMesero) {
                    this.nuevoParaLlevar()
                }
            } else if (e.key === 'Enter') {
                // Confirmaciones rápidas en modales con la tecla Enter
                if (this.mostrarModalCliente) {
                    e.preventDefault()
                    this.confirmarClienteOrden()
                } else if (this.mostrarModalCobro) {
                    e.preventDefault()
                    if (this.pagoValido) this.procesarCobro()
                } else if (this.mostrarModalMesero) {
                    e.preventDefault()
                    if (this.meseroAsignadoId) this.confirmarAsignacionMesero()
                }
            }
        },

        puedeAccederOrden(idUsuarioOrden) {
            if (this.rol !== 'mesero') return true;
            return String(idUsuarioOrden || '') === String(this.idUsuarioActual || '');
        },

        queryAcceso() {
            if (this.rol === 'mesero' && this.idUsuarioActual) {
                return `?rol=mesero&idUsuario=${encodeURIComponent(this.idUsuarioActual)}`;
            }
            return '';
        },

        payloadAcceso() {
            return {
                idUsuarioSolicitante: this.idUsuarioActual,
                rolSolicitante: this.rol
            };
        },

        verificarCaja() {
            HttpService.obtener("obtener_estado_caja.php")
                .then(resultado => {
                    this.cajaAbierta = !!resultado;
                })
                .catch(() => {
                    this.cajaAbierta = false;
                })
        },

        cancelarOrden(id) {
            this.$buefy.dialog.prompt({
                title: 'Cancelar mesa ' + id,
                message: 'Ingresa el motivo de cancelación',
                inputAttrs: { placeholder: 'Ej: Cliente se fue, error en pedido...', maxlength: 255 },
                confirmText: 'Sí, cancelar',
                cancelText: 'No',
                type: 'is-danger',
                hasIcon: true,
                trapFocus: true,
                onConfirm: (motivo) => {
                    if (!motivo || !motivo.trim()) {
                        this.$toast({ message: 'Debes ingresar un motivo', type: 'is-warning' })
                        return
                    }
                    this.cargando = true
                    HttpService.eliminar("cancelar_mesa.php", { idMesa: id, motivo: motivo.trim(), ...this.payloadAcceso() })
                        .then(resultado => {
                            if (resultado) {
                                this.$toast({
                                    message: "Orden de la mesa " + id + " cancelada",
                                    type: "is-success"
                                })
                                this.cargarDatos()
                            }
                            this.cargando = false
                        })
                        .catch(() => { this.cargando = false; this.$toast({ message: 'Error al cancelar', type: 'is-danger' }) })
                }
            })
        },

        cancelarDelivery(id) {
            this.$buefy.dialog.prompt({
                title: 'Cancelar Delivery',
                message: 'Ingresa el motivo de cancelación',
                inputAttrs: { placeholder: 'Ej: Cliente no contestó, dirección incorrecta...', maxlength: 255 },
                confirmText: 'Sí, cancelar',
                cancelText: 'No',
                type: 'is-danger',
                hasIcon: true,
                trapFocus: true,
                onConfirm: (motivo) => {
                    if (!motivo || !motivo.trim()) {
                        this.$toast({ message: 'Debes ingresar un motivo', type: 'is-warning' })
                        return
                    }
                    this.cargando = true
                    HttpService.eliminar("cancelar_delivery.php", { id, motivo: motivo.trim(), ...this.payloadAcceso() })
                        .then(resultado => {
                            if (resultado) {
                                this.$toast({
                                    message: "Delivery cancelado",
                                    type: "is-success"
                                })
                                this.cargarDatos()
                            }
                            this.cargando = false
                        })
                        .catch(() => { this.cargando = false; this.$toast({ message: 'Error al cancelar', type: 'is-danger' }) })
                }
            })
        },

        obtenerDatos() {
            HttpService.obtener("obtener_datos_local.php").then((resultado) => {
                this.datos = resultado;
                this.logo = Utiles.generarUrlImagen(this.datos.logo)
            });
        },

        onImpreso(resultado) {
            this.mostrarTicket = resultado
        },

        imprimirComprobante(venta) {
            this.ventaSeleccionada = venta;
            this.insumosSeleccionados = this.ordenarInsumos(venta.insumos);

            if (this.rol === 'mesero') {
                this.mostrarTicketMesero = true;
            } else {
                this.mostrarTicket = true;
            }
        },

        imprimirPrecuenta(elemento, tipo) {
            const esMesa = tipo === 'LOCAL';
            const ahora = new Date();
            const fechaActual = ahora.getFullYear() + '-' + (ahora.getMonth() + 1) + '-' + ahora.getDate() + ' ' + ahora.getHours() + ":" + ahora.getMinutes() + ":" + ahora.getSeconds();

            const datosVenta = {
                atendio: esMesa ? elemento.mesa.atiende : elemento.delivery.atiende,
                cliente: esMesa ? (elemento.mesa.cliente || 'S/N') : (elemento.delivery.cliente || 'S/N'),
                idMesa: esMesa ? elemento.mesa.idMesa : (tipo === 'LLEVAR' ? 'PARA LLEVAR' : 'DELIVERY'),
                total: parseFloat(esMesa ? elemento.mesa.total : elemento.delivery.total) || 0,
                insumos: this.ordenarInsumos(elemento.insumos),
                metodoPago: 'PRE-CUENTA',
                fecha: fechaActual,
                mesa: esMesa ? elemento.mesa.idMesa : null
            };
            this.imprimirComprobante(datosVenta);
        },

        imprimirComprobanteDesdeMesa(mesa) {
            const ahora = new Date();
            const fechaActual = ahora.getFullYear() + '-' + (ahora.getMonth() + 1) + '-' + ahora.getDate() + ' ' + ahora.getHours() + ":" + ahora.getMinutes() + ":" + ahora.getSeconds();

            const payload = {
                idMesa: mesa.mesa.idMesa,
                tipo_orden: 'LOCAL',
                cliente: mesa.mesa.cliente,
                total: mesa.mesa.total,
                metodoPago: 'EFECTIVO', // Valor por defecto para re-impresión
                atiende: mesa.mesa.atiende,
                insumos: this.ordenarInsumos(mesa.insumos),
                fecha: fechaActual,
                mesa: mesa.mesa.idMesa
            };
            this.imprimirComprobante(payload);
        },

        imprimirComprobanteDesdeDelivery(del) {
            const ahora = new Date();
            const fechaActual = ahora.getFullYear() + '-' + (ahora.getMonth() + 1) + '-' + ahora.getDate() + ' ' + ahora.getHours() + ":" + ahora.getMinutes() + ":" + ahora.getSeconds();

            const payload = {
                idDelivery: del.delivery.idDelivery,
                tipo_orden: del.delivery.tipo_orden || 'DELIVERY',
                cliente: del.delivery.cliente,
                total: del.delivery.total,
                metodoPago: 'EFECTIVO',
                atiende: del.delivery.atiende,
                insumos: this.ordenarInsumos(del.insumos),
                fecha: fechaActual
            };
            this.imprimirComprobante(payload);
        },

        imprimirComandaMesa(mesa) {
            const ahora = new Date();
            const fechaActual = ahora.getFullYear() + '-' + (ahora.getMonth() + 1) + '-' + ahora.getDate() + ' ' + ahora.getHours() + ":" + ahora.getMinutes() + ":" + ahora.getSeconds();

            const orden = {
                mesa: mesa.mesa.idMesa,
                tipo: 'LOCAL',
                cliente: mesa.mesa.cliente,
                atendio: mesa.mesa.atiende || 'N/A',
                insumos: this.ordenarInsumos(mesa.insumos),
                metodoPago: 'COMANDA',
                fecha: fechaActual
            }
            if (this.rol === 'mesero') {
                this.comandaSeleccionada = orden;
                this.mostrarComandaMesero = true;
            } else {
                this.imprimirComprobante(orden)
            }
        },

        imprimirComandaDelivery(del) {
            const ahora = new Date();
            const fechaActual = ahora.getFullYear() + '-' + (ahora.getMonth() + 1) + '-' + ahora.getDate() + ' ' + ahora.getHours() + ":" + ahora.getMinutes() + ":" + ahora.getSeconds();

            const orden = {
                idDelivery: del.delivery.idDelivery,
                tipo: del.delivery.tipo_orden || 'DELIVERY',
                cliente: del.delivery.cliente,
                atendio: del.delivery.atiende || 'N/A',
                insumos: this.ordenarInsumos(del.insumos),
                metodoPago: 'COMANDA',
                fecha: fechaActual
            }
            if (this.rol === 'mesero') {
                this.comandaSeleccionada = orden;
                this.mostrarComandaMesero = true;
            } else {
                this.imprimirComprobante(orden)
            }
        },

        marcarInsumosEntregados(mesa) {
            let marcados = this.checkedRowsMap[mesa.mesa.idMesa] || []
            const pendientes = marcados.filter(m => {
                const esCarnes = (m.categoria || '').toLowerCase() === 'carnes'
                const usaParrilla = parseInt(this.datos.usa_pantalla_parrilla) !== 0
                const usaCocina = parseInt(this.datos.usa_pantalla_cocina) !== 0

                if (esCarnes && usaParrilla) return m.acompanamiento_listo === 0
                return usaCocina && m.estado === 'pendiente'
            })

            if (pendientes.length > 0) {
                this.$buefy.dialog.confirm({
                    title: 'Ítems sin preparar',
                    message: `<b>${pendientes.length} ítem(s)</b> todavía están <b>pendientes en cocina</b>.<br>¿Seguro que deseas marcarlos como entregados sin que cocina los haya confirmado?`,
                    confirmText: 'Sí, entregar igual',
                    cancelText: 'Cancelar',
                    type: 'is-warning',
                    hasIcon: true,
                    onConfirm: () => this._ejecutarEntrega(mesa, marcados)
                })
                return
            }

            this._ejecutarEntrega(mesa, marcados)
        },

        _ejecutarEntrega(mesa, marcados) {
            this.cargando = true
            let insumos = mesa.insumos

            // marcados contiene referencias directas a los objetos de mesa.insumos,
            // mutar directamente evita falsos positivos por producto duplicado
            marcados.forEach(marca => {
                marca.estado = "entregado"
            })

            let payload = {
                id: mesa.mesa.idMesa,
                insumos: insumos,
                total: mesa.mesa.total,
                atiende: mesa.mesa.atiende,
                idUsuario: mesa.mesa.idUsuario,
                cliente: mesa.mesa.cliente,
                ...this.payloadAcceso()
            }

            HttpService.registrar(payload, "editar_mesa.php")
                .then(resultado => {
                    if (resultado) {
                        this.$toast({ message: 'Insumos marcados como entregados', type: 'is-success' })
                        this.ultimoIdInteractuado = 'mesa-' + mesa.mesa.idMesa
                        this.cargarDatos()
                        this.cargando = false
                    }
                    this.$set(this.checkedRowsMap, mesa.mesa.idMesa, [])
                })
                .catch(() => { this.cargando = false; this.$toast({ message: 'Error al actualizar', type: 'is-danger' }) })
        },

        cobrar(mesa) {
            if (!this.checkCaja()) return;
            if (!mesa || !mesa.mesa || !this.puedeAccederOrden(mesa.mesa.idUsuario)) {
                this.$toast({ message: 'Sin acceso a esta orden', type: 'is-danger' });
                return;
            }
            // Solo cobrar ítems no pagados previamente
            const insumosACobrar = (mesa.insumos || []).filter(i => !i.pagado)
            if (insumosACobrar.length === 0) {
                this.$toast({ message: 'Todos los ítems ya fueron cobrados', type: 'is-warning' })
                return
            }
            const totalACobrar = insumosACobrar.reduce((s, i) => s + parseFloat(i.precio) * parseFloat(i.cantidad), 0)
            const pendientes = insumosACobrar.filter(i => {
                if ((i.tipo || '').toUpperCase() === 'BEBIDA') return false
                if (i.estado === 'entregado') return false // Si ya se entregó, no importa la cocina
                
                const esCarnes = (i.categoria || '').toLowerCase().trim() === 'carnes'
                const usaParrilla = parseInt(this.datos.usa_pantalla_parrilla || 0) !== 0
                const usaCocina = parseInt(this.datos.usa_pantalla_cocina || 0) !== 0

                const faltaParrilla = esCarnes && usaParrilla && i.estado === 'pendiente'
                const faltaCocina = usaCocina && (esCarnes ? i.acompanamiento_listo === 0 : i.estado === 'pendiente')

                if (faltaParrilla || faltaCocina) {
                    // Guardamos la razón para mostrarla en el mensaje
                    i._razonPendiente = (faltaParrilla && faltaCocina) ? 'Parrilla y Cocina' : (faltaParrilla ? 'Parrilla' : 'Cocina')
                    return true
                }
                return false
            })
            const abrirCobro = () => {
                this.elementoCobro = mesa
                this.tipoCobro = 'LOCAL'
                this.resetPago(totalACobrar)
                this.mostrarModalCobro = true
            }
            if (pendientes.length > 0) {
                this.$buefy.dialog.confirm({
                    title: 'Ítems pendientes en cocina',
                    message: `<b>${pendientes.length} ítem(s)</b> aún no han sido terminados:<br><ul>${pendientes.map(p => `<li>${p.cantidad}x ${p.nombre} <small class="has-text-danger">(Falta: ${p._razonPendiente})</small></li>`).join('')}</ul>¿Deseas cobrar igual?`,
                    confirmText: 'Sí, cobrar igual',
                    cancelText: 'Esperar',
                    type: 'is-warning',
                    hasIcon: true,
                    onConfirm: abrirCobro
                })
                return
            }
            abrirCobro()
        },

        cobrarDelivery(del) {
            if (!this.checkCaja()) return;
            if (!del || !del.delivery || !this.puedeAccederOrden(del.delivery.idUsuario)) {
                this.$toast({ message: 'Sin acceso a este delivery', type: 'is-danger' });
                return;
            }
            // Solo cobrar ítems no pagados previamente
            const insumosACobrar = (del.insumos || []).filter(i => !i.pagado)
            if (insumosACobrar.length === 0) {
                this.$toast({ message: 'Todos los ítems ya fueron cobrados', type: 'is-warning' })
                return
            }
            const totalACobrar = insumosACobrar.reduce((s, i) => s + parseFloat(i.precio) * parseFloat(i.cantidad), 0)
            const pendientes = insumosACobrar.filter(i => {
                if ((i.tipo || '').toUpperCase() === 'BEBIDA') return false
                if (i.estado === 'entregado') return false
                
                const esCarnes = (i.categoria || '').toLowerCase().trim() === 'carnes'
                const usaParrilla = parseInt(this.datos.usa_pantalla_parrilla || 0) !== 0
                const usaCocina = parseInt(this.datos.usa_pantalla_cocina || 0) !== 0

                const faltaParrilla = esCarnes && usaParrilla && i.estado === 'pendiente'
                const faltaCocina = usaCocina && (esCarnes ? i.acompanamiento_listo === 0 : i.estado === 'pendiente')

                if (faltaParrilla || faltaCocina) {
                    i._razonPendiente = (faltaParrilla && faltaCocina) ? 'Parrilla y Cocina' : (faltaParrilla ? 'Parrilla' : 'Cocina')
                    return true
                }
                return false
            })
            const abrirCobro = () => {
                this.elementoCobro = del
                this.tipoCobro = del.delivery.tipo_orden || 'DELIVERY'
                this.resetPago(totalACobrar)
                this.mostrarModalCobro = true
            }
            if (pendientes.length > 0) {
                this.$buefy.dialog.confirm({
                    title: 'Ítems pendientes en cocina',
                    message: `<b>${pendientes.length} ítem(s)</b> aún no han sido terminados:<br><ul>${pendientes.map(p => `<li>${p.cantidad}x ${p.nombre} <small class="has-text-danger">(Falta: ${p._razonPendiente})</small></li>`).join('')}</ul>¿Deseas cobrar igual?`,
                    confirmText: 'Sí, cobrar igual',
                    cancelText: 'Esperar',
                    type: 'is-warning',
                    hasIcon: true,
                    onConfirm: abrirCobro
                })
                return
            }
            abrirCobro()
        },

        checkCaja() {
            if (!this.cajaAbierta) {
                this.$toast({
                    message: '¡No puedes cobrar porque la CAJA ESTÁ CERRADA! (Ábrela en la sección Inicio)',
                    type: 'is-danger',
                    position: 'is-bottom',
                    duration: 5000
                });
                return false;
            }
            return true;
        },

        resetPago(total) {
            this.pago = {
                metodo: 'EFECTIVO',
                montoEfectivo: 0,
                montoTarjeta: 0,
                montoQR: 0,
                montoRecibido: total
            };
        },

        onConfirmarPago({ pago, imprimir }) {
            this.pago = pago;
            this.imprimirAlFinal = imprimir;
            this.mostrarModalCobro = false;
            this.procesarCobro();
        },

        procesarCobro() {
            // Solo cobrar ítems no pagados previamente
            const todosInsumos = this.elementoCobro.insumos || []
            const insumosACobrar = todosInsumos.filter(i => !i.pagado)
            let total = insumosACobrar.reduce((s, i) => s + parseFloat(i.precio) * parseFloat(i.cantidad), 0)

            // Validación extra para evitar cobrar 'para llevar' sin idDelivery
            let idDelivery = (this.tipoCobro === 'DELIVERY' || this.tipoCobro === 'LLEVAR') ? this.elementoCobro.delivery.idDelivery : null;
            if (this.tipoCobro === 'LLEVAR' && (!idDelivery || idDelivery === null || idDelivery === undefined)) {
                this.$toast({ message: 'Error: No se puede cobrar este pedido para llevar porque falta el ID de la orden. Intenta refrescar la página o revisa el pedido.', type: 'is-danger' });
                this.cargando = false;
                return;
            }
            let payload = {
                idMesa: this.tipoCobro === 'LOCAL' ? this.elementoCobro.mesa.idMesa : 0,
                idDelivery: idDelivery,
                tipo_orden: this.tipoCobro,
                cliente: this.tipoCobro === 'LOCAL' ? this.elementoCobro.mesa.cliente : this.elementoCobro.delivery.cliente,
                direccion: this.tipoCobro === 'DELIVERY' ? this.elementoCobro.delivery.direccion : '',
                telefono: this.tipoCobro === 'DELIVERY' ? this.elementoCobro.delivery.telefono : '',
                total: total,
                pagado: this.pago.metodo === 'MIXTO' ? (parseFloat(this.pago.montoEfectivo || 0) + parseFloat(this.pago.montoTarjeta || 0) + parseFloat(this.pago.montoQR || 0)) : parseFloat(this.pago.montoRecibido),
                metodoPago: this.pago.metodo,
                montoEfectivo: 0,
                montoTarjeta: 0,
                montoQR: 0,
                idUsuario: (this.tipoCobro === 'LOCAL' ? this.elementoCobro.mesa.idUsuario : this.elementoCobro.delivery.idUsuario) || this.idUsuarioActual,
                insumos: insumosACobrar,
                atiende: this.tipoCobro === 'LOCAL' ? this.elementoCobro.mesa.atiende : this.elementoCobro.delivery.atiende
            };

            if (this.pago.metodo === 'MIXTO') {
                payload.montoEfectivo = parseFloat(this.pago.montoEfectivo || 0);
                payload.montoTarjeta = parseFloat(this.pago.montoTarjeta || 0);
                payload.montoQR = parseFloat(this.pago.montoQR || 0);
            } else if (this.pago.metodo === 'EFECTIVO') payload.montoEfectivo = total;
            else if (this.pago.metodo === 'TARJETA') payload.montoTarjeta = total;
            else if (this.pago.metodo === 'QR') payload.montoQR = total;

            let cambio = parseFloat(payload.pagado - total);
            this.cargando = true;
            this.mostrarModalCobro = false;

            HttpService.registrar(payload, "registrar_venta.php")
                .then(registrado => {
                    if (registrado) {
                        if (payload.tipo_orden === 'LLEVAR' || payload.tipo_orden === 'DELIVERY') {
                            this.$buefy.dialog.confirm({
                                title: 'Venta registrada',
                                message: 'Pago procesado: <b>$' + payload.pagado.toFixed(2) + '</b><br>Cambio: <b>$' + cambio.toFixed(2) + '</b><br><br>¿Deseas imprimir la <b>Comanda</b> para cocina?',
                                confirmText: 'Imprimir Comanda',
                                cancelText: 'Cerrar',
                                type: 'is-info',
                                onConfirm: () => {
                                    this.imprimirComandaDelivery(this.elementoCobro);
                                }
                            })
                        } else {
                            this.$buefy.dialog.confirm({
                                title: 'Venta registrada',
                                message: 'Pago procesado: <b>$' + payload.pagado.toFixed(2) + '</b><br>Cambio: <b>$' + cambio.toFixed(2) + '</b><br><br>¿Deseas imprimir la <b>Comanda</b> para cocina?',
                                confirmText: 'Imprimir Comanda',
                                cancelText: 'Cerrar',
                                type: 'is-info',
                                onConfirm: () => {
                                    this.imprimirComandaMesa(this.elementoCobro);
                                }
                            })
                        }
                        if (this.imprimirAlFinal) {
                            this.imprimirComprobante(payload)
                        }
                        this.cargarDatos()
                        this.verificarCaja()
                    }
                    this.cargando = false
                })
                .catch(() => { this.cargando = false; this.$toast({ message: 'Error al registrar venta', type: 'is-danger' }) })
        },

        cargarDatos(silencioso = false) {
            if (!silencioso) this.cargando = true

            // Capturar snapshot de ítems listos antes de recargar
            const prevListosMesas = {}
                ; (Array.isArray(this.mesas) ? this.mesas : []).forEach(m => {
                    if (!m || !m.mesa) return
                    prevListosMesas[m.mesa.idMesa] = (m.insumos || []).filter(i => i.estado === 'listo').map(i => i.nombre)
                })

            HttpService.obtener("obtener_mesas.php" + this.queryAcceso())
                .then(mesasRaw => {
                    const mesasLista = Array.isArray(mesasRaw) ? mesasRaw : []
                    if (silencioso) {
                        mesasLista.forEach(m => {
                            if (!m || !m.mesa) return
                            if (this.rol === 'mesero' && !this.puedeAccederOrden(m.mesa.idUsuario)) return
                            const prev = prevListosMesas[m.mesa.idMesa] || []
                            const nuevosListos = (m.insumos || []).filter(i => i.estado === 'listo' && !prev.includes(i.nombre))
                            if (nuevosListos.length > 0) {
                                this.programarSonidoListoUnaVez()
                                nuevosListos.forEach(item => {
                                    this.$buefy.notification.open({
                                        message: `🍽️ <b>Mesa #${m.mesa.idMesa}</b> — <b>${item.nombre}</b> está listo para entregar`,
                                        type: 'is-warning',
                                        duration: 8000,
                                        position: 'is-bottom-right',
                                        hasIcon: true,
                                        icon: 'bell-ring'
                                    })
                                })
                            }
                        })
                    }
                    const mesasProcesadas = mesasLista.slice()
                    mesasProcesadas.sort((a, b) => {
                        if (!a.mesa || !b.mesa) return 0
                        const idA = String(a.mesa.idMesa)
                        const idB = String(b.mesa.idMesa)
                        const baseA = parseInt(idA, 10)
                        const baseB = parseInt(idB, 10)
                        if (baseA !== baseB) return baseA - baseB
                        return idA.localeCompare(idB)
                    })
                    this.mesas = mesasProcesadas
                    Object.keys(this.checkedRowsMap).forEach(idMesa => {
                        const mesaData = mesasLista.find(m => m && m.mesa && String(m.mesa.idMesa) === String(idMesa))
                        if (!mesaData) return
                        const oldChecked = this.checkedRowsMap[idMesa] || []
                        const checkedItemIds = oldChecked.map(r => r.itemId).filter(Boolean)
                        if (checkedItemIds.length > 0) {
                            const nuevos = mesaData.insumos.filter(i => i.itemId && checkedItemIds.includes(i.itemId) && i.estado !== 'entregado')
                            this.$set(this.checkedRowsMap, idMesa, nuevos)
                        }
                    })
                })
                .catch(e => {
                    console.error("Error cargando mesas", e);
                    this.$toast({ message: 'Error al cargar mesas.', type: 'is-danger' })
                })
                .finally(() => {
                    if (!silencioso) this.cargando = false
                    this.$nextTick(() => {
                        this.realizarScrollSiHaceFalta();
                    });
                });

            const prevListosDeliveries = {}
                ; (Array.isArray(this.deliveries) ? this.deliveries : []).forEach(d => {
                    if (!d || !d.delivery) return
                    prevListosDeliveries[d.delivery.idDelivery] = (d.insumos || []).filter(i => i.estado === 'listo').map(i => i.nombre)
                })

            HttpService.obtener("obtener_deliveries.php" + this.queryAcceso())
                .then(deliveriesRaw => {
                    const deliveriesLista = Array.isArray(deliveriesRaw) ? deliveriesRaw : []
                    if (silencioso) {
                        deliveriesLista.forEach(d => {
                            if (!d || !d.delivery) return
                            if (this.rol === 'mesero' && !this.puedeAccederOrden(d.delivery.idUsuario)) return
                            const prev = prevListosDeliveries[d.delivery.idDelivery] || []
                            const nuevosListos = (d.insumos || []).filter(i => i.estado === 'listo' && !prev.includes(i.nombre))
                            if (nuevosListos.length > 0) {
                                this.programarSonidoListoUnaVez()
                                nuevosListos.forEach(item => {
                                    this.$buefy.notification.open({
                                        message: `🚚 <b>${d.delivery.cliente || 'Delivery #' + d.delivery.idDelivery}</b> — <b>${item.nombre}</b> está listo para entregar`,
                                        type: 'is-info',
                                        duration: 8000,
                                        position: 'is-bottom-right',
                                        hasIcon: true,
                                        icon: 'bell-ring'
                                    })
                                })
                            }
                        })
                    }
                    this.deliveries = deliveriesLista
                })
                .catch(e => {
                    console.error("Error cargando deliveries", e);
                    this.$toast({ message: 'Error al cargar deliveries.', type: 'is-danger' })
                })
                .finally(() => {
                    this.$nextTick(() => {
                        this.realizarScrollSiHaceFalta();
                    });
                });
        },

        realizarScrollSiHaceFalta() {
            if (this.ultimoIdInteractuado) {
                const el = document.getElementById(this.ultimoIdInteractuado);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('resaltar-cambio');
                    setTimeout(() => el.classList.remove('resaltar-cambio'), 2000);
                    this.ultimoIdInteractuado = null; // Limpiar para que no lo haga en cada refresco de 3s
                }
            }
        },

        ocuparMesa(mesa) {
            if (mesa && mesa.mesa && (mesa.mesa.estado === 'ocupada' || mesa.mesa.estado === 'pagada') && !this.puedeAccederOrden(mesa.mesa.idUsuario)) {
                this.$toast({ message: 'Sin acceso a esta orden', type: 'is-danger' });
                return;
            }
            if (this.rol === 'admin') {
                const params = {
                    id: mesa.mesa.idMesa,
                    insumosEnLista: mesa.insumos || [],
                    cliente: mesa.mesa.cliente || (mesa.mesa.reserva ? mesa.mesa.reserva.nombre_cliente : "")
                };

                // Si la mesa ya tiene un mesero asignado (ej: al Agregar insumos), no pedirlo de nuevo
                if (mesa.mesa.idUsuario) {
                    params.meseroAsignado = { id: mesa.mesa.idUsuario, nombre: mesa.mesa.atiende };
                    this.$router.push({ name: "Ordenar", params });
                    return;
                }

                this._pendingOcuparMesa = 'mesa';
                this._pendingOcuparParams = params;
                this.meseroAsignadoId = null;
                if (this.meseros.length === 0) {
                    HttpService.obtener('obtener_meseros.php').then(lista => {
                        this.meseros = lista || [];
                        if (this.meseros.length === 0) {
                            this.$router.push({ name: "Ordenar", params });
                        } else {
                            this.mostrarModalMesero = true;
                        }
                    });
                } else {
                    this.mostrarModalMesero = true;
                }
                return;
            }
            this.$router.push({
                name: "Ordenar",
                params: {
                    id: mesa.mesa.idMesa,
                    insumosEnLista: mesa.insumos || [],
                    cliente: mesa.mesa.cliente || (mesa.mesa.reserva ? mesa.mesa.reserva.nombre_cliente : "")
                },
            })
        },

        compartirMesa(mesa) {
            const idActual = String(mesa.mesa.idMesa);
            // Extraer número base (ej: de "1" -> "1", de "1-B" -> "1")
            const baseId = idActual.split('-')[0];

            // Buscar todas las mesas que tengan el mismo baseId y ver qué letras tienen
            const subfijosUsados = this.mesas
                .map(m => String(m.mesa.idMesa))
                .filter(id => id.startsWith(baseId + '-'))
                .map(id => id.split('-')[1]);

            // Determinar la siguiente letra disponible (B, C, D...)
            const siguiente = this._siguienteLetra(subfijosUsados);
            const nuevoId = `${baseId}-${siguiente}`;

            this.$buefy.toast.open({
                message: `Abriendo cuenta compartida: Mesa ${nuevoId}`,
                type: 'is-info'
            });

            // Reutilizamos la lógica de ocuparMesa pero para el nuevo ID
            const mesaSimulada = {
                mesa: {
                    idMesa: nuevoId,
                    estado: 'libre',
                    cliente: '',
                    atiende: '',
                    idUsuario: ''
                },
                insumos: []
            };
            this.ocuparMesa(mesaSimulada);
        },

        _siguienteLetra(usadas) {
            const abecedario = "BCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
            for (let letra of abecedario) {
                if (!usadas.includes(letra)) return letra;
            }
            return "Z2";
        },

        onConfirmarMesero(idMesero) {
            this.meseroAsignadoId = idMesero;
            this.mostrarModalMesero = false;
            this.confirmarAsignacionMesero();
        },

        confirmarAsignacionMesero() {
            const mesero = this.meseros.find(m => m.id === this.meseroAsignadoId);
            const params = Object.assign({}, this._pendingOcuparParams, {
                meseroAsignado: mesero ? { id: mesero.id, nombre: mesero.nombre } : null
            });
            this.$router.push({ name: "Ordenar", params });
        },

        nuevoDelivery() {
            this.pendingTipoOrden = 'DELIVERY';
            this.mostrarModalCliente = true;
        },

        nuevoParaLlevar() {
            this.pendingTipoOrden = 'LLEVAR';
            this.mostrarModalCliente = true;
        },

        onConfirmarCliente(datos) {
            this.mostrarModalCliente = false;
            this.confirmarClienteOrden(datos);
        },

        confirmarClienteOrden(datos) {
            const params = {
                id: 'DELIVERY',
                insumosEnLista: [],
                cliente: datos.nombre,
                telefono: datos.telefono,
                direccion: datos.direccion,
                tipo_orden: this.pendingTipoOrden
            };
            if (this.rol === 'admin') {
                this._pendingOcuparMesa = this.pendingTipoOrden === 'DELIVERY' ? 'delivery' : 'llevar';
                this._pendingOcuparParams = params;
                this.meseroAsignadoId = null;
                if (this.meseros.length === 0) {
                    HttpService.obtener('obtener_meseros.php').then(lista => {
                        this.meseros = lista || [];
                        if (this.meseros.length === 0) { this.$router.push({ name: 'Ordenar', params }); }
                        else { this.mostrarModalMesero = true; }
                    });
                } else { this.mostrarModalMesero = true; }
                return;
            }
            this.$router.push({ name: 'Ordenar', params });
        },

        editarParaLlevar(del) {
            if (!del || !del.delivery || !this.puedeAccederOrden(del.delivery.idUsuario)) {
                this.$toast({ message: 'Sin acceso a este pedido', type: 'is-danger' });
                return;
            }
            this.$router.push({
                name: "Ordenar",
                params: {
                    id: "DELIVERY",
                    idDelivery: del.delivery.idDelivery,
                    insumosEnLista: del.insumos,
                    cliente: del.delivery.cliente,
                    telefono: del.delivery.telefono,
                    tipo_orden: "LLEVAR"
                },
            })
        },

        editarDelivery(del) {
            if (!del || !del.delivery || !this.puedeAccederOrden(del.delivery.idUsuario)) {
                this.$toast({ message: 'Sin acceso a este delivery', type: 'is-danger' });
                return;
            }
            this.$router.push({
                name: "Ordenar",
                params: {
                    id: "DELIVERY",
                    idDelivery: del.delivery.idDelivery,
                    insumosEnLista: del.insumos,
                    cliente: del.delivery.cliente,
                    direccion: del.delivery.direccion,
                    telefono: del.delivery.telefono,
                    tipo_orden: "DELIVERY"
                },
            })
        },

        getAudioCampanaListo() {
            if (!this.audioCampanaListo) {
                this.audioCampanaListo = new Audio('/static/campana.ogg')
            }
            return this.audioCampanaListo
        },
        desbloquearAudioSiHaceFalta() {
            if (this.audioListo) return
            const a = this.getAudioCampanaListo()
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
        programarSonidoListoUnaVez() {
            if (this._debounceSonidoListo) clearTimeout(this._debounceSonidoListo)
            this._debounceSonidoListo = setTimeout(() => {
                this._debounceSonidoListo = null
                this.reproducirSonidoListo()
            }, 120)
        },
        reproducirSonidoListo() {
            try {
                const audio = this.getAudioCampanaListo()
                audio.currentTime = 0
                audio.volume = 0.6
                audio.play().catch(e => console.warn('Sonido listo (mesero) bloqueado:', e))
            } catch (e) {
                console.warn('No se pudo reproducir alerta de listo:', e)
            }
        },

        tieneListo(insumos) {
            return (insumos || []).some(i => i.estado === 'listo' &&
                (i.tipo || '').toUpperCase() !== 'BEBIDA' &&
                (i.categoria || '').toUpperCase() !== 'BEBIDA' &&
                (i.categoria || '').toUpperCase() !== 'BEBIDAS')
        },

        tienePendiente(insumos) {
            return (insumos || []).some(i => {
                const esCarnes = (i.categoria || '').toLowerCase() === 'carnes'
                const usaParrilla = parseInt(this.datos.usa_pantalla_parrilla) !== 0
                const usaCocina = parseInt(this.datos.usa_pantalla_cocina) !== 0

                if (esCarnes && usaParrilla) return i.acompanamiento_listo === 0
                return usaCocina && i.estado === 'pendiente'
            })
        },

        async entregarOrdenPagada(tipo, id) {
            const ok = await HttpService.registrar({ tipo, id }, 'entregar_orden_pagada.php')
            if (ok) {
                this.$toast({ message: 'Orden marcada como entregada', type: 'is-success' })
                this.ultimoIdInteractuado = (tipo === 'LOCAL' ? 'mesa-' : (tipo === 'DELIVERY' ? 'delivery-' : 'llevar-')) + id;
                this.cargarDatos()
            } else {
                this.$toast({ message: 'Error al entregar', type: 'is-danger' })
            }
        },

        async liberarMesa(tipo, id) {
            this.$buefy.dialog.confirm({
                title: 'Liberar Mesa',
                message: '¿Estás seguro de que deseas liberar esta mesa? La orden desaparecerá del sistema.',
                confirmText: 'Sí, liberar',
                cancelText: 'Cancelar',
                type: 'is-dark',
                onConfirm: async () => {
                    const ok = await HttpService.registrar({ tipo, id }, 'liberar_mesa.php')
                    if (ok) {
                        this.$toast({ message: 'Mesa liberada', type: 'is-success' })
                        this.cargarDatos()
                    }
                }
            })
        },


        solicitarCambioMesa(elemento, tipo) {
            const refActual = tipo === 'LOCAL' ? elemento.mesa.idMesa : elemento.delivery.idDelivery;
            const cliente = tipo === 'LOCAL' ? elemento.mesa.cliente : elemento.delivery.cliente;

            this.$buefy.dialog.prompt({
                title: 'Cambiar Mesa',
                message: `Mover la orden de <b>${cliente || 'Sin nombre'}</b> a una nueva mesa.`,
                inputAttrs: {
                    placeholder: 'Número de mesa (ej: 5)',
                    type: 'text'
                },
                trapFocus: true,
                confirmText: 'Cambiar',
                onConfirm: (nuevaMesa) => {
                    if (!nuevaMesa || nuevaMesa.trim() === "") return;

                    this.cargando = true;
                    HttpService.registrar({
                        tipoActual: tipo,
                        referenciaActual: refActual,
                        nuevaMesa: nuevaMesa.trim()
                    }, 'cambiar_mesa.php')
                        .then(res => {
                            if (res.ok) {
                                this.$toast({ message: 'Mesa cambiada con éxito', type: 'is-success' });
                                this.ultimoIdInteractuado = 'mesa-' + nuevaMesa.trim();
                                this.cargarDatos();
                            } else {
                                const msg = res.error === 'MESA_OCUPADA' ? 'La mesa destino ya está ocupada' : 'Error al cambiar mesa';
                                this.$toast({ message: msg, type: 'is-danger' });
                            }
                        })
                        .catch(() => {
                            this.$toast({ message: 'Error de conexión', type: 'is-danger' });
                        })
                        .finally(() => {
                            this.cargando = false;
                        });
                }
            });
        }
    }
}
</script>

<style scoped>
@keyframes parpadeo-listo {

    0%,
    100% {
        box-shadow: 0 0 0 0 rgba(255, 183, 0, 0);
    }

    50% {
        box-shadow: 0 0 0 8px rgba(255, 183, 0, 0.55);
    }
}

.orden-lista-pulso {
    animation: parpadeo-listo 1.4s ease-in-out infinite;
    border: 2px solid #f5a623 !important;
}

.box-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.box-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

@keyframes pulso-resaltar {
    0% {
        box-shadow: 0 0 0 0 rgba(72, 199, 142, 0.7);
        transform: scale(1);
    }

    50% {
        box-shadow: 0 0 0 15px rgba(72, 199, 142, 0);
        transform: scale(1.02);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(72, 199, 142, 0);
        transform: scale(1);
    }
}

.resaltar-cambio {
    animation: pulso-resaltar 1.5s ease-in-out;
    border: 2px solid #48c78e !important;
    z-index: 10;
}
</style>
