<template>
  <div class="box mb-5">
    <h3 class="title is-4">Registrar despiece de parrilla</h3>
    <p class="subtitle is-6 has-text-grey">
      Un mismo ingreso (por ejemplo 7 kg) se reparte en varias líneas. En cada línea elegí el <strong>insumo del
        catálogo</strong>.
      Al guardar, el sistema <strong>suma al stock en unidades</strong>: cada porción de 250 g cuenta 1. Las sobras y el
      desperdicio siguen sirviendo solo para el <strong>cuadre en gramos</strong> con los kg de la línea.
    </p>

    <div class="notification is-info is-light py-3 mb-4">
      <p class="mb-2">
        <router-link v-if="esAdmin" :to="{ path: '/registrar-insumo' }" class="has-text-weight-semibold">
          + Registrar nuevo insumo
        </router-link>
        <span v-else-if="rolUsuario === 'parrillero'">
          Si un corte no aparece en la búsqueda, pedí al administrador que lo dé de alta en <strong>Insumos</strong> y
          cargue stock en <strong>Reabastecer</strong>.
        </span>
        <span v-else>
          <router-link :to="{ path: '/insumos' }">Ver catálogo de insumos</router-link>
        </span>
      </p>
    </div>

    <form @submit.prevent="registrarDespiece">
      <div class="columns is-multiline">
        <div class="column is-4">
          <label class="label">Fecha</label>
          <input class="input" type="datetime-local" v-model="form.fecha" required />
        </div>
        <div class="column is-4">
          <label class="label">Total recibido (kg)</label>
          <input class="input" type="number" step="0.001" min="0.001" v-model.number="form.total_kg_recibido"
            required />
        </div>
        <div class="column is-4">
          <label class="label">Usuario</label>
          <input class="input" type="text" v-model="form.usuario" required />
        </div>
      </div>

      <div class="notification py-3 px-4 mb-4 cuadre-total-kg"
        :class="cuadreTotalOk ? 'is-success is-light' : 'is-warning is-light'">
        <strong>Cuadre de kg:</strong>
        suma de kg por línea = <strong>{{ sumaKgLineas.toFixed(3) }}</strong> kg ·
        total declarado = <strong>{{ numTotalKg.toFixed(3) }}</strong> kg
        <span v-if="!cuadreTotalOk" class="has-text-danger"> — deben coincidir (tolerancia 20 g).</span>
        <span v-else> — OK.</span>
      </div>

      <h4 class="title is-5">Líneas de despiece</h4>

      <div v-for="(ln, idx) in form.lineas" :key="'ln-' + idx" class="box has-background-light linea-despiece mb-3">
        <div class="is-flex is-justify-content-space-between is-align-items-center mb-2">
          <span class="has-text-weight-semibold">Línea {{ idx + 1 }}</span>
          <button v-if="form.lineas.length > 1" type="button" class="button is-small is-danger is-light"
            @click="quitarLinea(idx)">
            Quitar línea
          </button>
        </div>

        <!-- Una sola fila: insumo/corte + kg asignados -->
        <div class="columns is-multiline is-vcentered mb-0">
          <div class="column is-12-tablet is-8-desktop pb-2">
            <b-field label="Insumo / corte" :type="!ln.id_insumo && ln.materia_prima ? 'is-danger' : ''"
              :message="!ln.id_insumo && ln.materia_prima ? 'Elegí una opción de la lista' : ''">
              <b-autocomplete v-model="ln.materia_prima" :data="filtrarInsumos(ln.materia_prima)" field="nombre"
                placeholder="Buscar en catálogo..." icon="magnify" clearable open-on-focus :loading="cargandoInsumos"
                @select="(opt) => onSelectInsumo(ln, opt)" @typing="() => onTypingInsumo(ln)">
                <template slot-scope="props">
                  <div class="media">
                    <div class="media-content">
                      <span class="has-text-weight-semibold">{{ props.option.nombre }}</span>
                      <br>
                      <small class="has-text-grey">
                        {{ props.option.codigo || '—' }} · {{ props.option.categoria || '' }}
                        · Stock: {{ props.option.stock }}
                      </small>
                    </div>
                  </div>
                </template>
                <template slot="empty">No hay resultados — {{ esAdmin ? 'podés registrar un insumo nuevo arriba.' :
                  'avisá al administrador.' }}</template>
              </b-autocomplete>
            </b-field>
          </div>
          <div class="column is-12-tablet is-4-desktop pb-2">
            <label class="label">Kg asignados</label>
            <input class="input input-kg-linea" type="number" step="0.001" min="0.001" v-model.number="ln.kg_asignado"
              required />
          </div>
        </div>

        <!-- Segunda fila: porciones y gramos -->
        <div class="columns is-multiline is-mobile mb-0">
          <div class="column is-6-mobile is-3-tablet">
            <label class="label label-compacto">Porc. 250 g</label>
            <input class="input" type="number" min="0" v-model.number="ln.porciones_250" />
          </div>
          <div class="column is-6-mobile is-3-tablet">
            <label class="label label-compacto">Porc. 350 g</label>
            <input class="input" type="number" min="0" v-model.number="ln.porciones_350" />
          </div>
          <div class="column is-6-mobile is-3-tablet">
            <label class="label label-compacto">Desperdicio (g)</label>
            <input class="input" type="number" min="0" v-model.number="ln.desperdicio_g" />
          </div>
          <div class="column is-6-mobile is-3-tablet">
            <label class="label label-compacto">Sobras (g)</label>
            <input class="input" type="number" min="0" v-model.number="ln.sobras_g" />
          </div>
        </div>

        <p class="cuadre-gramos-linea mt-3" :class="lineaCuadra(idx) ? 'has-text-success' : 'has-text-danger'">
          Gramos porciones (250×{{ entero(ln.porciones_250) }} + 350×{{ entero(ln.porciones_350) }}) + desperdicio +
          sobras
          = <strong>{{ gramosLinea(idx) }} g</strong> · deben ser
          <strong>{{ gramosEsperadosLinea(idx) }} g</strong>
          ({{ kgLineaStr(ln) }} kg)
          <span v-if="!lineaCuadra(idx)"> — ajustá cantidades o desperdicio/sobras.</span>
        </p>
      </div>

      <button type="button" class="button is-light mb-4" @click="agregarLinea">+ Agregar línea</button>

      <div>
        <button class="button is-primary" type="submit" :disabled="enviando || !puedeEnviar">
          Registrar
        </button>
        <span v-if="mensaje" :class="{ 'has-text-success': exito, 'has-text-danger': !exito }" class="ml-3">{{ mensaje
          }}</span>
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
    porciones_250: 0,
    porciones_350: 0,
    desperdicio_g: 0,
    sobras_g: 0
  };
}

export default {
  name: 'RegistrarDespieceParrilla',
  data() {
    return {
      form: {
        fecha: '',
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
      return this.form.lineas.every((ln, i) => this.lineaCuadra(i) && ln.id_insumo);
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
    },
    onTypingInsumo(ln) {
      ln.id_insumo = null;
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
    gramosLinea(idx) {
      const ln = this.form.lineas[idx];
      const p250 = this.entero(ln.porciones_250);
      const p350 = this.entero(ln.porciones_350);
      const d = this.entero(ln.desperdicio_g);
      const s = this.entero(ln.sobras_g);
      return p250 * 250 + p350 * 350 + d + s;
    },
    lineaCuadra(idx) {
      const esp = this.gramosEsperadosLinea(idx);
      if (esp <= 0) return false;
      return Math.abs(this.gramosLinea(idx) - esp) <= 8;
    },
    agregarLinea() {
      this.form.lineas.push(lineaVacia());
    },
    quitarLinea(i) {
      if (this.form.lineas.length > 1) this.form.lineas.splice(i, 1);
    },
    payloadListo() {
      const lineas = this.form.lineas.map((ln) => ({
        id_insumo: ln.id_insumo,
        materia_prima: String(ln.materia_prima || '').trim(),
        kg_asignado: parseFloat(ln.kg_asignado),
        porciones_250: this.entero(ln.porciones_250),
        porciones_350: this.entero(ln.porciones_350),
        desperdicio_g: this.entero(ln.desperdicio_g),
        sobras_g: this.entero(ln.sobras_g)
      }));
      const idUsuario = typeof localStorage !== 'undefined' ? parseInt(localStorage.getItem('idUsuario'), 10) : 0;
      return {
        fecha: this.form.fecha,
        usuario: String(this.form.usuario || '').trim(),
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
          this.form.total_kg_recibido = '';
          this.form.lineas = [lineaVacia(), lineaVacia()];
          this.form.fecha = fechaLocalParaInput();
          this.form.usuario = u || (typeof localStorage !== 'undefined' ? localStorage.getItem('nombreUsuario') || '' : '');
        } else {
          this.mensaje = data.error || 'Error al registrar';
        }
      } catch (e) {
        this.mensaje = 'Error de red';
      } finally {
        this.enviando = false;
      }
    }
  }
};
</script>

<style scoped>
.box {
  max-width: 100%;
}

.linea-despiece {
  padding: 0.85rem 1rem;
}

.label-compacto {
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.input-kg-linea {
  font-size: 1.15rem;
  font-weight: 600;
}

/* Texto de cuadre en gramos: bien legible */
.cuadre-gramos-linea {
  font-size: 1.25rem;
  line-height: 1.5;
  font-weight: 500;
  padding: 0.65rem 0.85rem;
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.03);
}

.cuadre-gramos-linea.has-text-success {
  background: rgba(72, 199, 116, 0.12);
}

.cuadre-gramos-linea.has-text-danger {
  background: rgba(241, 70, 104, 0.1);
}

.cuadre-gramos-linea strong {
  font-size: 1.3rem;
  font-weight: 700;
}

.cuadre-total-kg {
  font-size: 1.1rem;
  line-height: 1.45;
}
</style>
