import Chart from 'chart.js'
const MESES = ["Enero" ,"Febrero" ,"Marzo" ,"Abril" ,"Mayo" ,"Junio" ,"Julio" ,"Agosto" ,"Septiembre" ,"Octubre" ,"Noviembre" ,"Diciembre"]
const DIAS = ["Domingo" ,"Lunes" ,"Martes" ,"Miércoles" ,"Jueves" ,"Viernes" ,"Sábado"]

const Utiles =  {
	generarUrlImagen(imagen){
		if (!imagen) return null;
		const base = (process.env.API_BASE || '/api/').replace(/\/$/, '');
		return base + imagen;
	},
	generarGrafica(array, contenedor, grafica, id, opciones = {}){
		this.resetearGrafica(contenedor, grafica, id, opciones)
		let lista = array
		const labels = lista.map(elemento => elemento[Object.keys(elemento)[0]])
		const totales = lista.map(elemento => elemento.totalVentas)

		const tipoGrafica = opciones.tipo || "line"
		const data = {
			labels: labels,
			datasets: [{
				label: "Total ",
				data: totales,
				backgroundColor: tipoGrafica === "bar" ? "#209cee" : ("#" + Math.floor(Math.random()*16777215).toString(16)),
				borderColor: tipoGrafica === "bar" ? "#209cee" : undefined,
				fill: false,
				tension: 0.1,
				borderWidth: 3
			}]
		}

		const ctx = document.getElementById(id);
		const graficaGenerada = new Chart(ctx, {
		type: tipoGrafica,
		data: data,
		options: {
			responsive: true,
			maintainAspectRatio: opciones.maintainAspectRatio !== undefined ? opciones.maintainAspectRatio : true,
			layout: {
				padding: {
					bottom: 10
				}
			},
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

	resetearGrafica(contenedor, grafica, id, opciones = {}){
		const $contenedorGrafica = document.querySelector(contenedor)
		let $grafica = document.querySelector(grafica)
		$grafica.remove()
		let $nuevaGrafica = document.createElement("canvas")
		$nuevaGrafica.setAttribute("id",id)
		if(opciones.height) {
			$nuevaGrafica.height = opciones.height
			$nuevaGrafica.style.height = opciones.height + "px"
		}
		$contenedorGrafica.appendChild($nuevaGrafica)
	},

	calcularTotales(array){
		let total = 0
		array.forEach(elemento => {
			total += parseFloat(elemento.totalVentas)
		});
		return total
	},


	validar(datos) {
       
		let errores = []
        
		for (let [clave, valor] of Object.entries(datos)) {
			if(!valor || !clave) errores.push("Debes colocar el valor de " + clave)
		}
		return errores
	},

	cambiarNumeroANombreMes(arreglo){
		for(let i = 0; i < arreglo.length; i++){
			let pos = arreglo[i].mes 
			arreglo[i].mes = MESES[pos-1]
			
		}
		return arreglo
	},

	cambiarDiaSemana(arreglo){
		for(let i = 0; i < arreglo.length; i++){
			let pos = arreglo[i].numeroDia 
			arreglo[i].dia = DIAS[pos-1]
			
		}
		return arreglo
	},


	

}

export default  Utiles 