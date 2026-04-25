import jsPDF from 'jspdf';
import 'jspdf-autotable';
import HttpService from './HttpService';
import Utiles from './Utiles';

class ReportesPdfService {
    async generar(titulo, columnas, datos, subtotalText = "") {
        // Obtenemos los datos del local para membrete
        let datosLocal = null;
        try {
            datosLocal = await HttpService.obtener("obtener_datos_local.php");
        } catch (e) { console.error('Error al obtener membrete'); }

        const doc = new jsPDF();
        let margenSuperior = 14;

        // Membrete
        if (datosLocal) {
            doc.setFontSize(20);
            doc.text(datosLocal.nombre, 14, 22);
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Tel: ${datosLocal.telefono || 'N/A'}`, 14, 28);

            // Si hay logo, idealmente habría que pre-procesar base64 si es de Vue, 
            // pero para ser genéricos y robustos:
            margenSuperior = 35;
        }

        // Título del Reporte
        doc.setFontSize(14);
        doc.setTextColor(40);
        doc.text(titulo, 14, margenSuperior);

        // Fecha de Impresión
        doc.setFontSize(9);
        doc.setTextColor(100);
        doc.text(`Generado el: ${new Date().toLocaleString()}`, 14, margenSuperior + 6);

        // Tabla AutoTable
        doc.autoTable({
            startY: margenSuperior + 10,
            head: [columnas],
            body: datos,
            theme: 'striped',
            headStyles: { fillColor: [41, 128, 185] },
            styles: { fontSize: 9 }
        });

        if (subtotalText) {
            let finalY = doc.lastAutoTable.finalY + 10;
            doc.setFontSize(12);
            doc.setTextColor(0);
            doc.text(subtotalText, 14, finalY);
        }

        // Descarga
        let fileName = titulo.replace(/ /g, '_').toLowerCase() + '.pdf';
        doc.save(fileName);
    }

    async generarCierreCaja(datos) {
        let datosLocal = null;
        try {
            datosLocal = await HttpService.obtener("obtener_datos_local.php");
        } catch (e) { /* sin membrete */ }

        const doc = new jsPDF();
        let y = 14;

        // Membrete
        if (datosLocal) {
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text(datosLocal.nombre || 'Negocio', 14, y + 8);
            doc.setFontSize(9);
            doc.setTextColor(100);
            doc.text(`Tel: ${datosLocal.telefono || ''}  |  ${datosLocal.direccion || ''}`, 14, y + 14);
            y += 20;
        }

        // Título
        doc.setFontSize(15);
        doc.setTextColor(0);
        doc.text('CORTE DE CAJA', 14, y + 8);
        doc.setFontSize(9);
        doc.setTextColor(100);
        doc.text(`Apertura: ${datos.fechaApertura}   |   Cierre: ${datos.fechaCierre}`, 14, y + 15);
        doc.text(`Cerrado por: ${datos.usuarioCierre}`, 14, y + 20);
        y += 28;

        // Resumen de ventas
        doc.autoTable({
            startY: y,
            head: [['Concepto', 'Monto (Bs.)']],
            body: [
                ['Fondo Inicial', Math.round(Number(datos.montoApertura))],
                ['Ventas — Efectivo', Math.round(Number(datos.ventasEfectivo))],
                ['Ventas — Tarjeta / Transferencia', Math.round(Number(datos.ventasTarjeta))],
                ['Ventas — QR', Math.round(Number(datos.ventasQR))],
                ['TOTAL VENTAS', Math.round(Number(datos.ventasTotales))],
                ['Gastos / Retiros', '-' + Math.round(Number(datos.gastosTotal))],
            ],
            theme: 'grid',
            headStyles: { fillColor: [41, 128, 185] },
            bodyStyles: { fontSize: 10 },
            columnStyles: { 1: { halign: 'right' } },
            didParseCell(info) {
                if (info.row.index === 4) {
                    info.cell.styles.fontStyle = 'bold';
                    info.cell.styles.fillColor = [235, 245, 255];
                }
            }
        });

        y = doc.lastAutoTable.finalY + 8;

        // Efectivo esperado vs declarado
        const esperado = Number(datos.montoApertura) + Number(datos.ventasEfectivo) - Number(datos.gastosTotal);
        const declarado = Number(datos.montoCierre);
        const diferencia = declarado - esperado;
        const colorDif = diferencia >= 0 ? [39, 174, 96] : [192, 57, 43];

        doc.autoTable({
            startY: y,
            head: [['Efectivo Calculado', 'Efectivo Declarado', 'Diferencia']],
            body: [[
                `Bs. ${Math.round(esperado)}`,
                `Bs. ${Math.round(declarado)}`,
                `${diferencia >= 0 ? '+' : ''}Bs. ${Math.round(diferencia)}`
            ]],
            theme: 'grid',
            headStyles: { fillColor: [52, 73, 94] },
            bodyStyles: { fontSize: 11, fontStyle: 'bold' },
            columnStyles: {
                0: { halign: 'center' },
                1: { halign: 'center' },
                2: { halign: 'center', textColor: colorDif }
            }
        });

        y = doc.lastAutoTable.finalY + 10;

        // Tabla de gastos (si hay)
        if (datos.gastos && datos.gastos.length > 0) {
            doc.setFontSize(11);
            doc.setTextColor(40);
            doc.text('Detalle de Gastos / Retiros', 14, y);
            y += 2;
            doc.autoTable({
                startY: y,
                head: [['Concepto', 'Monto (Bs.)', 'Hora']],
                body: datos.gastos.map(g => [
                    g.concepto,
                    Math.round(Number(g.monto)),
                    g.fecha ? g.fecha.substring(11, 16) : ''
                ]),
                theme: 'striped',
                headStyles: { fillColor: [192, 57, 43] },
                bodyStyles: { fontSize: 9 },
                columnStyles: { 1: { halign: 'right' } }
            });
        }

        doc.save(`corte_caja_${datos.fechaCierre.replace(/[: ]/g, '-')}.pdf`);
    }

    async imprimirCierreCaja80mm(datos) {
        let datosLocal = null;
        try {
            datosLocal = await HttpService.obtener("obtener_datos_local.php");
        } catch (e) { /* sin membrete */ }

        const nombreLocal = (datosLocal && datosLocal.nombre) || 'Restaurante';
        const telfLocal = (datosLocal && datosLocal.telefono) || '';
        const dirLocal = (datosLocal && datosLocal.direccion) || '';

        const esperado = Number(datos.montoApertura) + Number(datos.ventasEfectivo) - Number(datos.gastosTotal);
        const declarado = Number(datos.montoCierre);
        const diferencia = declarado - esperado;

        const formatNum = (n) => Math.round(Number(n || 0));

        let gastosHtml = '';
        if (datos.gastos && datos.gastos.length > 0) {
            gastosHtml = `
                <div class="separador"></div>
                <div class="seccion-titulo">DETALLE DE GASTOS</div>
                <table>
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${datos.gastos.map(g => `
                            <tr>
                                <td>${g.concepto}</td>
                                <td class="right">Bs. ${formatNum(g.monto)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    @page { size: 80mm auto; margin: 0; }
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body { 
                        font-family: 'Courier New', Courier, monospace; 
                        font-size: 12px; 
                        width: 72mm; 
                        margin: 0 auto;
                        padding: 4mm 2mm;
                    }
                    .center { text-align: center; }
                    .right { text-align: right; }
                    .bold { font-weight: bold; }
                    h2 { font-size: 16px; margin: 2px 0; text-transform: uppercase; }
                    .separador { border-top: 1px dashed #000; margin: 4px 0; }
                    .info-cierre { font-size: 11px; margin-bottom: 6px; }
                    .seccion-titulo { font-weight: bold; margin: 6px 0 2px 0; text-align: center; border-bottom: 1px solid #000; }
                    table { width: 100%; border-collapse: collapse; margin: 4px 0; }
                    th { border-bottom: 1px solid #000; text-align: left; padding: 2px 0; }
                    td { padding: 2px 0; vertical-align: top; }
                    .fila { display: flex; justify-content: space-between; margin: 2px 0; }
                    .total-box { border: 1px solid #000; padding: 4px; margin-top: 6px; }
                    .diferencia { font-size: 14px; margin-top: 4px; border-top: 1px double #000; padding-top: 2px; }
                </style>
            </head>
            <body>
                <div class="center">
                    <h2>${nombreLocal}</h2>
                    <p>${dirLocal}</p>
                    <p>Tel: ${telfLocal}</p>
                    <div class="separador"></div>
                    <h3 style="margin:4px 0">CORTE DE CAJA</h3>
                </div>

                <div class="info-cierre">
                    <div><b>Apertura:</b> ${datos.fechaApertura}</div>
                    <div><b>Cierre:</b>   ${datos.fechaCierre}</div>
                    <div><b>Usuario:</b>  ${datos.usuarioCierre}</div>
                </div>

                <div class="seccion-titulo">RESUMEN DE CAJA</div>
                <div class="fila"><span>Fondo Inicial:</span> <span class="bold">Bs. ${formatNum(datos.montoApertura)}</span></div>
                <div class="fila"><span>Ventas Efectivo:</span> <span class="bold">Bs. ${formatNum(datos.ventasEfectivo)}</span></div>
                <div class="fila"><span>Ventas Tarjeta:</span> <span class="bold">Bs. ${formatNum(datos.ventasTarjeta)}</span></div>
                <div class="fila"><span>Ventas QR:</span> <span class="bold">Bs. ${formatNum(datos.ventasQR)}</span></div>
                <div class="fila"><span>Gastos/Retiros:</span> <span class="bold">-Bs. ${formatNum(datos.gastosTotal)}</span></div>
                
                <div class="fila" style="margin-top:4px; border-top:1px solid #000; padding-top:2px">
                    <span class="bold">TOTAL VENTAS:</span>
                    <span class="bold">Bs. ${formatNum(datos.ventasTotales)}</span>
                </div>

                ${gastosHtml}

                <div class="total-box">
                    <div class="fila"><span>Efectivo Calculado:</span> <span>Bs. ${formatNum(esperado)}</span></div>
                    <div class="fila"><span>Efectivo Declarado:</span> <span>Bs. ${formatNum(declarado)}</span></div>
                    <div class="fila diferencia bold">
                        <span>DIFERENCIA:</span>
                        <span>Bs. ${formatNum(diferencia)}</span>
                    </div>
                </div>

                <div style="margin-top:20px; text-align:center; font-size:10px;">
                    --- FIN DEL REPORTE ---
                </div>
            </body>
            </html>
        `;

        const ventana = window.open('', '_blank', 'width=400,height=600');
        if (!ventana) {
            alert('Por favor, permite las ventanas emergentes para imprimir.');
            return;
        }
        ventana.document.write(html);
        ventana.document.close();
        ventana.focus();
        setTimeout(() => {
            ventana.print();
            ventana.close();
        }, 500);
    }
}

export default new ReportesPdfService();
