<template>
    <section class="hero is-fullheight login-bg">
        <div class="hero-body">
            <div class="container has-text-centered">
                <div class="columns is-centered">
                    <div class="column is-5-tablet is-4-desktop is-4-widescreen animate-in">
                        <div class="box glass login-card">
                            <div class="mb-6">
                                <img src="@/assets/logo_empresa.png" alt="Logo" height="120" width="120" style="margin: 0 auto; display: block;">
                                <h1 class="title is-3 mt-4 has-text-dark">Bienvenido</h1>
                                <p class="subtitle is-6 has-text-grey">Gestiona tu restaurante con elegancia</p>
                            </div>

                            <form @submit.prevent="ingresar">
                                <b-field label="Empresa" label-position="on-border">
                                    <b-input 
                                        placeholder="RUC o Código"
                                        icon="domain"
                                        v-model="ruc"
                                        size="is-medium"
                                        required>
                                    </b-input>
                                </b-field>

                                <b-field label="Correo" label-position="on-border" class="mt-4">
                                    <b-input 
                                        placeholder="email@ejemplo.com"
                                        type="email"
                                        icon="email"
                                        v-model="correo"
                                        size="is-medium"
                                        required>
                                    </b-input>
                                </b-field>

                                <b-field label="Contraseña" label-position="on-border" class="mt-4">
                                    <b-input 
                                        type="password"
                                        placeholder="••••••••"
                                        v-model="password"
                                        size="is-medium"
                                        password-reveal
                                        required>
                                    </b-input>
                                </b-field>

                                <div class="mt-6">
                                    <b-button 
                                        native-type="submit"
                                        type="is-primary" 
                                        size="is-large" 
                                        expanded
                                        :loading="cargando"
                                        icon-left="login">
                                        Acceder al Sistema
                                    </b-button>
                                </div>
                            </form>
                        </div>
                        <div class="mt-4 has-text-centered">
                            <p class="has-text-white mb-2">¿Aún no tienes una cuenta?</p>
                            <b-button 
                                type="is-light" 
                                outlined 
                                rounded
                                icon-left="shopping"
                                @click="abrirPlanes">
                                Ver Planes y Adquirir Sistema
                            </b-button>
                        </div>
                        <p class="mt-6 has-text-white is-size-7 opacity-75">
                            &copy; 2024 FoodSys. Todos los derechos reservados.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Planes -->
        <b-modal :active.sync="modalPlanesActivo" width="900px" scroll="keep">
            <div class="modal-card" style="width: auto">
                <header class="modal-card-head has-background-primary">
                    <p class="modal-card-title has-text-white">Planes Disponibles</p>
                    <button type="button" class="delete" @click="modalPlanesActivo = false"/>
                </header>
                <section class="modal-card-body has-background-light">
                    <div v-if="cargandoPlanes" class="has-text-centered py-6">
                        <b-loading :is-full-page="false" v-model="cargandoPlanes"></b-loading>
                        <p class="mt-4">Cargando planes disponibles...</p>
                    </div>
                    <div v-else-if="planes.length === 0" class="has-text-centered py-6">
                        <b-icon icon="alert-circle-outline" size="is-large" type="is-warning"></b-icon>
                        <p class="mt-4">No se pudieron cargar los planes. Verifica tu conexión.</p>
                        <b-button type="is-primary" class="mt-4" @click="obtenerPlanes">Reintentar</b-button>
                    </div>
                    <div v-else class="columns is-multiline is-centered py-4">
                        <div class="column is-4" v-for="p in planes" :key="p.id">
                            <div class="box pricing-card has-text-centered" :class="{'is-featured': p.nombre_plan === 'PRO'}">
                                <span class="tag is-primary is-rounded mb-2" v-if="p.nombre_plan === 'PRO'">RECOMENDADO</span>
                                <h3 class="title is-4 mb-0">{{ p.nombre_plan }}</h3>
                                <p class="is-size-7 has-text-grey mb-4">{{ p.duracion_meses }} Meses de servicio</p>
                                <div class="plan-price mb-4">
                                    <span class="is-size-4">$</span>{{ p.precio }}
                                </div>
                                <div class="feature-list is-size-7">
                                    <div class="feature-item" v-if="p.nombre_plan === 'BASICO'">
                                        <b-icon icon="check" size="is-small"></b-icon> Hasta 5 usuarios
                                    </div>
                                    <div class="feature-item" v-if="p.nombre_plan === 'PRO'">
                                        <b-icon icon="check" size="is-small"></b-icon> Hasta 15 usuarios
                                    </div>
                                    <div class="feature-item" v-if="p.nombre_plan === 'PREMIUM' || p.nombre_plan === 'ENTERPRISE'">
                                        <b-icon icon="check" size="is-small"></b-icon> Usuarios Ilimitados
                                    </div>
                                    <div class="feature-item">
                                        <b-icon icon="check" size="is-small"></b-icon> Facturación Electrónica
                                    </div>
                                    <div class="feature-item">
                                        <b-icon icon="check" size="is-small"></b-icon> Reportes en Tiempo Real
                                    </div>
                                </div>
                                <b-button 
                                    type="is-primary" 
                                    expanded 
                                    @click="seleccionarPlan(p)"
                                    icon-left="cart-outline">
                                    Adquirir Ahora
                                </b-button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </b-modal>

        <!-- Modal de Pago PayPal -->
        <b-modal :active.sync="modalPagoActivo" width="450px" :can-cancel="false">
             <div class="modal-card" style="width: auto">
                <header class="modal-card-head has-background-dark">
                    <p class="modal-card-title has-text-white">Confirmar Pago</p>
                </header>
                <section class="modal-card-body">
                    <div class="has-text-centered mb-5">
                         <p class="subtitle is-6 mb-1">Has seleccionado el plan:</p>
                         <p class="title is-4 has-text-primary">{{ planSeleccionado ? planSeleccionado.nombre_plan : '' }}</p>
                         <p class="title is-3 mt-4">${{ planSeleccionado ? planSeleccionado.precio : '0.00' }}</p>
                    </div>
                    <div id="paypal-button-new-container" style="min-height: 150px;"></div>
                </section>
                <footer class="modal-card-foot">
                    <b-button @click="modalPagoActivo = false" expanded>Cancelar</b-button>
                </footer>
            </div>
        </b-modal>

        <!-- Modal de Registro Post-Pago -->
        <b-modal :active.sync="modalRegistroActivo" width="600px" :can-cancel="false" scroll="keep">
            <div class="modal-card" style="width: auto">
                <header class="modal-card-head has-background-success">
                    <p class="modal-card-title has-text-white">¡Pago Exitoso! Crea tu cuenta</p>
                </header>
                <section class="modal-card-body">
                    <div class="columns is-multiline">
                        <div class="column is-12">
                            <h4 class="title is-5 mb-4">Información de la Empresa</h4>
                            <b-field label="Nombre del Restaurante">
                                <b-input v-model="nuevaEmpresa.razon_social" placeholder="Mi Restaurante Gourmet" required></b-input>
                            </b-field>
                            <b-field label="RUC / Identificación Fiscal (Opcional)">
                                <b-input v-model="nuevaEmpresa.ruc" placeholder="1234567890001"></b-input>
                            </b-field>
                        </div>
                        <div class="column is-12">
                            <hr>
                            <h4 class="title is-5 mb-4">Cuenta de Administrador</h4>
                            <b-field label="Nombre del Dueño/Administrador">
                                <b-input v-model="nuevaEmpresa.admin_nombre" placeholder="Juan Pérez" required></b-input>
                            </b-field>
                            <b-field label="Correo Electrónico (Será tu usuario)">
                                <b-input type="email" v-model="nuevaEmpresa.admin_correo" placeholder="admin@mi-restaurante.com" required></b-input>
                            </b-field>
                            <b-field label="Contraseña">
                                <b-input type="password" v-model="nuevaEmpresa.admin_password" password-reveal required></b-input>
                            </b-field>
                        </div>
                    </div>
                </section>
                <footer class="modal-card-foot">
                    <b-button 
                        type="is-success" 
                        expanded 
                        :loading="cargandoRegistro"
                        @click="finalizarRegistro">
                        Completar Registro e Iniciar Sesión
                    </b-button>
                </footer>
            </div>
        </b-modal>

        <!-- Modal de Reactivación -->
        <b-modal :active.sync="modalReactivacionActivo" has-modal-card :can-cancel="false">
            <div class="modal-card" style="width: 450px" v-if="infoSuspension">
                <header class="modal-card-head has-background-danger">
                    <p class="modal-card-title has-text-white">Cuenta Suspendida</p>
                </header>
                <section class="modal-card-body">
                    <div class="has-text-centered mb-4">
                        <b-icon icon="alert-circle-outline" size="is-large" type="is-danger"></b-icon>
                        <p class="subtitle is-5 mt-2">¡Oh no! Tu suscripción ha vencido.</p>
                    </div>
                    <p class="has-text-grey mb-5">
                        {{ infoSuspension.mensaje }}
                    </p>
                    <div class="box has-background-light">
                        <div class="level is-mobile mb-0">
                            <div class="level-left">
                                <div>
                                    <p class="heading">Plan Actual</p>
                                    <p class="title is-6">{{ infoSuspension.plan }}</p>
                                </div>
                            </div>
                            <div class="level-right">
                                <div>
                                    <p class="heading">Monto a Pagar</p>
                                    <p class="title is-4 has-text-primary-dark">${{ calcularMonto(infoSuspension.plan).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="paypal-button-container" style="min-height: 150px;">
                        <div v-if="cargandoPaypal" class="has-text-centered p-4">
                            <b-loading :is-full-page="false" v-model="cargandoPaypal"></b-loading>
                            <p class="is-size-7 mt-2">Cargando métodos de pago...</p>
                        </div>
                    </div>
                </section>
                <footer class="modal-card-foot">
                    <b-button @click="modalReactivacionActivo = false" expanded>Cerrar</b-button>
                </footer>
            </div>
        </b-modal>
    </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'

export default {
    name: "Login",

    data: () => ({
        ruc: "",
        correo: "", 
        password: "",
        cargando:false,
        modalReactivacionActivo: false,
        infoSuspension: null,
        cargandoPaypal: false,
        sdkCargado: false,

        // Nuevos para Registro Público
        modalPlanesActivo: false,
        modalPagoActivo: false,
        modalRegistroActivo: false,
        planes: [],
        planSeleccionado: null,
        cargandoRegistro: false,
        cargandoPlanes: false,
        nuevaEmpresa: {
            razon_social: "",
            ruc: "",
            admin_nombre: "",
            admin_correo: "",
            admin_password: "",
            plan: ""
        }
    }),

    methods: {
        calcularMonto(plan) {
            const precios = {
                'BASICO': 15.00,
                'PRO': 30.00,
                'ENTERPRISE': 60.00,
                'PREMIUM': 60.00
            }
            return precios[plan] || 15.00
        },

        abrirModalReactivacion() {
            this.modalReactivacionActivo = true
            this.cargandoPaypal = true
            this.cargarPayPalSDK().then(() => {
                this.initPayPal()
            })
        },

        async cargarPayPalSDK() {
            if (this.sdkCargado && window.paypal) {
                return Promise.resolve()
            }

            let clientId = 'sb'
            try {
                const cfg = await HttpService.obtener("paypal/cliente_config.php")
                if (cfg && cfg.client_id) {
                    clientId = cfg.client_id
                }
            } catch (e) {
                console.warn("Usando PayPal client_id por defecto:", e)
            }

            return new Promise((resolve, reject) => {
                const existing = document.getElementById('paypal-sdk')
                if (existing) {
                    if (window.paypal) {
                        this.sdkCargado = true
                        resolve()
                        return
                    }
                    existing.addEventListener('load', () => {
                        this.sdkCargado = true
                        resolve()
                    })
                    return
                }
                const script = document.createElement('script')
                script.id = 'paypal-sdk'
                script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=USD`
                script.addEventListener('load', () => {
                    this.sdkCargado = true
                    resolve()
                })
                script.addEventListener('error', (e) => reject(e))
                document.body.appendChild(script)
            })
        },

        initPayPal() {
            this.cargandoPaypal = false
            // Pequeño delay para asegurar que el div existe en el modal animado
            setTimeout(() => {
                if (!window.paypal) return
                
                // Limpiar container previo si existe
                const container = document.getElementById('paypal-button-container')
                if (container) container.innerHTML = ''

                window.paypal.Buttons({
                    createOrder: (data, actions) => {
                        const monto = String(Number(this.calcularMonto(this.infoSuspension.plan)).toFixed(2))
                        const desc = `Reactivacion Plan ${this.infoSuspension.plan} - Empresa ID: ${this.infoSuspension.empresaId}`
                        return HttpService.registrar({
                            plan: this.infoSuspension.plan,
                            monto: monto,
                            descripcion: desc
                        }, "paypal/crear_orden.php").then(res => {
                            if (res && res.id) return res.id
                            return actions.order.create({
                                purchase_units: [{
                                    description: desc,
                                    amount: { currency_code: 'USD', value: monto }
                                }]
                            })
                        }).catch(() => {
                            return actions.order.create({
                                purchase_units: [{
                                    description: desc,
                                    amount: { currency_code: 'USD', value: monto }
                                }]
                            })
                        })
                    },
                    onApprove: (data, actions) => {
                        this.cargando = true
                        this.modalReactivacionActivo = false
                        const procesarCaptura = (orderID) => {
                            return HttpService.registrar({
                                orderID: orderID,
                                empresa_id: this.infoSuspension.empresaId,
                                monto: this.calcularMonto(this.infoSuspension.plan)
                            }, "paypal/capturar_orden.php").then(res => {
                                this.cargando = false
                                if (res && res.resultado) {
                                    this.$buefy.toast.open({
                                        message: '¡Excelente! Pago procesado. Ya puedes ingresar.',
                                        type: 'is-success',
                                        duration: 6000
                                    })
                                } else {
                                    this.$buefy.toast.open({
                                        message: 'Error al confirmar pago: ' + (res && res.error ? res.error : 'Error desconocido'),
                                        type: 'is-danger'
                                    })
                                }
                            })
                        }
                        if (actions && actions.order) {
                            return actions.order.capture().then(() => procesarCaptura(data.orderID)).catch(() => procesarCaptura(data.orderID))
                        }
                        return procesarCaptura(data.orderID)
                    },
                    onError: (err) => {
                        console.error("PayPal Error:", err)
                        this.$buefy.toast.open({
                            message: 'Ocurrió un problema con PayPal. Intenta de nuevo.',
                            type: 'is-danger'
                        })
                    }
                }).render('#paypal-button-container')
            }, 500)
        },

        abrirPlanes() {
            this.modalPlanesActivo = true
            this.obtenerPlanes()
        },

        obtenerPlanes() {
            this.cargandoPlanes = true
            HttpService.obtener("publico/obtener_planes.php").then(res => {
                this.cargandoPlanes = false
                if (Array.isArray(res)) {
                    this.planes = res
                } else {
                    this.planes = []
                }
            }).catch(e => {
                this.cargandoPlanes = false
                this.planes = []
                console.error(e)
            })
        },

        seleccionarPlan(plan) {
            this.planSeleccionado = plan
            this.nuevaEmpresa.plan = plan.nombre_plan
            this.modalPlanesActivo = false
            this.modalPagoActivo = true
            this.cargarPayPalSDK().then(() => {
                this.initPayPalNuevo()
            })
        },

        initPayPalNuevo() {
            setTimeout(() => {
                if (!window.paypal) return
                const container = document.getElementById('paypal-button-new-container')
                if (container) container.innerHTML = ''
                
                window.paypal.Buttons({
                    createOrder: (data, actions) => {
                        const monto = String(Number(this.planSeleccionado.precio).toFixed(2))
                        const desc = `Nuevo Registro Plan ${this.planSeleccionado.nombre_plan}`
                        return HttpService.registrar({
                            plan: this.planSeleccionado.nombre_plan,
                            monto: monto,
                            descripcion: desc
                        }, "paypal/crear_orden.php").then(res => {
                            if (res && res.id) return res.id
                            return actions.order.create({
                                purchase_units: [{
                                    description: desc,
                                    amount: { currency_code: 'USD', value: monto }
                                }]
                            })
                        }).catch(() => {
                            return actions.order.create({
                                purchase_units: [{
                                    description: desc,
                                    amount: { currency_code: 'USD', value: monto }
                                }]
                            })
                        })
                    },
                    onApprove: (data, actions) => {
                        const mostrarRegistro = () => {
                            this.modalPagoActivo = false
                            this.modalRegistroActivo = true
                            this.$buefy.toast.open({
                                message: '¡Pago recibido! Por favor, completa los datos de tu restaurante.',
                                type: 'is-success',
                                duration: 5000
                            })
                        }
                        if (actions && actions.order) {
                            return actions.order.capture().then(() => mostrarRegistro()).catch(() => mostrarRegistro())
                        }
                        mostrarRegistro()
                    },
                    onError: (err) => {
                        console.error("PayPal Error:", err)
                        this.$buefy.toast.open({
                            message: 'Ocurrió un problema con PayPal. Intenta de nuevo.',
                            type: 'is-danger'
                        })
                    }
                }).render('#paypal-button-new-container')
            }, 500)
        },

        finalizarRegistro() {
            if (!this.nuevaEmpresa.razon_social || !this.nuevaEmpresa.admin_correo || !this.nuevaEmpresa.admin_password) {
                this.$buefy.toast.open({
                    message: 'Por favor complete todos los campos obligatorios.',
                    type: 'is-warning'
                })
                return
            }

            this.cargandoRegistro = true
            HttpService.registrar(this.nuevaEmpresa, "publico/registrar_empresa.php").then(res => {
                this.cargandoRegistro = false
                if (res.resultado) {
                    this.$buefy.toast.open({
                        message: res.mensaje,
                        type: 'is-success',
                        duration: 5000
                    })
                    this.modalRegistroActivo = false
                    // Auto-llenar login
                    this.ruc = this.nuevaEmpresa.ruc
                    this.correo = this.nuevaEmpresa.admin_correo
                } else {
                    this.$buefy.toast.open({
                        message: res.error,
                        type: 'is-danger'
                    })
                }
            })
        },

        ingresar(){
            if(!this.ruc) {
                this.$buefy.toast.open({
                    message: 'Debes colocar el RUC o código de empresa',
                    type: 'is-warning'
                })
                return
            }
            if(!this.correo) {
                this.$buefy.toast.open({
                    message: 'Debes colocar el correo',
                    type: 'is-warning'
                })
                return
            }
            if(!this.password) {
                this.$buefy.toast.open({
                    message: 'Debes colocar la contraseña',
                    type: 'is-warning'
                })
                return
            }
            this.cargando = true
            let payload = {
                ruc: this.ruc,
                correo: this.correo,
                password: this.password
            }

            HttpService.obtenerConDatos(payload, "iniciar_sesion.php")
            .then(log => {
                this.cargando = false
                if (!log) {
                    this.$buefy.toast.open({
                        message: 'Error de comunicación con el servidor',
                        type: 'is-danger'
                    })
                    return
                }

                if (log.error === "SUSPENDIDA") {
                    this.infoSuspension = log
                    this.abrirModalReactivacion()
                    return
                }

                if(log.resultado === "cambia"){
                   this.$buefy.toast.open({
                        message: 'Datos correctos. Debes cambiar tu contraseña',
                        type: 'is-info'
                    })
                    this.$emit("logeado", log)
                    return 
                }

                if(log.resultado) {
                    this.$buefy.toast.open({
                        message: 'Datos correctos. Bienvenido',
                        type: 'is-success'
                    })
                    this.$emit("logeado", log)
                } else {
                    this.$buefy.toast.open({
                        message: log.error || 'Datos incorrectos. Verifica tu información',
                        type: 'is-danger'
                    })
                }
            })
            .catch(e => {
                this.cargando = false
                console.error(e)
                this.$buefy.toast.open({
                    message: 'Ocurrió un error al intentar iniciar sesión',
                    type: 'is-danger'
                })
            })
            
        }
    }

}
</script>