<template>
  <section>
    <nav class="level mb-4">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="file-document-multiple-outline"
                    size="is-large"
                    type="is-primary"></b-icon>
            Historial de Facturas
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
            Nueva Factura
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
          <p class="heading">Monto total facturado</p>
          <p class="title is-4 has-text-success">Bs {{ formatNum(totalFacturado) }}</p>
        </div>
      </div>
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">IVA total (13%)</p>
          <p class="title is-4 has-text-info">Bs {{ formatNum(totalIVA) }}</p>
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
                      label="N° Factura"
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

      <b-table-column field="total"
                      label="Total (Bs)"
                      sortable
                      numeric
                      v-slot="props">
        <strong>Bs {{ formatNum(props.row.total) }}</strong>
      </b-table-column>

      <b-table-column field="iva"
                      label="IVA 13% (Bs)"
                      numeric
                      v-slot="props">
        <span class="has-text-grey">Bs {{ formatNum(props.row.iva) }}</span>
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
          <p>No se encontraron facturas</p>
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
        title: `Anular Factura N° ${String(factura.numero).padStart(7, '0')}`,
        message: '¿Seguro que deseas anular esta factura? El número quedará registrado como ANULADA en el libro.',
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
      const fmt = n => parseFloat(n || 0).toFixed(2)

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
  <title>Factura N° ${numFactura}</title>
  <style>
    @page{size:A4;margin:12mm 15mm}*{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#000}
    .factura{max-width:180mm;margin:0 auto;border:2px solid #000;padding:8mm;position:relative}
    .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5mm}
    .empresa h1{font-size:16px;font-weight:bold;text-transform:uppercase;margin-bottom:3px}
    .empresa p{margin:2px 0;font-size:10px}.empresa .nit{font-size:13px;font-weight:bold;margin:4px 0}
    .doc-box{border:2px solid #000;padding:5mm;text-align:center;min-width:70mm}
    .doc-box .titulo{font-size:22px;font-weight:bold;text-transform:uppercase;letter-spacing:3px;margin:2px 0}
    .doc-box .num{font-size:18px;font-weight:bold;margin:4px 0}
    .doc-box p{font-size:10px;margin:2px 0}.doc-box .aut{font-size:8px;margin-top:5px}
    .sep{border-top:1px solid #000;margin:4mm 0}.sep-doble{border-top:2px solid #000;margin:4mm 0}
    .comprador .row{display:flex;gap:5mm;align-items:center;margin-bottom:3px}
    .comprador .campo{font-weight:bold;white-space:nowrap}
    .comprador .valor{border-bottom:1px solid #aaa;flex:1;min-height:14px;padding-bottom:1px}
    .detalle table{width:100%;border-collapse:collapse}
    .detalle thead tr{background:#000;color:#fff}
    .detalle th{padding:3px 5px;font-size:10px;text-align:left}
    .detalle td{border:1px solid #ccc;padding:3px 5px;font-size:11px;vertical-align:top}
    .center{text-align:center}.right{text-align:right}
    .totales-table{margin-left:auto;width:80mm;border-collapse:collapse}
    .totales-table td{padding:2px 5px;font-size:11px}
    .totales-table .linea-total td{font-size:14px;font-weight:bold;border-top:2px solid #000;padding-top:4px}
    .leyenda{margin-top:5mm;font-size:8px;text-align:center;font-style:italic;border-top:1px dashed #000;padding-top:3mm;color:#333}
    @media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
  </style>
</head>
<body>
<div class="factura">
  ${anulada}
  <div class="header">
    <div class="empresa">
      <h1>${esc(c.razonSocial || 'NOMBRE DEL NEGOCIO')}</h1>
      <p class="nit">NIT: ${esc(c.nit || '—————')}</p>
      <p><strong>Actividad:</strong> ${esc(c.actividad || '—')}</p>
      <p><strong>Dirección:</strong> ${esc(c.direccion || '—')}</p>
      <p><strong>Ciudad:</strong> ${esc(c.ciudad || '—')}</p>
      ${c.telefono ? `<p><strong>Tel.:</strong> ${esc(c.telefono)}</p>` : ''}
    </div>
    <div class="doc-box">
      <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#555">Documento Fiscal</div>
      <div class="titulo">FACTURA</div>
      <div class="num">N° ${numFactura}</div>
      ${c.numAutorizacion ? `<div class="aut"><strong>Autorización:</strong> ${esc(c.numAutorizacion)}</div>` : ''}
      ${c.fechaLimite ? `<p style="font-size:8px">Límite emisión: ${c.fechaLimite}</p>` : ''}
      <p><strong>Fecha:</strong> ${fechaDisplay}</p>
      <p><strong>Hora:</strong> ${horaDisplay}</p>
      ${f.codigoControl ? `<p style="font-size:8px;margin-top:3px"><strong>Cod. Control:</strong> ${esc(f.codigoControl)}</p>` : ''}
    </div>
  </div>
  <div class="sep-doble"></div>
  <div class="comprador">
    <div class="row">
      <span class="campo">NIT / CI:</span>
      <span class="valor">${esc(f.nitComprador)}</span>
      <span class="campo" style="margin-left:5mm">Nombre / Razón Social:</span>
      <span class="valor">${esc(f.nombreComprador)}</span>
    </div>
  </div>
  <div class="sep"></div>
  <div class="detalle">
    <table>
      <thead>
        <tr>
          <th style="width:12mm" class="center">Cant.</th>
          <th>Descripción</th>
          <th style="width:25mm" class="right">P. Unit. (Bs)</th>
          <th style="width:22mm" class="right">Descuento</th>
          <th style="width:25mm" class="right">Subtotal (Bs)</th>
        </tr>
      </thead>
      <tbody>${filas}</tbody>
    </table>
  </div>
  <div style="margin-top:4mm">
    <table class="totales-table">
      <tr><td>Subtotal bruto:</td><td class="right">Bs ${fmt(f.subtotal)}</td></tr>
      ${parseFloat(f.descuentos) > 0 ? `<tr><td>Descuentos:</td><td class="right" style="color:#c00">- Bs ${fmt(f.descuentos)}</td></tr>` : ''}
      <tr style="color:#555"><td>Importe Base Créd. Fiscal:</td><td class="right">Bs ${fmt(f.baseCredito)}</td></tr>
      <tr style="color:#555"><td>IVA (13%):</td><td class="right">Bs ${fmt(f.iva)}</td></tr>
      <tr class="linea-total"><td>TOTAL A PAGAR:</td><td class="right">Bs ${fmt(f.total)}</td></tr>
    </table>
  </div>
  ${f.nota ? `<div class="sep"></div><p style="font-size:10px;margin-top:3mm"><strong>Nota:</strong> ${esc(f.nota)}</p>` : ''}
  <div class="leyenda">
    <p>"El IVA incluido en esta factura, podrá ser acreditado siempre que los datos del comprador se encuentren registrados en su declaración de impuestos conforme a la Ley 843."</p>
    <p style="margin-top:2mm">${esc(c.ciudad || 'Bolivia')} — Este documento es válido para crédito fiscal</p>
  </div>
</div>
<script>window.onload=function(){window.print()}<\/script>
</body></html>`

      const w = window.open('', '_blank', 'width=850,height=1000')
      if (w) { w.document.write(html); w.document.close() }
    },

    formatNum(n) {
      return parseFloat(n || 0).toFixed(2)
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
