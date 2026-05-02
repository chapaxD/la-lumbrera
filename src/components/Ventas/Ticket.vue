<template>
  <section :style="noImprimir ? '' : 'display:none'">
    <div id="comprobante" :class="{ 'vista-previa': noImprimir }" style="background: #fff; font-family: monospace; width: 300px; margin: 0 auto; color: #000; line-height: 1.2; text-align: left;">
      <!-- MODO TICKET / PRECUENTA -->
      <div v-if="venta.metodoPago !== 'COMANDA'">
        <div class="has-text-centered" style="text-align: center;">
          <h3 class="has-text-weight-bold is-size-5" style="font-size: 1.25rem; font-weight: bold; margin: 0;">{{ datosLocal.nombre }}</h3>
          <div v-if="venta.metodoPago === 'PRE-CUENTA'" class="is-size-7"
              style="border: 1px dashed #000; margin: 5px 0; font-size: 0.75rem;">--- DETALLE DE PEDIDO ---</div>
          <div v-if="venta.mesa" class="is-size-4 has-text-weight-bold" style="font-size: 1.5rem; font-weight: bold; margin-top: 5px;">MESA #{{ venta.mesa }}</div>
          <div v-else-if="venta.tipo === 'LLEVAR' || venta.tipoOrden === 'LLEVAR'" class="is-size-4 has-text-weight-bold" style="font-size: 1.5rem; font-weight: bold; margin-top: 5px;">PARA LLEVAR #{{ venta.id || venta.idDelivery || venta.idVenta }}</div>
          <div v-else-if="venta.tipo === 'DELIVERY' || venta.tipoOrden === 'DELIVERY'" class="is-size-4 has-text-weight-bold" style="font-size: 1.5rem; font-weight: bold; margin-top: 5px;">DELIVERY #{{ venta.id || venta.idDelivery || venta.idVenta }}</div>
        </div>
        <hr style="border-top: 1px dashed #000; margin: 8px 0;">
        <div class="is-size-7" style="font-size: 0.75rem;">
            <div>Fecha: {{ venta.fecha | formatFecha }}</div>
            <div>Atiende: {{ venta.atendio }}</div>
            <div>Cliente: {{ venta.cliente || 'MOSTRADOR' }}</div>
            <div v-if="venta.adelanto && venta.adelanto > 0" style="font-weight:bold;">RESERVA</div>
        </div>
        <hr style="border-top: 1px dashed #000; margin: 8px 0;">
        <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #000;">
                    <th style="text-align: left;">Prod.</th>
                    <th style="text-align: center;">Cant</th>
                    <th style="text-align: right;">Sub.</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(ins, idx) in insumos" :key="idx">
                    <td style="font-weight: bold; text-transform: uppercase; padding: 2px 0;">
                        {{ ins.nombre }}
                        <div v-if="ins.caracteristicas" style="font-size: 11px; border-left: 1px solid #000; padding-left: 4px; font-weight: normal; font-style: italic; margin-top: 2px;">
                            {{ ins.caracteristicas }}
                        </div>
                        <div v-if="ins.resumenCombo" style="font-size: 11px; border-left: 1px solid #000; padding-left: 4px; white-space: pre-line; font-weight: bold; margin-top: 2px;">
                            {{ Utiles.formatearResumenCombo(ins.resumenCombo) }}
                        </div>
                    </td>
                    <td style="text-align: center; padding: 2px 0;">{{ ins.cantidad }}</td>
                    <td style="text-align: right; padding: 2px 0;">{{ Math.round(ins.cantidad * ins.precio) }}</td>
                </tr>
            </tbody>
        </table>
        <hr style="border-top: 1px dashed #000; margin: 8px 0;">
        
        <div style="font-size: 1.5rem; font-weight: bold; display: flex; justify-content: space-between;">
            <span>TOTAL</span>
            <span>Bs. {{ Math.round(venta.total + (venta.adelanto || 0)) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 14px;" v-if="venta.adelanto">
          <span>Adelanto</span>
          <span>- Bs. {{ Math.round(venta.adelanto) }}</span>
        </div>
        
        <div v-if="venta.metodoPago !== 'PRE-CUENTA'" style="font-size: 0.75rem; margin-top: 8px;">
            <div v-if="venta.metodoPago !== 'MIXTO'">MÉTODO: {{ venta.metodoPago }}</div>
            <div v-if="venta.metodoPago === 'MIXTO'">
                <div style="font-weight:bold">PAGO MIXTO:</div>
                <div>Efectivo: Bs. {{ Math.round(venta.montoEfectivo) }}</div>
                <div>Tarjeta: Bs. {{ Math.round(venta.montoTarjeta) }}</div>
                <div>QR: Bs. {{ Math.round(venta.montoQR) }}</div>
            </div>
        </div>
        <hr style="border-top: 1px dashed #000; margin: 8px 0;">
      </div>

      <!-- MODO COMANDA -->
      <div v-if="venta.metodoPago === 'COMANDA'">
          <h2 style="text-align: center; font-weight: bold; font-size: 1.5rem; margin: 0;">--- COMANDA ---</h2>
          <hr style="border-top: 1px dashed #000; margin: 5px 0;">
          <div style="text-align: center; font-size: 1.5rem; font-weight: bold; margin-bottom: 5px;">
              <span v-if="venta.mesa">MESA #{{ venta.mesa }}</span>
              <span v-else-if="venta.tipo === 'LLEVAR' || venta.tipoOrden === 'LLEVAR'">LLEVAR #{{ venta.id || venta.idDelivery || venta.idVenta }}</span>
              <span v-else-if="venta.tipo === 'DELIVERY' || venta.tipoOrden === 'DELIVERY'">DELIVERY #{{ venta.id || venta.idDelivery || venta.idVenta }}</span>
          </div>
          <div v-if="venta.cliente" style="text-align: center; font-size: 0.85rem;">Cliente: {{ venta.cliente }}</div>
          <div style="text-align: center; font-size: 0.85rem; margin-bottom: 5px;">Hora: {{ venta.fecha | formatFecha }}</div>
          <hr style="border-top: 1px dashed #000; margin: 5px 0;">
          <div v-for="(ins, idx) in insumos" :key="idx" style="margin-bottom: 8px;">
              <div style="font-size: 1.25rem;"><span style="font-weight: bold;">{{ ins.cantidad }}x</span> <span style="font-weight: bold; text-transform: uppercase;">{{ ins.nombre }}</span></div>
              <div v-if="ins.caracteristicas" style="font-size: 1.1rem; margin-left: 1rem; border-left: 2px solid #000; padding-left: 5px; text-transform: uppercase;">{{ ins.caracteristicas }}</div>
              <div v-if="ins.resumenCombo" style="font-size: 1.1rem; margin-left: 1rem; font-weight: bold; border-left: 2px solid #000; padding-left: 5px; white-space: pre-line; text-transform: uppercase;">
                  {{ Utiles.formatearResumenCombo(ins.resumenCombo) }}
              </div>
          </div>
          <hr style="border-top: 1px dashed #000; margin: 5px 0;">
      </div>

    </div>
  </section>
</template>

<script>
import Utiles from "../../Servicios/Utiles";

export default {
  name: "Ticket",
  props: ["venta", "insumos", "datosLocal", "logo", "noImprimir"],

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
        margin: 0;
      }
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body { 
        margin: 0; 
        padding: 0; 
        font-family: 'Courier New', Courier, monospace; 
        width: 72mm; 
        margin-left: auto;
        margin-right: auto;
        padding-left: 2mm;
        padding-right: 2mm;
        padding-top: 4mm;
        -webkit-print-color-adjust: exact;
        color: #000;
      }
    `,
  }),

  mounted() {
    if (!this.noImprimir) {
      this.imprimir();
    }
  },

  methods: {
    imprimir() {
      const zona = document.getElementById("comprobante");
      if (!zona) return;
      const html = zona.innerHTML;
      
      // Número de copias (por defecto 2 para comandas, 1 para tickets normales)
      const copias = this.venta.metodoPago === 'COMANDA' ? 3 : 1;
      const htmlFinal = Array(copias).fill(html).join('<div style="page-break-after: always; margin-top: 15px;"></div>');

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
            ${htmlFinal}
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
