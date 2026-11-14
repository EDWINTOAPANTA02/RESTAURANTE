import Chart from 'chart.js'
const MESES = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"]
const DIAS = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"]

const Utiles = {
	generarUrlImagen(imagen) {
		const base = process.env.NODE_ENV === 'production'
			? './api'
			: 'http://localhost/botanero_ventas/api'
		return base + imagen
	},
	generarGrafica(array, contenedor, grafica, id) {
		this.resetearGrafica(contenedor, grafica, id)
		let lista = array
		//console.log(lista)
		const labels = lista.map(elemento => {
			return elemento[Object.keys(elemento)[0]]
		})
		const totales = lista.map(elemento => {
			return elemento.totalVentas
		})


		const data = {
			labels: labels,
			datasets: [{
				label: "Total ",
				data: totales,
				backgroundColor: "#" + Math.floor(Math.random() * 16777215).toString(16),
				fill: false,
				tension: 0.1,
				borderWidth: 3
			}]
		}

		const ctx = document.getElementById(id);
		const graficaGenerada = new Chart(ctx, {
			type: "line",
			data: data,
			options: {
				responsive: true,
				lineTension: 1,
				scales: {
					yAxes: [
						{
							ticks: {
								beginAtZero: true,
								padding: 25
							}
						}
					]
				}
			}
		});


	},

	resetearGrafica(contenedor, grafica, id) {
		const $contenedorGrafica = document.querySelector(contenedor)
		let $grafica = document.querySelector(grafica)
		$grafica.remove()
		let $nuevaGrafica = document.createElement("canvas")
		$nuevaGrafica.setAttribute("id", id)
		$contenedorGrafica.appendChild($nuevaGrafica)
	},

	calcularTotales(array) {
		let total = 0
		array.forEach(elemento => {
			total += parseFloat(elemento.totalVentas)
		});
		return total
	},


	validar(datos) {

		let errores = []

		for (let [clave, valor] of Object.entries(datos)) {
			if (!valor || !clave) errores.push("Debes colocar el valor de " + clave)
		}
		return errores
	},

	validarCedula(cedula) {
		if (!cedula) return false
		const limpia = String(cedula).trim()
		if (!/^\d{10}$/.test(limpia)) return false
		const provincia = parseInt(limpia.substring(0, 2), 10)
		if (!((provincia >= 1 && provincia <= 24) || provincia === 30)) return false
		const tercerDigito = parseInt(limpia.charAt(2), 10)
		if (tercerDigito < 6) {
			const coef = [2, 1, 2, 1, 2, 1, 2, 1, 2]
			let suma = 0
			for (let i = 0; i < 9; i++) {
				let val = parseInt(limpia.charAt(i), 10) * coef[i]
				if (val >= 10) val -= 9
				suma += val
			}
			const verificador = (10 - (suma % 10)) % 10
			return verificador === parseInt(limpia.charAt(9), 10)
		}
		return true
	},

	cambiarNumeroANombreMes(arreglo) {
		for (let i = 0; i < arreglo.length; i++) {
			let pos = arreglo[i].mes
			arreglo[i].mes = MESES[pos - 1]

		}
		return arreglo
	},

	cambiarDiaSemana(arreglo) {
		for (let i = 0; i < arreglo.length; i++) {
			let pos = arreglo[i].numeroDia
			arreglo[i].dia = DIAS[pos - 1]

		}
		return arreglo
	},




}

export default Utiles 