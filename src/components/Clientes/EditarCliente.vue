<template>
    <section>
        <p class="title is-1 has-text-weight-bold">Editar Cliente</p>
        <b-breadcrumb align="is-left">
            <b-breadcrumb-item tag="router-link" to="/">Inicio</b-breadcrumb-item>
            <b-breadcrumb-item tag="router-link" to="/clientes">Clientes</b-breadcrumb-item>
            <b-breadcrumb-item active>Editar</b-breadcrumb-item>
        </b-breadcrumb>
        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
        <div class="box" v-if="cliente.id">
            <datos-cliente :cliente="cliente" :modo-edicion="true" @guardado="onGuardado"></datos-cliente>
        </div>
    </section>
</template>

<script>
import DatosCliente from './DatosCliente.vue'
import HttpService from '../../Servicios/HttpService'

export default {
    name: 'EditarCliente',
    components: { DatosCliente },
    props: { id: { type: [String, Number], required: true } },

    data: () => ({
        cargando: false,
        cliente: {}
    }),

    mounted() {
        this.cargarCliente()
    },

    methods: {
        cargarCliente() {
            this.cargando = true
            HttpService.obtenerConDatos({ id: this.id }, 'obtener_cliente_id.php')
                .then(datos => {
                    this.cliente  = datos
                    this.cargando = false
                })
        },

        onGuardado(datos) {
            this.cargando = true
            HttpService.obtenerConDatos({ ...datos, id: this.id }, 'editar_cliente.php')
                .then(res => {
                    this.cargando = false
                    if (res && res.resultado) {
                        this.$buefy.toast.open({ message: 'Cliente actualizado correctamente.', type: 'is-success' })
                        this.$router.push('/clientes')
                    } else {
                        this.$buefy.toast.open({
                            message: (res && res.error) || 'Error al actualizar el cliente.',
                            type: 'is-danger', duration: 5000
                        })
                    }
                })
        }
    }
}
</script>
