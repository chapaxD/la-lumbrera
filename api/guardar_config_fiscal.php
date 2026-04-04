<?php
include_once "encabezado.php";
include_once "funciones.php";

$datos = json_decode(file_get_contents("php://input"));

if (!$datos) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

$bd = conectarBaseDatos();

$existe = $bd->query("SELECT COUNT(*) FROM informacion_negocio")->fetchColumn();

if ((int)$existe > 0) {
    $stmt = $bd->prepare(
        "UPDATE informacion_negocio SET
            nit_emisor           = ?,
            razon_social         = ?,
            actividad            = ?,
            ciudad               = ?,
            direccion            = ?,
            telefono             = ?,
            num_autorizacion     = ?,
            fecha_limite_emision = ?"
    );
    $ok = $stmt->execute([
        $datos->nit           ?? null,
        $datos->razonSocial   ?? null,
        $datos->actividad     ?? null,
        $datos->ciudad        ?? null,
        $datos->direccion     ?? null,
        $datos->telefono      ?? null,
        $datos->numAutorizacion ?? null,
        $datos->fechaLimite   ?? null,
    ]);
} else {
    $stmt = $bd->prepare(
        "INSERT INTO informacion_negocio
            (nit_emisor, razon_social, actividad, ciudad, direccion, telefono, num_autorizacion, fecha_limite_emision)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ok = $stmt->execute([
        $datos->nit           ?? null,
        $datos->razonSocial   ?? null,
        $datos->actividad     ?? null,
        $datos->ciudad        ?? null,
        $datos->direccion     ?? null,
        $datos->telefono      ?? null,
        $datos->numAutorizacion ?? null,
        $datos->fechaLimite   ?? null,
    ]);
}

$bd = null;
echo json_encode(["ok" => $ok]);
