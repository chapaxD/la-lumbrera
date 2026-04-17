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

$fechaInicioHora = (!empty($filtros->hora->inicio)) ? $filtros->hora->inicio : date("Y-m-d");
$fechaFinHora = (!empty($filtros->hora->fin)) ? $filtros->hora->fin : date("Y-m-d");

$fechaInicioUsuarios = (!empty($filtros->usuarios->inicio)) ? $filtros->usuarios->inicio : date("Y-m-d");
$fechaFinUsuarios = (!empty($filtros->usuarios->fin)) ? $filtros->usuarios->fin : date("Y-m-d");

$ventasDiasSemana = obtenerVentasDiasSemana();
$ventasPorHora = obtenerVentasPorHora($fechaInicioHora, $fechaFinHora);
$ventasMeses = obtenerVentasPorMeses($filtros->anio ?? date('Y'));
$ventasUsuario = obtenerVentasUsuario($fechaInicioUsuarios, $fechaFinUsuarios);
$insumosMasVendidos = obtenerInsumosMasVendidos($filtros->limite ?? 5);
$totalesPorMesa = obtenerTotalesPorMesa($filtros->limite ?? 5);
$alertasStock = obtenerInsumosBajoStock();

// Consolidar las métricas del día en 1 sola query (ahorra 2 round-trips a TiDB)
$hoy = date('Y-m-d');
$bdDash = conectarBaseDatos();
$stmtDash = $bdDash->prepare("
    SELECT
        IFNULL(SUM(CASE WHEN fecha >= ? AND fecha < DATE_ADD(?, INTERVAL 1 DAY) THEN total ELSE 0 END), 0) AS totalVentasHoy,
        COUNT(CASE WHEN fecha >= ? AND fecha < DATE_ADD(?, INTERVAL 1 DAY) THEN 1 END)                    AS cantidadHoy,
        IFNULL(SUM(total), 0)                                                                             AS totalVentasGeneral
    FROM ventas
");
$stmtDash->execute([$hoy, $hoy, $hoy, $hoy]);
$metricas = $stmtDash->fetch(PDO::FETCH_OBJ);

$totalDia    = (float) $metricas->totalVentasHoy;
$cantidadDia = (int)   $metricas->cantidadHoy;
$totalVentas = (float) $metricas->totalVentasGeneral;

$cartas = [
    "totalVentasDia"      => $totalDia,
    "cantidadVentasDia"   => $cantidadDia,
    "ticketPromedio"      => ($cantidadDia > 0) ? round($totalDia / $cantidadDia, 2) : 0,
    "numeroUsuarios"      => obtenerNumeroUsuarios(),
    "numeroInsumos"       => obtenerNumeroInsumos(),
    "totalVentas"         => $totalVentas,
    "numeroMesasOcupadas" => obtenerNumeroMesasOcupadas(),
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