<template>
  <section>
    <nav class="level mb-4">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="file-document-multiple-outline"
                    size="is-large"
                    type="is-primary"></b-icon>
            Historial de Notas de Venta
          </p>
        </div>
      </div>
      <div class="level-right">
        <div class="level-item">
          <b-select v-model="perPage" size="is-small">
            <option value="5">5 por página</option>
            <option value="10">10 por página</option>
            <option value="15">15 por página</option>
            <option value="20">20 por página</option>
          </b-select>
        </div>
        <div class="level-item">
          <b-button type="is-primary"
                    icon-left="plus"
                    tag="router-link"
                    :to="{ name: 'Factura' }">
            Nueva Nota
          </b-button>
        </div>
      </div>
    </nav>

    <!-- Filtros -->
    <div class="box mb-4">
      <b-field grouped
               group-multiline>
        <b-field label="Desde"
                 expanded>
          <b-datepicker v-model="filtros.desde"
                        placeholder="Fecha inicio"
                        icon="calendar"
                        :max-date="hoy"></b-datepicker>
        </b-field>
        <b-field label="Hasta"
                 expanded>
          <b-datepicker v-model="filtros.hasta"
                        placeholder="Fecha fin"
                        icon="calendar"
                        :max-date="hoy"></b-datepicker>
        </b-field>
        <b-field label="NIT/CI Comprador"
                 expanded>
          <b-input v-model="filtros.nitComprador"
                   placeholder="Buscar por NIT o CI"
                   icon="magnify"
                   @keyup.enter.native="cargar"></b-input>
        </b-field>
        <b-field label="Estado">
          <b-select v-model="filtros.estado">
            <option value="">Todos</option>
            <option value="EMITIDA">Emitidas</option>
            <option value="ANULADA">Anuladas</option>
          </b-select>
        </b-field>
        <b-field class="is-align-self-flex-end">
          <b-button type="is-primary"
                    icon-left="magnify"
                    @click="cargar">
            Buscar
          </b-button>
        </b-field>
      </b-field>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="columns mb-2"
         v-if="facturas.length > 0">
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">Total facturas</p>
          <p class="title is-4">{{ facturas.length }}</p>
        </div>
      </div>
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">Monto total acumulado</p>
          <p class="title is-4 has-text-success">Bs. {{ formatNum(totalFacturado) }}</p>
        </div>
      </div>
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">Impuesto estimado (13%)</p>
          <p class="title is-4 has-text-info">Bs. {{ formatNum(totalIVA) }}</p>
        </div>
      </div>
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">Facturas anuladas</p>
          <p class="title is-4 has-text-danger">{{ totalAnuladas }}</p>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="box">
    <b-table :data="facturas"
             :loading="cargando"
             :paginated="true"
             :per-page="perPage"
             :current-page.sync="currentPage"
             bordered
             narrowed
             striped
             hoverable
             detailed
             detail-key="id">
      <b-table-column field="numero"
                      label="N° Nota"
                      sortable
                      numeric
                      v-slot="props">
        <strong>{{ String(props.row.numero).padStart(7, '0') }}</strong>
      </b-table-column>

      <b-table-column field="fechaHora"
                      label="Fecha / Hora"
                      sortable
                      v-slot="props">
        {{ formatFecha(props.row.fechaHora) }}
      </b-table-column>

      <b-table-column field="nitComprador"
                      label="NIT / CI"
                      searchable
                      v-slot="props">
        {{ props.row.nitComprador }}
      </b-table-column>

      <b-table-column field="nombreComprador"
                      label="Comprador"
                      searchable
                      v-slot="props">
        {{ props.row.nombreComprador }}
      </b-table-column>

      <b-table-column field="metodoPago"
                      label="Método"
                      sortable
                      v-slot="props">
        <b-tag type="is-info is-light">{{ props.row.metodoPago || 'EFECTIVO' }}</b-tag>
      </b-table-column>

      <b-table-column field="total"
                      label="Total (Bs)"
                      sortable
                      numeric
                      v-slot="props">
        <strong>Bs. {{ formatNum(props.row.total) }}</strong>
      </b-table-column>

      <b-table-column field="iva"
                      label="IVA 13% (Bs)"
                      numeric
                      v-slot="props">
        <span class="has-text-grey">Bs. {{ formatNum(props.row.iva) }}</span>
      </b-table-column>

      <b-table-column field="usuarioNombre"
                      label="Emitió"
                      searchable
                      v-slot="props">
        {{ props.row.usuarioNombre || '-' }}
      </b-table-column>

      <b-table-column field="estado"
                      label="Estado"
                      centered
                      v-slot="props">
        <b-tag :type="props.row.estado === 'ANULADA' ? 'is-danger' : 'is-success'">
          {{ props.row.estado }}
        </b-tag>
      </b-table-column>

      <b-table-column label="Acciones"
                      v-slot="props">
        <div class="buttons">
          <b-button size="is-small"
                    type="is-info"
                    icon-left="printer"
                    title="Reimprimir"
                    @click="reimprimir(props.row)"></b-button>
          <b-button v-if="props.row.estado === 'EMITIDA'"
                    size="is-small"
                    type="is-danger is-light"
                    icon-left="cancel"
                    title="Anular"
                    @click="anular(props.row)"></b-button>
        </div>
      </b-table-column>

      <!-- Detalle expandible: ítems -->
      <template #detail="props">
        <b-table :data="props.row.items"
                 narrowed
                 class="is-size-7">
          <b-table-column field="cantidad"
                          label="Cant."
                          v-slot="p">{{ p.row.cantidad }}</b-table-column>
          <b-table-column field="descripcion"
                          label="Descripción"
                          v-slot="p">{{ p.row.descripcion }}</b-table-column>
          <b-table-column field="precioUnitario"
                          label="P. Unit. (Bs)"
                          numeric
                          v-slot="p">{{ formatNum(p.row.precioUnitario) }}</b-table-column>
          <b-table-column field="descuento"
                          label="Desc. (Bs)"
                          numeric
                          v-slot="p">{{ formatNum(p.row.descuento) }}</b-table-column>
          <b-table-column field="subtotal"
                          label="Subtotal (Bs)"
                          numeric
                          v-slot="p"><strong>{{ formatNum(p.row.subtotal) }}</strong></b-table-column>
        </b-table>
        <div v-if="props.row.nota"
             class="mt-2 is-size-7">
          <strong>Nota:</strong> {{ props.row.nota }}
        </div>
        <div v-if="props.row.codigoControl"
             class="mt-1 is-size-7">
          <strong>Cód. Control:</strong> {{ props.row.codigoControl }}
        </div>
      </template>

      <template #empty>
        <div class="has-text-centered py-4 has-text-grey">
          <b-icon icon="file-document-multiple-outline"
                  size="is-large"></b-icon>
          <p>No se encontraron notas de venta</p>
        </div>
      </template>
    </b-table>
    </div>

  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

const CONFIG_KEY = 'botanero_factura_config'

export default {
  name: 'HistorialFacturas',

  data() {
    const hoy = new Date()
    const hace30 = new Date()
    hace30.setDate(hoy.getDate() - 30)
    return {
      cargando: false,
      facturas: [],
      hoy,
      perPage: 10,
      currentPage: 1,
      filtros: {
        desde: hace30,
        hasta: hoy,
        nitComprador: '',
        estado: '',
      },
    }
  },

  computed: {
    totalFacturado() {
      return this.facturas
        .filter(f => f.estado === 'EMITIDA')
        .reduce((acc, f) => acc + parseFloat(f.total || 0), 0)
    },
    totalIVA() {
      return this.facturas
        .filter(f => f.estado === 'EMITIDA')
        .reduce((acc, f) => acc + parseFloat(f.iva || 0), 0)
    },
    totalAnuladas() {
      return this.facturas.filter(f => f.estado === 'ANULADA').length
    },
  },

  mounted() {
    this.cargar()
  },

  methods: {
    cargar() {
      this.cargando = true
      const payload = {}
      if (this.filtros.desde) payload.desde = this.filtros.desde.toISOString().substring(0, 10)
      if (this.filtros.hasta) payload.hasta = this.filtros.hasta.toISOString().substring(0, 10)
      if (this.filtros.nitComprador) payload.nitComprador = this.filtros.nitComprador
      if (this.filtros.estado) payload.estado = this.filtros.estado

      HttpService.registrar(payload, 'obtener_facturas.php')
        .then(datos => {
          this.facturas = Array.isArray(datos) ? datos : []
          this.cargando = false
        })
        .catch(() => {
          this.cargando = false
          this.$toast({ message: 'Error al cargar facturas', type: 'is-danger' })
        })
    },

    anular(factura) {
      this.$buefy.dialog.confirm({
        title: `Anular Nota N° ${String(factura.numero).padStart(7, '0')}`,
        message: '¿Seguro que deseas anular esta nota? El número quedará registrado como ANULADA.',
        confirmText: 'Sí, anular',
        cancelText: 'Cancelar',
        type: 'is-danger',
        hasIcon: true,
        onConfirm: () => {
          HttpService.registrar({ id: factura.id }, 'anular_factura.php')
            .then(res => {
              if (res && res.ok) {
                this.$toast({ message: 'Factura anulada', type: 'is-success' })
                this.cargar()
              } else {
                this.$toast({ message: 'Error al anular', type: 'is-danger' })
              }
            })
        },
      })
    },

    reimprimir(factura) {
      const config = JSON.parse(localStorage.getItem(CONFIG_KEY) || '{}')
      const c = config
      const f = factura
      const numFactura = String(f.numero).padStart(7, '0')

      const dt = new Date(f.fechaHora)
      const pad = n => String(n).padStart(2, '0')
      const fechaDisplay = `${pad(dt.getDate())}/${pad(dt.getMonth() + 1)}/${dt.getFullYear()}`
      const horaDisplay = `${pad(dt.getHours())}:${pad(dt.getMinutes())}`

      const esc = str => String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      const fmt = n => Math.round(parseFloat(n || 0))

      const filas = (f.items || []).map(i => `
        <tr>
          <td class="center">${i.cantidad}</td>
          <td>${esc(i.descripcion)}</td>
          <td class="right">${fmt(i.precioUnitario)}</td>
          <td class="right">${parseFloat(i.descuento) > 0 ? fmt(i.descuento) : '—'}</td>
          <td class="right"><strong>${fmt(i.subtotal)}</strong></td>
        </tr>`).join('')

      const anulada = f.estado === 'ANULADA'
        ? `<div style="position:absolute;top:40%;left:5%;font-size:60px;color:rgba(200,0,0,0.15);font-weight:bold;transform:rotate(-30deg);pointer-events:none;">ANULADA</div>`
        : ''

      const html = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nota de Venta N° ${numFactura}</title>
  <style>
    @page { size: 80mm auto; margin: 0; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; width: 80mm; padding: 2mm 3mm; }
    
    .ticket { width: 72mm; margin: 0 auto; text-align: center; position: relative; }
    .header-nota { font-size: 14px; font-weight: bold; border-bottom: 1px double #000; margin-bottom: 4px; padding-bottom: 2px; }
    .empresa h1 { font-size: 15px; font-weight: bold; text-transform: uppercase; margin: 2px 0; }
    .empresa p { font-size: 11px; margin: 1px 0; line-height: 1.2; }
    
    .doc-box { margin: 4mm 0; text-align: left; font-size: 11px; line-height: 1.4; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 2mm 0; }
    .doc-box strong { display: inline-block; width: 25mm; }
    
    table { width: 100%; border-collapse: collapse; margin: 3mm 0; font-size: 11px; }
    th { border-bottom: 1px solid #000; padding: 2px 0; text-align: left; }
    td { padding: 2px 0; vertical-align: top; text-align: left; }
    .right { text-align: right; }
    .center { text-align: center; }
    
    .totales { margin-top: 2mm; text-align: right; line-height: 1.6; border-top: 1px solid #000; padding-top: 1mm; }
    .fila-total { display: flex; justify-content: space-between; }
    .total-grande { font-size: 14px; font-weight: bold; border-top: 1px double #000; margin-top: 1mm; padding-top: 1mm; }
    .anulada-label { position: absolute; top: 30%; left: 0; width: 100%; font-size: 40px; color: rgba(255,0,0,0.2); font-weight: bold; transform: rotate(-20deg); text-align: center; pointer-events: none; }
    
    .leyenda { margin-top: 5mm; font-size: 10px; text-align: center; font-style: italic; }
  </style>
</head>
<body>
  <div class="ticket">
    ${f.estado === 'ANULADA' ? '<div class="anulada-label">ANULADA</div>' : ''}
    <div class="header-nota">--- NOTA DE VENTA ---</div>
    <div class="empresa">
      <h1>${esc(c.razonSocial || 'RESTO')}</h1>
      <p>${esc(c.direccion || '')}</p>
      <p>${esc(c.ciudad || '')} ${c.telefono ? '- Tel: ' + esc(c.telefono) : ''}</p>
    </div>

    <div class="doc-box">
      <p><strong>N° NOTA:</strong> ${numFactura}</p>
      <p><strong>FECHA:</strong> ${fechaDisplay} ${horaDisplay}</p>
      <p><strong>CLIENTE:</strong> ${esc(f.nombreComprador || 'S/N')}</p>
      <p><strong>NIT/CI:</strong> ${esc(f.nitComprador || '—————')}</p>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:10mm" class="center">Cant</th>
          <th>Detalle</th>
          <th style="width:18mm" class="right">Subt.</th>
        </tr>
      </thead>
      <tbody>
        ${(f.items || []).map(i => `
          <tr>
            <td class="center">${i.cantidad}</td>
            <td style="text-transform:uppercase">${esc(i.descripcion)}</td>
            <td class="right">${fmt(i.subtotal)}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>

    <div class="totales">
      ${parseFloat(f.descuentos) > 0 ? `<div class="fila-total"><span>Subtotal:</span><span>${fmt(f.subtotal)}</span></div>` : ''}
      ${parseFloat(f.descuentos) > 0 ? `<div class="fila-total"><span>Descuento:</span><span>-${fmt(f.descuentos)}</span></div>` : ''}
      <div class="fila-total total-grande">
        <span>TOTAL:</span>
        <span>Bs. ${fmt(f.total)}</span>
      </div>
      <div class="fila-total" style="border-top: 1px dashed #000; margin-top: 2mm; padding-top: 1mm;">
        <span>MÉTODO DE PAGO:</span>
        <span>${f.metodoPago || 'EFECTIVO'}</span>
      </div>
    </div>

    ${f.nota ? `<div style="text-align:left; margin-top:3mm; font-size:10px;"><strong>Nota:</strong> ${esc(f.nota)}</div>` : ''}

    <div class="leyenda">
      <p>¡Gracias por su preferencia!</p>
      <p>Este documento no tiene validez fiscal</p>
    </div>
  </div>
  <script>window.onload=function(){window.print(); setTimeout(window.close, 1500);}<\/script>
</body></html>`

      const w = window.open('', '_blank', 'width=850,height=1000')
      if (w) { w.document.write(html); w.document.close() }
    },

    formatNum(n) {
      return Math.round(parseFloat(n || 0))
    },

    formatFecha(str) {
      if (!str) return '-'
      const d = new Date(str)
      if (isNaN(d)) return str
      const pad = n => String(n).padStart(2, '0')
      return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
    },
  },
}
</script>
