<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";

$filtros = json_decode(file_get_contents("php://input"));
if (!$filtros) $filtros = (object)[];

echo json_encode(obtenerCancelaciones($filtros));
