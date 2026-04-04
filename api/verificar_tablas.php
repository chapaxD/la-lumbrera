<?php
include_once "encabezado.php";
include_once "funciones.php";
require_once __DIR__ . '/db_config.php';

$conexion = conectarBaseDatos();
$sentencia  = $conexion->query("SELECT COUNT(*) AS resultado FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
$resultado = $sentencia->fetchAll();
$conexion = null;
echo json_encode($resultado[0]);