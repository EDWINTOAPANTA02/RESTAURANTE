<template>
    <section>
        <ul v-if="errores.length > 0" class="mb-3">
            <li class="has-text-danger has-text-centered" v-for="error in errores" :key="error">{{ error }}</li>
        </ul>

        <div class="columns is-multiline">
            <div class="column is-7">
                <b-field label="Tipo">
                    <b-select v-model="insumo.tipo" @change.native="obtenerCategorias" expanded>
                        <option value="" disabled selected>Selecciona el tipo de insumo</option>
                        <option value="PLATILLO">Platillo</option>
                        <option value="BEBIDA">Bebida</option>
                    </b-select>
                </b-field>

                <b-field label="Código">
                    <b-input type="text" placeholder="Código identificador del insumo (Ej. 001)" v-model="insumo.codigo"></b-input>
                </b-field>

                <b-field label="Nombre del Platillo o Bebida">
                    <b-input type="text" placeholder="Ej. Seco de Pollo, Encebollado, Coca-Cola..." v-model="insumo.nombre"></b-input>
                </b-field>

                <b-field label="Descripción (Visible en el Menú Digital QR)">
                    <b-input maxlength="200" type="textarea"
                        placeholder="Ej. Acompañado de arroz amarillo, maduro frito y ensalada fresca" v-model="insumo.descripcion"></b-input>
                </b-field>

                <div class="columns">
                    <div class="column is-4">
                        <b-field label="Categoría">
                            <b-select v-model="insumo.categoria" expanded>
                                <option value="" selected disabled>Selecciona la categoría</option>
                                <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                                    {{ categoria.nombre }}
                                </option>
                            </b-select>
                        </b-field>
                    </div>
                    <div class="column is-4">
                        <b-field label="Precio de Venta ($)">
                            <b-input type="number" step="0.01" placeholder="Ej. 5.50" v-model="insumo.precio"></b-input>
                        </b-field>
                    </div>
                    <div class="column is-4">
                        <b-field label="Stock hoy (Opcional)">
                            <b-input type="number" min="0" placeholder="Vacío = Ilimitado" v-model="insumo.stock"></b-input>
                        </b-field>
                    </div>
                </div>
            </div>

            <!-- Columna de Imagen Referencial para el Catálogo y Menú QR -->
            <div class="column is-5">
                <div class="box has-background-light has-text-centered" style="height: 100%; display: flex; flex-direction: column; justify-content: center;">
                    <p class="label mb-2">
                        <b-icon icon="camera" size="is-small" type="is-primary"></b-icon>
                        Foto Referencial del Platillo (Menú QR)
                    </p>
                    <p class="is-size-7 has-text-grey mb-3">
                        Esta imagen aparecerá en el Menú Digital QR cuando tus clientes escaneen desde su mesa.
                    </p>

                    <div v-if="vistaPreviaImagen" class="mb-3">
                        <img
                            :src="vistaPreviaImagen"
                            alt="Vista previa del platillo"
                            style="width: 100%; max-height: 210px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.12);"
                        />
                        <div class="mt-2">
                            <b-button size="is-small" type="is-danger" outlined icon-left="delete" @click="quitarImagen">
                                Quitar foto
                            </b-button>
                        </div>
                    </div>
                    <div v-else class="py-5 mb-3" style="border: 2px dashed #cbd5e1; border-radius: 10px; background: #fff;">
                        <b-icon icon="image-plus" size="is-large" type="is-grey-light"></b-icon>
                        <p class="is-size-7 has-text-grey mt-2">Sin foto seleccionada</p>
                    </div>

                    <div class="field">
                        <label class="button is-primary is-outlined is-fullwidth" style="cursor: pointer;">
                            <b-icon icon="upload" size="is-small" class="mr-1"></b-icon>
                            <span>Subir Foto desde mi Equipo / Celular</span>
                            <input type="file" accept="image/*" style="display: none;" @change="alSeleccionarArchivo" />
                        </label>
                    </div>

                    <b-field label="O pegar enlace (URL) de una imagen:" custom-class="is-size-7 has-text-grey mt-2">
                        <b-input
                            size="is-small"
                            type="text"
                            placeholder="https://ejemplo.com/foto-plato.jpg"
                            v-model="insumo.imagen"
                            @input="insumo.imagen_base64 = null"
                        ></b-input>
                    </b-field>
                </div>
            </div>
        </div>

        <div class="has-text-centered mt-4">
            <b-button type="is-success" size="is-large" icon-left="check" @click="registrar">
                Guardar Platillo / Bebida
            </b-button>
        </div>
    </section>
</template>

<script>
import Utiles from '../../Servicios/Utiles'
import HttpService from '../../Servicios/HttpService'

export default {
    name: "DatosInsumo",
    props: ["insumo"],

    data: () => ({
        errores: [],
        categorias: []
    }),

    computed: {
        vistaPreviaImagen() {
            if (this.insumo && this.insumo.imagen_base64) {
                return this.insumo.imagen_base64
            }
            if (this.insumo && this.insumo.imagen) {
                return Utiles.generarUrlImagen(this.insumo.imagen)
            }
            return null
        }
    },

    watch: {
        'insumo.tipo': {
            immediate: true,
            handler(nuevoTipo) {
                if (nuevoTipo) {
                    this.obtenerCategorias()
                }
            }
        }
    },

    methods: {
        alSeleccionarArchivo(evento) {
            const archivo = evento.target.files && evento.target.files[0]
            if (!archivo) return

            const lector = new FileReader()
            lector.onload = (e) => {
                const img = new Image()
                img.onload = () => {
                    const canvas = document.createElement('canvas')
                    const maxDim = 650
                    let width = img.width
                    let height = img.height
                    if (width > height && width > maxDim) {
                        height = Math.round((height * maxDim) / width)
                        width = maxDim
                    } else if (height > maxDim) {
                        width = Math.round((width * maxDim) / height)
                        height = maxDim
                    }
                    canvas.width = width
                    canvas.height = height
                    const ctx = canvas.getContext('2d')
                    ctx.drawImage(img, 0, 0, width, height)
                    const comprimida = canvas.toDataURL('image/jpeg', 0.85)
                    this.$set(this.insumo, 'imagen_base64', comprimida)
                }
                img.src = e.target.result
            }
            lector.readAsDataURL(archivo)
        },

        quitarImagen() {
            this.$set(this.insumo, 'imagen', null)
            this.$set(this.insumo, 'imagen_base64', null)
        },

        registrar() {
            let datos = {
                tipo: this.insumo.tipo,
                codigo: this.insumo.codigo,
                nombre: this.insumo.nombre,
                descripcion: this.insumo.descripcion,
                categoria: this.insumo.categoria,
                precio: this.insumo.precio
            }
            this.errores = Utiles.validar(datos)
            if (this.errores.length > 0) return
            this.$emit("registrado", this.insumo)
        },

        obtenerCategorias() {
            if (!this.insumo || !this.insumo.tipo) return
            HttpService.obtenerConDatos(this.insumo.tipo, "obtener_categorias_tipo.php")
                .then(resultado => {
                    this.categorias = resultado
                })
        }
    }
}
</script>
