<template>
  <section>
    <br />
    <div class="level">
      <div class="level-left">
        <div>
          <p class="title is-1 has-text-weight-bold mb-1">
            <b-icon icon="qrcode" size="is-large" type="is-primary"></b-icon>
            Menú Digital con Código QR por Mesa
          </p>
          <p class="subtitle is-6 has-text-grey">
            Imprime los códigos QR de tus mesas para que los clientes escaneen con su celular y vean la carta actualizada en tiempo real.
          </p>
        </div>
      </div>
      <div class="level-right">
        <b-button
          type="is-primary"
          size="is-medium"
          icon-left="eye"
          class="mr-2"
          @click="abrirVistaPrevia(1)"
        >
          Ver Menú Digital
        </b-button>
        <b-button
          type="is-dark"
          size="is-medium"
          icon-left="printer"
          @click="imprimirTodosQr"
        >
          Imprimir Todos los QR
        </b-button>
      </div>
    </div>

    <div class="columns is-multiline" id="zona-qr-mesas">
      <div
        class="column is-3-desktop is-4-tablet"
        v-for="numMesa in numeroMesas"
        :key="numMesa"
      >
        <div class="box has-text-centered qr-card">
          <p class="title is-4 mb-1">{{ datosLocal.nombre || 'Restaurante' }}</p>
          <span class="tag is-primary is-medium mb-3"><strong>MESA #{{ numMesa }}</strong></span>
          <div class=" my-2">
            <img
              :src="generarQrImagenUrl(numMesa)"
              :alt="'QR Mesa ' + numMesa"
              style="width: 180px; height: 180px; margin: 0 auto; display: block;"
            />
          </div>
          <p class="is-size-7 has-text-grey mt-2">
            Escanea el código QR con la cámara de tu celular para ver nuestro menú digital
          </p>
          <div class="buttons is-centered mt-3 no-print">
            <b-button
              size="is-small"
              type="is-info is-light"
              icon-left="open-in-new"
              @click="abrirVistaPrevia(numMesa)"
            >
              Probar Link
            </b-button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import HttpService from "../../Servicios/HttpService";
import Printd from "printd";

export default {
  name: "GeneradorQr",
  data: () => ({
    datosLocal: {},
    numeroMesas: 5,
    empresaId: 1,
  }),
  mounted() {
    this.obtenerDatos();
  },
  methods: {
    obtenerDatos() {
      HttpService.obtener("obtener_datos_local.php").then((res) => {
        if (res) {
          this.datosLocal = res;
          this.numeroMesas = parseInt(res.numeroMesas || 5, 10);
          this.empresaId = res.empresa_id || localStorage.getItem("empresaId") || 1;
        }
      });
    },
    construirUrlMenu(numMesa) {
      const base = window.location.origin + window.location.pathname;
      return `${base}#/menu-digital/${this.empresaId}?mesa=${numMesa}`;
    },
    generarQrImagenUrl(numMesa) {
      const urlMenu = this.construirUrlMenu(numMesa);
      return `https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=${encodeURIComponent(urlMenu)}`;
    },
    abrirVistaPrevia(numMesa) {
      const url = this.construirUrlMenu(numMesa);
      window.open(url, "_blank");
    },
    imprimirTodosQr() {
      const d = new Printd();
      const zona = document.getElementById("zona-qr-mesas");
      const css = `
        #zona-qr-mesas {
          display: flex;
          flex-wrap: wrap;
          gap: 16px;
          justify-content: center;
          font-family: Arial, sans-serif;
        }
        .qr-card {
          border: 2px dashed #222;
          border-radius: 12px;
          padding: 16px;
          width: 210px;
          text-align: center;
          page-break-inside: avoid;
        }
        .no-print {
          display: none !important;
        }
      `;
      d.print(zona, [css]);
    },
  },
};
</script>
