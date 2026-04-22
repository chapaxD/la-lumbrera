<template>
    <section class="section" style="min-height: 70vh;" @click="desbloquearAudioSiHaceFalta">
        <div class="columns is-mobile is-multiline is-vcentered mb-4">
            <div class="column is-12-mobile">
                <p class="title is-1 has-text-weight-bold">
                    <b-icon icon="silverware-fork-knife" size="is-large" type="is-primary"></b-icon>
                    Pantalla Cocina
                </p>
            </div>
            <div class="column is-12-mobile has-text-right-tablet">
                <div class="buttons is-right">
                    <b-button type="is-warning" icon-left="alert-circle-outline" @click="abrirModalReporte">
                        Reportar faltante
                    </b-button>
                </div>
                <p class="is-size-7 mt-1" :class="conectado ? 'has-text-success' : 'has-text-danger'">
                    <b-icon :icon="conectado ? 'wifi' : 'wifi-off'" size="is-small" class="mr-1"></b-icon>
                    {{ conectado ? 'Conectado (6s)' : 'Reconectando...' }}
                </p>
                <p v-if="!audioListo" class="is-size-7 has-text-info mt-1">
                    Tocá la pantalla una vez (cualquier botón) para activar el sonido de nuevas comandas.
                </p>
            </div>
        </div>

        <!-- Sin órdenes (Empty State) -->
        <div v-if="!cargando && ordenes.length === 0" class="has-text-centered py-6 is-flex is-flex-direction-column is-align-items-center" style="animation: fadeIn 0.5s;">
            <div class="mb-4 p-5" style="border-radius: 50%; background: rgba(0, 209, 178, 0.1); display: inline-block;">
                <b-icon icon="silverware-clean" type="is-success" custom-size="fa-5x" style="font-size: 5rem; opacity: 0.9;"></b-icon>
            </div>
            <p class="title is-3 mt-3 has-text-success">¡Todo preparado y servido!</p>
            <p class="subtitle is-5 has-text-grey mt-2">Tómense un buen respiro, equipo. La cocina brilla de limpia. ✨<br><br><small>Esperando nuevas comandas...</small></p>
        </div>

        <!-- Tarjetas de órdenes -->
        <div class="columns is-multiline">
            <div class="column is-4-widescreen is-6-tablet is-12-mobile" v-for="orden in ordenes"
                :key="orden.tipo + '-' + orden.id">
                <div class="card cocina-card" :class="claseUrgencia(minutosEspera(orden.horaInicio), orden.todoListo)">
                    <!-- Encabezado tarjeta -->
                    <div class="cocina-card-header">
                        <!-- Fila 1: tipo + id + cliente -->
                        <div class="cocina-card-titulo">
                            <b-icon
                                :icon="orden.tipo === 'LOCAL' ? 'table-chair' : orden.tipo === 'LLEVAR' ? 'walk' : 'moped'"
                                size="is-medium" class="mr-2 cocina-icono-tipo">
                            </b-icon>
                            <div class="cocina-titulo-texto">
                                <span class="is-size-5 has-text-weight-bold">
                                    <span v-if="orden.tipo === 'LOCAL'">Mesa {{ orden.id }}</span>
                                    <span v-else-if="orden.tipo === 'LLEVAR'">Para llevar #{{ orden.id }}</span>
                                    <span v-else>Delivery #{{ orden.id }}</span>
                                </span>
                                <span class="is-size-6 has-text-grey cocina-cliente"
                                    v-if="orden.cliente && orden.cliente !== 'S/N'">
                                    {{ orden.cliente }}
                                </span>
                            </div>
                        </div>
                        <!-- Fila 2: tags de estado -->
                        <div class="cocina-card-tags">
                            <b-tag v-if="orden.pagada" type="is-success" rounded>
                                <b-icon icon="cash-check" size="is-small" class="mr-1"></b-icon>
                                COBRADO
                            </b-tag>
                            <b-tag v-if="orden.horaInicio" :type="colorEspera(minutosEspera(orden.horaInicio))" rounded>
                                <b-icon icon="clock-outline" size="is-small" class="mr-1"></b-icon>
                                {{ minutosEspera(orden.horaInicio) }}m
                            </b-tag>
                            <b-tag :type="orden.todoListo ? 'is-success' : 'is-danger'" rounded>
                                {{ orden.pendientes }} pendiente{{ orden.pendientes !== 1 ? 's' : '' }}
                            </b-tag>
                        </div>
                    </div>

                    <!-- Ítems de la orden -->
                    <div class="card-content p-3">
                        <div v-for="insumo in orden.insumos" :key="insumo._originalIndex" class="box p-3 mb-2" :class="{
                            'has-background-danger-light': ((insumo.categoria || '').toLowerCase() !== 'carnes' || !usaParrilla) ? insumo.estado === 'pendiente' : insumo.acompanamiento_listo === 0,
                            'has-background-success-light': ((insumo.categoria || '').toLowerCase() !== 'carnes' || !usaParrilla) ? insumo.estado === 'listo' : insumo.acompanamiento_listo === 1,
                            'has-background-info-light': (insumo.categoria || '').toLowerCase() === 'carnes' && usaParrilla && insumo.acompanamiento_listo === 0
                        }">
                            <div
                                style="display:flex; align-items:center; gap:6px; margin-bottom:4px; flex-wrap:wrap;">
                                <b-icon :icon="(((insumo.categoria || '').toLowerCase() === 'carnes' && usaParrilla) ? insumo.acompanamiento_listo === 1 : insumo.estado === 'listo') ? 'check-circle' : 'clock-alert-outline'"
                                    :type="(((insumo.categoria || '').toLowerCase() === 'carnes' && usaParrilla) ? insumo.acompanamiento_listo === 1 : insumo.estado === 'listo') ? 'is-success' : ((insumo.categoria || '').toLowerCase() === 'carnes' && usaParrilla) ? 'is-info' : 'is-danger'"
                                    style="flex-shrink:0;">
                                </b-icon>
                                <span class="has-text-weight-bold is-size-5"
                                    style="flex:1; min-width:0; word-break:break-word;">
                                    {{ insumo.cantidad }}x {{ insumo.nombre }}
                                    <b-tag v-if="(insumo.categoria || '').toLowerCase() === 'carnes' && usaParrilla" type="is-info"
                                        size="is-small" class="ml-1" rounded>
                                        ACOMPAÑAMIENTO / PARRILLA
                                    </b-tag>
                                </span>
                                <div style="flex-shrink:0; margin-left:4px;">
                                    <template v-if="(insumo.categoria || '').toLowerCase() === 'carnes' && usaParrilla">
                                        <b-button v-if="insumo.acompanamiento_listo === 0" type="is-success" size="is-small"
                                            icon-left="check" :loading="insumo.cargando"
                                            @click="marcarListo(orden, insumo)">
                                            Acomp. Listo
                                        </b-button>
                                        <b-tag v-else type="is-success is-light">Acomp. Listo ✓</b-tag>
                                    </template>
                                    <template v-else>
                                        <b-button v-if="insumo.estado === 'pendiente'" type="is-success" size="is-small"
                                            icon-left="check" :loading="insumo.cargando"
                                            @click="marcarListo(orden, insumo)">
                                            Listo
                                        </b-button>
                                        <b-tag v-else type="is-success is-light">Listo ✓</b-tag>
                                    </template>
                                </div>
                            </div>
                            <!-- Características / instrucciones especiales -->
                            <p v-if="insumo.caracteristicas && insumo.caracteristicas.trim() !== ''"
                                class="is-size-4 has-text-black has-text-weight-bold ml-5 mt-1 mb-2"
                                style="border-left: 5px solid #f5a623; padding-left: 12px; background-color: rgba(245, 166, 35, 0.15); border-radius: 0 4px 4px 0;">
                                <b-icon icon="note-text-outline" size="is-small" class="mr-1"></b-icon>
                                {{ insumo.caracteristicas }}
                            </p>
                            <p v-if="insumo.resumenCombo && insumo.resumenCombo.trim() !== ''"
                                class="is-size-4 has-text-black has-text-weight-bold ml-5 mt-2"
                                style="border-left: 5px solid #000; padding-left: 12px; background-color: rgba(0,0,0,0.05); border-radius: 0 4px 4px 0; white-space: pre-line;">
                                <b-icon icon="food-variant" size="is-small" class="mr-1"></b-icon>
                                {{ Utiles.formatearResumenCombo(insumo.resumenCombo) }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <footer class="card-footer">
                        <a class="card-footer-item has-text-grey-dark" @click="imprimirComanda(orden)">
                            <b-icon icon="printer" size="is-small" class="mr-1"></b-icon>
                            Imprimir comanda
                        </a>
                    </footer>
                </div>
            </div>
        </div>

        <!-- Modal: Reportar faltante -->
        <b-modal v-model="modalReporte" has-modal-card trap-focus :destroy-on-hide="false">
            <div class="modal-card" style="min-width: 420px; max-width: 500px;">
                <header class="modal-card-head">
                    <p class="modal-card-title">
                        <b-icon icon="alert-circle-outline" type="is-warning"></b-icon>
                        Reportar faltante o problema
                    </p>
                    <button class="delete" @click="modalReporte = false"></button>
                </header>
                <section class="modal-card-body">
                    <b-field label="Insumo (opcional — escribí el nombre)">
                        <b-autocomplete v-model="reporte.nombreInsumo" :data="insumosFiltrados" field="nombre"
                            placeholder="Ej: Queso mozzarella..." @typing="buscarInsumo" @select="seleccionarInsumo"
                            clearable>
                        </b-autocomplete>
                    </b-field>
                    <b-field label="Tipo de problema">
                        <b-select v-model="reporte.tipo" expanded>
                            <option value="FALTANTE">Faltante — se agotó</option>
                            <option value="BAJO_STOCK">Bajo stock — queda poco</option>
                            <option value="VENCIDO">Vencido / en mal estado</option>
                            <option value="OTRO">Otro</option>
                        </b-select>
                    </b-field>
                    <b-field label="Nota adicional">
                        <b-input v-model="reporte.nota" type="textarea" rows="3"
                            placeholder="Ej: Se terminó el aceite, no alcanza para la noche...">
                        </b-input>
                    </b-field>
                </section>
                <footer class="modal-card-foot">
                    <b-button type="is-warning" icon-left="send" @click="enviarReporte" :loading="enviandoReporte">
                        Enviar reporte
                    </b-button>
                    <b-button type="is-dark" @click="modalReporte = false">Cancelar</b-button>
                </footer>
            </div>
        </b-modal>
    </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'
import Utiles from '../../Servicios/Utiles'

export default {
    name: 'Cocina',
    data: () => ({
        Utiles,
        ordenes: [],
        cargando: true,
        ahora: Date.now(),
        conectado: false,
        intervaloPolling: null,
        intervaloReloj: null,
        modalReporte: false,
        enviandoReporte: false,
        insumosFiltrados: [],
        ultimoConteoPendientes: 0,
        pollSonidoInicialHecha: false,
        audioCampana: null,
        audioListo: false,
        reporte: {
            idInsumo: null,
            nombreInsumo: '',
            tipo: 'FALTANTE',
            nota: ''
        },
        usaParrilla: true
    }),

    mounted() {
        this.obtenerConfiguracion()
        this.cargarOrdenes()
        this.intervaloPolling = setInterval(() => {
            this.ahora = Date.now()
            this.cargarOrdenes()
        }, 6000)
        this.intervaloReloj = setInterval(() => { this.ahora = Date.now() }, 30000)
    },

    beforeDestroy() {
        clearInterval(this.intervaloPolling)
        clearInterval(this.intervaloReloj)
    },

    methods: {
        async obtenerConfiguracion() {
            try {
                const config = await HttpService.obtener('obtener_datos_local.php')
                if (config) {
                    this.usaParrilla = parseInt(config.usa_pantalla_parrilla) !== 0
                }
            } catch (e) {
                console.error("Error al obtener config", e)
            }
        },

        async cargarOrdenes() {
            try {
                const [mesas, deliveries] = await Promise.all([
                    HttpService.obtener('obtener_mesas.php'),
                    HttpService.obtener('obtener_deliveries.php')
                ])
                this.conectado = true
                this.procesarDatos(mesas, deliveries)
            } catch (e) {
                this.conectado = false
            }
        },

        procesarDatos(mesas, deliveries) {
            const ordenesLocales = (mesas || [])
                .filter(m => m.mesa.estado === 'ocupada' || m.mesa.estado === 'pagada')
                .map(m => {
                    const insumos = (m.insumos || [])
                        .map((insumo, idx) => ({ ...insumo, _originalIndex: idx, cargando: false }))
                        .filter(i =>
                            i.estado !== 'entregado' &&
                            (i.tipo || '').toUpperCase() !== 'BEBIDA'
                        )
                    return {
                        id: m.mesa.idMesa,
                        tipo: 'LOCAL',
                        cliente: m.mesa.cliente,
                        horaInicio: m.mesa.created_at || null,
                        pagada: m.mesa.estado === 'pagada',
                        insumos,
                        pendientes: insumos.filter(i => i.estado === 'pendiente').length,
                        todoListo: insumos.length > 0 && insumos.every(i => i.estado === 'listo')
                    }
                })
                .filter(o => o.insumos.length > 0)

            const ordenesDelivery = (deliveries || []).map(d => {
                const insumos = (d.insumos || [])
                    .map((insumo, idx) => ({ ...insumo, _originalIndex: idx, cargando: false }))
                    // para filtrar que mostrar en cocina
                    .filter(i =>
                        i.estado !== 'entregado' &&
                        (i.tipo || '').toUpperCase() !== 'BEBIDA'
                    )
                return {
                    id: d.delivery.idDelivery,
                    tipo: d.delivery.tipo_orden || 'DELIVERY',
                    cliente: d.delivery.cliente,
                    atiende: d.delivery.atiende,
                    horaInicio: d.delivery.created_at || null,
                    pagada: d.delivery.estado_orden === 'pagada',
                    insumos,
                    pendientes: insumos.filter(i => i.estado === 'pendiente').length,
                    todoListo: insumos.length > 0 && insumos.every(i => i.estado === 'listo')
                }
            }).filter(o => o.insumos.length > 0)

            this.ordenes = [...ordenesLocales, ...ordenesDelivery]
            
            const pendientesActuales = this.ordenes.reduce((total, orden) => total + orden.pendientes, 0)
            const subieronPendientes = pendientesActuales > this.ultimoConteoPendientes
            if (this.pollSonidoInicialHecha && subieronPendientes) {
                this.reproducirSonido()
            }
            this.ultimoConteoPendientes = pendientesActuales
            this.pollSonidoInicialHecha = true

            this.cargando = false
        },

        getAudioCampana() {
            if (!this.audioCampana) {
                this.audioCampana = new Audio('/static/campana.ogg')
            }
            return this.audioCampana
        },
        desbloquearAudioSiHaceFalta() {
            if (this.audioListo) return
            const a = this.getAudioCampana()
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
        reproducirSonido() {
            try {
                const audio = this.getAudioCampana()
                audio.currentTime = 0
                audio.volume = 0.6
                audio.play().catch(e => console.warn('Sonido bloqueado por navegador:', e))
            } catch (e) {
                console.warn('No se pudo reproducir alerta:', e)
            }
        },

        async marcarListo(orden, insumo) {
            insumo.cargando = true
            // Pausar polling para evitar que revierta el cambio visual
            clearInterval(this.intervaloPolling)
            const esCarnes = (insumo.categoria || '').toLowerCase() === 'carnes' && this.usaParrilla
            const ok = await HttpService.registrar({
                tipo: orden.tipo,
                id: orden.id,
                indiceInsumo: insumo._originalIndex,
                soloAcompanamiento: esCarnes
            }, 'marcar_listo_cocina.php')
            if (ok) {
                if (esCarnes) insumo.acompanamiento_listo = 1
                else insumo.estado = 'listo'
                
                orden.pendientes = orden.insumos.filter(i => 
                    ((i.categoria || '').toLowerCase() === 'carnes' && this.usaParrilla) ? i.acompanamiento_listo === 0 : i.estado === 'pendiente'
                ).length
                orden.todoListo = orden.insumos.length > 0 && orden.insumos.every(i => 
                    ((i.categoria || '').toLowerCase() === 'carnes' && this.usaParrilla) ? i.acompanamiento_listo === 1 : i.estado === 'listo'
                )
            }
            insumo.cargando = false
            // Recargar desde BD después de un momento y reanudar polling
            setTimeout(() => {
                this.cargarOrdenes()
                this.intervaloPolling = setInterval(() => {
                    this.ahora = Date.now()
                    this.cargarOrdenes()
                }, 6000)
            }, 1500)
        },

        minutosEspera(horaInicio) {
            if (!horaInicio) return 0
            // Normalizamos el formato para evitar problemas de interpretación UTC vs Local
            let fechaStr = horaInicio.replace(' ', 'T')
            const inicio = new Date(fechaStr).getTime()
            let minutos = Math.floor((this.ahora - inicio) / 60000)

            // Si la diferencia es muy grande o negativa (ej. -240 min), compensamos zona horaria
            if (minutos < -5 || minutos > 720) {
                const offsetMin = new Date().getTimezoneOffset()
                // Intentamos corregir el desfase restando el offset del navegador
                minutos = Math.floor((this.ahora - (inicio - offsetMin * 60000)) / 60000)
            }

            return minutos < 0 ? 0 : minutos
        },

        colorEspera(mins) {
            if (mins < 10) return 'is-success'
            if (mins < 20) return 'is-warning'
            return 'is-danger'
        },

        claseUrgencia(mins, todoListo) {
            if (todoListo) return 'cocina-pagada-lista'
            if (mins >= 20) return 'urgencia-roja'
            if (mins >= 10) return 'urgencia-naranja'
            return 'urgencia-verde'
        },

        abrirModalReporte() {
            this.reporte = { idInsumo: null, nombreInsumo: '', tipo: 'FALTANTE', nota: '' }
            this.insumosFiltrados = []
            this.modalReporte = true
        },

        async buscarInsumo(texto) {
            if (!texto || texto.length < 2) {
                this.insumosFiltrados = []
                return
            }
            const resultado = await HttpService.registrar({ insumo: texto }, 'obtener_insumo_nombre.php')
            this.insumosFiltrados = resultado || []
        },

        seleccionarInsumo(insumo) {
            if (insumo) {
                this.reporte.idInsumo = insumo.id
                this.reporte.nombreInsumo = insumo.nombre
            }
        },

        imprimirComanda(orden) {
            this.Utiles.imprimirComanda(orden)
        },

        async enviarReporte() {
            if (!this.reporte.nombreInsumo.trim() && !this.reporte.nota.trim()) {
                this.$toast({ message: 'Indicá el insumo o describí el problema', type: 'is-warning' })
                return
            }
            this.enviandoReporte = true
            try {
                await HttpService.registrar({
                    ...this.reporte,
                    idUsuario: localStorage.getItem('idUsuario')
                }, 'registrar_reporte_cocina.php')
                this.$toast({ message: '✓ Reporte enviado al administrador', type: 'is-success', duration: 4000 })
                this.modalReporte = false
            } finally {
                this.enviandoReporte = false
            }
        }
    }
}
</script>

<style scoped>
.urgencia-verde {
    background-color: #f5fff5;
    border-top: 5px solid #48c774;
}
.urgencia-naranja {
    background-color: #fff9f0;
    border-top: 5px solid #ffdd57;
}
.urgencia-roja {
    background-color: #fff0f0;
    border-top: 5px solid #f14668;
    animation: pulso-rojo 2s infinite;
}
@keyframes pulso-rojo {
    0% { box-shadow: 0 0 0 0 rgba(241, 70, 104, 0.4); }
    70% { box-shadow: 0 0 0 15px rgba(241, 70, 104, 0); }
    100% { box-shadow: 0 0 0 0 rgba(241, 70, 104, 0); }
}
</style>
