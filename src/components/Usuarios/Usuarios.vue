<template>
  <section>
    <!-- Encabezado -->
    <nav class="level mb-4">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="account-group"
                    size="is-large"
                    type="is-primary"></b-icon>
            Usuarios
          </p>
        </div>
      </div>
      <div class="level-right">
        <div class="level-item">
          <b-select v-model="perPage"
                    size="is-small">
            <option value="5">5 por página</option>
            <option value="10">10 por página</option>
            <option value="15">15 por página</option>
            <option value="20">20 por página</option>
          </b-select>
        </div>
        <div class="level-item">
          <div class="buttons">
            <b-button type="is-primary"
                      icon-left="filter-variant"
                      @click="filtrar = !filtrar">Filtrar</b-button>
            <b-button type="is-primary"
                      icon-left="account-multiple-plus"
                      @click="abrirNuevoUsuario">Añadir usuario</b-button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Filtros desplegables -->
    <div class="box mb-4"
         v-if="filtrar">
      <b-field grouped
               group-multiline>
        <b-field label="Rol"
                 expanded>
          <b-select v-model="filtros.rol"
                    @input="aplicarFiltros">
            <option value="">Todos los roles</option>
            <option value="admin">Admin</option>
            <option value="cocina">Cocina</option>
            <option value="mesero">Mesero</option>
          </b-select>
        </b-field>
        <b-field label="Nombre"
                 expanded>
          <b-input placeholder="Buscar por nombre..."
                   v-model="filtros.nombre"
                   icon="magnify"
                   @input="aplicarFiltros"></b-input>
        </b-field>
      </b-field>
    </div>
    <!-- Tabla -->
    <div class="box">
      <b-table :data="usuariosFiltrados"
               :loading="cargando"
               paginated
               :per-page="perPage"
               striped
               hoverable
               mobile-cards>
        <b-table-column field="nombre"
                        label="Nombre"
                        sortable
                        v-slot="props">
          <strong>{{ props.row.nombre }}</strong>
        </b-table-column>
        <b-table-column field="telefono"
                        label="Teléfono"
                        sortable
                        v-slot="props">
          {{ props.row.telefono }}
        </b-table-column>
        <b-table-column field="correo"
                        label="Correo"
                        sortable
                        v-slot="props">
          <b-icon icon="email-outline"
                  size="is-small"></b-icon>
          {{ props.row.correo }}
        </b-table-column>




        <b-table-column field="rol"
                        label="Rol"
                        sortable
                        v-slot="props">
          <b-tag
                 :type="props.row.rol === 'admin' ? 'is-danger is-light' : props.row.rol === 'cocina' ? 'is-warning is-light' : 'is-info is-light'">
            <b-icon :icon="props.row.rol === 'admin' ? 'shield-account' : props.row.rol === 'cocina' ? 'chef-hat' : 'account'"
                    size="is-small"></b-icon>
            {{ props.row.rol ? props.row.rol.toUpperCase() : 'MESERO' }}
          </b-tag>
        </b-table-column>

        <b-table-column field="acciones"
                        label="Acciones"
                        v-slot="props">
          <div class="buttons">
            <b-button size="is-small"
                      type="is-info"
                      icon-left="pen"
                      title="Editar"
                      @click="editar(props.row.id)"></b-button>
            <b-button size="is-small"
                      type="is-danger"
                      icon-left="delete"
                      title="Eliminar"
                      @click="eliminar(props.row)"></b-button>
          </div>
        </b-table-column>

        <template #empty>
          <div class="has-text-centered py-5 has-text-grey">
            <b-icon icon="account-group"
                    size="is-large"></b-icon>
            <p class="mt-2">No se encontraron usuarios</p>
          </div>
        </template>
      </b-table>
    </div>

    <!-- Modal: Registrar / Editar usuario -->
    <b-modal v-model="modalUsuario"
             has-modal-card
             :destroy-on-hide="true"
             scroll="keep">
      <div class="modal-card"
           style="width:560px; max-width:95vw">
        <header class="modal-card-head"
                style="background:var(--color-primario); box-shadow:none">
          <p class="modal-card-title"
             style="color:#fff">
            <b-icon :icon="editandoUsuario ? 'pencil' : 'account-multiple-plus'"
                    size="is-small"
                    style="color:#fff"></b-icon>
            &nbsp;{{ editandoUsuario ? 'Editar Usuario' : 'Registrar Usuario' }}
          </p>
          <button class="delete"
                  @click="modalUsuario = false"></button>
        </header>
        <section class="modal-card-body"
                 style="position:relative">
          <b-loading :active="guardandoUsuario"
                     :is-full-page="false"></b-loading>
          <datos-usuario v-if="modalUsuario"
                         :usuario="usuarioModal"
                         :editar="editandoUsuario"
                         @registrado="onGuardarUsuario">
          </datos-usuario>
        </section>
      </div>
    </b-modal>
  </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'
import DatosUsuario from './DatosUsuario.vue'
export default {
  name: "Usuarios",
  components: { DatosUsuario },

  data: () => ({
    usuarios: [],
    cargando: false,
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
    filtros: { rol: '', nombre: '' },
    // Modal
    modalUsuario: false,
    usuarioModal: {},
    editandoUsuario: false,
    guardandoUsuario: false,
  }),

  computed: {
    totalAdmins() { return this.usuarios.filter(u => u.rol === 'admin').length },
    totalCocineros() { return this.usuarios.filter(u => u.rol === 'cocina').length },
    totalMeseros() { return this.usuarios.filter(u => !u.rol || u.rol === 'mesero').length },
    usuariosFiltrados() {
      return this.usuarios.filter(u => {
        const matchRol = !this.filtros.rol || (u.rol || 'mesero') === this.filtros.rol
        const matchNombre = !this.filtros.nombre || u.nombre.toLowerCase().includes(this.filtros.nombre.toLowerCase())
        return matchRol && matchNombre
      })
    }
  },

  mounted() {
    this.obtenerUsuarios()
  },

  methods: {
    aplicarFiltros() {
      this.currentPage = 1
    },

    abrirNuevoUsuario() {
      this.usuarioModal = { correo: '', nombre: '', telefono: '', rol: '' }
      this.editandoUsuario = false
      this.modalUsuario = true
    },

    onGuardarUsuario(usuario) {
      this.guardandoUsuario = true
      const endpoint = this.editandoUsuario ? 'editar_usuario.php' : 'registrar_usuario.php'
      HttpService.registrar(usuario, endpoint).then(res => {
        this.guardandoUsuario = false
        if (res) {
          const msg = this.editandoUsuario
            ? 'Usuario actualizado'
            : 'Usuario registrado. Contraseña por defecto: Admin12345'
          this.$toast({ message: msg, type: 'is-success', duration: 5000 })
          this.modalUsuario = false
          this.obtenerUsuarios()
        }
      }).catch(() => {
        this.guardandoUsuario = false
        this.$toast({ message: 'Error al guardar usuario', type: 'is-danger' })
      })
    },

    editar(idUsuario) {
      this.guardandoUsuario = true
      this.editandoUsuario = true
      this.modalUsuario = true
      HttpService.obtenerConDatos(idUsuario, 'obtener_usuario_id.php').then(resultado => {
        this.usuarioModal = resultado
        this.guardandoUsuario = false
      }).catch(() => {
        this.guardandoUsuario = false
        this.$toast({ message: 'Error al cargar usuario', type: 'is-danger' })
      })
    },

    eliminar(usuario) {
      this.$buefy.dialog.confirm({
        title: 'Eliminar el usuario ' + usuario.nombre,
        message: '¿Seguro que deseas eliminar el usuario? Esta acción no se puede deshacer',
        confirmText: 'Sí, eliminar',
        cancelText: 'No, salir',
        type: 'is-danger',
        hasIcon: true,
        onConfirm: () => {
          HttpService.eliminar("eliminar_usuario.php", usuario.id)
            .then(eliminado => {
              if (eliminado) {
                this.obtenerUsuarios()
                this.$toast('Usuario eliminado')
              }
            })

        }
      })
    },

    obtenerUsuarios() {
      this.cargando = true
      HttpService.obtener("obtener_usuarios.php")
        .then(resultado => {
          this.usuarios = resultado
          this.cargando = false
        })
        .catch(() => {
          this.cargando = false
          this.$toast({ message: 'Error al cargar usuarios. Verifica el servidor.', type: 'is-danger' })
        })
    }
  }
}
</script>