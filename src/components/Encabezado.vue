<template>
    <div class="header-wrapper">
        <div class="vencimiento-banner has-background-danger has-text-white has-text-centered py-2" v-if="mensajeVencimiento">
            <b-icon icon="alert-circle-outline" size="is-small" class="mr-2"></b-icon>
            <strong>{{ mensajeVencimiento }}</strong>
        </div>
        <b-navbar class="glass is-fixed-top" :transparent="true" :style="navbarStyle">
            <template #brand>
                <b-navbar-item class="ml-4">
                    <img :src="logo" alt="logo" class="mr-2" style="border-radius: 8px;">
                    <span class="has-text-weight-bold is-size-5 has-text-primary">{{ datosLocal.nombre }}</span>
                </b-navbar-item>
            </template>
            <template #start>
                <b-navbar-item tag="router-link" :to="{ path: '/' }">
                    <b-icon icon="home"></b-icon>
                    Inicio
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/insumos' }" v-if="esAdmin">
                    <b-icon icon="food-fork-drink"></b-icon>
                    Insumos
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/realizar-orden' }" v-if="!esSuperAdmin">
                    <b-icon icon="order-bool-ascending-variant"></b-icon>
                    Ordenar
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/categorias' }" v-if="esAdmin">
                    <b-icon icon="archive-outline"></b-icon>
                    Categorías
                </b-navbar-item>
                
                <b-navbar-item tag="router-link" :to="{ path: '/usuarios' }" v-if="esAdmin">
                    <b-icon icon="account-group"></b-icon>
                    Usuarios
                </b-navbar-item>
                
                <b-navbar-item tag="router-link" :to="{ path: '/reporte-ventas' }" v-if="esAdmin || esCajero">
                    <b-icon icon="cash-register"></b-icon>
                    Ventas
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/clientes' }" v-if="esAdmin || esCajero">
                    <b-icon icon="account-multiple"></b-icon>
                    Clientes
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/configurar' }" v-if="esAdmin">
                    <b-icon icon="application-cog-outline"></b-icon>
                    Configurar
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/empresas' }" v-if="esSuperAdmin">
                    <b-icon icon="domain"></b-icon>
                    Empresas SaaS
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/configurar-planes' }" v-if="esSuperAdmin">
                    <b-icon icon="cash-multiple"></b-icon>
                    Precios Planes
                </b-navbar-item>
            </template>

            <template #end>
                <b-navbar-item tag="div">
                    <div class="buttons">
                        <a class="button is-warning" @click="irAPerfil">
                            {{ nombreUsuario }}
                        </a>
                        <a class="button is-light" @click="salir">
                            Salir
                        </a>
                    </div>
                </b-navbar-item>
            </template>
        </b-navbar>
    </div>
</template>
<script>
import HttpService from '../Servicios/HttpService'
import Utiles from '../Servicios/Utiles'

export default ({
    name: 'Encabezado',
    data:() => ({
        expandOnHover: false,
        expandWithDelay: false,
        mobile: "reduce",
        reduce: false,
        datosLocal: {},
        nombreUsuario: "",
        rol: "",
        logo: null
    }),
    computed: {
        esSuperAdmin() {
            return this.rol === 'SUPERADMIN'
        },
        esAdmin() {
            return this.rol === 'ADMINISTRADOR'
        },
        esCajero() {
            return this.rol === 'CAJERO'
        },
        mensajeVencimiento() {
            if (!this.datosLocal.fecha_vencimiento || this.esSuperAdmin) return null
            const hoy = new Date()
            const vencimiento = new Date(this.datosLocal.fecha_vencimiento)
            const diffTime = vencimiento - hoy
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
            
            if (diffDays < 0) return "Suscripción vencida. Algunas funciones pueden estar limitadas."
            if (diffDays <= 7) return `Aviso: Tu plan de FoodSys vence en ${diffDays} día(s). ¡Manten tu restaurante activo!`
            return null
        },
        navbarStyle() {
            if (this.mensajeVencimiento) {
                return { top: '38px' } // Height of the banner
            }
            return {}
        }
    },

    mounted(){
        this.obtenerDatos()
        this.nombreUsuario = localStorage.getItem('nombreUsuario')
        this.rol = localStorage.getItem('rol')
        this.logo = Utiles.generarUrlImagen(this.datosLocal.logo)
    },

    methods: {
        irAPerfil(){
            this.$router.push({
                name: "Perfil",
            })
        },


        obtenerDatos(){
            HttpService.obtener("obtener_datos_local.php")
            .then(resultado => {
                this.datosLocal = resultado
                this.logo = Utiles.generarUrlImagen(this.datosLocal.logo)
            })
        },

        salir(){
            this.$buefy.dialog.confirm({
                title: '¿Salir de la aplicación?',
                message: 'Deseas salir',
                confirmText: 'Sí, salir',
                cancelText: 'No',
                type: 'is-danger',
                hasIcon: true,
                onConfirm: () => {
                    this.$emit("cerrar", false)
                    localStorage.removeItem('logeado')
                    localStorage.removeItem('rol')
                    localStorage.removeItem('nombreUsuario')
                    localStorage.removeItem('idUsuario')
                    this.$buefy.toast.open('Hasta pronto')
                }
            })
            
        }
    }
})
</script>
<style scoped>
.vencimiento-banner {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1001;
    font-size: 0.9rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
</style>
