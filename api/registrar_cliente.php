<?php
include_once "encabezado.php";
include_once "verificar_token.php";

$data = json_decode(file_get_contents("php://input"));
if (!$data) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Sin datos']));
}

include_once "funciones.php";

$resultado = registrarCliente($data);
echo json_encode($resultado);
