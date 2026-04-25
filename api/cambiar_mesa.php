<?php
include_once "encabezado.php";
include_once "funciones.php";

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'POST') {
    http_response_code(405);
    exit;
}

$json = file_get_contents('php://input');
$datos = json_decode($json);

if (!$datos || !isset($datos->tipoActual) || !isset($datos->referenciaActual) || !isset($datos->nuevaMesa)) {
    http_response_code(400);
    echo json_encode(["error" => "DATOS_INCOMPLETOS"]);
    exit;
}

$resultado = cambiarMesa($datos->tipoActual, $datos->referenciaActual, $datos->nuevaMesa);
echo json_encode($resultado);
