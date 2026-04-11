<?php
include_once 'encabezado.php';
include_once 'funciones.php';

$payload = json_decode(file_get_contents('php://input'));
$id = isset($payload->id) ? (int) $payload->id : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

echo json_encode(['ok' => eliminarPlantillaCombo($id)]);
