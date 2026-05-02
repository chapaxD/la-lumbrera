<template>
  <section class="section">
    <div class="container is-fluid">
      <nav class="level">
        <div class="level-left">
          <div class="level-item">
            <p class="title is-1 has-text-weight-bold">
              <b-icon icon="calendar-clock"
                      size="is-large"
                      type="is-primary"></b-icon>
              Gestión de Reservas
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
                      
                      icon-left="plus"
                      @click="nuevaReserva">
              Nueva Reserva
            </b-button>
          </div>
        </div>
      </nav>

      <div class="box">
        <b-table :data="reservas"
                 :loading="cargando"
                 :paginated="true"
                 :per-page="perPage"
                 pagination-position="bottom"
                 striped
                 hovered>
          <b-table-column field="fecha"
                          label="Fecha/Hora"
                          v-slot="props"
                          sortable>
            {{ props.row.fecha | formatFecha }} - <b>{{ props.row.hora }}</b>
          </b-table-column>
          <b-table-column field="nombre_cliente"
                          label="Cliente"
                          v-slot="props"
                          sortable
                          searchable>
            <b>{{ props.row.nombre_cliente }}</b>
            <b-tooltip v-if="estaVencida(props.row)"
                       label="Lleva más de 30 min de espera"
                       type="is-warning"
                       position="is-right">
              <b-tag type="is-warning"
                     size="is-small"
                     style="margin-left:6px">⏰ +30 min</b-tag>
            </b-tooltip>
            <br><small class="has-text-grey">{{ props.row.telefono }}</small>
          </b-table-column>
          <b-table-column field="idMesa"
                          label="Mesa"
                          v-slot="props"
                          sortable>
            <b-tag type="is-info"
                   v-if="props.row.idMesa">Mesa #{{ props.row.idMesa }}</b-tag>
            <b-tag type="is-danger"
                   v-else>EVENTO TOTAL 🏢</b-tag>
          </b-table-column>
          <b-table-column field="personas"
                          label="Gente"
                          v-slot="props"
                          centered>
            <b-icon icon="account-group"
                    size="is-small"></b-icon> {{ props.row.personas }}
          </b-table-column>
          <b-table-column field="adelanto"
                          label="Adelanto"
                          v-slot="props"
                          centered>
            <b-tag v-if="props.row.adelanto > 0"
                   type="is-success"
                   size="is-small">Bs. {{ Math.round(props.row.adelanto) }}</b-tag>
            <span v-else class="has-text-grey-light">—</span>
          </b-table-column>
          <b-table-column field="estado"
                          label="Estado"
                          v-slot="props"
                          centered>
            <!-- PENDIENTE: puede avanzar a CONFIRMADA, CANCELADA, NO-SHOW -->
            <b-dropdown v-if="props.row.estado === 'PENDIENTE'"
                        :value="props.row.estado"
                        @input="val => { props.row.estado = val; cambiarEstado(props.row) }">
              <template #trigger>
                <b-button :type="tipoEstado(props.row.estado)"
                          size="is-small"
                          icon-right="chevron-down">{{ props.row.estado }}</b-button>
              </template>
              <b-dropdown-item value="CONFIRMADA">CONFIRMADA</b-dropdown-item>
              <b-dropdown-item value="NO-SHOW">NO-SHOW</b-dropdown-item>
              <b-dropdown-item value="CANCELADA">CANCELADA</b-dropdown-item>
            </b-dropdown>
            <!-- CONFIRMADA: puede ir a CANCELADA o NO-SHOW (SENTADA solo via botón) -->
            <b-dropdown v-else-if="props.row.estado === 'CONFIRMADA'"
                        :value="props.row.estado"
                        @input="val => { props.row.estado = val; cambiarEstado(props.row) }">
              <template #trigger>
                <b-button :type="tipoEstado(props.row.estado)"
                          size="is-small"
                          icon-right="chevron-down">{{ props.row.estado }}</b-button>
              </template>
              <b-dropdown-item value="NO-SHOW">NO-SHOW</b-dropdown-item>
              <b-dropdown-item value="CANCELADA">CANCELADA</b-dropdown-item>
            </b-dropdown>
            <!-- SENTADA / NO-SHOW / CANCELADA / COMPLETADA: estado final, bloqueado -->
            <b-tag v-else
                   :type="tipoEstado(props.row.estado)"
                   size="is-small"
                   rounded>{{ props.row.estado }}</b-tag>
          </b-table-column>
          <b-table-column field="usuario_registro"
                          label="Registró"
                          v-slot="props">
            {{ props.row.usuarioNombre }}
          </b-table-column>
          <b-table-column label="Acciones"
                          v-slot="props">
            <b-button v-if="props.row.estado === 'PENDIENTE' || props.row.estado === 'CONFIRMADA'"
                      type="is-success"
                      icon-left="seat"
                      size="is-small"
                      style="margin-right:4px"
                      @click="sentarCliente(props.row)"></b-button>
            <b-button type="is-danger"
                      icon-left="delete"
                      size="is-small"
                      @click="eliminar(props.row)"
                      :disabled="['SENTADA', 'COMPLETADA'].includes(props.row.estado)"></b-button>
          </b-table-column>
          <template #detail="props">
            <div class="content">
              <p><b>Notas:</b> {{ props.row.notas || 'Sin observaciones' }}</p>
              <template v-if="!props.row.idMesa">
                <hr class="my-2">
                <p class="has-text-weight-bold has-text-danger mb-1">🏢 Evento — Restaurante Completo</p>
                <p><b>Personas:</b> {{ props.row.personas }}</p>
                <template v-if="props.row.menu_evento">
                  <p><b>Menú a servir:</b></p>
                  <table class="table is-narrow is-size-7" v-if="parsearMenu(props.row.menu_evento).length > 0">
                    <thead><tr><th>Plato</th><th class="has-text-centered">Cant.</th><th class="has-text-right">Subtotal</th></tr></thead>
                    <tbody>
                      <tr v-for="(item, i) in parsearMenu(props.row.menu_evento)" :key="i">
                        <td>{{ item.nombre }}</td>
                        <td class="has-text-centered">{{ item.cantidad }}</td>
                        <td class="has-text-right">Bs. {{ (item.precio * item.cantidad).toFixed(2) }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <p v-else><span style="white-space:pre-line">{{ props.row.menu_evento }}</span></p>
                </template>
                <p v-if="props.row.costo_total_evento > 0">
                  <b>Costo total del evento:</b>
                  <b-tag type="is-danger" size="is-medium" class="ml-2">Bs. {{ Number(props.row.costo_total_evento).toFixed(2) }}</b-tag>
                </p>
                <p v-if="props.row.adelanto > 0">
                  <b>Adelanto recibido:</b>
                  <b-tag type="is-success" size="is-medium" class="ml-2">Bs. {{ Math.round(props.row.adelanto) }}</b-tag>
                  <span v-if="props.row.costo_total_evento > 0" class="has-text-grey ml-2">
                    (Saldo: Bs. {{ (Number(props.row.costo_total_evento) - Number(props.row.adelanto)).toFixed(2) }})
                  </span>
                </p>
              </template>
            </div>
          </template>
        </b-table>
      </div>
    </div>

    <!-- Modal Sentar Cliente -->
    <b-modal v-model="mostrarModalSentar"
             has-modal-card
             trap-focus
             :can-cancel="false">
      <div class="modal-card"
           style="min-width:380px">
        <header class="modal-card-head"
                style="background:var(--color-primario);">
          <p class="modal-card-title"
             style="color:#fff">🪑 Sentar cliente</p>
          <button class="delete"
                  @click="mostrarModalSentar = false"></button>
        </header>
        <section class="modal-card-body"
                 v-if="reservaASentar">
          <div class="box"
               style="margin-bottom:1rem;background:#f5f5f5">
            <p><b>Cliente:</b> {{ reservaASentar.nombre_cliente }}</p>
            <p v-if="reservaASentar.telefono"><b>Teléfono:</b> {{ reservaASentar.telefono }}</p>
            <p><b>Mesa:</b> {{ reservaASentar.idMesa ? 'Mesa #' + reservaASentar.idMesa : 'Restaurante completo' }}</p>
            <p><b>Personas:</b> {{ reservaASentar.personas }}</p>
            <p v-if="reservaASentar.adelanto > 0"><b>Adelanto:</b> <b-tag type="is-success">Bs. {{ Math.round(reservaASentar.adelanto) }}</b-tag></p>
            <p v-if="reservaASentar.notas"><b>Notas:</b> {{ reservaASentar.notas }}</p>
            <template v-if="!reservaASentar.idMesa">
              <hr class="my-2">
              <p v-if="reservaASentar.menu_evento" class="mb-1"><b>Menú:</b><br><span style="white-space:pre-line;font-size:0.9em">{{ reservaASentar.menu_evento }}</span></p>
              <p v-if="reservaASentar.costo_total_evento > 0">
                <b>Total evento:</b>
                <b-tag type="is-danger" class="ml-1">Bs. {{ Number(reservaASentar.costo_total_evento).toFixed(2) }}</b-tag>
                <span v-if="reservaASentar.adelanto > 0" class="has-text-grey ml-2">
                  Saldo pendiente: Bs. {{ (Number(reservaASentar.costo_total_evento) - Number(reservaASentar.adelanto)).toFixed(2) }}
                </span>
              </p>
            </template>
          </div>
          <b-field label="Asignar mesero">
            <b-select v-model="meseroSeleccionado"
                      placeholder="Selecciona un mesero"
                      expanded>
              <option :value="null">— Sin asignar (yo atiendo) —</option>
              <option v-for="m in meseros"
                      :key="m.id"
                      :value="m">{{ m.nombre }}</option>
            </b-select>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button label="Cancelar"
                    type="is-dark"
                    @click="mostrarModalSentar = false" />
          <b-button label="Confirmar sentada"
                    type="is-success"
                    icon-left="seat"
                    @click="confirmarSentada" />
        </footer>
      </div>
    </b-modal>

    <!-- Modal Nueva Reserva -->
    <b-modal v-model="mostrarModal"
             has-modal-card
             trap-focus
             :can-cancel="false">
      <div class="modal-card"
           style="width: auto">
        <header class="modal-card-head"
                style="background:var(--color-primario); box-shadow:none">
          <p class="modal-card-title"
             style="color:#fff">
            <b-icon icon="calendar-plus"
                    size="is-small"
                    style="color:#fff"></b-icon>
            &nbsp;Programar Reserva
          </p>
          <button class="delete"
                  @click="mostrarModal = false"></button>
        </header>
        <section class="modal-card-body">
          <b-field label="Nombre del Cliente">
            <b-autocomplete v-model="form.nombre_cliente"
                            :data="sugerenciasClientes"
                            placeholder="Escribe el nombre..."
                            icon="account-search"
                            field="nombre_completo"
                            :loading="buscandoCliente"
                            @typing="buscarCliente"
                            @select="seleccionarCliente"
                            clearable>
              <template slot="empty">Sin resultados</template>
            </b-autocomplete>
          </b-field>
          <b-field label="Teléfono">
            <b-input v-model="form.telefono"
                     placeholder="Opcional"
                     icon="phone"></b-input>
          </b-field>
          <div class="columns">
            <div class="column">
              <b-field label="Fecha">
                <b-datepicker v-model="form.fecha_obj"
                              :min-date="new Date()"
                              placeholder="Selecciona..."
                              icon="calendar"
                              required></b-datepicker>
              </b-field>
            </div>
            <div class="column">
              <b-field label="Hora">
                <b-timepicker v-model="form.hora_obj"
                              placeholder="Selecciona..."
                              icon="clock"
                              required></b-timepicker>
              </b-field>
            </div>
          </div>
          <div class="columns">
            <div class="column">
              <b-field label="Personas">
                <b-input v-model="form.personas"
                         type="number"
                         min="1"></b-input>
              </b-field>
            </div>
            <div class="column">
              <b-field label="Tipo de Reserva">
                <b-switch v-model="esEventoTotal"
                          type="is-danger">
                  {{ esEventoTotal ? 'Restaurante Completo' : 'Mesa Específica' }}
                </b-switch>
              </b-field>
            </div>
          </div>
          <b-field label="Mesa"
                   v-if="!esEventoTotal">
            <b-input v-model="form.idMesa"
                     type="number"
                     placeholder="Número de mesa"
                     :required="!esEventoTotal"></b-input>
          </b-field>

          <!-- Campos especiales para evento total -->
          <template v-if="esEventoTotal">
            <div class="notification is-danger is-light py-3 px-4 mb-3">
              <b-icon icon="store" size="is-small"></b-icon>
              <strong> Evento — Restaurante Completo</strong>: bloquea toda la agenda de ese día. No se podrán crear otras reservas.
            </div>

            <b-field label="Buscar platos / insumos">
              <b-autocomplete
                v-model="busquedaInsumoEvento"
                :data="insumosFiltrados"
                placeholder="Escribe el nombre del plato..."
                icon="magnify"
                field="nombre"
                :loading="cargandoInsumos"
                @select="agregarInsumoEvento"
                clearable>
                <template slot-scope="props">
                  <div class="is-flex is-justify-content-space-between">
                    <span>{{ props.option.nombre }}</span>
                    <span class="has-text-grey">Bs. {{ Number(props.option.precio).toFixed(2) }}</span>
                  </div>
                </template>
                <template slot="empty">Sin resultados</template>
              </b-autocomplete>
            </b-field>

            <div v-if="itemsEvento.length > 0" class="box p-3 mb-3">
              <table class="table is-fullwidth is-narrow is-size-7">
                <thead>
                  <tr>
                    <th>Plato</th>
                    <th class="has-text-right">Precio</th>
                    <th class="has-text-centered">Cant.</th>
                    <th class="has-text-right">Subtotal</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in itemsEvento" :key="idx">
                    <td>{{ item.nombre }}</td>
                    <td class="has-text-right">{{ Number(item.precio).toFixed(2) }}</td>
                    <td class="has-text-centered" style="width:100px">
                      <div class="field has-addons" style="justify-content:center">
                        <p class="control"><b-button size="is-small" @click="item.cantidad > 1 ? item.cantidad-- : null">-</b-button></p>
                        <p class="control"><b-input size="is-small" v-model.number="item.cantidad" type="number" min="1" style="width:48px" custom-class="has-text-centered"></b-input></p>
                        <p class="control"><b-button size="is-small" @click="item.cantidad++">+</b-button></p>
                      </div>
                    </td>
                    <td class="has-text-right has-text-weight-bold">Bs. {{ (item.precio * item.cantidad).toFixed(2) }}</td>
                    <td><b-button size="is-small" type="is-danger is-light" icon-left="close" @click="itemsEvento.splice(idx, 1)"></b-button></td>
                  </tr>
                </tbody>
              </table>
              <div class="has-text-right is-size-5 has-text-weight-bold has-text-primary mt-2">
                Total evento: Bs. {{ totalEvento.toFixed(2) }}
              </div>
            </div>
            <p v-else class="has-text-grey has-text-centered mb-3">Busca y agrega los platos que se van a servir</p>
          </template>

          <b-field label="Notas / Observaciones">
            <b-input type="textarea"
                     v-model="form.notas"
                     placeholder="Ej: Cumpleaños, alérgenos, etc."></b-input>
          </b-field>
          <b-field label="Adelanto (Bs.)">
            <b-input v-model="form.adelanto"
                     type="number"
                     min="0"
                     step="1"
                     placeholder="0"
                     icon="cash"></b-input>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button label="Cancelar"
                    type="is-dark"
                    @click="mostrarModal = false" />
          <b-button label="Guardar Reserva"
                    type="is-primary"
                    @click="guardar" />
        </footer>
      </div>
    </b-modal>
  </section>
</template>

<script>
import HttpService from "../../Servicios/HttpService";

export default {
  data: () => ({
    reservas: [],
    meseros: [],
    cargando: false,
    perPage: 10,
    mostrarModal: false,
    mostrarModalSentar: false,
    reservaASentar: null,
    meseroSeleccionado: null,
    esEventoTotal: false,
    itemsEvento: [],
    insumosDisponibles: [],
    busquedaInsumoEvento: '',
    cargandoInsumos: false,
    sugerenciasClientes: [],
    buscandoCliente: false,
    _timerCliente: null,
    _intervalNoShow: null,
    form: {
      nombre_cliente: "",
      telefono: "",
      fecha_obj: new Date(),
      hora_obj: new Date(),
      personas: 2,
      idMesa: null,
      notas: "",
      adelanto: 0
    }
  }),
  computed: {
    totalEvento() {
      return this.itemsEvento.reduce((s, i) => s + (parseFloat(i.precio) || 0) * (parseInt(i.cantidad) || 1), 0)
    },
    insumosFiltrados() {
      if (!this.busquedaInsumoEvento || this.busquedaInsumoEvento.length < 2) return []
      const q = this.busquedaInsumoEvento.toLowerCase()
      return this.insumosDisponibles.filter(i => i.nombre.toLowerCase().includes(q)).slice(0, 15)
    }
  },
  watch: {
    esEventoTotal(val) {
      if (val && this.insumosDisponibles.length === 0) this.cargarInsumosEvento()
    }
  },
  mounted() {
    this.obtenerReservas();
    HttpService.obtener('obtener_meseros.php').then(d => { this.meseros = d || []; });
    this._intervalNoShow = setInterval(this.autoCheckNoShow, 60000);
  },
  beforeDestroy() {
    clearInterval(this._intervalNoShow);
  },
  methods: {
    cargarInsumosEvento() {
      this.cargandoInsumos = true
      HttpService.obtenerConDatos({ tipo: 'PLATILLO', categoria: '', nombre: '' }, 'obtener_insumos.php').then(d => {
        this.insumosDisponibles = (d || []).filter(i => parseFloat(i.precio) > 0)
        this.cargandoInsumos = false
      })
    },
    agregarInsumoEvento(insumo) {
      if (!insumo) return
      const existente = this.itemsEvento.find(i => i.id === insumo.id)
      if (existente) {
        existente.cantidad++
      } else {
        this.itemsEvento.push({ id: insumo.id, nombre: insumo.nombre, precio: parseFloat(insumo.precio) || 0, cantidad: 1 })
      }
      this.busquedaInsumoEvento = ''
    },
    buscarCliente(q) {
      clearTimeout(this._timerCliente);
      if (!q || q.length < 2) { this.sugerenciasClientes = []; return; }
      this._timerCliente = setTimeout(() => {
        this.buscandoCliente = true;
        HttpService.obtener('obtener_clientes.php?q=' + encodeURIComponent(q)).then(datos => {
          this.sugerenciasClientes = (datos || []).map(c => ({
            ...c,
            nombre_completo: c.nombre + (c.apellido ? ' ' + c.apellido : '')
          }));
          this.buscandoCliente = false;
        });
      }, 350);
    },
    seleccionarCliente(cliente) {
      if (!cliente) return;
      this.form.nombre_cliente = cliente.nombre + (cliente.apellido ? ' ' + cliente.apellido : '');
      if (cliente.telefono) this.form.telefono = cliente.telefono;
    },
    obtenerReservas() {
      this.cargando = true;
      HttpService.obtener("obtener_reservas.php").then(datos => {
        this.reservas = datos || [];
        this.cargando = false;
      });
    },
    nuevaReserva() {
      this.form = {
        nombre_cliente: "",
        telefono: "",
        fecha_obj: new Date(),
        hora_obj: new Date(),
        personas: 2,
        idMesa: null,
        notas: "",
        adelanto: 0
      };
      this.itemsEvento = [];
      this.busquedaInsumoEvento = '';
      this.sugerenciasClientes = [];
      this.esEventoTotal = false;
      this.mostrarModal = true;
    },
    guardar() {
      if (!this.form.nombre_cliente || !this.form.fecha_obj || !this.form.hora_obj) {
        this.$toast({ message: "Nombre, fecha y hora son obligatorios", type: "is-warning" });
        return;
      }
      // Validar que la hora de reserva sea en el futuro
      const fechaReserva = new Date(
        this.form.fecha_obj.getFullYear(),
        this.form.fecha_obj.getMonth(),
        this.form.fecha_obj.getDate(),
        this.form.hora_obj.getHours(),
        this.form.hora_obj.getMinutes()
      );
      if (fechaReserva <= new Date()) {
        this.$toast({ message: "La hora de la reserva debe ser mayor a la hora actual", type: "is-warning" });
        return;
      }

      const n = (v) => v < 10 ? '0' + v : v;
      const fechaLocal = `${this.form.fecha_obj.getFullYear()}-${n(this.form.fecha_obj.getMonth() + 1)}-${n(this.form.fecha_obj.getDate())}`;
      const horaLocal = `${n(this.form.hora_obj.getHours())}:${n(this.form.hora_obj.getMinutes())}`;

      const payload = {
        ...this.form,
        fecha: fechaLocal,
        hora: horaLocal,
        idMesa: this.esEventoTotal ? null : this.form.idMesa,
        menu_evento: this.esEventoTotal ? JSON.stringify(this.itemsEvento) : null,
        costo_total_evento: this.esEventoTotal ? this.totalEvento : 0,
        idUsuario: localStorage.getItem("idUsuario")
      };

      // Verificar si la mesa está ocupada ahora mismo (si la reserva es para hoy)
      if (!this.esEventoTotal && fechaLocal === new Date().toISOString().split('T')[0]) {
          const mesaOcupada = this.$parent.$parent.mesas && this.$parent.$parent.mesas.find(m => m.mesa.idMesa == this.form.idMesa && m.mesa.estado === 'ocupada');
          // Nota: El acceso a mesas via parent puede ser fragil dependiendo de la estructura, 
          // pero para este caso intentaremos una validacion simple o solo procederemos al backend.
      }

      HttpService.registrar(payload, "registrar_reserva.php").then(resultado => {
        if (resultado && resultado.ok) {
          this.$toast({ message: "Reserva registrada correctamente", type: "is-success" });
          this.mostrarModal = false;
          this.obtenerReservas();
        } else if (resultado && resultado.error === 'SOLAPAMIENTO') {
          const mesa = resultado.idMesa ? `Mesa #${resultado.idMesa}` : 'Restaurante completo';
          this.$buefy.dialog.alert({
            title: '⚠ Horario ocupado',
            message: `Ya existe una reserva activa de <b>${resultado.cliente}</b> a las <b>${resultado.hora}</b> para <b>${mesa}</b> en esa misma fecha.<br><br>Las reservas requieren al menos <b>2 horas de separación</b> entre turnos.`,
            type: 'is-warning',
            confirmText: 'Entendido'
          });
        } else if (resultado && resultado.error === 'MESA_OCUPADA_AHORA') {
          this.$buefy.dialog.alert({
            title: 'Mesa ocupada actualmente',
            message: `No puedes reservar la <b>Mesa ${this.form.idMesa}</b> para hoy porque ya tiene clientes sentados con un pedido activo.<br><br>Primero deben cobrar o liberar la mesa.`,
            type: 'is-warning',
            confirmText: 'Entendido'
          });
        } else if (resultado && resultado.error === 'MESA_OCUPADA') {
            this.$toast({ message: "La mesa ya tiene clientes sentados ahora mismo", type: "is-warning" });
        } else {
          this.$toast({ message: "Error al registrar la reserva", type: "is-danger" });
        }
      });
    },
    cambiarEstado(reserva) {
      const payload = { 
        id: reserva.id, 
        estado: reserva.estado,
        idUsuario: localStorage.getItem("idUsuario")
      };
      HttpService.registrar(payload, "cambiar_estado_reserva.php").then(resultado => {
        if (resultado) {
          this.$toast({ message: "Estado actualizado", type: "is-info" });
        }
      });
    },
    eliminar(reserva) {
      this.$buefy.dialog.confirm({
        title: "Eliminar Reserva",
        message: "¿Seguro que deseas eliminar la reserva de " + reserva.nombre_cliente + "?",
        confirmText: "Eliminar",
        type: "is-danger",
        onConfirm: () => {
          HttpService.eliminar("eliminar_reserva.php", reserva.id).then(() => {
            this.obtenerReservas();
          });
        }
      });
    },
    tipoEstado(estado) {
      if (estado === "CONFIRMADA") return "is-success";
      if (estado === "SENTADA") return "is-primary";
      if (estado === "CANCELADA") return "is-danger";
      if (estado === "NO-SHOW") return "is-dark";
      if (estado === "COMPLETADA") return "is-dark";
      return "is-warning";
    },
    sentarCliente(reserva) {
      this.reservaASentar = reserva;
      this.meseroSeleccionado = null;
      this.mostrarModalSentar = true;
    },
    confirmarSentada() {
      const reserva = this.reservaASentar;
      const mesero = this.meseroSeleccionado;
      const atiende = mesero ? mesero.nombre : (localStorage.getItem('nombre') || 'Reserva');
      const idUsuario = mesero ? mesero.id : localStorage.getItem('idUsuario');

      if (reserva.idMesa) {
        const payload = {
          id: reserva.idMesa,
          atiende,
          idUsuario,
          rolSolicitante: localStorage.getItem('rol'),
          desdeReserva: true,
          total: 0,
          cliente: reserva.nombre_cliente,
          insumos: []
        };
        HttpService.registrar(payload, 'ocupar_mesa.php').then(resultado => {
          // Si el backend devuelve un objeto con error o un false directamente
          if (resultado === false || (resultado && resultado.ok === false)) {
            const msg = (resultado && resultado.error === 'MESA_OCUPADA_ACTIVA') 
                ? '¡Error! La mesa ya tiene un pedido activo. Debe cobrar o cancelar la mesa antes de sentar la reserva.'
                : 'Reserva SENTADA, pero la mesa ya estaba ocupada por otro usuario.';
            
            this.$buefy.dialog.alert({
                title: 'Mesa ocupada',
                message: msg,
                type: 'is-danger',
                hasIcon: true,
                icon: 'alert-circle',
                confirmText: 'Entendido'
            });
          } else {
            // Solo cambiamos el estado si se pudo ocupar la mesa
            reserva.estado = 'SENTADA';
            this.cambiarEstado(reserva);
            this.mostrarModalSentar = false;
            this.$toast({ message: 'Cliente sentado correctamente', type: 'is-success' });
            this.obtenerReservas();
          }
        });
      } else {
        // Evento total o similar sin mesa fija
        reserva.estado = 'SENTADA';
        this.cambiarEstado(reserva);
        this.mostrarModalSentar = false;
      }
    },
    autoCheckNoShow() {
      const vencidas = this.reservas.filter(r => this.estaVencida(r));
      if (!vencidas.length) return;
      vencidas.forEach(reserva => {
        reserva.estado = 'NO-SHOW';
        const payload = { 
          id: reserva.id, 
          estado: 'NO-SHOW',
          idUsuario: localStorage.getItem("idUsuario")
        };
        HttpService.registrar(payload, 'cambiar_estado_reserva.php');
      });
      this.$toast({ message: `${vencidas.length} reserva(s) marcadas como NO-SHOW por tiempo vencido`, type: 'is-warning' });
    },
    parsearMenu(raw) {
      if (!raw) return []
      try {
        const data = JSON.parse(raw)
        return Array.isArray(data) ? data : []
      } catch (e) { return [] }
    },
    estaVencida(reserva) {
      if (reserva.estado !== 'PENDIENTE' && reserva.estado !== 'CONFIRMADA') return false;
      const dt = new Date(reserva.fecha + 'T' + reserva.hora);
      return (new Date() - dt) > 30 * 60 * 1000;
    }
  }
};
</script>
