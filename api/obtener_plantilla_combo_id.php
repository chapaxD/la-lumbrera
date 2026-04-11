<?php
include_once 'encabezado.php';
include_once 'funciones.php';

$payload = json_decode(file_get_contents('php://input'));
$id = isset($payload->id) ? (int) $payload->id : 0;
$p = obtenerPlantillaComboPorId($id);
echo json_encode($p ?: null);
