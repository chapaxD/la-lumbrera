<?php
include_once "encabezado.php";
$payload = json_decode(file_get_contents("php://input"));
if (!$payload) {
    http_response_code(500);
    exit;
}

include_once "funciones.php";

$idMesa = (is_object($payload) && isset($payload->idMesa)) ? $payload->idMesa : $payload;
$idUsuarioSolicitante = is_object($payload) && isset($payload->idUsuarioSolicitante) ? $payload->idUsuarioSolicitante : null;
$rolSolicitante = is_object($payload) && isset($payload->rolSolicitante) ? $payload->rolSolicitante : null;
$motivo = is_object($payload) && isset($payload->motivo) ? trim($payload->motivo) : null;

if ($rolSolicitante !== 'admin' && $idUsuarioSolicitante !== null && $idUsuarioSolicitante !== '') {
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT idUsuario FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $stmt->execute([(string)$idMesa]);
    $orden = $stmt->fetch();
    if ($orden && (string)$orden->idUsuario !== (string)$idUsuarioSolicitante) {
        echo json_encode(false);
        exit;
    }
}

$resultado = cancelarMesa($idMesa, $idUsuarioSolicitante, $motivo);

echo json_encode($resultado);
