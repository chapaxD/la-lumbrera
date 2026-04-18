<template>
    <section>
        <b-table
        :data="lista"
        :bordered="true"
        :narrowed="true">
            <b-table-column field="nombre" label="Nombre" v-slot="props">
            {{ props.row.nombre }}
            <span v-if="(props.row.tipoVenta || '') === 'COMBO'" class="is-size-7 has-text-grey"><br>Menú ({{ props.row.cantidad }} u.)</span>
            <p v-if="props.row.resumenCombo" class="is-size-7 has-text-info mt-1" style="white-space: pre-line;">
              <b-icon icon="food-variant" size="is-small"></b-icon>
              {{ props.row.resumenCombo }}
            </p>
          </b-table-column>

          <b-table-column field="precio" label="Precio" v-slot="props">
            ${{ props.row.precio }}
          </b-table-column>

          <b-table-column field="cantidad" label="Cantidad" v-slot="props">
            <span v-if="tipo == 'entregado'">
                {{ props.row.cantidad }}
            </span>

            <span v-if="tipo === 'nuevo'">
                <template v-if="(props.row.tipoVenta || '') === 'COMBO'">
                  <span class="has-text-weight-semibold">{{ props.row.cantidad }}</span>
                  <p class="is-size-7 has-text-grey mt-1">Cantidad = menús (editar quitando y volviendo a agregar)</p>
                </template>
                <template v-else>
                <b-numberinput
                type="is-info"
                size="is-small"
                controls-position="compact"
                v-model="props.row.cantidad"
                :min="1"
                :max="props.row._stock > 0 ? props.row._stock : undefined"
                @input="validarCantidad(props.row); calcularTotal()"
                ></b-numberinput>
                <p v-if="props.row._stock > 0" class="is-size-7 has-text-grey mt-1">
                    Stock: {{ props.row._stock }}
                </p>
                </template>
            </span>
            
          </b-table-column>

          <b-table-column field="subtotal" label="Subtotal" v-slot="props">
            ${{ props.row.cantidad * props.row.precio }}
          </b-table-column>

          <b-table-column
            field="descripcion"
            label="Características"
            v-slot="props"
          >
            <span v-if="tipo === 'entregado'">
                {{ props.row.caracteristicas }}
            </span>
            
            <span v-if="tipo === 'nuevo'">
                <b-input
                v-model="props.row.caracteristicas"
                placeholder="Ej. Salsa roja, sin queso"
                ></b-input>
            </span>
          </b-table-column>

          <b-table-column field="estado" label="" v-slot="props" v-if="tipo === 'entregado'">
            <b-icon
              icon="alert"
              type="is-danger"
              v-if="props.row.estado === 'pendiente'"
            ></b-icon>
            <b-icon
              icon="check"
              type="is-success"
              v-if="props.row.estado === 'entregado'"
            ></b-icon>
          </b-table-column>

        <b-table-column field="quitar" label="Quitar" v-slot="props" v-if="tipo === 'nuevo'">
            <b-button
              type="is-danger"
              class="mb-1"
              @click="eliminar(props.row._lineId || props.row.id)"
            >
              <b-icon icon="delete"></b-icon>
            </b-button>
          </b-table-column>
        </b-table>
    </section>
</template>
<script>
export default {
    name: "ProductosOrden",
    props:  ["lista", "tipo"],

    methods: {
        validarCantidad(insumo) {
            if ((insumo.tipoVenta || '') === 'COMBO') return;
            if (!insumo._stock || insumo._stock <= 0) return;
            const val = parseInt(insumo.cantidad) || 1;
            if (val > insumo._stock) {
                insumo.cantidad = insumo._stock;
                this.$buefy.toast.open({
                    message: '\u26a0 Solo hay ' + insumo._stock + ' unidades de "' + insumo.nombre + '" disponibles',
                    type: 'is-warning',
                    duration: 3000
                });
            } else if (val < 1) {
                insumo.cantidad = 1;
            }
        },
        calcularTotal(){
            this.$emit("modificado")
        },

        eliminar(id){
            this.$emit("quitar", id)
        }
    }
}
</script>