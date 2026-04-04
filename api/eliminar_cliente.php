<?php
include_once "encabezado.php";
include_once "verificar_token.php";

$id = json_decode(file_get_contents("php://input"));
if (!$id) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Sin ID']));
}

include_once "funciones.php";

$resultado = eliminarCliente($id);
echo json_encode($resultado);
