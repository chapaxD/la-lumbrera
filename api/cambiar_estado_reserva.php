<?php
include_once "encabezado.php";
include_once "funciones.php";
$datos = json_decode(file_get_contents("php://input"));
if (!$datos) {
    exit("No hay datos");
}
$idUsuario = isset($datos->idUsuario) ? $datos->idUsuario : 1;
$resultado = cambiarEstadoReserva($datos->id, $datos->estado, $idUsuario);
echo json_encode($resultado);
