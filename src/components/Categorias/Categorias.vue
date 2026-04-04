<template>
  <section>
    <!-- Encabezado -->
    <nav class="level mb-4">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="archive-outline" size="is-large" type="is-primary"></b-icon>
            Categorías
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
            <b-button type="is-primary" icon-left="plus" @click="abrirModal('registra')">Añadir categoría</b-button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Filtros desplegables -->
    <div class="box mb-4" v-if="filtrar">
      <b-field grouped group-multiline>
        <b-field label="Tipo" expanded>
          <b-select v-model="filtros.tipo" @input="aplicarFiltros">
            <option value="">Todos los tipos</option>
            <option value="PLATILLO">Platillo</option>
            <option value="BEBIDA">Bebida</option>
          </b-select>
        </b-field>
        <b-field label="Nombre" expanded>
          <b-input placeholder="Buscar por nombre..." v-model="filtros.nombre" icon="magnify" @input="aplicarFiltros"></b-input>
        </b-field>
      </b-field>
    </div>

    <!-- Estadísticas -->
    <div class="columns mb-2" v-if="categorias.length > 0">
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">Total categorías</p>
          <p class="title is-4">{{ categorias.length }}</p>
        </div>
      </div>
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">Platillos</p>
          <p class="title is-4 has-text-warning">{{ totalPlatillos }}</p>
        </div>
      </div>
      <div class="column">
        <div class="box has-text-centered py-3">
          <p class="heading">Bebidas</p>
          <p class="title is-4 has-text-info">{{ totalBebidas }}</p>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="box">
    <b-table
      :data="categoriasFiltradas"
      :loading="cargando"
      :paginated="isPaginated"
      :per-page="perPage"
      :bordered="true"
      :striped="true"
      :hoverable="true"
      :narrowed="true"
      :current-page.sync="currentPage"
      :pagination-simple="isPaginationSimple"
      :pagination-position="paginationPosition"
      :default-sort-direction="defaultSortDirection"
      :sort-icon="sortIcon"
      :sort-icon-size="sortIconSize"
      aria-next-label="Siguiente"
      aria-previous-label="Anterior"
      aria-page-label="Página"
      aria-current-label="Página actual"
    >
      <b-table-column field="tipo" label="Tipo" sortable searchable v-slot="props">
        <b-tag :type="props.row.tipo === 'PLATILLO' ? 'is-warning is-light' : 'is-info is-light'">
          <b-icon :icon="props.row.tipo === 'PLATILLO' ? 'noodles' : 'cup'" size="is-small"></b-icon>
          {{ props.row.tipo }}
        </b-tag>
      </b-table-column>

      <b-table-column field="nombre" label="Nombre" sortable searchable v-slot="props">
        <strong>{{ props.row.nombre }}</strong>
      </b-table-column>

      <b-table-column field="descripcion" label="Descripción" searchable v-slot="props">
        <span class="is-size-7">{{ props.row.descripcion || '—' }}</span>
      </b-table-column>

      <b-table-column field="acciones" label="Acciones" v-slot="props">
        <div class="buttons">
          <b-button size="is-small" type="is-info" icon-left="pen" title="Editar"
            @click="editar(props.row)"></b-button>
          <b-button size="is-small" type="is-danger" icon-left="delete" title="Eliminar"
            @click="eliminar(props.row)"></b-button>
        </div>
      </b-table-column>

      <template #empty>
        <div class="has-text-centered py-5 has-text-grey">
          <b-icon icon="archive-outline" size="is-large"></b-icon>
          <p class="mt-2">No se encontraron categorías</p>
        </div>
      </template>
    </b-table>
    </div>

    <b-modal
      :active.sync="mostrarModalCategoria"
      has-modal-card
      trap-focus
      :destroy-on-hide="false"
      aria-role="dialog"
      aria-label="Agregar categoría"
      close-button-aria-label="Close"
      aria-modal>
      <modal-categoria @registrado="onRegistrado" :categoria="categoria" :titulo="titulo" :tipo="tipo"></modal-categoria>
    </b-modal>
  </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'
import ModalCategoria from './ModalCategoria.vue'
export default {
    name: 'Categorias',
    components: {
            ModalCategoria
        },
        data() {
            return {
                mostrarModalCategoria: false,
                estaRegistrando: false,
                estaEditando: false,
                titulo : "",
                tipo: "",
                categoria: {
                    tipo: "",
                    nombre: "",
                    descripcion: ""
                },
                categorias: [],
                isPaginated: true,
                isPaginationSimple: false,
                isPaginationRounded: true,
                paginationPosition: 'bottom',
                defaultSortDirection: 'asc',
                sortIcon: 'arrow-up',
                sortIconSize: 'is-small',
                currentPage: 1,
                perPage: 10,
                filtrar: false,
                filtros: { tipo: '', nombre: '' }
            }
        },

        computed: {
            totalPlatillos() {
                return this.categorias.filter(c => c.tipo === 'PLATILLO').length
            },
            totalBebidas() {
                return this.categorias.filter(c => c.tipo === 'BEBIDA').length
            },
            categoriasFiltradas() {
                return this.categorias.filter(c => {
                    const matchTipo = !this.filtros.tipo || c.tipo === this.filtros.tipo
                    const matchNombre = !this.filtros.nombre || c.nombre.toLowerCase().includes(this.filtros.nombre.toLowerCase())
                    return matchTipo && matchNombre
                })
            }
        },

        created() {
            this.obtenerCategorias()
        },

        methods: {
            eliminar(categoria) {
                this.$buefy.dialog.confirm({
                    title: 'Eliminar categoría ' + categoria.nombre,
                    message: '¿Seguro que deseas eliminar la categoría? Esta acción no se puede deshacer',
                    confirmText: 'Sí, eliminar',
                    cancelText: 'No, salir',
                    type: 'is-danger',
                    hasIcon: true,
                    onConfirm: () => {
                        this.cargando = true
                        HttpService.eliminar("eliminar_categoria.php", categoria.id)
                        .then(eliminado => {
                            if(eliminado) {
                                this.obtenerCategorias()
                                this.$toast('Categoría eliminada')
                                this.cargando = false
                            }
                        })
                        .catch(() => { this.cargando = false; this.$toast({ message: 'Error al eliminar', type: 'is-danger' }) })
                    }
                })
            },

            editar(categoria){
                this.abrirModal("edita", categoria)
            },

            abrirModal(tipo, categoria = {}) {
                this.categoria = categoria

                this.mostrarModalCategoria = true
                if(tipo === "registra") {
                    this.tipo = tipo
                    this.titulo = "Agregar "
                }
                if(tipo === "edita") {
                    this.tipo = tipo
                    this.titulo = "Editar "
                }
            },

            onRegistrado(){
                this.obtenerCategorias()
                this.categoria = {
                    tipo: "",
                    nombre: "",
                    descripcion: ""
                }
            },

            aplicarFiltros() {
                this.currentPage = 1
            },

            obtenerCategorias() {
                this.cargando = true
                HttpService.obtener("obtener_categorias.php")
                .then(resultado =>{
                    this.categorias = resultado
                    this.cargando = false
                })
                .catch(() => {
                    this.cargando = false
                    this.$toast({ message: 'Error al cargar categorías. Verifica el servidor.', type: 'is-danger' })
                })
            }
        }
}
</script>