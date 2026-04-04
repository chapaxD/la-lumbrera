<?php
include_once "encabezado.php";
include_once "funciones.php";

$idUsuario = isset($_GET["idUsuario"]) ? $_GET["idUsuario"] : null;
$rol = isset($_GET["rol"]) ? $_GET["rol"] : null;

$resultado = obtenerMesas($idUsuario, $rol);

echo json_encode($resultado);
