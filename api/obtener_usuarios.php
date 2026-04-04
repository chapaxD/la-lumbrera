<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";

$usuarios = obtenerusuarios();

echo json_encode($usuarios);