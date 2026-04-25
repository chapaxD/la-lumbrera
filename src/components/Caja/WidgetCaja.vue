<template>
    <div class="card">
        <header class="card-header" style="background:var(--color-primario); box-shadow:none; border-radius:4px 4px 0 0">
            <p class="card-header-title" style="color:#fff">
                <b-icon icon="cash-register" size="is-small" style="color:#fff; margin-right:.4rem"></b-icon>
                Estado de Caja Diaria
            </p>
        </header>
        <div class="card-content">

        <b-loading :is-full-page="false" v-model="cargando" :can-cancel="false"></b-loading>

        <!-- CAJA CERRADA -->
        <div v-if="!cajaAbierta" class="has-text-centered p-4 rounded" style="background-color: #fcebeb;">
            <p class="is-size-5 has-text-danger mb-4"><strong>La caja está CERRADA</strong></p>
            <b-field label="Monto físico inicial (Bs.)" class="mx-auto" style="max-width: 300px;">
                <b-input type="number" v-model="montoApertura" step="1" min="0" required></b-input>
            </b-field>
            <b-button type="is-primary is-large" icon-left="lock-open" @click="abrirCaja">Iniciar Turno</b-button>
        </div>

        <!-- CAJA ABIERTA -->
        <div v-else class="has-text-centered p-4 rounded" style="background-color: #eefcf3;">
            <p class="is-size-5 has-text-success mb-2"><strong>La caja está ABIERTA</strong></p>
            <p class="mb-1">Iniciada el: <strong>{{ caja.fechaApertura | formatFecha }}</strong></p>
            <p class="mb-4">Fondo inicial: <strong>Bs. {{ Math.round(caja.montoApertura) }}</strong></p>
            
            <div class="columns is-mobile is-multiline mb-4">
                <div class="column is-6">
                    <p class="is-size-7">Ventas Diarias (Total)</p>
                    <p class="is-size-4 has-text-weight-bold has-text-primary">Bs. {{ Math.round(caja.ventasAcumuladas || 0) }}</p>
                </div>
                <div class="column is-6">
                    <p class="is-size-7">Ventas en Efectivo</p>
                    <p class="is-size-4 has-text-weight-bold has-text-success">Bs. {{ Math.round(caja.ventasEfectivo || 0) }}</p>
                </div>
                <div class="column is-6">
                    <p class="is-size-7">Tarjeta / Transf.</p>
                    <p class="is-size-4 has-text-weight-bold has-text-info">Bs. {{ Math.round(caja.ventasTarjeta || 0) }}</p>
                </div>
                <div class="column is-6">
                    <p class="is-size-7">Pago con QR</p>
                    <p class="is-size-4 has-text-weight-bold has-text-link">Bs. {{ Math.round(caja.ventasQR || 0) }}</p>
                </div>
                <div class="column is-12">
                    <p class="is-size-7">Gastos Totales (Retiros del día)</p>
                    <p class="is-size-4 has-text-weight-bold has-text-danger">-Bs. {{ Math.round(caja.gastosAcumulados || 0) }}</p>
                </div>
            </div>
            
            <div class="buttons is-centered">
                <b-button type="is-warning" icon-left="minus" @click="registrarGasto">Gasto / Retiro</b-button>
                <b-button type="is-danger" icon-left="lock" @click="cerrarCaja">Hacer Corte</b-button>
            </div>
        </div>

        </div>
    </div>
</template>

<script>
import HttpService from '../../Servicios/HttpService'
import ReportesPdfService from '../../Servicios/ReportesPdfService'

export default {
    name: 'WidgetCaja',
    data: () => ({
        cargando: false,
        cajaAbierta: false,
        caja: null,
        montoApertura: 0
    }),
    mounted() {
        this.verificarCaja()
    },
    methods: {
        verificarCaja() {
            this.cargando = true
            HttpService.obtener("obtener_estado_caja.php")
            .then(resultado => {
                this.cargando = false
                if (resultado) {
                    this.cajaAbierta = true
                    this.caja = resultado
                } else {
                    this.cajaAbierta = false
                    this.caja = null
                }
            })
            .catch(() => {
                this.cargando = false
                this.$toast({ message: 'Error verificando caja', type: 'is-danger' })
            })
        },
        abrirCaja() {
            if (this.montoApertura < 0 || this.montoApertura === '') {
                this.$toast({ message: 'Escribe un monto de fondo válido', type: 'is-warning' })
                return
            }
            this.cargando = true
            let payload = {
                idUsuario: localStorage.getItem('idUsuario'),
                montoApertura: parseFloat(this.montoApertura)
            }
            HttpService.registrar(payload, "abrir_caja.php")
            .then(resultado => {
                this.cargando = false
                if(resultado) {
                    this.$toast({ message: 'Caja Abierta Exitosamente', type: 'is-success' })
                    this.verificarCaja()
                }
            })
        },
        registrarGasto() {
            this.$buefy.dialog.prompt({
                title: 'Registrar Gasto o Retiro',
                message: 'Escribe el Motivo del gasto (ej. "Garrafones de agua"):',
                inputAttrs: { type: 'text', placeholder: 'Motivo / Concepto', required: true },
                trapFocus: true,
                onConfirm: (concepto) => {
                    this.$buefy.dialog.prompt({
                        title: 'Monto del Gasto',
                        message: `Cantidad retirada de caja para: <b>${concepto}</b>`,
                        inputAttrs: { type: 'number', step: '0.01', min: 0.1, placeholder: '100.00', required: true },
                        confirmText: 'Retirar Efectivo',
                        type: 'is-danger',
                        onConfirm: (monto) => {
                            this.cargando = true;
                            let payload = {
                                idCaja: this.caja.id,
                                concepto: concepto,
                                monto: parseFloat(monto),
                                idUsuario: localStorage.getItem('idUsuario')
                            };
                            HttpService.registrar(payload, "registrar_gasto.php")
                            .then(resultado => {
                                this.cargando = false;
                                if(resultado) {
                                    this.$toast({ message: 'Gasto registrado. Caja actualizada.', type: 'is-success' });
                                    this.verificarCaja();
                                }
                            });
                        }
                    });
                }
            });
        },
        cerrarCaja() {
            this.cargando = true;
            HttpService.obtener("obtener_mesas.php")
            .then(resultado => {
                this.cargando = false;
                let mesasOcupadas = resultado.filter(m => m.mesa.estado === 'ocupada');
                if (mesasOcupadas.length > 0) {
                    this.$toast({
                        message: `¡No puedes cerrar la caja! Tienes ${mesasOcupadas.length} mesa(s) aún sin cobrar. Cóbralas o cancélalas primero.`,
                        type: 'is-danger',
                        position: 'is-bottom',
                        duration: 6000
                    });
                    return;
                }
                
                this.mostrarPromptCierre();
            })
            .catch(() => {
                this.cargando = false;
            });
        },
        mostrarPromptCierre() {
            let totalEsperado = Math.round(parseFloat(this.caja.montoApertura) + parseFloat(this.caja.ventasEfectivo || 0) - parseFloat(this.caja.gastosAcumulados || 0));
            
            let message = `<p>Fondo Inicial: <b>Bs. ${Math.round(this.caja.montoApertura)}</b></p>
                           <p>Ingresos a Cajón (Efectivo): <b>Bs. ${Math.round(this.caja.ventasEfectivo || 0)}</b></p>
                           <p>Retiros y Gastos: <b class="has-text-danger">-Bs. ${Math.round(this.caja.gastosAcumulados || 0)}</b></p>
                           <hr>
                           <p class="is-size-5">Efectivo Físico Esperado: <b>Bs. ${totalEsperado}</b></p>
                           <br>
                           <p>¿Cuánto dinero FÍSICO hay en el cajón en este momento?</p>`;

            this.$buefy.dialog.prompt({
                title: 'Corte de Caja',
                message: message,
                inputAttrs: {
                    type: 'number',
                    placeholder: 'Efectivo Real',
                    min: 0,
                    step: '0.01'
                },
                trapFocus: true,
                confirmText: 'Declarar Efectivo y Cerrar',
                cancelText: 'Cancelar',
                type: 'is-danger',
                onConfirm: (value) => {
                    let fisico = parseFloat(value);
                    let diferencia = fisico - totalEsperado;

                    this.cargando = true;
                    let payload = {
                        idUsuario: localStorage.getItem('idUsuario'),
                        montoCierre: fisico
                    };

                    HttpService.registrar(payload, "cerrar_caja.php")
                    .then(resultado => {
                        this.cargando = false;
                        if(resultado && resultado.ok) {
                            let fisico = Math.round(parseFloat(value));
                            let diferencia = fisico - totalEsperado;
                            let msj = `Caja Cerrada. <br>Efectivo declarado: Bs. ${fisico} <br>Efectivo calculado: Bs. ${totalEsperado} <br><br>`
                            msj += diferencia === 0 ? "<b>¡El cuadre de efectivo es EXACTO!</b>" : `<b>Diferencia reportada: Bs. ${diferencia}</b>`
                            this.$buefy.dialog.alert({
                                title: 'Corte Exitoso',
                                message: msj,
                                type: diferencia >= 0 ? 'is-success' : 'is-warning'
                            })
                            ReportesPdfService.imprimirCierreCaja80mm(resultado)
                            this.verificarCaja();
                            this.montoApertura = 0;
                        }
                    })
                }
            })
        }
    }
}
</script>
