<?php
include_once "encabezado.php";
include_once "funciones.php";
$filtros = json_decode(file_get_contents("php://input"));

$insumos = obtenerInsumos($filtros);

if (isset($filtros->ajusteStockVenta) && $filtros->ajusteStockVenta) {
    $bd = conectarBaseDatos();
    ajustarStockDisponibleVentaEnFilas($bd, $insumos);
}

echo json_encode($insumos);