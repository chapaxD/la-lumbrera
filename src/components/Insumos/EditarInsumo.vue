<template>
    <section>
        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
        <div style="max-width: 940px; margin: 0 auto">
            <b-breadcrumb align="is-left" class="mb-3">
                <b-breadcrumb-item tag='router-link' to="/">Inicio</b-breadcrumb-item>
                <b-breadcrumb-item tag='router-link' to="/insumos">Insumos</b-breadcrumb-item>
                <b-breadcrumb-item active>Editar</b-breadcrumb-item>
            </b-breadcrumb>
            <div class="card" style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10)">
                <header class="card-header" style="background: var(--color-primario); box-shadow: none">
                    <p class="card-header-title" style="color: #fff; font-size: 1.1rem">
                        <b-icon icon="pencil" size="is-small" custom-class="mr-2" style="color:#fff"></b-icon>
                        &nbsp;Editar Insumo
                    </p>
                </header>
                <div class="card-content">
                    <datos-insumo @registrado="onRegistrado" :insumo="insumo" :editar="true"></datos-insumo>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'
import DatosInsumo from './DatosInsumo.vue'

export default ({
    name: "EditarInsumo",
    components: { DatosInsumo },
    data: () => ({
        insumo: {},
        cargando: false
    }),

    mounted() {
        this.cargando = true
        HttpService.obtenerConDatos(this.$route.params.id, "obtener_insumo_id.php")
        .then(resultado => {
            this.insumo = resultado
            this.cargando = false
        })
        .catch(() => {
            this.cargando = false
            this.$toast({ message: 'Error al cargar insumo.', type: 'is-danger' })
        })
    },

    methods: {
        onRegistrado(insumo){
            this.insumo = insumo
            this.insumo.idUsuario = localStorage.getItem('idUsuario')
            this.cargando = true
            HttpService.registrar(this.insumo, "editar_insumo.php")
            .then(editado => {
                if(editado){
                    this.$toast({
                        message: 'Información actualizada',
                        type: 'is-success'
                    })
                    this.cargando = false
                    this.$router.push({
                        name: "Insumos",
                    })
                }
            })
            .catch(() => {
                this.cargando = false
                this.$toast({ message: 'Error al actualizar insumo.', type: 'is-danger' })
            })
        }
    }
})
</script>
