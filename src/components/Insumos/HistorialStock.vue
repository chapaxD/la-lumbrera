<template>
    <section>
        <!-- Encabezado -->
        <nav class="level mb-4">
            <div class="level-left">
                <div class="level-item">
                    <p class="title is-1 has-text-weight-bold">
                        <b-icon icon="history"
                                size="is-large"
                                type="is-primary"></b-icon>
                        Historial de Kardex
                    </p>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <b-select v-model="perPage" size="is-small">
                        <option value="5">5 por página</option>
                        <option value="10">10 por página</option>
                        <option value="15">15 por página</option>
                        <option value="20">20 por página</option>
                    </b-select>
                </div>
                <div class="level-item">
                    <div class="buttons">
                        <b-button type="is-primary"
                                  icon-left="filter-variant"
                                  @click="filtrar = !filtrar">Filtrar</b-button>
                        <b-button type="is-danger"
                                  icon-left="file-pdf-box"
                                  @click="exportarPDF">PDF</b-button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Filtros desplegables -->
        <div class="box mb-4"
             v-if="filtrar">
            <b-field grouped
                     group-multiline>
                <b-field label="Período"
                         expanded>
                    <b-datepicker placeholder="Selecciona un rango de fechas..."
                                  v-model="fechasSeleccionadas"
                                  @input="onFechasSeleccionadas"
                                  icon="calendar-today"
                                  range
                                  :max-date="hoy">
                    </b-datepicker>
                </b-field>
                <b-field label="Acciones"
                         expanded>
                    <b-button type="is-warning"
                              icon-left="close"
                              @click="limpiarFiltro"
                              :disabled="fechasSeleccionadas.length === 0">Quitar filtro</b-button>
                </b-field>
            </b-field>
        </div>

        <!-- Estadísticas -->
        <div class="columns mb-2"
             v-if="historial.length > 0">
            <div class="column">
                <div class="box has-text-centered py-3">
                    <p class="heading">Total movimientos</p>
                    <p class="title is-4">{{ historial.length }}</p>
                </div>
            </div>
            <div class="column">
                <div class="box has-text-centered py-3">
                    <p class="heading">Compras</p>
                    <p class="title is-4 has-text-success">{{ totalCompras }}</p>
                </div>
            </div>
            <div class="column">
                <div class="box has-text-centered py-3">
                    <p class="heading">Ventas</p>
                    <p class="title is-4 has-text-danger">{{ totalVentas }}</p>
                </div>
            </div>
            <div class="column">
                <div class="box has-text-centered py-3">
                    <p class="heading">Ajustes / Mermas</p>
                    <p class="title is-4 has-text-warning">{{ totalAjustes }}</p>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="box">
        <b-table :data="historial"
                 :loading="cargando"
                 :bordered="true"
                 :striped="true"
                 :hoverable="true"
                 :narrowed="true"
                 :paginated="true"
                 :per-page="perPage">
            <b-table-column field="fecha"
                            label="Fecha"
                            sortable
                            v-slot="props">
                {{ props.row.fecha | formatFecha }}
            </b-table-column>

            <b-table-column field="insumo"
                            label="Insumo"
                            sortable
                            searchable
                            v-slot="props">
                <strong>{{ props.row.insumoNombre || 'Insumo Eliminado' }}</strong>
            </b-table-column>

            <b-table-column field="tipo"
                            label="Tipo"
                            sortable
                            searchable
                            v-slot="props">
                <b-tag :type="tipoColor(props.row.tipo)">{{ props.row.tipo }}</b-tag>
            </b-table-column>

            <b-table-column field="cantidad"
                            label="Cantidad"
                            sortable
                            numeric
                            v-slot="props">
                <span
                      :class="{ 'has-text-danger': props.row.cantidad < 0, 'has-text-success': props.row.cantidad > 0, 'has-text-weight-bold': true }">
                    {{ props.row.cantidad > 0 ? '+' : '' }}{{ props.row.cantidad }}
                </span>
            </b-table-column>

            <b-table-column field="usuario"
                            label="Responsable"
                            sortable
                            searchable
                            v-slot="props">
                <b-icon icon="account"
                        size="is-small"></b-icon> {{ props.row.usuarioNombre || 'Sistema' }}
            </b-table-column>

            <template #empty>
                <div class="has-text-centered py-5 has-text-grey">
                    <b-icon icon="history"
                            size="is-large"></b-icon>
                    <p class="mt-2">No hay movimientos en el período seleccionado</p>
                </div>
            </template>
        </b-table>
        </div>
    </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'
import ReportesPdfService from '../../Servicios/ReportesPdfService'

export default {
    name: 'HistorialStock',
    data: () => ({
        historial: [],
        cargando: false,
        fechasSeleccionadas: [],
        hoy: new Date(),
        filtrar: true,
        perPage: 20
    }),

    computed: {
        totalCompras() { return this.historial.filter(h => h.tipo === 'COMPRA').length },
        totalVentas() { return this.historial.filter(h => h.tipo === 'VENTA').length },
        totalAjustes() { return this.historial.filter(h => h.tipo !== 'COMPRA' && h.tipo !== 'VENTA').length }
    },

    mounted() {
        this.obtenerHistorial()
    },
    methods: {
        onFechasSeleccionadas(fechas) {
            if (fechas.length === 2) this.obtenerHistorial();
        },
        limpiarFiltro() {
            this.fechasSeleccionadas = [];
            this.obtenerHistorial();
        },
        obtenerHistorial() {
            this.cargando = true;
            const filtros = this.fechasSeleccionadas.length === 2 ? {
                inicio: this.fechasSeleccionadas[0].toISOString().substring(0, 10),
                fin: this.fechasSeleccionadas[1].toISOString().substring(0, 10)
            } : {};
            HttpService.obtenerConDatos(filtros, "obtener_historial_stock.php")
                .then(resultado => {
                    this.historial = resultado || []
                    this.cargando = false
                })
                .catch(() => {
                    this.cargando = false
                    this.$toast({ message: 'Error al cargar el historial', type: 'is-danger' })
                })
        },
        tipoColor(tipo) {
            if (tipo === 'VENTA') return 'is-danger is-light'
            if (tipo === 'COMPRA') return 'is-success is-light'
            if (tipo === 'AJUSTE') return 'is-warning is-light'
            return 'is-dark'
        },
        exportarPDF() {
            if (this.historial.length === 0) return;
            const titulo = this.fechasSeleccionadas.length === 2
                ? `Kardex del ${this.fechasSeleccionadas[0].toLocaleDateString()} al ${this.fechasSeleccionadas[1].toLocaleDateString()}`
                : 'Bitácora de Historial de Stock (Kardex)';
            let columnas = ["Fecha", "Insumo", "Tipo Operación", "Cantidad Movida", "Responsable"];
            let filas = this.historial.map(h => [
                h.fecha,
                h.insumoNombre || 'Insumo Eliminado',
                h.tipo,
                (h.cantidad > 0 ? '+' : '') + h.cantidad,
                h.usuarioNombre || 'Sistema'
            ]);
            ReportesPdfService.generar(titulo, columnas, filas);
        }
    }
}
</script>
