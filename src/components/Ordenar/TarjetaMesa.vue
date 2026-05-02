<template>
  <div class="box" :class="{ 'has-background-warning-light': mesa.mesa.reserva, 'orden-lista-pulso': tieneListo(mesa.insumos) }">
    <div class="is-flex is-justify-content-space-between is-align-items-center is-flex-wrap-wrap mb-2">
      <p class="title is-2 has-text-grey mb-0">Mesa #{{ mesa.mesa.idMesa }}</p>
      <span class="title is-1 has-text-weight-bold" v-if="mesa.mesa.total && puedeAcceder">
        {{ Utiles.formatearDinero(mesa.mesa.total) }}
      </span>
    </div>
    <p v-if="mesa.mesa.atiende">
      <strong>Atiende</strong>: {{ mesa.mesa.atiende }}
    </p>
    <p v-if="mesa.mesa.cliente && puedeAcceder">
      <strong>Cliente</strong>: {{ mesa.mesa.cliente }}
    </p>
    <div v-if="mesa.mesa.reserva" class="notification py-2 px-3 mt-2 mb-0"
      :class="mesa.mesa.reserva.estado === 'SENTADA' ? 'is-success is-light' : 'is-warning is-light'">
      <b-icon :icon="mesa.mesa.reserva.estado === 'SENTADA' ? 'account-check' : 'calendar-clock'"
        size="is-small"></b-icon>
      <span v-if="mesa.mesa.reserva.idMesa">
        {{ mesa.mesa.reserva.estado === 'SENTADA' ? 'Cliente sentado:' : 'Reservada hoy' }}
        <b>{{ mesa.mesa.reserva.hora }}</b> - {{ mesa.mesa.reserva.nombre_cliente }}
      </span>
      <span v-else> <b>EVENTO TOTAL: {{ mesa.mesa.reserva.hora }}</b> </span>
    </div>
    <b-collapse class="card mt-2" animation="slide" aria-id="contentIdForA11y3"
      v-if="(mesa.mesa.estado === 'ocupada' || mesa.mesa.estado === 'pagada') && puedeAcceder">
      <template #trigger="props">
        <div class="card-header" role="button" aria-controls="contentIdForA11y3" :aria-expanded="props.open">
          <p class="card-header-title">
            Insumos en la orden
          </p>
          <a class="card-header-icon">
            <b-icon :icon="props.open ? 'menu-down' : 'menu-up'">
            </b-icon>
          </a>
        </div>
      </template>

      <div class="card-content">
        <div class="content">
          <b-table :data="mesa.insumos" :checked-rows="checkedRows"
            @check="(rows) => $emit('update:checked-rows', rows)"
            :is-row-checkable="(row) => row.estado === 'listo' || (parseInt(datos.usa_pantalla_cocina || 0) === 0 && parseInt(datos.usa_pantalla_parrilla || 0) === 0)"
            :checkable="puedeAcceder" mobile-cards narrow custom-row-key="itemId"
            :checkbox-position="checkboxPosition" :checkbox-type="checkboxType">

            <b-table-column field="nombre" label="Nombre" v-slot="props">
              {{ props.row.nombre }}
              <p v-if="props.row.resumenCombo" class="is-size-6 has-text-dark has-text-weight-bold mt-1"
                style="white-space: pre-line;">
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
              <b-icon icon="bell-ring" type="is-warning" title="¡Listo! Retirar de cocina"
                v-if="props.row.estado === 'listo' && (props.row.tipo || '').toUpperCase() !== 'BEBIDA' && (props.row.categoria || '').toUpperCase() !== 'BEBIDAS'"></b-icon>
              <b-icon icon="check" type="is-success" v-if="props.row.estado === 'entregado'"></b-icon>
            </b-table-column>
          </b-table>
        </div>
      </div>
    </b-collapse>
    <br>
    <div class="has-text-centered">
      <!-- Mesa libre sin reserva activa -->
      <b-button type="is-primary" icon-left="check" @click="$emit('ocuparMesa', mesa)"
        v-if="mesa.mesa.estado === 'libre' && !mesa.mesa.reserva">Ocupar</b-button>
      <!-- Mesa libre pero con reserva activa: bloqueada -->
      <div v-if="mesa.mesa.estado === 'libre' && mesa.mesa.reserva"
        class="notification is-danger is-light py-2 px-3">
        <b-icon icon="lock" size="is-small"></b-icon>
        <b>Mesa {{ mesa.mesa.reserva.estado === 'PENDIENTE' ? 'en espera de confirmación' : 'confirmada' }}</b> — solo
        puede abrirse desde <b>Gestión de Reservas</b>
      </div>
      <div class="field is-grouped is-grouped-centered is-grouped-multiline"
        v-if="mesa.mesa.estado === 'ocupada' && puedeAcceder">
        <p class="control" v-if="rol !== 'mesero'">
          <b-button type="is-success" icon-left="cash" @click="$emit('cobrar', mesa)">Cobrar</b-button>
        </p>
        <p class="control">
          <b-button type="is-info" icon-left="printer" is-light @click="$emit('imprimirPrecuenta', mesa, 'LOCAL')"
            title="Imprimir detalle para el cliente">Detalle</b-button>
        </p>
        <p class="control">
          <b-button type="is-info" icon-left="plus" @click="$emit('ocuparMesa', mesa)">Agregar</b-button>
        </p>
        <p class="control">
          <b-button type="is-primary" icon-left="account-multiple-plus" @click="$emit('compartirMesa', mesa)"
            title="Compartir mesa con otro grupo">Compartir</b-button>
        </p>
        <p class="control">
          <b-button type="is-warning" icon-left="check" v-if="(checkedRows || []).length > 0"
            @click="$emit('marcarInsumosEntregados', mesa)">Entregado</b-button>
        </p>
        <p class="control">
          <b-button type="is-danger" icon-left="close" @click="$emit('cancelarOrden', mesa.mesa.idMesa)">Cancelar</b-button>
        </p>
        <p class="control">
          <b-button type="is-info" is-light icon-left="printer" @click="$emit('imprimirComandaMesa', mesa)"
            title="Imprimir comanda para cocina">Comanda</b-button>
        </p>
        <p class="control">
          <b-button type="is-link" is-light icon-left="swap-horizontal" @click="$emit('solicitarCambioMesa', mesa, 'LOCAL')"
            title="Cambiar de mesa">Cambiar</b-button>
        </p>
      </div>
      <p class="has-text-danger" v-if="mesa.mesa.estado === 'ocupada' && !puedeAcceder">
        Sin acceso a esta orden
      </p>
      <div class="notification is-success is-light py-2 px-3 mb-2" v-if="mesa.mesa.estado === 'pagada'">
        <b-icon icon="cash-check" type="is-success" size="is-small"></b-icon>
        <span v-if="tienePendiente(mesa.insumos)"><strong>Cobrado</strong> — el pedido está en preparación en
          cocina</span>
        <span v-else><strong>Cobrado y listo</strong> — entregar al cliente</span>
      </div>

      <div class="notification is-link is-light py-2 px-3 mb-2" v-if="mesa.mesa.estado === 'entregada'">
        <b-icon icon="truck-check" type="is-link" size="is-small"></b-icon>
        <span><strong>Entregado</strong> — esperando que el cliente se retire</span>
      </div>


      <div class="field is-grouped is-grouped-centered is-grouped-multiline"
        v-if="(mesa.mesa.estado === 'pagada' || mesa.mesa.estado === 'entregada') && puedeAcceder">
        <p class="control" v-if="mesa.mesa.estado === 'pagada'">
          <b-button type="is-success" icon-left="hand-okay" v-if="!tienePendiente(mesa.insumos)"
            @click="$emit('entregarOrdenPagada', 'LOCAL', mesa.mesa.idMesa)">Entregar</b-button>
          <b-button v-else type="is-success" icon-left="cash-check" disabled>Cobrado</b-button>
        </p>
        <p class="control" v-if="rol !== 'mesero' && !esPagada(mesa.insumos)">
          <b-button type="is-success" icon-left="cash" @click="$emit('cobrar', mesa)">Cobrar</b-button>
        </p>
        <p class="control" v-if="esPagada(mesa.insumos)">
          <b-button type="is-dark" icon-left="logout" @click="$emit('liberarMesa', 'LOCAL', mesa.mesa.idMesa)">Liberar
            Mesa</b-button>
        </p>
        <p class="control">
          <b-button type="is-info" icon-left="printer" is-light @click="$emit('imprimirPrecuenta', mesa, 'LOCAL')"
            title="Imprimir detalle para el cliente">Detalle</b-button>
        </p>
        <p class="control" v-if="mesa.mesa.estado === 'pagada'">
          <b-button type="is-info" icon-left="plus" @click="$emit('ocuparMesa', mesa)">Agregar</b-button>
        </p>
        <p class="control" v-if="mesa.mesa.estado === 'pagada' || mesa.mesa.estado === 'entregada'">
          <b-button type="is-primary" icon-left="account-multiple-plus" @click="$emit('compartirMesa', mesa)"
            title="Compartir mesa con otro grupo">Compartir</b-button>
        </p>
        <p class="control" v-if="mesa.mesa.estado === 'pagada'">
          <b-button type="is-warning" icon-left="check" v-if="(checkedRows || []).length > 0"
            @click="$emit('marcarInsumosEntregados', mesa)">Entregado</b-button>
        </p>
        <p class="control" v-if="mesa.mesa.estado === 'pagada'">
          <b-button type="is-danger" icon-left="close" @click="$emit('cancelarOrden', mesa.mesa.idMesa)">Cancelar</b-button>
        </p>
        <p class="control" v-if="mesa.mesa.estado === 'pagada'">
          <b-button type="is-info" is-light icon-left="printer" @click="$emit('imprimirComandaMesa', mesa)"
            title="Imprimir comanda para cocina">Comanda</b-button>
        </p>
        <p class="control" v-if="mesa.mesa.estado === 'pagada' || mesa.mesa.estado === 'entregada'">
          <b-button type="is-link" is-light icon-left="swap-horizontal" @click="$emit('solicitarCambioMesa', mesa, 'LOCAL')"
            title="Cambiar de mesa">Cambiar</b-button>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import Utiles from '../../Servicios/Utiles'

export default {
  name: "TarjetaMesa",
  props: {
    mesa: { type: Object, required: true },
    puedeAcceder: { type: Boolean, required: true },
    checkedRows: { type: Array, default: () => [] },
    checkboxPosition: { type: String, default: 'left' },
    checkboxType: { type: String, default: 'is-primary' },
    rol: { type: String, default: '' },
    datos: { type: Object, default: () => ({}) }
  },
  data() {
    return {
      Utiles
    }
  },
  methods: {
    tienePendiente(insumos) {
      return insumos && insumos.length > 0 && insumos.some(i => i.estado === 'pendiente')
    },
    tieneListo(insumos) {
      return insumos && insumos.length > 0 && insumos.some(i => i.estado === 'listo' && (i.tipo || '').toUpperCase() !== 'BEBIDA' && (i.categoria || '').toUpperCase() !== 'BEBIDA' && (i.categoria || '').toUpperCase() !== 'BEBIDAS')
    },
    esPagada(insumos) {
      return insumos && insumos.length > 0 && insumos.every(i => i.pagado == 1)
    }
  }
}
</script>
