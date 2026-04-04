<?php
set_exception_handler(function($e) {
    http_response_code(500);
    header('content-type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
    exit;
});
include_once "encabezado.php";
include_once "funciones.php";

$filtros = json_decode(file_get_contents("php://input"));

$fechaInicioHora = ($filtros->hora->inicio !== "") ? $filtros->hora->inicio : date("Y-m-d");
$fechaFinHora = ($filtros->hora->fin !== "") ? $filtros->hora->fin : date("Y-m-d");

$fechaInicioUsuarios = ($filtros->usuarios->inicio !== "") ? $filtros->usuarios->inicio : date("Y-m-d");
$fechaFinUsuarios = ($filtros->usuarios->fin !== "") ? $filtros->usuarios->fin : date("Y-m-d");

$ventasDiasSemana = obtenerVentasDiasSemana();
$ventasPorHora = obtenerVentasPorHora($fechaInicioHora, $fechaFinHora);
$ventasMeses = obtenerVentasPorMeses($filtros->anio);
$ventasUsuario = obtenerVentasUsuario($fechaInicioUsuarios, $fechaFinUsuarios);
$insumosMasVendidos = obtenerInsumosMasVendidos($filtros->limite);
$totalesPorMesa = obtenerTotalesPorMesa();
$alertasStock = obtenerInsumosBajoStock();

$totalDia = obtenerVentasDelDia();
$cantidadDia = cantidadVentasDia();
$cartas = [
    "totalVentasDia" =>  $totalDia,
    "cantidadVentasDia" => $cantidadDia,
    "ticketPromedio" => ($cantidadDia > 0) ? round($totalDia / $cantidadDia, 2) : 0,
    "numeroUsuarios" =>  obtenerNumeroUsuarios(),
    "numeroInsumos" =>  obtenerNumeroInsumos(),
    "totalVentas" =>  obtenerTotalVentas(),
    "numeroMesasOcupadas" =>  obtenerNumeroMesasOcupadas(),
    ];

echo json_encode(
	[
		"ventasDiasSemana" => $ventasDiasSemana,
        "ventasHora" => $ventasPorHora,
        "ventasMeses" => $ventasMeses,
        "ventasUsuario" => $ventasUsuario,
        "insumosMasVendidos" => $insumosMasVendidos,
        "totalesPorMesa" => $totalesPorMesa,
        "alertasStock" => $alertasStock,
        "cartas" => $cartas
	]);