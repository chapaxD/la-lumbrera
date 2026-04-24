<template>
  <section style="display:none">
    <div id="comprobante">
      <div v-if="venta.metodoPago === 'PRE-CUENTA'" class="header-nota">--- DETALLE DE PEDIDO ---</div>
      <h2>{{ datosLocal.nombre }}</h2>
      <div v-if="venta.mesa" class="num-mesa">MESA #{{ venta.mesa }}</div>

      <div class="separador"></div>

      <div class="datos-venta">
        <div>Fecha: {{ venta.fecha | formatFecha }}</div>
        <div>Atiende: {{ venta.atendio }}</div>
        <div>Cliente: {{ venta.cliente || 'MOSTRADOR' }}</div>
        <div v-if="venta.adelanto && venta.adelanto > 0" class="has-text-info" style="font-weight:bold;">RESERVA</div>
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
            <td>
              {{ insumo.nombre }}
              <div v-if="insumo.resumenCombo" class="carac-combo">
                {{ Utiles.formatearResumenCombo(insumo.resumenCombo) }}
              </div>
            </td>
            <td class="col-cant">{{ insumo.cantidad }}</td>
            <td class="col-sub">Bs. {{ formatNum(insumo.cantidad * insumo.precio) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="separador"></div>

      <div class="totales">
        <div class="fila-total grande">
          <span>TOTAL</span>
          <span>Bs. {{ formatNum(venta.total + (venta.adelanto || 0)) }}</span>
        </div>
        <div class="fila-total" v-if="venta.adelanto">
          <span>Adelanto aplicado</span>
          <span>- Bs. {{ formatNum(venta.adelanto) }}</span>
        </div>
        <div class="fila-total"
          v-if="venta.total === 0 && venta.adelanto && venta.adelanto > (venta.total + (venta.adelanto || 0))">
          <span class="has-text-success">A devolver al cliente</span>
          <span class="has-text-success">Bs. {{ formatNum(venta.adelanto - (venta.total + (venta.adelanto || 0))) }}</span>
        </div>

        <div class="separador"></div>
        <div class="fila-total" v-if="venta.metodoPago && venta.metodoPago !== 'MIXTO'">
          <span>METODO PAGO:</span>
          <span>{{ venta.metodoPago }}</span>
        </div>
        <div class="fila-total" v-if="venta.metodoPago === 'MIXTO'">
          <span style="font-weight:bold">PAGO MIXTO:</span>
        </div>
        <div class="fila-total" v-if="venta.metodoPago === 'MIXTO'">
          <span>Efectivo</span><span>Bs. {{ formatNum(venta.montoEfectivo) }}</span>
        </div>
        <div class="fila-total" v-if="venta.metodoPago === 'MIXTO'">
          <span>Tarjeta</span><span>Bs. {{ formatNum(venta.montoTarjeta) }}</span>
        </div>
        <div class="fila-total" v-if="venta.metodoPago === 'MIXTO'">
          <span>QR</span><span>Bs. {{ formatNum(venta.montoQR) }}</span>
        </div>
      </div>

      <div class="separador"></div>
    </div>
  </section>
</template>

<script>
import Utiles from "../../Servicios/Utiles";

export default {
  name: "Ticket",
  props: ["venta", "insumos", "datosLocal", "logo"],

  computed: {
    cambio() {
      return Math.max(0, parseFloat(this.venta.pagado || 0) - parseFloat(this.venta.total || 0));
    }
  },

  data: () => ({
    Utiles,
    cssText: `
      @page {
        size: 80mm auto;
        margin: 3mm 2mm;
      }
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body { 
        margin: 0; 
        padding: 0; 
        font-family: 'Courier New', Courier, monospace; 
        font-size: 13px; 
        width: 76mm; 
        -webkit-print-color-adjust: exact;
      }
      #comprobante {
        width: 100%;
        text-align: center;
      }
      h2 { font-size: 17px; font-weight: bold; margin: 3px 0; letter-spacing: 1px; }
      .header-nota { font-size: 15px; font-weight: bold; border-bottom: 1px double #000; margin-bottom: 5px; padding-bottom: 2px; }
      .num-mesa { font-size: 22px; font-weight: bold; margin: 5px 0; }
      .info { font-size: 12px; margin: 2px 0; }
      .separador { border-top: 1px dashed #000; margin: 6px 0; }
      .datos-venta { text-align: left; font-size: 12px; line-height: 1.4; margin-bottom: 4px; }
      table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 4px 0; table-layout: fixed; }
      th { border-bottom: 1px solid #000; padding: 3px 2px; text-align: left; font-weight: bold; }
      td { padding: 4px 2px; vertical-align: top; text-align: left; font-weight: bold; text-transform: uppercase; word-wrap: break-word; }
      .col-cant { text-align: center; width: 12mm; }
      .col-sub  { text-align: right;  width: 20mm; }
      .carac-combo {
        font-size: 12px;
        font-weight: bold;
        text-transform: none;
        margin-top: 2px;
        white-space: pre-line;
        border-left: 2px solid #000;
        padding-left: 5px;
        font-style: italic;
      }
      .totales { text-align: left; font-size: 13px; line-height: 1.6; margin-top: 6px; }
      .fila-total { display: flex; justify-content: space-between; padding: 1px 0; }
      .fila-total.grande {
        font-size: 16px; font-weight: bold;
        border-top: 1px solid #000; border-bottom: 1px solid #000;
        padding: 4px 0; margin: 4px 0;
      }
    `,
  }),

  mounted() {
    this.imprimir();
  },

  methods: {
    imprimir() {
      const zona = document.getElementById("comprobante");
      if (!zona) return;
      const html = zona.innerHTML;
      const ventana = window.open('', '_blank', 'width=420,height=640');
      if (!ventana) {
          alert('El navegador bloqueó la ventana emergente. Por favor, actívalas para poder imprimir.');
          this.$emit("impreso", false);
          return;
      }
      ventana.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8">
            <title>Ticket</title>
            <style>${this.cssText}</style>
          </head>
          <body>
            ${html}
          </body>
        </html>
      `);
      ventana.document.close();
      ventana.focus();
      setTimeout(() => {
        ventana.print();
        ventana.close();
        this.$emit("impreso", false);
      }, 500);
    },
    formatNum(n) {
      return Math.round(parseFloat(n || 0));
    }
  },
};
</script>
