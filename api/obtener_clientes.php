<?php
include_once "encabezado.php";
include_once "verificar_token.php";
include_once "funciones.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$clientes = obtenerClientes($q);
echo json_encode($clientes);
