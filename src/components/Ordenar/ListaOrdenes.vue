<template>
    <div class="columns is-multiline">
        <template v-if="cargando">
            <div class="column is-12" v-for="i in 4" :key="'skel-lista-' + i">
                <b-skeleton height="120px" animated></b-skeleton>
            </div>
        </template>
        <!-- Barra de resumen -->
        <template v-if="!cargando && ordenes.length > 0">
            <div class="column is-12 mb-2">
                <div class="is-flex is-flex-wrap-wrap" style="gap: 0.5rem;">
                    <span v-if="resumen.cobrar > 0" class="tag is-danger is-medium" style="font-weight:bold;">
                        <b-icon icon="cash-remove" size="is-small" class="mr-1"></b-icon>
                        {{ resumen.cobrar }} falta{{ resumen.cobrar > 1 ? 'n' : '' }} cobrar
                    </span>
                    <span v-if="resumen.listos > 0" class="tag is-warning is-medium" style="font-weight:bold;">
                        <b-icon icon="bell-ring" size="is-small" class="mr-1"></b-icon>
                        {{ resumen.listos }} listo{{ resumen.listos > 1 ? 's' : '' }}
                    </span>
                    <span v-if="resumen.cocina > 0" class="tag is-info is-medium">
                        <b-icon icon="chef-hat" size="is-small" class="mr-1"></b-icon>
                        {{ resumen.cocina }} en cocina
                    </span>
                    <span v-if="resumen.pagados > 0" class="tag is-success is-medium">
                        <b-icon icon="cash-check" size="is-small" class="mr-1"></b-icon>
                        {{ resumen.pagados }} pagado{{ resumen.pagados > 1 ? 's' : '' }}
                    </span>
                    <span v-if="resumen.entregados > 0" class="tag is-dark is-medium">
                        <b-icon icon="check-all" size="is-small" class="mr-1"></b-icon>
                        {{ resumen.entregados }} entregado{{ resumen.entregados > 1 ? 's' : '' }}
                    </span>
                </div>
            </div>
        </template>

        <template v-if="!cargando">
            <div class="column is-12" v-for="grupo in gruposOrdenes" :key="'grupo-' + grupo.titulo">
                <!-- Separador de Grupo -->
                <div class="notification is-light mb-4 mt-2 py-2" :class="grupo.color"
                    style="border-radius: 12px; border-left: 5px solid;">
                    <div class="is-flex is-align-items-center">
                        <b-icon :icon="grupo.icon" size="is-small" class="mr-2"></b-icon>
                        <span class="has-text-weight-bold is-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">
                            {{ grupo.titulo }}
                        </span>
                        <b-tag class="ml-auto" rounded :type="grupo.color"
                            style="font-weight: bold;">{{ grupo.items.length }}</b-tag>
                    </div>
                </div>

                <div class="columns is-multiline">
                    <div class="column is-12" v-for="orden in grupo.items" :key="orden.idUnique" :id="orden.idUnique">
                        <div class="card box-glass mb-4" :class="{ 'orden-lista-pulso': tieneListo(orden.insumos), 'orden-cobrar-pulso': orden.estado === 'entregada' && !orden.esPagada }">
                            <div class="card-content py-4">
                                <div class="columns is-vcentered is-mobile is-multiline">
                                    <!-- Icono y Ref -->
                                    <div class="column is-narrow">
                                        <div class="has-text-centered p-2"
                                            style="background: rgba(255,255,255,0.1); border-radius: 12px; min-width: 80px;">
                                            <b-icon
                                                :icon="orden.tipoRef === 'LOCAL' ? 'table-chair' : (orden.tipoRef === 'LLEVAR' ? 'walk' : 'truck-delivery')"
                                                size="is-medium"
                                                :type="orden.estado === 'pagada' ? 'is-success' : 'is-grey'"></b-icon>
                                            <p class="title is-4 mb-0"
                                                :class="orden.estado === 'pagada' ? 'has-text-success' : 'has-text-grey-dark'">
                                                {{ orden.tipoRef === 'LOCAL' ? '#' + orden.idRef : (orden.tipoRef ===
                                                    'LLEVAR' ? 'LLEV' : 'DEL') }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Info Principal -->
                                    <div class="column">
                                        <div class="is-flex is-align-items-center is-flex-wrap-wrap">
                                            <p class="title is-4 mb-0 mr-3">{{ orden.cliente || 'Sin Nombre' }}</p>
                                            <div class="tags">
                                                <b-tag v-if="orden.tipoRef !== 'LOCAL' && orden.estado === 'pendiente'"
                                                    type="is-danger" rounded pulse>¡IMPRIMIR COMANDA!</b-tag>
                                                <b-tag v-if="orden.estado === 'pagada'" type="is-success"
                                                    rounded>PAGADO</b-tag>
                                                <b-tag v-else-if="orden.estado === 'entregada' && !orden.esPagada"
                                                    type="is-danger" rounded pulse>¡FALTA COBRAR!</b-tag>
                                                <b-tag v-else-if="orden.estado === 'entregada'" type="is-link"
                                                    rounded>ENTREGADO</b-tag>
                                                <b-tag v-else-if="tieneListo(orden.insumos)" type="is-warning" rounded
                                                    pulse>¡LISTO!</b-tag>
                                                <b-tag v-else type="is-info" is-light rounded>EN COCINA</b-tag>
                                            </div>
                                        </div>
                                        <p class="is-size-6 has-text-grey">
                                            <b-icon icon="account" size="is-small"></b-icon> {{ orden.atiende }}
                                            <span class="mx-2">|</span>
                                            <b-icon icon="clock-outline" size="is-small"
                                                :class="claseReloj(orden.created_at)"></b-icon>
                                            <span :class="claseReloj(orden.created_at)" style="font-weight: 600;">
                                                Hace {{ Utiles.obtenerTiempoTranscurrido(orden.created_at) }}
                                            </span>
                                        </p>
                                        <p class="is-size-7 has-text-weight-bold mt-2"
                                            v-if="puedeAccederOrden(orden.idUsuario)">
                                            {{ orden.insumos.map(i => `${i.cantidad}x ${i.nombre}`).join(', ').substring(0,
                                                150) }}...
                                        </p>
                                        <p v-else class="has-text-danger is-size-7 mt-2">
                                            <b-icon icon="lock" size="is-small"></b-icon> Sin acceso a esta orden (Otro mesero)
                                        </p>
                                    </div>

                                    <!-- Total -->
                                    <div class="column is-narrow has-text-right"
                                        v-if="puedeAccederOrden(orden.idUsuario)">
                                        <p class="title is-2 has-text-primary mb-0">{{ Utiles.formatearDinero(orden.total) }}</p>
                                    </div>
                                </div>

                                <!-- Acciones Rápidas -->
                                <div class="buttons is-centered mt-4" v-if="puedeAccederOrden(orden.idUsuario)">
                                    <b-button v-if="orden.estado === 'pagada'" type="is-success" icon-left="hand-okay"
                                        @click="$emit('entregar', orden)">
                                        Finalizar Entrega
                                    </b-button>

                                    <b-button
                                        v-if="rol === 'mesero' && (orden.estado === 'ocupada' || orden.estado === 'pagada') && (tieneListo(orden.insumos) || (parseInt(datos.usa_pantalla_cocina || 0) === 0 && parseInt(datos.usa_pantalla_parrilla || 0) === 0))"
                                        type="is-warning" icon-left="bell-check" @click="$emit('servir', orden)">
                                        {{ orden.tipoRef === 'LOCAL' ? 'Servir Mesa' : 'Despachar' }}
                                    </b-button>

                                    <b-button v-if="rol !== 'mesero' && orden.estado === 'entregada' && orden.esPagada"
                                        :type="orden.tipoRef === 'LOCAL' ? 'is-dark' : 'is-success'"
                                        :icon-left="orden.tipoRef === 'LOCAL' ? 'logout' : 'hand-okay'"
                                        @click="$emit('liberar', orden)">
                                        {{ orden.tipoRef === 'LOCAL' ? 'Liberar Mesa' : 'Finalizar Entrega' }}
                                    </b-button>

                                    <b-button
                                        v-if="rol !== 'mesero' && (orden.estado === 'ocupada' || (orden.estado === 'entregada' && !orden.esPagada) || (orden.tipoRef !== 'LOCAL' && orden.estado === 'pendiente'))"
                                        type="is-success" icon-left="cash" @click="$emit('cobrar', orden)">
                                        Cobrar
                                    </b-button>

                                    <b-button type="is-info"
                                        :class="{ 'is-light': !(orden.tipoRef !== 'LOCAL' && orden.estado === 'pendiente'), 'btn-comanda-pulso': (orden.tipoRef !== 'LOCAL' && orden.estado === 'pendiente') }"
                                        icon-left="printer" @click="$emit('comanda', orden)">
                                        Comanda
                                    </b-button>

                                    <b-button type="is-info" icon-left="printer" is-light @click="$emit('detalle', orden)">
                                        Detalle
                                    </b-button>

                                    <b-button type="is-info" icon-left="plus" @click="$emit('agregar', orden)">
                                        Agregar
                                    </b-button>

                                    <b-button v-if="orden.tipoRef === 'LOCAL' && (orden.estado === 'ocupada' || orden.estado === 'pagada' || orden.estado === 'entregada')"
                                        type="is-primary" icon-left="account-multiple-plus" @click="$emit('compartir', orden)">
                                        Compartir
                                    </b-button>

                                    <b-button type="is-link" is-light icon-left="swap-horizontal" @click="$emit('cambiar', orden)">
                                        Cambiar
                                    </b-button>

                                    <b-button v-if="rol !== 'mesero'" type="is-danger" is-light icon-left="delete"
                                        @click="$emit('cancelar', orden)">
                                        Eliminar
                                    </b-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import Utiles from '../../Servicios/Utiles'

export default {
    name: 'ListaOrdenes',
    props: {
        ordenes: { type: Array, required: true },
        cargando: { type: Boolean, default: false },
        rol: { type: String, required: true },
        idUsuarioActual: { type: String, required: true },
        datos: { type: Object, default: () => ({}) }
    },
    data() {
        return {
            Utiles
        }
    },
    computed: {
        resumen() {
            return {
                cobrar:     this.ordenes.filter(o => o.prioridad === 0).length,
                listos:     this.ordenes.filter(o => o.prioridad === 1).length,
                pagados:    this.ordenes.filter(o => o.prioridad === 2).length,
                cocina:     this.ordenes.filter(o => o.prioridad === 3).length,
                entregados: this.ordenes.filter(o => o.prioridad === 4).length,
            }
        },
        gruposOrdenes() {
            const config = {
                0: { titulo: '⚠ ENTREGADO — ¡FALTA COBRAR!', color: 'is-danger', icon: 'cash-remove' },
                1: { titulo: '¡PEDIDOS LISTOS PARA ENTREGAR!', color: 'is-warning', icon: 'bell-ring' },
                2: { titulo: 'PEDIDOS PAGADOS / POR DESPACHAR', color: 'is-success', icon: 'cash-check' },
                3: { titulo: 'EN PREPARACIÓN (COCINA)', color: 'is-info', icon: 'chef-hat' },
                4: { titulo: 'PEDIDOS ENTREGADOS', color: 'is-dark', icon: 'check-all' }
            };

            const grupos = [];
            [0, 1, 2, 3, 4].forEach(prio => {
                const items = this.ordenes.filter(o => o.prioridad === prio);
                if (items.length > 0) {
                    grupos.push({
                        ...config[prio],
                        items
                    });
                }
            });
            return grupos;
        }
    },
    methods: {
        claseReloj(created_at) {
            if (!created_at) return 'has-text-grey'
            const mins = (Date.now() - new Date(created_at).getTime()) / 60000
            if (mins >= 20) return 'has-text-danger'
            if (mins >= 10) return 'has-text-warning-dark'
            return 'has-text-success'
        },
        tieneListo(insumos) {
            return (insumos || []).some(i => i.estado === 'listo' && 
                (i.tipo || '').toUpperCase() !== 'BEBIDA' && 
                (i.categoria || '').toUpperCase() !== 'BEBIDA' && 
                (i.categoria || '').toUpperCase() !== 'BEBIDAS');
        },
        tienePendiente(insumos) {
            return (insumos || []).some(i => i.estado === 'pendiente');
        },
        puedeAccederOrden(idUsuarioOrden) {
            if (this.rol === 'admin') return true;
            return String(idUsuarioOrden) === String(this.idUsuarioActual);
        }
    }
}
</script>

<style scoped>
.box-glass {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.box-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
}

.orden-lista-pulso {
    animation: pulso-advertencia 2s infinite;
    border-left: 8px solid #ffdd57 !important;
}

.orden-cobrar-pulso {
    animation: pulso-cobrar 1.5s infinite;
    border-left: 8px solid #ff3860 !important;
}

@keyframes pulso-cobrar {
    0% { background-color: rgba(255, 255, 255, 0.7); }
    50% { background-color: rgba(255, 56, 96, 0.12); }
    100% { background-color: rgba(255, 255, 255, 0.7); }
}

@keyframes pulso-advertencia {
    0% { background-color: rgba(255, 255, 255, 0.7); }
    50% { background-color: rgba(255, 221, 87, 0.2); }
    100% { background-color: rgba(255, 255, 255, 0.7); }
}

.btn-comanda-pulso {
    animation: pulso-comanda 1.5s infinite;
    font-weight: bold !important;
    border: 2px solid #fff !important;
}

@keyframes pulso-comanda {
    0% {
        box-shadow: 0 0 0 0 rgba(62, 142, 208, 0.7);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 0 15px rgba(62, 142, 208, 0);
        transform: scale(1.05);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(62, 142, 208, 0);
        transform: scale(1);
    }
}
</style>
