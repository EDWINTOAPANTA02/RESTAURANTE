<template>
    <b-modal :active="true" has-modal-card @close="$emit('close')">
        <div class="modal-card" style="width: 540px; max-width: 95vw;">
            <header class="modal-card-head">
                <p class="modal-card-title">
                    {{ Number(mesa.mesa.idMesa) === 0 ? 'Cobrar Pedido Para Llevar / Domicilio' : 'Cobrar Mesa #' + mesa.mesa.idMesa }}
                </p>
                <button type="button" class="delete" @click="$emit('close')"/>
            </header>

            <section class="modal-card-body">
                <!-- División de Cuenta (Opcional) -->
                <div class="box has-background-white-ter p-3 mb-4" v-if="mesa.insumos && mesa.insumos.length > 1">
                    <b-checkbox v-model="dividirCuenta" type="is-info" @input="onToggleDividir">
                        <strong>Dividir cuenta (Cobrar solo platillos seleccionados)</strong>
                    </b-checkbox>
                    <div v-if="dividirCuenta" class="mt-2">
                        <p class="is-size-7 has-text-grey mb-2">Marca los ítems que va a pagar este cliente ahora:</p>
                        <div v-for="(item, idx) in itemsSeleccionables" :key="idx" class="field is-grouped is-align-items-center mb-1">
                            <b-checkbox v-model="item.seleccionado" type="is-success">
                                {{ item.nombre }} (${{ parseFloat(item.precio).toFixed(2) }} c/u)
                            </b-checkbox>
                            <b-numberinput
                                v-if="item.seleccionado && item.maxCantidad > 1"
                                v-model="item.cantidadPagar"
                                :min="1"
                                :max="item.maxCantidad"
                                size="is-small"
                                controls-position="compact"
                                style="width: 110px; margin-left: auto;"
                            />
                        </div>
                    </div>
                </div>

                <h3 class="title is-4 mb-3">
                    Total a pagar: <span class="has-text-success">${{ totalAPagar.toFixed(2) }}</span>
                </h3>

                <!-- Método de Pago -->
                <b-field label="Método de Pago">
                    <div class="buttons has-addons">
                        <b-button
                            :type="metodoPago === 'EFECTIVO' ? 'is-success is-selected' : ''"
                            icon-left="cash"
                            @click="seleccionarMetodo('EFECTIVO')">
                            Efectivo
                        </b-button>
                        <b-button
                            :type="metodoPago === 'TRANSFERENCIA' ? 'is-info is-selected' : ''"
                            icon-left="bank-transfer"
                            @click="seleccionarMetodo('TRANSFERENCIA')">
                            Transferencia / Deuna
                        </b-button>
                        <b-button
                            :type="metodoPago === 'TARJETA' ? 'is-primary is-selected' : ''"
                            icon-left="credit-card"
                            @click="seleccionarMetodo('TARJETA')">
                            Tarjeta
                        </b-button>
                    </div>
                </b-field>

                <b-field label="Cantidad con la que paga">
                    <b-input type="number" step="0.01" v-model="pago" placeholder="Escribe la cantidad" min="0.01"></b-input>
                </b-field>
                <p v-if="pago && parseFloat(pago) >= totalAPagar" class="has-text-weight-bold has-text-info mb-2">
                    Cambio a entregar: ${{ (parseFloat(pago) - totalAPagar).toFixed(2) }}
                </p>

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
                            placeholder="Escribe la cédula, RUC o nombre...">
                            
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
                        <button type="button" class="delete" @click="limpiarSeleccion"></button>
                        <strong>Cliente Seleccionado:</strong><br>
                        {{ selectedCliente.nombres }} {{ selectedCliente.apellidos }}<br>
                        {{ selectedCliente.cedula_ruc }}<br>
                        {{ selectedCliente.correo }}
                    </div>

                    <div v-else-if="busqueda && busqueda.trim().length >= 5" class="box has-background-light mt-2">
                        <p class="has-text-weight-bold is-size-6 mb-2">
                            Cliente nuevo ({{ busqueda.trim() }}) — Regístralo aquí mismo:
                        </p>
                        <div class="columns is-multiline is-mobile">
                            <div class="column is-6">
                                <b-field label="Nombres *">
                                    <b-input v-model="nuevoCliente.nombres" placeholder="Ej. Juan"></b-input>
                                </b-field>
                            </div>
                            <div class="column is-6">
                                <b-field label="Apellidos *">
                                    <b-input v-model="nuevoCliente.apellidos" placeholder="Ej. Pérez"></b-input>
                                </b-field>
                            </div>
                            <div class="column is-6">
                                <b-field label="Teléfono">
                                    <b-input v-model="nuevoCliente.telefono" placeholder="0999999999"></b-input>
                                </b-field>
                            </div>
                            <div class="column is-6">
                                <b-field label="Correo">
                                    <b-input type="email" v-model="nuevoCliente.correo" placeholder="cliente@correo.com"></b-input>
                                </b-field>
                            </div>
                            <div class="column is-12">
                                <b-field label="Dirección">
                                    <b-input v-model="nuevoCliente.direccion" placeholder="Dirección del cliente"></b-input>
                                </b-field>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <footer class="modal-card-foot">
                <b-button label="Cancelar" @click="$emit('close')" />
                <b-button label="Confirmar Pago" type="is-success" :loading="guardando" @click="confirmar" />
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
            metodoPago: 'EFECTIVO',
            dividirCuenta: false,
            itemsSeleccionables: [],
            tipoFactura: 'CONSUMIDOR_FINAL',
            busqueda: '',
            clientes: [],
            selectedCliente: null,
            timer: null,
            guardando: false,
            nuevoCliente: {
                nombres: '',
                apellidos: '',
                telefono: '',
                correo: '',
                direccion: ''
            }
        }
    },
    computed: {
        totalAPagar() {
            if (!this.dividirCuenta) {
                return parseFloat(this.mesa.mesa.total || 0);
            }
            return this.itemsSeleccionables
                .filter(i => i.seleccionado)
                .reduce((acc, i) => acc + (parseFloat(i.precio) * parseInt(i.cantidadPagar || 1, 10)), 0);
        }
    },
    mounted() {
        this.inicializarItems();
    },
    methods: {
        inicializarItems() {
            const lista = this.mesa.insumos || [];
            this.itemsSeleccionables = lista.map(ins => ({
                ...ins,
                maxCantidad: parseInt(ins.cantidad, 10),
                cantidadPagar: parseInt(ins.cantidad, 10),
                seleccionado: true
            }));
        },
        onToggleDividir(val) {
            if (val) {
                this.inicializarItems();
            }
            if (this.metodoPago !== 'EFECTIVO') {
                this.pago = this.totalAPagar.toFixed(2);
            }
        },
        seleccionarMetodo(metodo) {
            this.metodoPago = metodo;
            if (metodo === 'TRANSFERENCIA' || metodo === 'TARJETA') {
                this.pago = this.totalAPagar.toFixed(2);
            }
        },
        buscarClientes(texto) {
            this.selectedCliente = null;
            if (this.timer) clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                if (!texto || texto.length < 2) {
                    this.clientes = [];
                    return;
                }
                HttpService.registrar({ busqueda: texto }, "obtener_clientes.php")
                    .then(res => {
                        this.clientes = Array.isArray(res) ? res : [];
                        const exacto = this.clientes.find(c => c.cedula_ruc === texto.trim());
                        if (exacto) {
                            this.selectedCliente = exacto;
                        }
                    });
            }, 250);
        },
        seleccionarCliente(option) {
            if (option) {
                this.selectedCliente = option;
            }
        },
        limpiarSeleccion() {
            this.selectedCliente = null;
            this.busqueda = '';
        },
        async confirmar() {
            if (this.totalAPagar <= 0) {
                this.$buefy.toast.open({
                    message: 'Selecciona al menos un platillo para cobrar',
                    type: 'is-warning'
                })
                return
            }

            if (!this.pago || parseFloat(this.pago) < this.totalAPagar) {
                this.$buefy.toast.open({
                    message: 'Escribe una cantidad igual o mayor al total a pagar',
                    type: 'is-warning'
                })
                return
            }

            if (this.tipoFactura === 'FACTURA' && !this.selectedCliente) {
                const cedulaRuc = (this.busqueda || '').trim();
                if (!cedulaRuc || !this.nuevoCliente.nombres.trim() || !this.nuevoCliente.apellidos.trim()) {
                    this.$buefy.toast.open({
                        message: 'Selecciona un cliente de la lista o completa Nombres y Apellidos para registrarlo',
                        type: 'is-danger'
                    })
                    return
                }

                this.guardando = true;
                const payloadNuevo = {
                    cedula_ruc: cedulaRuc,
                    tipo_id: cedulaRuc.length === 13 ? 'RUC' : 'CEDULA',
                    nombres: this.nuevoCliente.nombres.trim(),
                    apellidos: this.nuevoCliente.apellidos.trim(),
                    telefono: this.nuevoCliente.telefono.trim(),
                    correo: this.nuevoCliente.correo.trim(),
                    direccion: this.nuevoCliente.direccion.trim()
                };

                const resCli = await HttpService.registrar(payloadNuevo, "registrar_cliente.php");
                this.guardando = false;

                if (!resCli || !resCli.resultado || !resCli.id) {
                    this.$buefy.toast.open({
                        message: (resCli && resCli.error) ? resCli.error : 'No se pudo registrar el cliente',
                        type: 'is-danger'
                    })
                    return
                }

                this.selectedCliente = resCli.cliente || {
                    id: resCli.id,
                    nombres: payloadNuevo.nombres,
                    apellidos: payloadNuevo.apellidos
                };
            }

            const nombreCliente = this.selectedCliente
                ? `${this.selectedCliente.nombres} ${this.selectedCliente.apellidos}`
                : (this.mesa.mesa.cliente || 'CONSUMIDOR FINAL');

            let insumosCobrar = this.mesa.insumos;
            let insumosRestantes = [];
            let cobroParcial = false;

            if (this.dividirCuenta) {
                insumosCobrar = [];
                this.itemsSeleccionables.forEach(item => {
                    if (item.seleccionado) {
                        insumosCobrar.push({
                            id: item.id,
                            codigo: item.codigo,
                            nombre: item.nombre,
                            precio: item.precio,
                            cantidad: item.cantidadPagar,
                            caracteristicas: item.caracteristicas || '',
                            estado: item.estado || 'entregado'
                        });
                        const rest = item.maxCantidad - item.cantidadPagar;
                        if (rest > 0) {
                            insumosRestantes.push({
                                ...item,
                                cantidad: rest
                            });
                        }
                    } else {
                        insumosRestantes.push({
                            ...item,
                            cantidad: item.maxCantidad
                        });
                    }
                });
                cobroParcial = insumosRestantes.length > 0;
            }

            this.$emit('confirm', {
                pago: this.pago,
                total_pagar: this.totalAPagar,
                metodo_pago: this.metodoPago,
                tipo_factura: this.tipoFactura,
                cliente_id: this.selectedCliente ? this.selectedCliente.id : null,
                cliente_nombre: nombreCliente,
                insumos_cobrar: insumosCobrar,
                insumos_restantes: insumosRestantes,
                cobro_parcial: cobroParcial
            })
            this.$emit('close')
        }
    }
}
</script>
