<template>
    <section>
        <p class="title is-1 has-text-weight-bold">
            <b-icon
                icon="application-cog-outline"
                size="is-large"
                type="is-primary">
            </b-icon>
            Configurar 
        </p>
        <datos-configuracion @registrado="onRegistrado" :datos="datosLocal" ></datos-configuracion>

        <hr>
        <div class="box">
            <p class="title is-4 has-text-weight-bold">
                <b-icon icon="certificate" type="is-primary"></b-icon>
                Facturación Electrónica SRI (Firma Digital .p12)
            </p>
            <p class="subtitle is-6" v-if="estadoSri.certificado">
                Certificado activo — vence el <strong>{{ estadoSri.certificado.fecha_expira }}</strong>
            </p>
            <p class="subtitle is-6 has-text-grey" v-else>
                En Ambiente de Pruebas (1) puedes probar la firma y el envío SOAP de inmediato; sube tu archivo <code>.p12</code> oficial para firmar con tu RUC real.
            </p>
            <div class="columns">
                <div class="column is-4">
                    <b-field label="Ambiente SRI">
                        <b-select v-model="formSri.ambiente" expanded>
                            <option :value="1">1 - Pruebas (celcer.sri.gob.ec)</option>
                            <option :value="2">2 - Producción (cel.sri.gob.ec)</option>
                        </b-select>
                    </b-field>
                </div>
                <div class="column is-4">
                    <b-field label="Archivo de Firma Electrónica (.p12)">
                        <input class="input" type="file" accept=".p12,.pfx" @change="seleccionarP12">
                    </b-field>
                </div>
                <div class="column is-4">
                    <b-field label="Contraseña del Certificado .p12">
                        <b-input type="password" v-model="formSri.password" password-reveal placeholder="Contraseña de la firma"></b-input>
                    </b-field>
                </div>
            </div>
            <b-button type="is-primary" icon-left="upload" @click="guardarCertificadoSri">
                Guardar Firma Electrónica (.p12)
            </b-button>
        </div>

        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>

    </section>
</template>
<script>
import DatosConfiguracion from './DatosConfiguracion.vue'
import HttpService from '../../Servicios/HttpService'

export default ({
    name: "Configurar",
    components: { DatosConfiguracion },

    data: ()=>({
        cargando: false,
        datosLocal: {
            nombre: "",
            telefono: "",
            numeroMesas: 1,
        },
        estadoSri: {
            certificado: null
        },
        formSri: {
            ambiente: 1,
            archivo_base64: "",
            password: ""
        }
    }),

    mounted(){
        this.obtenerInformacion()
        this.obtenerEstadoSri()
    },

    methods: {
        onRegistrado(datos){
            this.cargando = true
            this.datosLocal = datos

            HttpService.registrar(this.datosLocal, "registrar_datos_local.php")
            .then(registrado => {
                this.cargando = false
                if(registrado && (registrado.resultado || registrado === true)) {
                    this.$buefy.toast.open({
                        message: 'Información actualizada. Recarga la página para ver los cambios',
                        type: 'is-success'
                    })
                    this.obtenerInformacion()
                } else {
                    this.$buefy.toast.open({
                        message: registrado ? registrado.error : 'No se pudo actualizar la información',
                        type: 'is-danger'
                    })
                }
            })
            .catch(e => {
                this.cargando = false
                console.error(e)
                this.$buefy.toast.open({
                    message: 'Error al conectar con el servidor',
                    type: 'is-danger'
                })
            })
        },
        obtenerInformacion(){
            this.cargando = true
            HttpService.obtener("obtener_datos_local.php")
            .then(resultado => {
                if (resultado) {
                    this.datosLocal = resultado
                    this.datosLocal.numeroMesas = parseInt(this.datosLocal.numeroMesas || 0)
                }
                this.cargando = false
            })
            .catch(e => {
                this.cargando = false
                console.error(e)
            })
        },
        obtenerEstadoSri(){
            HttpService.registrar({ accion: "estado_certificado" }, "emitir_factura.php")
            .then(res => {
                if (res && res.resultado) {
                    this.estadoSri = res
                    this.formSri.ambiente = res.ambiente || 1
                }
            })
        },
        seleccionarP12(e){
            const files = e.target.files
            if (files && files[0]) {
                const reader = new FileReader()
                reader.onload = ev => {
                    this.formSri.archivo_base64 = ev.target.result
                }
                reader.readAsDataURL(files[0])
            }
        },
        guardarCertificadoSri(){
            if (!this.formSri.archivo_base64 || !this.formSri.password) {
                this.$buefy.toast.open({
                    message: 'Selecciona tu archivo .p12 e ingresa su contraseña',
                    type: 'is-warning'
                })
                return
            }
            this.cargando = true
            HttpService.registrar({
                accion: "subir_certificado",
                ambiente: this.formSri.ambiente,
                archivo_base64: this.formSri.archivo_base64,
                password: this.formSri.password
            }, "emitir_factura.php").then(res => {
                this.cargando = false
                if (res && res.resultado) {
                    this.$buefy.toast.open({
                        message: `Firma electrónica validada correctamente (vence: ${res.fecha_expira})`,
                        type: 'is-success',
                        duration: 5000
                    })
                    this.formSri.password = ""
                    this.obtenerEstadoSri()
                } else {
                    this.$buefy.toast.open({
                        message: res && res.error ? res.error : 'Error al validar el archivo .p12',
                        type: 'is-danger',
                        duration: 5000
                    })
                }
            })
        }
    }
})
</script>
