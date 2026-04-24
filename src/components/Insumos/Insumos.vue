<template>
  <section>
    <!-- Encabezado -->
    <nav class="level mb-4">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="food-fork-drink" size="is-large" type="is-primary"></b-icon>
            Insumos
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
          <div class="buttons">
            <b-button type="is-primary" icon-left="filter-variant" @click="filtrar = !filtrar">Filtrar</b-button>
            <b-button type="is-danger" icon-left="file-pdf-box" @click="exportarPDF">PDF</b-button>
            <b-button type="is-primary" icon-left="plus" @click="abrirNuevoInsumo">Añadir insumo</b-button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Filtros -->
    <div class="box mb-4" v-if="filtrar">
      <b-field grouped group-multiline>
        <b-field label="Tipo" expanded>
          <b-select v-model="filtros.tipo" @input="busquedaAvanzada">
            <option value="">Todos los tipos</option>
            <option value="PLATILLO">Platillo</option>
            <option value="BEBIDA">Bebida</option>
          </b-select>
        </b-field>
        <b-field label="Categoría" expanded>
          <b-select v-model="filtros.categoria" @input="aplicarFiltros">
            <option value="">Todas las categorías</option>
            <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
              {{ categoria.nombre }}
            </option>
          </b-select>
        </b-field>
        <b-field label="Nombre" expanded>
          <b-input placeholder="Buscar por nombre..." v-model="filtros.nombre" icon="magnify"
            @input="busquedaAvanzada"></b-input>
        </b-field>
      </b-field>
    </div>

    <!-- Estadísticas -->
    <div class="columns mb-2" v-if="insumos.length > 0">
      <div class="column">
        <div class="notification is-light py-4 has-text-centered">
          <p class="heading has-text-grey">Total productos</p>
          <p class="title is-3">{{ insumos.length }}</p>
        </div>
      </div>
      <div class="column">
        <div class="notification is-warning is-light py-4 has-text-centered">
          <p class="heading">Platillos</p>
          <p class="title is-3 has-text-warning-dark">{{ totalPlatillos }}</p>
        </div>
      </div>
      <div class="column">
        <div class="notification is-info is-light py-4 has-text-centered">
          <p class="heading">Bebidas</p>
          <p class="title is-3 has-text-info-dark">{{ totalBebidas }}</p>
        </div>
      </div>
      <div class="column">
        <div class="notification py-4 has-text-centered"
          :class="stockCritico > 0 ? 'is-danger is-light' : 'is-success is-light'">
          <p class="heading">Stock crítico</p>
          <p class="title is-3">{{ stockCritico }}</p>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="box">
      <b-table :data="insumos" :loading="cargando" :paginated="isPaginated" :per-page="perPage" :bordered="true"
        :striped="true" :hoverable="true" :narrowed="true" :current-page.sync="currentPage"
        :pagination-simple="isPaginationSimple" :pagination-position="paginationPosition"
        :default-sort-direction="defaultSortDirection" :sort-icon="sortIcon" :sort-icon-size="sortIconSize"
        aria-next-label="Siguiente" aria-previous-label="Anterior" aria-page-label="Página"
        aria-current-label="Página actual">
        <b-table-column field="tipo" label="Tipo" sortable v-slot="props">
          <b-tag :type="props.row.tipo === 'PLATILLO' ? 'is-warning is-light' : 'is-info is-light'">
            <b-icon :icon="props.row.tipo === 'PLATILLO' ? 'noodles' : 'cup'" size="is-small"></b-icon>
            {{ props.row.tipo }}
          </b-tag>
        </b-table-column>

        <b-table-column field="codigo" label="Código" sortable v-slot="props">
          <span class="is-size-7 has-text-grey">{{ props.row.codigo }}</span>
        </b-table-column>

        <b-table-column field="nombre" label="Nombre" searchable sortable v-slot="props">
          <strong>{{ props.row.nombre }}</strong>
        </b-table-column>

        <b-table-column field="descripcion" label="Descripción" v-slot="props">
          <span class="is-size-7">{{ props.row.descripcion }}</span>
        </b-table-column>

        <b-table-column field="categoria" label="Categoría" searchable sortable v-slot="props">
          {{ props.row.categoria || '—' }}
        </b-table-column>

        <b-table-column field="precio" label="Precio" sortable numeric v-slot="props">
          <strong>{{ formatearDinero(props.row.precio) }}</strong>
        </b-table-column>

        <b-table-column field="stock" label="Stock" sortable numeric v-slot="props">
          <template v-if="props.row.tipoVenta === 'COMBO'">
            <b-tag type="is-info is-light">Menú (por componentes)</b-tag>
          </template>
          <template v-else-if="props.row.tipoVenta === 'RECETA'">
            <b-tag type="is-info is-light">Receta fija</b-tag>
          </template>
          <template v-else>
            <b-tag :type="tipoAlertaStock(props.row)">
              <b-icon icon="alert-circle" size="is-small" v-if="props.row.stock <= props.row.stockMinimo"></b-icon>
              {{ formatearCantidad(props.row.stock) }} uds.
            </b-tag>
            <span class="is-size-7 has-text-grey"> mín: {{ formatearCantidad(props.row.stockMinimo) }}</span>
          </template>
        </b-table-column>

        <b-table-column field="porciones" label="Porciones / Unidades" v-slot="props">
          <template v-if="props.row.tipo === 'PLATILLO' && props.row.tipoCorte > 0">
            <b-tag type="is-primary">{{ formatearCantidad(props.row.stock) }} listas</b-tag>
            <span class="is-size-7">&nbsp;+&nbsp;<strong>{{ Math.floor((props.row.stockMateria * 1000) /
              props.row.tipoCorte) }}</strong> posibles</span>
          </template>
          <template v-else-if="props.row.tipo === 'BEBIDA' && props.row.tipoCorte > 0">
            <b-tag type="is-info">{{ formatearCantidad(props.row.stock) }} uds.</b-tag>
            <span class="is-size-7">&nbsp;+&nbsp;<strong>{{ Math.floor((props.row.stockMateria * 1000) /
              props.row.tipoCorte) }}</strong> posibles</span>
          </template>
          <span v-else class="has-text-grey">—</span>
        </b-table-column>

        <b-table-column field="acciones" label="Acciones" v-slot="props">
          <div class="buttons">
            <b-button size="is-small" type="is-info" icon-left="pen" title="Editar"
              @click="editar(props.row.id)"></b-button>
            <b-button size="is-small" type="is-warning" icon-left="minus" title="Dar Baja"
              @click="darBaja(props.row)"></b-button>
            <b-button size="is-small" type="is-success" icon-left="pot-steam" title="Producir"
              v-if="props.row.tipoCorte > 0 && props.row.stockMateria > 0" @click="abrirProducir(props.row)"></b-button>
            <b-button size="is-small" type="is-danger" icon-left="delete" title="Eliminar"
              @click="eliminar(props.row)"></b-button>
          </div>
        </b-table-column>

        <template #empty>
          <div class="has-text-centered py-6 has-text-grey" style="opacity: 0.8;">
            <b-icon icon="food-apple-outline" custom-size="fa-4x" style="font-size: 4rem;"></b-icon>
            <p class="mt-4 title is-4 has-text-grey-dark">El menú está vacío</p>
            <p class="subtitle is-6">Sube tus recetas haciendo clic en "Añadir insumo".</p>
          </div>
        </template>
      </b-table>
    </div>

    <!-- Modal: Registrar / Editar insumo -->
    <b-modal v-model="modalInsumo" has-modal-card :destroy-on-hide="true" scroll="keep">
      <div class="modal-card" style="width:960px; max-width:95vw">
        <header class="modal-card-head" style="background:var(--color-primario); box-shadow:none">
          <p class="modal-card-title" style="color:#fff">
            <b-icon :icon="editandoInsumo ? 'pencil' : 'plus-circle'" size="is-small" style="color:#fff"></b-icon>
            &nbsp;{{ editandoInsumo ? 'Editar Insumo' : 'Registrar Insumo' }}
          </p>
          <button class="delete" @click="modalInsumo = false"></button>
        </header>
        <section class="modal-card-body" style="position:relative">
          <b-loading :active="guardandoInsumo" :is-full-page="false"></b-loading>
          <datos-insumo v-if="modalInsumo" :insumo="insumoModal" :editar="editandoInsumo" @registrado="onGuardarInsumo">
          </datos-insumo>
        </section>
      </div>
    </b-modal>

    <!-- Modal: Producir desde materia prima -->
    <b-modal v-model="modalProducir" has-modal-card trap-focus :destroy-on-hide="false">
      <div class="modal-card" style="min-width:420px; max-width:520px">
        <header class="modal-card-head">
          <p class="modal-card-title">
            <b-icon icon="pot-steam" type="is-success"></b-icon>
            Producir: {{ insumoProducir ? insumoProducir.nombre : '' }}
          </p>
          <button class="delete" @click="modalProducir = false"></button>
        </header>
        <section class="modal-card-body" v-if="insumoProducir">
          <!-- Info actual -->
          <div class="notification is-light is-info py-3 px-4 mb-4">
            <div class="columns is-mobile mb-0">
              <div class="column has-text-centered">
                <p class="is-size-7 has-text-grey">{{ insumoProducir.tipo === 'BEBIDA' ? 'Líquido disponible' : 'Materia disponible' }}</p>
                <p class="title is-4 mb-0">{{ insumoProducir.stockMateria }} {{ insumoProducir.tipo === 'BEBIDA' ? 'L' : 'kg' }}</p>
              </div>
              <div class="column has-text-centered">
                <p class="is-size-7 has-text-grey">{{ insumoProducir.tipo === 'BEBIDA' ? 'ml por unidad' : 'Tasa de corte' }}</p>
                <p class="title is-4 mb-0">{{ insumoProducir.tipoCorte }} {{ insumoProducir.tipo === 'BEBIDA' ? 'ml/ud.' : 'g/porción' }}</p>
              </div>
              <div class="column has-text-centered">
                <p class="is-size-7 has-text-grey">{{ insumoProducir.tipo === 'BEBIDA' ? 'Máx. unidades posibles' : 'Máx. porciones posibles' }}</p>
                <p class="title is-4 mb-0 has-text-success">{{ maxPorciones }}</p>
              </div>
            </div>
          </div>

          <!-- Preview dinámico -->
          <div class="notification is-success is-light py-2 px-4 mb-3"
            v-if="usoMateriaBorrador > 0 && porcionesPreview > 0">
            <b-icon icon="information-outline" size="is-small" class="mr-1"></b-icon>
            Con <strong>{{ usoMateriaBorrador }} {{ insumoProducir && insumoProducir.tipo === 'BEBIDA' ? 'L' : 'kg'
              }}</strong> podés preparar
            <strong>{{ porcionesPreview }} {{ insumoProducir && insumoProducir.tipo === 'BEBIDA' ? 'unidades' :
              'porciones'
              }}</strong> más
          </div>
          <div class="notification is-warning is-light py-2 px-4 mb-3" v-else-if="usoMateriaBorrador > 0">
            <b-icon icon="alert" size="is-small" class="mr-1"></b-icon>
            La cantidad ingresada no alcanza para preparar ninguna porción
          </div>

          <b-field
            :label="insumoProducir && insumoProducir.tipo === 'BEBIDA' ? 'Líquido a usar (L)' : 'Materia prima a usar (kg)'">
            <b-numberinput v-model="usoMateriaBorrador" :min="0" :max="insumoProducir.stockMateria" :step="0.1"
              type="is-success" @input="recalcularPreview">
            </b-numberinput>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button type="is-success" icon-left="check" :disabled="porcionesPreview <= 0" :loading="cargando"
            @click="confirmarProducir">
            Producir {{ porcionesPreview > 0 ? (porcionesPreview + (insumoProducir && insumoProducir.tipo === 'BEBIDA' ? ' unidades' : ' porciones')) : '' }}
          </b-button>
          <b-button type="is-light" @click="modalProducir = false">Cancelar</b-button>
        </footer>
      </div>
    </b-modal>
  </section>
</template>
<script>
import HttpService from "../../Servicios/HttpService";
import ReportesPdfService from "../../Servicios/ReportesPdfService";
import Utiles from "../../Servicios/Utiles";
import DatosInsumo from './DatosInsumo.vue';

export default {
  name: "Insumos",
  components: { DatosInsumo },

  data: () => ({
    filtrar: false,
    insumos: [],
    filtros: {
      tipo: "",
      categoria: "",
      nombre: "",
    },
    categorias: [],
    cargando: false,
    isPaginated: true,
    isPaginationSimple: false,
    isPaginationRounded: true,
    paginationPosition: "bottom",
    defaultSortDirection: "asc",
    sortIcon: "arrow-up",
    sortIconSize: "is-small",
    currentPage: 1,
    perPage: 20,
    // Producir desde materia prima
    modalProducir: false,
    insumoProducir: null,
    usoMateriaBorrador: 0,
    porcionesPreview: 0,
    // Modal registrar/editar
    modalInsumo: false,
    insumoModal: {},
    editandoInsumo: false,
    guardandoInsumo: false,
  }),

  computed: {
    maxPorciones() {
      if (!this.insumoProducir || !this.insumoProducir.tipoCorte) return 0
      return Math.floor((this.insumoProducir.stockMateria * 1000) / this.insumoProducir.tipoCorte)
    },
    totalPlatillos() {
      return this.insumos.filter(i => i.tipo === 'PLATILLO').length
    },
    totalBebidas() {
      return this.insumos.filter(i => i.tipo === 'BEBIDA').length
    },
    stockCritico() {
      return this.insumos.filter(i => {
        if (i.tipoVenta === 'RECETA' || i.tipoVenta === 'COMBO') return false
        return this.tipoAlertaStock(i) === 'is-danger'
      }).length
    },
  },

  mounted() {
    this.obtenerInsumos();
  },

  methods: {
    abrirProducir(insumo) {
      this.insumoProducir = insumo
      this.usoMateriaBorrador = 0
      this.porcionesPreview = 0
      this.modalProducir = true
    },
    recalcularPreview() {
      if (!this.insumoProducir || this.usoMateriaBorrador <= 0) {
        this.porcionesPreview = 0
        return
      }
      this.porcionesPreview = Math.floor((this.usoMateriaBorrador * 1000) / this.insumoProducir.tipoCorte)
    },
    async confirmarProducir() {
      if (this.porcionesPreview <= 0) return
      this.cargando = true
      const payload = {
        idInsumo: this.insumoProducir.id,
        usoMateria: this.usoMateriaBorrador,
        idUsuario: localStorage.getItem('idUsuario')
      }
      const res = await HttpService.registrar(payload, 'producir_desde_materia.php')
      this.cargando = false
      if (res && res.ok) {
        this.$toast({
          message: `✓ ${res.porcionesNuevas} porciones de "${this.insumoProducir.nombre}" agregadas al stock`,
          type: 'is-success',
          duration: 5000
        })
        this.modalProducir = false
        this.obtenerInsumos()
      } else {
        this.$toast({ message: res && res.error ? res.error : 'Error al producir', type: 'is-danger' })
      }
    },
    darBaja(insumo) {
      this.$buefy.dialog.prompt({
        title: `Baja de stock: ` + insumo.nombre,
        message: `¿Cuántas unidades se darán de baja (Consumo dueño / Merma)?`,
        inputAttrs: {
          type: 'number',
          value: 1,
          placeholder: 'Cantidad',
          min: 1,
          max: insumo.stock
        },
        trapFocus: true,
        confirmText: 'Registrar Baja',
        cancelText: 'Cancelar',
        onConfirm: (value) => {
          let cantidad = parseFloat(value);
          if (cantidad <= 0 || cantidad > insumo.stock) {
            this.$toast({ message: 'Cantidad inválida o superior al stock', type: 'is-danger' });
            return;
          }
          this.cargando = true;
          let payload = {
            idInsumo: insumo.id,
            cantidad: cantidad,
            idUsuario: localStorage.getItem('idUsuario')
          };
          HttpService.registrar(payload, "registrar_merma.php")
            .then(resultado => {
              this.cargando = false;
              if (resultado) {
                this.$toast({ message: 'Baja registrada en Kardex', type: 'is-success' });
                this.obtenerInsumos();
              }
            })
            .catch(() => {
              this.cargando = false;
              this.$toast({ message: 'Error al registrar', type: 'is-danger' });
            });
        }
      })
    },

    exportarPDF() {
      if (this.insumos.length === 0) return;

      // Agrupar por categoría
      const agrupados = {};
      this.insumos.forEach(ins => {
        const cat = ins.categoria || 'Sin Categoría';
        if (!agrupados[cat]) agrupados[cat] = [];
        agrupados[cat].push(ins);
      });

      // Ordenar categorías: 'Carnes' primero, luego el resto alfabéticamente
      const categoriasOrdenadas = Object.keys(agrupados).sort((a, b) => {
        if (a.toLowerCase() === 'carnes') return -1;
        if (b.toLowerCase() === 'carnes') return 1;
        return a.localeCompare(b);
      });

      let columnas = ["Tipo", "Código", "Nombre", "Precio Unit.", "Stock", "Límite Mínimo"];
      let filas = [];

      categoriasOrdenadas.forEach(cat => {
        // Fila de encabezado de categoría (Sección)
        filas.push([
          {
            content: `CATEGORÍA: ${cat.toUpperCase()}`,
            colSpan: 6,
            styles: { fillColor: [245, 245, 245], fontStyle: 'bold', textColor: [44, 62, 80] }
          }
        ]);

        agrupados[cat].forEach(ins => {
          filas.push([
            ins.tipo,
            ins.codigo,
            ins.nombre,
            this.formatearDinero(ins.precio),
            this.formatearCantidad(ins.stock) + " uds",
            this.formatearCantidad(ins.stockMinimo) + " uds"
          ]);
        });
      });

      let totalStockInfo = "Total Productos en Catálogo: " + this.insumos.length;
      ReportesPdfService.generar("Inventario de Insumos por Categoría", columnas, filas, totalStockInfo);
    },

    busquedaAvanzada(tipo) {
      const tipoActual = tipo !== undefined ? tipo : this.filtros.tipo
      if (tipoActual === "BEBIDA" || tipoActual === "PLATILLO") {
        this.obtenerCategorias(tipoActual);
        this.filtros.categoria = "";
      } else {
        this.categorias = [];
      }
      this.obtenerInsumos();
    },
    aplicarFiltros() {
      this.obtenerInsumos();
    },

    eliminar(insumo) {
      this.$buefy.dialog.confirm({
        title: "Eliminar el insumo " + insumo.nombre,
        message:
          "¿Seguro que deseas eliminar el insumo? Esta acción no se puede deshacer",
        confirmText: "Sí, eliminar",
        cancelText: "No, salir",
        type: "is-danger",
        hasIcon: true,
        onConfirm: () => {
          HttpService.eliminar("eliminar_insumo.php", insumo.id).then(
            (eliminado) => {
              if (eliminado) {
                this.obtenerInsumos();
                this.$toast("Insumo eliminado");
              }
            }
          );
        },
      });
    },

    abrirNuevoInsumo() {
      this.insumoModal = { tipo: '', codigo: '', nombre: '', descripcion: '', categoria: '', precio: '', stock: 0, stockMinimo: 0, stockMateria: 0, tipoCorte: 0, tipoVenta: 'NORMAL', idComboPlantilla: '', receta: [] }
      this.editandoInsumo = false
      this.modalInsumo = true
    },

    onGuardarInsumo(insumo) {
      this.guardandoInsumo = true
      const endpoint = this.editandoInsumo ? 'editar_insumo.php' : 'registrar_insumo.php'
      if (this.editandoInsumo) insumo.idUsuario = localStorage.getItem('idUsuario')
      HttpService.registrar(insumo, endpoint).then(res => {
        this.guardandoInsumo = false
        if (res) {
          this.$toast({ message: this.editandoInsumo ? 'Insumo actualizado' : 'Insumo registrado', type: 'is-success' })
          this.modalInsumo = false
          this.obtenerInsumos()
        }
      }).catch(() => {
        this.guardandoInsumo = false
        this.$toast({ message: 'Error al guardar insumo', type: 'is-danger' })
      })
    },

    editar(idInsumo) {
      this.guardandoInsumo = true
      this.editandoInsumo = true
      this.modalInsumo = true
      HttpService.obtenerConDatos(idInsumo, 'obtener_insumo_id.php').then(resultado => {
        this.insumoModal = resultado
        this.guardandoInsumo = false
      }).catch(() => {
        this.guardandoInsumo = false
        this.$toast({ message: 'Error al cargar insumo', type: 'is-danger' })
      })
    },

    obtenerInsumos() {
      this.cargando = true;
      HttpService.obtenerConDatos(this.filtros, "obtener_insumos.php").then(
        (datos) => {
          this.insumos = datos;
          this.cargando = false;
        }
      ).catch(() => {
        this.cargando = false;
        this.$toast({ message: 'Error al cargar insumos. Verifica el servidor.', type: 'is-danger' });
      });
    },

    tipoAlertaStock(insumo) {
      const stock = Number(insumo.stock) || 0;
      const stockMinimo = Number(insumo.stockMinimo) || 0;
      const tipoCorte = Number(insumo.tipoCorte) || 0;
      const stockMateria = Number(insumo.stockMateria) || 0;

      const posibles = (insumo.tipo === 'PLATILLO' && tipoCorte > 0)
        ? Math.floor((stockMateria * 1000) / tipoCorte)
        : 0;
      const totalDisponible = stock + posibles;

      if (totalDisponible <= stockMinimo) return 'is-danger';
      if (totalDisponible <= stockMinimo * 2) return 'is-warning';
      return 'is-success';
    },

    obtenerCategorias(tipo) {
      const tipoInsumo = tipo !== undefined ? tipo : this.filtros.tipo
      if (!tipoInsumo) {
        this.categorias = [];
        return;
      }
      HttpService.obtenerConDatos(
        { tipo: tipoInsumo },
        "obtener_categorias_tipo.php"
      ).then((datos) => {
        this.categorias = Array.isArray(datos) ? datos : [];
      }).catch(() => {
        this.categorias = [];
      });
    },
    formatearDinero(m) { return Utiles.formatearDinero(m) },
    formatearCantidad(c) { return Utiles.formatearCantidad(c) }
  },
};
</script>