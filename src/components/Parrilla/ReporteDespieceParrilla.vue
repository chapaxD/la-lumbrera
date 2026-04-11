<template>
  <section class="section despiece-reporte-print">
    <div class="mb-4">
      <p class="title is-4 has-text-weight-bold">
        <b-icon icon="clipboard-list-outline" type="is-primary"></b-icon>
        Reporte / control de despiece de parrilla
      </p>
      <p class="subtitle is-6">
        Auditoría de recepción y reparto de materia prima. Usá <strong>Imprimir</strong> o <strong>PDF</strong> abajo para control y archivo.
        <span v-if="esAdmin" class="is-block mt-2">Si cargaste mal un despiece, usá <strong>Quitar registro</strong> en la tabla y volvé a registrar en <em>Registrar despiece</em>.</span>
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
              </div>
            </b-field>
          </b-field>
        </div>
        <div class="column is-12-tablet">
          <label class="label">Exportar (visible después de cargar datos)</label>
          <div class="buttons">
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
        </div>
      </div>
    </div>

    <p v-if="leyendaPeriodo" class="is-size-7 has-text-grey mb-2 no-print">{{ leyendaPeriodo }}</p>

    <div class="table-container despiece-tabla-print">
      <table class="table is-bordered is-striped is-narrow is-fullwidth">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Total kg rec.</th>
            <th>Usuario</th>
            <th>Id insumo</th>
            <th>Materia / corte</th>
            <th>Kg línea</th>
            <th>Porc. 250 g</th>
            <th>Porc. 350 g</th>
            <th>Desp. (g)</th>
            <th>Sobras (g)</th>
            <th v-if="esAdmin" class="no-print">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="lote in despiece">
            <template v-if="lote.lineas && lote.lineas.length">
              <tr v-for="(ln, idx) in lote.lineas" :key="'d' + lote.id + '-' + ln.id">
                <td v-if="idx === 0" :rowspan="lote.lineas.length">{{ lote.fecha }}</td>
                <td v-if="idx === 0" :rowspan="lote.lineas.length">{{ lote.total_kg_recibido }}</td>
                <td v-if="idx === 0" :rowspan="lote.lineas.length">{{ lote.usuario }}</td>
                <td>{{ ln.id_insumo != null ? ln.id_insumo : '—' }}</td>
                <td>{{ ln.materia_prima }}</td>
                <td>{{ ln.kg_asignado }}</td>
                <td>{{ ln.porciones_250 }}</td>
                <td>{{ ln.porciones_350 }}</td>
                <td>{{ ln.desperdicio_g }}</td>
                <td>{{ ln.sobras_g }}</td>
                <td v-if="esAdmin && idx === 0" :rowspan="lote.lineas.length" class="no-print is-nowrap">
                  <b-button
                    type="is-danger is-light"
                    size="is-small"
                    icon-left="delete-outline"
                    :loading="eliminandoId === lote.id"
                    @click="confirmarEliminar(lote)">
                    Quitar registro
                  </b-button>
                </td>
              </tr>
            </template>
            <tr v-else :key="'empty-' + lote.id">
              <td>{{ lote.fecha }}</td>
              <td>{{ lote.total_kg_recibido }}</td>
              <td>{{ lote.usuario }}</td>
              <td colspan="7" class="has-text-grey">Sin líneas</td>
              <td v-if="esAdmin" class="no-print">
                <b-button
                  type="is-danger is-light"
                  size="is-small"
                  icon-left="delete-outline"
                  :loading="eliminandoId === lote.id"
                  @click="confirmarEliminar(lote)">
                  Quitar registro
                </b-button>
              </td>
            </tr>
          </template>
          <tr v-if="despiece.length === 0 && !cargando">
            <td :colspan="esAdmin ? 11 : 10" class="has-text-centered">No hay registros en el periodo</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="is-size-7 has-text-grey mt-3 no-print print-footer-hint">
      Documento para control de cuadre de kilos y trazabilidad por usuario e insumo del catálogo.
    </p>
  </section>
</template>

<script>
import ReportesPdfService from '../../Servicios/ReportesPdfService';
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
    filasParaPdf() {
      const rows = [];
      this.despiece.forEach((lote) => {
        if (lote.lineas && lote.lineas.length) {
          lote.lineas.forEach((ln) => {
            rows.push([
              String(lote.fecha || ''),
              String(lote.total_kg_recibido != null ? lote.total_kg_recibido : ''),
              String(lote.usuario || ''),
              String(ln.id_insumo != null ? ln.id_insumo : '—'),
              String(ln.materia_prima || ''),
              String(ln.kg_asignado != null ? ln.kg_asignado : ''),
              String(ln.porciones_250 != null ? ln.porciones_250 : ''),
              String(ln.porciones_350 != null ? ln.porciones_350 : ''),
              String(ln.desperdicio_g != null ? ln.desperdicio_g : ''),
              String(ln.sobras_g != null ? ln.sobras_g : '')
            ]);
          });
        } else {
          rows.push([
            String(lote.fecha || ''),
            String(lote.total_kg_recibido != null ? lote.total_kg_recibido : ''),
            String(lote.usuario || ''),
            '—',
            '—',
            '—',
            '—',
            '—',
            '—',
            '—'
          ]);
        }
      });
      return rows;
    },
    textoPiePdf() {
      const n = this.despiece.length;
      return `Lotes en reporte: ${n}. ${this.leyendaPeriodo}`;
    },
    async exportarPdf() {
      if (!this.tieneDatos) {
        this.toast('No hay datos: filtrá fechas o registrá despiece primero.', 'is-warning');
        return;
      }
      const columnas = [
        'Fecha',
        'T.kg',
        'Usuario',
        'Id ins.',
        'Corte',
        'Kg línea',
        '250g',
        '350g',
        'Desp.',
        'Sob.'
      ];
      await ReportesPdfService.generar(
        'Control despiece parrilla',
        columnas,
        this.filasParaPdf(),
        this.textoPiePdf()
      );
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
.table-container { overflow-x: auto; }
</style>

<style>
@media print {
  .no-print,
  .navbar,
  .app-shell > footer,
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
