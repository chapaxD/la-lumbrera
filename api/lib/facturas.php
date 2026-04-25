<?php
// lib/facturas.php - Gestión de Facturación

function guardarFactura($datos)
{
    $bd = conectarBaseDatos();

    // Verificar que el número no esté ya usado
    $check = $bd->prepare("SELECT id FROM facturas WHERE numero = ?");
    $check->execute([$datos->numero]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'El número de factura ' . $datos->numero . ' ya existe en la base de datos.'];
    }

    $bd->beginTransaction();
    try {
        $stmt = $bd->prepare("
            INSERT INTO facturas
                (numero, fechaHora, nitComprador, nombreComprador, codigoControl,
                 subtotal, descuentos, baseCredito, iva, total, nota, idVenta, idUsuario, metodoPago)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $datos->numero,
            $datos->fechaHora,
            $datos->nitComprador ?? '99001',
            $datos->nombreComprador ?? 'SIN NOMBRE',
            $datos->codigoControl ?? null,
            $datos->subtotal,
            $datos->descuentos,
            $datos->baseCredito,
            $datos->iva,
            $datos->total,
            $datos->nota ?? null,
            $datos->idVenta ?? null,
            $datos->idUsuario,
            $datos->metodoPago ?? 'EFECTIVO'
        ]);
        $idFactura = $bd->lastInsertId();

        // Insertar ítems
        $stmtItem = $bd->prepare("
            INSERT INTO factura_items (idFactura, cantidad, descripcion, precioUnitario, descuento, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($datos->items as $item) {
            $subtotalItem = max(0, ($item->cantidad * $item->precioUnitario) - ($item->descuento ?? 0));
            $stmtItem->execute([
                $idFactura,
                $item->cantidad,
                $item->descripcion,
                $item->precioUnitario,
                $item->descuento ?? 0,
                $subtotalItem
            ]);
        }

        $bd->commit();
        return ['ok' => true, 'id' => $idFactura];
    } catch (Exception $e) {
        $bd->rollBack();
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function obtenerFacturas($filtros)
{
    $bd = conectarBaseDatos();

    $condiciones = [];
    $params = [];

    if (!empty($filtros->desde)) {
        $condiciones[] = "DATE(f.fechaHora) >= ?";
        $params[] = $filtros->desde;
    }
    if (!empty($filtros->hasta)) {
        $condiciones[] = "DATE(f.fechaHora) <= ?";
        $params[] = $filtros->hasta;
    }
    if (!empty($filtros->nitComprador)) {
        $condiciones[] = "LOWER(f.nitComprador) LIKE LOWER(?)";
        $params[] = '%' . $filtros->nitComprador . '%';
    }
    if (!empty($filtros->estado)) {
        $condiciones[] = "f.estado = ?";
        $params[] = $filtros->estado;
    }

    $where = count($condiciones) > 0 ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    $sentencia = $bd->prepare("
        SELECT f.*, u.nombre as usuarioNombre
        FROM facturas f
        LEFT JOIN usuarios u ON u.id = f.idUsuario
        $where
        ORDER BY f.numero DESC
    ");
    $sentencia->execute($params);
    $facturas = $sentencia->fetchAll();

    foreach ($facturas as &$fac) {
        $stmtItems = $bd->prepare("SELECT * FROM factura_items WHERE idFactura = ?");
        $stmtItems->execute([$fac->id]);
        $fac->items = $stmtItems->fetchAll();
    }
    return $facturas;
}

function anularFactura($id)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE facturas SET estado = 'ANULADA' WHERE id = ?");
    return $sentencia->execute([$id]);
}
