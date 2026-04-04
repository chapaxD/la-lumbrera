<?php
include_once "encabezado.php";
$payload = json_decode(file_get_contents("php://input"));
if (!$payload) {
    http_response_code(500);
    exit;
}
include_once "funciones.php";
$resultado = registrarMerma($payload);
echo json_encode($resultado);
