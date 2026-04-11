<template>
  <section class="section">
    <div class="container">
      <nav class="level">
        <div class="level-left">
          <div class="level-item">
            <p class="title is-1 has-text-weight-bold">
              <b-icon icon="calendar-check" size="is-large" type="is-primary"></b-icon>
              Planificador de Menú por Día
            </p>
          </div>
        </div>
      </nav>

      <div class="columns">
        <!-- Selector de Día -->
        <div class="column is-3">
          <aside class="menu">
            <p class="menu-label">Días de la Semana</p>
            <ul class="menu-list">
              <li v-for="(dia, index) in diasSemana" :key="index">
                <a 
                  :class="{ 'is-active': diaSeleccionado === dia.valor }"
                  @click="seleccionarDia(dia.valor)"
                >
                  <b-icon :icon="dia.icono" size="is-small"></b-icon>
                  {{ dia.nombre }}
                </a>
              </li>
            </ul>
          </aside>
        </div>

        <!-- Gestión de Productos del Día -->
        <div class="column">
          <div class="box">
            <nav class="level">
              <div class="level-left">
                <div class="level-item">
                  <h2 class="subtitle is-4">Platos para el <strong>{{ nombreDiaSeleccionado }}</strong></h2>
                </div>
              </div>
              <div class="level-right">
                <div class="level-item">
                  <b-field>
                    <b-autocomplete
                      v-model="busqueda"
                      :data="insumosFiltrados"
                      placeholder="Buscar producto para añadir..."
                      field="nombre"
                      icon="magnify"
                      clearable
                      @select="aniadirAlMenu"
                    >
                      <template slot-scope="props">
                        <div class="media">
                          <div class="media-content">
                            <span class="has-text-weight-semibold">{{ props.option.nombre }}</span>
                            <br>
                            <small class="has-text-grey">{{ props.option.categoria }} — ${{ props.option.precio }}</small>
                            &nbsp;
                            <b-tag
                              :type="props.option.stock <= 0 ? 'is-danger' : props.option.stock <= props.option.stockMinimo ? 'is-warning' : 'is-success'"
                              size="is-small"
                            >
                              <b-icon icon="package-variant" size="is-small" class="mr-1"></b-icon>
                              Stock: {{ props.option.stock }}
                            </b-tag>
                          </div>
                        </div>
                      </template>
                    </b-autocomplete>
                  </b-field>
                </div>
              </div>
            </nav>

            <b-table :data="menuDelDia" :striped="true" :hoverable="true" v-if="menuDelDia.length > 0">
              <b-table-column field="nombre" label="Producto" v-slot="props">
                {{ props.row.nombre }}
              </b-table-column>
              <b-table-column field="categoria" label="Categoría" v-slot="props">
                {{ props.row.categoria }}
              </b-table-column>
              <b-table-column field="precio" label="Precio" v-slot="props">
                ${{ props.row.precio }}
              </b-table-column>
              <b-table-column field="stock" label="Stock" v-slot="props" centered>
                <template v-if="props.row.tipoVenta === 'COMBO'">
                  <b-tag type="is-info is-light" rounded>Menú (por componentes)</b-tag>
                </template>
                <template v-else-if="props.row.tipoVenta === 'RECETA'">
                  <b-tag type="is-info is-light" rounded>Receta fija</b-tag>
                </template>
                <template v-else>
                  <b-tag
                    :type="props.row.stock <= 0 ? 'is-danger' : props.row.stock <= props.row.stockMinimo ? 'is-warning' : 'is-success'"
                    rounded
                  >
                    <b-icon icon="package-variant" size="is-small" class="mr-1"></b-icon>
                    {{ props.row.stock <= 0 ? 'Sin stock' : props.row.stock + ' uds.' }}
                  </b-tag>
                </template>
              </b-table-column>
              <b-table-column label="Acciones" v-slot="props">
                <b-button 
                  type="is-danger" 
                  icon-left="delete" 
                  size="is-small"
                  @click="quitarDelMenu(props.row)"
                >
                  Quitar
                </b-button>
              </b-table-column>
            </b-table>

            <div v-else class="has-text-centered py-6">
              <b-icon icon="food-off" size="is-large" type="is-grey-light"></b-icon>
              <p class="has-text-grey mt-3">No hay productos asignados para este día.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
  </section>
</template>

<script>
import HttpService from "../../Servicios/HttpService";

export default {
  name: "MenuDia",
  data() {
    return {
      cargando: false,
      diaSeleccionado: 1, // Lunes por defecto (1-7, 7=Domingo en MySQL DAYOFWEEK si usamos esa lógica, pero usualmente 1=Dom, 2=Lun... definiremos 1=Dom, 2=Lun, 3=Mar, 4=Mie, 5=Jue, 6=Vie, 7=Sab)
      // Ajuste: MySQL DAYOFWEEK(): 1=Sunday, 2=Monday, ..., 7=Saturday
      diasSemana: [
        { nombre: "Domingo", valor: 1, icono: "calendar-sun" },
        { nombre: "Lunes", valor: 2, icono: "calendar-today" },
        { nombre: "Martes", valor: 3, icono: "calendar-today" },
        { nombre: "Miércoles", valor: 4, icono: "calendar-today" },
        { nombre: "Jueves", valor: 5, icono: "calendar-today" },
        { nombre: "Viernes", valor: 6, icono: "calendar-today" },
        { nombre: "Sábado", valor: 7, icono: "calendar-star" },
      ],
      menuDelDia: [],
      todosLosInsumos: [],
      busqueda: "",
    };
  },
  computed: {
    nombreDiaSeleccionado() {
      const dia = this.diasSemana.find(d => d.valor === this.diaSeleccionado);
      return dia ? dia.nombre : "";
    },
    insumosFiltrados() {
      return this.todosLosInsumos.filter(option => {
        return (
          option.nombre
            .toString()
            .toLowerCase()
            .indexOf(this.busqueda.toLowerCase()) >= 0
        );
      });
    }
  },
  async mounted() {
    // Inicializar con el día actual
    const hoy = new Date().getDay() + 1; // getDay() es 0-6 (Dom-Sab), MySQL 1-7 (Dom-Sab)
    this.diaSeleccionado = hoy;
    await this.cargarTodosLosInsumos();
    await this.cargarMenuDia();
  },
  methods: {
    async seleccionarDia(dia) {
      this.diaSeleccionado = dia;
      await this.cargarMenuDia();
    },
    async cargarTodosLosInsumos() {
      try {
        const filtros = { tipo: "", categoria: "", nombre: "" };
        this.todosLosInsumos = await HttpService.obtenerConDatos(filtros, "obtener_insumos.php");
      } catch (e) {
        console.error(e);
      }
    },
    async cargarMenuDia() {
      this.cargando = true;
      try {
        this.menuDelDia = await HttpService.obtenerConDatos(this.diaSeleccionado, "obtener_menu_dia.php");
      } catch (e) {
        this.$toast({ message: "Error al cargar el menú", type: "is-danger" });
      } finally {
        this.cargando = false;
      }
    },
    async aniadirAlMenu(insumo) {
      if (!insumo) return;
      this.cargando = true;
      try {
        await HttpService.registrar({
          idInsumo: insumo.id,
          diaSemana: this.diaSeleccionado
        }, "guardar_menu_dia.php");
        this.$toast({ message: "Añadido al menú", type: "is-success" });
        await this.cargarMenuDia();
        this.busqueda = "";
      } catch (e) {
        this.$toast({ message: "Error al añadir", type: "is-danger" });
      } finally {
        this.cargando = false;
      }
    },
    async quitarDelMenu(insumo) {
      this.cargando = true;
      try {
        await HttpService.registrar({
          idInsumo: insumo.id,
          diaSemana: this.diaSeleccionado
        }, "eliminar_menu_dia.php");
        this.$toast({ message: "Quitado del menú", type: "is-warning" });
        await this.cargarMenuDia();
      } catch (e) {
        this.$toast({ message: "Error al quitar", type: "is-danger" });
      } finally {
        this.cargando = false;
      }
    }
  }
};
</script>


