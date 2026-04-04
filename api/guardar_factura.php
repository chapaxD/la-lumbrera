<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";

$datos = json_decode(file_get_contents("php://input"));
if (!$datos) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

echo json_encode(guardarFactura($datos));
