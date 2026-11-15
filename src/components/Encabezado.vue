<template>
    <div class="header-wrapper">
        <div class="vencimiento-banner has-background-danger has-text-white has-text-centered py-2" v-if="mensajeVencimiento">
            <b-icon icon="alert-circle-outline" size="is-small" class="mr-2"></b-icon>
            <strong>{{ mensajeVencimiento }}</strong>
        </div>

        <b-navbar class="glass is-fixed-top nav-profesional" :transparent="true" :style="navbarStyle">
            <template #brand>
                <b-navbar-item tag="router-link" :to="{ path: '/' }" class="brand-item">
                    <img :src="logo" alt="logo" class="brand-logo" />
                    <span class="brand-title">{{ datosLocal.nombre || 'FoodSys' }}</span>
                </b-navbar-item>
            </template>

            <template #start>
                <!-- Accesos directos de operación diaria -->
                <b-navbar-item tag="router-link" :to="{ path: '/' }" class="nav-link-item">
                    <b-icon icon="home-outline" size="is-small" class="nav-icon"></b-icon>
                    <span>Inicio</span>
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/realizar-orden' }" v-if="!esSuperAdmin" class="nav-link-item">
                    <b-icon icon="silverware-fork-knife" size="is-small" class="nav-icon"></b-icon>
                    <span>Ordenar</span>
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/caja' }" v-if="esAdmin || esCajero" class="nav-link-item">
                    <b-icon icon="cash-multiple" size="is-small" class="nav-icon"></b-icon>
                    <span>Caja</span>
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/reporte-ventas' }" v-if="esAdmin || esCajero" class="nav-link-item">
                    <b-icon icon="cash-register" size="is-small" class="nav-icon"></b-icon>
                    <span>Ventas</span>
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/clientes' }" v-if="esCajero && !esAdmin" class="nav-link-item">
                    <b-icon icon="account-multiple-outline" size="is-small" class="nav-icon"></b-icon>
                    <span>Clientes</span>
                </b-navbar-item>

                <!-- Menú Desplegable: Catálogo y Carta QR -->
                <b-navbar-dropdown v-if="esAdmin" hoverable boxed class="nav-dropdown">
                    <template #label>
                        <span class="dropdown-trigger-label">
                            <b-icon icon="book-open-page-variant-outline" size="is-small" class="nav-icon"></b-icon>
                            <span>Catálogo y QR</span>
                        </span>
                    </template>
                    <b-navbar-item tag="router-link" :to="{ path: '/insumos' }" class="dropdown-link">
                        <b-icon icon="food-fork-drink" size="is-small" class="nav-icon has-text-primary"></b-icon>
                        <div>
                            <p class="has-text-weight-semibold">Insumos y Stock</p>
                            <p class="is-size-7 has-text-grey">Platillos, bebidas y disponibilidad</p>
                        </div>
                    </b-navbar-item>
                    <b-navbar-item tag="router-link" :to="{ path: '/categorias' }" class="dropdown-link">
                        <b-icon icon="shape-outline" size="is-small" class="nav-icon has-text-info"></b-icon>
                        <div>
                            <p class="has-text-weight-semibold">Categorías</p>
                            <p class="is-size-7 has-text-grey">Clasificación del menú</p>
                        </div>
                    </b-navbar-item>
                    <hr class="navbar-divider" />
                    <b-navbar-item tag="router-link" :to="{ path: '/menu-qr' }" class="dropdown-link">
                        <b-icon icon="qrcode-scan" size="is-small" class="nav-icon has-text-success"></b-icon>
                        <div>
                            <p class="has-text-weight-semibold">Menú Digital QR</p>
                            <p class="is-size-7 has-text-grey">Códigos QR por mesa</p>
                        </div>
                    </b-navbar-item>
                </b-navbar-dropdown>

                <!-- Menú Desplegable: Administración -->
                <b-navbar-dropdown v-if="esAdmin" hoverable boxed class="nav-dropdown">
                    <template #label>
                        <span class="dropdown-trigger-label">
                            <b-icon icon="cog-outline" size="is-small" class="nav-icon"></b-icon>
                            <span>Administración</span>
                        </span>
                    </template>
                    <b-navbar-item tag="router-link" :to="{ path: '/clientes' }" class="dropdown-link">
                        <b-icon icon="account-multiple-outline" size="is-small" class="nav-icon has-text-info"></b-icon>
                        <div>
                            <p class="has-text-weight-semibold">Clientes</p>
                            <p class="is-size-7 has-text-grey">Directorio para facturación</p>
                        </div>
                    </b-navbar-item>
                    <b-navbar-item tag="router-link" :to="{ path: '/usuarios' }" class="dropdown-link">
                        <b-icon icon="badge-account-horizontal-outline" size="is-small" class="nav-icon has-text-primary"></b-icon>
                        <div>
                            <p class="has-text-weight-semibold">Usuarios y Personal</p>
                            <p class="is-size-7 has-text-grey">Meseros, cajeros y accesos</p>
                        </div>
                    </b-navbar-item>
                    <hr class="navbar-divider" />
                    <b-navbar-item tag="router-link" :to="{ path: '/configurar' }" class="dropdown-link">
                        <b-icon icon="store-cog-outline" size="is-small" class="nav-icon has-text-warning-dark"></b-icon>
                        <div>
                            <p class="has-text-weight-semibold">Configuración y SRI</p>
                            <p class="is-size-7 has-text-grey">Datos del local y firma electrónica</p>
                        </div>
                    </b-navbar-item>
                </b-navbar-dropdown>

                <!-- Opciones SuperAdmin -->
                <b-navbar-item tag="router-link" :to="{ path: '/empresas' }" v-if="esSuperAdmin" class="nav-link-item">
                    <b-icon icon="domain" size="is-small" class="nav-icon"></b-icon>
                    <span>Empresas SaaS</span>
                </b-navbar-item>

                <b-navbar-item tag="router-link" :to="{ path: '/configurar-planes' }" v-if="esSuperAdmin" class="nav-link-item">
                    <b-icon icon="cash-multiple" size="is-small" class="nav-icon"></b-icon>
                    <span>Precios Planes</span>
                </b-navbar-item>
            </template>

            <template #end>
                <!-- Menú desplegable de Usuario compacto -->
                <b-navbar-dropdown right hoverable boxed class="user-dropdown mr-3">
                    <template #label>
                        <div class="user-pill">
                            <b-icon icon="account-circle" size="is-small" class="mr-1"></b-icon>
                            <span class="user-name">{{ nombreUsuario }}</span>
                            <span class="tag is-light is-rounded is-size-7 ml-2">{{ rol }}</span>
                        </div>
                    </template>
                    <b-navbar-item @click="irAPerfil" class="dropdown-link">
                        <b-icon icon="account-edit-outline" size="is-small" class="nav-icon has-text-primary"></b-icon>
                        <span>Mi Perfil y Estadísticas</span>
                    </b-navbar-item>
                    <hr class="navbar-divider" />
                    <b-navbar-item @click="salir" class="dropdown-link has-text-danger">
                        <b-icon icon="logout" size="is-small" class="nav-icon has-text-danger"></b-icon>
                        <span class="has-text-weight-semibold">Cerrar Sesión</span>
                    </b-navbar-item>
                </b-navbar-dropdown>
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
                return { top: '38px' }
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
                message: 'Deseas cerrar tu sesión actual',
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

.nav-profesional {
    padding: 0.25rem 0.75rem;
    border-bottom: 1px solid rgba(226, 232, 240, 0.85);
}

.brand-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding-right: 1rem;
}

.brand-logo {
    border-radius: 8px;
    max-height: 36px;
}

.brand-title {
    font-weight: 700;
    font-size: 1.05rem;
    color: #16A34A;
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nav-link-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 500;
    font-size: 0.94rem;
    padding: 0.5rem 0.85rem !important;
    border-radius: 0.6rem;
    margin: 0 0.1rem;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.nav-link-item:hover,
.nav-link-item.router-link-exact-active {
    background-color: rgba(22, 163, 74, 0.1);
    color: #15803d !important;
}

.dropdown-trigger-label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 500;
    font-size: 0.94rem;
}

.nav-icon {
    margin-right: 0.25rem;
}

.dropdown-link {
    display: flex !important;
    align-items: center;
    gap: 0.65rem;
    padding: 0.55rem 1rem !important;
}

.user-pill {
    display: inline-flex;
    align-items: center;
    background: #fef3c7;
    color: #92400e;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.88rem;
}

.user-name {
    max-width: 155px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
