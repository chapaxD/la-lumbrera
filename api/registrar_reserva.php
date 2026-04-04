<?php
include_once "encabezado.php";
include_once "funciones.php";
$reserva = json_decode(file_get_contents("php://input"));
if (!$reserva) {
    exit("No hay datos");
}
$resultado = registrarReserva($reserva);
echo json_encode($resultado);
