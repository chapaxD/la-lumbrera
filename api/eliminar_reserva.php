<?php
include_once "encabezado.php";
include_once "funciones.php";
$idReserva = json_decode(file_get_contents("php://input"));
if (!$idReserva) {
    exit("No hay id");
}
$resultado = eliminarReserva($idReserva);
echo json_encode($resultado);
