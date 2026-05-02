<?php
include_once "encabezado.php";
include_once "funciones.php";

$payload = json_decode(file_get_contents("php://input"));
if (!$payload || !isset($payload->tipo) || !isset($payload->id)) {
    http_response_code(400);
    echo json_encode(false);
    exit;
}

$tipo = $payload->tipo;
$id   = $payload->id;

// LLEVAR se almacena con tipo='DELIVERY' en ordenes_activas
$tipoDb = ($tipo === 'LLEVAR') ? 'DELIVERY' : $tipo;

$bd = conectarBaseDatos();
$stmt = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo=? AND referencia=?");
$stmt->execute([$tipoDb, (string)$id]);
$orden = $stmt->fetch();

if (!$orden) {
    echo json_encode(false);
    exit;
}

// Eliminar la orden (CASCADE borra items_orden)
$ok = $bd->prepare("DELETE FROM ordenes_activas WHERE id=?")->execute([$orden->id]);
echo json_encode($ok);
