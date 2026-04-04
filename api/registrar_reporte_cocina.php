<?php
include_once "encabezado.php";
include_once "funciones.php";

$payload = json_decode(file_get_contents("php://input"));
$resultado = registrarReporteCocina($payload);
echo json_encode(["resultado" => $resultado]);
