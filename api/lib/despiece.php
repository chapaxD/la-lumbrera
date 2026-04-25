<?php
// lib/despiece.php - Gestión de Despiece de Carne

function obtenerDespieceParrilla($fechaInicio = null, $fechaFin = null) {
    $bd = conectarBaseDatos();
    $sql = "SELECT * FROM despiece_parrilla_lote ";
    $params = [];
    if ($fechaInicio && $fechaFin) {
        $sql .= "WHERE DATE(fecha) >= ? AND DATE(fecha) <= ? ";
        $params[] = $fechaInicio;
        $params[] = $fechaFin;
    } elseif ($fechaInicio) {
        $sql .= "WHERE DATE(fecha) = ? ";
        $params[] = $fechaInicio;
    }
    $sql .= "ORDER BY fecha DESC, id DESC";
    $sentencia = $bd->prepare($sql);
    $sentencia->execute($params);
    $lotes = $sentencia->fetchAll(PDO::FETCH_ASSOC);
    if (!$lotes) {
        return [];
    }
    $ids = array_column($lotes, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stLineas = $bd->prepare(
        "SELECT * FROM despiece_parrilla_linea WHERE id_lote IN ($placeholders) ORDER BY id_lote ASC, id ASC"
    );
    $stLineas->execute($ids);
    $todasLineas = $stLineas->fetchAll(PDO::FETCH_ASSOC);
    $porLote = [];
    foreach ($todasLineas as $ln) {
        $idLote = (int) $ln['id_lote'];
        if (!isset($porLote[$idLote])) {
            $porLote[$idLote] = [];
        }
        $porLote[$idLote][] = $ln;
    }
    foreach ($lotes as &$l) {
        $lid = (int) $l['id'];
        $l['lineas'] = isset($porLote[$lid]) ? $porLote[$lid] : [];
    }
    unset($l);
    return $lotes;
}

function unidadesStockDesdeLineaDespiece($porciones250, $porciones350, $porcionesGenericas = 0) {
    $gen = (int) $porcionesGenericas;
    if ($gen > 0) return $gen;
    return (int) $porciones250 + (int) $porciones350;
}

function normalizarFechaDespieceMysql($fecha) {
    $fecha = str_replace('T', ' ', trim((string) $fecha));
    if ($fecha === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fecha)) {
        $fecha .= ':00';
    }
    return $fecha;
}
