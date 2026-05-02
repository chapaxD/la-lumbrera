<template>
    <b-modal :active="active" @close="$emit('close')" has-modal-card trap-focus>
        <div class="modal-card" style="width: auto" v-if="elemento">
            <header class="modal-card-head">
                <p class="modal-card-title">
                    Cobrar {{ tipoCobro === 'LOCAL' ? 'Mesa #' + elemento.mesa.idMesa : (tipoCobro === 'LLEVAR' ? 'Para llevar: ' : 'Delivery: ') + elemento.delivery.cliente }}
                </p>
            </header>
            <section class="modal-card-body">
                <p class="is-size-4 has-text-centered mb-4">
                    Total a pagar: <strong>{{ Utiles.formatearDinero(totalACobrar) }}</strong>
                </p>

                <b-field label="Método de pago principal">
                    <b-select v-model="pagoInterno.metodo" expanded>
                        <option value="EFECTIVO">Efectivo 💵</option>
                        <option value="TARJETA">Tarjeta / Transferencia 💳</option>
                        <option value="QR">Código QR 📱</option>
                        <option value="MIXTO">Mixto (Dividir pago) 🧾</option>
                    </b-select>
                </b-field>

                <div v-if="pagoInterno.metodo === 'MIXTO'" class="box mt-4">
                    <p class="has-text-weight-bold mb-2">Desglose de montos:</p>
                    <b-field label="Efectivo (Bs.)">
                        <b-input type="number" step="0.01" min="0" v-model="pagoInterno.montoEfectivo"></b-input>
                    </b-field>
                    <b-field label="Tarjeta/Transf. (Bs.)">
                        <b-input type="number" step="0.01" min="0" v-model="pagoInterno.montoTarjeta"></b-input>
                    </b-field>
                    <b-field label="Código QR (Bs.)">
                        <b-input type="number" step="0.01" min="0" v-model="pagoInterno.montoQR"></b-input>
                    </b-field>
                    <p class="is-size-6 mt-3"
                        :class="{ 'has-text-danger': totalDesglose < totalACobrar, 'has-text-success': totalDesglose >= totalACobrar }">
                        Suma total devengada: {{ Utiles.formatearDinero(totalDesglose) }} / {{
                            Utiles.formatearDinero(totalACobrar) }}
                    </p>
                </div>

                <div v-else class="mt-4">
                    <b-field label="Monto Recibido en Físico (Bs.)">
                        <b-input type="number" step="0.01" :min="totalACobrar"
                            v-model="pagoInterno.montoRecibido"></b-input>
                    </b-field>
                    <p v-if="cambioIndividual >= 0" class="has-text-success is-size-5 mt-2">
                        Cambio a devolver: <b>{{ Utiles.formatearDinero(cambioIndividual) }}</b>
                    </p>
                </div>

                <div class="field mt-4">
                    <b-checkbox v-model="imprimirAlFinalInterno">
                        Imprimir ticket de venta
                    </b-checkbox>
                </div>

            </section>
            <footer class="modal-card-foot">
                <b-button label="Cancelar" type="is-dark" @click="$emit('close')" />
                <b-button label="Confirmar Pago" type="is-success" icon-left="cash" @click="confirmar"
                    :disabled="!pagoValido" />
            </footer>
        </div>
    </b-modal>
</template>

<script>
import Utiles from '../../Servicios/Utiles'

export default {
    name: 'ModalCobro',
    props: {
        active: Boolean,
        elemento: Object,
        tipoCobro: String
    },
    data() {
        return {
            Utiles,
            pagoInterno: {
                metodo: 'EFECTIVO',
                montoEfectivo: 0,
                montoTarjeta: 0,
                montoQR: 0,
                montoRecibido: 0
            },
            imprimirAlFinalInterno: true
        }
    },
    watch: {
        active(newVal) {
            if (newVal) {
                // Resetear al abrir
                this.pagoInterno = {
                    metodo: 'EFECTIVO',
                    montoEfectivo: 0,
                    montoTarjeta: 0,
                    montoQR: 0,
                    montoRecibido: this.totalACobrar
                };
            }
        }
    },
    computed: {
        totalACobrar() {
            if (!this.elemento) return 0;
            const insumos = this.elemento.insumos || [];
            const noPagados = insumos.filter(i => !i.pagado);
            if (noPagados.length > 0) {
                return noPagados.reduce((s, i) => s + parseFloat(i.precio) * parseFloat(i.cantidad), 0);
            }
            return parseFloat(this.tipoCobro === 'LOCAL' ? this.elemento.mesa.total : this.elemento.delivery.total) || 0;
        },
        totalDesglose() {
            return (parseFloat(this.pagoInterno.montoEfectivo) || 0) + 
                   (parseFloat(this.pagoInterno.montoTarjeta) || 0) + 
                   (parseFloat(this.pagoInterno.montoQR) || 0);
        },
        cambioIndividual() {
            return (parseFloat(this.pagoInterno.montoRecibido) || 0) - this.totalACobrar;
        },
        pagoValido() {
            if (this.pagoInterno.metodo === 'MIXTO') {
                return this.totalDesglose >= this.totalACobrar;
            }
            return parseFloat(this.pagoInterno.montoRecibido) >= this.totalACobrar;
        }
    },
    methods: {
        confirmar() {
            this.$emit('confirmar', {
                pago: this.pagoInterno,
                imprimir: this.imprimirAlFinalInterno
            });
        }
    }
}
</script>
