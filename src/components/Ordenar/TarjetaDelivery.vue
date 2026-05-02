<template>
  <div class="box" :class="[isLlevar ? 'has-background-warning-light' : 'has-background-info-light', { 'orden-lista-pulso': tieneListo(del.insumos) }]">
    <p class="has-text-weight-bold mb-1" :class="isLlevar ? 'has-text-warning-dark' : 'has-text-info'">
      <b-icon :icon="isLlevar ? 'walk' : 'truck-delivery'" size="is-small"></b-icon>
      {{ isLlevar ? 'PARA LLEVAR' : 'DELIVERY' }}
    </p>
    <div class="is-flex is-justify-content-space-between is-align-items-center is-flex-wrap-wrap mb-2">
      <p class="title is-3 has-text-grey mb-0">{{ del.delivery.cliente || ((isLlevar ? 'Pedido ' : 'Delivery ') + del.delivery.idDelivery) }}</p>
      <span class="title is-1 has-text-weight-bold" v-if="del.delivery.total && puedeAcceder">
        ${{ del.delivery.total }}
      </span>
    </div>
    <p v-if="!isLlevar && del.delivery.direccion && puedeAcceder">
      <strong>Dirección</strong>: {{ del.delivery.direccion }}
    </p>
    <p v-if="del.delivery.telefono && puedeAcceder">
      <strong>Teléfono</strong>: {{ del.delivery.telefono }}
    </p>
    <p><strong>Atiende</strong>: {{ del.delivery.atiende }}</p>

    <b-collapse class="card mt-2" animation="slide" v-if="del.insumos.length > 0 && puedeAcceder">
      <template #trigger="props">
        <div class="card-header" role="button" :aria-expanded="props.open">
          <p class="card-header-title">{{ isLlevar ? 'Insumos del pedido' : 'Insumos en el delivery' }}</p>
          <a class="card-header-icon">
            <b-icon :icon="props.open ? 'menu-down' : 'menu-up'"></b-icon>
          </a>
        </div>
      </template>
      <div class="card-content">
        <div class="content">
          <b-table :data="del.insumos" mobile-cards narrow>
            <b-table-column field="nombre" label="Nombre" v-slot="props">
              {{ props.row.nombre }}
              <p v-if="props.row.resumenCombo" class="is-size-6 has-text-dark has-text-weight-bold mt-1" style="white-space: pre-line;">
                <b-icon icon="food-variant" size="is-small"></b-icon>
                {{ Utiles.formatearResumenCombo(props.row.resumenCombo) }}
              </p>
            </b-table-column>
            <b-table-column field="cantidad" label="Cantidad" v-slot="props">
              {{ props.row.cantidad }} X {{ Utiles.formatearDinero(props.row.precio) }}
            </b-table-column>
            <b-table-column field="subtotal" label="Subtotal" v-slot="props">
              {{ Utiles.formatearDinero(props.row.cantidad * props.row.precio) }}
            </b-table-column>
            <b-table-column field="estado" label="" v-slot="props">
              <b-icon icon="alert" type="is-danger" v-if="props.row.estado === 'pendiente'"></b-icon>
              <b-icon icon="bell-ring" type="is-warning" title="¡Listo! Retirar de cocina" v-if="props.row.estado === 'listo'"></b-icon>
              <b-icon icon="check" type="is-success" v-if="props.row.estado === 'entregado'"></b-icon>
            </b-table-column>
          </b-table>
        </div>
      </div>
    </b-collapse>
    <br>
    <div class="has-text-centered">
      <template v-if="del.delivery.estado_orden !== 'pagada'">
        <div class="field is-grouped is-grouped-centered is-grouped-multiline" v-if="puedeAcceder">
          <p class="control" v-if="rol !== 'mesero'">
            <b-button type="is-success" icon-left="cash" @click="$emit('cobrarDelivery', del)">Cobrar</b-button>
          </p>
          <p class="control">
            <b-button type="is-info" icon-left="printer" is-light @click="$emit('imprimirPrecuenta', del, del.delivery.tipo_orden || 'DELIVERY')" title="Imprimir detalle para el cliente">Detalle</b-button>
          </p>
          <p class="control">
            <b-button type="is-info" icon-left="plus" @click="isLlevar ? $emit('editarParaLlevar', del) : $emit('editarDelivery', del)">Agregar</b-button>
          </p>
          <p class="control">
            <b-button type="is-danger" icon-left="close" @click="$emit('cancelarDelivery', del.delivery.idDelivery)">Cancelar</b-button>
          </p>
          <p class="control">
            <b-button type="is-info" is-light icon-left="printer" @click="$emit('imprimirComandaDelivery', del)" title="Imprimir comanda para cocina">Comanda</b-button>
          </p>
          <p class="control">
            <b-button type="is-link" is-light icon-left="swap-horizontal" @click="$emit('solicitarCambioMesa', del, 'DELIVERY')" title="Asignar a una mesa">Cambiar</b-button>
          </p>
        </div>
        <p class="has-text-danger" v-else>Sin acceso a esta orden</p>
      </template>

      <div class="notification is-success is-light py-2 px-3 mb-0" v-if="del.delivery.estado_orden === 'pagada' && !isLlevar">
          <b-icon icon="cash-check" type="is-success"></b-icon>
          <span v-if="tienePendiente(del.insumos)"><strong>Cobrado</strong> — el pedido está en preparación en cocina</span>
          <span v-else><strong>Cobrado y listo</strong> — entregar al cliente</span>
          <div class="mt-2" v-if="puedeAcceder">
              <b-button v-if="!tienePendiente(del.insumos)" type="is-success" icon-left="hand-okay" class="mr-2"
                  @click="$emit('entregarOrdenPagada', 'DELIVERY', del.delivery.idDelivery)">Entregar al cliente</b-button>
              <b-button type="is-info" is-light icon-left="printer" class="mr-2"
                  @click="$emit('imprimirComandaDelivery', del)" title="Reimprimir comanda">Comanda</b-button>
              <b-button type="is-info" icon-left="plus" @click="$emit('editarDelivery', del)">Agregar más</b-button>
          </div>
      </div>

      <div class="notification is-success is-light py-2 px-3 mb-2" v-if="del.delivery.estado_orden === 'pagada' && isLlevar">
        <b-icon icon="cash-check" type="is-success" size="is-small"></b-icon>
        <span v-if="tienePendiente(del.insumos)"><strong>Cobrado</strong> — el pedido está en preparación en cocina</span>
        <span v-else><strong>Cobrado y listo</strong> — entregar al cliente</span>
      </div>

      <div class="field is-grouped is-grouped-centered is-grouped-multiline" v-if="del.delivery.estado_orden === 'pagada' && puedeAcceder && isLlevar">
          <p class="control">
              <b-button type="is-success" icon-left="hand-okay" v-if="!tienePendiente(del.insumos)"
                  @click="$emit('entregarOrdenPagada', del.delivery.tipo_orden || 'DELIVERY', del.delivery.idDelivery)">Entregar</b-button>
              <b-button v-else type="is-success" icon-left="cash-check" disabled>Cobrado</b-button>
          </p>
          <p class="control">
              <b-button type="is-info" icon-left="printer" is-light @click="$emit('imprimirPrecuenta', del, del.delivery.tipo_orden || 'DELIVERY')" title="Imprimir detalle para el cliente">Detalle</b-button>
          </p>
          <p class="control">
              <b-button type="is-info" icon-left="plus" @click="$emit('editarParaLlevar', del)">Agregar</b-button>
          </p>
          <p class="control">
              <b-button type="is-danger" icon-left="close" @click="$emit('cancelarDelivery', del.delivery.idDelivery)">Cancelar</b-button>
          </p>
          <p class="control">
              <b-button type="is-info" is-light icon-left="printer" @click="$emit('imprimirComandaDelivery', del)" title="Imprimir comanda para cocina">Comanda</b-button>
          </p>
          <p class="control">
              <b-button type="is-link" is-light icon-left="swap-horizontal" @click="$emit('solicitarCambioMesa', del, 'DELIVERY')" title="Asignar a una mesa">Cambiar</b-button>
          </p>
      </div>
    </div>
  </div>
</template>

<script>
import Utiles from '../../Servicios/Utiles'

export default {
  name: "TarjetaDelivery",
  props: {
    del: { type: Object, required: true },
    puedeAcceder: { type: Boolean, required: true },
    rol: { type: String, default: '' },
    tipo: { type: String, default: 'DELIVERY' }
  },
  data() {
    return { Utiles }
  },
  computed: {
    isLlevar() {
      return this.tipo === 'LLEVAR';
    }
  },
  methods: {
    tienePendiente(insumos) {
      return insumos && insumos.length > 0 && insumos.some(i => i.estado === 'pendiente')
    },
    tieneListo(insumos) {
      return insumos && insumos.length > 0 && insumos.some(i => i.estado === 'listo')
    }
  }
}
</script>
