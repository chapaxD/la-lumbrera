<?php
include_once "encabezado.php";
include_once "funciones.php";
$delivery = json_decode(file_get_contents("php://input"));
if (!$delivery) {
    exit("No hay datos");
}
$resultado = ocuparDelivery($delivery);
echo json_encode($resultado);
