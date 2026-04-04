<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";

$datos = json_decode(file_get_contents("php://input"));
if (!$datos || !isset($datos->id)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID requerido']);
    exit;
}

$ok = anularFactura((int)$datos->id);
echo json_encode(['ok' => (bool)$ok]);
