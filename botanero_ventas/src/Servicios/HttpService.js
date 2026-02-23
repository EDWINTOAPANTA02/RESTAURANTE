const RUTA_GLOBAL = "http://localhost/botanero-ventas/api/"

const HttpService = {
    async registrar(datos, ruta) {
        const respuesta = await fetch(RUTA_GLOBAL + ruta, {
            method: "post",
            body: JSON.stringify(datos),
            credentials: 'include'
        });
        let resultado = await respuesta.json()
        if (resultado && resultado.error) {
            console.error("Error en registrar:", resultado.error)
            return false
        }
        return resultado
    },

    async obtenerConDatos(datos, ruta) {
        const respuesta = await fetch(RUTA_GLOBAL + ruta, {
            method: "post",
            body: JSON.stringify(datos),
            credentials: 'include'
        });
        let resultado = await respuesta.json()
        if (resultado && resultado.error) {
            console.error("Error en obtenerConDatos:", resultado.error)
            return null
        }
        return resultado
    },


    async obtener(ruta) {
        let respuesta = await fetch(RUTA_GLOBAL + ruta, {
            credentials: 'include'
        })
        let datos = await respuesta.json()
        if (datos && datos.error) {
            console.error("Error en obtener:", datos.error)
            return []
        }
        return datos
    },

    async eliminar(ruta, id) {
        const respuesta = await fetch(RUTA_GLOBAL + ruta, {
            method: "post",
            body: JSON.stringify(id),
            credentials: 'include'
        });
        let resultado = await respuesta.json()
        if (resultado && resultado.error) {
            console.error("Error en eliminar:", resultado.error)
            return false
        }
        return resultado
    }

}

export default HttpService