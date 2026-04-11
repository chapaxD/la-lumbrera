<?php
include_once "funciones.php";
header('Content-Type: application/json');

$fechaInicio = isset($_GET['fechaInicio']) ? $_GET['fechaInicio'] : null;
$fechaFin = isset($_GET['fechaFin']) ? $_GET['fechaFin'] : null;

$registros = obtenerDespieceParrilla($fechaInicio, $fechaFin);
echo json_encode($registros, JSON_UNESCAPED_UNICODE);
