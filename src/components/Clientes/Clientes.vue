<template>
  <section>
    <!-- Encabezado -->
    <nav class="level mb-4">
      <div class="level-left">
        <div class="level-item">
          <p class="title is-1 has-text-weight-bold">
            <b-icon icon="account-multiple"
                    size="is-large"
                    type="is-primary"></b-icon>
            Clientes
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
          <b-button type="is-primary"
                    icon-left="account-plus"
                    @click="abrirNuevo">
            Nuevo Cliente
          </b-button>
        </div>
      </div>
    </nav>

    <!-- Buscador rápido -->
    <div class="box mb-4">
      <b-field label="Buscar cliente">
        <b-input v-model="busqueda"
                 placeholder="Nombre, apellido o NIT..."
                 icon="magnify"
                 @input="buscar"></b-input>
      </b-field>
    </div>

    <!-- Tabla -->
    <div class="box">
      <b-table :data="clientes"
               :loading="cargando"
               paginated
               :per-page="perPage"
               striped
               hoverable
               mobile-cards>
        <b-table-column field="nombre"
                        label="Nombre"
                        v-slot="props"
                        sortable
                        searchable>
          <b>{{ props.row.nombre }}</b>
          <span v-if="props.row.apellido"> {{ props.row.apellido }}</span>
        </b-table-column>

        <b-table-column field="telefono"
                        label="Teléfono"
                        v-slot="props">
          <span v-if="props.row.telefono">
            <b-icon icon="phone"
                    size="is-small"></b-icon>
            {{ props.row.telefono }}
          </span>
          <span v-else
                class="has-text-grey">—</span>
        </b-table-column>

        <b-table-column field="nit"
                        label="NIT / CI"
                        v-slot="props">
          <span v-if="props.row.nit">{{ props.row.nit }}</span>
          <span v-else
                class="has-text-grey">—</span>
        </b-table-column>

        <b-table-column field="email"
                        label="Email"
                        v-slot="props">
          <span v-if="props.row.email">{{ props.row.email }}</span>
          <span v-else
                class="has-text-grey">—</span>
        </b-table-column>

        <b-table-column field="notas"
                        label="Notas"
                        v-slot="props">
          <span v-if="props.row.notas"
                class="has-text-grey is-size-7">{{ props.row.notas }}</span>
          <span v-else
                class="has-text-grey">—</span>
        </b-table-column>

        <b-table-column label="Acciones"
                        v-slot="props"
                        centered
                        width="120">
          <div class="buttons is-centered">
            <b-button size="is-small"
                      type="is-info"
                      icon-left="pen"
                      @click="abrirEditar(props.row)"></b-button>
            <b-button size="is-small"
                      type="is-danger"
                      icon-left="delete"
                      @click="confirmarEliminar(props.row)"></b-button>
          </div>
        </b-table-column>

        <template #empty>
          <div class="has-text-centered has-text-grey py-5">
            <b-icon icon="account-off"
                    size="is-large"></b-icon>
            <p class="mt-2">No hay clientes registrados</p>
          </div>
        </template>
      </b-table>
    </div>

    <!-- Modal nuevo / editar -->
    <b-modal v-model="mostrarModal"
             has-modal-card
             trap-focus
             :can-cancel="false">
      <div class="modal-card"
           style="min-width: 460px">
        <header class="modal-card-head"
                style="background:var(--color-primario); box-shadow:none">
          <p class="modal-card-title"
             style="color:#fff">
            <b-icon :icon="editando ? 'pencil' : 'account-plus'"
                    size="is-small"
                    style="color:#fff"></b-icon>
            &nbsp;{{ editando ? 'Editar Cliente' : 'Nuevo Cliente' }}
          </p>
          <button class="delete"
                  @click="cerrarModal"></button>
        </header>
        <section class="modal-card-body">
          <div class="columns">
            <div class="column">
              <b-field label="Nombre *">
                <b-input v-model="form.nombre"
                         placeholder="Ej: Juan"
                         required></b-input>
              </b-field>
            </div>
            <div class="column">
              <b-field label="Apellido">
                <b-input v-model="form.apellido"
                         placeholder="Ej: Pérez"></b-input>
              </b-field>
            </div>
          </div>
          <div class="columns">
            <div class="column">
              <b-field label="Teléfono">
                <b-input v-model="form.telefono"
                         placeholder="Ej: 72345678"
                         icon="phone"></b-input>
              </b-field>
            </div>
            <div class="column">
              <b-field label="NIT / CI">
                <b-input v-model="form.nit"
                         placeholder="Ej: 12345678"
                         icon="card-account-details-outline"></b-input>
              </b-field>
            </div>
          </div>
          <b-field label="Email">
            <b-input v-model="form.email"
                     type="email"
                     placeholder="Ej: cliente@correo.com"
                     icon="email-outline"></b-input>
          </b-field>
          <b-field label="Dirección">
            <b-input v-model="form.direccion"
                     placeholder="Ej: Av. Heroínas 123"
                     icon="map-marker"></b-input>
          </b-field>
          <b-field label="Notas">
            <b-input type="textarea"
                     v-model="form.notas"
                     placeholder="Ej: alérgico a mariscos, prefiere mesa cerca de ventana..."></b-input>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button @click="cerrarModal">Cancelar</b-button>
          <b-button type="is-primary"
                    :loading="guardando"
                    @click="guardar">
            {{ editando ? 'Guardar cambios' : 'Registrar' }}
          </b-button>
        </footer>
      </div>
    </b-modal>

  </section>
</template>

<script>
import HttpService from "../../Servicios/HttpService";

const FORM_VACIO = () => ({
  nombre: '',
  apellido: '',
  telefono: '',
  nit: '',
  email: '',
  direccion: '',
  notas: ''
});

export default {
  data: () => ({
    clientes: [],
    perPage: 10,
    cargando: false,
    guardando: false,
    mostrarModal: false,
    editando: false,
    busqueda: '',
    form: FORM_VACIO(),
    _buscarTimer: null
  }),
  mounted() {
    this.cargarClientes();
  },
  methods: {
    cargarClientes(q = '') {
      this.cargando = true;
      HttpService.obtener('obtener_clientes.php' + (q ? `?q=${encodeURIComponent(q)}` : ''))
        .then(datos => {
          this.clientes = datos || [];
          this.cargando = false;
        });
    },
    buscar() {
      clearTimeout(this._buscarTimer);
      this._buscarTimer = setTimeout(() => {
        this.cargarClientes(this.busqueda);
      }, 350);
    },
    abrirNuevo() {
      this.form = FORM_VACIO();
      this.editando = false;
      this.mostrarModal = true;
    },
    abrirEditar(cliente) {
      this.form = {
        id: cliente.id,
        nombre: cliente.nombre || '',
        apellido: cliente.apellido || '',
        telefono: cliente.telefono || '',
        nit: cliente.nit || '',
        email: cliente.email || '',
        direccion: cliente.direccion || '',
        notas: cliente.notas || ''
      };
      this.editando = true;
      this.mostrarModal = true;
    },
    cerrarModal() {
      this.mostrarModal = false;
    },
    guardar() {
      if (!this.form.nombre.trim()) {
        this.$toast({ message: 'El nombre es obligatorio', type: 'is-warning' });
        return;
      }
      this.guardando = true;
      const api = this.editando ? 'editar_cliente.php' : 'registrar_cliente.php';
      HttpService.registrar(this.form, api).then(res => {
        this.guardando = false;
        if (res && res.ok) {
          this.$toast({ message: this.editando ? 'Cliente actualizado' : 'Cliente registrado', type: 'is-success' });
          this.cerrarModal();
          this.cargarClientes(this.busqueda);
        } else {
          this.$toast({ message: (res && res.error) || 'Error al guardar', type: 'is-danger' });
        }
      });
    },
    confirmarEliminar(cliente) {
      this.$buefy.dialog.confirm({
        title: 'Eliminar Cliente',
        message: `¿Eliminar a <b>${cliente.nombre} ${cliente.apellido || ''}</b>? Esta acción no se puede deshacer.`,
        confirmText: 'Eliminar',
        type: 'is-danger',
        onConfirm: () => {
          HttpService.eliminar('eliminar_cliente.php', cliente.id).then(() => {
            this.$toast({ message: 'Cliente eliminado', type: 'is-info' });
            this.cargarClientes(this.busqueda);
          });
        }
      });
    }
  }
};
</script>
