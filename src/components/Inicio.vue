<template>
    <section>
        <div class="columns is-multiline mb-4">
            <!-- Skeletons para carga inicial -->
            <template v-if="cargando">
                <div class="column is-6-tablet is-4-desktop is-3-widescreen is-12-mobile" v-for="i in 4"
                    :key="'skel-card-' + i">
                    <div class="card is-card-widget h-100 glass-card">
                        <div class="card-content widget-card-body">
                            <div class="widget-icon-bubble">
                                <b-skeleton circle width="28px" height="28px" animated></b-skeleton>
                            </div>
                            <p class="is-size-7 mt-3 mb-1"><b-skeleton width="60%" animated></b-skeleton></p>
                            <h3 class="title is-3 mb-1"><b-skeleton width="80%" height="36px" animated></b-skeleton>
                            </h3>
                            <div class="mt-auto"><b-skeleton width="40%" animated></b-skeleton></div>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="column is-6-tablet is-4-desktop is-3-widescreen is-12-mobile"
                    v-for="(carta, index) in cartas" :key="index">
                    <div class="card is-card-widget h-100 glass-card transition-card">
                        <div class="card-content widget-card-body">
                            <div class="widget-icon-bubble">
                                <b-icon :icon="carta.icono" size="is-medium" :class="carta.colorTexto"></b-icon>
                            </div>
                            <p class="is-size-7 is-uppercase has-text-weight-semibold has-text-grey mt-3 mb-1">{{
                                carta.encabezado }}</p>
                            <h3 class="title is-3 has-text-weight-bold mb-1">{{ carta.total }}</h3>
                            <div class="is-flex is-align-items-center mt-auto">
                                <p class="is-size-7 has-text-grey mb-0">{{ carta.titulo }}</p>
                                <b-button tag="router-link" type="is-ghost" size="is-small" :to="{ path: carta.ruta }"
                                    class="ml-auto px-0">
                                    <b-icon icon="arrow-right-circle" size="is-small"></b-icon>
                                </b-button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="columns is-multiline">
            <div class="column is-one-third">
                <div class="box">
                    <p class="title is-4 has-text-grey ">
                        <b-icon icon="clock-outline"></b-icon>
                        Ventas por hora
                        <span class="tag is-primary is-large is-pulled-right"> ${{ totalVentasHora }}</span>

                    </p>
                    <b-field label="Selecciona un periodo de tiempo">
                        <b-datepicker placeholder="Click para seleccionar..." size="is-small" v-model="periodoHoras"
                            @input="busquedaAvanzada('hora')" range>
                        </b-datepicker>
                    </b-field>
                    <div id="contenedor-hora">
                        <canvas id="grafica-hora"></canvas>
                    </div>
                </div>
            </div>
            <div class="column is-one-third">
                <div class="box">
                    <p class="title is-4 has-text-grey ">
                        <b-icon icon="account"></b-icon>
                        Ventas de usuarios
                        <span class="tag is-primary is-large is-pulled-right"> ${{ totalVentasUsuarios }}</span>
                    </p>
                    <b-field label="Selecciona un periodo de tiempo">
                        <b-datepicker placeholder="Click para seleccionar..." size="is-small" v-model="periodoUsuarios"
                            @input="busquedaAvanzada('usuario')" range>
                        </b-datepicker>
                    </b-field>
                    <div id="contenedor-usuarios">
                        <canvas id="grafica-usuarios"></canvas>
                    </div>
                </div>
            </div>
            <div class="column is-one-third">
                <div class="box">
                    <p class="title is-4 has-text-grey ">
                        <b-icon icon="calendar-week"></b-icon>
                        Ventas de la semana
                        <span class="tag is-primary is-large is-pulled-right"> ${{ totalVentasSemana }}</span>
                    </p>

                    <div id="contenedor-semana">
                        <canvas id="grafica-semana"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-5 glass-card">
            <div class="card-content">
                <div class="mb-4">
                    <div class="is-flex is-flex-wrap-wrap is-align-items-center mb-1" style="gap: 0.5rem">
                        <p class="title is-4 has-text-weight-bold mb-0">
                            <b-icon icon="calendar-month"></b-icon>
                            Ventas por año
                        </p>
                        <span class="tag is-primary is-medium has-text-weight-bold ml-auto">Total:
                            ${{ totalVentasMeses }}</span>
                        <b-select size="is-small" v-model="anioSeleccionado" @change.native="busquedaAvanzada('mes')">
                            <option v-for="(anio, index) in listaAnios" :key="index" :value="anio">{{ anio }}</option>
                        </b-select>
                    </div>
                    <p class="has-text-grey is-size-6">Resumen mensual de ingresos</p>
                </div>
                <div id="contenedor-mes" class="grafica-anual">
                    <canvas id="grafica-mes"></canvas>
                </div>
            </div>
        </div>
        <div class="columns is-multiline" v-if="alertasStock.length > 0">
            <div class="column is-12">
                <div class="box" style="border-top: 4px solid #f14668;">
                    <div class="title is-4 has-text-danger">
                        <b-icon icon="alert-decagram"></b-icon>
                        Insumos con bajo stock
                    </div>
                    <b-table :data="alertasStock" :bordered="true" :striped="true">
                        <b-table-column field="codigo" label="Código" v-slot="props">{{ props.row.codigo
                        }}</b-table-column>
                        <b-table-column field="nombre" label="Nombre" v-slot="props">{{ props.row.nombre
                        }}</b-table-column>
                        <b-table-column field="stock" label="Stock actual" v-slot="props">
                            <b-tag type="is-danger" size="is-medium">{{ props.row.stock }}</b-tag>
                        </b-table-column>
                        <b-table-column field="stockMinimo" label="Stock mínimo" v-slot="props">{{ props.row.stockMinimo
                        }}</b-table-column>
                        <b-table-column field="acciones" label="" v-slot="props">
                            <b-button tag="router-link" :to="'/editar-insumo/' + props.row.id" type="is-primary"
                                size="is-small" icon-left="pen">Editar y reabastecer</b-button>
                        </b-table-column>
                    </b-table>
                </div>
            </div>
        </div>
        <div class="columns is-multiline">
            <div class="column is-6">
                <div class="box">
                    <div class="title is-4 has-text-grey ">
                        <b-icon icon="food-fork-drink"></b-icon>
                        Insumos más vendidos

                        <b-field class="is-pulled-right">
                            <b-select v-model="limiteSeleccionado" @change.native="busquedaAvanzada('limite')" expanded>
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </b-select>
                        </b-field>
                    </div>

                    <b-table :data="insumosMasVendidos" :bordered="true" :striped="true">

                        <b-table-column field="icono" label="" v-slot="props">
                            <b-icon icon="noodles" size="is-small" type="is-info" v-if="props.row.tipo === 'PLATILLO'">
                            </b-icon>

                            <b-icon icon="cup" size="is-small" type="is-success" v-if="props.row.tipo === 'BEBIDA'">
                            </b-icon>
                        </b-table-column>
                        <b-table-column field="nombre" label="Nombre" v-slot="props">
                            {{ props.row.nombre }}
                        </b-table-column>
                        <b-table-column field="categoria" label="Categoria" v-slot="props">
                            {{ props.row.categoria }}
                        </b-table-column>
                        <b-table-column field="total" label="Total" v-slot="props">
                            ${{ props.row.total }}
                        </b-table-column>
                        <b-table-column field="progreso" label="Progreso" v-slot="props">
                            <b-progress :value="props.row.progreso" show-value format="percent" :type="{
                                'is-success': props.row.progreso >= 90,
                                'is-info': props.row.progreso >= 70 && props.row.progreso < 90,
                                'is-danger': props.row.progreso < 70
                            }">
                            </b-progress>
                        </b-table-column>
                    </b-table>
                </div>
            </div>
            <div class="column is-6">
                <div class="box">

                    <p class="title is-4 has-text-grey ">
                        <b-icon icon="table-furniture"></b-icon>
                        Mesas más ocupadas
                        <b-field class="is-pulled-right">
                            <b-select v-model="limiteSeleccionado" @change.native="busquedaAvanzada('limite')" expanded>
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </b-select>
                        </b-field>
                    </p>
                    <b-table :data="totalesPorMesa" :bordered="true" :striped="true">
                        <b-table-column field="idMesa" label="Mesa" v-slot="props">
                            Mesa #{{ props.row.idMesa }}
                        </b-table-column>
                        <b-table-column field="total" label="Total" v-slot="props">
                            ${{ props.row.total }}
                        </b-table-column>
                        <b-table-column field="progreso" label="Progreso" v-slot="props">
                            <b-progress :value="props.row.progreso" show-value format="percent" :type="{
                                'is-success': props.row.progreso >= 90,
                                'is-info': props.row.progreso >= 70 && props.row.progreso < 90,
                                'is-danger': props.row.progreso < 70
                            }">
                            </b-progress>
                        </b-table-column>
                    </b-table>
                </div>
            </div>
        </div>

    </section>
</template>
<script>
import HttpService from '../Servicios/HttpService'
import Utiles from '../Servicios/Utiles'

export default ({
    name: "Inicio",
    data: () => ({
        ventasSemana: [],
        ventasHora: [],
        ventasMeses: [],
        ventasUsuarios: [],
        insumosMasVendidos: [],
        totalesPorMesa: [],
        alertasStock: [],
        resultadoCartas: {},
        cargando: false,
        tipoGrafica: "line",
        cartas: [],
        totalVentasHora: 0,
        totalVentasSemana: 0,
        totalVentasUsuarios: 0,
        totalVentasMeses: 0,
        periodoHoras: [],
        periodoUsuarios: [],
        anioSeleccionado: new Date().getFullYear(),
        listaAnios: [],
        limiteSeleccionado: 5,
        filtros: {
            hora: { inicio: "", fin: "" },
            usuarios: { inicio: "", fin: "" }
        },
        timer: null,
        rol: ""
    }),

    mounted() {
        this.rol = localStorage.getItem('rol')
        this.filtros.anio = this.anioSeleccionado
        this.filtros.limite = this.limiteSeleccionado
        this.obtenerDatos()
        this.llenarListaAnios()
        this.iniciarPolling()
        document.addEventListener('visibilitychange', this.manejarVisibilidad)
    },

    beforeDestroy() {
        this.detenerPolling()
        document.removeEventListener('visibilitychange', this.manejarVisibilidad)
    },

    methods: {
        iniciarPolling() {
            if (!this.timer) {
                this.timer = setInterval(() => {
                    this.obtenerDatos(true);
                }, 30000);
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
                this.obtenerDatos(true);
                this.iniciarPolling();
            }
        },

        calcularProgreso(arreglo) {

            let mayor = (arreglo[0]) ? arreglo[0].total : 0
            arreglo.forEach(elemento => {
                elemento.progreso = parseInt(elemento.total * 100 / mayor)
            });
            return arreglo


        },

        busquedaAvanzada(tipo,) {
            switch (tipo) {
                case "hora":
                    this.filtros.hora = {
                        inicio: this.periodoHoras[0].toISOString().substring(0, 10),
                        fin: this.periodoHoras[1].toISOString().substring(0, 10)
                    }
                    break

                case "usuario":
                    this.filtros.usuarios = {
                        inicio: this.periodoUsuarios[0].toISOString().substring(0, 10),
                        fin: this.periodoUsuarios[1].toISOString().substring(0, 10)
                    }
                    break

                case "mes":
                    this.filtros.anio = this.anioSeleccionado
                    break
                case "limite":
                    this.filtros.limite = this.limiteSeleccionado
            }

            this.obtenerDatos()
        },

        llenarListaAnios() {
            for (let i = 2015; i <= this.anioSeleccionado; i++) {
                this.listaAnios.push(i)
            }
        },

        obtenerDatos(silencioso = false) {
            if (!silencioso) this.cargando = true
            HttpService.obtenerConDatos(this.filtros, "inicio.php")
                .then(resultado => {
                    this.ventasSemana = Utiles.cambiarDiaSemana(resultado.ventasDiasSemana)
                    this.ventasHora = resultado.ventasHora
                    this.ventasMeses = Utiles.cambiarNumeroANombreMes(resultado.ventasMeses)
                    this.ventasUsuarios = resultado.ventasUsuario
                    this.resultadoCartas = resultado.cartas
                    this.insumosMasVendidos = this.calcularProgreso(resultado.insumosMasVendidos)
                    this.totalesPorMesa = this.calcularProgreso(resultado.totalesPorMesa)
                    this.alertasStock = resultado.alertasStock || []

                    this.totalVentasHora = Utiles.calcularTotales(this.ventasHora)
                    this.totalVentasSemana = Utiles.calcularTotales(this.ventasSemana)
                    this.totalVentasUsuarios = Utiles.calcularTotales(this.ventasUsuarios)
                    this.totalVentasMeses = Utiles.calcularTotales(this.ventasMeses)

                    Utiles.generarGrafica(this.ventasSemana, "#contenedor-semana", "#grafica-semana", "grafica-semana")
                    Utiles.generarGrafica(this.ventasHora, "#contenedor-hora", "#grafica-hora", "grafica-hora")
                    Utiles.generarGrafica(this.ventasMeses, "#contenedor-mes", "#grafica-mes", "grafica-mes", { maintainAspectRatio: false, height: 240, tipo: 'bar' })
                    Utiles.generarGrafica(this.ventasUsuarios, "#contenedor-usuarios", "#grafica-usuarios", "grafica-usuarios")
                    this.cartas = [
                        {
                            encabezado: "Ventas del día",
                            titulo: "Ventas hoy",
                            total: this.resultadoCartas.totalVentasDia > 0 ? "$" + this.resultadoCartas.totalVentasDia : "$0 — ¡Sin ventas aún!",
                            icono: "cart-outline",
                            colorTexto: "has-text-info",
                            ruta: "/reporte-ventas"
                        },
                        {
                            encabezado: "Estado mesas",
                            titulo: "Mesas ocupadas",
                            total: this.resultadoCartas.numeroMesasOcupadas,
                            icono: "table-furniture",
                            colorTexto: "has-text-success",
                            ruta: "/realizar-orden"
                        },
                        {
                            encabezado: "Usuarios registrados",
                            titulo: "Usuarios",
                            total: this.resultadoCartas.numeroUsuarios,
                            icono: "account",
                            colorTexto: "has-text-danger",
                            ruta: "/usuarios"
                        },
                        {
                            encabezado: "Insumos registrados",
                            titulo: "Insumos",
                            total: this.resultadoCartas.numeroInsumos,
                            icono: "food-fork-drink",
                            colorTexto: "has-text-warning",
                            ruta: "/insumos"
                        },
                        {
                            encabezado: "Total ventas",
                            titulo: "Ventas acumuladas",
                            total: "$" + this.resultadoCartas.totalVentas,
                            icono: "trending-up",
                            colorTexto: "has-text-primary",
                            ruta: "/reporte-ventas"
                        },
                        {
                            encabezado: "Tickets del día",
                            titulo: "Órdenes hoy",
                            total: this.resultadoCartas.cantidadVentasDia > 0 ? this.resultadoCartas.cantidadVentasDia : "0 hoy",
                            icono: "receipt",
                            colorTexto: "has-text-success",
                            ruta: "/reporte-ventas"
                        },
                        {
                            encabezado: "Ticket promedio",
                            titulo: "Promedio por orden hoy",
                            total: this.resultadoCartas.ticketPromedio > 0 ? "$" + this.resultadoCartas.ticketPromedio : "Sin ventas hoy",
                            icono: "cash-multiple",
                            colorTexto: "has-text-warning",
                            ruta: "/reporte-ventas"
                        },
                    ]

                    // Filtrar cartas según el rol (solo admin ve todas)
                    if (this.rol !== 'admin') {
                        this.cartas = this.cartas.filter(carta =>
                            carta.ruta === '/realizar-orden' ||
                            (carta.ruta === '/reporte-ventas' && carta.titulo === 'Ventas hoy')
                        )
                    }
                    if (!silencioso) this.cargando = false
                })
                .catch(err => {
                    if (!silencioso) {
                        this.cargando = false
                        this.$toast({
                            message: 'Error al cargar los datos. Verifica que el servidor esté activo.',
                            type: 'is-danger'
                        })
                    }
                    console.error('Error en obtenerDatos:', err)
                })
        },


    }
})
</script>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    border-radius: 16px;
}

/* Burbuja de ícono del widget - fondo neutro suave */
.widget-icon-bubble {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.05);
}

/* Layout interno de la tarjeta */
.widget-card-body {
    display: flex;
    flex-direction: column;
}

/* Backgrounds suaves para iconos */
.bg-info {
    background: rgba(32, 156, 238, 0.12);
}

.bg-success {
    background: rgba(72, 199, 142, 0.12);
}

.bg-danger {
    background: rgba(241, 70, 104, 0.12);
}

.bg-warning {
    background: rgba(255, 221, 87, 0.15);
}

.bg-primary {
    background: rgba(0, 209, 178, 0.12);
}

.transition-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.transition-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
}

.grafica-anual {
    height: 300px;
    position: relative;
}
</style>
