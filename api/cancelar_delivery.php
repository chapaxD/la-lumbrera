<?php
include_once "encabezado.php";
include_once "funciones.php";
$payload = json_decode(file_get_contents("php://input"));

$id = isset($_GET["id"]) ? $_GET["id"] : null;
if ($id === null || $id === '') {
    if (is_object($payload) && isset($payload->id)) $id = $payload->id;
    else $id = $payload;
}

if ($id === null || $id === '') {
    echo json_encode(false);
    exit;
}

$idUsuarioSolicitante = is_object($payload) && isset($payload->idUsuarioSolicitante) ? $payload->idUsuarioSolicitante : null;
$rolSolicitante = is_object($payload) && isset($payload->rolSolicitante) ? $payload->rolSolicitante : null;
$motivo = is_object($payload) && isset($payload->motivo) ? trim($payload->motivo) : null;

if ($rolSolicitante !== 'admin' && $idUsuarioSolicitante !== null && $idUsuarioSolicitante !== '') {
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT idUsuario FROM ordenes_activas WHERE tipo='DELIVERY' AND referencia=?");
    $stmt->execute([(string)$id]);
    $orden = $stmt->fetch();
    if ($orden && (string)$orden->idUsuario !== (string)$idUsuarioSolicitante) {
        echo json_encode(false);
        exit;
    }
}

$resultado = cancelarDelivery($id, $idUsuarioSolicitante, $motivo);
echo json_encode($resultado);
