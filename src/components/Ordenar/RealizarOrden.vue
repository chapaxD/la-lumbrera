<template>
    <section @click="desbloquearAudioSiHaceFalta">
        <div class="columns is-mobile is-multiline mb-4">
            <div class="column is-12-mobile is-6-tablet">
                <p class="title is-1 has-text-weight-bold">
                    <b-icon icon="order-bool-ascending-variant" size="is-large" type="is-primary"></b-icon>
                    Realizar orden 
                </p>
                <p v-if="!audioListo" class="is-size-7 has-text-info mb-0">
                    Tocá la pantalla una vez para activar el sonido cuando cocina marque un plato listo.
                </p>
            </div>
            <div class="column is-12-mobile is-6-tablet has-text-right-tablet">
                <div class="buttons is-right">
                    <b-button type="is-warning" icon-left="walk" size="is-medium" class="is-responsive" @click="nuevoParaLlevar">Para llevar</b-button>
                    <b-button type="is-info" icon-left="truck-delivery" size="is-medium" class="is-responsive" @click="nuevoDelivery">Nuevo Delivery</b-button>
                </div>
            </div>
        </div>

        <div class="columns is-multiline">
            <!-- Skeleton Screens para la primera carga -->
            <template v-if="cargando">
                <div class="column is-4-desktop is-6-tablet" v-for="i in 6" :key="'skel-mesa-'+i">
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
            <div class="column is-4-desktop is-6-tablet" 
            v-for="mesa in mesas" 
            :key="'mesa-'+mesa.mesa.idMesa">
                
                    <div class="box" :class="{'has-background-warning-light': mesa.mesa.reserva, 'orden-lista-pulso': tieneListo(mesa.insumos)}">
                    <div class="is-flex is-justify-content-space-between is-align-items-center is-flex-wrap-wrap mb-2">
                        <p class="title is-2 has-text-grey mb-0">Mesa #{{ mesa.mesa.idMesa }}</p>
                        <span class="title is-1 has-text-weight-bold" v-if="mesa.mesa.total && puedeAccederOrden(mesa.mesa.idUsuario)">
                            {{ Utiles.formatearDinero(mesa.mesa.total) }}
                        </span>
                    </div>
                    <p v-if="mesa.mesa.atiende">
                        <strong>Atiende</strong>: {{ mesa.mesa.atiende }}
                    </p>
                    <p v-if="mesa.mesa.cliente && puedeAccederOrden(mesa.mesa.idUsuario)">
                        <strong>Cliente</strong>: {{ mesa.mesa.cliente }}
                    </p>
                    <div v-if="mesa.mesa.reserva" 
                         class="notification py-2 px-3 mt-2 mb-0"
                         :class="mesa.mesa.reserva.estado === 'SENTADA' ? 'is-success is-light' : 'is-warning is-light'">
                        <b-icon :icon="mesa.mesa.reserva.estado === 'SENTADA' ? 'account-check' : 'calendar-clock'" size="is-small"></b-icon>
                        <span v-if="mesa.mesa.reserva.idMesa"> 
                            {{ mesa.mesa.reserva.estado === 'SENTADA' ? 'Cliente sentado:' : 'Reservada hoy' }} 
                            <b>{{ mesa.mesa.reserva.hora }}</b> - {{ mesa.mesa.reserva.nombre_cliente }}
                        </span>
                        <span v-else> <b>EVENTO TOTAL: {{ mesa.mesa.reserva.hora }}</b> </span>
                    </div>
                    <b-collapse 
                        class="card mt-2" 
                        animation="slide" 
                        aria-id="contentIdForA11y3"
                        v-if="mesa.mesa.estado === 'ocupada' || mesa.mesa.estado === 'pagada'">
                        <template #trigger="props">
                            <div
                                class="card-header"
                                role="button"
                                aria-controls="contentIdForA11y3"
                                :aria-expanded="props.open">
                                <p class="card-header-title">
                                    Insumos en la orden
                                </p>
                                <a class="card-header-icon">
                                    <b-icon
                                        :icon="props.open ? 'menu-down' : 'menu-up'">
                                    </b-icon>
                                </a>
                            </div>
                        </template>

                        <div class="card-content">
                            <div class="content">
                                <b-table
                                :data="mesa.insumos"
                                :checked-rows="checkedRowsMap[mesa.mesa.idMesa] || []"
                                @check="(rows) => $set(checkedRowsMap, mesa.mesa.idMesa, rows)"
                                :is-row-checkable="(row) => row.estado !== 'entregado'"
                                :checkable="puedeAccederOrden(mesa.mesa.idUsuario)"
                                mobile-cards
                                narrow
                                custom-row-key="itemId"
                                :checkbox-position="checkboxPosition"
                                :checkbox-type="checkboxType">

                                    <b-table-column field="nombre" label="Nombre" v-slot="props">
                                        {{ props.row.nombre }}
                                        <p v-if="props.row.resumenCombo" class="is-size-6 has-text-dark has-text-weight-bold mt-1" style="white-space: pre-line;">
                                            <b-icon icon="food-variant" size="is-small"></b-icon>
                                            {{ Utiles.formatearResumenCombo(props.row.resumenCombo) }}
                                        </p>
                                    </b-table-column>
                                    
                                    <b-table-column field="cantidad" label="Cantidad" v-slot="props">
                                        {{ props.row.cantidad }} X {{ Utiles.formatearDinero(props.row.precio) }}
                                    </b-table-column>                                        
                                    
                                    <b-table-column field="subtotal" label="Subtotal" v-slot="props">
                                        {{ Utiles.formatearDinero(props.row.cantidad * props.row.precio) }}
                                    </b-table-column>                                                                
                                    <b-table-column field="estado" label="" v-slot="props">
                                        <b-icon icon="alert" type="is-danger" v-if="props.row.estado ==='pendiente'"></b-icon>
                                        <b-icon icon="bell-ring" type="is-warning" title="¡Listo! Retirar de cocina" v-if="props.row.estado ==='listo'"></b-icon>
                                        <b-icon icon="check" type="is-success" v-if="props.row.estado ==='entregado'"></b-icon>
                                    </b-table-column>                                                                
                                </b-table>
                            </div>
                        </div>
                    </b-collapse>
                    <br>
                    <div class="has-text-centered">
                        <!-- Mesa libre sin reserva activa -->
                        <b-button type="is-primary" icon-left="check" @click="ocuparMesa(mesa)" v-if="mesa.mesa.estado === 'libre' && !mesa.mesa.reserva">Ocupar</b-button>
                        <!-- Mesa libre pero con reserva activa: bloqueada -->
                        <div v-if="mesa.mesa.estado === 'libre' && mesa.mesa.reserva" class="notification is-danger is-light py-2 px-3">
                          <b-icon icon="lock" size="is-small"></b-icon>
                          <b>Mesa {{ mesa.mesa.reserva.estado === 'PENDIENTE' ? 'en espera de confirmación' : 'confirmada' }}</b> — solo puede abrirse desde <b>Gestión de Reservas</b>
                        </div>
                        <div class="field is-grouped is-grouped-centered is-grouped-multiline" v-if="mesa.mesa.estado === 'ocupada' && puedeAccederOrden(mesa.mesa.idUsuario)">
                            <p class="control" v-if="rol !== 'mesero'">
                                <b-button type="is-success" icon-left="cash" @click="cobrar(mesa)">Cobrar</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-info" icon-left="printer" is-light @click="imprimirPrecuenta(mesa, 'LOCAL')" title="Imprimir detalle para el cliente">Detalle</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-info" icon-left="plus" @click="ocuparMesa(mesa)">Agregar</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-primary" icon-left="account-multiple-plus" @click="compartirMesa(mesa)" title="Compartir mesa con otro grupo">Compartir</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-warning" icon-left="check" v-if="(checkedRowsMap[mesa.mesa.idMesa] || []).length > 0" @click="marcarInsumosEntregados(mesa)">Entregado</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-danger" icon-left="close"  @click="cancelarOrden(mesa.mesa.idMesa)">Cancelar</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-info" is-light icon-left="printer" @click="imprimirComandaMesa(mesa)" title="Imprimir comanda para cocina">Comanda</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-link" is-light icon-left="swap-horizontal" @click="solicitarCambioMesa(mesa, 'LOCAL')" title="Cambiar de mesa">Cambiar</b-button>
                            </p>
                        </div>
                        <p class="has-text-danger" v-if="mesa.mesa.estado === 'ocupada' && !puedeAccederOrden(mesa.mesa.idUsuario)">
                            Sin acceso a esta orden
                        </p>
                        <div class="notification is-success is-light py-2 px-3 mb-0" v-if="mesa.mesa.estado === 'pagada'">
                            <b-icon icon="cash-check" type="is-success"></b-icon>
                            <span v-if="tienePendiente(mesa.insumos)"><strong>Cobrado</strong> — el pedido está en preparación en cocina</span>
                            <span v-else><strong>Cobrado y listo</strong> — entregar al cliente</span>
                            <div class="mt-2" v-if="puedeAccederOrden(mesa.mesa.idUsuario)">
                                <b-button v-if="!tienePendiente(mesa.insumos)" type="is-success" icon-left="hand-okay" class="mr-2" @click="entregarOrdenPagada('LOCAL', mesa.mesa.idMesa)">Entregar al cliente</b-button>
                                <b-button type="is-info" is-light icon-left="printer" class="mr-2" @click="imprimirComandaMesa(mesa)" title="Reimprimir comanda">Comanda</b-button>
                                <b-button type="is-info" icon-left="plus" @click="ocuparMesa(mesa)">Agregar más</b-button>
                            </div>
                        </div>
                    </div>            
                </div>
            </div>

            <!-- Deliveries Activos -->
            <div class="column is-4-desktop is-6-tablet" 
            v-for="del in deliveriesNormales" 
            :key="'delivery-'+del.delivery.idDelivery">
                
                <div class="box has-background-info-light" :class="{'orden-lista-pulso': tieneListo(del.insumos)}">
                    <p class="has-text-info has-text-weight-bold mb-1">
                        <b-icon icon="truck-delivery" size="is-small"></b-icon> DELIVERY
                    </p>
                    <div class="is-flex is-justify-content-space-between is-align-items-center is-flex-wrap-wrap mb-2">
                        <p class="title is-3 has-text-grey mb-0">{{ del.delivery.cliente || ('Delivery ' + del.delivery.idDelivery) }}</p>
                        <span class="title is-1 has-text-weight-bold" v-if="del.delivery.total && puedeAccederOrden(del.delivery.idUsuario)">
                            ${{ del.delivery.total }}
                        </span>
                    </div>
                    <p v-if="del.delivery.direccion && puedeAccederOrden(del.delivery.idUsuario)"><strong>Dirección</strong>: {{ del.delivery.direccion }}</p>
                    <p v-if="del.delivery.telefono && puedeAccederOrden(del.delivery.idUsuario)"><strong>Teléfono</strong>: {{ del.delivery.telefono }}</p>
                    <p><strong>Atiende</strong>: {{ del.delivery.atiende }}</p>
                    
                    <b-collapse 
                        class="card mt-2" 
                        animation="slide" 
                        aria-id="contentIdForA11y3"
                        v-if="del.insumos.length > 0 && puedeAccederOrden(del.delivery.idUsuario)">
                        <template #trigger="props">
                            <div
                                class="card-header"
                                role="button"
                                aria-controls="contentIdForA11y3"
                                :aria-expanded="props.open">
                                <p class="card-header-title">Insumos en el delivery</p>
                                <a class="card-header-icon">
                                    <b-icon :icon="props.open ? 'menu-down' : 'menu-up'"></b-icon>
                                </a>
                            </div>
                        </template>

                        <div class="card-content">
                            <div class="content">
                                <b-table :data="del.insumos" mobile-cards narrow>
                                    <b-table-column field="nombre" label="Nombre" v-slot="props">
                                        {{ props.row.nombre }}
                                        <p v-if="props.row.resumenCombo" class="is-size-6 has-text-dark has-text-weight-bold mt-1" style="white-space: pre-line;">
                                            <b-icon icon="food-variant" size="is-small"></b-icon>
                                            {{ Utiles.formatearResumenCombo(props.row.resumenCombo) }}
                                        </p>
                                    </b-table-column>
                                    <b-table-column field="cantidad" label="Cantidad" v-slot="props">
                                        {{ props.row.cantidad }} X {{ Utiles.formatearDinero(props.row.precio) }}
                                    </b-table-column>                                                                        
                                    <b-table-column field="subtotal" label="Subtotal" v-slot="props">
                                        {{ Utiles.formatearDinero(props.row.cantidad * props.row.precio) }}
                                    </b-table-column>
                                    <b-table-column field="estado" label="" v-slot="props">
                                        <b-icon icon="alert" type="is-danger" v-if="props.row.estado ==='pendiente'"></b-icon>
                                        <b-icon icon="bell-ring" type="is-warning" title="¡Listo! Retirar de cocina" v-if="props.row.estado ==='listo'"></b-icon>
                                        <b-icon icon="check" type="is-success" v-if="props.row.estado ==='entregado'"></b-icon>
                                    </b-table-column>
                                </b-table>
                            </div>
                        </div>
                    </b-collapse>
                    <br>
                    <div class="has-text-centered">
                        <template v-if="del.delivery.estado_orden !== 'pagada'">
                            <div class="field is-grouped is-grouped-centered is-grouped-multiline" v-if="puedeAccederOrden(del.delivery.idUsuario)">
                                <p class="control" v-if="rol !== 'mesero'">
                                    <b-button type="is-success" icon-left="cash" @click="cobrarDelivery(del)">Cobrar</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-info" icon-left="printer" is-light @click="imprimirPrecuenta(del, del.delivery.tipo_orden || 'DELIVERY')" title="Imprimir detalle para el cliente">Detalle</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-info" icon-left="plus" @click="editarDelivery(del)">Agregar</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-danger" icon-left="close" @click="cancelarDelivery(del.delivery.idDelivery)">Cancelar</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-info" is-light icon-left="printer" @click="imprimirComandaDelivery(del)" title="Imprimir comanda para cocina">Comanda</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-link" is-light icon-left="swap-horizontal" @click="solicitarCambioMesa(del, 'DELIVERY')" title="Asignar a una mesa">Cambiar</b-button>
                                </p>
                            </div>
                            <p class="has-text-danger" v-else>
                                Sin acceso a este delivery
                            </p>
                        </template>
                        <div class="notification is-success is-light py-2 px-3 mb-0" v-if="del.delivery.estado_orden === 'pagada'">
                            <b-icon icon="cash-check" type="is-success"></b-icon>
                            <span v-if="tienePendiente(del.insumos)"><strong>Cobrado</strong> — el pedido está en preparación en cocina</span>
                            <span v-else><strong>Cobrado y listo</strong> — entregar al cliente</span>
                            <div class="mt-2" v-if="puedeAccederOrden(del.delivery.idUsuario)">
                                <b-button v-if="!tienePendiente(del.insumos)" type="is-success" icon-left="hand-okay" class="mr-2" @click="entregarOrdenPagada('DELIVERY', del.delivery.idDelivery)">Entregar al cliente</b-button>
                                <b-button type="is-info" is-light icon-left="printer" class="mr-2" @click="imprimirComandaDelivery(del)" title="Reimprimir comanda">Comanda</b-button>
                                <b-button type="is-info" icon-left="plus" @click="editarDelivery(del)">Agregar más</b-button>
                            </div>
                        </div>
                    </div>            
                </div>
            </div>

            <!-- Para llevar activos -->
            <div class="column is-4-desktop is-6-tablet"
            v-for="del in paraLlevar"
            :key="'llevar-'+del.delivery.idDelivery">
                <div class="box has-background-warning-light" :class="{'orden-lista-pulso': tieneListo(del.insumos)}">
                    <p class="has-text-warning-dark has-text-weight-bold mb-1">
                        <b-icon icon="walk" size="is-small"></b-icon> PARA LLEVAR
                    </p>
                    <div class="is-flex is-justify-content-space-between is-align-items-center is-flex-wrap-wrap mb-2">
                        <p class="title is-3 has-text-grey mb-0">{{ del.delivery.cliente || ('Pedido ' + del.delivery.idDelivery) }}</p>
                        <span class="title is-1 has-text-weight-bold" v-if="del.delivery.total && puedeAccederOrden(del.delivery.idUsuario)">
                            ${{ del.delivery.total }}
                        </span>
                    </div>
                    <p v-if="del.delivery.telefono && puedeAccederOrden(del.delivery.idUsuario)"><strong>Teléfono</strong>: {{ del.delivery.telefono }}</p>
                    <p><strong>Atiende</strong>: {{ del.delivery.atiende }}</p>

                    <b-collapse
                        class="card mt-2"
                        animation="slide"
                        v-if="del.insumos.length > 0 && puedeAccederOrden(del.delivery.idUsuario)">
                        <template #trigger="props">
                            <div class="card-header" role="button" :aria-expanded="props.open">
                                <p class="card-header-title">Insumos del pedido</p>
                                <a class="card-header-icon">
                                    <b-icon :icon="props.open ? 'menu-down' : 'menu-up'"></b-icon>
                                </a>
                            </div>
                        </template>
                        <div class="card-content">
                            <div class="content">
                                <b-table :data="del.insumos" mobile-cards narrow>
                                    <b-table-column field="nombre" label="Nombre" v-slot="props">
                                        {{ props.row.nombre }}
                                        <p v-if="props.row.resumenCombo" class="is-size-6 has-text-dark has-text-weight-bold mt-1" style="white-space: pre-line;">
                                            <b-icon icon="food-variant" size="is-small"></b-icon>
                                            {{ Utiles.formatearResumenCombo(props.row.resumenCombo) }}
                                        </p>
                                    </b-table-column>
                                    <b-table-column field="cantidad" label="Cantidad" v-slot="props">
                                        {{ props.row.cantidad }} X {{ Utiles.formatearDinero(props.row.precio) }}
                                    </b-table-column>
                                    <b-table-column field="subtotal" label="Subtotal" v-slot="props">
                                        {{ Utiles.formatearDinero(props.row.cantidad * props.row.precio) }}
                                    </b-table-column>
                                    <b-table-column field="estado" label="" v-slot="props">
                                        <b-icon icon="alert" type="is-danger" v-if="props.row.estado ==='pendiente'"></b-icon>
                                        <b-icon icon="bell-ring" type="is-warning" title="¡Listo! Retirar de cocina" v-if="props.row.estado ==='listo'"></b-icon>
                                        <b-icon icon="check" type="is-success" v-if="props.row.estado ==='entregado'"></b-icon>
                                    </b-table-column>
                                </b-table>
                            </div>
                        </div>
                    </b-collapse>
                    <br>
                    <div class="has-text-centered">
                        <template v-if="del.delivery.estado_orden !== 'pagada'">
                            <div class="field is-grouped is-grouped-centered is-grouped-multiline" v-if="puedeAccederOrden(del.delivery.idUsuario)">
                                <p class="control" v-if="rol !== 'mesero'">
                                    <b-button type="is-success" icon-left="cash" @click="cobrarDelivery(del)">Cobrar</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-info" icon-left="printer" is-light @click="imprimirPrecuenta(del, 'LLEVAR')" title="Imprimir detalle para el cliente">Detalle</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-info" icon-left="plus" @click="editarParaLlevar(del)">Agregar</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-danger" icon-left="close" @click="cancelarDelivery(del.delivery.idDelivery)">Cancelar</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-info" is-light icon-left="printer" @click="imprimirComandaDelivery(del)" title="Imprimir comanda para cocina">Comanda</b-button>
                                </p>
                                <p class="control">
                                    <b-button type="is-link" is-light icon-left="swap-horizontal" @click="solicitarCambioMesa(del, 'DELIVERY')" title="Asignar a una mesa">Cambiar</b-button>
                                </p>
                            </div>
                            <p class="has-text-danger" v-else>
                                Sin acceso a esta orden
                            </p>
                        </template>
                        <div class="notification is-success is-light py-2 px-3 mb-0" v-if="del.delivery.estado_orden === 'pagada'">
                            <b-icon icon="cash-check" type="is-success"></b-icon>
                            <span v-if="tienePendiente(del.insumos)"><strong>Cobrado</strong> — el pedido está en preparación en cocina</span>
                            <span v-else><strong>Cobrado y listo</strong> — entregar al cliente</span>
                            <div class="mt-2" v-if="puedeAccederOrden(del.delivery.idUsuario)">
                                <b-button v-if="!tienePendiente(del.insumos)" type="is-success" icon-left="hand-okay" class="mr-2" @click="entregarOrdenPagada('LLEVAR', del.delivery.idDelivery)">Entregar al cliente</b-button>
                                <b-button type="is-info" is-light icon-left="printer" class="mr-2" @click="imprimirComandaDelivery(del)" title="Reimprimir comanda">Comanda</b-button>
                                <b-button type="is-info" icon-left="plus" @click="editarParaLlevar(del)">Agregar más</b-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </template>
        </div>
        <ticket @impreso="onImpreso" :venta="this.ventaSeleccionada" :insumos="insumosSeleccionados" :datosLocal="datos" :logo="logo" v-if="mostrarTicket"></ticket>

        <b-modal :active.sync="mostrarModalCobro" has-modal-card trap-focus>
            <div class="modal-card" style="width: auto" v-if="elementoCobro">
                <header class="modal-card-head">
                    <p class="modal-card-title">
                        Cobrar {{ tipoCobro === 'LOCAL' ? 'Mesa #' + elementoCobro.mesa.idMesa : (tipoCobro === 'LLEVAR' ? 'Para llevar: ' : 'Delivery: ') + elementoCobro.delivery.cliente }}
                    </p>
                </header>
                <section class="modal-card-body">
                    <p class="is-size-4 has-text-centered mb-4">
                        Total a pagar: <strong>{{ Utiles.formatearDinero(totalACobrar) }}</strong>
                    </p>
                    
                    <b-field label="Método de pago principal">
                        <b-select v-model="pago.metodo" expanded>
                            <option value="EFECTIVO">Efectivo 💵</option>
                            <option value="TARJETA">Tarjeta / Transferencia 💳</option>
                            <option value="QR">Código QR 📱</option>
                            <option value="MIXTO">Mixto (Dividir pago) 🧾</option>
                        </b-select>
                    </b-field>

                    <div v-if="pago.metodo === 'MIXTO'" class="box mt-4">
                        <p class="has-text-weight-bold mb-2">Desglose de montos:</p>
                        <b-field label="Efectivo (Bs.)">
                            <b-input type="number" step="0.01" min="0" v-model="pago.montoEfectivo"></b-input>
                        </b-field>
                        <b-field label="Tarjeta/Transf. (Bs.)">
                            <b-input type="number" step="0.01" min="0" v-model="pago.montoTarjeta"></b-input>
                        </b-field>
                        <b-field label="Código QR (Bs.)">
                            <b-input type="number" step="0.01" min="0" v-model="pago.montoQR"></b-input>
                        </b-field>
                        <p class="is-size-6 mt-3" :class="{'has-text-danger': totalDesglose < totalACobrar, 'has-text-success': totalDesglose >= totalACobrar}">
                            Suma total devengada: {{ Utiles.formatearDinero(totalDesglose) }} / {{ Utiles.formatearDinero(totalACobrar) }}
                        </p>
                    </div>

                    <div v-else class="mt-4">
                        <b-field label="Monto Recibido en Físico (Bs.)">
                            <b-input type="number" step="0.01" :min="totalACobrar" v-model="pago.montoRecibido"></b-input>
                        </b-field>
                        <p v-if="cambioIndividual >= 0" class="has-text-success is-size-5 mt-2">
                            Cambio a devolver: <b>{{ Utiles.formatearDinero(cambioIndividual) }}</b>
                        </p>
                    </div>

                    <div class="field mt-4">
                        <b-checkbox v-model="imprimirAlFinal">
                            Imprimir ticket de venta
                        </b-checkbox>
                    </div>

                </section>
                <footer class="modal-card-foot">
                    <b-button label="Cancelar" type="is-dark" @click="mostrarModalCobro = false" />
                    <b-button label="Confirmar Pago" type="is-success" icon-left="cash" @click="procesarCobro" :disabled="!pagoValido" />
                </footer>
            </div>
        </b-modal>

        <!-- Modal cliente para llevar / delivery -->
        <b-modal :active.sync="mostrarModalCliente" has-modal-card trap-focus :can-cancel="['escape', 'outside']">
            <div class="modal-card" style="min-width: 420px">
                <header class="modal-card-head">
                    <p class="modal-card-title">
                        <b-icon :icon="pendingTipoOrden === 'DELIVERY' ? 'truck-delivery' : 'walk'" size="is-small"></b-icon>
                        &nbsp;{{ pendingTipoOrden === 'DELIVERY' ? 'Nuevo Delivery' : 'Para Llevar' }} — ¿Para quién?
                    </p>
                </header>
                <section class="modal-card-body">
                    <b-field label="Cliente">
                        <b-autocomplete
                            v-model="formCliente.nombre"
                            :data="sugerenciasClientes"
                            placeholder="Escribe nombre o mostrador..."
                            icon="account-search"
                            field="nombre_completo"
                            :loading="buscandoCliente"
                            @typing="buscarClienteOrden"
                            @select="seleccionarClienteOrden"
                            clearable
                        >
                            <template slot="empty">Sin resultados — se usará el nombre escrito</template>
                        </b-autocomplete>
                    </b-field>
                    <b-field label="Teléfono">
                        <b-input v-model="formCliente.telefono" placeholder="Opcional" icon="phone"></b-input>
                    </b-field>
                    <b-field label="Dirección" v-if="pendingTipoOrden === 'DELIVERY'">
                        <b-input v-model="formCliente.direccion" placeholder="Dirección de entrega" icon="map-marker"></b-input>
                    </b-field>
                </section>
                <footer class="modal-card-foot">
                    <b-button label="Cancelar" type="is-dark" @click="mostrarModalCliente = false" />
                    <b-button label="Continuar" type="is-primary" icon-left="arrow-right" @click="confirmarClienteOrden" />
                </footer>
            </div>
        </b-modal>

        <!-- Modal asignación de mesero (solo admin) -->
        <b-modal :active.sync="mostrarModalMesero" has-modal-card trap-focus :can-cancel="['escape', 'outside']">
            <div class="modal-card" style="width: 380px">
                <header class="modal-card-head">
                    <p class="modal-card-title">Asignar mesero</p>
                </header>
                <section class="modal-card-body">
                    <b-field label="Selecciona el mesero que atenderá esta orden">
                        <b-select v-model="meseroAsignadoId" expanded placeholder="-- Seleccionar mesero --">
                            <option v-for="m in meseros" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                        </b-select>
                    </b-field>
                </section>
                <footer class="modal-card-foot">
                    <b-button label="Cancelar" type="is-dark" @click="mostrarModalMesero = false" />
                    <b-button label="Continuar" type="is-primary" icon-left="arrow-right" :disabled="!meseroAsignadoId" @click="confirmarAsignacionMesero" />
                </footer>
            </div>
        </b-modal>
    </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'
import Ticket from '../Ventas/Ticket.vue'
import Utiles from '../../Servicios/Utiles'

export default {
    name: "RealizarOrden",
    components: { Ticket },

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
            formCliente: { nombre: '', telefono: '', direccion: '' },
            sugerenciasClientes: [],
            buscandoCliente: false,
            _timerCliente: null,
            audioCampanaListo: null,
            audioListo: false,
            _debounceSonidoListo: null,
        }
    },

    computed: {
        totalACobrar() {
            if (!this.elementoCobro) return 0;
            const insumos = this.elementoCobro.insumos || [];
            const noPagados = insumos.filter(i => !i.pagado);
            if (noPagados.length > 0) {
                return noPagados.reduce((s, i) => s + parseFloat(i.precio) * parseFloat(i.cantidad), 0);
            }
            // Si no hay flag pagado (orden normal sin cobros previos), usar total original
            return parseFloat(this.tipoCobro === 'LOCAL' ? this.elementoCobro.mesa.total : this.elementoCobro.delivery.total) || 0;
        },
        totalDesglose() {
            return parseFloat(this.pago.montoEfectivo || 0) + parseFloat(this.pago.montoTarjeta || 0) + parseFloat(this.pago.montoQR || 0);
        },
        cambioIndividual() {
            if(!this.elementoCobro) return 0;
            return parseFloat(this.pago.montoRecibido || 0) - this.totalACobrar;
        },
        pagoValido() {
            if(!this.elementoCobro) return false;
            let total = this.totalACobrar;
            if(this.pago.metodo === 'MIXTO') return this.totalDesglose >= total;
            return parseFloat(this.pago.montoRecibido || 0) >= total;
        },
        deliveriesNormales() {
            return this.deliveries.filter(d => (d.delivery.tipo_orden || 'DELIVERY') === 'DELIVERY')
        },
        paraLlevar() {
            return this.deliveries.filter(d => d.delivery.tipo_orden === 'LLEVAR')
        }
    },

    mounted(){
        this.verificarCaja()
        this.cargarDatos()
        this.obtenerDatos()
        // Polling cada 3 segundos para notificar rápido al mesero cuando cocina marca listo
        this.timer = setInterval(() => {
            this.cargarDatos(true);
            this.verificarCaja();
        }, 3000);
        window.addEventListener('keydown', this.manejarAtajos)
    },

    beforeDestroy() {
        if(this.timer) clearInterval(this.timer);
        if (this._debounceSonidoListo) clearTimeout(this._debounceSonidoListo)
        window.removeEventListener('keydown', this.manejarAtajos)
    },

    methods:{
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

        cancelarOrden(id){
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
                        if(resultado){
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

        cancelarDelivery(id){
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
                        if(resultado){
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

        onImpreso(resultado){
            this.mostrarTicket = resultado
        },

        imprimirComprobante(venta){
            let hoy = new Date();
            let fechaVenta = hoy.getFullYear()+'-'+(hoy.getMonth()+1)+'-'+hoy.getDate() + ' ' + hoy.getHours() + ":" + hoy.getMinutes() + ":" + hoy.getSeconds();
            
            this.ventaSeleccionada = {
                atendio: venta.atiende || venta.atendio,
                cliente: venta.cliente,
                fecha: fechaVenta,
                pagado: venta.pagado,
                total: venta.total,
                metodoPago: venta.metodoPago || 'EFECTIVO',
                montoEfectivo: venta.montoEfectivo || 0,
                montoTarjeta: venta.montoTarjeta || 0,
                montoQR: venta.montoQR || 0,
                mesa: (venta.idMesa && parseInt(venta.idMesa) > 0) ? String(venta.idMesa) : null,
            }

            this.insumosSeleccionados = venta.insumos
            this.mostrarTicket = true
        },

        imprimirPrecuenta(elemento, tipo) {
            const esMesa = tipo === 'LOCAL';
            const datosVenta = {
                atendio: esMesa ? elemento.mesa.atiende : elemento.delivery.atiende,
                cliente: esMesa ? (elemento.mesa.cliente || 'S/N') : (elemento.delivery.cliente || 'S/N'),
                idMesa: esMesa ? elemento.mesa.idMesa : (tipo === 'LLEVAR' ? 'PARA LLEVAR' : 'DELIVERY'),
                total: parseFloat(esMesa ? elemento.mesa.total : elemento.delivery.total) || 0,
                insumos: elemento.insumos,
                metodoPago: 'PRE-CUENTA'
            };
            this.imprimirComprobante(datosVenta);
        },

        imprimirComandaMesa(mesa) {
            const orden = {
                id: mesa.mesa.idMesa,
                tipo: 'LOCAL',
                cliente: mesa.mesa.cliente,
                insumos: mesa.insumos
            }
            this.Utiles.imprimirComanda(orden)
        },

        imprimirComandaDelivery(del) {
            const orden = {
                id: del.delivery.idDelivery,
                tipo: del.delivery.tipo_orden || 'DELIVERY',
                cliente: del.delivery.cliente,
                insumos: del.insumos
            }
            this.Utiles.imprimirComanda(orden)
        },

        marcarInsumosEntregados(mesa){
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
                if(resultado){
                    this.$toast({ message: 'Insumos marcados como entregados', type: 'is-success' })
                    this.cargarDatos()
                    this.cargando = false
                }
                this.$set(this.checkedRowsMap, mesa.mesa.idMesa, [])
            })
            .catch(() => { this.cargando = false; this.$toast({ message: 'Error al actualizar', type: 'is-danger' }) })
        },

        cobrar(mesa){
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
                const esCarnes = (i.categoria || '').toLowerCase() === 'carnes'
                const usaParrilla = parseInt(this.datos.usa_pantalla_parrilla) !== 0
                const usaCocina = parseInt(this.datos.usa_pantalla_cocina) !== 0

                // Si la parrilla está activa, las carnes se bloquean si no tienen acompañamiento listo
                if (esCarnes && usaParrilla) return i.acompanamiento_listo === 0
                
                // En cualquier otro caso (item normal, o carne con parrilla apagada), solo bloquea si cocina está activa
                return usaCocina && i.estado === 'pendiente'
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
                    message: `<b>${pendientes.length} ítem(s)</b> de esta mesa aún <b>no fueron marcados listos por cocina</b>:<br><ul>${pendientes.map(p => `<li>${p.cantidad}x ${p.nombre}</li>`).join('')}</ul>¿Deseas cobrar igual?`,
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

        cobrarDelivery(del){
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
                const esCarnes = (i.categoria || '').toLowerCase() === 'carnes'
                const usaParrilla = parseInt(this.datos.usa_pantalla_parrilla) !== 0
                const usaCocina = parseInt(this.datos.usa_pantalla_cocina) !== 0

                if (esCarnes && usaParrilla) return i.acompanamiento_listo === 0
                return usaCocina && i.estado === 'pendiente'
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
                    message: `<b>${pendientes.length} ítem(s)</b> de esta orden aún <b>no fueron marcados listos por cocina</b>:<br><ul>${pendientes.map(p => `<li>${p.cantidad}x ${p.nombre}</li>`).join('')}</ul>¿Deseas cobrar igual?`,
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
                pagado: this.pago.metodo === 'MIXTO' ? this.totalDesglose : parseFloat(this.pago.montoRecibido),
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
                if(registrado){
                    if (payload.tipo_orden === 'LLEVAR' || payload.tipo_orden === 'DELIVERY') {
                        this.$buefy.dialog.confirm({
                            title: 'Venta registrada',
                            message: 'Pago procesado: <b>$' + payload.pagado.toFixed(2) + '</b><br>Cambio: <b>$' + cambio.toFixed(2) + '</b><br><br>¿Deseas imprimir la <b>Comanda</b> para cocina?',
                            confirmText: 'Imprimir Comanda',
                            cancelText: 'Cerrar',
                            type: 'is-info',
                            onConfirm: () => {
                                this.Utiles.imprimirComanda({
                                    id: payload.idDelivery,
                                    tipo: payload.tipo_orden,
                                    cliente: payload.cliente,
                                    insumos: payload.insumos
                                });
                            }
                        })
                    } else {
                        this.$buefy.dialog.alert({
                            title: 'Venta registrada',
                            message: 'Pago procesado: <b>$' + payload.pagado.toFixed(2) + '</b><br>Cambio: <b>$' + cambio.toFixed(2) + '</b>',
                            confirmText: 'OK'
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

        cargarDatos(silencioso = false){
            if(!silencioso) this.cargando = true
            
            // Capturar snapshot de ítems listos antes de recargar
            const prevListosMesas = {}
            ;(Array.isArray(this.mesas) ? this.mesas : []).forEach(m => {
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
                if(!silencioso) this.cargando = false
            });

            const prevListosDeliveries = {}
            ;(Array.isArray(this.deliveries) ? this.deliveries : []).forEach(d => {
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
            });
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

        confirmarAsignacionMesero() {
            this.mostrarModalMesero = false;
            const mesero = this.meseros.find(m => m.id === this.meseroAsignadoId);
            const params = Object.assign({}, this._pendingOcuparParams, {
                meseroAsignado: mesero ? { id: mesero.id, nombre: mesero.nombre } : null
            });
            this.$router.push({ name: "Ordenar", params });
        },

        nuevoDelivery() {
            this.pendingTipoOrden = 'DELIVERY';
            this.formCliente = { nombre: '', telefono: '', direccion: '' };
            this.sugerenciasClientes = [];
            this.mostrarModalCliente = true;
        },

        nuevoParaLlevar() {
            this.pendingTipoOrden = 'LLEVAR';
            this.formCliente = { nombre: '', telefono: '', direccion: '' };
            this.sugerenciasClientes = [];
            this.mostrarModalCliente = true;
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
            this.formCliente.nombre    = cliente.nombre + (cliente.apellido ? ' ' + cliente.apellido : '');
            if (cliente.telefono)  this.formCliente.telefono  = cliente.telefono;
            if (cliente.direccion) this.formCliente.direccion = cliente.direccion;
        },

        confirmarClienteOrden() {
            this.mostrarModalCliente = false;
            const params = {
                id: 'DELIVERY',
                insumosEnLista: [],
                cliente:   this.formCliente.nombre,
                telefono:  this.formCliente.telefono,
                direccion: this.formCliente.direccion,
                tipo_orden: this.pendingTipoOrden
            };
            if (this.rol === 'admin') {
                this._pendingOcuparMesa   = this.pendingTipoOrden === 'DELIVERY' ? 'delivery' : 'llevar';
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
            return (insumos || []).some(i => i.estado === 'listo' && (i.tipo || '').toUpperCase() !== 'BEBIDA')
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
                this.$toast({ message: 'Orden entregada al cliente', type: 'is-success' })
                this.cargarDatos()
            } else {
                this.$toast({ message: 'Error al entregar', type: 'is-danger' })
            }
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
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 183, 0, 0); }
    50%       { box-shadow: 0 0 0 8px rgba(255, 183, 0, 0.55); }
}
.orden-lista-pulso {
    animation: parpadeo-listo 1.4s ease-in-out infinite;
    border: 2px solid #f5a623 !important;
}
</style>
