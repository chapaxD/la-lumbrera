<template>
  <section style="display:none">
    <div id="comprobante">
      <h2>{{ datosLocal.nombre }}</h2>
      <div v-if="venta.mesa" class="num-mesa">MESA #{{ venta.mesa }}</div>

      <div class="separador"></div>

      <div class="datos-venta">
        <div>Fecha: {{ venta.fecha | formatFecha }}</div>
        <div>Atiende: {{ venta.atendio }}</div>
        <div>Cliente: {{ venta.cliente || 'MOSTRADOR' }}</div>
      </div>

      <div class="separador"></div>

      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th class="col-cant">Cant</th>
            <th class="col-sub">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(insumo, index) in insumos" :key="index">
            <td>{{ insumo.nombre }}</td>
            <td class="col-cant">{{ insumo.cantidad }}</td>
            <td class="col-sub">${{ formatNum(insumo.cantidad * insumo.precio) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="separador"></div>

      <div class="totales">
        <div class="fila-total grande">
          <span>TOTAL</span>
          <span>${{ formatNum(venta.total) }}</span>
        </div>
        <div class="fila-total" v-if="venta.metodoPago === 'MIXTO'">
          <span>Efectivo</span><span>${{ formatNum(venta.montoEfectivo) }}</span>
        </div>
        <div class="fila-total" v-if="venta.metodoPago === 'MIXTO'">
          <span>Tarjeta</span><span>${{ formatNum(venta.montoTarjeta) }}</span>
        </div>
        <div class="fila-total" v-if="venta.metodoPago === 'MIXTO'">
          <span>QR</span><span>${{ formatNum(venta.montoQR) }}</span>
        </div>
      </div>

      <div class="separador"></div>
    </div>
  </section>
</template>

<script>
import Printd from "printd";

export default {
  name: "Ticket",
  props: ["venta", "insumos", "datosLocal", "logo"],

  computed: {
    cambio() {
      return Math.max(0, parseFloat(this.venta.pagado || 0) - parseFloat(this.venta.total || 0));
    }
  },

  data: () => ({
    cssText: `
      @page {
        size: 80mm auto;
        margin: 3mm 2mm;
      }
      * { box-sizing: border-box; }
      body { margin: 0; padding: 0; }
      #comprobante {
        width: 72mm;
        margin: 0 auto;
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        text-align: center;
      }
      h2 { font-size: 15px; font-weight: bold; margin: 2px 0; }
      .num-mesa { font-size: 22px; font-weight: bold; margin: 4px 0; }
      .info { font-size: 11px; margin: 1px 0; }
      .separador { border-top: 1px dashed #000; margin: 4px 0; }
      .datos-venta { text-align: left; font-size: 11px; line-height: 1.5; }
      table { width: 100%; border-collapse: collapse; font-size: 15px; margin: 2px 0; }
      th { border-bottom: 1px solid #000; padding: 2px 3px; text-align: left; }
      td { padding: 2px 3px; vertical-align: top; text-align: left; font-weight: bold; text-transform: uppercase; }
      .col-cant { text-align: center; width: 10mm; }
      .col-sub  { text-align: right;  width: 16mm; }
      .totales { text-align: left; font-size: 12px; line-height: 1.7; }
      .fila-total { display: flex; justify-content: space-between; padding: 0; }
      .fila-total.grande {
        font-size: 14px; font-weight: bold;
        border-top: 1px solid #000; border-bottom: 1px solid #000;
        padding: 2px 0; margin: 2px 0;
      }
    `,
  }),

  mounted() {
    this.d = new Printd();
    this.imprimir();
  },

  methods: {
    imprimir() {
      const zona = document.getElementById("comprobante");
      setTimeout(() => {
        this.d.print(zona, [this.cssText]);
        this.$emit("impreso", false);
      }, 50);
    },
    formatNum(n) {
      return parseFloat(n || 0).toFixed(2);
    }
  },
};
</script>
