<?php
// lib/reportes.php - Gestión de Reportes Operativos

function registrarReporteCocina($payload)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare(
        "INSERT INTO reportes_cocina (idInsumo, nombreInsumo, tipo, nota, idUsuario, fecha) VALUES (?, ?, ?, ?, ?, ?)"
    );
    return $sentencia->execute([
        isset($payload->idInsumo) && $payload->idInsumo !== '' ? $payload->idInsumo : null,
        $payload->nombreInsumo ?? 'Sin especificar',
        $payload->tipo ?? 'OTRO',
        $payload->nota ?? '',
        $payload->idUsuario ?? null,
        date("Y-m-d H:i:s")
    ]);
}

function obtenerReportesCocina()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query(
        "SELECT r.*, u.nombre AS usuarioNombre 
         FROM reportes_cocina r 
         LEFT JOIN usuarios u ON u.id = r.idUsuario 
         WHERE r.resuelto = 0 
         ORDER BY r.fecha DESC"
    );
    return $sentencia->fetchAll();
}

function resolverReporteCocina($id)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE reportes_cocina SET resuelto = 1 WHERE id = ?");
    return $sentencia->execute([(int)$id]);
}
