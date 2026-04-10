<?php
include_once "encabezado.php";
include_once "funciones.php";
$payload = json_decode(file_get_contents("php://input"));
if ($payload === null) {
    exit;
}
$dia = is_object($payload) && isset($payload->dia) ? $payload->dia : $payload;
$ajustar = is_object($payload) && !empty($payload->ajusteStockVenta);
if ($dia === null || $dia === '') {
    exit;
}
$menu = obtenerMenuDia($dia, $ajustar);
echo json_encode($menu);
