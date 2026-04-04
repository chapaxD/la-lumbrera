<template>
  <section>
    <br>
    <p class="title is-1 has-text-weight-bold">
        <b-icon icon="truck-fast" size="is-large" type="is-primary"></b-icon>
        Entrada de Mercadería
    </p>

    <div class="box">
        <p class="subtitle is-5 has-text-grey">Busca los insumos que vas a reabastecer</p>
        <b-field>
            <b-autocomplete
                size="is-large"
                v-model="nombreBusqueda"
                placeholder="Escribe el nombre o código del insumo..."
                :data="insumosFiltrados"
                field="nombre"
                @input="buscarInsumo"
                @select="option => agregarInsumoALista(option)"
                :clearable="true"
                id="busqueda"
                icon="magnify"
            >
                <template slot-scope="props">
                    <div class="media">
                        <div class="media-content">
                            <strong>{{ props.option.nombre }}</strong>
                            <br>
                            <small class="has-text-grey">Stock actual: {{ props.option.stock }} | Código: {{ props.option.codigo }}</small>
                        </div>
                    </div>
                </template>
            </b-autocomplete>
        </b-field>
    </div>

    <div v-if="listaCompras.length > 0" class="box border-success" style="border-top: 4px solid #48c774;">
        <p class="title is-4 has-text-success">
            <b-icon icon="format-list-checks"></b-icon> Lista de recepción
        </p>
        
        <b-table :data="listaCompras" bordered striped>
            <b-table-column field="codigo" label="Código" v-slot="props">
                {{ props.row.codigo }}
            </b-table-column>
            
            <b-table-column field="nombre" label="Nombre" v-slot="props">
                <strong>{{ props.row.nombre }}</strong>
            </b-table-column>

            <b-table-column field="stock_actual" label="Stock actual" v-slot="props">
                <b-tag type="is-light" size="is-medium">{{ props.row.stockPrevio }}</b-tag>
            </b-table-column>

            <b-table-column field="cantidad" label="Cantidad recibida (+)" v-slot="props">
                <b-input type="number" min="1" v-model.number="props.row.cantidad" size="is-small" style="width: 100px;"></b-input>
            </b-table-column>

            <b-table-column field="stock_final" label="Stock tras aplicar" v-slot="props">
                <b-tag type="is-success" size="is-medium" class="has-text-weight-bold">
                    {{ parseInt(props.row.stockPrevio) + parseInt(props.row.cantidad || 0) }}
                </b-tag>
            </b-table-column>

            <b-table-column field="acciones" label="" v-slot="props">
                <b-button type="is-danger" icon-left="delete" size="is-small" @click="quitar(props.index)">Quitar</b-button>
            </b-table-column>
        </b-table>
        
        <div class="has-text-centered" style="margin-top: 20px;">
            <b-button type="is-success" size="is-large" icon-left="check-all" @click="registrarCompra">
                Confirmar y Sumar al Inventario
            </b-button>
        </div>
    </div>
    
    <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
    name: 'Compras',
    data: () => ({
        nombreBusqueda: "",
        insumosBusqueda: [],
        listaCompras: [],
        cargando: false
    }),
    
    mounted() {
        const input = document.querySelector("#busqueda");
        if (input) input.focus();
    },

    computed: {
        insumosFiltrados() {
            // El servidor ya filtra por nombre/código, no aplicar doble filtro local
            return this.insumosBusqueda;
        }
    },

    methods: {
        buscarInsumo() {
            if (this.nombreBusqueda) {
                HttpService.obtenerConDatos(this.nombreBusqueda, "obtener_insumo_nombre.php")
                .then(resultado => {
                    this.insumosBusqueda = resultado;
                });
            }
        },

        agregarInsumoALista(insumo) {
            if (insumo) {
                let indice = this.listaCompras.findIndex(item => item.id === insumo.id);
                if (indice >= 0) {
                    this.listaCompras[indice].cantidad++;
                } else {
                    this.listaCompras.push({
                        id: insumo.id,
                        codigo: insumo.codigo,
                        nombre: insumo.nombre,
                        stockPrevio: parseFloat(insumo.stock) || 0,
                        cantidad: 1
                    });
                }
                setTimeout(() => { this.nombreBusqueda = ""; this.insumosBusqueda = []; }, 10);
            }
        },

        quitar(index) {
            this.listaCompras.splice(index, 1);
        },

        registrarCompra() {
            if(this.listaCompras.length === 0) return;
            
            this.$buefy.dialog.confirm({
                title: 'Confirmar recepción',
                message: `¿Estás seguro de sumar al inventario estos ${this.listaCompras.length} productos?`,
                confirmText: 'Sí, registrar',
                cancelText: 'Cancelar',
                type: 'is-success',
                onConfirm: () => {
                    this.cargando = true;
                    let payload = {
                        insumos: this.listaCompras,
                        idUsuario: localStorage.getItem('idUsuario')
                    };
                    HttpService.registrar(payload, "registrar_compra.php")
                    .then(resultado => {
                        this.cargando = false;
                        if(resultado) {
                            this.$toast({
                                message: "¡Inventario actualizado exitosamente!",
                                type: "is-success"
                            });
                            this.listaCompras = [];
                            this.nombreBusqueda = "";
                            document.querySelector("#busqueda").focus();
                        }
                    })
                    .catch(() => {
                        this.cargando = false;
                        this.$toast({
                            message: "Error al actualizar el inventario",
                            type: "is-danger"
                        });
                    });
                }
            })
        }
    }
}
</script>
