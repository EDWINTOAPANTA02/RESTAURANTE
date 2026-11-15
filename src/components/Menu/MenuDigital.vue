<template>
  <section class="menu-publico">
    <div class="hero is-primary is-small mb-5">
      <div class="hero-body has-text-centered">
        <img v-if="logoUrl" :src="logoUrl" alt="Logo" style="max-height: 75px; border-radius: 12px; margin-bottom: 8px;" />
        <h1 class="title is-3 has-text-white mb-1">{{ negocio.nombre || negocio.razon_social || 'Menú Digital' }}</h1>
        <p class="subtitle is-6 has-text-white-bis mt-1" v-if="mesa">
          <span class="tag is-warning is-medium"><strong>Mesa #{{ mesa }}</strong></span>
        </p>
        <p class="is-size-7 has-text-white-ter" v-if="negocio.direccion || negocio.telefono">
          {{ negocio.direccion }} <span v-if="negocio.telefono">• Tel: {{ negocio.telefono }}</span>
        </p>
      </div>
    </div>

    <div class="container px-3 pb-6">
      <!-- Filtro rápido por categorías -->
      <div class="buttons is-centered mb-4">
        <b-button
          :type="categoriaActiva === '' ? 'is-primary' : 'is-light'"
          size="is-small"
          rounded
          @click="categoriaActiva = ''"
        >
          Todos ({{ insumos.length }})
        </b-button>
        <b-button
          v-for="cat in categoriasConInsumos"
          :key="cat"
          :type="categoriaActiva === cat ? 'is-primary' : 'is-light'"
          size="is-small"
          rounded
          @click="categoriaActiva = cat"
        >
          {{ cat }}
        </b-button>
      </div>

      <!-- Buscador -->
      <b-field class="mb-4">
        <b-input
          v-model="busqueda"
          placeholder="Buscar platillo o bebida..."
          icon="magnify"
          rounded
        ></b-input>
      </b-field>

      <!-- Lista de platillos y bebidas con foto referencial -->
      <div class="columns is-multiline">
        <div
          class="column is-6-tablet is-4-desktop"
          v-for="item in insumosFiltrados"
          :key="item.id"
        >
          <div class="box p-0" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: 14px;">
            <div>
              <!-- Imagen Referencial del Platillo / Bebida -->
              <div v-if="item.imagen" style="width: 100%; height: 185px; overflow: hidden; background: #f8fafc;">
                <img
                  :src="obtenerUrlFoto(item.imagen)"
                  :alt="item.nombre"
                  style="width: 100%; height: 100%; object-fit: cover; display: block;"
                />
              </div>

              <div class="p-4">
                <div class="is-flex is-justify-content-space-between is-align-items-flex-start">
                  <span class="tag is-info is-light is-size-7 mb-2">{{ item.categoria_nombre }}</span>
                  <span class="title is-4 has-text-primary mb-0">${{ parseFloat(item.precio).toFixed(2) }}</span>
                </div>
                <h3 class="title is-5 mb-1">{{ item.nombre }}</h3>
                <p class="has-text-grey is-size-6 mb-2">{{ item.descripcion }}</p>
              </div>
            </div>

            <div class="px-4 pb-4 is-flex is-justify-content-space-between is-align-items-center">
              <span class="tag is-light is-size-7">
                <b-icon :icon="item.tipo === 'BEBIDA' ? 'cup' : 'food'" size="is-small" class="mr-1"></b-icon>
                {{ item.tipo }}
              </span>
              <span class="tag is-warning is-light" v-if="item.stock !== null && item.stock !== undefined && item.stock !== ''">
                Disponibles: {{ item.stock }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!cargando && insumosFiltrados.length === 0" class="notification is-light has-text-centered">
        No se encontraron platillos disponibles en esta categoría.
      </div>
    </div>

    <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
  </section>
</template>

<script>
import HttpService from "../../Servicios/HttpService";
import Utiles from "../../Servicios/Utiles";

export default {
  name: "MenuDigital",
  data: () => ({
    cargando: false,
    negocio: {},
    insumos: [],
    categoriaActiva: "",
    busqueda: "",
    logoUrl: null,
  }),
  computed: {
    empresaId() {
      return this.$route.params.empresaId;
    },
    mesa() {
      return this.$route.query.mesa || null;
    },
    categoriasConInsumos() {
      const set = new Set();
      this.insumos.forEach((i) => {
        if (i.categoria_nombre) set.add(i.categoria_nombre);
      });
      return Array.from(set);
    },
    insumosFiltrados() {
      return this.insumos.filter((i) => {
        const coincideCat = !this.categoriaActiva || i.categoria_nombre === this.categoriaActiva;
        const q = this.busqueda.trim().toLowerCase();
        const coincideTexto =
          !q ||
          (i.nombre && i.nombre.toLowerCase().includes(q)) ||
          (i.descripcion && i.descripcion.toLowerCase().includes(q));
        return coincideCat && coincideTexto;
      });
    },
  },
  mounted() {
    this.cargarMenu();
  },
  methods: {
    obtenerUrlFoto(ruta) {
      return Utiles.generarUrlImagen(ruta);
    },
    cargarMenu() {
      this.cargando = true;
      HttpService.obtener(`publico/menu.php?empresa=${this.empresaId}`).then((res) => {
        this.cargando = false;
        if (res && res.resultado) {
          this.negocio = res.negocio || {};
          this.insumos = res.insumos || [];
          if (this.negocio.logo) {
            this.logoUrl = Utiles.generarUrlImagen(this.negocio.logo);
          }
        }
      });
    },
  },
};
</script>
