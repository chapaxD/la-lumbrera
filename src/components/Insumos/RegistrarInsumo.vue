<template>
    <section>
        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
        <div style="max-width: 940px; margin: 0 auto">
            <b-breadcrumb align="is-left" class="mb-3">
                <b-breadcrumb-item tag='router-link' to="/">Inicio</b-breadcrumb-item>
                <b-breadcrumb-item tag='router-link' to="/insumos">Insumos</b-breadcrumb-item>
                <b-breadcrumb-item active>Registrar</b-breadcrumb-item>
            </b-breadcrumb>
            <div class="card" style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10)">
                <header class="card-header" style="background: var(--color-primario); box-shadow: none">
                    <p class="card-header-title" style="color: #fff; font-size: 1.1rem">
                        <b-icon icon="plus-circle" size="is-small" custom-class="mr-2" style="color:#fff"></b-icon>
                        &nbsp;Registrar Insumo
                    </p>
                </header>
                <div class="card-content">
                    <datos-insumo @registrado="onRegistrado" :insumo="insumo"></datos-insumo>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
import DatosInsumo from "./DatosInsumo.vue"
import HttpService from '../../Servicios/HttpService'

export default {
    name: "RegistrarInsumo",
    components: { DatosInsumo },

    data: () => ({
        cargando: false,
        insumo: {
            tipo: "",
            codigo: "",
            nombre: "",
            descripcion: "",
            categoria: "",
            precio: "",
            stock: 0,
            stockMinimo: 0,
            stockMateria: 0,
            tipoCorte: 0,
            tipoVenta: "NORMAL",
            idComboPlantilla: "",
            receta: []
        }
    }),

    methods: {
        onRegistrado(registro) {
            this.insumo = registro
            this.cargando = true
            HttpService.registrar(this.insumo, "registrar_insumo.php")
            .then(registrado => {
                if(registrado) {
                    this.insumo = {
                        tipo: "",
                        codigo: "",
                        nombre: "",
                        descripcion: "",
                        categoria: "",
                        precio: "",
                        stock: 0,
                        stockMinimo: 0,
                        stockMateria: 0,
                        tipoCorte: 0,
                        tipoVenta: "NORMAL",
                        idComboPlantilla: "",
                        receta: []
                    }
                    this.$toast({
                        message: 'Insumo registrado',
                        type: 'is-success'
                    })
                    this.cargando = false
                }
            })
        }
    }

}
</script>