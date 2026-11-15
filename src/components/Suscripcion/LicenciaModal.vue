<template>
  <div>
    <!-- Modo Modal (cuando la suscripción caduca) -->
    <b-modal v-if="!modoPanel" :active="activa" has-modal-card :can-cancel="false">
      <div class="modal-card" style="width: 680px; max-width: 96vw;">
        <header class="modal-card-head has-background-danger">
          <p class="modal-card-title has-text-white">
            <b-icon icon="alert-decagram"></b-icon>
            Licencia del Sistema Caducada
          </p>
        </header>
        <section class="modal-card-body">
          <div class="notification is-danger is-light">
            <strong>{{ mensaje || 'Tu suscripción mensual ha expirado.' }}</strong><br>
            Selecciona un plan y realiza el pago con <strong>PayPal</strong> para reactivar tu restaurante inmediatamente por 30 días adicionales.
          </div>

          <div class="columns is-multiline">
            <div class="column is-4" v-for="p in listaPlanes" :key="p.codigo">
              <div
                class="box has-text-centered"
                :style="planSeleccionado === p.codigo ? 'border: 2px solid #7957d5; background: #f9f6ff;' : 'cursor: pointer;'"
                @click="seleccionarPlan(p.codigo)"
              >
                <p class="title is-5">{{ p.nombre }}</p>
                <p class="title is-3 has-text-primary">${{ p.precio }} <small class="is-size-6">/mes</small></p>
                <p class="is-size-7 has-text-grey">{{ p.detalle }}</p>
                <b-button
                  size="is-small"
                  :type="planSeleccionado === p.codigo ? 'is-primary' : 'is-light'"
                  class="mt-2"
                >
                  {{ planSeleccionado === p.codigo ? 'Seleccionado' : 'Elegir' }}
                </b-button>
              </div>
            </div>
          </div>

          <div class="has-text-centered mt-3">
            <div :id="contenedorId" style="max-width: 360px; margin: 0 auto;"></div>
            <p class="is-size-7 has-text-grey mt-2">O para pruebas rápidas de demostración en entorno local:</p>
            <b-button
              type="is-warning"
              icon-left="credit-card-check"
              class="mt-1"
              :loading="procesando"
              @click="simularPagoPayPal"
            >
              Checkout PayPal Sandbox Instantáneo (${{ precioActual }})
            </b-button>
          </div>
        </section>
        <footer class="modal-card-foot" style="justify-content: space-between;">
          <b-button type="is-text" icon-left="logout" @click="cerrarSesion">Cerrar sesión</b-button>
        </footer>
      </div>
    </b-modal>

    <!-- Modo Panel (dentro de Configurar) -->
    <div v-else class="box">
      <div class="level">
        <div class="level-left">
          <div>
            <p class="title is-4 has-text-weight-bold">
              <b-icon icon="shield-check" type="is-primary"></b-icon>
              Licencia del Sistema SaaS y Renovación PayPal
            </p>
            <p class="subtitle is-6" v-if="empresa">
              Plan actual: <b-tag type="is-primary">{{ empresa.plan }}</b-tag>
              &nbsp;|&nbsp;
              Vencimiento:
              <b-tag :type="expirado ? 'is-danger' : 'is-success'">
                {{ empresa.fecha_vencimiento || 'Sin fecha' }}
              </b-tag>
            </p>
          </div>
        </div>
      </div>

      <div class="columns is-multiline">
        <div class="column is-4" v-for="p in listaPlanes" :key="p.codigo">
          <div
            class="box has-text-centered"
            :style="planSeleccionado === p.codigo ? 'border: 2px solid #7957d5; background: #f9f6ff;' : 'cursor: pointer;'"
            @click="seleccionarPlan(p.codigo)"
          >
            <p class="title is-5">{{ p.nombre }}</p>
            <p class="title is-3 has-text-primary">${{ p.precio }} <small class="is-size-6">/mes</small></p>
            <p class="is-size-7 has-text-grey">{{ p.detalle }}</p>
            <b-button
              size="is-small"
              :type="planSeleccionado === p.codigo ? 'is-primary' : 'is-light'"
              class="mt-2"
            >
              {{ planSeleccionado === p.codigo ? 'Plan Seleccionado' : 'Seleccionar' }}
            </b-button>
          </div>
        </div>
      </div>

      <div class="columns is-vcentered">
        <div class="column is-6 has-text-centered">
          <p class="has-text-weight-bold mb-2">Pagar / Extender +1 Mes con PayPal (${{ precioActual }} USD):</p>
          <div :id="contenedorId" style="max-width: 340px; margin: 0 auto;"></div>
          <b-button
            type="is-warning"
            size="is-small"
            icon-left="credit-card-check"
            class="mt-2"
            :loading="procesando"
            @click="simularPagoPayPal"
          >
            Simular Pago PayPal Sandbox (${{ precioActual }} USD)
          </b-button>
        </div>
        <div class="column is-6">
          <p class="has-text-weight-bold mb-2">Historial de Pagos de Licencia:</p>
          <b-table :data="pagos" :narrowed="true" class="is-size-7" v-if="pagos.length > 0">
            <b-table-column field="fecha_pago" label="Fecha" v-slot="props">
              {{ props.row.fecha_pago }}
            </b-table-column>
            <b-table-column field="metodo_pago" label="Método" v-slot="props">
              <b-tag type="is-info" size="is-small">{{ props.row.metodo_pago }}</b-tag>
            </b-table-column>
            <b-table-column field="monto" label="Monto" v-slot="props">
              ${{ parseFloat(props.row.monto).toFixed(2) }}
            </b-table-column>
            <b-table-column field="fecha_vencimiento_nueva" label="Válido hasta" v-slot="props">
              {{ props.row.fecha_vencimiento_nueva }}
            </b-table-column>
          </b-table>
          <p class="has-text-grey is-size-7" v-else>Aún no se registran pagos de renovación.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
  name: 'LicenciaModal',
  props: {
    activa: { type: Boolean, default: false },
    modoPanel: { type: Boolean, default: false },
    mensaje: { type: String, default: '' }
  },
  data() {
    return {
      procesando: false,
      empresa: null,
      expirado: false,
      pagos: [],
      planSeleccionado: 'PRO',
      paypalClientId: 'sb',
      contenedorId: 'paypal-btn-' + Math.random().toString(36).substring(2, 8),
      listaPlanes: [
        { codigo: 'BASICO', nombre: 'Plan Básico', precio: '15.00', detalle: 'Hasta 5 usuarios, 50 insumos y 10 mesas' },
        { codigo: 'PRO', nombre: 'Plan Pro', precio: '30.00', detalle: 'Hasta 15 usuarios, 300 insumos, 30 mesas + SRI' },
        { codigo: 'PREMIUM', nombre: 'Plan Premium', precio: '50.00', detalle: 'Usuarios, insumos y mesas ilimitados + SRI' }
      ]
    }
  },
  computed: {
    precioActual() {
      const encontrado = this.listaPlanes.find(p => p.codigo === this.planSeleccionado)
      return encontrado ? encontrado.precio : '30.00'
    }
  },
  watch: {
    activa(val) {
      if (val) {
        this.cargarEstadoLicencia()
      }
    }
  },
  mounted() {
    if (this.modoPanel || this.activa) {
      this.cargarEstadoLicencia()
    }
  },
  methods: {
    seleccionarPlan(codigo) {
      this.planSeleccionado = codigo
    },
    cargarEstadoLicencia() {
      HttpService.registrar({ accion: 'estado_licencia' }, 'pagos.php').then(res => {
        if (res && res.resultado) {
          this.empresa = res.empresa
          this.expirado = res.expirado
          this.pagos = res.pagos || []
          if (res.empresa && res.empresa.plan) {
            this.planSeleccionado = res.empresa.plan === 'ENTERPRISE' ? 'PREMIUM' : res.empresa.plan
          }
          this.paypalClientId = res.paypal_client_id || 'sb'
          this.$nextTick(() => {
            this.inicializarPayPalSdk()
          })
        }
      })
    },
    inicializarPayPalSdk() {
      if (window.paypal) {
        this.renderizarBotonPayPal()
        return
      }
      const existing = document.getElementById('paypal-sdk-script')
      if (existing) {
        existing.addEventListener('load', () => this.renderizarBotonPayPal())
        return
      }
      const script = document.createElement('script')
      script.id = 'paypal-sdk-script'
      script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(this.paypalClientId)}&currency=USD`
      script.onload = () => this.renderizarBotonPayPal()
      document.body.appendChild(script)
    },
    renderizarBotonPayPal() {
      const el = document.getElementById(this.contenedorId)
      if (!el || !window.paypal) return
      el.innerHTML = ''
      window.paypal.Buttons({
        style: { layout: 'horizontal', color: 'gold', shape: 'rect', label: 'paypal', tagline: false },
        createOrder: (data, actions) => {
          return actions.order.create({
            purchase_units: [{
              description: `Licencia SaaS Restaurante - Plan ${this.planSeleccionado} (1 Mes)`,
              amount: { value: this.precioActual }
            }]
          })
        },
        onApprove: (data, actions) => {
          return actions.order.capture().then(details => {
            const email = (details && details.payer && details.payer.email_address) ? details.payer.email_address : ''
            this.procesarRenovacionBackend(data.orderID, email)
          })
        }
      }).render('#' + this.contenedorId)
    },
    simularPagoPayPal() {
      const orderIdSimulado = 'PAYPAL-SB-' + Math.random().toString(36).substring(2, 10).toUpperCase()
      this.procesarRenovacionBackend(orderIdSimulado, 'comprador-sandbox@paypal.com')
    },
    procesarRenovacionBackend(orderId, payerEmail) {
      this.procesando = true
      HttpService.registrar({
        accion: 'renovar_paypal',
        plan: this.planSeleccionado,
        paypal_order_id: orderId,
        payer_email: payerEmail
      }, 'pagos.php').then(res => {
        this.procesando = false
        if (res && res.resultado) {
          this.$buefy.toast.open({
            message: res.mensaje || 'Licencia renovada exitosamente con PayPal',
            type: 'is-success',
            duration: 5000
          })
          this.cargarEstadoLicencia()
          this.$emit('renovada', res)
        } else {
          this.$buefy.toast.open({
            message: (res && res.error) ? res.error : 'Error al activar la licencia',
            type: 'is-danger'
          })
        }
      })
    },
    cerrarSesion() {
      localStorage.removeItem('logeado')
      localStorage.removeItem('nombreUsuario')
      localStorage.removeItem('idUsuario')
      localStorage.removeItem('rol')
      window.location.reload()
    }
  }
}
</script>
