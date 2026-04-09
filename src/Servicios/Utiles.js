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
		const ctx = document.getElementById(id).getContext('2d');
		
		// Crear Gradiente
		let gradient = ctx.createLinearGradient(0, 0, 0, opciones.height || 400);
		
		const mainColor = tipoGrafica === "bar" ? 'rgba(32, 156, 238, 0.8)' : 'rgba(72, 199, 142, 0.8)';
		const accentColor = tipoGrafica === "bar" ? 'rgba(32, 156, 238, 0.1)' : 'rgba(72, 199, 142, 0.1)';
		
		gradient.addColorStop(0, mainColor);
		gradient.addColorStop(1, accentColor);

		const data = {
			labels: labels,
			datasets: [{
				label: "Subtotal ($) ",
				data: totales,
				backgroundColor: tipoGrafica === "bar" ? gradient : gradient,
				borderColor: tipoGrafica === "bar" ? "rgba(32, 156, 238, 1)" : "rgba(72, 199, 142, 1)",
				pointBackgroundColor: "rgba(255, 255, 255, 1)",
				pointBorderColor: "rgba(72, 199, 142, 1)",
				pointHoverBackgroundColor: "rgba(72, 199, 142, 1)",
				pointHoverBorderColor: "rgba(255, 255, 255, 1)",
				fill: true,
				tension: 0.4, // Suavizado de línea
				borderWidth: 3,
				pointRadius: 4,
				pointHoverRadius: 6
			}]
		}

		new Chart(ctx, {
			type: tipoGrafica,
			data: data,
			options: {
				responsive: true,
				maintainAspectRatio: opciones.maintainAspectRatio !== undefined ? opciones.maintainAspectRatio : true,
				legend: {
					display: false // Menos ruido visual
				},
				tooltips: {
					backgroundColor: 'rgba(0,0,0,0.8)',
					titleFontColor: '#fff',
					bodyFontSize: 14,
					xPadding: 12,
					yPadding: 12,
					displayColors: false,
					callbacks: {
						label: function(tooltipItem) {
							return " Total: $" + tooltipItem.yLabel.toFixed(2);
						}
					}
				},
				layout: {
					padding: {
						bottom: 5,
						top: 5,
						left: 5,
						right: 5
					}
				},
				scales: {
					xAxes: [{
						gridLines: {
							display: false
						},
						ticks: {
							fontColor: '#888'
						}
					}],
					yAxes: [{
						gridLines: {
							color: 'rgba(200, 200, 200, 0.1)',
							zeroLineColor: 'rgba(200, 200, 200, 0.1)'
						},
						ticks: {
							beginAtZero: true,
							padding: 10,
							fontColor: '#888',
							callback: function(value) {
								if (value >= 1000) return '$' + (value / 1000) + 'k';
								return '$' + value;
							}
						}
					}]
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