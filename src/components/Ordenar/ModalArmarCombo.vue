<template>
  <b-modal :active="active" @update:active="$emit('update:active', $event)" has-modal-card trap-focus aria-close-label="Cerrar">
    <div class="modal-card" style="width: 100%; max-width: 900px">
      <header class="modal-card-head">
        <p class="modal-card-title">Armar menú: {{ insumo ? insumo.nombre : '' }}</p>
      </header>
      <section class="modal-card-body" style="position: relative; min-height: 120px; overflow-x: hidden;">
        <b-loading :is-full-page="false" :active="cargandoPlantillaCombo"></b-loading>
        <template v-if="plantillaCombo && !cargandoPlantillaCombo">
          <b-field label="Cantidad de almuerzos a armar" class="mb-5">
            <b-numberinput v-model="comboNumMenus" :min="1" :max="50" controls-position="compact"
              size="is-medium"></b-numberinput>
          </b-field>

          <div class="notification is-info is-light py-2 px-3 mb-4">
            <b-icon icon="information" size="is-small" class="mr-1"></b-icon>
            Selecciona el total de platos para los <strong>{{ comboNumMenus }}</strong> almuerzos.
          </div>

          <div v-for="slot in plantillaCombo.slots || []" :key="'sl' + slot.id" class="mb-5">
            <div class="is-flex is-justify-content-space-between is-align-items-center mb-3">
              <h3 class="title is-5 mb-0 has-text-primary">
                {{ slot.etiqueta }}
              </h3>
              <b-tag :type="totalElegidoEnSlot(slot.id) === comboNumMenus ? 'is-success' : 'is-warning'"
                size="is-medium" rounded>
                {{ totalElegidoEnSlot(slot.id) }} / {{ comboNumMenus }}
              </b-tag>
            </div>

            <div class="columns is-multiline">
              <div v-for="op in slot.opciones" :key="'op' + op.id" class="column is-6-tablet is-12-mobile">
                <div class="box p-3 is-flex is-justify-content-space-between is-align-items-center"
                  :style="obtenerCantidadElegida(slot.id, op.id_insumo) > 0 ? 'border: 2px solid #48c78e; background: #f6fffa;' : ''">
                  <div style="flex: 1;">
                    <p class="has-text-weight-bold is-size-6">{{ op.nombre_insumo }}</p>
                    <p class="is-size-7 has-text-grey">
                      Disp: {{ obtenerStockDisponible(op.id_insumo, op.stock, slot.id) }}
                    </p>
                  </div>
                  <div class="is-flex is-align-items-center">
                    <b-button size="is-small" icon-left="minus"
                      :disabled="obtenerCantidadElegida(slot.id, op.id_insumo) <= 0"
                      @click="ajustarCantidadOpcion(slot.id, op, -1)"></b-button>
                    <span class="mx-3 has-text-weight-bold is-size-5" style="min-width: 20px; text-align: center;">
                      {{ obtenerCantidadElegida(slot.id, op.id_insumo) }}
                    </span>
                    <b-button type="is-primary" size="is-small" icon-left="plus"
                      :disabled="totalElegidoEnSlot(slot.id) >= comboNumMenus || (op.stock !== undefined && (op.stock - obtenerCantidadElegida(slot.id, op.id_insumo) <= 0))"
                      @click="ajustarCantidadOpcion(slot.id, op, 1)"></b-button>
                  </div>
                </div>
              </div>
            </div>
            <hr class="my-4">
          </div>
        </template>
      </section>
      <footer class="modal-card-foot is-justify-content-flex-end">
        <b-button @click="$emit('update:active', false)" size="is-medium">Cancelar</b-button>
        <b-button type="is-primary" icon-left="check" size="is-medium"
          :disabled="!plantillaCombo || cargandoPlantillaCombo" @click="confirmarComboAlPedido">Agregar al
          pedido</b-button>
      </footer>
    </div>
  </b-modal>
</template>

<script>
import HttpService from "../../Servicios/HttpService";

export default {
  name: "ModalArmarCombo",
  props: {
    active: {
      type: Boolean,
      default: false
    },
    insumo: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      cargandoPlantillaCombo: false,
      plantillaCombo: null,
      comboNumMenus: 1,
      eleccionesBulk: {},
    };
  },
  watch: {
    active(val) {
      if (val && this.insumo) {
        this.iniciarModalCombo();
      } else {
        this.plantillaCombo = null;
        this.comboNumMenus = 1;
        this.eleccionesBulk = {};
      }
    }
  },
  methods: {
    async iniciarModalCombo() {
      this.cargandoPlantillaCombo = true;
      this.plantillaCombo = null;
      try {
        const pl = await HttpService.obtenerConDatos({ id: this.insumo.idComboPlantilla }, 'obtener_plantilla_combo_id.php')
        if (!pl || !pl.slots || !pl.slots.length) {
          this.$toast({ message: 'Plantilla de combo vacía o inexistente', type: 'is-danger' })
          this.$emit('update:active', false);
          this.cargandoPlantillaCombo = false
          return
        }
        this.plantillaCombo = pl
        this.comboNumMenus = 1
        const bulk = {}
        pl.slots.forEach(s => {
          bulk[String(s.id)] = {}
        })
        this.eleccionesBulk = bulk
      } catch (e) {
        this.$toast({ message: 'No se pudo cargar la plantilla del combo', type: 'is-danger' })
        this.$emit('update:active', false);
      }
      this.cargandoPlantillaCombo = false
    },
    obtenerStockDisponible(idInsumo, stockBase, slotId) {
      if (stockBase === undefined) return 999;
      let consumidoEnEsteId = 0;
      const slotChoices = this.eleccionesBulk[String(slotId)] || {};
      Object.keys(slotChoices).forEach(id => {
        if (String(id) === String(idInsumo)) consumidoEnEsteId = slotChoices[id];
      });
      return stockBase - consumidoEnEsteId;
    },
    ajustarCantidadOpcion(slotId, opcion, delta) {
      const sid = String(slotId);
      const oid = String(opcion.id_insumo);
      const actual = this.eleccionesBulk[sid][oid] || 0;
      const nuevo = actual + delta;

      if (nuevo < 0) return;
      if (delta > 0) {
        // Validar stock
        if (opcion.stock !== undefined && actual >= opcion.stock) return;
        // Validar que no pase del total de menus
        if (this.totalElegidoEnSlot(slotId) >= this.comboNumMenus) return;
      }

      this.$set(this.eleccionesBulk[sid], oid, nuevo);
    },
    totalElegidoEnSlot(slotId) {
      const choices = this.eleccionesBulk[String(slotId)] || {};
      return Object.values(choices).reduce((sum, val) => sum + val, 0);
    },
    obtenerCantidadElegida(slotId, idInsumo) {
      const slot = this.eleccionesBulk[String(slotId)];
      return (slot && slot[String(idInsumo)]) || 0;
    },
    confirmarComboAlPedido() {
      const n = this.comboNumMenus
      if (!this.insumo || !this.plantillaCombo) return
      const slots = this.plantillaCombo.slots || []

      // 1. Validar que todos los slots estén completos
      for (const s of slots) {
        if (this.totalElegidoEnSlot(s.id) !== n) {
          this.$buefy.toast.open({
            message: `Por favor completa la selección de ${s.etiqueta} (${this.totalElegidoEnSlot(s.id)}/${n})`,
            type: 'is-warning'
          })
          return
        }
      }

      const listasPorSlot = {}
      slots.forEach(s => {
        const sid = String(s.id)
        const lista = []
        const choices = this.eleccionesBulk[sid] || {}
        Object.keys(choices).forEach(oid => {
          for (let i = 0; i < choices[oid]; i++) {
            lista.push(oid)
          }
        })
        listasPorSlot[sid] = lista
      })

      const menusTraducidos = []
      for (let i = 0; i < n; i++) {
        const row = {}
        slots.forEach(s => {
          row[String(s.id)] = listasPorSlot[String(s.id)][i]
        })
        menusTraducidos.push(row)
      }

      const conteos = {}
      const ordenComponentes = []

      menusTraducidos.forEach((row) => {
        slots.forEach(s => {
          const val = row[String(s.id)]
          const op = s.opciones.find(o => String(o.id_insumo) === String(val))
          const nombre = op ? op.nombre_insumo : ('#' + val)
          if (!conteos[nombre]) {
            conteos[nombre] = 0
            ordenComponentes.push(nombre)
          }
          conteos[nombre]++
        })
      })

      const menus = menusTraducidos.map((row) => ({ slots: { ...row } }))
      const detalleJson = { menus }
      const resumenCombo = ordenComponentes.map(nom => `${conteos[nom]} ${nom}`).join('\n')

      this.$emit('confirmar', {
        n,
        detalleJson,
        resumenCombo
      });
    }
  }
};
</script>
