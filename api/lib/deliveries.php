<?php
// lib/deliveries.php - Gestión de Deliveries y Pedidos Para Llevar

function obtenerDeliveries($idUsuario = null, $rol = null)
{
    $bd = conectarBaseDatos();
    if (_rolEsMeseroConFiltro($idUsuario, $rol)) {
        $stmt = $bd->prepare("SELECT referencia FROM ordenes_activas WHERE tipo='DELIVERY' AND idUsuario=?");
        $stmt->execute([(string)$idUsuario]);
    } else {
        $stmt = $bd->query("SELECT referencia FROM ordenes_activas WHERE tipo='DELIVERY'");
    }
    $deliveries = [];
    while ($row = $stmt->fetch()) {
        $data = leerArchivoDelivery($row->referencia, $idUsuario, $rol);
        if ($data) $deliveries[] = $data;
    }
    return $deliveries;
}

function leerArchivoDelivery($idDelivery, $idUsuario = null, $rol = null)
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT * FROM ordenes_activas WHERE tipo='DELIVERY' AND referencia=?");
    $stmt->execute([$idDelivery]);
    $orden = $stmt->fetch();

    if (!$orden) return null;

    if (_rolEsMeseroConFiltro($idUsuario, $rol) && (string)$orden->idUsuario !== (string)$idUsuario) {
        $delivery = [
            "idDelivery"  => $orden->referencia,
            "atiende"     => $orden->atiende,
            "idUsuario"   => $orden->idUsuario,
            "total"       => "",
            "estado_orden" => $orden->estado,
            "cliente"     => "",
            "direccion"   => "",
            "telefono"    => "",
            "tipo_orden"  => $orden->tipo_orden ?? 'DELIVERY'
        ];
        return ["delivery" => $delivery, "insumos" => []];
    }

    $delivery = [
        "idDelivery"  => $orden->referencia,
        "atiende"     => $orden->atiende,
        "idUsuario"   => $orden->idUsuario,
        "total"       => $orden->total,
        "estado_orden" => $orden->estado,
        "cliente"     => $orden->cliente,
        "direccion"   => $orden->direccion ?? '',
        "telefono"    => $orden->telefono ?? '',
        "tipo_orden"  => $orden->tipo_orden ?? 'DELIVERY',
        "created_at"  => $orden->created_at ?? null,
    ];

    $stmtItems = $bd->prepare("
        SELECT io.*, IFNULL(c.nombre, 'NO DEFINIDA') AS nombreCategoria, IFNULL(i.tipoVenta, 'NORMAL') AS insumoTipoVenta,
               i.idComboPlantilla AS insumoIdComboPlantilla
        FROM items_orden io
        LEFT JOIN insumos i ON i.id = io.idInsumo
        LEFT JOIN categorias c ON c.id = i.categoria
        WHERE io.idOrden=? ORDER BY io.id ASC
    ");
    $stmtItems->execute([$orden->id]);
    $rawItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $insumos = array_map(function ($item) use ($bd) {
        $dj = $item['detalle_json'] ?? null;
        $detalleJson = null;
        if ($dj !== null && $dj !== '') {
            $decoded = json_decode($dj, true);
            $detalleJson = is_array($decoded) ? $decoded : null;
        }
        $tv = strtoupper((string) ($item['insumoTipoVenta'] ?? 'NORMAL'));
        $resumenCombo = '';
        if ($tv === 'COMBO') {
            $resumenCombo = construirResumenComboParaCocina($bd, $dj, (int) ($item['insumoIdComboPlantilla'] ?? 0));
        }
        return [
            "itemId"          => $item['id'] ?? null,
            "id"              => $item['idInsumo'] ?? null,
            "codigo"          => $item['codigo'] ?? '',
            "nombre"          => $item['nombre'] ?? '',
            "precio"          => $item['precio'] ?? 0,
            "caracteristicas" => $item['caracteristicas'] ?? '',
            "cantidad"        => $item['cantidad'] ?? 1,
            "estado"          => $item['estado'] ?? 'pendiente',
            "pagado"          => (int)($item['pagado'] ?? 0),
            "acompanamiento_listo" => (int)($item['acompanamiento_listo'] ?? 0),
            "categoria"       => $item['nombreCategoria'] ?? 'NO DEFINIDA',
            "tipoVenta"       => $item['insumoTipoVenta'] ?? 'NORMAL',
            "detalleJson"     => $detalleJson,
            "resumenCombo"    => $resumenCombo,
        ];
    }, $rawItems);

    return ["delivery" => $delivery, "insumos" => $insumos];
}

function ocuparDelivery($delivery)
{
    $bd = conectarBaseDatos();

    if (!isset($delivery->idDelivery) || $delivery->idDelivery === "" || $delivery->idDelivery === null) {
        $maxStmt = $bd->query("SELECT MAX(CAST(SUBSTRING(referencia, 2) AS UNSIGNED)) FROM ordenes_activas WHERE tipo='DELIVERY'");
        $maxNum  = (int) $maxStmt->fetchColumn();
        $delivery->idDelivery = "D" . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    }

    $cliente    = ($delivery->cliente === "" || $delivery->cliente === null) ? "S/N" : $delivery->cliente;
    $direccion  = $delivery->direccion ?? "";
    $telefono   = $delivery->telefono  ?? "";
    $tipo_orden = (isset($delivery->tipo_orden) && $delivery->tipo_orden) ? $delivery->tipo_orden : 'DELIVERY';

    $rolSolicitante = isset($delivery->rolSolicitante) ? $delivery->rolSolicitante : null;
    $idUsuarioSolicitante = isset($delivery->idUsuarioSolicitante) ? $delivery->idUsuarioSolicitante : null;

    $stmt = $bd->prepare("SELECT id, idUsuario FROM ordenes_activas WHERE tipo='DELIVERY' AND referencia=?");
    $stmt->execute([$delivery->idDelivery]);
    $existing = $stmt->fetch();

    if ($existing) {
        $owner = (string)($existing->idUsuario ?? '');
        $solicitante = (string)($idUsuarioSolicitante ?? '');
        if ($owner !== '' && $solicitante !== '' && $rolSolicitante !== 'admin' && $owner !== $solicitante) {
            return false;
        }
    }

    $idOrdenExcluir = $existing ? (int)$existing->id : null;
    $stockOk = validarStockDisponibleParaItemsOrden($bd, $delivery->insumos ?? [], $idOrdenExcluir);
    if ($stockOk !== true) {
        return array_merge(['status' => false], (array)$stockOk);
    }

    if ($existing) {
        $idOrden = $existing->id;
        $bd->prepare("UPDATE ordenes_activas SET atiende=?, idUsuario=?, total=?, estado='pendiente', cliente=?, direccion=?, telefono=?, tipo_orden=? WHERE id=?")
            ->execute([$delivery->atiende, $delivery->idUsuario, $delivery->total, $cliente, $direccion, $telefono, $tipo_orden, $idOrden]);
        $bd->prepare("DELETE FROM items_orden WHERE idOrden=?")->execute([$idOrden]);
    } else {
        $bd->prepare("INSERT INTO ordenes_activas (tipo, referencia, atiende, idUsuario, total, estado, cliente, direccion, telefono, tipo_orden) VALUES ('DELIVERY',?,?,?,?,'pendiente',?,?,?,?)")
            ->execute([$delivery->idDelivery, $delivery->atiende, $delivery->idUsuario, $delivery->total, $cliente, $direccion, $telefono, $tipo_orden]);
        $idOrden = $bd->lastInsertId();
    }

    foreach ($delivery->insumos as $insumo) {
        $i = is_object($insumo) ? get_object_vars($insumo) : (array)$insumo;
        $tipoInsumo = isset($i['tipo']) ? strtoupper($i['tipo']) : 'PLATILLO';
        $estadoInicial = ($tipoInsumo === 'BEBIDA') ? 'listo' : ($i['estado'] ?? 'pendiente');
        $pagado = isset($i['pagado']) ? (int)$i['pagado'] : 0;
        $detJson = _encodeDetalleJsonParaDb($i['detalleJson'] ?? $i['detalle_json'] ?? null);
        $bd->prepare("INSERT INTO items_orden (idOrden, idInsumo, codigo, nombre, precio, caracteristicas, cantidad, estado, tipo, pagado, detalle_json) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$idOrden, $i['id'] ?? 0, $i['codigo'] ?? '', $i['nombre'] ?? '', $i['precio'] ?? 0, $i['caracteristicas'] ?? '', $i['cantidad'] ?? 1, $estadoInicial, $tipoInsumo, $pagado, $detJson]);
    }

    return ["status" => true, "idDelivery" => $delivery->idDelivery];
}

function editarDelivery($delivery)
{
    return ocuparDelivery($delivery);
}

function cancelarDelivery($id, $idUsuario = null, $motivo = null)
{
    $bd = conectarBaseDatos();

    // Obtener orden e items antes de eliminar
    $stmtOrden = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo='DELIVERY' AND referencia=?");
    $stmtOrden->execute([(string)$id]);
    $orden = $stmtOrden->fetch();

    if ($orden) {
        $idOrden = $orden->id;

        // Registrar en tabla cancelaciones
        $bd->prepare("INSERT INTO cancelaciones (tipo, referencia, idOrden, idUsuario, motivo, fecha) VALUES ('DELIVERY', ?, ?, ?, ?, ?)")
            ->execute([(string)$id, $idOrden, $idUsuario, $motivo, date('Y-m-d H:i:s')]);

        // Solo descontar stock para ítems que ya fueron preparados o entregados
        $stmtItems = $bd->prepare("
            SELECT io.*, IFNULL(i.tipoVenta, 'NORMAL') AS tipoVenta
            FROM items_orden io
            LEFT JOIN insumos i ON i.id = io.idInsumo
            WHERE io.idOrden=? AND (
                io.estado = 'entregado' 
                OR (io.estado = 'listo' AND io.tipo != 'BEBIDA')
            )
        ");
        $stmtItems->execute([$idOrden]);
        $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);
        foreach ($items as $item) {
            if ($item->idInsumo) {
                $ex = expandirNecesidadesDesdeFilaItemOrden($bd, $item);
                aplicarDescuentoStockPorMapa($bd, $ex, $idUsuario, 'CANCELACION', $motivo);
            }
        }
    }

    $stmt = $bd->prepare("DELETE FROM ordenes_activas WHERE tipo='DELIVERY' AND referencia=?");
    $resultado = $stmt->execute([(string)$id]);
    if ($orden) {
        $bd->prepare("DELETE FROM items_orden WHERE idOrden=?")->execute([$orden->id]);
    }
    return $resultado;
}
