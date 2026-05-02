<?php
// lib/cocina.php - Gestión de Cocina y Servicio

function marcarItemListoCocina($payload)
{
    $bd = conectarBaseDatos();
    $id = (int)$payload->id;
    $estado = (string)($payload->estado ?? 'listo');
    $stmt = $bd->prepare("UPDATE items_orden SET estado=? WHERE id=?");
    return $stmt->execute([$estado, $id]);
}

function entregarItem($payload)
{
    $bd = conectarBaseDatos();
    $id = (int)$payload->id;
    $stmt = $bd->prepare("UPDATE items_orden SET estado='entregado' WHERE id=?");
    return $stmt->execute([$id]);
}

function obtenerInsumosPendientes($estacion = 'cocina')
{
    $bd = conectarBaseDatos();
    if ($estacion === 'parrilla') {
        $filtro = "c.nombre = 'Carnes' AND io.estado = 'pendiente'";
    } else {
        // Para cocina: todo lo que no sea Carnes que esté pendiente, 
        // MAS las Carnes que tengan el acompañamiento pendiente (aunque la carne ya esté lista en parrilla)
        $filtro = "((c.nombre != 'Carnes' OR c.nombre IS NULL) AND io.estado = 'pendiente') 
                   OR (c.nombre = 'Carnes' AND io.acompanamiento_listo = 0)";
    }

    $sql = "
        SELECT io.*, oa.referencia AS mesa, oa.tipo AS tipoOrden, IFNULL(c.nombre, 'NO DEFINIDA') AS nombreCategoria
        FROM items_orden io
        INNER JOIN ordenes_activas oa ON oa.id = io.idOrden
        LEFT JOIN insumos i ON i.id = io.idInsumo
        LEFT JOIN categorias c ON c.id = i.categoria
        WHERE $filtro
        ORDER BY oa.created_at ASC, io.id ASC
    ";
    $stmt = $bd->query($sql);
    return $stmt->fetchAll();
}

function marcarAcompanamientoListo($idItem)
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("UPDATE items_orden SET acompanamiento_listo = 1 WHERE id = ?");
    return $stmt->execute([$idItem]);
}
