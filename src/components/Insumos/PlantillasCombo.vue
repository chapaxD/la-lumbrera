<template>
  <section class="section">
    <h1 class="title is-4">
      <b-icon icon="food-variant" class="mr-2"></b-icon>
      Plantillas de menú / combo
    </h1>
    <p class="subtitle is-6 has-text-grey">
      Define slots (ej. sopa, segundo) y qué insumos puede elegir el cliente en cada uno. Luego enlaza la plantilla a un insumo con tipo de venta «Combo».
    </p>

    <b-button type="is-primary" icon-left="plus" class="mb-4" @click="abrirNuevo">Nueva plantilla</b-button>

    <b-table :data="plantillas" :loading="cargando" striped>
      <b-table-column field="nombre" label="Nombre" v-slot="p">{{ p.row.nombre }}</b-table-column>
      <b-table-column field="descuento_pct" label="Dto. %" numeric v-slot="p">{{ p.row.descuento_pct }}</b-table-column>
      <b-table-column field="activo" label="Activa" v-slot="p">
        <b-tag :type="p.row.activo ? 'is-success' : 'is-light'" size="is-small">{{ p.row.activo ? 'Sí' : 'No' }}</b-tag>
      </b-table-column>
      <b-table-column label="" v-slot="p">
        <b-button size="is-small" @click="editar(p.row)">Editar</b-button>
        <b-button size="is-small" type="is-danger" outlined class="ml-1" @click="eliminar(p.row)">Eliminar</b-button>
      </b-table-column>
    </b-table>

    <b-modal :active.sync="modal" has-modal-card trap-focus @close="cerrarModal">
      <div class="modal-card" style="width: auto; max-width: 640px">
        <header class="modal-card-head">
          <p class="modal-card-title">{{ borrador.id ? 'Editar plantilla' : 'Nueva plantilla' }}</p>
        </header>
        <section class="modal-card-body">
          <b-field label="Nombre">
            <b-input v-model="borrador.nombre" placeholder="Ej. Almuerzo ejecutivo"></b-input>
          </b-field>
          <b-field label="Descripción">
            <b-input v-model="borrador.descripcion" type="textarea" rows="2"></b-input>
          </b-field>
          <b-field label="Descuento % (informativo)">
            <b-input v-model.number="borrador.descuento_pct" type="number" min="0" step="0.5"></b-input>
          </b-field>
          <b-field>
            <b-checkbox v-model="borrador.activo">Plantilla activa</b-checkbox>
          </b-field>

          <hr />
          <p class="heading">Slots (categorías de elección)</p>
          <div v-for="(slot, idx) in borrador.slots" :key="'s' + idx" class="box py-3 mb-3">
            <b-field grouped>
              <b-field label="Etiqueta" expanded>
                <b-input v-model="slot.etiqueta" placeholder="Ej. Sopa"></b-input>
              </b-field>
              <b-field label="Orden">
                <b-input v-model.number="slot.orden" type="number"></b-input>
              </b-field>
              <b-field>
                <b-button type="is-danger" outlined icon-left="delete" @click="borrador.slots.splice(idx, 1)"></b-button>
              </b-field>
            </b-field>
            <b-field label="Insumos permitidos en este slot">
              <b-taginput
                v-model="slot._tags"
                :data="insumosFiltrados"
                field="nombre"
                autocomplete
                :open-on-focus="false"
                placeholder="Buscar y agregar"
                @typing="filtrarInsumos"
              >
                <template slot-scope="props">{{ props.option.nombre }} <span class="is-size-7 has-text-grey">#{{ props.option.id }}</span></template>
              </b-taginput>
            </b-field>
          </div>
          <b-button type="is-info" outlined icon-left="plus" size="is-small" @click="agregarSlot">Agregar slot</b-button>
        </section>
        <footer class="modal-card-foot">
          <b-button type="is-primary" :loading="guardando" @click="guardar">Guardar</b-button>
          <b-button @click="modal = false">Cancelar</b-button>
        </footer>
      </div>
    </b-modal>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
  name: 'PlantillasCombo',
  data() {
    return {
      plantillas: [],
      insumosCatalogo: [],
      insumosFiltrados: [],
      cargando: false,
      guardando: false,
      modal: false,
      borrador: this.estadoBorradorVacio()
    }
  },
  mounted() {
    this.cargar()
    this.cargarInsumos()
  },
  methods: {
    estadoBorradorVacio() {
      return {
        id: 0,
        nombre: '',
        descripcion: '',
        descuento_pct: 0,
        activo: true,
        slots: []
      }
    },
    agregarSlot() {
      this.borrador.slots.push({ etiqueta: '', orden: this.borrador.slots.length, _tags: [] })
    },
    async cargarInsumos() {
      try {
        const datos = await HttpService.obtenerConDatos({ tipo: '', categoria: '', nombre: '' }, 'obtener_insumos.php')
        this.insumosCatalogo = Array.isArray(datos) ? datos : []
        this.insumosFiltrados = this.insumosCatalogo.slice(0, 30)
      } catch (e) {
        this.insumosCatalogo = []
      }
    },
    filtrarInsumos(text) {
      const q = (text || '').toLowerCase()
      if (!q) {
        this.insumosFiltrados = this.insumosCatalogo.slice(0, 40)
        return
      }
      this.insumosFiltrados = this.insumosCatalogo
        .filter(i => (i.nombre || '').toLowerCase().includes(q) || String(i.codigo || '').toLowerCase().includes(q))
        .slice(0, 40)
    },
    async cargar() {
      this.cargando = true
      try {
        const datos = await HttpService.obtener('obtener_plantillas_combo.php')
        this.plantillas = Array.isArray(datos) ? datos : []
      } catch (e) {
        this.$toast({ message: 'No se pudieron cargar las plantillas (¿ejecutaste crear_tablas?).', type: 'is-danger' })
        this.plantillas = []
      }
      this.cargando = false
    },
    abrirNuevo() {
      this.borrador = this.estadoBorradorVacio()
      this.agregarSlot()
      this.modal = true
    },
    editar(row) {
      const slots = (row.slots || []).map((s, i) => ({
        etiqueta: s.etiqueta || '',
        orden: s.orden != null ? s.orden : i,
        _tags: (s.opciones || []).map(o => {
          const id = o.id_insumo
          const cat = this.insumosCatalogo.find(x => String(x.id) === String(id))
          return cat || { id, nombre: o.nombre_insumo || ('Insumo #' + id) }
        })
      }))
      this.borrador = {
        id: row.id,
        nombre: row.nombre || '',
        descripcion: row.descripcion || '',
        descuento_pct: parseFloat(row.descuento_pct) || 0,
        activo: !!row.activo,
        slots: slots.length ? slots : []
      }
      if (!this.borrador.slots.length) this.agregarSlot()
      this.modal = true
    },
    cerrarModal() {},
    async guardar() {
      if (!this.borrador.nombre.trim()) {
        this.$toast({ message: 'El nombre es obligatorio', type: 'is-warning' })
        return
      }
      const slotsPayload = this.borrador.slots
        .filter(s => (s.etiqueta || '').trim())
        .map((s, idx) => ({
          etiqueta: s.etiqueta.trim(),
          orden: s.orden != null ? s.orden : idx,
          opciones: (s._tags || []).map(t => ({ id_insumo: t.id })).filter(o => o.id_insumo)
        }))
      if (slotsPayload.some(s => s.opciones.length === 0)) {
        this.$toast({ message: 'Cada slot debe tener al menos un insumo permitido', type: 'is-warning' })
        return
      }
      this.guardando = true
      try {
        const res = await HttpService.registrar(
          {
            id: this.borrador.id || 0,
            nombre: this.borrador.nombre.trim(),
            descripcion: this.borrador.descripcion || '',
            descuento_pct: this.borrador.descuento_pct,
            activo: this.borrador.activo ? 1 : 0,
            slots: slotsPayload
          },
          'guardar_plantilla_combo.php'
        )
        if (res && res.ok) {
          this.$toast({ message: 'Plantilla guardada', type: 'is-success' })
          this.modal = false
          this.cargar()
        } else {
          this.$toast({ message: (res && res.error) || 'Error al guardar', type: 'is-danger' })
        }
      } catch (e) {
        this.$toast({ message: 'Error al guardar', type: 'is-danger' })
      }
      this.guardando = false
    },
    eliminar(row) {
      this.$buefy.dialog.confirm({
        title: 'Eliminar plantilla',
        message: `¿Eliminar «${row.nombre}»? Los insumos que la referencian quedarán con id de plantilla huérfano.`,
        confirmText: 'Eliminar',
        type: 'is-danger',
        onConfirm: async () => {
          await HttpService.registrar({ id: row.id }, 'eliminar_plantilla_combo.php')
          this.$toast({ message: 'Eliminada', type: 'is-success' })
          this.cargar()
        }
      })
    }
  }
}
</script>
