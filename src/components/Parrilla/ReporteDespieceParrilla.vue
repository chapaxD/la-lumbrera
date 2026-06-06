<template>
  <section class="section despiece-reporte-print">
    <div class="mb-4">
      <p class="title is-4 has-text-weight-bold">
        <b-icon icon="clipboard-list-outline" type="is-primary"></b-icon>
        Reporte / control de despiece de parrilla
      </p>
      <p class="subtitle is-6">
        Auditoría de recepción y reparto de materia prima. Usá <strong>Imprimir</strong> o <strong>PDF</strong> abajo
        para control y archivo.
        <span v-if="esAdmin" class="is-block mt-2">Si cargaste mal un despiece, usá <strong>Quitar registro</strong> en
          la tabla y volvé a registrar en <em>Registrar despiece</em>.</span>
      </p>
    </div>

    <div class="box no-print">
      <div class="columns is-multiline is-vcentered">
        <div class="column is-12-tablet">
          <b-field grouped group-multiline>
            <b-field label="Fecha inicio">
              <b-input type="date" v-model="fechaInicio" />
            </b-field>
            <b-field label="Fecha fin">
              <b-input type="date" v-model="fechaFin" />
            </b-field>
            <b-field class="is-align-self-flex-end">
              <div class="buttons">
                <b-button type="is-primary" icon-left="filter" :loading="cargando" @click="cargarDespiece">
                  Filtrar
                </b-button>
                <b-button type="is-light" icon-left="refresh" :loading="cargando" @click="cargarDespiece">
                  Actualizar
                </b-button>
                <b-button type="is-info" icon-left="printer" @click="imprimir">
                  Imprimir
                </b-button>
                <b-button type="is-danger" icon-left="file-pdf-box" @click="exportarPdf">
                  Descargar PDF
                </b-button>
              </div>
              <p class="is-size-7 has-text-grey mt-2 mb-0">
                Si no ves datos, elegí fechas y pulsá <strong>Filtrar</strong>. Los botones siempre están aquí.
              </p>
            </b-field>
          </b-field>
        </div>
      </div>
    </div>

    <p v-if="leyendaPeriodo" class="is-size-7 has-text-grey mb-2 no-print">{{ leyendaPeriodo }}</p>

    <div class="despiece-tabla-print">

      <p v-if="despiece.length === 0 && !cargando" class="has-text-centered has-text-grey mt-5">
        No hay registros en el periodo seleccionado.
      </p>

      <div v-for="lote in despiece" :key="lote.id" class="card mb-4 lote-card">

        <!-- Encabezado del lote -->
        <div class="card-header lote-header px-4 py-3" style="display:block">
          <div class="is-flex is-align-items-center is-justify-content-space-between is-flex-wrap-wrap" style="gap:8px">
            <div class="is-flex is-align-items-center" style="gap:12px; flex-wrap:wrap">
              <span class="tag is-dark is-medium">{{ formatearFecha(lote.fecha) }}</span>
              <span class="has-text-weight-bold is-size-5">{{ lote.materia_prima }}</span>
              <span class="tag is-primary is-light">Recibido: {{ parseFloat(lote.total_kg_recibido).toFixed(3) }} kg</span>
              <span v-if="parseFloat(lote.sobrante_kg) > 0" class="tag is-info is-light has-text-weight-bold">
                🥩 Sin cortar: {{ parseFloat(lote.sobrante_kg).toFixed(3) }} kg
              </span>
              <span class="has-text-grey is-size-7">
                <b-icon icon="account" size="is-small"></b-icon> {{ lote.usuario }}
              </span>
            </div>
            <b-button v-if="esAdmin" type="is-danger is-light" size="is-small" icon-left="delete-outline"
              class="no-print" :loading="eliminandoId === lote.id" @click="confirmarEliminar(lote)">
              Quitar
            </b-button>
          </div>

          <!-- Resumen rápido del lote -->
          <div class="is-flex mt-2" style="gap:16px; flex-wrap:wrap">
            <span class="is-size-7 has-text-grey">
              Cortes: <strong>{{ lote.lineas ? lote.lineas.length : 0 }}</strong>
            </span>
            <span class="is-size-7 has-text-grey">
              Porciones: <strong class="has-text-primary">{{ totalPorcionesLote(lote) }}</strong>
            </span>
            <span class="is-size-7 has-text-grey">
              Desperdicio: <strong class="has-text-danger">{{ totalDespLote(lote) }} g</strong>
            </span>
            <span v-if="totalSobrasLote(lote) > 0" class="is-size-7 has-text-grey">
              Sobras cortes: <strong class="has-text-warning-dark">{{ totalSobrasLote(lote) }} g</strong>
            </span>
            <span v-if="parseFloat(lote.sobrante_kg) > 0 || totalSobrasLote(lote) > 0"
              class="is-size-7 has-text-success-dark has-text-weight-bold">
              ♻ Reutilizable mañana:
              {{ (parseFloat(lote.sobrante_kg || 0) + totalSobrasLote(lote) / 1000).toFixed(3) }} kg
            </span>
          </div>
        </div>

        <!-- Subtabla de cortes -->
        <div class="card-content p-0" v-if="lote.lineas && lote.lineas.length">
          <table class="table is-fullwidth is-narrow is-striped mb-0" style="font-size:0.88rem">
            <thead>
              <tr class="has-background-white-bis">
                <th class="pl-4">Corte</th>
                <th class="has-text-centered">Kg asig.</th>
                <th class="has-text-centered">Porciones</th>
                <th class="has-text-centered">g / porc.</th>
                <th class="has-text-centered">Desperdicio</th>
                <th class="has-text-centered">Sobras corte</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ln in lote.lineas" :key="ln.id">
                <td class="pl-4 has-text-weight-semibold">{{ ln.materia_prima }}</td>
                <td class="has-text-centered">{{ parseFloat(ln.kg_asignado).toFixed(3) }} kg</td>
                <td class="has-text-centered has-text-primary has-text-weight-bold">
                  {{ ln.porciones > 0 ? ln.porciones : ((ln.porciones_250 || 0) + (ln.porciones_350 || 0)) }}
                </td>
                <td class="has-text-centered">
                  <span v-if="ln.gramos_porcion > 0">{{ ln.gramos_porcion }} g</span>
                  <span v-else class="has-text-grey">250/350g</span>
                </td>
                <td class="has-text-centered">
                  <span v-if="parseInt(ln.desperdicio_g) > 0" class="has-text-danger">{{ ln.desperdicio_g }} g</span>
                  <span v-else class="has-text-grey-light">—</span>
                </td>
                <td class="has-text-centered">
                  <span v-if="parseInt(ln.sobras_g) > 0" class="tag is-warning is-light">{{ ln.sobras_g }} g</span>
                  <span v-else class="has-text-grey-light">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="card-content has-text-grey is-size-7 py-2">Sin líneas de corte registradas.</div>

      </div>
    </div>

    <p class="is-size-7 has-text-grey mt-3 no-print print-footer-hint">
      Documento para control de cuadre de kilos y trazabilidad por usuario e insumo del catálogo.
    </p>

    <!-- Panel de totales -->
    <div v-if="tieneDatos" class="box mt-4">
      <p class="title is-6 mb-3 has-text-grey-dark">
        <b-icon icon="sigma" class="mr-1"></b-icon> Totales del periodo
      </p>
      <div class="columns is-mobile is-multiline">
        <div class="column is-narrow">
          <div class="has-text-grey is-size-7 mb-1">Total kg recibidos</div>
          <div class="has-text-weight-bold is-size-5">{{ totales.kgRecibido.toFixed(3) }} kg</div>
        </div>
        <div class="column is-narrow">
          <div class="has-text-grey is-size-7 mb-1">Total porciones cortadas</div>
          <div class="has-text-weight-bold is-size-5 has-text-primary">{{ totales.porciones }}</div>
        </div>
        <div class="column is-narrow">
          <div class="has-text-grey is-size-7 mb-1">Total desperdicio</div>
          <div class="has-text-weight-bold is-size-5 has-text-danger">{{ (totales.desperdicioG / 1000).toFixed(3) }} kg</div>
        </div>
        <div class="column is-narrow">
          <div class="has-text-grey is-size-7 mb-1">Sobras de cortes</div>
          <div class="has-text-weight-bold is-size-5 has-text-warning-dark">{{ (totales.sobrasG / 1000).toFixed(3) }} kg</div>
        </div>
        <div class="column is-narrow">
          <div class="has-text-grey is-size-7 mb-1">Piezas sin cortar (sobrante)</div>
          <div class="has-text-weight-bold is-size-5 has-text-info">{{ totales.sobranteLoteKg.toFixed(3) }} kg</div>
        </div>
        <div class="column is-narrow" style="border-left: 3px solid #48c78e; padding-left: 1rem">
          <div class="has-text-grey is-size-7 mb-1">Total reutilizable acumulado</div>
          <div class="has-text-weight-bold is-size-4 has-text-success">
            {{ (totales.sobranteLoteKg + totales.sobrasG / 1000).toFixed(3) }} kg
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService';

export default {
  name: 'ReporteDespieceParrilla',
  data() {
    return {
      despiece: [],
      fechaInicio: '',
      fechaFin: '',
      cargando: false,
      eliminandoId: null
    };
  },
  computed: {
    esAdmin() {
      return typeof localStorage !== 'undefined' && localStorage.getItem('rol') === 'admin';
    },
    tieneDatos() {
      return Array.isArray(this.despiece) && this.despiece.length > 0;
    },
    leyendaPeriodo() {
      if (this.fechaInicio && this.fechaFin) {
        return `Periodo: ${this.fechaInicio} al ${this.fechaFin}`;
      }
      if (this.fechaInicio) return `Fecha: ${this.fechaInicio}`;
      return 'Sin filtro de fechas (todos los registros disponibles)';
    },
    totales() {
      let kgRecibido = 0, porciones = 0, desperdicioG = 0, sobrasG = 0, sobranteLoteKg = 0;
      this.despiece.forEach((lote) => {
        kgRecibido += parseFloat(lote.total_kg_recibido) || 0;
        sobranteLoteKg += parseFloat(lote.sobrante_kg) || 0;
        (lote.lineas || []).forEach((ln) => {
          porciones += ln.porciones > 0 ? (parseInt(ln.porciones) || 0)
            : ((parseInt(ln.porciones_250) || 0) + (parseInt(ln.porciones_350) || 0));
          desperdicioG += parseInt(ln.desperdicio_g) || 0;
          sobrasG += parseInt(ln.sobras_g) || 0;
        });
      });
      return { kgRecibido, porciones, desperdicioG, sobrasG, sobranteLoteKg };
    }
  },
  mounted() {
    this.cargarDespiece();
  },
  methods: {
    toast(msg, type) {
      if (this.$buefy && this.$buefy.toast) {
        this.$buefy.toast.open({ message: msg, type: type || 'is-info', duration: 4000 });
      } else {
        alert(msg);
      }
    },
    formatearFecha(fechaStr) {
      if (!fechaStr) return '';
      const d = new Date(fechaStr.replace(' ', 'T'));
      return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
        + ' ' + d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    },
    totalPorcionesLote(lote) {
      return (lote.lineas || []).reduce((acc, ln) => {
        return acc + (ln.porciones > 0 ? parseInt(ln.porciones) : ((parseInt(ln.porciones_250) || 0) + (parseInt(ln.porciones_350) || 0)));
      }, 0);
    },
    totalDespLote(lote) {
      return (lote.lineas || []).reduce((acc, ln) => acc + (parseInt(ln.desperdicio_g) || 0), 0);
    },
    totalSobrasLote(lote) {
      return (lote.lineas || []).reduce((acc, ln) => acc + (parseInt(ln.sobras_g) || 0), 0);
    },
    filasParaPdf() {
      const rows = [];
      this.despiece.forEach((lote) => {
        const sobrante = parseFloat(lote.sobrante_kg) > 0 ? parseFloat(lote.sobrante_kg).toFixed(3) + ' kg' : '—';
        if (lote.lineas && lote.lineas.length) {
          lote.lineas.forEach((ln) => {
            rows.push([
              String(lote.fecha || ''),
              String(lote.materia_prima || ''),
              String(lote.total_kg_recibido != null ? lote.total_kg_recibido : ''),
              sobrante,
              String(lote.usuario || ''),
              String(ln.id_insumo != null ? ln.id_insumo : '—'),
              String(ln.materia_prima || ''),
              String(ln.kg_asignado != null ? ln.kg_asignado : ''),
              String(ln.porciones > 0 ? ln.porciones : ((ln.porciones_250 || 0) + (ln.porciones_350 || 0))),
              String(ln.gramos_porcion > 0 ? ln.gramos_porcion + 'g' : '250/350g'),
              String(ln.desperdicio_g != null ? ln.desperdicio_g : ''),
              String(ln.sobras_g != null ? ln.sobras_g : '')
            ]);
          });
        } else {
          rows.push([
            String(lote.fecha || ''),
            String(lote.materia_prima || ''),
            String(lote.total_kg_recibido != null ? lote.total_kg_recibido : ''),
            sobrante,
            String(lote.usuario || ''),
            '—', '—', '—', '—', '—', '—', '—'
          ]);
        }
      });
      return rows;
    },
    textoPiePdf() {
      const t = this.totales;
      const reutilizable = (t.sobranteLoteKg + t.sobrasG / 1000).toFixed(3);
      return `Lotes: ${this.despiece.length} | Total recibido: ${t.kgRecibido.toFixed(3)} kg | Porciones: ${t.porciones} | Reutilizable: ${reutilizable} kg | ${this.leyendaPeriodo}`;
    },
    async exportarPdf() {
      if (!this.tieneDatos) {
        this.toast('No hay datos: filtrá fechas o registrá despiece primero.', 'is-warning');
        return;
      }

      const jsPDF = (await import('jspdf')).default;
      await import('jspdf-autotable');

      let datosLocal = null;
      try {
        const HttpService = (await import('../../Servicios/HttpService')).default;
        datosLocal = await HttpService.obtener('obtener_datos_local.php');
      } catch (e) { /* sin membrete */ }

      const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
      const PW = 210; // ancho A4
      const ML = 14;  // margen izquierdo
      const MR = 14;  // margen derecho
      const CW = PW - ML - MR; // ancho útil
      let y = 14;

      // ── Membrete ──────────────────────────────────────────────
      if (datosLocal && datosLocal.nombre) {
        doc.setFontSize(16);
        doc.setTextColor(40);
        doc.text(datosLocal.nombre, ML, y + 6);
        doc.setFontSize(8);
        doc.setTextColor(120);
        doc.text(`Tel: ${datosLocal.telefono || ''}`, ML, y + 12);
        y += 18;
      }

      // ── Título ────────────────────────────────────────────────
      doc.setFontSize(13);
      doc.setTextColor(30);
      doc.text('Control de Despiece de Parrilla', ML, y + 6);
      doc.setFontSize(8);
      doc.setTextColor(120);
      doc.text(`Generado: ${new Date().toLocaleString('es-AR')}   |   ${this.leyendaPeriodo}`, ML, y + 12);
      y += 20;

      // ── Lotes ─────────────────────────────────────────────────
      const COLOR_HEADER_BG = [41, 98, 163];   // azul oscuro
      const COLOR_HEADER_TXT = [255, 255, 255];
      const COLOR_INFO_BG = [240, 246, 255];
      const COLOR_REUTILIZABLE = [39, 174, 96]; // verde

      for (const lote of this.despiece) {
        const porcs  = this.totalPorcionesLote(lote);
        const desp   = this.totalDespLote(lote);
        const sobras = this.totalSobrasLote(lote);
        const sobranteLote = parseFloat(lote.sobrante_kg) || 0;
        const reutilizable = (sobranteLote + sobras / 1000).toFixed(3);

        // Estimar altura necesaria para este lote
        const filasLineas = lote.lineas ? lote.lineas.length : 0;
        const alturaEstimada = 28 + filasLineas * 7 + 16;
        if (y + alturaEstimada > 275) {
          doc.addPage();
          y = 14;
        }

        // ─ Encabezado del lote (barra azul) ─
        doc.setFillColor(...COLOR_HEADER_BG);
        doc.roundedRect(ML, y, CW, 9, 1, 1, 'F');
        doc.setFontSize(9);
        doc.setTextColor(...COLOR_HEADER_TXT);
        doc.setFont(undefined, 'bold');
        const fechaStr = this.formatearFecha(lote.fecha);
        doc.text(`${fechaStr}  —  ${lote.materia_prima}`, ML + 3, y + 6);
        doc.setFont(undefined, 'normal');
        y += 11;

        // ─ Fila de datos del lote ─
        doc.setFillColor(...COLOR_INFO_BG);
        doc.rect(ML, y, CW, 10, 'F');
        doc.setTextColor(50);
        doc.setFontSize(8);

        const col1 = ML + 3;
        const col2 = ML + 55;
        const col3 = ML + 108;
        const col4 = ML + 145;

        doc.setFont(undefined, 'normal');
        doc.text('Recibido:', col1, y + 4);
        doc.setFont(undefined, 'bold');
        doc.text(`${parseFloat(lote.total_kg_recibido).toFixed(3)} kg`, col1 + 18, y + 4);

        doc.setFont(undefined, 'normal');
        doc.text('Sin cortar:', col2, y + 4);
        doc.setFont(undefined, 'bold');
        if (sobranteLote > 0) {
          doc.setTextColor(41, 128, 185);
          doc.text(`${sobranteLote.toFixed(3)} kg`, col2 + 20, y + 4);
          doc.setTextColor(50);
        } else {
          doc.text('—', col2 + 20, y + 4);
        }

        doc.setFont(undefined, 'normal');
        doc.text('Porciones:', col3, y + 4);
        doc.setFont(undefined, 'bold');
        doc.text(`${porcs}`, col3 + 20, y + 4);

        doc.setFont(undefined, 'normal');
        doc.text('Usuario:', col4, y + 4);
        doc.setFont(undefined, 'bold');
        doc.text(`${lote.usuario || ''}`, col4 + 16, y + 4);

        // Segunda fila de datos
        doc.setFont(undefined, 'normal');
        doc.setTextColor(50);
        doc.text('Desperdicio:', col1, y + 8.5);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(180, 50, 50);
        doc.text(`${desp} g`, col1 + 23, y + 8.5);

        doc.setFont(undefined, 'normal');
        doc.setTextColor(50);
        doc.text('Sobras cortes:', col2, y + 8.5);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(180, 130, 0);
        doc.text(`${sobras} g`, col2 + 27, y + 8.5);

        if (parseFloat(reutilizable) > 0) {
          doc.setFont(undefined, 'bold');
          doc.setTextColor(...COLOR_REUTILIZABLE);
          doc.text(`Reutilizable manana: ${reutilizable} kg`, col3, y + 8.5);
        }

        doc.setTextColor(50);
        doc.setFont(undefined, 'normal');
        y += 12;

        // ─ Subtabla de cortes ─
        if (filasLineas > 0) {
          doc.autoTable({
            startY: y,
            margin: { left: ML, right: MR },
            head: [['Corte', 'Kg asig.', 'Porciones', 'g/porc.', 'Desperdicio', 'Sobras']],
            body: lote.lineas.map((ln) => [
              ln.materia_prima || '',
              `${parseFloat(ln.kg_asignado).toFixed(3)} kg`,
              ln.porciones > 0 ? ln.porciones : ((ln.porciones_250 || 0) + (ln.porciones_350 || 0)),
              ln.gramos_porcion > 0 ? `${ln.gramos_porcion} g` : '250/350g',
              parseInt(ln.desperdicio_g) > 0 ? `${ln.desperdicio_g} g` : '—',
              parseInt(ln.sobras_g) > 0 ? `${ln.sobras_g} g` : '—',
            ]),
            theme: 'striped',
            headStyles: { fillColor: [100, 140, 200], fontSize: 8, halign: 'center' },
            bodyStyles: { fontSize: 8 },
            columnStyles: {
              0: { cellWidth: 55 },
              1: { halign: 'center', cellWidth: 22 },
              2: { halign: 'center', cellWidth: 20 },
              3: { halign: 'center', cellWidth: 18 },
              4: { halign: 'center', cellWidth: 22 },
              5: { halign: 'center', cellWidth: 22 },
            },
            didParseCell(info) {
              if (info.section === 'body') {
                if (info.column.index === 4 && info.cell.text[0] !== '—') {
                  info.cell.styles.textColor = [180, 50, 50];
                }
                if (info.column.index === 5 && info.cell.text[0] !== '—') {
                  info.cell.styles.textColor = [180, 130, 0];
                }
                if (info.column.index === 2) {
                  info.cell.styles.fontStyle = 'bold';
                  info.cell.styles.textColor = [41, 98, 163];
                }
              }
            }
          });
          y = doc.lastAutoTable.finalY + 6;
        } else {
          doc.setFontSize(8);
          doc.setTextColor(150);
          doc.text('Sin líneas de corte.', ML + 3, y + 4);
          y += 8;
        }
      }

      // ── Panel de totales ────────────────────────────────────────
      const t = this.totales;
      const reutilizableTotal = (t.sobranteLoteKg + t.sobrasG / 1000).toFixed(3);

      if (y + 30 > 275) { doc.addPage(); y = 14; }

      doc.setDrawColor(39, 174, 96);
      doc.setLineWidth(0.5);
      doc.line(ML, y, PW - MR, y);
      y += 5;

      doc.setFontSize(10);
      doc.setFont(undefined, 'bold');
      doc.setTextColor(40);
      doc.text('TOTALES DEL PERIODO', ML, y);
      y += 6;

      doc.setFontSize(9);
      doc.setFont(undefined, 'normal');
      doc.setTextColor(60);
      doc.text(`Lotes registrados: ${this.despiece.length}`, ML, y);
      doc.text(`Total recibido: ${t.kgRecibido.toFixed(3)} kg`, ML + 50, y);
      doc.text(`Porciones cortadas: ${t.porciones}`, ML + 105, y);
      y += 6;
      doc.text(`Desperdicio total: ${(t.desperdicioG / 1000).toFixed(3)} kg`, ML, y);
      doc.text(`Sobras de cortes: ${(t.sobrasG / 1000).toFixed(3)} kg`, ML + 50, y);
      doc.text(`Piezas sin cortar: ${t.sobranteLoteKg.toFixed(3)} kg`, ML + 105, y);
      y += 7;

      doc.setFontSize(11);
      doc.setFont(undefined, 'bold');
      doc.setTextColor(...COLOR_REUTILIZABLE);
      doc.text(`TOTAL REUTILIZABLE ACUMULADO: ${reutilizableTotal} kg`, ML, y);

      doc.save('despiece_parrilla.pdf');
    },
    imprimir() {
      if (!this.tieneDatos) {
        this.toast('No hay datos para imprimir. Filtrá o cargá registros.', 'is-warning');
        return;
      }
      window.print();
    },
    async cargarDespiece() {
      this.cargando = true;
      try {
        let url = '/api/obtener_despiece_parrilla.php';
        const params = [];
        if (this.fechaInicio) params.push(`fechaInicio=${encodeURIComponent(this.fechaInicio)}`);
        if (this.fechaFin) params.push(`fechaFin=${encodeURIComponent(this.fechaFin)}`);
        if (params.length) url += '?' + params.join('&');
        const res = await fetch(url);
        this.despiece = await res.json();
      } finally {
        this.cargando = false;
      }
    },
    confirmarEliminar(lote) {
      if (!this.esAdmin || !lote || !lote.id) return;
      const msg = `Se eliminará el registro de despiece del ${lote.fecha} (${lote.total_kg_recibido} kg recibidos) y todas sus líneas. No se puede deshacer.`;
      if (this.$buefy && this.$buefy.dialog) {
        this.$buefy.dialog.confirm({
          title: 'Quitar registro mal cargado',
          message: msg,
          confirmText: 'Eliminar',
          type: 'is-danger',
          hasIcon: true,
          onConfirm: () => this.ejecutarEliminar(lote)
        });
      } else if (window.confirm(msg)) {
        this.ejecutarEliminar(lote);
      }
    },
    async ejecutarEliminar(lote) {
      this.eliminandoId = lote.id;
      try {
        const res = await HttpService.registrar({ id_lote: lote.id }, 'eliminar_despiece_parrilla.php');
        if (res && res.ok) {
          this.toast('Registro eliminado. Podés cargar uno nuevo en Registrar despiece.', 'is-success');
          await this.cargarDespiece();
        } else {
          this.toast((res && res.error) || 'No se pudo eliminar', 'is-danger');
        }
      } catch (e) {
        this.toast('Error al eliminar', 'is-danger');
      } finally {
        this.eliminandoId = null;
      }
    }
  }
};
</script>

<style scoped>
.lote-card {
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e8e8e8;
}
.lote-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e8e8e8;
}
.table-container {
  overflow-x: auto;
}
</style>

<style>
@media print {

  .no-print,
  .navbar,
  .app-shell>footer,
  .footer-slim {
    display: none !important;
  }

  .app-shell-main.container {
    width: 100% !important;
    max-width: 100% !important;
    padding: 0.5rem !important;
  }

  .despiece-reporte-print .table {
    font-size: 8pt;
  }

  .despiece-tabla-print {
    overflow: visible !important;
  }

  body {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}
</style>
