<?php
include_once 'encabezado.php';
include_once 'funciones.php';

$payload = json_decode(file_get_contents('php://input'));
if (!$payload) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

try {
    $id = guardarPlantillaCombo($payload);
    if ($id === false) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Nombre requerido']);
        exit;
    }
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
