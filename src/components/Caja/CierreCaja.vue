<template>
  <section>
    <br />
    <p class="title is-1 has-text-weight-bold">
      <b-icon icon="cash-multiple" size="is-large" type="is-primary"></b-icon>
      Control y Arqueo de Caja
    </p>

    <!-- Si NO hay caja abierta -->
    <div class="box has-background-white" v-if="!cajaAbierta">
      <h3 class="title is-4 has-text-grey-dark">
        <b-icon icon="lock-open-outline" type="is-success"></b-icon>
        Abrir Turno de Caja
      </h3>
      <p class="subtitle is-6 has-text-grey">
        Registra el fondo inicial de efectivo (monedas y billetes) con el que inicias el día o turno.
      </p>
      <div class="columns">
        <div class="column is-4">
          <b-field label="Fondo Inicial en Efectivo ($)">
            <b-input
              type="number"
              step="0.01"
              min="0"
              v-model="formApertura.monto_inicial"
              placeholder="Ej. 50.00"
            ></b-input>
          </b-field>
        </div>
        <div class="column is-6">
          <b-field label="Observaciones de apertura (Opcional)">
            <b-input
              v-model="formApertura.observaciones"
              placeholder="Ej. Turno mañana - billetes sueltos"
            ></b-input>
          </b-field>
        </div>
        <div class="column is-2 is-flex is-align-items-flex-end">
          <b-button
            type="is-success"
            icon-left="check"
            expanded
            @click="abrirCaja"
          >
            Abrir Caja
          </b-button>
        </div>
      </div>
    </div>

    <!-- Si HAY caja abierta -->
    <div v-else>
      <div class="notification is-success is-light">
        <div class="level">
          <div class="level-left">
            <div>
              <p class="is-size-5 has-text-weight-bold">
                <b-icon icon="check-circle"></b-icon>
                Turno de Caja ABIERTO
              </p>
              <p class="is-size-7">
                <strong>Abierto desde:</strong> {{ cajaAbierta.fecha_apertura }} &nbsp;|&nbsp;
                <strong>Cajero:</strong> {{ cajaAbierta.cajero }}
              </p>
            </div>
          </div>
          <div class="level-right">
            <b-button
              type="is-warning"
              icon-left="cash-minus"
              class="mr-2"
              @click="registrarGasto"
            >
              Registrar Salida / Gasto
            </b-button>
            <b-button
              type="is-info"
              icon-left="refresh"
              @click="obtenerEstadoCaja"
            >
              Actualizar
            </b-button>
          </div>
        </div>
      </div>

      <!-- Tarjetas de Resumen en Tiempo Real -->
      <div class="columns is-multiline mb-4">
        <div class="column is-2">
          <div class="box has-text-centered">
            <p class="heading">Fondo Inicial</p>
            <p class="title is-4">${{ parseFloat(cajaAbierta.monto_inicial).toFixed(2) }}</p>
          </div>
        </div>
        <div class="column is-2">
          <div class="box has-text-centered">
            <p class="heading">Ventas Efectivo</p>
            <p class="title is-4 has-text-success">+${{ parseFloat(cajaAbierta.total_efectivo).toFixed(2) }}</p>
          </div>
        </div>
        <div class="column is-2">
          <div class="box has-text-centered">
            <p class="heading">Transferencias</p>
            <p class="title is-4 has-text-info">${{ parseFloat(cajaAbierta.total_transferencia).toFixed(2) }}</p>
          </div>
        </div>
        <div class="column is-2">
          <div class="box has-text-centered">
            <p class="heading">Tarjetas</p>
            <p class="title is-4 has-text-primary">${{ parseFloat(cajaAbierta.total_tarjeta).toFixed(2) }}</p>
          </div>
        </div>
        <div class="column is-2">
          <div class="box has-text-centered">
            <p class="heading">Gastos Caja Chica</p>
            <p class="title is-4 has-text-danger">-${{ parseFloat(cajaAbierta.gastos).toFixed(2) }}</p>
          </div>
        </div>
        <div class="column is-2">
          <div class="box has-text-centered has-background-primary has-text-white">
            <p class="heading has-text-white">Efectivo Esperado</p>
            <p class="title is-4 has-text-white">${{ efectivoEsperado.toFixed(2) }}</p>
          </div>
        </div>
      </div>

      <!-- Formulario de Arqueo y Cierre -->
      <div class="box">
        <h4 class="title is-4">
          <b-icon icon="calculator" type="is-primary"></b-icon>
          Realizar Cierre de Caja (Arqueo Final)
        </h4>
        <div class="columns is-vcentered">
          <div class="column is-4">
            <b-field label="Efectivo Físico Contado en Gaveta ($)">
              <b-input
                type="number"
                step="0.01"
                v-model="formCierre.efectivo_contado"
                placeholder="Cuenta los billetes y monedas"
              ></b-input>
            </b-field>
          </div>
          <div class="column is-3" v-if="formCierre.efectivo_contado !== ''">
            <p class="heading">Diferencia (Sobrante / Faltante)</p>
            <p
              class="title is-4"
              :class="diferenciaActual >= 0 ? 'has-text-success' : 'has-text-danger'"
            >
              {{ diferenciaActual >= 0 ? '+' : '' }}${{ diferenciaActual.toFixed(2) }}
              <span class="tag" :class="diferenciaActual === 0 ? 'is-success' : (diferenciaActual > 0 ? 'is-info' : 'is-danger')">
                {{ diferenciaActual === 0 ? 'CUADRE EXACTO' : (diferenciaActual > 0 ? 'SOBRANTE' : 'FALTANTE') }}
              </span>
            </p>
          </div>
          <div class="column is-3">
            <b-field label="Notas de cierre">
              <b-input
                v-model="formCierre.observaciones"
                placeholder="Observaciones del turno"
              ></b-input>
            </b-field>
          </div>
          <div class="column is-2">
            <b-button
              type="is-danger"
              size="is-medium"
              icon-left="lock"
              expanded
              @click="cerrarCaja"
            >
              Cerrar e Imprimir
            </b-button>
          </div>
        </div>
      </div>
    </div>

    <!-- Historial de Cierres -->
    <div class="box mt-5">
      <h4 class="title is-4 has-text-grey">
        <b-icon icon="history"></b-icon>
        Historial de Cierres de Caja
      </h4>
      <b-table :data="historial" :bordered="true" :striped="true" :narrowed="true">
        <b-table-column field="id" label="#" v-slot="props">{{ props.row.id }}</b-table-column>
        <b-table-column field="cajero" label="Cajero" v-slot="props">{{ props.row.cajero }}</b-table-column>
        <b-table-column field="fecha_apertura" label="Apertura" v-slot="props">{{ props.row.fecha_apertura }}</b-table-column>
        <b-table-column field="fecha_cierre" label="Cierre" v-slot="props">{{ props.row.fecha_cierre }}</b-table-column>
        <b-table-column field="monto_inicial" label="Inicial" v-slot="props">${{ parseFloat(props.row.monto_inicial).toFixed(2) }}</b-table-column>
        <b-table-column field="total_efectivo" label="Efectivo" v-slot="props">${{ parseFloat(props.row.total_efectivo).toFixed(2) }}</b-table-column>
        <b-table-column field="total_transferencia" label="Transf." v-slot="props">${{ parseFloat(props.row.total_transferencia).toFixed(2) }}</b-table-column>
        <b-table-column field="total_tarjeta" label="Tarjeta" v-slot="props">${{ parseFloat(props.row.total_tarjeta).toFixed(2) }}</b-table-column>
        <b-table-column field="gastos" label="Gastos" v-slot="props">-${{ parseFloat(props.row.gastos).toFixed(2) }}</b-table-column>
        <b-table-column field="efectivo_contado" label="Contado" v-slot="props">
          <strong>${{ parseFloat(props.row.efectivo_contado || 0).toFixed(2) }}</strong>
        </b-table-column>
        <b-table-column field="diferencia" label="Diferencia" v-slot="props">
          <span
            class="tag"
            :class="parseFloat(props.row.diferencia) === 0 ? 'is-success' : (parseFloat(props.row.diferencia) > 0 ? 'is-info' : 'is-danger')"
          >
            ${{ parseFloat(props.row.diferencia || 0).toFixed(2) }}
          </span>
        </b-table-column>
        <b-table-column field="acciones" label="Ticket" v-slot="props">
          <b-button
            size="is-small"
            type="is-dark"
            icon-left="printer"
            @click="imprimirTicketCierre(props.row)"
          >
            Imprimir
          </b-button>
        </b-table-column>
      </b-table>
    </div>

    <!-- Ticket Imprimible de Cierre para Impresora Matricial / Térmica -->
    <section class="noMostrar" v-if="cierreImprimir">
      <div id="ticket-cierre-caja">
        <p style="text-align:center; font-weight:bold; font-size:14px;">*** CORTE Y ARQUEO DE CAJA ***</p>
        <p style="text-align:center;">===============================</p>
        <p><strong>CAJERO:</strong> {{ cierreImprimir.cajero }}</p>
        <p><strong>APERTURA:</strong> {{ cierreImprimir.fecha_apertura }}</p>
        <p><strong>CIERRE:</strong> {{ cierreImprimir.fecha_cierre }}</p>
        <p style="text-align:center;">-------------------------------</p>
        <p>FONDO INICIAL:      ${{ parseFloat(cierreImprimir.monto_inicial).toFixed(2) }}</p>
        <p>+ VENTAS EFECTIVO:  ${{ parseFloat(cierreImprimir.total_efectivo).toFixed(2) }}</p>
        <p>- GASTOS CAJA:      ${{ parseFloat(cierreImprimir.gastos).toFixed(2) }}</p>
        <p style="text-align:center;">-------------------------------</p>
        <p><strong>EFECTIVO ESPERADO:  ${{ (parseFloat(cierreImprimir.monto_inicial) + parseFloat(cierreImprimir.total_efectivo) - parseFloat(cierreImprimir.gastos)).toFixed(2) }}</strong></p>
        <p><strong>EFECTIVO CONTADO:   ${{ parseFloat(cierreImprimir.efectivo_contado || 0).toFixed(2) }}</strong></p>
        <p><strong>DIFERENCIA:         ${{ parseFloat(cierreImprimir.diferencia || 0).toFixed(2) }}</strong></p>
        <p style="text-align:center;">-------------------------------</p>
        <p>TRANSFERENCIAS:     ${{ parseFloat(cierreImprimir.total_transferencia).toFixed(2) }}</p>
        <p>TARJETAS:           ${{ parseFloat(cierreImprimir.total_tarjeta).toFixed(2) }}</p>
        <p><strong>TOTAL VENTAS TURNO: ${{ (parseFloat(cierreImprimir.total_efectivo) + parseFloat(cierreImprimir.total_transferencia) + parseFloat(cierreImprimir.total_tarjeta)).toFixed(2) }}</strong></p>
        <p v-if="cierreImprimir.observaciones">NOTAS: {{ cierreImprimir.observaciones }}</p>
        <br /><br />
        <p style="text-align:center;">___________________________</p>
        <p style="text-align:center;">FIRMA CAJERO / RESPONSABLE</p>
      </div>
    </section>

    <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
  </section>
</template>

<script>
import HttpService from "../../Servicios/HttpService";
import Printd from "printd";

export default {
  name: "CierreCaja",
  data: () => ({
    cargando: false,
    cajaAbierta: null,
    historial: [],
    cierreImprimir: null,
    formApertura: {
      monto_inicial: 50,
      observaciones: "",
    },
    formCierre: {
      efectivo_contado: "",
      observaciones: "",
    },
  }),
  computed: {
    efectivoEsperado() {
      if (!this.cajaAbierta) return 0;
      return (
        parseFloat(this.cajaAbierta.monto_inicial || 0) +
        parseFloat(this.cajaAbierta.total_efectivo || 0) -
        parseFloat(this.cajaAbierta.gastos || 0)
      );
    },
    diferenciaActual() {
      const contado = parseFloat(this.formCierre.efectivo_contado || 0);
      return contado - this.efectivoEsperado;
    },
  },
  mounted() {
    this.obtenerEstadoCaja();
  },
  methods: {
    obtenerEstadoCaja() {
      this.cargando = true;
      HttpService.obtener("caja.php").then((res) => {
        this.cargando = false;
        this.cajaAbierta = res && res.abierta ? res.abierta : null;
        this.historial = res && Array.isArray(res.historial) ? res.historial : [];
        if (this.cajaAbierta) {
          this.formCierre.efectivo_contado = this.efectivoEsperado.toFixed(2);
          this.formCierre.observaciones = this.cajaAbierta.observaciones || "";
        }
      });
    },
    abrirCaja() {
      this.cargando = true;
      HttpService.registrar(
        {
          accion: "abrir",
          monto_inicial: this.formApertura.monto_inicial,
          observaciones: this.formApertura.observaciones,
        },
        "caja.php"
      ).then((res) => {
        this.cargando = false;
        if (res && res.resultado) {
          this.$buefy.toast.open({
            message: "¡Turno de caja abierto correctamente!",
            type: "is-success",
          });
          this.obtenerEstadoCaja();
        } else {
          this.$buefy.toast.open({
            message: (res && res.error) || "Error al abrir caja",
            type: "is-danger",
          });
        }
      });
    },
    registrarGasto() {
      this.$buefy.dialog.prompt({
        title: "Registrar Salida de Efectivo / Gasto",
        message: "Ingresa el monto ($) retirado de caja chica:",
        inputAttrs: { type: "number", step: "0.01", min: "0.01", placeholder: "Ej. 5.00" },
        confirmText: "Continuar",
        cancelText: "Cancelar",
        onConfirm: (monto) => {
          this.$buefy.dialog.prompt({
            title: "Motivo del Gasto",
            message: "¿En qué se utilizó este dinero?",
            inputAttrs: { placeholder: "Ej. Compra de hielo / servilletas" },
            confirmText: "Guardar Gasto",
            cancelText: "Cancelar",
            onConfirm: (motivo) => {
              HttpService.registrar(
                { accion: "gasto", gasto: monto, motivo: motivo },
                "caja.php"
              ).then((res) => {
                if (res && res.resultado) {
                  this.$buefy.toast.open({
                    message: "Salida de caja registrada",
                    type: "is-success",
                  });
                  this.obtenerEstadoCaja();
                }
              });
            },
          });
        },
      });
    },
    cerrarCaja() {
      if (this.formCierre.efectivo_contado === "") {
        this.$buefy.toast.open({
          message: "Ingresa el efectivo contado en gaveta",
          type: "is-warning",
        });
        return;
      }
      this.$buefy.dialog.confirm({
        title: "Confirmar Cierre de Caja",
        message: `Efectivo esperado: <b>$${this.efectivoEsperado.toFixed(2)}</b><br>Efectivo contado: <b>$${parseFloat(this.formCierre.efectivo_contado).toFixed(2)}</b><br>¿Deseas cerrar el turno e imprimir el comprobante de arqueo?`,
        confirmText: "Sí, cerrar caja",
        cancelText: "Cancelar",
        type: "is-danger",
        hasIcon: true,
        onConfirm: () => {
          this.cargando = true;
          HttpService.registrar(
            {
              accion: "cerrar",
              efectivo_contado: this.formCierre.efectivo_contado,
              observaciones: this.formCierre.observaciones,
            },
            "caja.php"
          ).then((res) => {
            this.cargando = false;
            if (res && res.resultado) {
              this.$buefy.toast.open({
                message: "Caja cerrada correctamente",
                type: "is-success",
              });
              if (res.cierre) {
                this.imprimirTicketCierre(res.cierre);
              }
              this.obtenerEstadoCaja();
            }
          });
        },
      });
    },
    imprimirTicketCierre(cierre) {
      this.cierreImprimir = cierre;
      this.$nextTick(() => {
        const d = new Printd();
        const zona = document.getElementById("ticket-cierre-caja");
        const css = `
          #ticket-cierre-caja {
            width: 260px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #000;
          }
          #ticket-cierre-caja p {
            margin: 3px 0;
          }
        `;
        setTimeout(() => d.print(zona, [css]), 50);
      });
    },
  },
};
</script>

<style scoped>
.noMostrar {
  position: fixed;
  left: -9999px;
  top: -9999px;
  visibility: hidden;
}
</style>
