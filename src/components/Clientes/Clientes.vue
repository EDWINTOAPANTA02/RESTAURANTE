<template>
    <div>
        <br>
        <nav class="level">
            <div class="level-left">
                <div class="level-item">
                    <p class="title is-1 has-text-weight-bold">
                        <b-icon icon="account-multiple" size="is-large" type="is-primary"></b-icon>
                        Clientes
                        <span class="has-text-grey title is-4">{{ clientes.length }} resultados</span>
                    </p>
                </div>
            </div>
            <div class="level-right">
                <p class="level-item">
                    <b-button type="is-success" size="is-large" icon-left="plus" tag="router-link" to="/registrar-cliente">
                        Nuevo cliente
                    </b-button>
                </p>
            </div>
        </nav>

        <b-field>
            <b-input
                v-model="busqueda"
                placeholder="Buscar por nombre, apellido o cédula/RUC..."
                icon="magnify"
                icon-right="close-circle"
                icon-right-clickable
                @icon-right-click="busqueda = ''"
                @input="buscar"
                expanded>
            </b-input>
        </b-field>

        <b-table
            :data="clientes"
            :paginated="true"
            :per-page="15"
            :bordered="true"
            :striped="true"
            :loading="cargando"
            default-sort="nombres"
        >
            <b-table-column field="nombres" label="Nombres" sortable v-slot="props">
                {{ props.row.nombres }} {{ props.row.apellidos }}
            </b-table-column>

            <b-table-column field="tipo_id" label="Tipo" sortable v-slot="props">
                <b-tag :type="props.row.tipo_id === 'RUC' ? 'is-info' : 'is-light'">
                    {{ props.row.tipo_id }}
                </b-tag>
            </b-table-column>

            <b-table-column field="cedula_ruc" label="Cédula / RUC" sortable v-slot="props">
                {{ props.row.cedula_ruc }}
            </b-table-column>

            <b-table-column field="telefono" label="Teléfono" v-slot="props">
                {{ props.row.telefono || '—' }}
            </b-table-column>

            <b-table-column field="correo" label="Correo" v-slot="props">
                {{ props.row.correo || '—' }}
            </b-table-column>

            <b-table-column field="estado" label="Estado" sortable v-slot="props">
                <b-tag :type="props.row.estado === 'ACTIVO' ? 'is-success' : 'is-danger'">
                    {{ props.row.estado }}
                </b-tag>
            </b-table-column>

            <b-table-column label="Acciones" v-slot="props">
                <div class="field is-grouped">
                    <p class="control">
                        <b-button type="is-info" icon-left="pen" size="is-small" @click="editar(props.row.id)">
                            Editar
                        </b-button>
                    </p>
                    <p class="control">
                        <b-button
                            :type="props.row.estado === 'ACTIVO' ? 'is-danger' : 'is-success'"
                            :icon-left="props.row.estado === 'ACTIVO' ? 'account-off' : 'account-check'"
                            size="is-small"
                            @click="cambiarEstado(props.row)">
                            {{ props.row.estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}
                        </b-button>
                    </p>
                </div>
            </b-table-column>

            <template #empty>
                <div class="has-text-centered" style="padding:2rem">
                    <b-icon icon="account-multiple" size="is-large" type="is-light"></b-icon>
                    <p class="has-text-grey">No se encontraron clientes</p>
                </div>
            </template>
        </b-table>

        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
    </div>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
    name: 'Clientes',

    data: () => ({
        clientes: [],
        busqueda: '',
        cargando: false,
        timer: null
    }),

    mounted() {
        this.obtenerClientes()
    },

    methods: {
        buscar() {
            // Debounce: esperar 350ms antes de llamar al servidor
            clearTimeout(this.timer)
            this.timer = setTimeout(() => this.obtenerClientes(), 350)
        },

        obtenerClientes() {
            this.cargando = true
            HttpService.obtenerConDatos({ busqueda: this.busqueda }, 'obtener_clientes.php')
                .then(datos => {
                    this.clientes = datos
                    this.cargando = false
                })
        },

        editar(id) {
            this.$router.push({ name: 'EditarCliente', params: { id } })
        },

        cambiarEstado(cliente) {
            const nuevoEstado = cliente.estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO'
            const accion      = nuevoEstado === 'INACTIVO' ? 'desactivar' : 'activar'

            this.$buefy.dialog.confirm({
                title: `${accion.charAt(0).toUpperCase() + accion.slice(1)} cliente`,
                message: `¿Deseas ${accion} a <b>${cliente.nombres} ${cliente.apellidos}</b>?`,
                confirmText: `Sí, ${accion}`,
                cancelText: 'Cancelar',
                type: nuevoEstado === 'INACTIVO' ? 'is-danger' : 'is-success',
                hasIcon: true,
                onConfirm: () => {
                    HttpService.obtenerConDatos(
                        { id: cliente.id, estado: nuevoEstado },
                        'eliminar_cliente.php'
                    ).then(res => {
                        if (res && res.resultado) {
                            this.$buefy.toast.open({ message: res.mensaje, type: 'is-success' })
                            this.obtenerClientes()
                        }
                    })
                }
            })
        }
    }
}
</script>
