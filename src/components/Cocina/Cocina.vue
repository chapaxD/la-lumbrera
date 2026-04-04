<template>
    <section class="section">
        <div class="columns is-vcentered mb-4">
            <div class="column">
                <p class="title is-1 has-text-weight-bold">
                    <b-icon icon="silverware-fork-knife" size="is-large" type="is-primary"></b-icon>
                    Pantalla Cocina
                </p>
            </div>
            <div class="column is-narrow has-text-right">
                <b-button type="is-warning" icon-left="alert-circle-outline" @click="abrirModalReporte">
                    Reportar faltante
                </b-button>
                <p class="is-size-7 mt-1" :class="conectado ? 'has-text-success' : 'has-text-danger'">
                    <b-icon :icon="conectado ? 'wifi' : 'wifi-off'" size="is-small" class="mr-1"></b-icon>
                    {{ conectado ? 'Conectado (6s)' : 'Reconectando...' }}
                </p>
            </div>
        </div>

        <!-- Sin órdenes -->
        <div v-if="!cargando && ordenes.length === 0" class="has-text-centered py-6">
            <b-icon icon="check-circle-outline" type="is-success" size="is-large"></b-icon>
            <p class="title is-4 mt-3 has-text-success">Sin órdenes pendientes</p>
            <p class="has-text-grey">Esperando nuevas comandas...</p>
        </div>

        <!-- Tarjetas de órdenes -->
        <div class="columns is-multiline">
            <div
                class="column is-4-widescreen is-6-tablet is-12-mobile"
                v-for="orden in ordenes"
                :key="orden.tipo + '-' + orden.id">
                <div class="card cocina-card" :class="[
                    orden.todoListo ? 'cocina-pagada-lista' : 'has-background-warning-light'
                ]">
                    <!-- Encabezado tarjeta -->
                    <div class="cocina-card-header">
                        <!-- Fila 1: tipo + id + cliente -->
                        <div class="cocina-card-titulo">
                            <b-icon
                                :icon="orden.tipo === 'LOCAL' ? 'table-chair' : orden.tipo === 'LLEVAR' ? 'walk' : 'moped'"
                                size="is-medium"
                                class="mr-2 cocina-icono-tipo">
                            </b-icon>
                            <div class="cocina-titulo-texto">
                                <span class="is-size-5 has-text-weight-bold">
                                    <span v-if="orden.tipo === 'LOCAL'">Mesa {{ orden.id }}</span>
                                    <span v-else-if="orden.tipo === 'LLEVAR'">Para llevar #{{ orden.id }}</span>
                                    <span v-else>Delivery #{{ orden.id }}</span>
                                </span>
                                <span class="is-size-6 has-text-grey cocina-cliente" v-if="orden.cliente && orden.cliente !== 'S/N'">
                                    {{ orden.cliente }}
                                </span>
                            </div>
                        </div>
                        <!-- Fila 2: tags de estado -->
                        <div class="cocina-card-tags">
                            <b-tag
                                v-if="orden.pagada"
                                type="is-success"
                                rounded>
                                <b-icon icon="cash-check" size="is-small" class="mr-1"></b-icon>
                                COBRADO
                            </b-tag>
                            <b-tag
                                v-if="orden.horaInicio"
                                :type="colorEspera(minutosEspera(orden.horaInicio))"
                                rounded>
                                <b-icon icon="clock-outline" size="is-small" class="mr-1"></b-icon>
                                {{ minutosEspera(orden.horaInicio) }}m
                            </b-tag>
                            <b-tag
                                :type="orden.todoListo ? 'is-success' : 'is-danger'"
                                rounded>
                                {{ orden.pendientes }} pendiente{{ orden.pendientes !== 1 ? 's' : '' }}
                            </b-tag>
                        </div>
                    </div>

                    <!-- Ítems de la orden -->
                    <div class="card-content p-3">
                        <div
                            v-for="insumo in orden.insumos"
                            :key="insumo._originalIndex"
                            class="box p-3 mb-2"
                            :class="{
                                'has-background-danger-light': insumo.estado === 'pendiente',
                                'has-background-success-light': insumo.estado === 'listo'
                            }">
                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px; flex-wrap:nowrap;">
                                <b-icon
                                    :icon="insumo.estado === 'listo' ? 'check-circle' : 'clock-alert-outline'"
                                    :type="insumo.estado === 'listo' ? 'is-success' : 'is-danger'"
                                    style="flex-shrink:0;">
                                </b-icon>
                                <span class="has-text-weight-bold is-size-5" style="flex:1; min-width:0; word-break:break-word;">
                                    {{ insumo.cantidad }}x {{ insumo.nombre }}
                                </span>
                                <div style="flex-shrink:0; margin-left:4px;">
                                    <b-button
                                        v-if="insumo.estado === 'pendiente'"
                                        type="is-success"
                                        size="is-small"
                                        icon-left="check"
                                        :loading="insumo.cargando"
                                        @click="marcarListo(orden, insumo)">
                                        Listo
                                    </b-button>
                                    <b-tag v-else type="is-success is-light">Listo ✓</b-tag>
                                </div>
                            </div>
                            <!-- Características / instrucciones especiales -->
                            <p
                                v-if="insumo.caracteristicas && insumo.caracteristicas.trim() !== ''"
                                class="is-size-6 has-text-dark ml-5 mt-1"
                                style="border-left: 3px solid #f5a623; padding-left: 8px;">
                                <b-icon icon="note-text-outline" size="is-small"></b-icon>
                                {{ insumo.caracteristicas }}
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
                        <b-autocomplete
                            v-model="reporte.nombreInsumo"
                            :data="insumosFiltrados"
                            field="nombre"
                            placeholder="Ej: Queso mozzarella..."
                            @typing="buscarInsumo"
                            @select="seleccionarInsumo"
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
                        <b-input
                            v-model="reporte.nota"
                            type="textarea"
                            rows="3"
                            placeholder="Ej: Se terminó el aceite, no alcanza para la noche...">
                        </b-input>
                    </b-field>
                </section>
                <footer class="modal-card-foot">
                    <b-button
                        type="is-warning"
                        icon-left="send"
                        @click="enviarReporte"
                        :loading="enviandoReporte">
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

export default {
    name: 'Cocina',
    data: () => ({
        ordenes: [],
        cargando: true,
        ahora: Date.now(),
        conectado: false,
        intervaloPolling: null,
        intervaloReloj: null,
        modalReporte: false,
        enviandoReporte: false,
        insumosFiltrados: [],
        reporte: {
            idInsumo: null,
            nombreInsumo: '',
            tipo: 'FALTANTE',
            nota: ''
        }
    }),

    mounted() {
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
                        .filter(i => i.estado !== 'entregado' && (i.tipo || '').toUpperCase() !== 'BEBIDA')
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
                    .filter(i => i.estado !== 'entregado' && (i.tipo || '').toUpperCase() !== 'BEBIDA')
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
            this.cargando = false
        },

        async marcarListo(orden, insumo) {
            insumo.cargando = true
            // Pausar polling para evitar que revierta el cambio visual
            clearInterval(this.intervaloPolling)
            const ok = await HttpService.registrar({
                tipo: orden.tipo,
                id: orden.id,
                indiceInsumo: insumo._originalIndex
            }, 'marcar_listo_cocina.php')
            if (ok) {
                insumo.estado = 'listo'
                orden.pendientes = orden.insumos.filter(i => i.estado === 'pendiente').length
                orden.todoListo = orden.insumos.length > 0 && orden.insumos.every(i => i.estado === 'listo')
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
            const inicio = new Date(horaInicio.replace(' ', 'T') + 'Z').getTime()
            return Math.floor((this.ahora - inicio) / 60000)
        },

        colorEspera(mins) {
            if (mins < 10) return 'is-success'
            if (mins < 20) return 'is-warning'
            return 'is-danger'
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
            const ahora = new Date()
            const hora = ahora.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })
            const fecha = ahora.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
            const espera = this.minutosEspera(orden.horaInicio)

            let encabezadoTipo = ''
            if (orden.tipo === 'LOCAL') encabezadoTipo = `&#x1F4CB; MESA ${orden.id}`
            else if (orden.tipo === 'LLEVAR') encabezadoTipo = `&#x1F6B6; PARA LLEVAR #${orden.id}`
            else encabezadoTipo = `&#x1F6F5; DELIVERY #${orden.id}`

            const itemsHtml = orden.insumos.map(ins => {
                const estadoTag = ins.estado === 'listo' ? ' <span style="color:#2d8a2d">[LISTO]</span>' : ''
                let html = `<div class="item"><span class="cant">${ins.cantidad}x</span> <span class="nombre">${ins.nombre}</span>${estadoTag}</div>`
                if (ins.caracteristicas && ins.caracteristicas.trim()) {
                    html += `<div class="carac">&rarr; ${ins.caracteristicas}</div>`
                }
                return html
            }).join('')

            const ventana = window.open('', '_blank', 'width=420,height=640')
            if (!ventana) {
                this.$buefy.toast.open({ message: 'El navegador bloqueó la ventana. Permití las ventanas emergentes.', type: 'is-warning', duration: 5000 })
                return
            }
            ventana.document.write(`<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Comanda</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Courier New', Courier, monospace; font-size: 13px; width: 80mm; padding: 5mm 4mm; }
    h2 { text-align: center; font-size: 17px; letter-spacing: 2px; margin-bottom: 3px; }
    .linea { border-top: 1px dashed #000; margin: 6px 0; }
    .tipo { text-align: center; font-size: 20px; font-weight: bold; margin: 5px 0 3px; }
    .cliente { text-align: center; font-size: 12px; margin-bottom: 2px; }
    .meta { text-align: center; font-size: 11px; color: #555; margin-bottom: 4px; }
    .item { font-size: 15px; margin: 5px 0 2px; }
    .cant { font-weight: bold; }
    .nombre { }
    .carac { font-size: 12px; margin-left: 24px; font-style: italic; margin-bottom: 3px; color: #333; }
    .pie { text-align: center; font-size: 11px; margin-top: 8px; color: #777; }
    @media print { body { margin: 0; } }
  </style>
</head>
<body>
  <h2>--- COCINA ---</h2>
  <div class="linea"></div>
  <div class="tipo">${encabezadoTipo}</div>
  ${orden.cliente && orden.cliente !== 'S/N' ? `<div class="cliente">Cliente: <strong>${orden.cliente}</strong></div>` : ''}
  <div class="meta">${fecha} &bull; ${hora}${espera > 0 ? ` &bull; Espera: ${espera} min` : ''}</div>
  <div class="linea"></div>
  ${itemsHtml}
  <div class="linea"></div>
  <div class="pie">Impreso a las ${hora}</div>
</body>
</html>`)
            ventana.document.close()
            ventana.focus()
            setTimeout(() => { ventana.print() }, 300)
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
<style>
@keyframes pulso-pagada {
    0%, 100% { box-shadow: 0 0 0 0 rgba(72, 199, 142, 0); }
    50%       { box-shadow: 0 0 0 10px rgba(72, 199, 142, 0.5); }
}
.cocina-pagada-lista {
    background-color: #effaf3 !important;
    border: 2px solid #48c78e !important;
    animation: pulso-pagada 1.2s ease-in-out infinite;
}
.cocina-pagada-lista.cobrado {
    border-color: #257942 !important;
}

/* ── Encabezado rediseñado ── */
.cocina-card {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.cocina-card-header {
    padding: 10px 14px 8px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.cocina-card-titulo {
    display: flex;
    align-items: center;
    min-width: 0;
}
.cocina-icono-tipo {
    flex-shrink: 0;
}
.cocina-titulo-texto {
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
}
.cocina-titulo-texto span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cocina-cliente {
    font-style: italic;
    line-height: 1.2;
}
.cocina-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
</style>
