<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";

$filtros = json_decode(file_get_contents("php://input"));
if (!$filtros) $filtros = new stdClass();

echo json_encode(obtenerFacturas($filtros));
