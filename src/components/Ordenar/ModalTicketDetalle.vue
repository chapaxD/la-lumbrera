<template>
    <b-modal :active="active" @close="$emit('close')" has-modal-card trap-focus :can-cancel="['escape', 'outside']">
        <div class="modal-card" style="width: auto">
            <header class="modal-card-head" style="background: #f5f5f5; border-bottom: 1px solid #ddd;">
                <p class="modal-card-title is-size-6 has-text-weight-bold">
                    {{ modo === 'COMANDA' ? 'Vista Previa de Comanda' : 'Vista Previa de Ticket' }}
                </p>
                <button type="button" class="delete" @click="$emit('close')" />
            </header>
            <section class="modal-card-body" style="background: #eee; padding: 20px;">
                <!-- MODO TICKET / PRECUENTA -->
                <div v-if="modo === 'TICKET' && venta" class="box"
                    style="background: #fff; font-family: monospace; width: 300px; margin: 0 auto; color: #000; line-height: 1.2;">
                    <div class="has-text-centered">
                        <h3 class="has-text-weight-bold is-size-5">{{ nombreLocal }}</h3>
                        <div v-if="venta.metodoPago === 'PRE-CUENTA'" class="is-size-7"
                            style="border: 1px dashed #000; margin: 5px 0;">--- DETALLE DE PEDIDO ---</div>
                        <div v-if="venta.mesa" class="is-size-4 has-text-weight-bold">MESA #{{ venta.mesa }}</div>
                    </div>
                    <hr style="border-top: 1px dashed #000; margin: 8px 0;">
                    <div class="is-size-7">
                        <div>Fecha: {{ venta.fecha }}</div>
                        <div>Atiende: {{ venta.atendio }}</div>
                        <div>Cliente: {{ venta.cliente || 'MOSTRADOR' }}</div>
                    </div>
                    <hr style="border-top: 1px dashed #000; margin: 8px 0;">
                    <table style="width: 100%; font-size: 14px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #000;">
                                <th class="has-text-left">Prod.</th>
                                <th class="has-text-centered">Cant</th>
                                <th class="has-text-right">Sub.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(ins, idx) in insumos" :key="idx">
                                <td class="has-text-weight-bold" style="text-transform: uppercase;">
                                    {{ ins.nombre }}
                                    <div v-if="ins.caracteristicas" class="is-size-7 ml-1"
                                        style="border-left: 1px solid #000; padding-left: 4px; font-weight: normal; font-style: italic;">
                                        {{ ins.caracteristicas }}
                                    </div>
                                    <div v-if="ins.resumenCombo" class="is-size-7 ml-1"
                                        style="border-left: 1px solid #000; padding-left: 4px; white-space: pre-line; font-weight: bold;">
                                        {{ Utiles.formatearResumenCombo(ins.resumenCombo) }}
                                    </div>
                                </td>
                                <td class="has-text-centered">{{ ins.cantidad }}</td>
                                <td class="has-text-right">{{ Math.round(ins.cantidad * ins.precio) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <hr style="border-top: 1px dashed #000; margin: 8px 0;">
                    <div class="is-size-4 has-text-weight-bold is-flex is-justify-content-space-between">
                        <span>TOTAL</span>
                        <span>Bs. {{ Math.round(venta.total) }}</span>
                    </div>
                    <div v-if="venta.metodoPago !== 'PRE-CUENTA'" class="is-size-7 mt-2">
                        MÉTODO: {{ venta.metodoPago }}
                    </div>
                    <hr style="border-top: 1px dashed #000; margin: 8px 0;">
                </div>

                <!-- MODO COMANDA -->
                <div v-if="modo === 'COMANDA' && comanda" class="box"
                    style="background: #fff; font-family: monospace; width: 300px; margin: 0 auto; color: #000;">
                    <h2 class="has-text-centered has-text-weight-bold">--- COMANDA ---</h2>
                    <hr style="border-top: 1px dashed #000; margin: 5px 0;">
                    <div class="has-text-centered is-size-4 has-text-weight-bold">
                        <span v-if="comanda.mesa">MESA #{{ comanda.mesa }}</span>
                        <span v-else-if="comanda.tipo === 'LLEVAR' || comanda.tipoOrden === 'LLEVAR'">LLEVAR #{{ comanda.id || comanda.idDelivery || comanda.idVenta }}</span>
                        <span v-else-if="comanda.tipo === 'DELIVERY' || comanda.tipoOrden === 'DELIVERY'">DELIVERY #{{ comanda.id || comanda.idDelivery || comanda.idVenta }}</span>
                    </div>
                    <div v-if="comanda.cliente" class="has-text-centered is-size-7">Cliente: {{ comanda.cliente }}</div>
                    <div v-if="comanda.fecha" class="has-text-centered is-size-7 mb-1">Hora: {{ comanda.fecha | formatFecha }}</div>
                    <hr style="border-top: 1px dashed #000; margin: 5px 0;">
                    <div v-for="(ins, idx) in comanda.insumos" :key="idx" class="mb-2">
                        <div class="is-size-5"><span class="has-text-weight-bold">{{ ins.cantidad }}x</span> <span class="has-text-weight-bold" style="text-transform: uppercase;">{{ ins.nombre }}</span>
                        </div>
                        <div v-if="ins.caracteristicas" class="is-size-6 ml-4"
                            style="border-left: 2px solid #000; padding-left: 5px; text-transform: uppercase;">{{ ins.caracteristicas }}</div>
                        <div v-if="ins.resumenCombo" class="is-size-6 ml-4 has-text-weight-bold"
                            style="border-left: 2px solid #000; padding-left: 5px; white-space: pre-line; text-transform: uppercase;">
                            {{ Utiles.formatearResumenCombo(ins.resumenCombo) }}
                        </div>
                    </div>
                    <hr style="border-top: 1px dashed #000; margin: 5px 0;">
                </div>
            </section>
            <footer class="modal-card-foot" style="justify-content: center;">
                <b-button label="Cerrar" @click="$emit('close')" type="is-dark" />
            </footer>
        </div>
    </b-modal>
</template>

<script>
import Utiles from '../../Servicios/Utiles'

export default {
    name: 'ModalTicketDetalle',
    props: {
        active: Boolean,
        modo: { type: String, default: 'TICKET' }, // 'TICKET' or 'COMANDA'
        nombreLocal: String,
        venta: Object,
        insumos: Array,
        comanda: Object
    },
    data() {
        return {
            Utiles
        }
    }
}
</script>
