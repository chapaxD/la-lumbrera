<template>
    <div class="columns is-variable is-6 is-multiline">

        <!-- ── Columna izquierda: Preview vivo ── -->
        <div class="column is-4-desktop is-12-tablet">
            <div class="insumo-preview-sticky">
                <p class="heading has-text-grey mb-2">Vista previa</p>
                <div class="card insumo-preview-card">
                    <div class="card-content">
                        <!-- Tipo badge -->
                        <div class="mb-3">
                            <b-tag v-if="insumo.tipo === 'PLATILLO'" type="is-warning" class="mr-1">
                                <b-icon icon="food" size="is-small"></b-icon>&nbsp;Platillo
                            </b-tag>
                            <b-tag v-else-if="insumo.tipo === 'BEBIDA'" type="is-info" class="mr-1">
                                <b-icon icon="cup" size="is-small"></b-icon>&nbsp;Bebida
                            </b-tag>
                            <b-tag v-else type="is-light">Sin tipo</b-tag>
                            <b-tag v-if="(insumo.tipoVenta || 'NORMAL') === 'RECETA'" type="is-dark" class="ml-1 is-size-7">Receta</b-tag>
                            <b-tag v-else-if="(insumo.tipoVenta || 'NORMAL') === 'COMBO'" type="is-link" class="ml-1 is-size-7">Combo</b-tag>
                            <b-tag v-if="insumo.codigo" type="is-light" class="ml-1 is-size-7">{{ insumo.codigo }}</b-tag>
                        </div>

                        <!-- Nombre y descripción -->
                        <p class="title is-5 mb-1" :class="{ 'has-text-grey-light': !insumo.nombre }">
                            {{ insumo.nombre || 'Nombre del producto' }}
                        </p>
                        <p class="is-size-7 has-text-grey mb-3" style="min-height:2rem">
                            {{ insumo.descripcion || 'Sin descripción...' }}
                        </p>

                        <hr class="my-2">

                        <!-- Precio y stock -->
                        <div class="level is-mobile mb-2">
                            <div class="level-left">
                                <div class="level-item">
                                    <div>
                                        <p class="heading">Precio</p>
                                        <p class="title is-4" style="color: var(--color-primario)">
                                            Bs. {{ insumo.precio || '0' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="level-right">
                                <div class="level-item has-text-right">
                                    <div>
                                        <p class="heading">Stock</p>
                                        <b-tag :type="stockTagType" size="is-medium">
                                            {{ insumo.stock || 0 }} uds
                                        </b-tag>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info materia prima calculada -->
                        <template v-if="insumo.stockMateria > 0 && insumo.tipoCorte > 0">
                            <hr class="my-2">
                            <p class="is-size-7 has-text-grey">
                                <b-icon icon="information-outline" size="is-small"></b-icon>
                                <span v-if="insumo.tipo === 'PLATILLO'">
                                    {{ insumo.stockMateria }} kg →
                                    <strong>{{ Math.floor((insumo.stockMateria * 1000) / insumo.tipoCorte) }} porciones</strong> disponibles
                                </span>
                                <span v-else-if="insumo.tipo === 'BEBIDA'">
                                    {{ insumo.stockMateria }} L →
                                    <strong>{{ Math.floor((insumo.stockMateria * 1000) / insumo.tipoCorte) }} unidades</strong> disponibles
                                </span>
                            </p>
                        </template>
                    </div>
                </div>

                <!-- Errores de validación -->
                <div v-if="errores.length > 0" class="mt-3">
                    <article class="message is-danger is-small">
                        <div class="message-body">
                            <ul>
                                <li v-for="error in errores" :key="error">{{ error }}</li>
                            </ul>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <!-- ── Columna derecha: Formulario ── -->
        <div class="column is-8-desktop is-12-tablet">

            <!-- Fila 1: Tipo · Categoría · Código -->
            <b-field grouped group-multiline class="mb-4">
                <b-field label="Tipo" :label-position="'inside'">
                    <b-select v-model="insumo.tipo" placeholder="Tipo">
                        <option value="PLATILLO">Platillo</option>
                        <option value="BEBIDA">Bebida</option>
                    </b-select>
                </b-field>
                <b-field label="Categoría" :label-position="'inside'" expanded>
                    <b-select v-model="insumo.categoria" expanded placeholder="Selecciona la categoría">
                        <option v-for="categoria in categorias" :key="categoria.id" :value="normalizarIdCategoria(categoria.id)">
                            {{ categoria.nombre }}
                        </option>
                    </b-select>
                </b-field>
                <b-field label="Código" :label-position="'inside'">
                    <b-input type="text" placeholder="Ej: PL-001" v-model="insumo.codigo" style="width:110px"></b-input>
                </b-field>
            </b-field>

            <!-- Fila 2: Nombre · Precio -->
            <b-field grouped group-multiline class="mb-4">
                <b-field label="Nombre" :label-position="'inside'" expanded>
                    <b-input type="text" placeholder="Nombre del insumo" v-model="insumo.nombre"></b-input>
                </b-field>
                <b-field label="Precio (Bs.)" :label-position="'inside'">
                    <b-input type="number" min="0" placeholder="0.00" v-model="insumo.precio" style="width:130px"></b-input>
                </b-field>
            </b-field>

            <hr class="my-3">
            <p class="heading has-text-grey mb-3">Venta e inventario</p>
            <b-field label="Tipo de venta" :label-position="'inside'" class="mb-3">
                <b-select v-model="insumo.tipoVenta" placeholder="Normal" expanded>
                    <option value="NORMAL">Normal (descontar este insumo)</option>
                    <option value="RECETA">Receta fija (descontar componentes)</option>
                    <option value="COMBO">Menú / combo (opciones por plantilla)</option>
                </b-select>
            </b-field>
            <b-field v-if="insumo.tipoVenta === 'COMBO'" label="Plantilla de menú (Obligatorio)" :label-position="'inside'" class="mb-3" type="is-info" message="Define qué slots tiene este combo">
                <b-select v-model="insumo.idComboPlantilla" expanded placeholder="-- Selecciona una plantilla --">
                    <option v-for="p in plantillasCombo" :key="'pc' + p.id" :value="normalizarId(p.id)">{{ p.nombre }}</option>
                </b-select>
            </b-field>
            <div v-if="insumo.tipoVenta === 'RECETA'" class="mb-4">
                <p class="is-size-7 has-text-grey mb-2">Componentes por cada <strong>1</strong> unidad vendida de este producto</p>
                <div v-for="(r, idx) in insumo.receta" :key="'rec' + idx" class="columns is-mobile is-variable is-1 mb-2">
                    <div class="column">
                        <b-field label="Insumo Componente" :label-position="'inside'" :type="!r.idInsumoHijo && r.nombreBusqueda ? 'is-danger' : (!r.nombreBusqueda ? '' : 'is-success')" :message="!r.idInsumoHijo && r.nombreBusqueda ? 'Selecciona una opción del menú' : ''">
                            <b-autocomplete
                                v-model="r.nombreBusqueda"
                                :data="filtrarInsumosComponentes(r.nombreBusqueda)"
                                field="nombre"
                                placeholder="Escribir para buscar..."
                                icon="magnify"
                                clearable
                                open-on-focus
                                keep-first
                                @select="opt => { if (opt) { r.idInsumoHijo = normalizarId(opt.id); r.nombreBusqueda = opt.nombre; } else { r.idInsumoHijo = null; } }"
                                @typing="() => { r.idInsumoHijo = null; }"
                            >
                                <template slot-scope="props">
                                    <div class="media">
                                        <div class="media-content">
                                            <strong>{{ props.option.nombre }}</strong>
                                            <br>
                                            <small class="has-text-grey">Cod: {{ props.option.codigo || '—' }} | Stock: {{ props.option.stock }}</small>
                                        </div>
                                    </div>
                                </template>
                                <template slot="empty">Insumo no encontrado</template>
                            </b-autocomplete>
                        </b-field>
                    </div>
                    <div class="column is-narrow" style="max-width:110px">
                        <b-field label="Cant." :label-position="'inside'">
                            <b-input type="number" min="0.001" step="0.001" v-model.number="r.cantidad"></b-input>
                        </b-field>
                    </div>
                    <div class="column is-narrow">
                        <b-button type="is-danger" outlined icon-left="delete" class="mt-4" @click="insumo.receta.splice(idx, 1)"></b-button>
                    </div>
                </div>
                <b-button type="is-info" size="is-small" outlined icon-left="plus" @click="agregarLineaReceta">Agregar componente</b-button>
            </div>

            <!-- Fila 3: Descripción -->
            <b-field label="Descripción" :label-position="'inside'" class="mb-4">
                <b-input maxlength="200" type="textarea" rows="2"
                    placeholder="Escribe una pequeña descripción..." v-model="insumo.descripcion"></b-input>
            </b-field>

            <!-- Fila 4: Stock actual · Stock mínimo -->
            <b-field grouped group-multiline class="mb-4">
                <b-field label="Stock actual" :label-position="'inside'" expanded>
                    <b-input type="number" min="0" placeholder="0" v-model="insumo.stock"></b-input>
                </b-field>
                <b-field label="Stock mínimo" :label-position="'inside'" expanded>
                    <b-input type="number" min="0" placeholder="Alerta cuando baje de..." v-model="insumo.stockMinimo"></b-input>
                </b-field>
            </b-field>

            <!-- Fila 5: Materia prima — PLATILLO -->
            <template v-if="insumo.tipo === 'PLATILLO'">
                <hr class="my-3">
                <p class="heading has-text-grey mb-3">Materia prima (opcional)</p>
                <b-field grouped group-multiline class="mb-4">
                    <b-field label="Disponible (kg)" :label-position="'inside'" expanded>
                        <b-input type="number" min="0" step="0.1" placeholder="Ej: 3" v-model="insumo.stockMateria"></b-input>
                    </b-field>
                    <b-field label="Corte por porción (gr)" :label-position="'inside'" expanded>
                        <b-input type="number" min="0" step="1" placeholder="Ej: 250" v-model="insumo.tipoCorte"></b-input>
                    </b-field>
                </b-field>
            </template>

            <!-- Fila 5: Materia prima — BEBIDA -->
            <template v-if="insumo.tipo === 'BEBIDA'">
                <hr class="my-3">
                <p class="heading has-text-grey mb-3">Materia prima (opcional)</p>
                <b-field grouped group-multiline class="mb-4">
                    <b-field label="Disponible (L)" :label-position="'inside'" expanded>
                        <b-input type="number" min="0" step="0.1" placeholder="Ej: 5" v-model="insumo.stockMateria"></b-input>
                    </b-field>
                    <b-field label="Unidad en (ml)" :label-position="'inside'" expanded>
                        <b-input type="number" min="0" step="1" placeholder="Ej: 350" v-model="insumo.tipoCorte"></b-input>
                    </b-field>
                </b-field>
            </template>

            <hr class="my-4">
            <div class="has-text-right">
                <b-button type="is-primary" size="is-medium" icon-left="check" @click="registrar">
                    {{ editar ? 'Guardar cambios' : 'Registrar insumo' }}
                </b-button>
            </div>
        </div>

    </div>
</template>
<script>
import Utiles from '../../Servicios/Utiles'
import HttpService from '../../Servicios/HttpService'

export default {
    name: "DatosInsumo",
    props: {
        insumo: { type: Object, required: true },
        editar: { type: Boolean, default: false }
    },

    data: () => ({
        errores: [],
        categorias: [],
        plantillasCombo: [],
        insumosComponentes: []
    }),

    computed: {
        insumosParaReceta() {
            return (this.insumosComponentes || []).filter(x => String(x.id) !== String(this.insumo.id))
        },
        stockTagType() {
            const s = Number(this.insumo.stock) || 0
            const m = Number(this.insumo.stockMinimo) || 0
            if (s <= 0) return 'is-danger'
            if (m > 0 && s <= m) return 'is-warning'
            return 'is-success'
        }
    },

    watch: {
        insumo: {
            immediate: true,
            deep: true,
            handler(val) {
                if (!val) return
                if (!val.tipoVenta) this.$set(val, 'tipoVenta', 'NORMAL')
                if (val.idComboPlantilla === undefined || val.idComboPlantilla === null) this.$set(val, 'idComboPlantilla', '')
                if (!Array.isArray(val.receta)) this.$set(val, 'receta', [])
                
                if (Array.isArray(val.receta) && this.insumosComponentes && this.insumosComponentes.length > 0) {
                    val.receta.forEach(r => {
                        if (r.idInsumoHijo && !r.nombreBusqueda) {
                            const found = this.insumosComponentes.find(x => String(x.id) === String(r.idInsumoHijo))
                            if (found) this.$set(r, 'nombreBusqueda', found.nombre)
                        }
                    })
                }
            }
        },
        'insumo.tipo': {
            immediate: true,
            handler(nuevo, anterior) {
                if (!this.insumo) return
                const tipoVal = nuevo || ''
                const cambioReal =
                    anterior !== undefined &&
                    anterior !== null &&
                    String(anterior) !== '' &&
                    String(anterior) !== String(tipoVal)
                this.cargarListaCategorias(tipoVal, cambioReal)
            }
        }
    },

    mounted() {
        this.cargarPlantillasYComponentes()
    },

    methods: {
        filtrarInsumosComponentes(texto) {
            const t = (texto || '').toLowerCase().trim();
            if (!t) return this.insumosParaReceta.slice(0, 50);
            return this.insumosParaReceta
                .filter((x) => {
                    const nom = (x.nombre && String(x.nombre).toLowerCase()) || '';
                    const cod = (x.codigo && String(x.codigo).toLowerCase()) || '';
                    return nom.includes(t) || cod.includes(t);
                })
                .slice(0, 50);
        },
        normalizarId(id) {
            const n = parseInt(id, 10)
            return Number.isFinite(n) ? n : id
        },
        async cargarPlantillasYComponentes() {
            try {
                const pl = await HttpService.obtener('obtener_plantillas_combo.php')
                this.plantillasCombo = Array.isArray(pl) ? pl.filter(p => p.activo) : []
            } catch (e) {
                this.plantillasCombo = []
            }
            try {
                const ins = await HttpService.obtenerConDatos({ tipo: '', categoria: '', nombre: '' }, 'obtener_insumos.php')
                this.insumosComponentes = Array.isArray(ins) ? ins : []

                if (this.insumo && Array.isArray(this.insumo.receta)) {
                    this.insumo.receta.forEach(r => {
                        if (r.idInsumoHijo && !r.nombreBusqueda) {
                            const found = this.insumosComponentes.find(x => String(x.id) === String(r.idInsumoHijo))
                            if (found) this.$set(r, 'nombreBusqueda', found.nombre)
                        }
                    })
                }
            } catch (e) {
                this.insumosComponentes = []
            }
        },
        agregarLineaReceta() {
            if (!Array.isArray(this.insumo.receta)) this.$set(this.insumo, 'receta', [])
            this.insumo.receta.push({ idInsumoHijo: '', cantidad: 1, nombreBusqueda: '' })
        },
        normalizarIdCategoria(id) {
            const n = parseInt(id, 10)
            return Number.isFinite(n) ? n : id
        },
        registrar() {
            let datos = {
                tipo: this.insumo.tipo,
                codigo: this.insumo.codigo,
                nombre: this.insumo.nombre,
                descripcion: this.insumo.descripcion,
                categoria: this.insumo.categoria,
                precio: this.insumo.precio,
                stock: this.insumo.stock || 0,
                stockMinimo: this.insumo.stockMinimo || 0,
                stockMateria: this.insumo.stockMateria || 0,
                tipoCorte: this.insumo.tipoCorte || 0
            }

            // Para validación solo exigir los campos base; stockMateria y tipoCorte son opcionales en BEBIDA
            let datosValidar = {
                tipo: datos.tipo,
                codigo: datos.codigo,
                nombre: datos.nombre,
                descripcion: datos.descripcion,
                categoria: datos.categoria,
                precio: datos.precio
            }
            this.errores = Utiles.validar(datosValidar)
            if(this.errores.length > 0) return

            const tv = this.insumo.tipoVenta || 'NORMAL'
            if (tv === 'COMBO' && !this.insumo.idComboPlantilla) {
                this.$buefy.toast.open({ message: 'Elegí una plantilla de menú para el combo', type: 'is-warning' })
                return
            }
            if (tv === 'RECETA') {
                const ok = (this.insumo.receta || []).some(r => r.idInsumoHijo && Number(r.cantidad) > 0)
                if (!ok) {
                    this.$buefy.toast.open({ message: 'Agregá al menos un componente con cantidad mayor a 0', type: 'is-warning' })
                    return
                }
            }
            if (tv !== 'RECETA') {
                this.$set(this.insumo, 'receta', [])
            }
            if (tv !== 'COMBO') {
                this.$set(this.insumo, 'idComboPlantilla', null)
            }

            this.$emit("registrado", this.insumo)
        },

        /** limpiarSeleccion: true si el usuario cambió el tipo (hay que elegir otra categoría). */
        cargarListaCategorias(tipoValido, limpiarSeleccion) {
            if (!tipoValido || tipoValido === '') {
                this.categorias = []
                if (limpiarSeleccion) this.$set(this.insumo, 'categoria', '')
                return
            }
            if (limpiarSeleccion) this.$set(this.insumo, 'categoria', '')
            HttpService.obtenerConDatos({ tipo: tipoValido }, "obtener_categorias_tipo.php")
                .then((resultado) => {
                    this.categorias = Array.isArray(resultado) ? resultado : []
                    this.alinearCategoriaConOpciones()
                })
                .catch(() => {
                    this.categorias = []
                })
        },

        /** El API puede devolver id de categoría como string; el select usa números. */
        alinearCategoriaConOpciones() {
            const c = this.insumo.categoria
            if (c === '' || c === null || c === undefined || !this.categorias.length) return
            const idNum = parseInt(c, 10)
            if (!Number.isFinite(idNum)) return
            const existe = this.categorias.some((cat) => parseInt(cat.id, 10) === idNum)
            if (existe) this.$set(this.insumo, 'categoria', idNum)
        }
    }
}
</script>

<style scoped>
.insumo-preview-sticky {
    position: sticky;
    top: 80px;
}
.insumo-preview-card {
    border-radius: 10px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
    transition: box-shadow 0.2s;
}
.insumo-preview-card:hover {
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}
@media screen and (max-width: 1023px) {
    .insumo-preview-sticky {
        position: static;
    }
}
</style>