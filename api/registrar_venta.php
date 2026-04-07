<?php
include_once "encabezado.php";
$venta = json_decode(file_get_contents("php://input"));
if (!$venta) {
    http_response_code(500);
    exit;
}

include_once "funciones.php";

try {
    $resultado = registrarVenta($venta);
    echo json_encode($resultado);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage(),
        "archivo" => basename($e->getFile()),
        "linea" => $e->getLine()
    ]);
}


