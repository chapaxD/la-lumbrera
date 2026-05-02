<template>
    <b-navbar class="fondo is-primary" :style="{ backgroundColor: 'var(--color-navbar) !important' }">
        <template #brand>
            <b-navbar-item>
                <img :src="logo" alt="logo">
            </b-navbar-item>
            <!-- Selector de tema visible solo en mobile (el del #end queda oculto en hamburger) -->
            <b-navbar-item class="is-hidden-desktop" style="margin-left:auto">
                <selector-tema />
            </b-navbar-item>
        </template>
        <template #start>
            <!-- Nav para rol cocina -->
            <b-navbar-item v-if="rol === 'cocina'" tag="router-link" :to="{ path: '/cocina' }">
                <b-icon icon="silverware-fork-knife"></b-icon>
                <span>&nbsp;Pantalla Cocina</span>
            </b-navbar-item>

            <!-- Nav para rol parrillero -->
            <b-navbar-item v-if="rol === 'parrillero' && parseInt(datosLocal.usa_pantalla_parrilla) !== 0" tag="router-link" :to="{ path: '/parrilla' }">
                <b-icon icon="fire"></b-icon>
                <span>&nbsp;Pantalla Parrilla</span>
            </b-navbar-item>
            <b-navbar-item v-if="rol === 'parrillero'" tag="router-link" :to="{ path: '/registrar-despiece-parrilla' }">
                <b-icon icon="clipboard-text-outline"></b-icon>
                <span>&nbsp;Registrar despiece</span>
            </b-navbar-item>

            <!-- Nav para admin y mesero -->
            <template
                v-if="rol !== 'cocina' && rol !== 'parrillero' && $route.path !== '/cocina' && $route.path !== '/parrilla'">
                <b-navbar-item tag="router-link" :to="{ path: '/' }">
                    <b-icon icon="home"></b-icon>
                    <span></span>
                    Inicio
                </b-navbar-item>
                <b-navbar-item tag="router-link" :to="{ path: '/cocina' }" v-if="rol === 'admin' && parseInt(datosLocal.usa_pantalla_cocina) !== 0">
                    <b-icon icon="silverware-fork-knife" size="is-small"></b-icon>
                    <span>&nbsp;Cocina</span>
                </b-navbar-item>
                <b-navbar-dropdown collapsible v-model="ddParrilla" v-if="rol === 'admin'">
                    <template #label>
                        <b-icon icon="fire" size="is-small"></b-icon>
                        &nbsp;Registro parrilla
                    </template>
                    <b-navbar-item v-if="parseInt(datosLocal.usa_pantalla_parrilla) !== 0" tag="router-link" :to="{ path: '/parrilla' }" @click.native="ddParrilla = false">
                        <b-icon icon="grill" size="is-small"></b-icon>
                        <span>&nbsp;Pantalla parrilla</span>
                    </b-navbar-item>
                    <b-navbar-item tag="router-link" :to="{ path: '/registrar-despiece-parrilla' }"
                        @click.native="ddParrilla = false">
                        <b-icon icon="clipboard-text-outline" size="is-small"></b-icon>
                        <span>&nbsp;Registrar despiece</span>
                    </b-navbar-item>
                    <b-navbar-item tag="router-link" :to="{ path: '/reporte-despiece-parrilla' }"
                        @click.native="ddParrilla = false">
                        <b-icon icon="clipboard-list-outline" size="is-small"></b-icon>
                        <span>&nbsp;Reporte / control</span>
                    </b-navbar-item>
                </b-navbar-dropdown>

                <b-navbar-item tag="router-link" :to="{ path: '/realizar-orden' }">
                    <b-icon icon="order-bool-ascending-variant"></b-icon>
                    <span></span>
                    Ordenar
                </b-navbar-item>

                <!-- Reservas visible para todos los roles -->
                <b-navbar-item tag="router-link" :to="{ path: '/reservas' }">
                    <b-icon icon="calendar-clock" size="is-small"></b-icon>
                    <span>&nbsp;Reservas</span>
                </b-navbar-item>

                <!-- Dropdowns solo admin -->
                <template v-if="rol === 'admin'">

                    <!-- Inventario -->
                    <b-navbar-dropdown collapsible v-model="ddInventario">
                        <template #label>
                            <b-icon icon="package-variant" size="is-small"></b-icon>
                            &nbsp;Inventario
                        </template>
                        <b-navbar-item tag="router-link" :to="{ path: '/insumos' }"
                            @click.native="ddInventario = false">
                            <b-icon icon="food-apple" size="is-small"></b-icon>
                            <span>&nbsp;Insumos</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/categorias' }"
                            @click.native="ddInventario = false">
                            <b-icon icon="archive-outline" size="is-small"></b-icon>
                            <span>&nbsp;Categorías</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/compras' }"
                            @click.native="ddInventario = false">
                            <b-icon icon="truck-fast" size="is-small"></b-icon>
                            <span>&nbsp;Reabastecer</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/historial-stock' }"
                            @click.native="ddInventario = false">
                            <b-icon icon="history" size="is-small"></b-icon>
                            <span>&nbsp;Kardex</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/plantillas-combo' }"
                            @click.native="ddInventario = false">
                            <b-icon icon="food-variant" size="is-small"></b-icon>
                            <span>&nbsp;Menús / combos</span>
                        </b-navbar-item>
                    </b-navbar-dropdown>

                    <!-- Ventas -->
                    <b-navbar-dropdown collapsible v-model="ddVentas">
                        <template #label>
                            <b-icon icon="cash-register" size="is-small"></b-icon>
                            &nbsp;Ventas
                        </template>
                        <b-navbar-item tag="router-link" :to="{ path: '/reporte-ventas' }"
                            @click.native="ddVentas = false">
                            <b-icon icon="cash-register" size="is-small"></b-icon>
                            <span>&nbsp;Ventas por día</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/cancelaciones' }"
                            @click.native="ddVentas = false">
                            <b-icon icon="cancel" size="is-small"></b-icon>
                            <span>&nbsp;Cancelaciones</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/factura' }" @click.native="ddVentas = false">
                            <b-icon icon="file-document-outline" size="is-small"></b-icon>
                            <span>&nbsp;Nota de Venta</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/historial-facturas' }"
                            @click.native="ddVentas = false">
                            <b-icon icon="file-document-multiple-outline" size="is-small"></b-icon>
                            <span>&nbsp;Historial Notas</span>
                        </b-navbar-item>
                        <hr class="navbar-divider">
                        <b-navbar-item tag="router-link" :to="{ path: '/clientes' }" @click.native="ddVentas = false">
                            <b-icon icon="account-multiple" size="is-small"></b-icon>
                            <span>&nbsp;Clientes</span>
                        </b-navbar-item>
                    </b-navbar-dropdown>

                    <!-- Operación -->
                    <b-navbar-dropdown collapsible v-model="ddOperacion">
                        <template #label>
                            <b-icon icon="calendar-check" size="is-small"></b-icon>
                            &nbsp;Operación
                        </template>
                        <b-navbar-item tag="router-link" :to="{ path: '/menu-dia' }"
                            @click.native="ddOperacion = false">
                            <b-icon icon="calendar-check" size="is-small"></b-icon>
                            <span>&nbsp;Menú del Día</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/reservas' }"
                            @click.native="ddOperacion = false">
                            <b-icon icon="calendar-clock" size="is-small"></b-icon>
                            <span>&nbsp;Reservas</span>
                        </b-navbar-item>
                    </b-navbar-dropdown>

                    <!-- Admin -->
                    <b-navbar-dropdown collapsible v-model="ddAdmin">
                        <template #label>
                            <b-icon icon="cogs" size="is-small"></b-icon>
                            &nbsp;Admin
                        </template>
                        <b-navbar-item tag="router-link" :to="{ path: '/usuarios' }" @click.native="ddAdmin = false">
                            <b-icon icon="account-group" size="is-small"></b-icon>
                            <span>&nbsp;Usuarios</span>
                        </b-navbar-item>
                        <b-navbar-item tag="router-link" :to="{ path: '/historial-cajas' }"
                            @click.native="ddAdmin = false">
                            <b-icon icon="currency-usd" size="is-small"></b-icon>
                            <span>&nbsp;Cajas</span>
                        </b-navbar-item>
                        <hr class="navbar-divider">
                        <b-navbar-item tag="router-link" :to="{ path: '/configurar' }" @click.native="ddAdmin = false">
                            <b-icon icon="application-cog-outline" size="is-small"></b-icon>
                            <span>&nbsp;Configurar</span>
                        </b-navbar-item>
                    </b-navbar-dropdown>

                </template>
            </template>
        </template>

        <template #end>
            <b-navbar-item tag="div">
                <div class="is-flex is-align-items-center" style="gap: 8px;">
                    <!-- Campana admin: alertas stock + reportes cocina -->
                    <b-dropdown position="is-bottom-left" aria-role="menu" style="margin-right: 10px;"
                        v-if="rol === 'admin'" @active-change="onBellOpen">
                        <template #trigger>
                            <a class="button is-danger is-light">
                                <b-icon icon="bell"></b-icon>
                                <span v-if="totalAlertas > 0" class="tag is-danger is-rounded has-text-weight-bold"
                                    style="margin-left: 5px">
                                    {{ totalAlertas }}
                                </span>
                            </a>
                        </template>

                        <!-- Alertas de stock bajo -->
                        <b-dropdown-item custom aria-role="menuitem">
                            <div class="is-flex is-align-items-center is-justify-content-space-between"
                                style="min-width:280px">
                                <p class="has-text-weight-bold has-text-grey is-size-7 mb-0">STOCK BAJO</p>
                                <b-button v-if="alertas.length > 0" size="is-small" type="is-danger is-light"
                                    icon-left="printer" @click.stop="imprimirStockBajo">
                                    Imprimir
                                </b-button>
                            </div>
                        </b-dropdown-item>
                        <b-dropdown-item custom aria-role="menuitem" v-if="alertas.length === 0">
                            <span class="has-text-grey is-size-7">Sin alertas de stock</span>
                        </b-dropdown-item>
                        <b-dropdown-item v-for="alerta in alertas" :key="'stock-' + alerta.id" aria-role="menuitem"
                            has-link>
                            <router-link :to="'/editar-insumo/' + alerta.id" style="min-width: 280px;">
                                <div class="media">
                                    <div class="media-left">
                                        <b-icon icon="alert" type="is-danger"></b-icon>
                                    </div>
                                    <div class="media-content">
                                        <p><strong>{{ alerta.nombre }}</strong></p>
                                        <p class="is-size-7 has-text-grey">Quedan: {{ alerta.stock }} | Mín: {{
                                            alerta.stockMinimo }}</p>
                                    </div>
                                </div>
                            </router-link>
                        </b-dropdown-item>

                        <hr class="dropdown-divider" v-if="reportesCocina.length > 0">

                        <!-- Reportes de cocina -->
                        <template v-if="reportesCocina.length > 0 && parseInt(datosLocal.usa_pantalla_cocina) !== 0">
                            <b-dropdown-item custom aria-role="menuitem">
                                <div class="is-flex is-align-items-center is-justify-content-space-between"
                                    style="min-width:280px">
                                    <p class="has-text-weight-bold has-text-grey is-size-7 mb-0">REPORTES COCINA</p>
                                    <b-button size="is-small" type="is-warning is-light" icon-left="printer"
                                        @click.stop="imprimirReportesCocina">
                                        Imprimir
                                    </b-button>
                                </div>
                            </b-dropdown-item>
                            <b-dropdown-item v-for="reporte in reportesCocina" :key="'cocina-' + reporte.id"
                                aria-role="menuitem" custom>
                                <div class="media" style="min-width: 280px;">
                                    <div class="media-left">
                                        <b-icon icon="chef-hat" type="is-warning"></b-icon>
                                    </div>
                                    <div class="media-content">
                                        <p><strong>{{ reporte.nombreInsumo }}</strong>
                                            <b-tag size="is-small" :type="tipoReporteColor(reporte.tipo)"
                                                class="ml-1">{{ reporte.tipo }}</b-tag>
                                        </p>
                                        <p class="is-size-7 has-text-grey" v-if="reporte.nota">{{ reporte.nota }}</p>
                                        <p class="is-size-7 has-text-grey">Por: {{ reporte.usuarioNombre }}</p>
                                    </div>
                                    <div class="media-right">
                                        <b-button size="is-small" type="is-success is-light" icon-left="check"
                                            @click.stop="resolverReporte(reporte.id)">
                                        </b-button>
                                    </div>
                                </div>
                            </b-dropdown-item>
                        </template>
                    </b-dropdown>

                    <selector-tema />
                    <a class="button is-warning" @click="irAPerfil">
                        {{ nombreUsuario }}
                    </a>
                    <a class="button is-light" @click="salir">
                        Salir
                    </a>
                </div>
            </b-navbar-item>
        </template>
    </b-navbar>
</template>
<script>
import HttpService from '../Servicios/HttpService'
import Utiles from '../Servicios/Utiles'
import ReportesPdfService from '../Servicios/ReportesPdfService'
import SelectorTema from './Configuracion/SelectorTema'

export default ({
    name: 'Encabezado',
    components: { SelectorTema },
    data: () => ({
        expandOnHover: false,
        expandWithDelay: false,
        mobile: "reduce",
        reduce: false,
        datosLocal: {},
        nombreUsuario: "",
        logo: null,
        alertas: [],
        reportesCocina: [],
        rol: "",
        dropdownActivo: false,
        ddInventario: false,
        ddParrilla: false,
        ddVentas: false,
        ddOperacion: false,
        ddAdmin: false,
        timer: null
    }),

    computed: {
        totalAlertas() {
            return this.alertas.length + this.reportesCocina.length
        }
    },

    mounted() {
        this.obtenerDatos()
        this.obtenerAlertas()
        this.nombreUsuario = localStorage.getItem('nombreUsuario')
        this.rol = localStorage.getItem('rol')
        this.logo = Utiles.generarUrlImagen(this.datosLocal.logo)
        if (this.rol === 'admin') {
            this.obtenerReportesCocina()
            this.iniciarPolling()
            document.addEventListener('visibilitychange', this.manejarVisibilidad)
        }
    },

    beforeDestroy() {
        if (this.rol === 'admin') {
            this.detenerPolling()
            document.removeEventListener('visibilitychange', this.manejarVisibilidad)
        }
    },

    methods: {
        iniciarPolling() {
            if (!this.timer) {
                this.timer = setInterval(this.obtenerReportesCocina, 30000)
            }
        },
        detenerPolling() {
            if (this.timer) {
                clearInterval(this.timer)
                this.timer = null
            }
        },
        manejarVisibilidad() {
            if (document.hidden) {
                this.detenerPolling()
            } else {
                this.obtenerReportesCocina()
                this.iniciarPolling()
            }
        },
        obtenerAlertas() {
            HttpService.obtener("obtener_alertas_stock.php")
                .then(resultado => {
                    this.alertas = resultado || []
                })
        },

        obtenerReportesCocina() {
            HttpService.obtener("obtener_reportes_cocina.php")
                .then(resultado => {
                    this.reportesCocina = resultado || []
                })
        },

        resolverReporte(id) {
            HttpService.registrar({ id }, "resolver_reporte_cocina.php")
                .then(() => {
                    this.reportesCocina = this.reportesCocina.filter(r => r.id !== id)
                })
        },

        tipoReporteColor(tipo) {
            const colores = {
                FALTANTE: 'is-danger is-light',
                BAJO_STOCK: 'is-warning is-light',
                VENCIDO: 'is-dark is-light',
                OTRO: 'is-info is-light'
            }
            return colores[tipo] || 'is-light'
        },

        imprimirStockBajo() {
            const columnas = ['Código', 'Nombre', 'Stock actual', 'Stock mínimo']
            const filas = this.alertas.map(a => [
                a.codigo || '-',
                a.nombre,
                a.stock + ' uds',
                a.stockMinimo + ' uds'
            ])
            ReportesPdfService.generar('Alerta de Stock Bajo', columnas, filas, `Total productos en alerta: ${this.alertas.length}`)
        },

        imprimirReportesCocina() {
            const columnas = ['Insumo', 'Tipo', 'Nota', 'Reportado por', 'Fecha']
            const filas = this.reportesCocina.map(r => [
                r.nombreInsumo || '-',
                r.tipo || '-',
                r.nota || '-',
                r.usuarioNombre || '-',
                r.fecha || '-'
            ])
            ReportesPdfService.generar('Reportes de Cocina Pendientes', columnas, filas, `Total reportes pendientes: ${this.reportesCocina.length}`)
        },

        onBellOpen(isOpen) {
            if (isOpen) {
                this.obtenerAlertas()
                this.obtenerReportesCocina()
            }
        },

        irAPerfil() {
            this.$router.push({
                name: "Perfil",
            })
        },

        obtenerDatos() {
            HttpService.obtener("obtener_datos_local.php")
                .then(resultado => {
                    this.datosLocal = resultado
                    this.logo = Utiles.generarUrlImagen(this.datosLocal.logo)
                })
        },

        salir() {
            this.$buefy.dialog.confirm({
                title: '¿Salir de la aplicación?',
                message: 'Deseas salir',
                confirmText: 'Sí, salir',
                cancelText: 'No',
                type: 'is-danger',
                hasIcon: true,
                onConfirm: () => {
                    this.$emit("cerrar", false)
                    localStorage.removeItem('jwt_token')
                    localStorage.removeItem('nombreUsuario')
                    localStorage.removeItem('idUsuario')
                    localStorage.removeItem('rol')
                    this.$toast('Hasta pronto')
                }
            })
        }
    }
})
</script>
