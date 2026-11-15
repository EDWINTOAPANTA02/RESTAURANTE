<template>
  <section>
    <br />
    <nav class="level">
      <div class="level-left">
        <div class="level-item">
            <p class="title is-1 has-text-weight-bold">
                <b-icon icon="cash-register" size="is-large" type="is-primary"> </b-icon>
                Reporte de ventas
            </p>

        </div>
      </div>

      <div class="level-right">
        <p class="level-item">
            <span
                class="has-background-success is-pulled-right has-text-white"
                style="font-size:3.5em; padding: 10px"
            >
                Total ${{ parseFloat(totalVentas).toFixed(2) }}
            </span>
        </p>
      </div>
    </nav>
    
    <div class="field is-grouped">
      <p class="control">
        <b-button class="mb-1" @click="recargar">
          <b-icon icon="refresh"></b-icon>
        </b-button>
      </p>
      <p class="control">
        <b-button
          type="is-primary"
          icon-left="filter-variant"
          @click="filtrar = !filtrar"
          >Filtrar</b-button
        >
      </p>
      <p class="control">
        <b-select v-model="perPage">
          <option value="5">5 por página</option>
          <option value="10">10 por página</option>
          <option value="15">15 por página</option>
          <option value="20">20 por página</option>
        </b-select>
      </p>
    </div>

    <div class="box" v-if="filtrar">
      <b-field grouped group-multiline>
        <b-field label="Selecciona un periodo de tiempo" expanded>
          <b-datepicker
            placeholder="Click para seleccionar..."
            v-model="fechasSeleccionadas"
            @input="buscarEnFecha"
            icon="calendar-today"
            range
          >
          </b-datepicker>
        </b-field>

        <b-field label="Ventas por usuario" expanded>
          <b-select
            v-model="usuarioSeleccionado"
            @change.native="buscarEnFecha"
          >
            <option value="" selected disabled>Selecciona</option>
            <option
              v-for="usuario in usuarios"
              :key="usuario.id"
              :value="usuario.id"
            >
              {{ usuario.nombre }}
            </option>
          </b-select>
        </b-field>
      </b-field>
    </div>

    <div class="box">
      <p class="title is-4 has-text-weight-bold has-text-grey">
        <b-icon icon="account" size="is-medium" type="is-primary"> </b-icon>
        Totales de ventas por usuario
      </p>
      <b-field grouped group-multiline>
        <div
          class="control"
          v-for="usuario in ventasPorUsuario"
          :key="usuario.nombre"
        >
          <b-taglist attached>
            <b-tag size="is-large">{{ usuario.nombre }}</b-tag>
            <b-tag size="is-large" type="is-info">${{ parseFloat(usuario.total).toFixed(2) }}</b-tag>
          </b-taglist>
        </div>
      </b-field>
    </div>
    <b-table
      :data="ventas"
      :paginated="isPaginated"
      :per-page="perPage"
      :bordered="true"
      :narrowed="true"
      :current-page.sync="currentPage"
      :pagination-simple="isPaginationSimple"
      :pagination-position="paginationPosition"
      :default-sort-direction="defaultSortDirection"
      :pagination-rounded="isPaginationRounded"
      :sort-icon="sortIcon"
      :sort-icon-size="sortIconSize"
      aria-next-label="Next page"
      aria-previous-label="Previous page"
      aria-page-label="Page"
      aria-current-label="Current page"
    >
      <b-table-column field="id" label="#" searchable sortable v-slot="props">
        {{ props.row.id }}
      </b-table-column>

      <b-table-column
        field="idMesa"
        label="Mesa"
        searchable
        sortable
        v-slot="props"
      >
        {{ Number(props.row.idMesa) === 0 ? 'Para Llevar' : 'Mesa # ' + props.row.idMesa }}
      </b-table-column>

      <b-table-column
        field="metodo_pago"
        label="Método"
        sortable
        v-slot="props"
      >
        <span class="tag is-light">{{ props.row.metodo_pago || 'EFECTIVO' }}</span>
      </b-table-column>

      <b-table-column
        field="fecha"
        label="Fecha"
        searchable
        sortable
        v-slot="props"
      >
        {{ props.row.fecha }}
      </b-table-column>

      <b-table-column
        field="atendio"
        label="Atendió"
        searchable
        sortable
        v-slot="props"
      >
        {{ props.row.atendio }}
      </b-table-column>

      <b-table-column
        field="cliente"
        label="Cliente"
        searchable
        sortable
        v-slot="props"
      >
        {{ props.row.cliente }}
      </b-table-column>

      <b-table-column field="pagado" label="Pago" v-slot="props">
        ${{ parseFloat(props.row.pagado).toFixed(2) }}
      </b-table-column>

      <b-table-column field="cambio" label="Cambio" sortable v-slot="props">
        ${{ parseFloat(props.row.pagado - props.row.total).toFixed(2) }}
      </b-table-column>

      <b-table-column field="total" label="Total" sortable v-slot="props">
        <strong>${{ parseFloat(props.row.total).toFixed(2) }}</strong>
      </b-table-column>

      <b-table-column field="factura" label="Facturación SRI" v-slot="props">
        <div v-if="props.row.factura">
          <p class="is-size-7"><strong>{{ props.row.factura.numero_serie }}</strong></p>
          <b-tag
            :type="props.row.factura.estado_sri === 'AUTORIZADA' ? 'is-success' : (props.row.factura.estado_sri === 'RECHAZADA' ? 'is-danger' : 'is-warning')"
            size="is-small"
          >
            {{ props.row.factura.estado_sri }}
          </b-tag>
          <p class="is-size-7 has-text-grey" v-if="props.row.factura.mensaje_sri">
            {{ props.row.factura.mensaje_sri }}
          </p>
        </div>
        <span class="tag is-light" v-else>Sin emitir</span>
      </b-table-column>

      <b-table-column field="insumos" label="Insumos" v-slot="props">
        <b-table :data="props.row.insumos" :narrowed="true" class="is-size-7">
          <b-table-column field="codigo" label="Código" v-slot="props">
            {{ props.row.codigo }}
          </b-table-column>
          <b-table-column field="nombre" label="Nombre" v-slot="props">
            {{ props.row.nombre }}
          </b-table-column>
          <b-table-column field="cantidad" label="Cantidad" v-slot="props">
            {{ props.row.cantidad }} X ${{ parseFloat(props.row.precio).toFixed(2) }}
          </b-table-column>
          <b-table-column field="subtotal" label="Subtotal" v-slot="props">
            ${{ parseFloat(props.row.cantidad * props.row.precio).toFixed(2) }}
          </b-table-column>
        </b-table>
      </b-table-column>

      <b-table-column field="acciones" label="Acciones" v-slot="props">
        <div class="field is-grouped">
          <p class="control">
            <b-button
              type="is-info"
              size="is-small"
              title="Imprimir Ticket"
              @click="imprimirComprobante(props.row)"
            >
              <b-icon icon="printer" size="is-small"></b-icon>
            </b-button>
          </p>
          <p class="control">
            <b-button
              type="is-primary"
              size="is-small"
              icon-left="send"
              @click="transmitirSri(props.row)"
            >
              SRI
            </b-button>
          </p>
          <p class="control" v-if="props.row.factura">
            <b-button
              type="is-danger"
              size="is-small"
              icon-left="file-pdf-box"
              title="Imprimir / Guardar PDF (RIDE)"
              @click="imprimirRide(props.row)"
            >
              RIDE
            </b-button>
          </p>
          <p class="control" v-if="props.row.factura">
            <b-button
              type="is-dark"
              size="is-small"
              icon-left="xml"
              @click="descargarXml(props.row.factura)"
            >
              XML
            </b-button>
          </p>
          <p class="control" v-if="props.row.factura">
            <b-button
              type="is-success"
              size="is-small"
              icon-left="whatsapp"
              title="Enviar Comprobante por WhatsApp"
              @click="enviarWhatsApp(props.row)"
            >
            </b-button>
          </p>
        </div>
      </b-table-column>
    </b-table>
    <ticket
      @impreso="onImpreso"
      :venta="this.ventaSeleccionada"
      :insumos="insumosSeleccionados"
      :datosLocal="datos"
      :logo="logo"
      v-if="mostrarTicket"
    ></ticket>
    <ride-factura
      @impreso="mostrarRide = false"
      :venta="ventaRide"
      :datosLocal="datos"
      :logo="logo"
      v-if="mostrarRide"
    ></ride-factura>
    <b-loading
      :is-full-page="true"
      v-model="cargando"
      :can-cancel="false"
    ></b-loading>
  </section>
</template>
<script>
import HttpService from "../../Servicios/HttpService";
import Utiles from "../../Servicios/Utiles";
import Ticket from "./Ticket.vue";
import RideFactura from "./RideFactura.vue";

export default {
  name: "ReporteVentas",
  components: { Ticket, RideFactura },

  data: () => ({
    usuarios: [],
    filtrar: false,
    datos: {},
    ventaSeleccionada: {},
    insumosSeleccionados: [],
    fechasSeleccionadas: [],
    usuarioSeleccionado: "",
    filtros: {},
    cargando: false,
    ventas: [],
    ventasPorUsuario: [],
    totalVentas: 0,
    mostrarTicket: false,
    mostrarRide: false,
    ventaRide: null,
    isPaginated: true,
    isPaginationSimple: false,
    isPaginationRounded: true,
    paginationPosition: "bottom",
    defaultSortDirection: "asc",
    sortIcon: "arrow-up",
    sortIconSize: "is-small",
    currentPage: 1,
    perPage: 20,
    logo: null,
  }),

  mounted() {
    this.obtenerVentas();
    this.obtenerDatos();
  },

  methods: {
    recargar() {
      (this.fechasSeleccionadas = []), (this.filtros = {});
      this.obtenerVentas();
    },

    onImpreso(resultado) {
      this.mostrarTicket = resultado;
    },

    imprimirComprobante(venta) {
      this.ventaSeleccionada = {
        atendio: venta.atendio,
        cliente: venta.cliente,
        fecha: venta.fecha,
        pagado: venta.pagado,
        total: venta.total,
      };

      this.insumosSeleccionados = venta.insumos;
      this.mostrarTicket = true;
    },

    imprimirRide(venta) {
      this.ventaRide = venta;
      this.mostrarRide = true;
    },

    enviarWhatsApp(venta) {
      const telRaw = (venta.cliente_telefono || "").replace(/\D/g, "");
      let tel = telRaw;
      if (tel.startsWith("09") && tel.length === 10) {
        tel = "593" + tel.substring(1);
      }
      const msg =
        `Hola *${venta.cliente || "Cliente"}*, gracias por su visita a *${this.datos.nombre || "nuestro restaurante"}*.\n\n` +
        `*Comprobante Electrónico:* ${venta.factura.numero_serie}\n` +
        `*Total:* $${parseFloat(venta.total).toFixed(2)}\n` +
        `*Estado SRI:* ${venta.factura.estado_sri}\n` +
        `*Clave de Acceso:* ${venta.factura.clave_acceso}`;
      const url = tel
        ? `https://wa.me/${tel}?text=${encodeURIComponent(msg)}`
        : `https://wa.me/?text=${encodeURIComponent(msg)}`;
      window.open(url, "_blank");
    },

    transmitirSri(venta) {
      this.cargando = true;
      HttpService.registrar(
        { venta_id: venta.id, enviar_sri: true },
        "emitir_factura.php"
      ).then((res) => {
        this.cargando = false;
        if (res && res.resultado) {
          this.$buefy.toast.open({
            message: `SRI (${res.estado_sri || "PROCESADO"}): ${res.mensaje_sri || "Comprobante procesado"}`,
            type: res.estado_sri === "AUTORIZADA" ? "is-success" : "is-info",
            duration: 5000,
          });
          this.obtenerVentas();
        } else {
          this.$buefy.toast.open({
            message: res && res.error ? res.error : "Error al transmitir al SRI",
            type: "is-danger",
            duration: 5000,
          });
        }
      });
    },

    descargarXml(factura) {
      this.cargando = true;
      HttpService.registrar(
        { factura_id: factura.id },
        "emitir_factura.php"
      ).then((res) => {
        this.cargando = false;
        if (res && res.resultado && res.factura && res.factura.xml_autorizado) {
          const blob = new Blob([res.factura.xml_autorizado], { type: "application/xml" });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.download = `FACTURA_${factura.numero_serie}.xml`;
          a.click();
          window.URL.revokeObjectURL(url);
        }
      });
    },

    buscarEnFecha() {
      this.filtros = {
        inicio: this.fechasSeleccionadas[0].toISOString().substring(0, 10),
        fin: this.fechasSeleccionadas[1].toISOString().substring(0, 10),
        idUsuario: this.usuarioSeleccionado,
      };
      this.obtenerVentas();
    },

    obtenerVentas() {
      this.cargando = true;
      HttpService.obtenerConDatos(this.filtros, "obtener_ventas.php").then(
        (resultado) => {
          this.ventas = resultado.ventas;
          this.ventasPorUsuario = resultado.ventasPorUsuario;
          this.usuarios = resultado.usuarios;

          let total = 0;
          this.ventas.forEach((venta) => {
            total = parseFloat(total) + parseFloat(venta.total);
          });

          this.totalVentas = total;
          this.cargando = false;
        }
      );
    },

    obtenerDatos() {
      HttpService.obtener("obtener_datos_local.php").then((resultado) => {
        this.datos = resultado;
        this.logo = Utiles.generarUrlImagen(this.datos.logo);
      });
    },
  },
};
</script>
