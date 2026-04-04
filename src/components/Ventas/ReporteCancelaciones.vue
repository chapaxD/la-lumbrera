<template>
    <section class="section">
        <!-- Encabezado -->
        <nav class="level mb-4">
            <div class="level-left">
                <div class="level-item">
                    <p class="title is-1 has-text-weight-bold">
                        <b-icon icon="cancel" size="is-large" type="is-primary"></b-icon>
                        Reporte de Cancelaciones
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
                    <b-button type="is-danger" icon-left="file-pdf-box" @click="exportarPDF" :disabled="cancelaciones.length === 0">
                        Exportar PDF
                    </b-button>
                </div>
            </div>
        </nav>

        <!-- Filtros -->
        <div class="box mb-4">
            <b-field grouped group-multiline>
                <b-field label="Desde" expanded>
                    <b-datepicker v-model="filtros.desde" placeholder="Fecha inicio" icon="calendar" :max-date="hoy" clearable></b-datepicker>
                </b-field>
                <b-field label="Hasta" expanded>
                    <b-datepicker v-model="filtros.hasta" placeholder="Fecha fin" icon="calendar" :max-date="hoy" clearable></b-datepicker>
                </b-field>
                <b-field label="Usuario" expanded>
                    <b-select v-model="filtros.idUsuario" expanded>
                        <option value="">Todos los usuarios</option>
                        <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.nombre }}</option>
                    </b-select>
                </b-field>
                <b-field label="Acciones" expanded>
                    <div class="buttons">
                        <b-button type="is-primary" icon-left="magnify" @click="cargar" :loading="cargando">Buscar</b-button>
                        <b-button type="is-light" icon-left="close" @click="limpiar">Limpiar</b-button>
                    </div>
                </b-field>
            </b-field>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="columns is-multiline mb-4" v-if="cancelaciones.length > 0">
            <div class="column is-3">
                <div class="box has-text-centered py-3">
                    <p class="is-size-7 has-text-grey mb-1">Total cancelaciones</p>
                    <p class="title is-3 has-text-danger mb-0">{{ cancelaciones.length }}</p>
                </div>
            </div>
            <div class="column is-3">
                <div class="box has-text-centered py-3">
                    <p class="is-size-7 has-text-grey mb-1">Órdenes locales</p>
                    <p class="title is-3 has-text-warning mb-0">{{ porTipo('LOCAL') }}</p>
                </div>
            </div>
            <div class="column is-3">
                <div class="box has-text-centered py-3">
                    <p class="is-size-7 has-text-grey mb-1">Deliveries</p>
                    <p class="title is-3 has-text-info mb-0">{{ porTipo('DELIVERY') }}</p>
                </div>
            </div>
            <div class="column is-3">
                <div class="box has-text-centered py-3">
                    <p class="is-size-7 has-text-grey mb-1">Con motivo registrado</p>
                    <p class="title is-3 has-text-success mb-0">{{ conMotivo }}</p>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="box">
            <b-table
                :data="cancelaciones"
                :loading="cargando"
                :paginated="true"
                :per-page="perPage"
                :current-page.sync="currentPage"
                default-sort="fecha"
                default-sort-direction="desc"
                :bordered="true"
                :striped="true"
                :hoverable="true"
                empty-label="Sin cancelaciones en el período">

                <b-table-column field="fecha" label="Fecha / Hora" sortable v-slot="props">
                    {{ formatearFecha(props.row.fecha) }}
                </b-table-column>

                <b-table-column field="tipo" label="Tipo" sortable v-slot="props">
                    <b-tag :type="props.row.tipo === 'LOCAL' ? 'is-warning' : 'is-info'">
                        <b-icon :icon="props.row.tipo === 'LOCAL' ? 'table-chair' : 'truck-delivery'" size="is-small" class="mr-1"></b-icon>
                        {{ props.row.tipo === 'LOCAL' ? 'Mesa' : 'Delivery' }} #{{ props.row.referencia }}
                    </b-tag>
                </b-table-column>

                <b-table-column field="usuario" label="Cancelado por" sortable v-slot="props">
                    <b-icon icon="account" size="is-small" class="mr-1"></b-icon>
                    {{ props.row.usuario }}
                </b-table-column>

                <b-table-column field="motivo" label="Motivo" v-slot="props">
                    <span v-if="props.row.motivo && props.row.motivo.trim()" class="has-text-dark">
                        <b-icon icon="note-text-outline" size="is-small" class="mr-1"></b-icon>
                        {{ props.row.motivo }}
                    </span>
                    <span v-else class="has-text-grey is-size-7">Sin motivo registrado</span>
                </b-table-column>
            </b-table>
        </div>

        <!-- Ranking de usuarios que más cancelan (si hay datos) -->
        <div class="box mt-4" v-if="rankingUsuarios.length > 0">
            <p class="title is-5 mb-3">
                <b-icon icon="podium" size="is-small"></b-icon>
                Cancelaciones por usuario
            </p>
            <div class="columns is-multiline">
                <div class="column is-narrow" v-for="(item, idx) in rankingUsuarios" :key="idx">
                    <div class="box py-2 px-4 has-text-centered" style="min-width:130px">
                        <p class="is-size-7 has-text-grey mb-1">{{ item.usuario }}</p>
                        <p class="title is-4 mb-0" :class="idx === 0 ? 'has-text-danger' : 'has-text-grey'">{{ item.total }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'
import ReportesPdfService from '../../Servicios/ReportesPdfService'

export default {
    name: 'ReporteCancelaciones',
    data() {
        return {
            cargando: false,
            cancelaciones: [],
            usuarios: [],
            hoy: new Date(),
            perPage: 10,
            currentPage: 1,
            filtros: {
                desde: null,
                hasta: null,
                idUsuario: ''
            }
        }
    },
    computed: {
        conMotivo() {
            return this.cancelaciones.filter(c => c.motivo && c.motivo.trim()).length
        },
        rankingUsuarios() {
            const mapa = {}
            this.cancelaciones.forEach(c => {
                mapa[c.usuario] = (mapa[c.usuario] || 0) + 1
            })
            return Object.entries(mapa)
                .map(([usuario, total]) => ({ usuario, total }))
                .sort((a, b) => b.total - a.total)
        }
    },
    mounted() {
        this.cargarUsuarios()
        this.cargar()
    },
    methods: {
        async cargarUsuarios() {
            const resultado = await HttpService.obtener('obtener_usuarios.php')
            this.usuarios = resultado || []
        },
        async cargar() {
            this.cargando = true
            const payload = {
                desde: this.filtros.desde ? this.filtros.desde.toISOString().slice(0, 10) : '',
                hasta: this.filtros.hasta ? this.filtros.hasta.toISOString().slice(0, 10) : '',
                idUsuario: this.filtros.idUsuario || ''
            }
            const resultado = await HttpService.registrar(payload, 'obtener_cancelaciones.php')
            this.cancelaciones = resultado || []
            this.cargando = false
        },
        limpiar() {
            this.filtros = { desde: null, hasta: null, idUsuario: '' }
            this.cargar()
        },
        porTipo(tipo) {
            return this.cancelaciones.filter(c => c.tipo === tipo).length
        },
        formatearFecha(f) {
            if (!f) return ''
            const s = f.replace('T', ' ').substring(0, 16)
            const [fecha, hora] = s.split(' ')
            if (!fecha) return f
            const [anio, mes, dia] = fecha.split('-')
            return `${dia}-${mes}-${anio}${hora ? ' ' + hora : ''}`
        },
        exportarPDF() {
            const columnas = ['Fecha / Hora', 'Tipo', 'Referencia', 'Cancelado por', 'Motivo']
            const filas = this.cancelaciones.map(c => [
                this.formatearFecha(c.fecha),
                c.tipo,
                (c.tipo === 'LOCAL' ? 'Mesa' : 'Delivery') + ' #' + c.referencia,
                c.usuario,
                c.motivo || 'Sin motivo'
            ])
            ReportesPdfService.generar('Reporte de Cancelaciones', columnas, filas,
                `Total: ${this.cancelaciones.length}  |  Mesas: ${this.porTipo('LOCAL')}  |  Deliveries: ${this.porTipo('DELIVERY')}`)
        }
    }
}
</script>
