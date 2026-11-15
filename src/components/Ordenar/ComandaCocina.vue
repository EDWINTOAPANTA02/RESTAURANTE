<template>
  <section class="noMostrar">
    <div id="comanda-cocina" class="comanda-cocina">
      <div class="comanda-header">
        <p class="comanda-titulo">*** COMANDA DE COCINA ***</p>
        <p class="comanda-subtitulo" v-if="orden.esAdicional">** PEDIDO ADICIONAL **</p>
        <p class="comanda-mesa">MESA #{{ orden.idMesa }}</p>
      </div>
      <div class="comanda-separador">================================</div>
      <div class="comanda-meta">
        <p><strong>FECHA/HORA:</strong> {{ orden.fecha }}</p>
        <p><strong>MESERO:</strong> {{ orden.atiende }}</p>
        <p v-if="orden.cliente"><strong>CLIENTE:</strong> {{ orden.cliente }}</p>
      </div>
      <div class="comanda-separador">--------------------------------</div>
      <table class="comanda-tabla">
        <thead>
          <tr>
            <th class="col-cant">CANT</th>
            <th class="col-desc">PLATILLO / DETALLE</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in insumos" :key="index">
            <td class="col-cant">{{ item.cantidad }}x</td>
            <td class="col-desc">
              <div class="item-nombre">{{ item.nombre }}</div>
              <div class="item-nota" v-if="item.caracteristicas">
                &gt;&gt; NOTA: {{ item.caracteristicas }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="comanda-separador">================================</div>
      <p class="comanda-pie">TOTAL ITEMS: {{ totalItems }}</p>
      <br />
      <p class="comanda-corte">. . . . . . . . . . . . . . . .</p>
    </div>
  </section>
</template>

<script>
import Printd from "printd";

export default {
  name: "ComandaCocina",
  props: ["orden", "insumos"],
  computed: {
    totalItems() {
      if (!this.insumos || !this.insumos.length) return 0;
      return this.insumos.reduce((acc, el) => acc + parseInt(el.cantidad || 1, 10), 0);
    }
  },
  data: () => ({
    cssText: `
      @page {
        margin: 2mm;
      }
      #comanda-cocina {
        width: 260px;
        margin: 0 auto;
        font-family: 'Courier New', Courier, monospace;
        color: #000;
        font-size: 12px;
        line-height: 1.25;
      }
      .comanda-header {
        text-align: center;
      }
      .comanda-titulo {
        font-size: 14px;
        font-weight: bold;
        margin: 2px 0;
      }
      .comanda-subtitulo {
        font-size: 13px;
        font-weight: bold;
        margin: 2px 0;
      }
      .comanda-mesa {
        font-size: 22px;
        font-weight: bold;
        margin: 4px 0;
        border: 2px solid #000;
        padding: 3px 0;
      }
      .comanda-separador {
        text-align: center;
        margin: 4px 0;
        font-weight: bold;
      }
      .comanda-meta p {
        margin: 2px 0;
        font-size: 12px;
      }
      .comanda-tabla {
        width: 100%;
        border-collapse: collapse;
        margin: 4px 0;
      }
      .comanda-tabla th {
        border-bottom: 1px dashed #000;
        text-align: left;
        font-size: 12px;
        padding: 3px 2px;
      }
      .comanda-tabla td {
        vertical-align: top;
        padding: 5px 2px;
        border-bottom: 1px dotted #000;
      }
      .col-cant {
        width: 42px;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
      }
      .col-desc {
        font-size: 14px;
        font-weight: bold;
      }
      .item-nombre {
        text-transform: uppercase;
      }
      .item-nota {
        font-size: 12px;
        font-weight: bold;
        margin-top: 2px;
        padding-left: 4px;
      }
      .comanda-pie {
        text-align: right;
        font-size: 13px;
        font-weight: bold;
        margin: 4px 0;
      }
      .comanda-corte {
        text-align: center;
        font-size: 10px;
        margin-top: 12px;
      }
    `
  }),
  mounted() {
    this.d = new Printd();
    this.imprimir();
  },
  methods: {
    imprimir() {
      const zona = document.getElementById("comanda-cocina");
      setTimeout(() => {
        this.d.print(zona, [this.cssText]);
        setTimeout(() => {
          this.$emit("impreso", false);
        }, 300);
      }, 50);
    }
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
