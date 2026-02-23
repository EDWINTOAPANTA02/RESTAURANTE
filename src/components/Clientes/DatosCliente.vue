<template>
    <div>
        <b-field grouped group-multiline>
            <b-field label="Nombres *" expanded>
                <b-input v-model="form.nombres" placeholder="Ej: Juan Carlos" maxlength="100" />
            </b-field>
            <b-field label="Apellidos *" expanded>
                <b-input v-model="form.apellidos" placeholder="Ej: Pérez García" maxlength="100" />
            </b-field>
        </b-field>

        <b-field grouped group-multiline>
            <b-field label="Tipo de identificación" expanded>
                <b-select v-model="form.tipo_id" expanded>
                    <option value="CEDULA">Cédula</option>
                    <option value="RUC">RUC</option>
                    <option value="PASAPORTE">Pasaporte</option>
                </b-select>
            </b-field>
            <b-field :label="etiquetaId + ' *'" expanded>
                <b-input v-model="form.cedula_ruc" :placeholder="placeholderCedula" />
            </b-field>
        </b-field>

        <b-field grouped group-multiline>
            <b-field label="Teléfono" expanded>
                <b-input v-model="form.telefono" placeholder="Ej: 0999123456" maxlength="20" icon="phone" />
            </b-field>
            <b-field label="Correo electrónico" expanded>
                <b-input v-model="form.correo" type="email" placeholder="cliente@email.com" maxlength="100" icon="email" />
            </b-field>
        </b-field>

        <b-field label="Dirección">
            <b-input v-model="form.direccion" placeholder="Ej: Av. Amazonas N12-34, Quito" maxlength="255" />
        </b-field>

        <b-field label="Estado" v-if="modoEdicion">
            <b-select v-model="form.estado" expanded>
                <option value="ACTIVO">Activo</option>
                <option value="INACTIVO">Inactivo</option>
            </b-select>
        </b-field>

        <div class="field is-grouped is-grouped-right" style="margin-top: 1.5rem">
            <p class="control">
                <b-button tag="router-link" to="/clientes" type="is-light" icon-left="arrow-left">
                    Cancelar
                </b-button>
            </p>
            <p class="control">
                <b-button type="is-primary" icon-left="content-save" @click="guardar">
                    {{ modoEdicion ? 'Guardar cambios' : 'Registrar cliente' }}
                </b-button>
            </p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DatosCliente',
    props: {
        cliente: {
            type: Object,
            default: () => ({
                nombres: '', apellidos: '', tipo_id: 'CEDULA',
                cedula_ruc: '', telefono: '', direccion: '', correo: '', estado: 'ACTIVO'
            })
        },
        modoEdicion: { type: Boolean, default: false }
    },

    data() {
        return {
            form: { ...this.cliente }
        }
    },

    watch: {
        cliente(val) { this.form = { ...val } }
    },

    computed: {
        etiquetaId() {
            return { CEDULA: 'Cédula', RUC: 'RUC', PASAPORTE: 'Pasaporte' }[this.form.tipo_id] || 'Identificación'
        },
        placeholderCedula() {
            return {
                CEDULA:    'Ej: 1712345678',
                RUC:       'Ej: 1712345678001',
                PASAPORTE: 'Ej: A1234567'
            }[this.form.tipo_id] || ''
        }
    },

    methods: {
        guardar() {
            if (!this.form.nombres.trim()) {
                this.$buefy.toast.open({ message: 'El nombre es requerido.', type: 'is-warning' }); return
            }
            if (!this.form.apellidos.trim()) {
                this.$buefy.toast.open({ message: 'El apellido es requerido.', type: 'is-warning' }); return
            }
            if (!this.form.cedula_ruc.trim() || this.form.cedula_ruc.trim().length < 5) {
                this.$buefy.toast.open({ message: `La ${this.etiquetaId} debe tener al menos 5 caracteres.`, type: 'is-warning' }); return
            }
            if (this.form.correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.correo)) {
                this.$buefy.toast.open({ message: 'El correo no tiene un formato válido.', type: 'is-warning' }); return
            }
            this.$emit('guardado', { ...this.form })
        }
    }
}
</script>
