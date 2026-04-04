<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";
$resultado = obtenerHistorialCajas();
echo json_encode($resultado);
