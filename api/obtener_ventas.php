<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";

try {
    $filtros = json_decode(file_get_contents("php://input"));
    $fechaInicio = (isset($filtros->inicio)) ? $filtros->inicio : date("Y-m-d");
    $fechaFin = (isset($filtros->fin)) ? $filtros->fin : date("Y-m-d");
    $idUsuario =  (isset($filtros->idUsuario)) ? $filtros->idUsuario : "";
    $limite = (isset($filtros->limite) && $filtros->limite > 0) ? (int)$filtros->limite : 20;
    $pagina = (isset($filtros->pagina) && $filtros->pagina > 0) ? (int)$filtros->pagina : 1;
    $offset = ($pagina - 1) * $limite;

    $resumen = contarVentas($fechaInicio, $fechaFin, $idUsuario);
    $ventasCount = obtenerVentas($fechaInicio, $fechaFin, $idUsuario, $limite, $offset);
    $ventasPorUsuario = obtenerVentasPorUsuario($fechaInicio, $fechaFin);
    $resumenPorDia = obtenerResumenVentasPorDia($fechaInicio, $fechaFin);
    $topInsumos = obtenerTopInsumosPorPeriodo($fechaInicio, $fechaFin, 10);

    $usuarios = obtenerUsuarios();

    foreach($ventasCount as &$venta){
        $venta->insumos = obtenerInsumosVenta($venta->id);
    }
    unset($venta);

    echo json_encode([
        "ventas" => $ventasCount,
        "totalRegistros" => (int)$resumen->total,
        "totalPeriodo" => (float)$resumen->totalDinero,
        "ventasPorUsuario" => $ventasPorUsuario,
        "resumenPorDia" => $resumenPorDia,
        "topInsumos" => $topInsumos,
        "usuarios" => $usuarios
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

