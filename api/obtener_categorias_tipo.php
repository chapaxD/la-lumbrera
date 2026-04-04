<?php
include_once "encabezado.php";
$input = json_decode(file_get_contents("php://input"));
$tipo = is_object($input) && isset($input->tipo) ? $input->tipo : $input;
if (!$tipo || !is_string($tipo)) {
    echo json_encode([]);
    exit;
}

include_once "funciones.php";

$resultado = obtenerCategoriasPorTipo($tipo);
echo json_encode($resultado);
