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
                    <b-select v-model="insumo.tipo" @input="obtenerCategorias" placeholder="Tipo">
                        <option value="PLATILLO">Platillo</option>
                        <option value="BEBIDA">Bebida</option>
                    </b-select>
                </b-field>
                <b-field label="Categoría" :label-position="'inside'" expanded>
                    <b-select v-model="insumo.categoria" expanded placeholder="Selecciona la categoría">
                        <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
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
        categorias: []
    }),

    computed: {
        stockTagType() {
            const s = Number(this.insumo.stock) || 0
            const m = Number(this.insumo.stockMinimo) || 0
            if (s <= 0) return 'is-danger'
            if (m > 0 && s <= m) return 'is-warning'
            return 'is-success'
        }
    },

    methods: {
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
            this.$emit("registrado", this.insumo)
        },

        obtenerCategorias(tipo) {
            const valor = tipo && typeof tipo === 'object' && tipo.target ? tipo.target.value : tipo
            const tipoValido = valor !== undefined && valor !== null && valor !== '' ? valor : this.insumo.tipo
            if (!tipoValido || tipoValido === '') {
                this.categorias = []
                this.$set(this.insumo, 'categoria', '')
                return
            }
            this.$set(this.insumo, 'categoria', '')
            HttpService.obtenerConDatos({ tipo: tipoValido }, "obtener_categorias_tipo.php")
                .then(resultado => {
                    this.categorias = Array.isArray(resultado) ? resultado : []
                })
                .catch(() => {
                    this.categorias = []
                })
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