<?php
include_once "encabezado.php";
include_once "funciones.php";
$filtros = json_decode(file_get_contents("php://input"));
$fechaInicio = (isset($filtros->inicio)) ? $filtros->inicio : null;
$fechaFin = (isset($filtros->fin)) ? $filtros->fin : null;
echo json_encode(obtenerHistorialStock($fechaInicio, $fechaFin));

