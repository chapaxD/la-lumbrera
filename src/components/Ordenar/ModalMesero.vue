<template>
    <b-modal :active="active" @close="$emit('close')" has-modal-card trap-focus :can-cancel="['escape', 'outside']">
        <div class="modal-card" style="width: 380px">
            <header class="modal-card-head">
                <p class="modal-card-title">Asignar mesero</p>
            </header>
            <section class="modal-card-body">
                <b-field label="Selecciona el mesero que atenderá esta orden">
                    <b-select v-model="meseroInternoId" expanded placeholder="-- Seleccionar mesero --">
                        <option v-for="m in meseros" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </b-select>
                </b-field>
            </section>
            <footer class="modal-card-foot">
                <b-button label="Cancelar" type="is-dark" @click="$emit('close')" />
                <b-button label="Continuar" type="is-primary" icon-left="arrow-right" :disabled="!meseroInternoId"
                    @click="confirmar" />
            </footer>
        </div>
    </b-modal>
</template>

<script>
export default {
    name: 'ModalMesero',
    props: {
        active: Boolean,
        meseros: { type: Array, default: () => [] }
    },
    data() {
        return {
            meseroInternoId: null
        }
    },
    watch: {
        active(newVal) {
            if (newVal) {
                this.meseroInternoId = null;
            }
        }
    },
    methods: {
        confirmar() {
            this.$emit('confirmar', this.meseroInternoId);
        }
    }
}
</script>
