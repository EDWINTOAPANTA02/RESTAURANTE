<template>
    <b-modal :active="true" has-modal-card @close="$emit('close')">
        <div class="modal-card" style="width: auto">
            <header class="modal-card-head">
                <p class="modal-card-title">Cobrar Mesa #{{ mesa.mesa.idMesa }}</p>
                <button type="button" class="delete" @click="$emit('close')"/>
            </header>

            <section class="modal-card-body">
                <h3 class="title is-4">Total a pagar: ${{ mesa.mesa.total }}</h3>
                
                <b-field label="Cantidad con la que paga">
                    <b-input type="number" step="0.01" v-model="pago" placeholder="Escribe la cantidad" min="1"></b-input>
                </b-field>

                <hr>

                <h3 class="title is-5">Datos para Facturación</h3>

                <b-field>
                    <b-radio v-model="tipoFactura" native-value="CONSUMIDOR_FINAL" type="is-success">
                        Consumidor Final
                    </b-radio>
                    <b-radio v-model="tipoFactura" native-value="FACTURA" type="is-info">
                        Factura (Datos del cliente)
                    </b-radio>
                </b-field>

                <div v-if="tipoFactura === 'FACTURA'">
                    <b-field label="Buscar Cliente por Cédula/RUC o Nombre">
                        <b-autocomplete
                            v-model="busqueda"
                            :data="clientes"
                            autocomplete="off"
                            field="cedula_ruc"
                            @typing="buscarClientes"
                            @select="seleccionarCliente"
                            placeholder="Buscar cliente...">
                            
                            <template slot-scope="props">
                                <div class="media">
                                    <div class="media-content">
                                        {{ props.option.cedula_ruc }} - {{ props.option.nombres }} {{ props.option.apellidos }}
                                    </div>
                                </div>
                            </template>
                        </b-autocomplete>
                    </b-field>

                    <div v-if="selectedCliente" class="notification is-light is-info mt-2">
                        <strong>Cliente Seleccionado:</strong><br>
                        {{ selectedCliente.nombres }} {{ selectedCliente.apellidos }}<br>
                        {{ selectedCliente.cedula_ruc }}<br>
                        {{ selectedCliente.correo }}
                    </div>
                </div>

            </section>

            <footer class="modal-card-foot">
                <b-button label="Cancelar" @click="$emit('close')" />
                <b-button label="Confirmar Pago" type="is-success" @click="confirmar" />
            </footer>
        </div>
    </b-modal>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
    name: 'ClienteModal',
    props: {
        mesa: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            pago: "",
            tipoFactura: 'CONSUMIDOR_FINAL',
            busqueda: '',
            clientes: [],
            selectedCliente: null,
            timer: null
        }
    },
    methods: {
        buscarClientes(texto) {
            if(this.timer) clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                if (texto.length < 2) {
                    this.clientes = [];
                    return;
                }
                HttpService.registrar({busqueda: texto}, "obtener_clientes.php")
                    .then(res => {
                        this.clientes = res || [];
                    });
            }, 300);
        },
        seleccionarCliente(option) {
            this.selectedCliente = option;
        },
        confirmar() {
            if (!this.pago || parseFloat(this.pago) < parseFloat(this.mesa.mesa.total)) {
                this.$buefy.toast.open({
                    message: 'Escribe una cantidad correcta',
                    type: 'is-warning'
                })
                return
            }

            if (this.tipoFactura === 'FACTURA' && !this.selectedCliente) {
                this.$buefy.toast.open({
                    message: 'Debes seleccionar un cliente para generar factura',
                    type: 'is-danger'
                })
                return
            }

            this.$emit('confirm', {
                pago: this.pago,
                tipo_factura: this.tipoFactura,
                cliente_id: this.selectedCliente ? this.selectedCliente.id : null
            })
            this.$emit('close')
        }
    }
}
</script>
