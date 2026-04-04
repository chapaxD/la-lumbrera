<?php
include_once "encabezado.php";
include_once "funciones.php";

$bd = conectarBaseDatos();
$stmt = $bd->query("SELECT id, nombre FROM usuarios WHERE rol = 'mesero' ORDER BY nombre ASC");
echo json_encode($stmt->fetchAll());
