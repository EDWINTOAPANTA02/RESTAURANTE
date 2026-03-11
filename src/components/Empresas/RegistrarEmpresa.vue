<template>
  <section>
    <p class="title is-1 has-text-weight-bold">
      <b-icon icon="domain-plus" size="is-large" type="is-primary"></b-icon> Registrar Empresa (SaaS)
    </p>
    <b-breadcrumb align="is-left">
      <b-breadcrumb-item tag="router-link" to="/">Inicio</b-breadcrumb-item>
      <b-breadcrumb-item tag="router-link" to="/empresas">Empresas</b-breadcrumb-item>
      <b-breadcrumb-item active>Registrar</b-breadcrumb-item>
    </b-breadcrumb>

    <div class="columns">
      <div class="column is-6">
        <div class="box">
          <p class="title is-4">1. Datos de la Empresa</p>
          <b-field label="Razón Social / Nombre del Restaurante">
            <b-input v-model="empresa.razon_social" required></b-input>
          </b-field>
          <b-field label="RUC / Identificación">
            <b-input v-model="empresa.ruc"></b-input>
          </b-field>
          <b-field label="Correo de contacto de la empresa">
            <b-input type="email" v-model="empresa.correo"></b-input>
          </b-field>
          <b-field label="Teléfono">
            <b-input v-model="empresa.telefono"></b-input>
          </b-field>
          <b-field label="Plan Contratado">
            <b-select v-model="empresa.plan" expanded>
              <option value="BASICO">Básico</option>
              <option value="PRO">Pro</option>
              <option value="PREMIUM">Premium</option>
            </b-select>
          </b-field>
        </div>
      </div>
      
      <div class="column is-6">
        <div class="box">
          <p class="title is-4">2. Cuenta Administrador Principal</p>
          <p class="subtitle is-6 has-text-grey">Esta cuenta será entregada al dueño para que entre al sistema y gestione su restaurante.</p>
          <b-field label="Nombre completo del Dueño">
            <b-input v-model="empresa.admin_nombre" required></b-input>
          </b-field>
          <b-field label="Correo de acceso (Usuario)">
            <b-input type="email" v-model="empresa.admin_correo" required></b-input>
          </b-field>
          <b-field label="Contraseña">
            <b-input type="password" v-model="empresa.admin_password" password-reveal required></b-input>
          </b-field>
        </div>
      </div>
    </div>
    
    <div class="has-text-centered">
      <b-button type="is-primary" size="is-large" icon-left="check" @click="registrar" :loading="cargando">
        Crear Empresa y Administrador
      </b-button>
    </div>

  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
  name: 'RegistrarEmpresa',
  data() {
    return {
      cargando: false,
      empresa: {
        razon_social: '',
        ruc: '',
        correo: '',
        telefono: '',
        plan: 'BASICO',
        admin_nombre: '',
        admin_correo: '',
        admin_password: ''
      }
    }
  },
  methods: {
    registrar() {
      if (!this.empresa.razon_social || !this.empresa.admin_correo || !this.empresa.admin_password) {
        this.$buefy.toast.open({
          message: 'Faltan datos obligatorios (Razón Social, Correo del Admin y Contraseña)',
          type: 'is-warning'
        })
        return
      }

      this.cargando = true
      HttpService.registrar(this.empresa, 'empresas.php').then(res => {
        if (res && res.resultado) {
          this.$buefy.toast.open({
            message: 'Empresa y usuario creados con éxito',
            type: 'is-success'
          })
          this.$router.push({ name: 'Empresas' })
        } else {
          this.$buefy.toast.open({
            message: res ? res.error : 'Error desconocido',
            type: 'is-danger'
          })
        }
        this.cargando = false
      })
    }
  }
}
</script>
