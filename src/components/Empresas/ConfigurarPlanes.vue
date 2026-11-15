<template>
    <section>
        <div class="columns">
            <div class="column">
                <h1 class="is-size-3">Configurar Precios de Planes</h1>
            </div>
        </div>
        <div class="box">
            <div v-if="planes.length === 0 && !cargando" class="has-text-centered py-5">
                <b-icon icon="information" size="is-large" type="is-grey-light"></b-icon>
                <p class="has-text-grey">No se encontraron planes configurados en la base de datos.</p>
                <b-button type="is-text" @click="obtenerPlanes">Reintentar</b-button>
            </div>
            <b-table :data="planes" :loading="cargando" v-else>
                <b-table-column field="nombre_plan" label="Nombre del Plan" v-slot="props">
                    {{ props.row.nombre_plan }}
                </b-table-column>
                <b-table-column field="precio" label="Precio ($)" v-slot="props">
                    <b-input type="number" step="0.01" v-model="props.row.precio"></b-input>
                </b-table-column>
                <b-table-column field="duracion_meses" label="Duración (meses)" v-slot="props">
                    <b-input type="number" v-model="props.row.duracion_meses"></b-input>
                </b-table-column>
                <b-table-column field="descripcion" label="Descripción" v-slot="props">
                    {{ props.row.descripcion }}
                </b-table-column>
                <b-table-column label="Acciones" v-slot="props">
                    <b-button type="is-primary" icon-left="check" @click="guardarCambios(props.row)">
                        Actualizar
                    </b-button>
                </b-table-column>
            </b-table>
        </div>
    </section>
</template>

<script>
import HttpService from "../../Servicios/HttpService";

export default {
    name: "ConfigurarPlanes",
    data: () => ({
        planes: [],
        cargando: false,
    }),
    mounted() {
        this.obtenerPlanes();
    },
    methods: {
        obtenerPlanes() {
            this.cargando = true;
            HttpService.obtener("obtener_planes.php").then((planes) => {
                console.log("Planes obtenidos:", planes);
                this.planes = planes;
                this.cargando = false;
            });
        },
        guardarCambios(plan) {
            this.cargando = true;
            HttpService.registrar(plan, "actualizar_precio_plan.php").then((res) => {
                this.cargando = false;
                if (res.resultado) {
                    this.$buefy.toast.open({
                        message: "Precio actualizado correctamente",
                        type: "is-success",
                    });
                } else {
                    this.$buefy.toast.open({
                        message: "Error al actualizar precio",
                        type: "is-danger",
                    });
                }
            });
        },
    },
};
</script>
