<template>
  <section class="section">
    <!-- Encabezado y Filtro de Fechas para Balance de Gastos vs Ventas -->
    <div class="level mb-4">
      <div class="level-left">
        <div>
          <h3 class="title is-3 mb-1">
            <b-icon icon="warehouse" type="is-primary"></b-icon>
            Inventario, Compras y Control de Gastos
          </h3>
          <p class="subtitle is-6 has-text-grey">
            Controla tus gaseosas, pescados/proteínas en bodega, registra compras y conoce tu ganancia real.
          </p>
        </div>
      </div>
      <div class="level-right">
        <div class="field is-grouped">
          <p class="control">
            <input class="input is-small" type="date" v-model="filtro.inicio" @change="cargarDatos">
          </p>
          <p class="control">
            <span class="button is-static is-small">al</span>
          </p>
          <p class="control">
            <input class="input is-small" type="date" v-model="filtro.fin" @change="cargarDatos">
          </p>
          <p class="control">
            <b-button type="is-primary" size="is-small" icon-left="refresh" @click="cargarDatos">
              Actualizar
            </b-button>
          </p>
        </div>
      </div>
    </div>

    <!-- Tarjetas de Balance Financiero y Estado de Bodega -->
    <div class="columns is-multiline mb-4">
      <div class="column is-3">
        <div class="box has-background-success-light" style="border-left: 5px solid #48c774;">
          <p class="heading has-text-success-dark has-text-weight-bold">VENTAS DEL PERIODO</p>
          <p class="title is-4 has-text-success-dark">${{ Number(finanzas.ventas_periodo || 0).toFixed(2) }}</p>
          <p class="is-size-7 has-text-grey">Ingresos cobrados en mesas/llevar</p>
        </div>
      </div>
      <div class="column is-3">
        <div class="box has-background-danger-light" style="border-left: 5px solid #f14668;">
          <p class="heading has-text-danger-dark has-text-weight-bold">GASTOS Y COMPRAS (MERCADERÍA)</p>
          <p class="title is-4 has-text-danger-dark">${{ Number(finanzas.compras_periodo || 0).toFixed(2) }}</p>
          <p class="is-size-7 has-text-grey">Compras de pescado, gaseosas, gas, etc.</p>
        </div>
      </div>
      <div class="column is-3">
        <div class="box" :class="finanzas.utilidad_neta >= 0 ? 'has-background-info-light' : 'has-background-warning-light'" style="border-left: 5px solid #3298dc;">
          <p class="heading has-text-info-dark has-text-weight-bold">GANANCIA / FLUJO NETO</p>
          <p class="title is-4" :class="finanzas.utilidad_neta >= 0 ? 'has-text-info-dark' : 'has-text-danger-dark'">
            ${{ Number(finanzas.utilidad_neta || 0).toFixed(2) }}
          </p>
          <p class="is-size-7 has-text-grey">Ventas menos Compras registradas</p>
        </div>
      </div>
      <div class="column is-3">
        <div class="box has-background-light" style="border-left: 5px solid #7957d5;">
          <p class="heading has-text-primary has-text-weight-bold">VALOR EN BODEGA / ALERTAS</p>
          <p class="title is-4 has-text-dark">
            ${{ Number(finanzas.valor_bodega || 0).toFixed(2) }}
            <span v-if="finanzas.alertas_bajo_stock > 0" class="tag is-danger is-rounded ml-2">
              {{ finanzas.alertas_bajo_stock }} por agotarse
            </span>
          </p>
          <p class="is-size-7 has-text-grey">Capital actual en stock disponible</p>
        </div>
      </div>
    </div>

    <!-- Barra de Acciones Rápidas -->
    <div class="box mb-4">
      <div class="buttons">
        <b-button type="is-primary" icon-left="plus-box" @click="abrirModalNuevoItem">
          Nuevo Producto en Bodega (Ej. Pescado, Gaseosa)
        </b-button>
        <b-button type="is-success" icon-left="cart-plus" @click="abrirModalCompra(null)">
          Registrar Compra / Abastecimiento
        </b-button>
        <b-button type="is-warning" icon-left="cash-minus" @click="modalGastoGeneral = true">
          Registrar Gasto General (Gas, Hielo, Verduras...)
        </b-button>
        <b-button type="is-info" outlined icon-left="bottle-soda-classic" @click="importarBebidasMenu">
          Importar Bebidas del Menú a Bodega (1 Clic)
        </b-button>
      </div>
    </div>

    <!-- Pestañas Principales -->
    <b-tabs v-model="tabActiva" type="is-boxed" class="box">
      <!-- TAB 1: EXISTENCIAS EN BODEGA -->
      <b-tab-item label="Existencias en Bodega (Stock)" icon="package-variant-closed">
        <div v-if="items.length === 0" class="notification is-light has-text-centered my-4">
          <p class="is-size-5 mb-2">Aún no tienes productos registrados en la Bodega de Inventario.</p>
          <p class="has-text-grey mb-3">
            Haz clic en <strong>"Nuevo Producto en Bodega"</strong> para agregar por ejemplo <em>"Pescado Entero"</em> o en <strong>"Importar Bebidas del Menú"</strong> para traer todas tus gaseosas.
          </p>
        </div>

        <b-table v-else :data="items" striped hoverable mobile-cards>
          <b-table-column field="categoria_inv" label="Categoría" width="140" v-slot="props">
            <span class="tag is-light is-info has-text-weight-bold">{{ props.row.categoria_inv }}</span>
          </b-table-column>

          <b-table-column field="nombre" label="Producto / Materia Prima" v-slot="props">
            <strong>{{ props.row.nombre }}</strong>
            <div class="mt-1">
              <span
                v-for="rec in obtenerPlatosVinculados(props.row.id)"
                :key="rec.id"
                class="tag is-primary is-light is-rounded mr-1"
                title="Al vender este plato en POS se descuenta automáticamente de este stock"
              >
                <b-icon icon="link-variant" size="is-small" class="mr-1"></b-icon>
                {{ rec.nombre_plato }} (-{{ Number(rec.cantidad_descuento) }} {{ props.row.unidad }})
              </span>
            </div>
          </b-table-column>

          <b-table-column field="stock_actual" label="Stock Disponible" numeric v-slot="props">
            <span
              class="tag is-medium has-text-weight-bold"
              :class="{
                'is-danger': Number(props.row.stock_actual) <= 0,
                'is-warning': Number(props.row.stock_actual) > 0 && Number(props.row.stock_actual) <= Number(props.row.stock_minimo),
                'is-success': Number(props.row.stock_actual) > Number(props.row.stock_minimo)
              }"
            >
              {{ Number(props.row.stock_actual) }} {{ props.row.unidad }}
            </span>
          </b-table-column>

          <b-table-column field="stock_minimo" label="Alerta Mín." numeric v-slot="props">
            <span class="has-text-grey">{{ Number(props.row.stock_minimo) }} {{ props.row.unidad }}</span>
          </b-table-column>

          <b-table-column field="costo_unitario" label="Costo Unit. Promedio" numeric v-slot="props">
            ${{ Number(props.row.costo_unitario || 0).toFixed(2) }}
          </b-table-column>

          <b-table-column label="Valor Stock" numeric v-slot="props">
            <strong>${{ (Number(props.row.stock_actual) * Number(props.row.costo_unitario || 0)).toFixed(2) }}</strong>
          </b-table-column>

          <b-table-column label="Acciones Rápidas" width="290" v-slot="props">
            <div class="buttons are-small">
              <b-button type="is-success" icon-left="cart-plus" @click="abrirModalCompra(props.row)">
                Comprar (+Stock)
              </b-button>
              <b-button type="is-warning" icon-left="minus-circle-outline" @click="abrirModalSalida(props.row)">
                Merma / Uso
              </b-button>
              <b-button type="is-danger" outlined icon-left="delete" title="Eliminar de bodega" @click="eliminarItem(props.row)">
              </b-button>
            </div>
          </b-table-column>
        </b-table>
      </b-tab-item>

      <!-- TAB 2: VINCULAR CON PLATOS DEL MENÚ (RECETAS SIMPLES) -->
      <b-tab-item label="Vincular con Platos del Menú" icon="link-variant">
        <div class="notification is-info is-light mb-4">
          <strong>¿Para qué sirve esta pestaña?</strong><br>
          Aquí conectas tu <strong>Bodega</strong> con los <strong>Platos/Bebidas que vendes en las mesas</strong>. Por ejemplo: si compras <strong>30 Pescados</strong> en Bodega, puedes vincular que cada vez que un mesero cobre un <em>"Pescado Frito"</em> o un <em>"Pescado Apanado"</em>, el sistema descuente automáticamente <strong>1 Pescado</strong> de tu bodega.
        </div>

        <div class="box has-background-light mb-4">
          <h5 class="title is-6 mb-3">Crear Nuevo Vínculo Automático</h5>
          <div class="columns is-vcentered">
            <div class="column is-4">
              <b-field label="Cuando en Mesa se venda el Plato / Bebida:">
                <b-select v-model="nuevoVinculo.insumo_id" placeholder="Selecciona un plato o bebida..." expanded>
                  <option v-for="ins in insumosMenu" :key="ins.id" :value="ins.id">
                    [{{ ins.tipo }}] {{ ins.nombre }} (${{ ins.precio }})
                  </option>
                </b-select>
              </b-field>
            </div>
            <div class="column is-4">
              <b-field label="Descontar automáticamente de Bodega el ítem:">
                <b-select v-model="nuevoVinculo.item_id" placeholder="Selecciona ítem de bodega..." expanded>
                  <option v-for="it in items" :key="it.id" :value="it.id">
                    {{ it.nombre }} (Stock: {{ Number(it.stock_actual) }} {{ it.unidad }})
                  </option>
                </b-select>
              </b-field>
            </div>
            <div class="column is-2">
              <b-field label="Cantidad a descontar:">
                <b-input type="number" step="0.5" min="0.1" v-model="nuevoVinculo.cantidad_descuento"></b-input>
              </b-field>
            </div>
            <div class="column is-2">
              <b-button type="is-primary" icon-left="check" expanded class="mt-4" @click="guardarVinculo">
                Vincular
              </b-button>
            </div>
          </div>
        </div>

        <b-table :data="recetas" striped hoverable>
          <b-table-column label="Plato o Bebida en el Menú" v-slot="props">
            <span class="tag is-light mr-2">{{ props.row.tipo_plato }}</span>
            <strong>{{ props.row.nombre_plato }}</strong>
          </b-table-column>
          <b-table-column label="Descuenta de Bodega" v-slot="props">
            <b-icon icon="arrow-right-bold" size="is-small" type="is-primary" class="mr-1"></b-icon>
            <strong>{{ props.row.nombre_item }}</strong>
          </b-table-column>
          <b-table-column label="Cantidad por cada venta" v-slot="props">
            <span class="tag is-primary is-light has-text-weight-bold">
              -{{ Number(props.row.cantidad_descuento) }} {{ props.row.unidad }}
            </span>
          </b-table-column>
          <b-table-column label="Quitar" width="100" v-slot="props">
            <b-button type="is-danger" size="is-small" outlined icon-left="link-off" @click="desvincularPlato(props.row.id)">
              Quitar
            </b-button>
          </b-table-column>
        </b-table>
      </b-tab-item>

      <!-- TAB 3: HISTORIAL DE COMPRAS, GASTOS Y KARDEX -->
      <b-tab-item label="Historial de Compras, Gastos y Movimientos (Kardex)" icon="clipboard-list-outline">
        <b-table :data="movimientos" striped hoverable paginated :per-page="15">
          <b-table-column field="fecha" label="Fecha y Hora" width="165" v-slot="props">
            {{ props.row.fecha }}
          </b-table-column>
          <b-table-column field="tipo_movimiento" label="Tipo" width="150" v-slot="props">
            <span
              class="tag has-text-weight-bold"
              :class="{
                'is-success': props.row.tipo_movimiento === 'COMPRA',
                'is-info': props.row.tipo_movimiento === 'VENTA',
                'is-warning': props.row.tipo_movimiento === 'GASTO_GENERAL',
                'is-danger': props.row.tipo_movimiento === 'MERMA' || props.row.tipo_movimiento === 'CONSUMO_COCINA'
              }"
            >
              {{ props.row.tipo_movimiento }}
            </span>
          </b-table-column>
          <b-table-column label="Producto / Bodega" v-slot="props">
            <strong v-if="props.row.nombre_item">{{ props.row.nombre_item }}</strong>
            <span v-else class="has-text-grey">Gasto Operativo General</span>
          </b-table-column>
          <b-table-column field="concepto" label="Detalle / Proveedor" v-slot="props">
            {{ props.row.concepto }}
            <span v-if="props.row.proveedor" class="tag is-light ml-1">Prov: {{ props.row.proveedor }}</span>
            <span v-if="Number(props.row.pagado_desde_caja) === 1" class="tag is-warning is-light ml-1">
              Pagado desde Caja
            </span>
          </b-table-column>
          <b-table-column label="Movimiento Stock" numeric v-slot="props">
            <span
              v-if="props.row.nombre_item"
              :class="Number(props.row.cantidad) >= 0 ? 'has-text-success has-text-weight-bold' : 'has-text-danger has-text-weight-bold'"
            >
              {{ Number(props.row.cantidad) > 0 ? '+' : '' }}{{ Number(props.row.cantidad) }} {{ props.row.unidad }}
            </span>
            <span v-else>-</span>
          </b-table-column>
          <b-table-column label="Gasto ($)" numeric v-slot="props">
            <strong v-if="Number(props.row.costo_total) > 0" class="has-text-danger">
              -${{ Number(props.row.costo_total).toFixed(2) }}
            </strong>
            <span v-else class="has-text-grey">$0.00</span>
          </b-table-column>
        </b-table>
      </b-tab-item>
    </b-tabs>

    <!-- MODAL 1: CREAR NUEVO ÍTEM EN BODEGA -->
    <b-modal :active.sync="modalNuevoItem" has-modal-card trap-focus>
      <div class="modal-card" style="max-width: 520px;">
        <header class="modal-card-head">
          <p class="modal-card-title">Nuevo Producto en Bodega</p>
          <button class="delete" aria-label="close" @click="modalNuevoItem = false"></button>
        </header>
        <section class="modal-card-body">
          <b-field label="Nombre del Producto o Materia Prima">
            <b-input v-model="formNuevoItem.nombre" placeholder="Ej. Pescado Entero, Coca-Cola 500ml, Pollo Presas..."></b-input>
          </b-field>
          <div class="columns">
            <div class="column is-6">
              <b-field label="Categoría de Bodega">
                <b-select v-model="formNuevoItem.categoria_inv" expanded>
                  <option value="PROTEINA">Proteína (Pescado, Carne, Pollo, Marisco)</option>
                  <option value="BEBIDA">Bebida / Gaseosa / Cerveza</option>
                  <option value="ABARROTE">Abarrote / Víveres (Arroz, Aceite...)</option>
                  <option value="DESCARTABLE">Descartables / Tarrinas /Otros</option>
                </b-select>
              </b-field>
            </div>
            <div class="column is-6">
              <b-field label="Unidad de Medida">
                <b-select v-model="formNuevoItem.unidad" expanded>
                  <option value="UND">Unidades (UND)</option>
                  <option value="PORCION">Porciones / Presas</option>
                  <option value="LIBRA">Libras (LB)</option>
                  <option value="LITRO">Litros (LT)</option>
                </b-select>
              </b-field>
            </div>
          </div>
          <div class="columns">
            <div class="column is-4">
              <b-field label="Stock Inicial">
                <b-input type="number" step="1" min="0" v-model="formNuevoItem.stock_actual"></b-input>
              </b-field>
            </div>
            <div class="column is-4">
              <b-field label="Costo Total Pagado ($)">
                <b-input type="number" step="0.01" min="0" v-model="formNuevoItem.costo_total_inicial" placeholder="Ej. 45.00"></b-input>
              </b-field>
            </div>
            <div class="column is-4">
              <b-field label="Avisar cuando queden:">
                <b-input type="number" step="1" min="0" v-model="formNuevoItem.stock_minimo"></b-input>
              </b-field>
            </div>
          </div>
          <b-field label="¿Vincular automáticamente con algún plato/bebida del Menú? (Opcional)">
            <b-select v-model="formNuevoItem.insumo_menu_id" placeholder="-- No vincular todavía --" expanded>
              <option :value="0">-- No vincular todavía --</option>
              <option v-for="ins in insumosMenu" :key="ins.id" :value="ins.id">
                [{{ ins.tipo }}] {{ ins.nombre }}
              </option>
            </b-select>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button type="is-primary" icon-left="content-save" @click="crearItemBodega">Guardar en Bodega</b-button>
          <b-button @click="modalNuevoItem = false">Cancelar</b-button>
        </footer>
      </div>
    </b-modal>

    <!-- MODAL 2: REGISTRAR COMPRA / ABASTECIMIENTO -->
    <b-modal :active.sync="modalCompra" has-modal-card trap-focus>
      <div class="modal-card" style="max-width: 500px;">
        <header class="modal-card-head has-background-success">
          <p class="modal-card-title has-text-white">Registrar Compra de Mercadería</p>
          <button class="delete" aria-label="close" @click="modalCompra = false"></button>
        </header>
        <section class="modal-card-body">
          <b-field label="Producto que compraste">
            <b-select v-model="formCompra.item_id" placeholder="Selecciona producto de bodega..." expanded>
              <option v-for="it in items" :key="it.id" :value="it.id">
                {{ it.nombre }} (Actual: {{ Number(it.stock_actual) }} {{ it.unidad }})
              </option>
            </b-select>
          </b-field>
          <div class="columns">
            <div class="column is-6">
              <b-field label="Cantidad Comprada (+Stock)">
                <b-input type="number" step="0.5" min="0.1" v-model="formCompra.cantidad" placeholder="Ej. 30"></b-input>
              </b-field>
            </div>
            <div class="column is-6">
              <b-field label="Costo Total Pagado ($)">
                <b-input type="number" step="0.01" min="0" v-model="formCompra.costo_total" placeholder="Ej. 45.00"></b-input>
              </b-field>
            </div>
          </div>
          <b-field label="Proveedor / Lugar de compra (Opcional)">
            <b-input v-model="formCompra.proveedor" placeholder="Ej. Mercado Mayorista, Camión Coca-Cola..."></b-input>
          </b-field>
          <b-field label="Nota / Detalle (Opcional)">
            <b-input v-model="formCompra.concepto" placeholder="Ej. Compra de 30 pescados frescos del día"></b-input>
          </b-field>
          <div class="field mt-3">
            <b-checkbox v-model="formCompra.pagado_desde_caja" type="is-warning">
              <strong>¿Se pagó con efectivo de la Caja abierta hoy?</strong> (Lo descuenta en el Arqueo de Caja)
            </b-checkbox>
          </div>
        </section>
        <footer class="modal-card-foot">
          <b-button type="is-success" icon-left="check" @click="guardarCompra">Sumar al Stock y Registrar Gasto</b-button>
          <b-button @click="modalCompra = false">Cancelar</b-button>
        </footer>
      </div>
    </b-modal>

    <!-- MODAL 3: REGISTRAR SALIDA / MERMA / CONSUMO COCINA -->
    <b-modal :active.sync="modalSalida" has-modal-card trap-focus>
      <div class="modal-card" style="max-width: 460px;">
        <header class="modal-card-head has-background-warning">
          <p class="modal-card-title">Descontar Stock (Merma / Uso en Cocina)</p>
          <button class="delete" aria-label="close" @click="modalSalida = false"></button>
        </header>
        <section class="modal-card-body">
          <p class="mb-3">
            Producto: <strong>{{ formSalida.nombre_item }}</strong>
          </p>
          <b-field label="Motivo de la salida">
            <b-select v-model="formSalida.tipo_movimiento" expanded>
              <option value="CONSUMO_COCINA">Uso en Cocina / Comida del Personal</option>
              <option value="MERMA">Merma / Producto Dañado o Caducado</option>
            </b-select>
          </b-field>
          <b-field label="Cantidad a descontar">
            <b-input type="number" step="0.5" min="0.1" v-model="formSalida.cantidad"></b-input>
          </b-field>
          <b-field label="Observación">
            <b-input v-model="formSalida.concepto" placeholder="Ej. 1 pescado dañado o consumo personal"></b-input>
          </b-field>
        </section>
        <footer class="modal-card-foot">
          <b-button type="is-warning" icon-left="check" @click="guardarSalida">Descontar de Bodega</b-button>
          <b-button @click="modalSalida = false">Cancelar</b-button>
        </footer>
      </div>
    </b-modal>

    <!-- MODAL 4: REGISTRAR GASTO GENERAL (GAS, HIELO, VERDURAS, ETC.) -->
    <b-modal :active.sync="modalGastoGeneral" has-modal-card trap-focus>
      <div class="modal-card" style="max-width: 480px;">
        <header class="modal-card-head has-background-danger">
          <p class="modal-card-title has-text-white">Registrar Gasto General del Restaurante</p>
          <button class="delete" aria-label="close" @click="modalGastoGeneral = false"></button>
        </header>
        <section class="modal-card-body">
          <p class="is-size-7 has-text-grey mb-3">
            Usa esta opción para gastos que no se cuentan por unidad en el menú, como: cilindros de gas, fundas de hielo, legumbres/verduras del día, servilletas, detergente, etc.
          </p>
          <b-field label="¿Qué se compró o pagó?">
            <b-input v-model="formGastoGeneral.concepto" placeholder="Ej. 2 cilindros de gas + cebollas y limones"></b-input>
          </b-field>
          <b-field label="Monto Total Pagado ($)">
            <b-input type="number" step="0.01" min="0.01" v-model="formGastoGeneral.costo_total" placeholder="Ej. 18.50"></b-input>
          </b-field>
          <b-field label="Proveedor (Opcional)">
            <b-input v-model="formGastoGeneral.proveedor" placeholder="Ej. Distribuidora de Gas / Mercado"></b-input>
          </b-field>
          <div class="field mt-3">
            <b-checkbox v-model="formGastoGeneral.pagado_desde_caja" type="is-warning">
              <strong>¿Se pagó con efectivo de la Caja abierta hoy?</strong>
            </b-checkbox>
          </div>
        </section>
        <footer class="modal-card-foot">
          <b-button type="is-danger" icon-left="check" @click="guardarGastoGeneral">Registrar Gasto</b-button>
          <b-button @click="modalGastoGeneral = false">Cancelar</b-button>
        </footer>
      </div>
    </b-modal>
  </section>
</template>

<script>
import HttpService from '../../Servicios/HttpService'

export default {
  name: 'Inventario',
  data() {
    const hoy = new Date()
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().substr(0, 10)
    const fechaHoy = hoy.toISOString().substr(0, 10)

    return {
      tabActiva: 0,
      filtro: {
        inicio: primerDiaMes,
        fin: fechaHoy
      },
      items: [],
      recetas: [],
      insumosMenu: [],
      movimientos: [],
      finanzas: {
        ventas_periodo: 0,
        compras_periodo: 0,
        utilidad_neta: 0,
        valor_bodega: 0,
        alertas_bajo_stock: 0
      },
      modalNuevoItem: false,
      formNuevoItem: {
        nombre: '',
        categoria_inv: 'PROTEINA',
        unidad: 'UND',
        stock_actual: 0,
        costo_total_inicial: 0,
        stock_minimo: 5,
        insumo_menu_id: 0
      },
      modalCompra: false,
      formCompra: {
        item_id: null,
        cantidad: '',
        costo_total: '',
        proveedor: '',
        concepto: '',
        pagado_desde_caja: false
      },
      modalSalida: false,
      formSalida: {
        item_id: null,
        nombre_item: '',
        tipo_movimiento: 'CONSUMO_COCINA',
        cantidad: 1,
        concepto: ''
      },
      modalGastoGeneral: false,
      formGastoGeneral: {
        concepto: '',
        costo_total: '',
        proveedor: '',
        pagado_desde_caja: false
      },
      nuevoVinculo: {
        insumo_id: null,
        item_id: null,
        cantidad_descuento: 1
      }
    }
  },
  mounted() {
    this.cargarDatos()
  },
  methods: {
    cargarDatos() {
      HttpService.obtenerConDatos(
        {
          accion: 'resumen',
          inicio: this.filtro.inicio,
          fin: this.filtro.fin
        },
        'inventario.php'
      ).then(res => {
        if (res && !res.error) {
          this.items = res.items || []
          this.recetas = res.recetas || []
          this.insumosMenu = res.insumos_menu || []
          this.movimientos = res.movimientos || []
          if (res.finanzas) {
            this.finanzas = res.finanzas
          }
        }
      })
    },
    obtenerPlatosVinculados(itemId) {
      return this.recetas.filter(r => Number(r.item_id) === Number(itemId))
    },
    abrirModalNuevoItem() {
      this.formNuevoItem = {
        nombre: '',
        categoria_inv: 'PROTEINA',
        unidad: 'UND',
        stock_actual: 0,
        costo_total_inicial: 0,
        stock_minimo: 5,
        insumo_menu_id: 0
      }
      this.modalNuevoItem = true
    },
    crearItemBodega() {
      if (!this.formNuevoItem.nombre) {
        this.$buefy.toast.open({ message: 'Ingresa el nombre del producto', type: 'is-danger' })
        return
      }
      HttpService.obtenerConDatos(
        Object.assign({ accion: 'crear_item' }, this.formNuevoItem),
        'inventario.php'
      ).then(res => {
        if (res && res.resultado) {
          this.modalNuevoItem = false
          this.$buefy.toast.open({ message: 'Producto agregado a Bodega', type: 'is-success' })
          this.cargarDatos()
        }
      })
    },
    abrirModalCompra(item) {
      this.formCompra = {
        item_id: item ? item.id : (this.items[0] ? this.items[0].id : null),
        cantidad: '',
        costo_total: '',
        proveedor: '',
        concepto: '',
        pagado_desde_caja: false
      }
      this.modalCompra = true
    },
    guardarCompra() {
      if (!this.formCompra.item_id || Number(this.formCompra.cantidad) <= 0) {
        this.$buefy.toast.open({ message: 'Selecciona un producto y la cantidad comprada', type: 'is-danger' })
        return
      }
      HttpService.obtenerConDatos(
        Object.assign({ accion: 'registrar_compra' }, this.formCompra),
        'inventario.php'
      ).then(res => {
        if (res && res.resultado) {
          this.modalCompra = false
          this.$buefy.toast.open({ message: 'Compra registrada y stock actualizado', type: 'is-success' })
          this.cargarDatos()
        }
      })
    },
    abrirModalSalida(item) {
      this.formSalida = {
        item_id: item.id,
        nombre_item: item.nombre,
        tipo_movimiento: 'CONSUMO_COCINA',
        cantidad: 1,
        concepto: ''
      }
      this.modalSalida = true
    },
    guardarSalida() {
      HttpService.obtenerConDatos(
        Object.assign({ accion: 'registrar_salida' }, this.formSalida),
        'inventario.php'
      ).then(res => {
        if (res && res.resultado) {
          this.modalSalida = false
          this.$buefy.toast.open({ message: 'Stock descontado de Bodega', type: 'is-warning' })
          this.cargarDatos()
        }
      })
    },
    guardarGastoGeneral() {
      if (!this.formGastoGeneral.concepto || Number(this.formGastoGeneral.costo_total) <= 0) {
        this.$buefy.toast.open({ message: 'Ingresa el concepto y monto del gasto', type: 'is-danger' })
        return
      }
      HttpService.obtenerConDatos(
        Object.assign({ accion: 'registrar_gasto_general' }, this.formGastoGeneral),
        'inventario.php'
      ).then(res => {
        if (res && res.resultado) {
          this.modalGastoGeneral = false
          this.formGastoGeneral = { concepto: '', costo_total: '', proveedor: '', pagado_desde_caja: false }
          this.$buefy.toast.open({ message: 'Gasto registrado correctamente', type: 'is-success' })
          this.cargarDatos()
        }
      })
    },
    guardarVinculo() {
      if (!this.nuevoVinculo.insumo_id || !this.nuevoVinculo.item_id) {
        this.$buefy.toast.open({ message: 'Selecciona el plato del menú y el producto de bodega', type: 'is-danger' })
        return
      }
      HttpService.obtenerConDatos(
        Object.assign({ accion: 'vincular_plato' }, this.nuevoVinculo),
        'inventario.php'
      ).then(res => {
        if (res && res.resultado) {
          this.$buefy.toast.open({ message: 'Plato vinculado con Bodega exitosamente', type: 'is-success' })
          this.cargarDatos()
        }
      })
    },
    desvincularPlato(idReceta) {
      HttpService.obtenerConDatos({ accion: 'desvincular_plato', id: idReceta }, 'inventario.php').then(() => {
        this.$buefy.toast.open({ message: 'Vínculo eliminado', type: 'is-info' })
        this.cargarDatos()
      })
    },
    importarBebidasMenu() {
      HttpService.obtenerConDatos({ accion: 'importar_bebidas' }, 'inventario.php').then(res => {
        if (res && res.resultado) {
          this.$buefy.toast.open({
            message: `Se importaron y vincularon ${res.importadas} bebidas a tu Bodega`,
            type: 'is-success'
          })
          this.cargarDatos()
        }
      })
    },
    eliminarItem(item) {
      this.$buefy.dialog.confirm({
        title: 'Eliminar de Bodega',
        message: `¿Seguro que deseas quitar <b>${item.nombre}</b> del inventario?`,
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar',
        type: 'is-danger',
        onConfirm: () => {
          HttpService.obtenerConDatos({ accion: 'eliminar_item', id: item.id }, 'inventario.php').then(() => {
            this.cargarDatos()
          })
        }
      })
    }
  }
}
</script>
