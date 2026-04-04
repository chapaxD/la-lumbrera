<?php
include_once "encabezado.php";
include_once "funciones.php";
$datos = json_decode(file_get_contents("php://input"));
if (!$datos) {
    exit("No hay datos");
}
$resultado = cambiarEstadoReserva($datos->id, $datos->estado);
echo json_encode($resultado);
