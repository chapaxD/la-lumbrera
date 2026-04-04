<template>
    <section>
         <ul v-if="errores.length > 0">
            <li class="has-text-danger has-text-centered" v-for="error in errores" :key="error">{{ error }}</li>
        </ul>
        <b-field label="Correo electrónico" >
            <b-input type="email" placeholder="Correo del usuario" v-model="usuario.correo"></b-input>
        </b-field>
        <b-field label="Nombre" >
            <b-input type="text" placeholder="Nombre del usuario" v-model="usuario.nombre"></b-input>
        </b-field>
        <b-field label="Teléfono" >
            <b-input type="text" placeholder="Teléfono del usuario" v-model="usuario.telefono"></b-input>
        </b-field>
        <b-field label="Rol de acceso" >
            <b-select placeholder="Selecciona el rol" v-model="usuario.rol" expanded required>
                <option value="mesero">Mesero (Solo ver y ordenar)</option>
                <option value="cocina">Cocina (Solo pantalla de comandas)</option>
                <option value="admin">Administrador (Acceso total)</option>
            </b-select>
        </b-field>

        <div class="has-text-right mt-4">
            <b-button type="is-primary" size="is-medium" icon-left="check" @click="registrar">
                {{ editar ? 'Guardar cambios' : 'Registrar usuario' }}
            </b-button>
        </div>
    </section>
</template>
<script>
import Utiles from '../../Servicios/Utiles'

export default ({
    name: "DatosUsuario",
    props: {
        usuario: { type: Object, required: true },
        editar: { type: Boolean, default: false }
    },

    data: () => ({
        errores: []
    }),

    methods: {
        registrar(){
            let datos = {
                correo: this.usuario.correo,
                nombre: this.usuario.nombre,
                telefono: this.usuario.telefono,
                rol: this.usuario.rol
            }
            this.errores = Utiles.validar(datos)
            if(this.errores.length > 0) return
            this.$emit("registrado", this.usuario)
        }
    }
})
</script>
