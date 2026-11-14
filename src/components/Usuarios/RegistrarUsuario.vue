<template>
    <section>
        <p class="title is-1 has-text-weight-bold">
            <b-icon
                icon="account-multiple-plus"
                size="is-large"
                type="is-primary">
            </b-icon>
            Registrar usuario
        </p> 
         <b-breadcrumb align="is-left" >
            <b-breadcrumb-item tag='router-link' to="/">Inicio</b-breadcrumb-item>
            <b-breadcrumb-item tag='router-link' to="/usuarios">Usuarios</b-breadcrumb-item>
        </b-breadcrumb>
        <datos-usuario @registrado="onRegistrado" :usuario="usuario"></datos-usuario> 
        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>

    </section>
</template>
<script>
import DatosUsuario from './DatosUsuario.vue'
import HttpService from '../../Servicios/HttpService'

export default ({
    name: "RegistrarUsuario",
    components : { DatosUsuario },

    data: () => ({
        usuario: {
            cedula: "",
            correo: "",
            nombre: "",
            telefono: "",
            rol: "MESERO"
        },
        cargando: false
    }),

    methods: {
        onRegistrado(usuario){
            this.cargando = true
            this.usuario = usuario
            HttpService.registrar(this.usuario, "registrar_usuario.php")
            .then(registrado => {
                if(registrado && registrado.resultado) {
                    this.usuario = {
                        cedula: "",
                        correo: "",
                        nombre: "",
                        telefono: "",
                        rol: "MESERO"
                    }
                    this.$buefy.toast.open({
                        message: 'Usuario registrado. Su contraseña inicial es su número de cédula',
                        type: 'is-success'
                    })
                } else {
                    this.$buefy.toast.open({
                        message: registrado ? registrado.error : 'Error al registrar usuario',
                        type: 'is-danger'
                    })
                }
                this.cargando = false
            })
        }
    }

})
</script>
