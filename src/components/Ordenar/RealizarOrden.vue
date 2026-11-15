<template>
    <section>
        <div class="level mb-4">
            <div class="level-left">
                <p class="title is-1 has-text-weight-bold mb-0">
                    <b-icon
                        icon="order-bool-ascending-variant"
                        size="is-large"
                        type="is-primary">
                    </b-icon>
                    Realizar orden 
                </p>
            </div>
            <div class="level-right">
                <b-button
                    type="is-warning"
                    size="is-medium"
                    icon-left="shopping"
                    @click="nuevoPedidoParaLlevar"
                >
                    <strong>Pedido Para Llevar / Domicilio</strong>
                </b-button>
            </div>
        </div>

        <div class="columns is-multiline">
            <div class="column" 
            v-for="mesa in mesas" 
            :key="mesa.mesa.idMesa">
                
                <div class="box" :style="Number(mesa.mesa.idMesa) === 0 ? 'border: 2px solid #f59e0b;' : ''">
                    <p class="title is-2 has-text-grey">
                        {{ Number(mesa.mesa.idMesa) === 0 ? 'Para Llevar / Domicilio' : 'Mesa #' + mesa.mesa.idMesa }}
                        <span class="title is-1 has-text-weight-bold is-pulled-right" v-if="mesa.mesa.total">
                            ${{ parseFloat(mesa.mesa.total).toFixed(2) }}
                        </span>
                    </p>
                    <p v-if="mesa.mesa.atiende">
                        <strong>Atiende</strong>: {{ mesa.mesa.atiende }}
                    </p>
                    <p v-if="mesa.mesa.cliente">
                        <strong>Cliente</strong>: {{ mesa.mesa.cliente }}
                    </p>
                    <b-collapse 
                        class="card" 
                        animation="slide" 
                        aria-id="contentIdForA11y3"
                        v-if="mesa.mesa.estado === 'ocupada'">
                        <template #trigger="props">
                            <div
                                class="card-header"
                                role="button"
                                aria-controls="contentIdForA11y3"
                                :aria-expanded="props.open">
                                <p class="card-header-title">
                                    Insumos en la orden
                                </p>
                                <a class="card-header-icon">
                                    <b-icon
                                        :icon="props.open ? 'menu-down' : 'menu-up'">
                                    </b-icon>
                                </a>
                            </div>
                        </template>

                        <div class="card-content">
                            <div class="content">
                                <b-table
                                :data="mesa.insumos"
                                :checked-rows.sync="checkedRows"
                                :is-row-checkable="(row) => row.estado !== 'entregado'"
                                checkable
                                :checkbox-position="checkboxPosition"
                                :checkbox-type="checkboxType">

                                    <b-table-column field="codigo" label="Código" v-slot="props">
                                        {{ props.row.codigo }}
                                    </b-table-column>                                    
                                    
                                    <b-table-column field="nombre" label="Nombre" v-slot="props">
                                        {{ props.row.nombre }}
                                    </b-table-column>                                    
                                    
                                    <b-table-column field="caracteristicas" label="Características" v-slot="props">
                                        {{ props.row.caracteristicas }}
                                    </b-table-column>  

                                    <b-table-column field="cantidad" label="Cantidad" v-slot="props">
                                        {{ props.row.cantidad }} X ${{  parseFloat(props.row.precio).toFixed(2) }}
                                    </b-table-column>                                        
                                    
                                    <b-table-column field="subtotal" label="Subtotal" v-slot="props">
                                        ${{ parseFloat(props.row.cantidad * props.row.precio).toFixed(2) }}
                                    </b-table-column>                                                                
                                    <b-table-column field="estado" label="" v-slot="props">
                                        <b-icon icon="alert" type="is-danger" v-if="props.row.estado ==='pendiente'"></b-icon>
                                        <b-icon icon="check" type="is-success" v-if="props.row.estado ==='entregado'"></b-icon>
                                    </b-table-column>                                                                
                                </b-table>
                            </div>
                        </div>
                    </b-collapse>
                    <br>
                    <div class="has-text-centered">
                        <b-button type="is-primary" icon-left="check" @click="ocuparMesa(mesa)" v-if="mesa.mesa.estado === 'libre'">Ocupar</b-button>  
                        <div class="field is-grouped is-centered" v-if="mesa.mesa.estado === 'ocupada'">
                            <p class="control">
                                <b-button type="is-success" icon-left="cash" @click="cobrar(mesa)">Cobrar</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-info" icon-left="plus" @click="ocuparMesa(mesa)">Agregar  insumos</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-dark" icon-left="printer" @click="imprimirComandaCocina(mesa)">Comanda Cocina</b-button>
                            </p>
                            <p class="control" v-if="Number(mesa.mesa.idMesa) > 0">
                                <b-button type="is-primary is-light" icon-left="swap-horizontal" @click="abrirCambioMesa(mesa)">Cambiar Mesa</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-warning" icon-left="check" v-if="checkedRows.length > 0" @click="marcarInsumosEntregados(mesa)">Marcar entrega</b-button>
                            </p>
                            <p class="control">
                                <b-button type="is-danger" icon-left="close"  @click="cancelarOrden(mesa.mesa.idMesa)">Cancelar</b-button>
                            </p>
                        </div>
                    </div>            
                </div>
            </div>
        </div>
        <b-loading :is-full-page="true" v-model="cargando" :can-cancel="false"></b-loading>
        <ticket @impreso="onImpreso" :venta="this.ventaSeleccionada" :insumos="insumosSeleccionados" :datosLocal="datos" :logo="logo" v-if="mostrarTicket"></ticket>
        <comanda-cocina @impreso="mostrarComanda = false" :orden="ordenComanda" :insumos="insumosComanda" v-if="mostrarComanda"></comanda-cocina>

        <ClienteModal v-if="mostrarClienteModal"
            :mesa="mesaActual"
            @close="mostrarClienteModal = false"
            @confirm="confirmarCobro"/>

    </section>
</template>
<script>
import HttpService from '../../Servicios/HttpService'
import ClienteModal from '../Ordenar/ClienteModal.vue'
import ComandaCocina from '../Ordenar/ComandaCocina.vue'
import Ticket from '../Ventas/Ticket.vue'
import Utiles from '../../Servicios/Utiles'

export default ({
    name: "RealizarOrden",
    components: { Ticket, ClienteModal, ComandaCocina },

    data: () => ({
        datos: {},
        logo: null,
        checkboxPosition: 'left',
        checkboxType: 'is-primary',
        checkedRows: [],
        mesas: [],
        cargando: false,
        mostrarTicket: false,
        ventaSeleccionada: {},
        insumosSeleccionados: [],
        mostrarComanda: false,
        ordenComanda: {},
        insumosComanda: [],
        mostrarClienteModal: false,
        clienteSeleccionado: null,
        tipoFactura: 'CONSUMIDOR_FINAL',
        mesaActual: null
    }),

    mounted(){
        this.crearMesas()
        this.obtenerDatos()
    },

    methods:{
        cancelarOrden(id){
            this.$buefy.dialog.confirm({
                title: 'Cancelar mesa ' + id,
                message: '¿Seguro deseas  cancelar la órden?',
                confirmText: 'Sí, cancelar',
                cancelText: 'No',
                type: 'is-danger',
                hasIcon: true,
                onConfirm: () => {
                    this.cargando = true
                    HttpService.eliminar("cancelar_mesa.php", id)
                    .then(resultado => {
                        if(resultado){
                            this.$buefy.toast.open({
                                message: "Orden de la mesa " + id + " cancelada",
                                type: "is-success"
                            })
                            this.crearMesas()
                            this.cargando = false
                        }
                    })
                    
                }
            })
        },

        obtenerDatos() {
            HttpService.obtener("obtener_datos_local.php").then((resultado) => {
                this.datos = resultado;
                this.logo = Utiles.generarUrlImagen(this.datos.logo)
            });
        },

        onImpreso(resultado){
            this.mostrarTicket = resultado
        },

        imprimirComandaCocina(mesa){
            const hoy = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const fechaHora = hoy.getFullYear() + '-' + pad(hoy.getMonth() + 1) + '-' + pad(hoy.getDate()) + ' ' + pad(hoy.getHours()) + ':' + pad(hoy.getMinutes()) + ':' + pad(hoy.getSeconds());

            // Si hay platillos pendientes, imprimir los pendientes; si todos están entregados, reimprimir todos
            const pendientes = (mesa.insumos || []).filter(i => i.estado !== 'entregado');
            this.insumosComanda = pendientes.length > 0 ? pendientes : (mesa.insumos || []);
            this.ordenComanda = {
                idMesa: mesa.mesa.idMesa,
                fecha: mesa.mesa.fecha || fechaHora,
                atiende: mesa.mesa.atiende || localStorage.getItem('nombreUsuario'),
                cliente: mesa.mesa.cliente || 'MOSTRADOR',
                esAdicional: false
            };
            this.mostrarComanda = true;
        },

        imprimirComprobante(venta){
            let hoy = new Date();

            let fecha = hoy.getFullYear()+'-'+(hoy.getMonth()+1)+'-'+hoy.getDate();

            let hora = hoy.getHours() + ":" + hoy.getMinutes() + ":" + hoy.getSeconds();

            let fechaVenta = fecha+' '+hora
            this.ventaSeleccionada = {
                atendio: venta.atiende,
                cliente: venta.cliente,
                fecha: fechaVenta,
                pagado: venta.pagado,
                total: venta.total
            }

            this.insumosSeleccionados = venta.insumos
            this.mostrarTicket = true
        },

        marcarInsumosEntregados(mesa){
            this.cargando = true
            let insumos = mesa.insumos
            let marcados = this.checkedRows

            insumos.forEach(insumo => {
                marcados.forEach(marca => {
                    if(insumo.id === marca.id)
                        insumo.estado = "entregado"
                })
            })

            let payload = {
                id: mesa.mesa.idMesa,
                insumos: insumos,
                total: mesa.mesa.total,
                atiende: mesa.mesa.atiende,
                idUsuario: mesa.mesa.idUsuario,
                cliente: mesa.mesa.cliente
            }

            HttpService.registrar(payload, "editar_mesa.php")
            .then(resultado => {
                if(resultado){
                    this.$buefy.toast.open({
                        message: 'Insumos marcados como entregados',
                        type: 'is-success'
                    })
                    this.crearMesas()
                    this.cargando = false
                }
                this.checkedRows = []
            })

        },

        cobrar(mesa){
            // Calcular el total de la mesa sumando los insumos (ya que el backend retorna total = 0 inicial)
            let total = 0
            if(mesa.insumos && mesa.insumos.length > 0){
                mesa.insumos.forEach(insumo => {
                    total += (insumo.cantidad * insumo.precio)
                })
            }
            mesa.mesa.total = total

            // Open client selection modal before payment
            this.clienteSeleccionado = null
            this.tipoFactura = 'CONSUMIDOR_FINAL'
            this.mesaActual = mesa
            this.mostrarClienteModal = true
        },
        // Handler called from ClienteModal when user confirms
        confirmarCobro(payloadCliente){
            const mesa = this.mesaActual
            const totalCobrado = payloadCliente.total_pagar !== undefined ? payloadCliente.total_pagar : mesa.mesa.total
            const insumosCobrar = payloadCliente.insumos_cobrar || mesa.insumos
            const payload = {
                idMesa: mesa.mesa.idMesa,
                cliente_id: payloadCliente.cliente_id,
                cliente: payloadCliente.cliente_nombre || mesa.mesa.cliente,
                tipo_factura: payloadCliente.tipo_factura,
                metodo_pago: payloadCliente.metodo_pago || 'EFECTIVO',
                total: totalCobrado,
                pagado: payloadCliente.pago,
                idUsuario: mesa.mesa.idUsuario,
                insumos: insumosCobrar,
                insumos_restantes: payloadCliente.insumos_restantes || [],
                cobro_parcial: payloadCliente.cobro_parcial || false,
                atiende: mesa.mesa.atiende
            }
            this.cargando = true
            HttpService.registrar(payload, "registrar_venta.php")
                .then(registrado => {
                    if(registrado){
                        const cambio = parseFloat(payloadCliente.pago - totalCobrado).toFixed(2)
                        this.$buefy.dialog.alert({
                            title: payload.cobro_parcial ? 'Cobro parcial registrado' : 'Venta registrada',
                            message: `Gracias por su compra (${payload.metodo_pago}), su cambio <b>$${cambio}</b>`,
                            confirmText: 'OK'
                        })
                        this.imprimirComprobante(payload)
                        this.crearMesas()
                    }
                    this.cargando = false
                })
        },

        nuevoPedidoParaLlevar(){
            const existente = this.mesas.find(m => Number(m.mesa.idMesa) === 0)
            if (existente) {
                this.ocuparMesa(existente)
                return
            }
            this.$router.push({
                name: "Ordenar",
                params: { id: 0, insumosEnLista: [], cliente: "PARA LLEVAR" },
            })
        },

        abrirCambioMesa(mesa){
            const libres = this.mesas
                .filter(m => m.mesa.estado === 'libre' && Number(m.mesa.idMesa) > 0)
                .map(m => m.mesa.idMesa)

            if (libres.length === 0) {
                this.$buefy.toast.open({
                    message: 'No hay mesas libres disponibles para realizar el cambio',
                    type: 'is-warning'
                })
                return
            }

            this.$buefy.dialog.prompt({
                title: `Cambiar Mesa #${mesa.mesa.idMesa}`,
                message: `Mesas libres disponibles: <b>${libres.join(', ')}</b>.<br>Escribe el número de la nueva mesa destino:`,
                inputAttrs: {
                    type: 'number',
                    placeholder: `Ej. ${libres[0]}`,
                    value: String(libres[0]),
                    min: 1
                },
                trapFocus: true,
                confirmText: 'Cambiar de Mesa',
                cancelText: 'Cancelar',
                onConfirm: (valor) => {
                    const destino = parseInt(valor, 10)
                    if (!destino || !libres.includes(destino)) {
                        this.$buefy.toast.open({
                            message: 'Selecciona un número de mesa libre válido',
                            type: 'is-danger'
                        })
                        return
                    }
                    this.cargando = true
                    HttpService.registrar({
                        mesaOrigen: mesa.mesa.idMesa,
                        mesaDestino: destino
                    }, "cambiar_mesa.php").then(res => {
                        this.cargando = false
                        if (res && res.resultado) {
                            this.$buefy.toast.open({
                                message: `Pedido trasladado de la Mesa #${mesa.mesa.idMesa} a la Mesa #${destino}`,
                                type: 'is-success'
                            })
                            this.crearMesas()
                        } else {
                            this.$buefy.toast.open({
                                message: (res && res.error) ? res.error : 'No se pudo cambiar la mesa',
                                type: 'is-danger'
                            })
                        }
                    })
                }
            })
        },

        crearMesas(){
            this.cargando = true
            HttpService.obtener("obtener_mesas.php")
            .then(resultado => {
                this.mesas = resultado
                this.cargando = false
            })
        },

        ocuparMesa(mesa){
            this.$router.push({
                name: "Ordenar",
                params: { id: mesa.mesa.idMesa, insumosEnLista: mesa.insumos, cliente: mesa.mesa.cliente },
            })
        }
    }
})
</script>
