<?php
include_once "encabezado.php";
$insumo = json_decode(file_get_contents("php://input"));
if ($insumo === null || $insumo === '') {
    http_response_code(500);
    exit;
}

include_once "funciones.php";

// $insumo puede llegar como string plano ("cua") o como objeto con ->insumo
$termino = is_object($insumo) ? $insumo->insumo : $insumo;
$resultado = obtenerInsumosPorNombre($termino);
echo json_encode($resultado);
