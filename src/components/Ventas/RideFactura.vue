<template>
  <section class="noMostrar">
    <div id="ride-factura" class="ride-container" v-if="venta && venta.factura">
      <div class="ride-top">
        <div class="ride-col-left">
          <img v-if="logo" :src="logo" alt="Logo" class="ride-logo" />
          <div class="ride-box">
            <h2 class="ride-empresa">{{ datosLocal.nombre || 'RESTAURANTE' }}</h2>
            <p><strong>Dirección Matriz:</strong> {{ datosLocal.direccion || 'Ecuador' }}</p>
            <p><strong>Teléfono:</strong> {{ datosLocal.telefono || 'S/N' }}</p>
            <p v-if="datosLocal.correo"><strong>Correo:</strong> {{ datosLocal.correo }}</p>
            <p><strong>Obligado a llevar contabilidad:</strong> NO</p>
          </div>
        </div>

        <div class="ride-col-right">
          <div class="ride-box ride-sri-box">
            <p class="ride-ruc"><strong>R.U.C.:</strong> {{ datosLocal.ruc || '9999999999001' }}</p>
            <h3 class="ride-doc-title">FACTURA</h3>
            <p><strong>No.:</strong> {{ venta.factura.numero_serie }}</p>
            <p><strong>NÚMERO DE AUTORIZACIÓN:</strong></p>
            <p class="ride-clave">{{ venta.factura.clave_acceso }}</p>
            <p><strong>FECHA Y HORA DE AUTORIZACIÓN:</strong> {{ venta.factura.fecha_autorizacion || venta.fecha }}</p>
            <p><strong>AMBIENTE:</strong> {{ datosLocal.ambiente_sri || 'PRUEBAS' }}</p>
            <p><strong>EMISIÓN:</strong> NORMAL</p>
            <p><strong>CLAVE DE ACCESO:</strong></p>
            <div class="ride-barcode">||| |||| || |||| ||| |||| ||| |||| || |||| ||| ||||</div>
            <p class="ride-clave">{{ venta.factura.clave_acceso }}</p>
          </div>
        </div>
      </div>

      <div class="ride-box ride-cliente-box">
        <p><strong>Razón Social / Nombres y Apellidos:</strong> {{ venta.cliente || 'CONSUMIDOR FINAL' }}</p>
        <p><strong>Identificación (RUC/Cédula):</strong> {{ venta.cliente_cedula || '9999999999999' }}</p>
        <p><strong>Fecha de Emisión:</strong> {{ (venta.fecha || '').substring(0, 10) }} &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Forma de Pago:</strong> {{ venta.metodo_pago || 'EFECTIVO' }}</p>
        <p v-if="venta.cliente_direccion"><strong>Dirección:</strong> {{ venta.cliente_direccion }}</p>
      </div>

      <table class="ride-table">
        <thead>
          <tr>
            <th>Código</th>
            <th>Cant.</th>
            <th>Descripción</th>
            <th>Precio Unit.</th>
            <th>Descuento</th>
            <th>Precio Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, idx) in venta.insumos" :key="idx">
            <td>{{ item.codigo || item.id }}</td>
            <td style="text-align: center;">{{ item.cantidad }}</td>
            <td>{{ item.nombre }}</td>
            <td style="text-align: right;">${{ parseFloat(item.precio).toFixed(2) }}</td>
            <td style="text-align: right;">$0.00</td>
            <td style="text-align: right;">${{ (parseFloat(item.precio) * parseFloat(item.cantidad)).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="ride-bottom">
        <div class="ride-info-adicional ride-box">
          <h4>Información Adicional</h4>
          <p><strong>Atendido por:</strong> {{ venta.atendio || 'Administrador' }}</p>
          <p><strong>Mesa / Orden:</strong> {{ Number(venta.idMesa) === 0 ? 'Para Llevar / Domicilio' : 'Mesa #' + venta.idMesa }}</p>
          <p v-if="venta.cliente_correo"><strong>Email Cliente:</strong> {{ venta.cliente_correo }}</p>
          <p v-if="venta.cliente_telefono"><strong>Teléfono Cliente:</strong> {{ venta.cliente_telefono }}</p>
          <p><strong>Estado SRI:</strong> {{ venta.factura.estado_sri }}</p>
        </div>

        <table class="ride-totales-table">
          <tbody>
            <tr>
              <td>SUBTOTAL SIN IMPUESTOS</td>
              <td style="text-align: right;">${{ subtotalSinImpuestos.toFixed(2) }}</td>
            </tr>
            <tr>
              <td>SUBTOTAL IVA ({{ porcentajeIva }}%)</td>
              <td style="text-align: right;">${{ subtotalSinImpuestos.toFixed(2) }}</td>
            </tr>
            <tr>
              <td>IVA ({{ porcentajeIva }}%)</td>
              <td style="text-align: right;">${{ valorIva.toFixed(2) }}</td>
            </tr>
            <tr class="ride-total-row">
              <td><strong>VALOR TOTAL</strong></td>
              <td style="text-align: right;"><strong>${{ parseFloat(venta.total).toFixed(2) }}</strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script>
import Printd from "printd";

export default {
  name: "RideFactura",
  props: ["venta", "datosLocal", "logo"],
  computed: {
    porcentajeIva() {
      return parseFloat(this.datosLocal.impuesto || 0);
    },
    subtotalSinImpuestos() {
      const total = parseFloat(this.venta.total || 0);
      if (this.porcentajeIva > 0) {
        return total / (1 + this.porcentajeIva / 100);
      }
      return total;
    },
    valorIva() {
      return parseFloat(this.venta.total || 0) - this.subtotalSinImpuestos;
    }
  },
  data: () => ({
    cssText: `
      @page { size: A4; margin: 12mm; }
      #ride-factura {
        font-family: Arial, Helvetica, sans-serif;
        color: #111;
        font-size: 12px;
        max-width: 760px;
        margin: 0 auto;
      }
      .ride-top {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 12px;
      }
      .ride-col-left, .ride-col-right {
        width: 49%;
      }
      .ride-logo {
        max-height: 75px;
        display: block;
        margin: 0 auto 8px auto;
      }
      .ride-box {
        border: 1px solid #222;
        border-radius: 6px;
        padding: 10px;
      }
      .ride-empresa {
        margin: 0 0 6px 0;
        font-size: 16px;
      }
      .ride-doc-title {
        margin: 6px 0;
        font-size: 18px;
        letter-spacing: 1px;
      }
      .ride-clave {
        font-family: 'Courier New', monospace;
        font-size: 10px;
        word-break: break-all;
        margin: 3px 0 6px 0;
      }
      .ride-barcode {
        font-family: 'Courier New', monospace;
        font-size: 20px;
        letter-spacing: 2px;
        text-align: center;
        margin: 6px 0 2px 0;
      }
      .ride-cliente-box {
        margin-bottom: 12px;
      }
      .ride-cliente-box p, .ride-box p {
        margin: 4px 0;
      }
      .ride-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
      }
      .ride-table th, .ride-table td {
        border: 1px solid #222;
        padding: 6px;
        font-size: 11px;
      }
      .ride-table th {
        background: #f1f5f9;
      }
      .ride-bottom {
        display: flex;
        justify-content: space-between;
        gap: 14px;
      }
      .ride-info-adicional {
        width: 55%;
      }
      .ride-info-adicional h4 {
        margin: 0 0 6px 0;
      }
      .ride-totales-table {
        width: 42%;
        border-collapse: collapse;
      }
      .ride-totales-table td {
        border: 1px solid #222;
        padding: 6px 8px;
      }
      .ride-total-row {
        background: #f1f5f9;
        font-size: 13px;
      }
    `
  }),
  mounted() {
    this.d = new Printd();
    const zona = document.getElementById("ride-factura");
    setTimeout(() => {
      this.d.print(zona, [this.cssText]);
      setTimeout(() => this.$emit("impreso", false), 300);
    }, 50);
  }
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
