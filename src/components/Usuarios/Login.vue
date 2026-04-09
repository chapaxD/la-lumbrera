<template>
    <section class="split-login-container">
        <div class="columns is-gapless mb-0" style="min-height: 100vh;">
            
            <!-- Panel Izquierdo: Información de la Empresa -->
            <div class="column is-flex is-flex-direction-column is-justify-content-center is-align-items-center panel-info is-hidden-mobile">
                <div class="info-content has-text-centered">
                    <img src="@/assets/LA LUMBRERA B.png" alt="Logo" class="login-logo-blanco glass-effect p-4 mb-5 animate-fade-in-up">
                    <h1 class="title is-2 has-text-white mb-3 animate-fade-in-up delay-1">La Lumbrera</h1>
                    <h2 class="subtitle is-5 has-text-white mt-1 mb-5 animate-fade-in-up delay-2" style="opacity: 0.9;">
                        Gestión Gastronómica Inteligente
                    </h2>
                    <div class="features-list animate-fade-in-up delay-3">
                        <p><b-icon icon="point-of-sale" size="is-small"></b-icon> Control de Ventas</p>
                        <p><b-icon icon="table-furniture" size="is-small"></b-icon> Gestión de Mesas</p>
                        <p><b-icon icon="chef-hat" size="is-small"></b-icon> Administración de Cocina</p>
                    </div>
                </div>
            </div>

            <!-- Panel Derecho: Formulario de Login -->
            <div class="column is-flex is-justify-content-center is-align-items-center panel-form">
                <div class="form-wrapper">
                    
                    <!-- Logo para móviles (oculto en desktop) -->
                    <div class="has-text-centered is-hidden-tablet mb-5 animate-fade-in-up">
                        <img src="@/assets/LA LUMBRERA N.png" alt="Logo" class="login-logo-movil">
                    </div>

                    <form action="" class="box login-box animate-scale-in delay-1" :class="{ 'shake': errorCredenciales }" @submit.prevent="ingresar">
                        
                        <div class="has-text-centered mb-5 animate-fade-in-up delay-2">
                            <h3 class="title is-4 has-text-weight-bold mb-2">¡Bienvenido de vuelta!</h3>
                            <p class="has-text-grey is-size-6">Ingresa tus credenciales para acceder</p>
                        </div>

                        <div class="animate-fade-in-up delay-3 mb-4">
                            <b-field :type="errorCredenciales ? 'is-danger' : (correo && correo.includes('@') ? 'is-success' : '')" >
                                <b-input placeholder="Correo electrónico"
                                    type="email"
                                    icon="email"
                                    ref="inputCorreo"
                                    v-model="correo"
                                    :disabled="cargando"
                                    size="is-medium"
                                    @keyup.enter.native="ingresar">
                                </b-input>
                            </b-field>
                        </div>

                        <div class="animate-fade-in-up delay-4 mb-4">
                            <b-field :type="errorCredenciales ? 'is-danger' : ''" :message="errorCredenciales ? 'Correo o contraseña incorrectos' : ''">
                                <b-input type="password"
                                    placeholder="Contraseña"
                                    icon="lock"
                                    v-model="password"
                                    :disabled="cargando"
                                    password-reveal
                                    size="is-medium"
                                    @keyup.enter.native="ingresar">
                                </b-input>
                            </b-field>
                        </div>
                        
                        <div class="animate-fade-in-up delay-5 mb-2 has-text-left mt-3">
                            <b-checkbox v-model="recordarCorreo" size="is-small" type="is-primary" class="has-text-grey">
                                Recordar mi correo
                            </b-checkbox>
                        </div>

                        <div class="field has-text-centered mt-4 pt-2 animate-fade-in-up delay-5">
                            <b-button
                                icon-left="login"
                                type="is-primary"
                                size="is-medium"
                                native-type="submit"
                                :loading="cargando"
                                :disabled="cargando"
                                expanded
                                class="btn-login"
                                @click="ingresar">
                                Ingresar al Sistema
                            </b-button>
                        </div>
                    </form>
                </div>
                <b-loading :is-full-page="false" v-model="cargando" :can-cancel="false"></b-loading>
            </div>
            
        </div>
    </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'

export default {
    name: "Login",

    data: () => ({
        correo: "", 
        password: "",
        cargando: false,
        errorCredenciales: false,
        recordarCorreo: false
    }),

    mounted() {
        if (localStorage.getItem('correo_recordado')) {
            this.correo = localStorage.getItem('correo_recordado');
            this.recordarCorreo = true;
            setTimeout(() => {
                const passInput = this.$el.querySelector('input[type="password"]');
                if(passInput) passInput.focus();
            }, 800);
        } else {
            setTimeout(() => {
                if(this.$refs.inputCorreo) this.$refs.inputCorreo.focus();
            }, 800);
        }

        if (localStorage.getItem('sesion_expirada')) {
            localStorage.removeItem('sesion_expirada');
            this.$toast({
                message: 'Tu sesión expiró. Por favor ingresá nuevamente.',
                type: 'is-warning',
                duration: 5000
            });
        }
    },

    methods: {
        ingresar(){
            this.errorCredenciales = false
            if(!this.correo) {
                this.$toast({
                    message: 'Debes colocar el correo',
                    type: 'is-warning'
                })
                return
            }
            if(!this.password) {
                this.$toast({
                    message: 'Debes colocar la contraseña',
                    type: 'is-warning'
                })
                return
            }
            this.cargando = true
            let payload = {
                correo: this.correo,
                password: this.password
            }

            HttpService.obtenerConDatos(payload, "iniciar_sesion.php")
            .then(log => {
                // Guardar correo si el usuario lo desea y el login es exitoso
                if (log.resultado || log.resultado === "cambia") {
                    if (this.recordarCorreo) {
                        localStorage.setItem('correo_recordado', this.correo);
                    } else {
                        localStorage.removeItem('correo_recordado');
                    }
                }

                if(log.resultado === "cambia"){
                   this.$toast({
                        message: 'Datos correctos. Debes cambiar tu contraseña',
                        type: 'is-info'
                    })
                    this.$emit("logeado", log)
                    this.cargando = false
                    return 
                }

                if(log.resultado) {
                    this.$toast({
                        message: 'Datos correctos. Bienvenido',
                        type: 'is-success'
                    })
                    this.$emit("logeado", log)
                    this.cargando = false
                } else {
                    this.errorCredenciales = true
                    this.$toast({
                        message: 'Datos incorrectos. Verifica tu información',
                        type: 'is-danger'
                    })
                    this.cargando = false
                    setTimeout(() => { this.errorCredenciales = false }, 600)
                }
            })
            .catch(err => {
                this.cargando = false
                this.$toast({
                    message: 'Error de conexión. Verifica que el servidor esté activo.',
                    type: 'is-danger'
                })
                console.error('Error al iniciar sesión:', err)
            })
        }
    }

}
</script>
<style scoped>
@import url('https://fonts.googleapis.com/css?family=Amaranth');

.split-login-container {
    overflow: hidden;
}

/* Panel Izquierdo con imagen */
.panel-info {
    position: relative;
    background-image: url('~@/assets/login_bg.png'); /* Carga la imagen generada */
    background-size: cover;
    background-position: center;
}

.panel-info::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    /* Gradiente oscuro para que resalte el texto */
    background: linear-gradient(135deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 100%);
    z-index: 1;
}

.info-content {
    position: relative;
    z-index: 2;
    padding: 2rem;
}

.login-logo-blanco {
    width: 220px;
    height: auto;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}

.features-list {
    text-align: left;
    margin-top: 2rem;
    display: inline-block;
    background: rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(8px);
    padding: 1.5rem;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.features-list p {
    color: white;
    font-size: 1.1rem;
    margin-bottom: 0.8rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.features-list p:last-child { margin-bottom: 0; }

/* Panel Derecho */
.panel-form {
    background-color: var(--color-fondo, #f8f9fa);
    position: relative;
}

.form-wrapper {
    width: 100%;
    max-width: 550px; /* Más ancho */
    padding: 2rem;
    animation: float 6s ease-in-out infinite; /* Animación flotante continua */
}

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.login-box {
    margin: 2.5rem; /* Margen más notorio */
    border-radius: 24px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.1);
    padding: 3.5rem 3rem; /* Más grande internamente */
    border: 1px solid rgba(255,255,255,0.3);
    background: var(--color-primario-claro, rgba(255,255,255,0.85));
    backdrop-filter: blur(16px);
    transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.login-box:hover {
    transform: scale(1.02); /* Se agranda un poco al pasar el mouse */
    box-shadow: 0 25px 60px rgba(var(--color-primario-rgb, 0,0,0), 0.2);
    border-color: rgba(255,255,255,0.8);
}

/* Ajustes responsivos para evitar que el formulario se vea alargado en celulares */
@media (max-width: 768px) {
    .form-wrapper {
        padding: 0.5rem;
    }
    .login-box {
        margin: 1rem;
        padding: 2rem 1.5rem;
        border-radius: 20px;
    }
    .login-logo-movil {
        width: 120px;
        margin-top: 1rem;
    }
}

.login-logo-movil {
    width: 160px;
    height: auto;
}

.btn-login {
    border-radius: 8px;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}
.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* ========================================= */
/* CLASES DE ANIMACIÓN                       */
/* ========================================= */

.animate-fade-in-up {
    animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    opacity: 0;
    transform: translateY(30px);
}

.animate-scale-in {
    animation: scaleIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    opacity: 0;
    transform: scale(0.95);
}

/* Retrasos escalonados para efecto cascada */
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.3s; }
.delay-3 { animation-delay: 0.45s; }
.delay-4 { animation-delay: 0.6s; }
.delay-5 { animation-delay: 0.75s; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%       { transform: translateX(-8px); }
  40%       { transform: translateX(8px); }
  60%       { transform: translateX(-6px); }
  80%       { transform: translateX(6px); }
}

.shake {
    animation: shake 0.5s ease;
}
</style>