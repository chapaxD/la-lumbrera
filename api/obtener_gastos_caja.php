<?php
include_once "encabezado.php";
include_once "verificar_token.php";
$datos = json_decode(file_get_contents("php://input"));
if (!$datos || !isset($datos->idCaja)) {
    http_response_code(400);
    exit;
}
include_once "funciones.php";
echo json_encode(obtenerGastosDeCaja((int)$datos->idCaja));
