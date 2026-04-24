import Chart from 'chart.js'
const MESES = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"]
const DIAS = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"]

const Utiles = {
	generarUrlImagen(imagen) {
		if (!imagen) return null;
		const base = (process.env.API_BASE || '/api/').replace(/\/$/, '');
		return base + imagen;
	},
	generarGrafica(array, contenedor, grafica, id, opciones = {}) {
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
				label: "Subtotal (Bs.) ",
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
						label: function (tooltipItem) {
							return " Total: Bs. " + Math.round(tooltipItem.yLabel);
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
							callback: function (value) {
								if (value >= 1000) return 'Bs. ' + (value / 1000) + 'k';
								return 'Bs. ' + value;
							}
						}
					}]
				}
			}
		});
	},

	resetearGrafica(contenedor, grafica, id, opciones = {}) {
		const $contenedorGrafica = document.querySelector(contenedor)
		let $grafica = document.querySelector(grafica)
		$grafica.remove()
		let $nuevaGrafica = document.createElement("canvas")
		$nuevaGrafica.setAttribute("id", id)
		if (opciones.height) {
			$nuevaGrafica.height = opciones.height
			$nuevaGrafica.style.height = opciones.height + "px"
		}
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

	formatearResumenCombo(texto) {
		if (!texto) return '';

		// Si el texto ya viene agrupado (sin "Menú X:") y no tiene caracteres viejos como " · ", 
		// lo devolvemos tal cual. Si tiene " · ", lo limpiamos.
		if (!texto.includes('Menú ') && !texto.includes(' · ')) {
			return texto;
		}

		// Si viene en formato "Menú X: ...", o tiene " · ", lo procesamos para agrupar
		const lineas = texto.split('\n');
		const conteos = {};
		const orden = [];

		lineas.forEach(linea => {
			// Eliminar prefijo de menú si existe: "Menú 1: "
			const contenido = linea.replace(/^Menú \d+: /, '');

			// Separar componentes por ' - ' o ' · '
			const partes = contenido.split(/ - | · /);

			partes.forEach(p => {
				let item = p.trim();

				// Si el componente tiene un tag tipo "Sopa: Maní", quedarnos solo con "Maní"
				if (item.includes(':')) {
					item = item.split(':').pop().trim();
				}

				if (item && !item.match(/^Menú \d+$/)) {
					if (!conteos[item]) {
						conteos[item] = 0;
						orden.push(item);
					}
					conteos[item]++;
				}
			});
		});

		// Si después de procesar no pudimos extraer nada (raro), devolvemos el original
		if (orden.length === 0) return texto;

		return orden
			.map(item => `${conteos[item]} ${item}`)
			.join('\n');
	},

	imprimirComanda(orden) {
		const ahora = new Date()
		const hora = ahora.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })
		const fecha = ahora.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })

		let encabezadoTipo = ''
		if (orden.tipo === 'LOCAL') encabezadoTipo = `&#x1F4CB; MESA ${orden.id}`
		else if (orden.tipo === 'LLEVAR') encabezadoTipo = `&#x1F6B6; PARA LLEVAR #${orden.id}`
		else encabezadoTipo = `&#x1F6F5; DELIVERY #${orden.id}`

		const esc = (t) => String(t || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')

		const itemsHtml = orden.insumos.map(ins => {
			const estadoTag = ins.estado === 'listo' ? ' <span style="color:#2d8a2d">[LISTO]</span>' : ''
			let html = `<div class="item"><span class="cant">${ins.cantidad}x</span> <span class="nombre">${esc(ins.nombre)}</span>${estadoTag}</div>`
			if (ins.caracteristicas && ins.caracteristicas.trim()) {
				html += `<div class="carac-instruccion" style="margin-left:8px; border-left:3px solid #000; padding-left:8px; font-size:14px; margin-bottom:4px;"> ${esc(ins.caracteristicas)}</div>`
			}
			if (ins.resumenCombo && ins.resumenCombo.trim()) {
				html += `<div class="carac" style="white-space:pre-line;border-left:3px solid #000;padding-left:8px;margin-top:3px;font-weight:bold;font-size:16px;background:#f9f9f9;">${esc(this.formatearResumenCombo(ins.resumenCombo))}</div>`
			}
			return html
		}).join('')

		const ventana = window.open('', '_blank', 'width=420,height=640')
		if (!ventana) {
			alert('El navegador bloqueó la ventana. Permití las ventanas emergentes.')
			return
		}
		ventana.document.write(`<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comanda</title>
    <style>
    @page { size: 80mm auto; margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: 'Courier New', Courier, monospace; 
      font-size: 12px; 
      width: 72mm; 
      margin: 0 auto;
      padding: 4mm 2mm;
    }
    h2 { text-align: center; font-size: 16px; letter-spacing: 2px; margin-bottom: 3px; }
    .linea { border-top: 1px dashed #000; margin: 6px 0; }
    .tipo { text-align: center; font-size: 20px; font-weight: bold; margin: 5px 0 3px; }
    .cliente { text-align: center; font-size: 12px; margin-bottom: 2px; }
    .meta { text-align: center; font-size: 11px; color: #555; margin-bottom: 4px; }
    .item { font-size: 16px; margin: 5px 0 2px; }
    .cant { font-weight: bold; }
    .nombre { font-weight: bold; text-transform: uppercase; }
    .carac { font-size: 12px; margin-left: 24px; font-style: italic; margin-bottom: 3px; color: #333; }
    .carac-instruccion { text-transform: uppercase; }
    .pie { text-align: center; font-size: 11px; margin-top: 8px; color: #777; }
    </style>
</head>
<body>
    <h2>--- COMANDA ---</h2>
    <div class="linea"></div>
    <div class="tipo">${encabezadoTipo}</div>
    ${orden.cliente && orden.cliente !== 'S/N' ? `<div class="cliente">Cliente: <strong>${orden.cliente}</strong></div>` : ''}
    <div class="meta">${fecha} &bull; ${hora}</div>
    <div class="linea"></div>
    ${itemsHtml}
    <div class="linea"></div>
    <div class="pie">Impreso a las ${hora}</div>
</body>
</html>`)
		ventana.document.close()
		ventana.focus()
		setTimeout(() => { ventana.print() }, 300)
	},
	formatearDinero(monto) {
		if (monto === undefined || monto === null) return 'Bs. 0';
		return 'Bs. ' + Math.round(parseFloat(monto));
	},
	formatearCantidad(valor) {
		if (valor === undefined || valor === null) return '0';
		const n = Number(valor);
		return n.toString(); // Number().toString() ya elimina ceros innecesarios
	}
}

export default Utiles