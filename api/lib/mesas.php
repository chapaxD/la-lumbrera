<?php
// lib/mesas.php - Gestión de Mesas y Pedidos Activos

function obtenerNumeroMesasOcupadas()
{
    $bd = conectarBaseDatos();
    $stmt = $bd->query("SELECT COUNT(*) AS total FROM ordenes_activas WHERE tipo='LOCAL'");
    return (int)$stmt->fetchObject()->total;
}


function obtenerMesas($idUsuario = null, $rol = null)
{
    $mesas = [];
    $hoy = date("Y-m-d");
    $reservasHoy = obtenerReservasDelDia($hoy);

    // 1. Obtener información de configuración
    $numeroMesas = (int)(obtenerInformacionLocal()[0]->numeroMesas ?? 0);
    
    // 2. Obtener TODAS las referencias activas actualmente para locales
    $bd = conectarBaseDatos();
    $stmtActivas = $bd->query("SELECT referencia FROM ordenes_activas WHERE tipo='LOCAL'");
    $refsActivas = $stmtActivas->fetchAll(PDO::FETCH_COLUMN);

    // 3. Procesar mesas físicas configuradas (1 a N)
    $referenciasProcesadas = [];
    for ($i = 1; $i <= $numeroMesas; $i++) {
        $refStr = (string)$i;
        $mesaData = leerArchivo($refStr, $idUsuario, $rol);

        // Buscar si esta mesa tiene reserva hoy (solo para la referencia base entera)
        $reservaEncontrada = null;
        foreach ($reservasHoy as $reserva) {
            if ($reserva->idMesa == $i) {
                $reservaEncontrada = $reserva;
                break;
            }
        }
        $mesaData["mesa"]["reserva"] = $reservaEncontrada;
        array_push($mesas, $mesaData);
        $referenciasProcesadas[] = $refStr;
    }

    // 4. Agregar cualquier otra orden activa que no haya sido procesada 
    // (Esto incluye sub-mesas como "1-B", mesas fuera de rango, etc.)
    foreach ($refsActivas as $ref) {
        if (!in_array((string)$ref, $referenciasProcesadas)) {
            $mesaData = leerArchivo($ref, $idUsuario, $rol);
            $mesaData["mesa"]["reserva"] = null;
            array_push($mesas, $mesaData);
            $referenciasProcesadas[] = (string)$ref;
        }
    }

    return $mesas;
}

function _rolEsMeseroConFiltro($idUsuario, $rol)
{
    return $rol === 'mesero' && $idUsuario !== null && $idUsuario !== '';
}

function obtenerReservasDelDia($fecha)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT idMesa, hora, nombre_cliente, estado FROM reservas WHERE fecha = ? AND estado IN ('PENDIENTE', 'CONFIRMADA', 'SENTADA')");
    $sentencia->execute([$fecha]);
    return $sentencia->fetchAll();
}

function leerArchivo($numeroMesa, $idUsuario = null, $rol = null)
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT * FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $stmt->execute([(string)$numeroMesa]);
    $orden = $stmt->fetch();

    if ($orden) {
        $esVistaAjenaMesero = _rolEsMeseroConFiltro($idUsuario, $rol) && (string)$orden->idUsuario !== (string)$idUsuario;
        if ($esVistaAjenaMesero) {
            $mesa = [
                "idMesa"     => $orden->referencia,
                "atiende"    => $orden->atiende,
                "idUsuario"  => $orden->idUsuario,
                "total"      => "",
                "estado"     => $orden->estado,
                "cliente"    => "",
                "created_at" => $orden->created_at ?? null,
            ];
        } else {
            $mesa = [
                "idMesa"     => $orden->referencia,
                "atiende"    => $orden->atiende,
                "idUsuario"  => $orden->idUsuario,
                "total"      => $orden->total,
                "estado"     => $orden->estado,
                "cliente"    => $orden->cliente,
                "created_at" => $orden->created_at ?? null,
            ];
        }

        $stmtItems = $bd->prepare("
            SELECT io.*, IFNULL(c.nombre, 'NO DEFINIDA') AS nombreCategoria, IFNULL(i.tipoVenta, 'NORMAL') AS insumoTipoVenta,
                   i.idComboPlantilla AS insumoIdComboPlantilla
            FROM items_orden io
            LEFT JOIN insumos i ON i.id = io.idInsumo
            LEFT JOIN categorias c ON c.id = i.categoria
            WHERE io.idOrden=? ORDER BY io.id ASC
        ");
        $stmtItems->execute([$orden->id]);
        $insumosArr = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

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
                "itemId"          => $item['id'],
                "id"              => $item['idInsumo'],
                "codigo"          => $item['codigo'],
                "nombre"          => $item['nombre'],
                "precio"          => $item['precio'],
                "caracteristicas" => $item['caracteristicas'],
                "cantidad"        => $item['cantidad'],
                "estado"          => $item['estado'],
                "pagado"          => (int)($item['pagado'] ?? 0),
                "acompanamiento_listo" => (int)($item['acompanamiento_listo'] ?? 0),
                "categoria"       => $item['nombreCategoria'] ?? 'NO DEFINIDA',
                "tipoVenta"       => $item['insumoTipoVenta'] ?? 'NORMAL',
                "detalleJson"     => $detalleJson,
                "resumenCombo"    => $resumenCombo,
            ];
        }, $insumosArr);
        return ["mesa" => $mesa, "insumos" => $insumos];
    } else {
        $mesa = [
            "idMesa"    => $numeroMesa,
            "atiende"   => "",
            "idUsuario" => "",
            "total"     => "",
            "estado"    => "libre",
        ];
        return ["mesa" => $mesa, "insumos" => []];
    }
}

function cancelarMesa($id, $idUsuario = null, $motivo = null)
{
    $bd = conectarBaseDatos();

    // Obtener orden e items antes de eliminar
    $stmtOrden = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $stmtOrden->execute([(string)$id]);
    $orden = $stmtOrden->fetch();

    if ($orden) {
        $idOrden = $orden->id;

        // Registrar en tabla cancelaciones
        $bd->prepare("INSERT INTO cancelaciones (tipo, referencia, idOrden, idUsuario, motivo, fecha) VALUES ('LOCAL', ?, ?, ?, ?, ?)")
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

    $stmt = $bd->prepare("DELETE FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $resultado = $stmt->execute([(string)$id]);
    if ($orden) {
        $bd->prepare("DELETE FROM items_orden WHERE idOrden=?")->execute([$orden->id]);
    }
    return $resultado;
}

function editarMesa($mesa)
{
    return ocuparMesa($mesa);
}

function ocuparMesa($mesa)
{
    $bd = conectarBaseDatos();
    $cliente = ($mesa->cliente === "" || $mesa->cliente === null) ? "MOSTRADOR" : $mesa->cliente;

    $rolSolicitante = isset($mesa->rolSolicitante) ? $mesa->rolSolicitante : null;
    $idUsuarioSolicitante = isset($mesa->idUsuarioSolicitante) ? $mesa->idUsuarioSolicitante : null;
    $desdeReserva = isset($mesa->desdeReserva) && $mesa->desdeReserva === true;

    if (!$desdeReserva) {
        $hoy = date("Y-m-d");
        $checkRes = $bd->prepare("SELECT estado, nombre_cliente, hora FROM reservas WHERE (idMesa = ? OR idMesa IS NULL) AND fecha = ? AND estado IN ('PENDIENTE','CONFIRMADA') LIMIT 1");
        $checkRes->execute([$mesa->id, $hoy]);
        $reservaActiva = $checkRes->fetch();
        if ($reservaActiva) {
            return [
                "ok" => false, 
                "error" => "MESA_RESERVADA", 
                "cliente" => $reservaActiva->nombre_cliente,
                "hora" => $reservaActiva->hora
            ];
        }
    }

    $stmt = $bd->prepare("SELECT id, idUsuario FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $stmt->execute([(string)$mesa->id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // SI viene de una reserva, NO permitimos sobreponer si ya hay una orden activa
        if ($desdeReserva) {
            return ["ok" => false, "error" => "MESA_OCUPADA_ACTIVA"];
        }

        $owner = (string)($existing->idUsuario ?? '');
        $solicitante = (string)($idUsuarioSolicitante ?? '');
        if ($owner !== '' && $solicitante !== '' && $rolSolicitante !== 'admin' && $owner !== $solicitante) {
            return false;
        }
    }

    $idOrdenExcluir = $existing ? (int)$existing->id : null;
    $stockOk = validarStockDisponibleParaItemsOrden($bd, $mesa->insumos ?? [], $idOrdenExcluir);
    if ($stockOk !== true) {
        return $stockOk;
    }

    if ($existing) {
        $idOrden = $existing->id;
        $bd->prepare("UPDATE ordenes_activas SET atiende=?, idUsuario=?, total=?, estado='ocupada', cliente=? WHERE id=?")
            ->execute([$mesa->atiende, $mesa->idUsuario, $mesa->total, $cliente, $idOrden]);
        $bd->prepare("DELETE FROM items_orden WHERE idOrden=?")->execute([$idOrden]);
    } else {
        $bd->prepare("INSERT INTO ordenes_activas (tipo, referencia, atiende, idUsuario, total, estado, cliente) VALUES ('LOCAL',?,?,?,?,'ocupada',?)")
            ->execute([(string)$mesa->id, $mesa->atiende, $mesa->idUsuario, $mesa->total, $cliente]);
        $idOrden = $bd->lastInsertId();
    }

    foreach ($mesa->insumos as $insumo) {
        $i = is_object($insumo) ? get_object_vars($insumo) : (array)$insumo;
        $tipoInsumo = isset($i['tipo']) ? strtoupper($i['tipo']) : 'PLATILLO';
        $estadoInicial = ($tipoInsumo === 'BEBIDA') ? 'listo' : ($i['estado'] ?? 'pendiente');
        $pagado = isset($i['pagado']) ? (int)$i['pagado'] : 0;
        $detJson = _encodeDetalleJsonParaDb($i['detalleJson'] ?? $i['detalle_json'] ?? null);
        $bd->prepare("INSERT INTO items_orden (idOrden, idInsumo, codigo, nombre, precio, caracteristicas, cantidad, estado, tipo, pagado, detalle_json) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$idOrden, $i['id'] ?? 0, $i['codigo'] ?? '', $i['nombre'] ?? '', $i['precio'] ?? 0, $i['caracteristicas'] ?? '', $i['cantidad'] ?? 1, $estadoInicial, $tipoInsumo, $pagado, $detJson]);
    }
    return true;
}

function cambiarMesa($tipoActual, $refActual, $nuevaRef)
{
    $bd = conectarBaseDatos();

    // 1. Verificar si la mesa destino ya está ocupada
    $stmtCheck = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $stmtCheck->execute([(string)$nuevaRef]);
    if ($stmtCheck->fetch()) {
        return ["ok" => false, "error" => "MESA_OCUPADA"];
    }

    // 2. Actualizar la orden activa
    // Si viene de DELIVERY, cambiamos el tipo a LOCAL y reseteamos campos de delivery
    $stmt = $bd->prepare("
        UPDATE ordenes_activas 
        SET tipo='LOCAL', referencia=?, tipo_orden='LOCAL', direccion=NULL, telefono=NULL 
        WHERE tipo=? AND referencia=?
    ");
    $ok = $stmt->execute([(string)$nuevaRef, $tipoActual, (string)$refActual]);

    return ["ok" => $ok];
}

