<template>
    <section>
        <p class="title is-1 has-text-weight-bold">Registrar Cliente</p>
        <b-breadcrumb align="is-left">
            <b-breadcrumb-item tag="router-link" to="/">Inicio</b-breadcrumb-item>
            <b-breadcrumb-item tag="router-link" to="/clientes">Clientes</b-breadcrumb-item>
            <b-breadcrumb-item active>Registrar</b-breadcrumb-item>
        </b-breadcrumb>
        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
        <div class="box">
            <datos-cliente @guardado="onGuardado"></datos-cliente>
        </div>
    </section>
</template>

<script>
import DatosCliente from './DatosCliente.vue'
import HttpService from '../../Servicios/HttpService'

export default {
    name: 'RegistrarCliente',
    components: { DatosCliente },

    data: () => ({ cargando: false }),

    methods: {
        onGuardado(datos) {
            this.cargando = true
            HttpService.registrar(datos, 'registrar_cliente.php')
                .then(res => {
                    this.cargando = false
                    if (res && res.resultado) {
                        this.$buefy.toast.open({ message: 'Cliente registrado correctamente.', type: 'is-success' })
                        this.$router.push('/clientes')
                    } else {
                        this.$buefy.toast.open({
                            message: (res && res.error) || 'Error al registrar el cliente.',
                            type: 'is-danger', duration: 5000
                        })
                    }
                })
        }
    }
}
</script>
