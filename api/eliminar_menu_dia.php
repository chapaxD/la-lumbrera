<?php
include_once "encabezado.php";
include_once "funciones.php";
$datos = json_decode(file_get_contents("php://input"));
if (!$datos) exit;
$resultado = eliminarDelMenuDia($datos->idInsumo, $datos->diaSemana);
echo json_encode($resultado);
