<template>
    <b-modal :active="active" @close="$emit('close')" has-modal-card trap-focus :can-cancel="['escape', 'outside']">
        <div class="modal-card" style="min-width: 420px">
            <header class="modal-card-head">
                <p class="modal-card-title">
                    <b-icon :icon="tipoOrden === 'DELIVERY' ? 'truck-delivery' : 'walk'"
                        size="is-small"></b-icon>
                    &nbsp;{{ tipoOrden === 'DELIVERY' ? 'Nuevo Delivery' : 'Para Llevar' }} — ¿Para quién?
                </p>
            </header>
            <section class="modal-card-body">
                <b-field label="Cliente">
                    <b-autocomplete v-model="formInterno.nombre" :data="sugerenciasClientes"
                        placeholder="Escribe nombre o mostrador..." icon="account-search" field="nombre_completo"
                        :loading="buscandoCliente" @typing="buscarCliente" @select="seleccionarCliente"
                        clearable>
                        <template slot="empty">Sin resultados — se usará el nombre escrito</template>
                    </b-autocomplete>
                </b-field>
                <b-field label="Teléfono">
                    <b-input v-model="formInterno.telefono" placeholder="Opcional" icon="phone"></b-input>
                </b-field>
                <b-field label="Dirección" v-if="tipoOrden === 'DELIVERY'">
                    <b-input v-model="formInterno.direccion" placeholder="Dirección de entrega"
                        icon="map-marker"></b-input>
                </b-field>
            </section>
            <footer class="modal-card-foot">
                <b-button label="Cancelar" type="is-dark" @click="$emit('close')" />
                <b-button label="Continuar" type="is-primary" icon-left="arrow-right"
                    @click="confirmar" />
            </footer>
        </div>
    </b-modal>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
    name: 'ModalCliente',
    props: {
        active: Boolean,
        tipoOrden: { type: String, default: 'LLEVAR' }
    },
    data() {
        return {
            formInterno: { nombre: '', telefono: '', direccion: '' },
            sugerenciasClientes: [],
            buscandoCliente: false,
            _timer: null
        }
    },
    watch: {
        active(newVal) {
            if (newVal) {
                this.formInterno = { nombre: '', telefono: '', direccion: '' };
                this.sugerenciasClientes = [];
            }
        }
    },
    methods: {
        buscarCliente(nombre) {
            if (!nombre || nombre.length < 3) {
                this.sugerenciasClientes = [];
                return;
            }
            if (this._timer) clearTimeout(this._timer);
            this._timer = setTimeout(() => {
                this.buscandoCliente = true;
                HttpService.obtener(`obtener_clientes.php?q=${encodeURIComponent(nombre)}`)
                    .then(res => {
                        this.sugerenciasClientes = (res || []).map(c => ({
                            ...c,
                            nombre_completo: c.nombre + (c.apellido ? ' ' + c.apellido : '')
                        }));
                    })
                    .finally(() => { this.buscandoCliente = false; });
            }, 300);
        },
        seleccionarCliente(cliente) {
            if (cliente) {
                this.formInterno.nombre = cliente.nombre_completo || cliente.nombre;
                this.formInterno.telefono = cliente.telefono || '';
                this.formInterno.direccion = cliente.direccion || '';
            }
        },
        confirmar() {
            this.$emit('confirmar', { ...this.formInterno });
        }
    }
}
</script>
