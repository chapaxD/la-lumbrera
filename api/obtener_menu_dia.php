<?php
include_once "encabezado.php";
include_once "funciones.php";
$dia = json_decode(file_get_contents("php://input"));
if ($dia === null) exit;
$menu = obtenerMenuDia($dia);
echo json_encode($menu);
