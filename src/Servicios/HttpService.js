const RUTA_GLOBAL = process.env.NODE_ENV === 'production'
    ? './api/'
    : 'http://localhost/botanero_ventas/api/'

function notificarSiExpirado(res) {
    if (res && res.expirado) {
        window.dispatchEvent(new CustomEvent('licencia-expirada', { detail: res }))
    }
}

const HttpService = {
    async registrar(datos, ruta) {
        try {
            const respuesta = await fetch(RUTA_GLOBAL + ruta, {
                method: "post",
                body: JSON.stringify(datos),
                credentials: 'include'
            });
            let resultado = await respuesta.json()
            notificarSiExpirado(resultado)
            return resultado
        } catch (e) {
            console.error("Error en HTTP registrar:", e)
            return { resultado: false, error: "Error de conexión con el servidor" }
        }
    },

    async obtenerConDatos(datos, ruta) {
        try {
            const respuesta = await fetch(RUTA_GLOBAL + ruta, {
                method: "post",
                body: JSON.stringify(datos),
                credentials: 'include'
            });
            let resultado = await respuesta.json()
            notificarSiExpirado(resultado)
            if (resultado && resultado.error) {
                console.error("Error en obtenerConDatos:", resultado.error)
                return null
            }
            return resultado
        } catch (e) {
            console.error("Error en HTTP obtenerConDatos:", e)
            return { resultado: false, error: "Error de conexión" }
        }
    },


    async obtener(ruta) {
        try {
            let respuesta = await fetch(RUTA_GLOBAL + ruta, {
                credentials: 'include'
            })
            let datos = await respuesta.json()
            notificarSiExpirado(datos)
            if (datos && datos.error) {
                console.error("Error en obtener:", datos.error)
                return []
            }
            return datos
        } catch (e) {
            console.error("Error en HTTP obtener:", e)
            return []
        }
    },

    async eliminar(ruta, id) {
        try {
            const respuesta = await fetch(RUTA_GLOBAL + ruta, {
                method: "post",
                body: JSON.stringify(id),
                credentials: 'include'
            });
            let resultado = await respuesta.json()
            notificarSiExpirado(resultado)
            if (resultado && resultado.error) {
                console.error("Error en eliminar:", resultado.error)
                return false
            }
            return resultado
        } catch (e) {
            console.error("Error en HTTP eliminar:", e)
            return { resultado: false, error: "Error de conexión" }
        }
    },

    async actualizar(datos, ruta) {
        try {
            const respuesta = await fetch(RUTA_GLOBAL + ruta, {
                method: "PUT",
                body: JSON.stringify(datos),
                credentials: 'include'
            });
            let resultado = await respuesta.json()
            notificarSiExpirado(resultado)
            return resultado
        } catch (e) {
            console.error("Error en HTTP actualizar:", e)
            return { resultado: false, error: "Error de conexión" }
        }
    }

}

export default HttpService
