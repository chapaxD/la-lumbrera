<template>
  <div class="box mb-5 despiece-container">
    <div class="is-flex is-align-items-center mb-4 pb-2 border-bottom">
      <span class="icon is-large has-text-primary mr-3">
        <b-icon icon="knife-military" size="is-large"></b-icon>
      </span>
      <div>
        <h3 class="title is-4 mb-1 has-text-weight-bold">Registrar Despiece de Parrilla</h3>
        <p class="subtitle is-6 has-text-grey mt-0 mb-0">
          Divide un ingreso en cortes específicos para control de stock.
        </p>
      </div>
    </div>

    <div class="notification is-info is-light py-3 mb-5 info-banner">
      <div class="is-flex is-align-items-center">
        <b-icon icon="information" size="is-medium" class="mr-3"></b-icon>
        <p class="mb-0 is-size-6">
          <span v-if="esAdmin">
            <router-link :to="{ path: '/registrar-insumo' }" class="has-text-weight-bold is-underlined">
              + Registrar nuevo insumo
            </router-link>
            — recordá configurar el <strong>tamaño de porción (g)</strong> en el catálogo de insumos.
          </span>
          <span v-else-if="rolUsuario === 'parrillero'">
            Si un corte no aparece, pedí al administrador que lo dé de alta en <strong>Insumos</strong> y cargue stock
            en <strong>Reabastecer</strong>.
          </span>
          <span v-else>
            <router-link :to="{ path: '/insumos' }" class="has-text-weight-bold is-underlined">Ver catálogo de
              insumos</router-link>
          </span>
        </p>
      </div>
    </div>

    <form @submit.prevent="registrarDespiece">
      <!-- General Info Card -->
      <div class="card mb-5 general-info-card shadow-sm">
        <div class="card-content pb-3">
          <h4 class="title is-5 mb-4 has-text-grey-dark is-flex is-align-items-center">
            <b-icon icon="clipboard-list-outline" class="mr-2"></b-icon> Información General
          </h4>
          <div class="columns is-multiline">
            <div class="column is-3-desktop is-6-tablet">
              <b-field label="Fecha">
                <b-input type="datetime-local" v-model="form.fecha" required icon="calendar"></b-input>
              </b-field>
            </div>
            <div class="column is-3-desktop is-6-tablet">
              <b-field label="Materia Prima / Lote">
                <b-input type="text" v-model="form.materia_prima" required placeholder="Ej: Media res..."
                  icon="label"></b-input>
              </b-field>
            </div>
            <div class="column is-3-desktop is-6-tablet">
              <b-field label="Total Recibido (kg)">
                <b-input type="number" step="0.001" min="0.001" v-model.number="form.total_kg_recibido" required
                  icon="scale"></b-input>
              </b-field>
            </div>
            <div class="column is-3-desktop is-6-tablet">
              <b-field label="Usuario">
                <b-input type="text" v-model="form.usuario" required icon="account"></b-input>
              </b-field>
            </div>
          </div>

          <!-- Cuadre Total -->
          <div
            class="notification py-2 px-4 mt-2 mb-0 is-flex is-align-items-center is-justify-content-space-between cuadre-total-kg"
            :class="cuadreTotalOk ? 'bg-success-light border-success' : 'bg-warning-light border-warning'">
            <div>
              <b-icon :icon="cuadreTotalOk ? 'check-circle' : 'alert'"
                :type="cuadreTotalOk ? 'is-success' : 'is-warning'" class="mr-2"></b-icon>
              <strong>Cuadre Total:</strong>
              Suma líneas = <strong>{{ sumaKgLineas.toFixed(3) }}</strong> kg
              <span class="mx-2 has-text-grey-light">|</span>
              Total declarado = <strong>{{ numTotalKg.toFixed(3) }}</strong> kg
            </div>
            <div v-if="!cuadreTotalOk"
              class="has-text-danger has-text-weight-bold is-size-7 ml-3 bg-white px-2 py-1 rounded">
              Diferencia: {{ Math.abs(sumaKgLineas - numTotalKg).toFixed(3) }} kg
            </div>
          </div>
        </div>
      </div>

      <div class="is-flex is-justify-content-space-between is-align-items-flex-end mb-4 px-1">
        <h4 class="title is-4 mb-0 has-text-grey-dark is-flex is-align-items-center">
          <b-icon icon="format-list-numbered" class="mr-2"></b-icon> Cortes Resultantes
        </h4>
        <b-button type="is-info is-light" icon-left="plus" @click="agregarLinea"
          class="add-line-btn has-text-weight-bold">
          Agregar Corte
        </b-button>
      </div>

      <transition-group name="list" tag="div">
        <div v-for="(ln, idx) in form.lineas" :key="'ln-' + idx"
          class="card mb-4 linea-despiece shadow-sm hover-elevate">
          <header class="card-header"
            :class="!lineaCuadra(idx) ? 'has-background-danger-light' : 'has-background-white-ter'">
            <p class="card-header-title is-size-6 py-2">
              <span class="tag is-rounded is-dark mr-2">{{ idx + 1 }}</span>
              <span v-if="ln.materia_prima" class="has-text-primary">{{ ln.materia_prima }}</span>
              <span v-else class="has-text-grey-light is-italic">Seleccione un corte...</span>
              <span v-if="ln.gramos_porcion > 0" class="tag is-rounded is-warning is-light ml-2 is-size-7">
                {{ ln.gramos_porcion }}g / porción
              </span>
            </p>
            <a v-if="form.lineas.length > 1" href="#" class="card-header-icon has-text-danger"
              @click.prevent="quitarLinea(idx)" title="Eliminar corte">
              <b-icon icon="delete"></b-icon>
            </a>
          </header>

          <div class="card-content py-4">
            <div class="columns is-multiline is-valign-bottom">

              <!-- Catálogo (fila propia, ancho completo) -->
              <div class="column is-12 pb-1">
                <b-field label="Catálogo de Insumos"
                  :type="!ln.id_insumo && ln.materia_prima ? 'is-danger' : (!ln.materia_prima ? '' : 'is-success')"
                  :message="!ln.id_insumo && ln.materia_prima ? 'Debe seleccionar del catálogo' : ''">
                  <b-autocomplete v-model="ln.materia_prima" :data="filtrarInsumos(ln.materia_prima)" field="nombre"
                    placeholder="Buscar corte o insumo..." icon="magnify" clearable open-on-focus
                    :loading="cargandoInsumos" @select="(opt) => onSelectInsumo(ln, opt)"
                    @typing="() => onTypingInsumo(ln)">
                    <template slot-scope="props">
                      <div class="media is-align-items-center">
                        <div class="media-left">
                          <b-icon icon="silverware" class="has-text-grey-light"></b-icon>
                        </div>
                        <div class="media-content">
                          <div class="has-text-weight-bold">{{ props.option.nombre }}</div>
                          <div class="is-size-7 has-text-grey">
                            <span v-if="props.option.codigo">Cod: {{ props.option.codigo }}</span>
                            <span v-if="props.option.categoria" class="mx-1">•</span>
                            <span v-if="props.option.categoria">{{ props.option.categoria }}</span>
                            <span v-if="props.option.tipoCorte > 0" class="ml-2 has-text-warning-dark">
                              {{ props.option.tipoCorte }}g/porción
                            </span>
                          </div>
                        </div>
                        <div class="media-right">
                          <b-tag type="is-light" :class="props.option.stock <= 0 ? 'is-danger' : 'is-info'">
                            Stock: {{ props.option.stock }}
                          </b-tag>
                        </div>
                      </div>
                    </template>
                    <template slot="empty">Insumo no encontrado en catálogo.</template>
                  </b-autocomplete>
                </b-field>
              </div>

              <!-- ── Fila de numericos — todos en la misma línea — suman 12 en desktop ── -->

              <!-- Kg asignados (3 cols) -->
              <div class="column is-6-mobile is-3-tablet is-3-desktop pb-2">
                <b-field label="Kg Asignados">
                  <b-input type="number" step="0.001" min="0.001" v-model.number="ln.kg_asignado" required
                    @input.native="recalcularPorciones(idx)"></b-input>
                </b-field>
              </div>

              <!-- Gramos x porción (2 cols) -->
              <div class="column is-6-mobile is-2-tablet is-2-desktop pb-2">
                <b-field label="g / porción"
                  :type="ln.gramos_porcion > 0 ? 'is-success' : 'is-warning'">
                  <b-input type="number" min="1" step="1" v-model.number="ln.gramos_porcion"
                    placeholder="Ej: 350"
                    @input.native="recalcularPorciones(idx)"></b-input>
                </b-field>
              </div>

              <!-- Porciones (2 cols) -->
              <div class="column is-6-mobile is-2-tablet is-2-desktop pb-2">
                <b-field label="Porciones">
                  <b-input type="number" min="0" v-model.number="ln.porciones"
                    custom-class="has-text-weight-bold"></b-input>
                </b-field>
              </div>

              <!-- Desperdicio (2 cols) -->
              <div class="column is-6-mobile is-2-tablet is-2-desktop pb-2">
                <b-field label="Desperdicio (g)" custom-class="has-text-warning-dark">
                  <b-input type="number" min="0" v-model.number="ln.desperdicio_g"></b-input>
                </b-field>
              </div>

              <!-- Sobras calculadas (3 cols) — read-only, misma altura que un input -->
              <div class="column is-12-mobile is-3-tablet is-3-desktop pb-2">
                <b-field label="Sobras (g)">
                  <div class="control">
                    <div class="sobras-display input"
                      :class="sobrasLinea(idx) < 0 ? 'sobras-negativo' : (sobrasLinea(idx) === 0 ? 'sobras-cero' : 'sobras-positivo')">
                      <b-icon
                        :icon="sobrasLinea(idx) < 0 ? 'alert-circle' : (sobrasLinea(idx) === 0 ? 'check-circle' : 'information')"
                        :type="sobrasLinea(idx) < 0 ? 'is-danger' : (sobrasLinea(idx) === 0 ? 'is-success' : 'is-info')"
                        size="is-small" class="mr-1"></b-icon>
                      <strong>{{ sobrasLinea(idx) }} g</strong>
                      <span v-if="sobrasLinea(idx) < 0" class="is-size-7 ml-1 has-text-danger">¡excede!</span>
                    </div>
                  </div>
                </b-field>
              </div>

            </div>

            <!-- 🥩 Banner de sobras inteligentes — aparece siempre que haya sobras > 0 -->
            <transition name="slide-down">
              <div v-if="sobrasLinea(idx) > 0"
                class="sobras-banner mt-3 p-3 rounded">
                <div class="is-flex is-align-items-flex-start is-justify-content-space-between">
                  <div class="is-flex is-align-items-flex-start" style="flex:1">
                    <b-icon icon="food-variant" type="is-success" class="mr-3 mt-1" style="flex-shrink:0"></b-icon>
                    <div>
                      <p class="is-size-6 mb-1 has-text-weight-bold has-text-success-dark">
                        Sobran <strong>{{ sobrasLinea(idx) }}g</strong> de este corte
                      </p>

                      <!-- Cuántas porciones completas salen de las sobras -->
                      <template v-if="ln.gramos_porcion > 0">
                        <p class="is-size-7 mb-1 has-text-grey-dark">
                          Con {{ sobrasLinea(idx) }}g podés sacar:
                          <strong class="has-text-primary">{{ Math.floor(sobrasLinea(idx) / ln.gramos_porcion) }} porción(es) más de {{ ln.gramos_porcion }}g</strong>
                          <template v-if="sobrasLinea(idx) % ln.gramos_porcion > 0">
                            y quedarían <strong>{{ sobrasLinea(idx) % ln.gramos_porcion }}g</strong> adicionales.
                          </template>
                        </p>
                      </template>

                      <!-- Alternativas de destino -->
                      <p class="is-size-7 mb-0 has-text-grey">
                        💡 <em>Estas sobras podés aprovecharlas en otro corte, para moler
                        (hamburguesas, milanesas, relleno) o registrarlas como merma si no se aprovechan.</em>
                      </p>
                    </div>
                  </div>
                  <b-button size="is-small" type="is-success is-light" icon-left="plus-circle" class="ml-3 sobras-btn"
                    style="flex-shrink:0"
                    @click.prevent="agregarLineaDesdeSobras(idx)">
                    Usar en otro corte
                  </b-button>
                </div>
              </div>
            </transition>

            <!-- Cuadre gramos por línea -->
            <div
              class="cuadre-gramos-linea mt-3 is-flex is-align-items-center is-justify-content-space-between p-3 rounded"
              :class="lineaCuadra(idx) ? 'has-background-success-light border-left-success' : 'has-background-danger-light border-left-danger'">
              <div class="is-flex is-align-items-center">
                <b-icon :icon="lineaCuadra(idx) ? 'check-circle' : 'alert-circle'"
                  :type="lineaCuadra(idx) ? 'is-success' : 'is-danger'" class="mr-3 is-hidden-mobile"></b-icon>
                <div class="is-size-6">
                  <span class="has-text-grey">Asignado:</span>
                  <strong class="is-size-5 ml-1">{{ gramosEsperadosLinea(idx) }}g</strong>
                  <span class="mx-2 has-text-grey-light is-hidden-mobile">=</span><br class="is-hidden-tablet">
                  <span class="has-text-grey is-size-7">
                    {{ entero(ln.porciones) }} × {{ entero(ln.gramos_porcion) }}g
                    + {{ entero(ln.desperdicio_g) }}g desp.
                    + {{ Math.max(0, sobrasLinea(idx)) }}g sobras
                    = <strong>{{ gramosLinea(idx) }}g</strong>
                  </span>
                </div>
              </div>
              <div v-if="!lineaCuadra(idx)"
                class="has-text-danger has-text-weight-bold is-size-7 bg-white px-2 py-1 rounded shadow-sm">
                Dif: {{ Math.abs(gramosEsperadosLinea(idx) - gramosLinea(idx)) }}g
              </div>
            </div>

          </div>
        </div>
      </transition-group>

      <div
        class="mt-5 is-flex is-align-items-center is-justify-content-flex-end form-actions p-4 box bg-white-ter has-background-white-ter shadow-sm">
        <span v-if="mensaje" class="mr-4 has-text-weight-bold is-size-6"
          :class="{ 'has-text-success': exito, 'has-text-danger': !exito }">
          <b-icon :icon="exito ? 'check' : 'close'" size="is-small" class="mr-1"></b-icon> {{ mensaje }}
        </span>
        <div v-if="!puedeEnviar && numTotalKg > 0"
          class="mr-4 has-text-warning-dark is-size-6 is-flex is-align-items-center bg-warning-light px-3 py-1 rounded">
          <b-icon icon="alert" size="is-small" class="mr-2"></b-icon> Verificá los cuadros
        </div>
        <b-button type="is-primary" native-type="submit" size="is-large" icon-left="content-save"
          :disabled="enviando || !puedeEnviar" :loading="enviando" class="submit-btn has-text-weight-bold px-6 shadow">
          Registrar Despiece
        </b-button>
      </div>
    </form>
  </div>
</template>

<script>
import HttpService from '../../Servicios/HttpService';

function fechaLocalParaInput() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function lineaVacia() {
  return {
    id_insumo: null,
    materia_prima: '',
    kg_asignado: '',
    gramos_porcion: 0,   // cargado desde tipoCorte del insumo seleccionado
    porciones: 0,        // calculado automáticamente, ajustable
    desperdicio_g: 0,    // ingresado manualmente
    // sobras_g se calcula reactivamente — NO se ingresa
  };
}

export default {
  name: 'RegistrarDespieceParrilla',
  data() {
    return {
      form: {
        fecha: '',
        materia_prima: '',
        usuario: '',
        total_kg_recibido: '',
        lineas: [lineaVacia(), lineaVacia()]
      },
      insumosPlatillos: [],
      cargandoInsumos: false,
      enviando: false,
      mensaje: '',
      exito: false
    };
  },
  computed: {
    rolUsuario() {
      return typeof localStorage !== 'undefined' ? localStorage.getItem('rol') || '' : '';
    },
    esAdmin() {
      return this.rolUsuario === 'admin';
    },
    numTotalKg() {
      const t = parseFloat(this.form.total_kg_recibido);
      return Number.isFinite(t) ? t : 0;
    },
    sumaKgLineas() {
      return this.form.lineas.reduce((acc, ln) => {
        const k = parseFloat(ln.kg_asignado);
        return acc + (Number.isFinite(k) && k > 0 ? k : 0);
      }, 0);
    },
    cuadreTotalOk() {
      return Math.abs(this.sumaKgLineas - this.numTotalKg) <= 0.02;
    },
    puedeEnviar() {
      if (!this.cuadreTotalOk || this.numTotalKg <= 0) return false;
      return this.form.lineas.every((ln, i) =>
        this.lineaCuadra(i) && ln.id_insumo && ln.gramos_porcion > 0
      );
    }
  },
  mounted() {
    this.form.fecha = fechaLocalParaInput();
    const nombre = typeof localStorage !== 'undefined' ? localStorage.getItem('nombreUsuario') : '';
    if (nombre) this.form.usuario = nombre;
    this.cargarInsumos();
  },
  methods: {
    async cargarInsumos() {
      this.cargandoInsumos = true;
      try {
        const filtros = { tipo: 'PLATILLO', categoria: '', nombre: '' };
        const lista = await HttpService.obtenerConDatos(filtros, 'obtener_insumos.php');
        this.insumosPlatillos = Array.isArray(lista) ? lista : [];
      } catch (e) {
        this.insumosPlatillos = [];
      } finally {
        this.cargandoInsumos = false;
      }
    },
    filtrarInsumos(texto) {
      const t = (texto || '').toLowerCase().trim();
      if (!t) return this.insumosPlatillos.slice(0, 40);
      return this.insumosPlatillos
        .filter((i) => {
          const nom = (i.nombre && String(i.nombre).toLowerCase()) || '';
          const cod = (i.codigo && String(i.codigo).toLowerCase()) || '';
          return nom.includes(t) || cod.includes(t);
        })
        .slice(0, 60);
    },
    onSelectInsumo(ln, opt) {
      if (!opt) return;
      ln.id_insumo = opt.id;
      ln.materia_prima = opt.nombre || '';
      // Precarga el tamaño de porción desde el catálogo (tipoCorte en gramos)
      const gc = parseInt(opt.tipoCorte, 10);
      ln.gramos_porcion = (Number.isFinite(gc) && gc > 0) ? gc : 0;
      // Recalcular porciones sugeridas con el nuevo tamaño
      this.recalcularPorciones(this.form.lineas.indexOf(ln));
    },
    onTypingInsumo(ln) {
      ln.id_insumo = null;
      ln.gramos_porcion = 0;
      ln.porciones = 0;
    },
    /**
     * Recalcula automáticamente las porciones sugeridas
     * al cambiar kg_asignado o gramos_porcion.
     */
    recalcularPorciones(idx) {
      const ln = this.form.lineas[idx];
      const totalG = this.gramosEsperadosLinea(idx);
      const gp = this.entero(ln.gramos_porcion);
      if (totalG > 0 && gp > 0) {
        // Sugerencia: máximas porciones posibles descontando desperdicio
        const dispBruto = totalG - this.entero(ln.desperdicio_g);
        ln.porciones = Math.max(0, Math.floor(dispBruto / gp));
      }
    },
    /**
     * Gramos que sobran en esa línea (pueden ser negativos si el usuario puso
     * demasiadas porciones — se detecta en la validación).
     * sobras = kg_asignado*1000 - porciones*gramos_porcion - desperdicio_g
     */
    sobrasLinea(idx) {
      const ln = this.form.lineas[idx];
      const totalG = this.gramosEsperadosLinea(idx);
      if (totalG <= 0) return 0;
      const porciones = this.entero(ln.porciones);
      const gp = this.entero(ln.gramos_porcion);
      const desp = this.entero(ln.desperdicio_g);
      return totalG - porciones * gp - desp;
    },
    entero(n) {
      const x = parseInt(n, 10);
      return Number.isFinite(x) ? x : 0;
    },
    kgLineaStr(ln) {
      const k = parseFloat(ln.kg_asignado);
      return Number.isFinite(k) ? k.toFixed(3) : '0';
    },
    gramosEsperadosLinea(idx) {
      const ln = this.form.lineas[idx];
      const k = parseFloat(ln.kg_asignado);
      if (!Number.isFinite(k) || k <= 0) return 0;
      return Math.round(k * 1000);
    },
    /**
     * Gramos "usados" en la línea = porciones*gp + desperdicio + max(0,sobras)
     * Se usa para la barra de cuadre.
     */
    gramosLinea(idx) {
      const ln = this.form.lineas[idx];
      const porciones = this.entero(ln.porciones);
      const gp = this.entero(ln.gramos_porcion);
      const desp = this.entero(ln.desperdicio_g);
      const sobras = Math.max(0, this.sobrasLinea(idx));
      return porciones * gp + desp + sobras;
    },
    lineaCuadra(idx) {
      const totalG = this.gramosEsperadosLinea(idx);
      if (totalG <= 0) return false;
      // Las sobras son la diferencia — si son negativas no cuadra
      return this.sobrasLinea(idx) >= 0 && Math.abs(this.gramosLinea(idx) - totalG) <= 8;
    },
    agregarLinea() {
      this.form.lineas.push(lineaVacia());
    },
    quitarLinea(i) {
      if (this.form.lineas.length > 1) this.form.lineas.splice(i, 1);
    },
    agregarLineaDesdeSobras(idx) {
      const sobras = this.sobrasLinea(idx);
      if (sobras <= 0) return;
      const ln = this.form.lineas[idx];
      const nuevaLinea = lineaVacia();
      nuevaLinea.kg_asignado = parseFloat((sobras / 1000).toFixed(3));
      // Heredar el mismo tamaño de porción del corte actual como sugerencia
      nuevaLinea.gramos_porcion = ln.gramos_porcion || 0;
      
      // Reducir kg de la línea original
      const kgOriginal = parseFloat(ln.kg_asignado) || 0;
      ln.kg_asignado = (kgOriginal - (sobras / 1000)).toFixed(3);
      this.recalcularPorciones(idx);

      this.form.lineas.splice(idx + 1, 0, nuevaLinea);
      this.$nextTick(() => {
        const inputs = this.$el.querySelectorAll('.linea-despiece input[type="text"]');
        if (inputs && inputs[idx + 1]) inputs[idx + 1].focus();
      });
    },
    payloadListo() {
      const lineas = this.form.lineas.map((ln) => {
        const sobras = Math.max(0, this.sobrasLinea(this.form.lineas.indexOf(ln)));
        return {
          id_insumo: ln.id_insumo,
          materia_prima: String(ln.materia_prima || '').trim(),
          kg_asignado: parseFloat(ln.kg_asignado),
          gramos_porcion: this.entero(ln.gramos_porcion),
          porciones: this.entero(ln.porciones),
          desperdicio_g: this.entero(ln.desperdicio_g),
          sobras_g: sobras,
          // compatibilidad backward (el backend los ignorará si gramos_porcion > 0)
          porciones_250: 0,
          porciones_350: 0,
        };
      });
      const idUsuario = typeof localStorage !== 'undefined' ? parseInt(localStorage.getItem('idUsuario'), 10) : 0;
      return {
        fecha: this.form.fecha,
        usuario: String(this.form.usuario || '').trim(),
        materia_prima: String(this.form.materia_prima || '').trim(),
        total_kg_recibido: this.numTotalKg,
        id_usuario: Number.isFinite(idUsuario) && idUsuario > 0 ? idUsuario : undefined,
        lineas
      };
    },
    async registrarDespiece() {
      this.enviando = true;
      this.mensaje = '';
      this.exito = false;
      try {
        const res = await fetch('/api/registrar_despiece_parrilla.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.payloadListo())
        });
        const data = await res.json();
        if (data.ok) {
          this.mensaje = '¡Registro exitoso!';
          this.exito = true;
          const u = this.form.usuario;
          this.form.materia_prima = '';
          this.form.total_kg_recibido = '';
          this.form.lineas = [lineaVacia(), lineaVacia()];
          this.form.fecha = fechaLocalParaInput();
          this.form.usuario = u || (typeof localStorage !== 'undefined' ? localStorage.getItem('nombreUsuario') || '' : '');
          if (this.$buefy && this.$buefy.toast) {
            this.$buefy.toast.open({
              message: '¡El despiece se registró correctamente! El stock se actualizó.',
              type: 'is-success',
              duration: 5000
            });
          }
        } else {
          this.mensaje = data.error || 'Error al registrar';
          if (this.$buefy && this.$buefy.toast) {
            this.$buefy.toast.open({ message: this.mensaje, type: 'is-danger', duration: 5000 });
          }
        }
      } catch (e) {
        this.mensaje = 'Error de red';
        if (this.$buefy && this.$buefy.toast) {
          this.$buefy.toast.open({ message: 'Error de red al intentar registrar.', type: 'is-danger', duration: 4000 });
        }
      } finally {
        this.enviando = false;
      }
    }
  }
};
</script>

<style scoped>
.despiece-container {
  max-width: 100%;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
  padding: 2rem;
  background-color: #fff;
}

@media (max-width: 768px) {
  .despiece-container {
    padding: 1rem;
  }
}

.border-bottom {
  border-bottom: 2px solid #f5f5f5;
}

.shadow-sm {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.shadow {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

.rounded {
  border-radius: 8px;
}

.info-banner {
  border-left: 4px solid #3298dc;
  border-radius: 8px;
}

.general-info-card {
  border-radius: 10px;
  border: 1px solid #eee;
  background-color: #fafafa;
}

.hover-elevate {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border-radius: 10px;
  border: 1px solid #eeeeee;
  overflow: hidden;
}

.hover-elevate:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08) !important;
}

.bg-success-light {
  background-color: #effaf3 !important;
}

.bg-warning-light {
  background-color: #fffcf5 !important;
}

.bg-white {
  background-color: #ffffff !important;
}

.border-success {
  border: 1px solid #48c774;
}

.border-warning {
  border: 1px solid #ffdd57;
  border-left-color: #ffdd57 !important;
  border-left-width: 4px !important;
}

.border-left-success {
  border-left: 4px solid #48c774 !important;
}

.border-left-danger {
  border-left: 4px solid #f14668 !important;
}

.is-valign-bottom {
  align-items: flex-end;
}

.add-line-btn {
  border-radius: 20px;
}

.submit-btn {
  border-radius: 24px;
  transition: all 0.3s ease;
}

.submit-btn:not([disabled]):hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 209, 178, 0.3) !important;
}

/* === Chip de sobras (read-only) === */
.sobras-chip {
  display: flex;
  align-items: center;
  padding: 0.55rem 0.85rem;
  border-radius: 8px;
  border: 2px solid #eee;
  min-height: 2.5rem;
  font-size: 1rem;
}
.sobras-chip--ok {
  background: #effaf3;
  border-color: #48c774;
}
.sobras-chip--positivo {
  background: #eff8ff;
  border-color: #3298dc;
}
.sobras-chip--negativo {
  background: #fff0f0;
  border-color: #f14668;
}

/* === Display de Sobras (read-only, misma altura que input Bulma) === */
.sobras-display {
  display: flex;
  align-items: center;
  cursor: default;
  pointer-events: none;
  font-size: 1rem;
  font-weight: 600;
}
.sobras-cero {
  background: #effaf3 !important;
  border-color: #48c774 !important;
  color: #257942;
}
.sobras-positivo {
  background: #eff8ff !important;
  border-color: #3298dc !important;
  color: #1d72aa;
}
.sobras-negativo {
  background: #fff0f0 !important;
  border-color: #f14668 !important;
  color: #cc0f35;
}

/* === Banner de Sobras === */
.sobras-banner {
  background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
  border: 1px solid #a3d9a5;
  border-left: 4px solid #23a832;
}

.sobras-btn {
  border-radius: 20px;
  white-space: nowrap;
  flex-shrink: 0;
  font-weight: 600;
}

/* Animations for adding/removing lines */
.list-enter-active,
.list-leave-active {
  transition: all 0.4s;
}

.list-enter,
.list-leave-to {
  opacity: 0;
  transform: translateY(15px);
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.35s ease;
  overflow: hidden;
}

.slide-down-enter,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-8px);
}

.slide-down-enter-to,
.slide-down-leave {
  opacity: 1;
  max-height: 300px;
  transform: translateY(0);
}
</style>
