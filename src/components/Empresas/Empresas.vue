<template>
  <section>
    <p class="title is-1 has-text-weight-bold">
      <b-icon icon="domain" size="is-large" type="is-primary"></b-icon> Empresas (SaaS)
    </p>

    <div class="level">
      <div class="level-left">
        <b-button type="is-success" icon-left="plus" tag="router-link" to="/registrar-empresa">
          Registrar Empresa
        </b-button>
      </div>
    </div>

    <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>

    <div class="box">
      <b-table
        :data="empresas"
        :bordered="true"
        :striped="true"
        class="is-fullwidth"
      >
        <b-table-column field="id" label="ID" v-slot="props" width="40">
          {{ props.row.id }}
        </b-table-column>
        <b-table-column field="razon_social" label="Razón Social" v-slot="props">
          {{ props.row.razon_social }}
        </b-table-column>
        <b-table-column field="ruc" label="RUC" v-slot="props">
          {{ props.row.ruc || 'N/A' }}
        </b-table-column>
        <b-table-column field="contacto" label="Contacto" v-slot="props">
          {{ props.row.correo }}<br>
          <small class="has-text-grey">{{ props.row.telefono }}</small>
        </b-table-column>
        <b-table-column field="plan" label="Plan" v-slot="props">
          <b-tag type="is-link">{{ props.row.plan }}</b-tag>
        </b-table-column>
        <b-table-column field="fecha_vencimiento" label="Vencimiento" v-slot="props">
          <span :class="{'has-text-danger has-text-weight-bold': esExpirado(props.row.fecha_vencimiento)}">
            {{ props.row.fecha_vencimiento || 'N/A' }}
          </span>
        </b-table-column>
        <b-table-column field="estado" label="Estado" v-slot="props">
          <b-tag :type="props.row.estado === 'ACTIVO' ? 'is-success' : 'is-danger'">
            {{ props.row.estado }}
          </b-tag>
        </b-table-column>
        <b-table-column field="opciones" label="Opciones" v-slot="props">
          <b-button size="is-small" type="is-warning" icon-left="pencil" @click="editarEmpresa(props.row)">
            Editar
          </b-button>
          <b-button size="is-small" type="is-success" icon-left="cash" @click="abrirPago(props.row)" style="margin-left: 5px;">
            Pago
          </b-button>
        </b-table-column>
      </b-table>
    </div>

    <b-modal :active.sync="modalEdicionAbierto" has-modal-card>
      <div class="modal-card" style="width: auto" v-if="empresaEditada">
        <header class="modal-card-head">
          <p class="modal-card-title">Editar Empresa #{{empresaEditada.id}}</p>
        </header>
        <section class="modal-card-body">
          <b-field label="Razón Social">
            <b-input v-model="empresaEditada.razon_social" required></b-input>
          </b-field>
          <b-field label="RUC">
            <b-input v-model="empresaEditada.ruc" required></b-input>
          </b-field>
          <b-field label="Dirección Matriz">
            <b-input v-model="empresaEditada.direccion_matriz" required></b-input>
          </b-field>
          <b-field label="Correo de contacto">
            <b-input v-model="empresaEditada.correo" required></b-input>
          </b-field>
          <b-field label="Teléfono">
            <b-input v-model="empresaEditada.telefono" required></b-input>
          </b-field>
          <hr>
          <b-field label="Estado de la Cuenta">
            <b-select v-model="empresaEditada.estado" expanded>
              <option value="ACTIVO">Activo</option>
              <option value="INACTIVO">Inactivo (Suspendido)</option>
            </b-select>
          </b-field>
          <b-field label="Plan Actual">
            <b-select v-model="empresaEditada.plan" expanded>
              <option value="BASICO">Básico</option>
              <option value="PRO">Pro</option>
              <option value="PREMIUM">Premium</option>
            </b-select>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button @click="modalEdicionAbierto = false">Cancelar</b-button>
          <b-button type="is-primary" @click="guardarCambios">Guardar Cambios</b-button>
        </footer>
      </div>
    </b-modal>

    <!-- Modal para Registrar Pago -->
    <b-modal :active.sync="modalPagoAbierto" has-modal-card>
      <div class="modal-card" style="width: 400px" v-if="empresaParaPago">
        <header class="modal-card-head">
          <p class="modal-card-title">Registrar Pago: {{ empresaParaPago.razon_social }}</p>
        </header>
        <section class="modal-card-body">
          <b-field label="Monto Recibido ($)">
            <b-input type="number" v-model="pago.monto" required step="0.01"></b-input>
          </b-field>
          <b-field label="Método de Pago">
            <b-select v-model="pago.metodo_pago" expanded>
              <option value="EFECTIVO">Efectivo</option>
              <option value="TRANSFERENCIA">Transferencia</option>
              <option value="DEPOSITO">Depósito</option>
            </b-select>
          </b-field>
          <b-field label="Notas / Referencia">
            <b-input type="textarea" v-model="pago.notas" placeholder="Ej: Transferencia Banco Pichincha #12345"></b-input>
          </b-field>
          <p class="is-size-7 has-text-grey">
            * Al confirmar, se extenderá la fecha de vencimiento por 1 mes adicional desde la fecha actual de vencimiento.
          </p>
        </section>
        <footer class="modal-card-foot">
          <b-button @click="modalPagoAbierto = false">Cancelar</b-button>
          <b-button type="is-success" @click="guardarPago" icon-left="check">Confirmar Pago</b-button>
        </footer>
      </div>
    </b-modal>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
  name: 'Empresas',
  data() {
    return {
      cargando: false,
      empresas: [],
      modalEdicionAbierto: false,
      empresaEditada: null,
      modalPagoAbierto: false,
      empresaParaPago: null,
      pago: {
        monto: 0,
        metodo_pago: 'EFECTIVO',
        notas: ''
      }
    }
  },
  mounted() {
    this.obtenerEmpresas()
  },
  methods: {
    obtenerEmpresas() {
      this.cargando = true
      HttpService.obtener('empresas.php').then(res => {
        if (Array.isArray(res)) this.empresas = res
        this.cargando = false
      })
    },
    esExpirado(fecha) {
      if (!fecha) return false
      return new Date(fecha) < new Date()
    },
    abrirPago(empresa) {
      this.empresaParaPago = empresa
      this.pago = { monto: 0, metodo_pago: 'EFECTIVO', notas: '' }
      this.modalPagoAbierto = true
    },
    guardarPago() {
      if (!this.pago.monto || this.pago.monto <= 0) {
        return this.$buefy.toast.open({ message: 'El monto debe ser mayor a 0', type: 'is-warning' })
      }
      this.cargando = true
      let payload = {
        empresa_id: this.empresaParaPago.id,
        ...this.pago
      }
      HttpService.registrar(payload, 'pagos.php').then(res => {
         if (res && res.resultado) {
           this.$buefy.toast.open({ message: res.mensaje, type: 'is-success', duration: 5000 })
           this.obtenerEmpresas()
           this.modalPagoAbierto = false
         } else {
           this.$buefy.toast.open({ message: res ? res.error : 'Error al registrar pago', type: 'is-danger' })
         }
         this.cargando = false
      })
    },
    editarEmpresa(empresa) {
      this.empresaEditada = { ...empresa }
      this.modalEdicionAbierto = true
    },
    guardarCambios() {
      this.cargando = true
      HttpService.actualizar(this.empresaEditada, 'empresas.php').then(res => {
         if (res && res.resultado) {
           this.$buefy.toast.open({ message: 'Empresa actualizada', type: 'is-success' })
           this.obtenerEmpresas()
           this.modalEdicionAbierto = false
         } else {
           this.$buefy.toast.open({ message: 'Error al actualizar', type: 'is-danger' })
         }
         this.cargando = false
      })
    }
  }
}
</script>
