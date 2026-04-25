<?php
// lib/cancelaciones.php - Gestión de Cancelaciones

function obtenerCancelaciones($filtros)
{
    $bd = conectarBaseDatos();

    $condiciones = [];
    $params = [];

    if (!empty($filtros->desde)) {
        $condiciones[] = "c.fecha >= ?";
        $params[] = $filtros->desde . " 00:00:00";
    }
    if (!empty($filtros->hasta)) {
        $condiciones[] = "c.fecha <= ?";
        $params[] = $filtros->hasta . " 23:59:59";
    }
    if (!empty($filtros->idUsuario)) {
        $condiciones[] = "c.idUsuario = ?";
        $params[] = $filtros->idUsuario;
    }

    $where = count($condiciones) > 0 ? "WHERE " . implode(" AND ", $condiciones) : "";

    $sql = "SELECT c.id, c.tipo, c.referencia, c.motivo, c.fecha,
                   IFNULL(u.nombre, 'Desconocido') AS usuario
            FROM cancelaciones c
            LEFT JOIN usuarios u ON u.id = c.idUsuario
            $where
            ORDER BY c.fecha DESC";

    $sentencia = $bd->prepare($sql);
    $sentencia->execute($params);
    return $sentencia->fetchAll();
}
