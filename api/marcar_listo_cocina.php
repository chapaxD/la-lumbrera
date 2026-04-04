<?php
set_exception_handler(function($e) {
    http_response_code(500);
    header('content-type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
    exit;
});
include_once "encabezado.php";
include_once "funciones.php";

$payload = json_decode(file_get_contents("php://input"));
$resultado = marcarItemListoCocina($payload);
echo json_encode(["resultado" => $resultado]);
