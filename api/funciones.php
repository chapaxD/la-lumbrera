<?php
date_default_timezone_set('America/La_Paz');
define('DIRECTORIO', './fotos/');

function verificarTablas()
{
    $bd = conectarBaseDatos();
    $sentencia  = $bd->query("SELECT COUNT(*) AS resultado FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'botanero_ventas'");
    return $sentencia->fetchAll();
}

function obtenerVentasPorMesesDeUsuario($anio, $idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT MONTH(fecha) AS mes, SUM(total) AS totalVentas FROM ventas 
        WHERE YEAR(fecha) = ? AND idUsuario = ?
        GROUP BY MONTH(fecha) ORDER BY mes ASC");
    $sentencia->execute([$anio, $idUsuario]);
    return $sentencia->fetchAll();
}

function obtenerVentasPorDiaMes($mes, $anio, $idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DAY(fecha) AS dia, SUM(total) AS totalVentas
	FROM ventas
	WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND idUsuario = ?
	GROUP BY DAY(fecha)
	ORDER BY dia ASC");
    $sentencia->execute([$mes, $anio, $idUsuario]);
    return $sentencia->fetchAll();
}

function obtenerVentasSemanaDeUsuario($idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DAYNAME(fecha) AS dia, DAYOFWEEK(fecha) AS numeroDia, 
	 SUM(total) AS totalVentas FROM ventas
     WHERE YEARWEEK(fecha)=YEARWEEK(CURDATE())
	 AND idUsuario = ?
     GROUP BY dia, numeroDia 
     ORDER BY numeroDia ASC");
    $sentencia->execute([$idUsuario]);
    return $sentencia->fetchAll();
}

function obtenerInsumosMasVendidos($limite)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT SUM(insumos_venta.precio * insumos_venta.cantidad ) 
	AS total, insumos.nombre, insumos.tipo, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria 
	FROM insumos_venta 
	INNER JOIN insumos ON insumos.id = insumos_venta.idInsumo 
	LEFT JOIN categorias ON categorias.id = insumos.categoria
	GROUP BY insumos_venta.idInsumo, insumos.nombre, insumos.tipo, categoria 
	ORDER BY total DESC 
	LIMIT ?");
    $sentencia->bindValue(1, (int)$limite, \PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}

function obtenerTotalesPorMesa($limite = 5)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT SUM(total) AS total, idMesa
	FROM ventas 
	GROUP BY idMesa
	ORDER BY total DESC LIMIT ?");
    $sentencia->bindValue(1, (int)$limite, \PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}

function obtenerVentasDelDia()
{
    $bd = conectarBaseDatos();
    $hoy = date('Y-m-d'); // Usa la zona horaria de PHP (America/La_Paz)
    $sentencia = $bd->prepare("SELECT IFNULL(SUM(total),0) AS totalVentasHoy
	FROM ventas
	WHERE DATE(fecha) = ?");
    $sentencia->execute([$hoy]);
    return $sentencia->fetchObject()->totalVentasHoy;
}

function obtenerNumeroUsuarios()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT COUNT(*) AS numeroUsuarios
	FROM usuarios");
    return $sentencia->fetchObject()->numeroUsuarios;
}

function obtenerNumeroInsumos()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT COUNT(*) AS numeroInsumos
	FROM insumos");
    return $sentencia->fetchObject()->numeroInsumos;
}

function obtenerTotalVentas()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT IFNULL(SUM(total),0) AS totalVentas
	FROM ventas");
    return $sentencia->fetchObject()->totalVentas;
}

function cantidadVentasDia()
{
    $bd = conectarBaseDatos();
    $hoy = date('Y-m-d'); // Usa la zona horaria de PHP (America/La_Paz)
    $sentencia = $bd->prepare("SELECT COUNT(*) AS cantidad FROM ventas WHERE DATE(fecha) = ?");
    $sentencia->execute([$hoy]);
    return (int)$sentencia->fetchObject()->cantidad;
}

function obtenerNumeroMesasOcupadas()
{
    $bd = conectarBaseDatos();
    $stmt = $bd->query("SELECT COUNT(*) AS total FROM ordenes_activas WHERE tipo='LOCAL'");
    return (int)$stmt->fetchObject()->total;
}

function obtenerVentasUsuario($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT usuarios.nombre, SUM(ventas.total) AS totalVentas
	FROM ventas
	INNER JOIN usuarios ON usuarios.id = ventas.idUsuario
	WHERE (DATE(fecha) >= ? AND DATE(fecha) <= ?)
	GROUP BY ventas.idUsuario, usuarios.nombre");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}

function obtenerVentasPorHora($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DATE_FORMAT(fecha,'%H') AS hora, 
   	SUM(total) as totalVentas FROM ventas 
    WHERE (DATE(fecha) >= ? AND DATE(fecha) <= ?)
    GROUP BY DATE_FORMAT(fecha,'%H') 
    ORDER BY hora ASC
    ");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}

function obtenerVentasPorMeses($anio)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT MONTH(fecha) AS mes, SUM(total) AS totalVentas FROM ventas 
        WHERE YEAR(fecha) = ?
        GROUP BY MONTH(fecha) ORDER BY mes ASC");
    $sentencia->execute([$anio]);
    return $sentencia->fetchAll();
}

function obtenerVentasDiasSemana()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT DAYNAME(fecha) AS dia, DAYOFWEEK(fecha) AS numeroDia, SUM(total) AS totalVentas FROM ventas
     WHERE YEARWEEK(fecha, 1)=YEARWEEK(CURDATE(), 1)
     GROUP BY dia, numeroDia
     ORDER BY (numeroDia + 5) % 7 ASC");
    return $sentencia->fetchAll();
}

function obtenerResumenVentasPorDia($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DATE(fecha) as fecha, COUNT(*) as numVentas, SUM(total) as totalVentas 
                               FROM ventas 
                               WHERE DATE(fecha) >= ? AND DATE(fecha) <= ? 
                               GROUP BY DATE(fecha) 
                               ORDER BY DATE(fecha) ASC");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}

function obtenerTopInsumosPorPeriodo($fechaInicio, $fechaFin, $limite = 5)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT insumos.nombre, SUM(insumos_venta.cantidad) as totalVendidos, SUM(insumos_venta.cantidad * insumos_venta.precio) as totalDinero, IFNULL(categorias.nombre, 'NO DEFINIDA') as categoria
                               FROM insumos_venta
                               INNER JOIN ventas ON ventas.id = insumos_venta.idVenta
                               INNER JOIN insumos ON insumos.id = insumos_venta.idInsumo
                               LEFT JOIN categorias ON categorias.id = insumos.categoria
                               WHERE DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?
                               GROUP BY insumos_venta.idInsumo, insumos.nombre, categoria
                               ORDER BY totalVendidos DESC
                               LIMIT ?");
    $sentencia->bindValue(1, $fechaInicio);
    $sentencia->bindValue(2, $fechaFin);
    $sentencia->bindValue(3, (int)$limite, \PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}

function obtenerVentasPorUsuario($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT IFNULL(SUM(ventas.total), 0) AS total,
	usuarios.nombre 
	FROM ventas
	INNER JOIN usuarios ON usuarios.id = ventas.idUsuario
	WHERE (DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?)
	GROUP BY ventas.idUsuario, usuarios.nombre");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}

function obtenerVentas($fechaInicio, $fechaFin, $idUsuario, $limite = 20, $offset = 0)
{
    $bd = conectarBaseDatos();
    $valoresAEjecutar = [$fechaInicio, $fechaFin];

    $sql = "SELECT ventas.*, IFNULL(usuarios.nombre, 'NO ENCONTRADO') AS atendio 
	FROM ventas
	LEFT JOIN usuarios ON ventas.idUsuario = usuarios.id
	WHERE (DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?)";

    if ($idUsuario !== "") {
        $sql .= " AND ventas.idUsuario = ?";
        array_push($valoresAEjecutar, $idUsuario);
    }

    $sql .= " ORDER BY ventas.id DESC LIMIT ? OFFSET ?";

    $sentencia = $bd->prepare($sql);
    $pos = 1;
    foreach ($valoresAEjecutar as $val) {
        $sentencia->bindValue($pos++, $val);
    }
    $sentencia->bindValue($pos++, (int)$limite, \PDO::PARAM_INT);
    $sentencia->bindValue($pos,   (int)$offset, \PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}

function contarVentas($fechaInicio, $fechaFin, $idUsuario)
{
    $bd = conectarBaseDatos();
    $valoresAEjecutar = [$fechaInicio, $fechaFin];

    $sql = "SELECT COUNT(*) AS total, IFNULL(SUM(ventas.total), 0) AS totalDinero
	FROM ventas
	WHERE (DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?)";

    if ($idUsuario !== "") {
        $sql .= " AND ventas.idUsuario = ?";
        array_push($valoresAEjecutar, $idUsuario);
    }

    $sentencia = $bd->prepare($sql);
    $sentencia->execute($valoresAEjecutar);
    return $sentencia->fetch();
}

function obtenerInsumosVenta($idVenta)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT insumos_venta.*, insumos.nombre, insumos.codigo, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
FROM insumos_venta 
LEFT JOIN insumos ON insumos.id = insumos_venta.idInsumo
LEFT JOIN categorias ON categorias.id = insumos.categoria
WHERE idVenta = ?");
    $sentencia->execute([$idVenta]);
    return $sentencia->fetchAll();
}

function registrarVenta($venta)
{
    $bd = conectarBaseDatos();


    $metodoPago = isset($venta->metodoPago) ? $venta->metodoPago : 'EFECTIVO';
    $montoEfectivo = isset($venta->montoEfectivo) ? $venta->montoEfectivo : 0;
    $montoTarjeta = isset($venta->montoTarjeta) ? $venta->montoTarjeta : 0;
    $montoQR = isset($venta->montoQR) ? $venta->montoQR : 0;

    $tipo_orden = isset($venta->tipo_orden) ? $venta->tipo_orden : 'LOCAL';
    $direccion = isset($venta->direccion) ? $venta->direccion : NULL;
    $telefono = isset($venta->telefono) ? $venta->telefono : NULL;
    $estado_delivery = ($tipo_orden === 'DELIVERY') ? 'ENTREGADO' : NULL;

    // Descontar adelanto si hay reserva completada
    $adelanto = 0;
    $saldo_a_favor = 0;
    if ($tipo_orden === 'LOCAL' && isset($venta->idMesa) && $venta->idMesa) {
        $hoy = date('Y-m-d');
        // Descontar adelanto si la reserva está COMPLETADA o SENTADA
        $stmtReserva = $bd->prepare("SELECT adelanto FROM reservas WHERE idMesa = ? AND fecha = ? AND estado IN ('COMPLETADA','SENTADA') AND adelanto > 0 ORDER BY id DESC LIMIT 1");
        $stmtReserva->execute([$venta->idMesa, $hoy]);
        $reserva = $stmtReserva->fetch();
        if ($reserva && $reserva->adelanto > 0) {
            $adelanto = (float)$reserva->adelanto;
            // Si adelanto > total, saldo a favor
            if ($adelanto >= $venta->total) {
                $saldo_a_favor = $adelanto - $venta->total;
                $venta->total = 0;
            } else {
                $venta->total = $venta->total - $adelanto;
            }
        }
    }

    $idUsuario = (int)($venta->idUsuario ?? 0);
    if ($idUsuario === 0) {
        $idUsuario = 1; // Fallback hardcoded id if somehow not present
    }

    // Para ventas LLEVAR y DELIVERY, idMesa debe ser 0 (cero)
    $idMesa = $venta->idMesa;
    if ($tipo_orden === 'LLEVAR' || $tipo_orden === 'DELIVERY') {
        $idMesa = 0;
    }

    $sentencia = $bd->prepare("INSERT INTO ventas (idMesa, cliente, fecha, total, pagado, idUsuario, metodoPago, montoEfectivo, montoTarjeta, montoQR, tipo_orden, direccion, telefono, estado_delivery) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $exitoVenta = $sentencia->execute([$idMesa, $venta->cliente, date("Y-m-d H:i:s"), $venta->total, $venta->pagado,  $idUsuario, $metodoPago, $montoEfectivo, $montoTarjeta, $montoQR, $tipo_orden, $direccion, $telefono, $estado_delivery]);
    
    if (!$exitoVenta) return false;

    $idVenta = $bd->lastInsertId();
    $insumosRegistrados = registrarInsumosVenta($venta->insumos, $idVenta, $idUsuario);

    // Eliminar orden activa — o marcarla 'pagada' si cocina aún tiene ítems pendientes
    $tipoDb = ($tipo_orden === 'LOCAL') ? 'LOCAL' : 'DELIVERY';
    $refDb  = ($tipo_orden === 'LOCAL') ? (string)$venta->idMesa : $venta->idDelivery;
    $stmtOrdenActiva = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo=? AND referencia=?");
    $stmtOrdenActiva->execute([$tipoDb, $refDb]);
    $ordenActiva = $stmtOrdenActiva->fetch();

    if ($ordenActiva) {
        $stmtPendientes = $bd->prepare("SELECT COUNT(*) FROM items_orden WHERE idOrden=? AND estado='pendiente'");
        $stmtPendientes->execute([$ordenActiva->id]);
        $hayPendientes = (int) $stmtPendientes->fetchColumn();

        if ($hayPendientes > 0) {
            // Cocina aún prepara ítems → mantener orden visible para cocina como 'pagada'
            $bd->prepare("UPDATE ordenes_activas SET estado='pagada' WHERE id=?")
                ->execute([$ordenActiva->id]);
            // Marcar todos los ítems actuales como pagados
            $bd->prepare("UPDATE items_orden SET pagado=1 WHERE idOrden=?")
                ->execute([$ordenActiva->id]);
        } else {
            // Todo listo → eliminar normalmente
            $bd->prepare("DELETE FROM ordenes_activas WHERE id=?")->execute([$ordenActiva->id]);
        }
    }

    // Automatización: Completar reserva si existe una para esta mesa hoy
    if ($tipo_orden === 'LOCAL') {
        completarReservaPorMesa($venta->idMesa);
    }

    return true;
}

function completarReservaPorMesa($idMesa)
{
    $bd = conectarBaseDatos();
    $hoy = date("Y-m-d");
    // Marcamos como COMPLETADA cualquier reserva activa (incluye SENTADA) para esta mesa hoy
    $sentencia = $bd->prepare("UPDATE reservas SET estado = 'COMPLETADA' WHERE (idMesa = ? OR idMesa IS NULL) AND fecha = ? AND estado IN ('PENDIENTE', 'CONFIRMADA', 'SENTADA')");
    return $sentencia->execute([$idMesa, $hoy]);
}

function registrarInsumosVenta($insumos, $idVenta, $idUsuario)
{
    $resultados = [];
    $bd = conectarBaseDatos();
    _asegurarTipoVentaInsumos();
    foreach ($insumos as $insumo) {
        $arr = is_object($insumo) ? get_object_vars($insumo) : (array) $insumo;
        $sentencia = $bd->prepare("INSERT INTO insumos_venta(idInsumo, precio, cantidad, idVenta) VALUES(?,?,?,?)");
        $sentencia->execute([$arr['id'], $arr['precio'], $arr['cantidad'], $idVenta]);
        if ($sentencia) {
            array_push($resultados, $sentencia);
        }

        $nec = expandirNecesidadesLineaPedido($bd, $arr);
        aplicarDescuentoStockPorMapa($bd, $nec, $idUsuario, 'VENTA', null);
    }
    return $resultados;
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
    $sentencia = $bd->prepare("SELECT idMesa, hora, nombre_cliente, estado FROM reservas WHERE fecha = ? AND estado IN ('PENDIENTE', 'CONFIRMADA')");
    $sentencia->execute([$fecha]);
    return $sentencia->fetchAll();
}

function leerArchivo($numeroMesa, $idUsuario = null, $rol = null)
{
    _asegurarCreatedAtOrdenes();
    _asegurarTipoItemsOrden();
    _asegurarPagadoItemsOrden();
    _asegurarAcompanamientoItemsOrden();
    _asegurarDetalleJsonItemsOrden();
    _asegurarTipoVentaInsumos();
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT * FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $stmt->execute([(string)$numeroMesa]);
    $orden = $stmt->fetch();

    if ($orden) {
        $esVistaAjenaMesero = _rolEsMeseroConFiltro($idUsuario, $rol) && (string)$orden->idUsuario !== (string)$idUsuario;
        // Vista ajena: no mostrar total ni cliente (privacidad), pero sí ítems/estados para ver la mesa y cocina
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

// crearInsumosMesa ya no se usa — reemplazada por consulta directa a items_orden en leerArchivo()

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

        // Solo descontar stock para ítems que ya fueron preparados (cocina los usó pero no se cobró)
        // Los ítems 'pendiente' no se cocinaron, sus ingredientes siguen en stock
        _asegurarTipoVentaInsumos();
        _asegurarDetalleJsonItemsOrden();
        $stmtItems = $bd->prepare("
            SELECT io.*, IFNULL(i.tipoVenta, 'NORMAL') AS tipoVenta
            FROM items_orden io
            LEFT JOIN insumos i ON i.id = io.idInsumo
            WHERE io.idOrden=? AND io.estado IN ('listo','entregado')
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
    // Limpiar items huérfanos (ya fueron contabilizados en cancelaciones/stock)
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
    _asegurarDetalleJsonItemsOrden();
    $bd = conectarBaseDatos();
    $cliente = ($mesa->cliente === "" || $mesa->cliente === null) ? "MOSTRADOR" : $mesa->cliente;

    $rolSolicitante = isset($mesa->rolSolicitante) ? $mesa->rolSolicitante : null;
    $idUsuarioSolicitante = isset($mesa->idUsuarioSolicitante) ? $mesa->idUsuarioSolicitante : null;
    $desdeReserva = isset($mesa->desdeReserva) && $mesa->desdeReserva === true;

    // Bloquear si hay reserva activa y no viene del botón "Sentar" de Reservas
    if (!$desdeReserva) {
        $hoy = date("Y-m-d");
        $checkRes = $bd->prepare("SELECT estado FROM reservas WHERE (idMesa = ? OR idMesa IS NULL) AND fecha = ? AND estado IN ('PENDIENTE','CONFIRMADA') LIMIT 1");
        $checkRes->execute([$mesa->id, $hoy]);
        if ($checkRes->fetch()) {
            return false;
        }
    }

    $stmt = $bd->prepare("SELECT id, idUsuario FROM ordenes_activas WHERE tipo='LOCAL' AND referencia=?");
    $stmt->execute([(string)$mesa->id]);
    $existing = $stmt->fetch();

    if ($existing) {
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
        // Bebidas no pasan por cocina: nacen como 'listo' para que el mesero las entregue directamente
        $estadoInicial = ($tipoInsumo === 'BEBIDA') ? 'listo' : ($i['estado'] ?? 'pendiente');
        $pagado = isset($i['pagado']) ? (int)$i['pagado'] : 0;
        $detJson = _encodeDetalleJsonParaDb($i['detalleJson'] ?? $i['detalle_json'] ?? null);
        $bd->prepare("INSERT INTO items_orden (idOrden, idInsumo, codigo, nombre, precio, caracteristicas, cantidad, estado, tipo, pagado, detalle_json) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$idOrden, $i['id'] ?? 0, $i['codigo'] ?? '', $i['nombre'] ?? '', $i['precio'] ?? 0, $i['caracteristicas'] ?? '', $i['cantidad'] ?? 1, $estadoInicial, $tipoInsumo, $pagado, $detJson]);
    }
    return true;
}

function cambiarPassword($idUsuario, $password)
{
    $bd = conectarBaseDatos();
    $passwordCod = password_hash($password, PASSWORD_DEFAULT);
    $sentencia = $bd->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    return $sentencia->execute([$passwordCod, $idUsuario]);
}

function verificarPassword($password, $idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT password FROM usuarios  WHERE id = ?");
    $sentencia->execute([$idUsuario]);
    $usuario = $sentencia->fetchObject();
    if ($usuario === FALSE) return false;
    elseif ($sentencia->rowCount() == 1) {
        $passwordVerifica = password_verify($password, $usuario->password);
        if ($usuario && $passwordVerifica) {
            return true;
        } else {
            return false;
        }
    }
}

function iniciarSesion($correo, $password)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $sentencia->execute([$correo]);
    $usuario = $sentencia->fetchObject();
    if ($usuario === FALSE) return false;
    elseif ($sentencia->rowCount() == 1) {
        $passwordVerifica = password_verify($password, $usuario->password);
        if ($usuario && $passwordVerifica) {
            return $usuario;
        } else {
            return false;
        }
    }
}

function eliminarUsuario($idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM usuarios WHERE id = ?");
    return $sentencia->execute([$idUsuario]);
}

function editarUsuario($usuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE usuarios SET correo = ?, nombre = ?, telefono = ?, rol = ? WHERE id = ?");
    return $sentencia->execute([$usuario->correo, $usuario->nombre, $usuario->telefono, $usuario->rol ?? 'mesero', $usuario->id]);
}

function obtenerUsuarioPorId($idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT id, correo, nombre, telefono, rol FROM usuarios WHERE id = ?");
    $sentencia->execute([$idUsuario]);
    return $sentencia->fetchObject();
}

function obtenerUsuarios()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT id, correo, nombre, telefono, rol FROM usuarios");
    return $sentencia->fetchAll();
}

function registrarUsuario($usuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("INSERT INTO usuarios (correo, nombre, telefono, password, rol) VALUES(?,?,?,?,?)");
    return $sentencia->execute([$usuario->correo, $usuario->nombre, $usuario->telefono, $usuario->password, $usuario->rol ?? 'mesero']);
}

function obtenerInsumosPorNombre($insumo, $ajustarStockVenta = false)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
	FROM insumos
	LEFT JOIN categorias ON categorias.id = insumos.categoria 
	WHERE insumos.nombre LIKE ? ");
    $sentencia->execute(['%' . $insumo . '%']);
    $filas = $sentencia->fetchAll();
    if ($ajustarStockVenta) {
        ajustarStockDisponibleVentaEnFilas($bd, $filas);
    }
    return $filas;
}

function actualizarInformacionLocal($datos)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE informacion_negocio SET nombre = ?, telefono = ?, numeroMesas = ?, logo = ?, direccion = ?, nit_emisor = ?, razon_social = ?, actividad = ?, ciudad = ?, num_autorizacion = ?, fecha_limite_emision = ?");
    return $sentencia->execute([$datos->nombre, $datos->telefono, $datos->numeroMesas, $datos->logo, $datos->direccion ?? '', $datos->nit_emisor ?? null, $datos->razon_social ?? null, $datos->actividad ?? null, $datos->ciudad ?? null, $datos->num_autorizacion ?? null, $datos->fecha_limite_emision ?? null]);
}

function registrarInformacionLocal($datos)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("INSERT INTO informacion_negocio (nombre, telefono, numeroMesas, logo, direccion, nit_emisor, razon_social, actividad, ciudad, num_autorizacion, fecha_limite_emision) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    return $sentencia->execute([$datos->nombre, $datos->telefono, $datos->numeroMesas, $datos->logo, $datos->direccion ?? '', $datos->nit_emisor ?? null, $datos->razon_social ?? null, $datos->actividad ?? null, $datos->ciudad ?? null, $datos->num_autorizacion ?? null, $datos->fecha_limite_emision ?? null]);
}

function obtenerInformacionLocal()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM informacion_negocio");
    return $sentencia->fetchAll();
}

function obtenerImagen($imagen)
{
    $imagen = str_replace('data:image/png;base64,', '', $imagen);
    $imagen = str_replace('data:image/jpeg;base64,', '', $imagen);
    $imagen = str_replace(' ', '+', $imagen);
    $data = base64_decode($imagen);
    $file = DIRECTORIO . uniqid() . '.png';


    $insertar = file_put_contents($file, $data);
    return $file;
}

function eliminarInsumo($idInsumo)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM insumos WHERE id = ?");
    return $sentencia->execute([$idInsumo]);
}

function editarInsumo($insumo)
{
    _asegurarTipoVentaInsumos();
    $bd = conectarBaseDatos();

    $antiguo = $bd->prepare("SELECT stock FROM insumos WHERE id = ?");
    $antiguo->execute([$insumo->id]);
    $viejo = $antiguo->fetch();
    $stockViejo = $viejo ? $viejo->stock : 0;

    $tipoVenta = isset($insumo->tipoVenta) ? $insumo->tipoVenta : 'NORMAL';
    $idCombo = property_exists($insumo, 'idComboPlantilla') ? $insumo->idComboPlantilla : null;
    if ($idCombo === '' || $idCombo === false) {
        $idCombo = null;
    }

    $sentencia = $bd->prepare("UPDATE insumos SET tipo = ?, codigo = ?, nombre = ?, descripcion = ?, categoria = ?, precio = ?, stock = ?, stockMinimo = ?, stockMateria = ?, tipoCorte = ?, tipoVenta = ?, idComboPlantilla = ? WHERE id = ?");
    $resultado = $sentencia->execute([$insumo->tipo, $insumo->codigo, $insumo->nombre, $insumo->descripcion, $insumo->categoria, $insumo->precio, $insumo->stock ?? 0, $insumo->stockMinimo ?? 0, $insumo->stockMateria ?? 0, $insumo->tipoCorte ?? 0, $tipoVenta, $idCombo, $insumo->id]);

    if ($resultado) {
        $tvR = strtoupper((string) ($insumo->tipoVenta ?? 'NORMAL'));
        if ($tvR === 'RECETA' && isset($insumo->receta)) {
            guardarRecetaInsumo($insumo->id, $insumo->receta);
        } elseif ($tvR !== 'RECETA') {
            guardarRecetaInsumo($insumo->id, []);
        }
    }

    if ($resultado && isset($insumo->idUsuario)) {
        $diferencia = ($insumo->stock ?? 0) - $stockViejo;
        if ($diferencia != 0) {
            $movimiento = $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, fecha) VALUES (?, ?, ?, 'AJUSTE', ?)");
            $movimiento->execute([$insumo->id, $insumo->idUsuario, $diferencia, date("Y-m-d H:i:s")]);
        }
    }
    return $resultado;
}

function obtenerInsumoPorId($idInsumo)
{
    _asegurarTipoVentaInsumos();
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT * FROM insumos WHERE id = ?");
    $sentencia->execute([$idInsumo]);
    $obj = $sentencia->fetchObject();
    if ($obj) {
        $obj->receta = obtenerRecetaComponentesPorPadre($idInsumo);
    }
    return $obj;
}

function obtenerInsumos($filtros)
{
    $bd = conectarBaseDatos();
    $valoresAEjecutar = [];
    $sql = "SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
	FROM insumos
	LEFT JOIN categorias ON categorias.id = insumos.categoria WHERE 1 ";

    if ($filtros->tipo != "") {
        $sql .= " AND  insumos.tipo = ?";
        array_push($valoresAEjecutar, $filtros->tipo);
    }

    if ($filtros->categoria != "") {
        $sql .= " AND  insumos.categoria = ?";
        array_push($valoresAEjecutar, $filtros->categoria);
    }

    if ($filtros->nombre != "") {
        $sql .= " AND  insumos.nombre LIKE ? OR insumos.codigo LIKE ?";
        array_push($valoresAEjecutar, '%' . $filtros->nombre . '%');
        array_push($valoresAEjecutar, '%' . $filtros->nombre . '%');
    }

    $sentencia = $bd->prepare($sql);
    $sentencia->execute($valoresAEjecutar);
    return $sentencia->fetchAll();
}

function registrarInsumo($insumo)
{
    _asegurarTipoVentaInsumos();
    $bd = conectarBaseDatos();
    $tipoVenta = isset($insumo->tipoVenta) ? $insumo->tipoVenta : 'NORMAL';
    $idCombo = property_exists($insumo, 'idComboPlantilla') ? $insumo->idComboPlantilla : null;
    if ($idCombo === '' || $idCombo === false) {
        $idCombo = null;
    }
    $sentencia = $bd->prepare("INSERT INTO insumos (codigo, nombre, descripcion, precio, tipo, categoria, stock, stockMinimo, stockMateria, tipoCorte, tipoVenta, idComboPlantilla) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $ok = $sentencia->execute([$insumo->codigo, $insumo->nombre, $insumo->descripcion, $insumo->precio, $insumo->tipo, $insumo->categoria, $insumo->stock ?? 0, $insumo->stockMinimo ?? 0, $insumo->stockMateria ?? 0, $insumo->tipoCorte ?? 0, $tipoVenta, $idCombo]);
    if (!$ok) {
        return false;
    }
    $nuevoId = (int) $bd->lastInsertId();
    if ($nuevoId > 0 && strtoupper((string) $tipoVenta) === 'RECETA' && isset($insumo->receta)) {
        guardarRecetaInsumo($nuevoId, $insumo->receta);
    }
    return true;
}

function obtenerCategoriasPorTipo($tipo)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT * FROM categorias WHERE tipo = ?");
    $sentencia->execute([$tipo]);
    return $sentencia->fetchAll();
}


function eliminarCategoria($idCategoria)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM categorias WHERE id = ?");
    return $sentencia->execute([$idCategoria]);
}

function editarCategoria($categoria)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE categorias SET tipo = ?, nombre = ?, descripcion = ? WHERE id = ?");
    return $sentencia->execute([$categoria->tipo, $categoria->nombre, $categoria->descripcion, $categoria->id]);
}

function registrarCategoria($categoria)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("INSERT INTO categorias (tipo, nombre, descripcion) VALUES (?,?,?)");
    return $sentencia->execute([$categoria->tipo, $categoria->nombre, $categoria->descripcion]);
}

function obtenerCategorias()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM categorias ORDER BY id DESC");
    return $sentencia->fetchAll();
}

function _asegurarCreatedAtOrdenes()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ordenes_activas' AND COLUMN_NAME='created_at'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE ordenes_activas ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarTipoOrdenOrdenes()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ordenes_activas' AND COLUMN_NAME='tipo_orden'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE ordenes_activas ADD COLUMN tipo_orden VARCHAR(20) NOT NULL DEFAULT 'LOCAL'");
            $bd->exec("UPDATE ordenes_activas SET tipo_orden = 'DELIVERY' WHERE tipo = 'DELIVERY'");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarTipoItemsOrden()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='tipo'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT 'PLATILLO'");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarPagadoItemsOrden()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='pagado'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN pagado TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarAcompanamientoItemsOrden()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='acompanamiento_listo'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN acompanamiento_listo TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarTipoVentaInsumos()
{
    static $verificado = false;
    if ($verificado) {
        return;
    }
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='insumos' AND COLUMN_NAME='tipoVenta'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE insumos ADD COLUMN tipoVenta VARCHAR(20) NOT NULL DEFAULT 'NORMAL'");
        }
        $existe2 = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='insumos' AND COLUMN_NAME='idComboPlantilla'")->fetchColumn();
        if (!$existe2) {
            $bd->exec("ALTER TABLE insumos ADD COLUMN idComboPlantilla BIGINT UNSIGNED NULL DEFAULT NULL");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarDetalleJsonItemsOrden()
{
    static $verificado = false;
    if ($verificado) {
        return;
    }
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='detalle_json'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN detalle_json LONGTEXT NULL DEFAULT NULL");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _encodeDetalleJsonParaDb($raw)
{
    if ($raw === null || $raw === '') {
        return null;
    }
    if (is_string($raw)) {
        return $raw;
    }
    return json_encode($raw, JSON_UNESCAPED_UNICODE);
}

function _mergeCantidadesMapa(&$dest, $src)
{
    foreach ($src as $id => $q) {
        $iid = (int) $id;
        $qq = (float) $q;
        if ($iid <= 0 || $qq <= 0) {
            continue;
        }
        $dest[$iid] = ($dest[$iid] ?? 0) + $qq;
    }
}

function expandirRecetaInsumo($bd, $idPadre, $multCantidad)
{
    $idPadre = (int) $idPadre;
    $multCantidad = (float) $multCantidad;
    if ($idPadre <= 0 || $multCantidad <= 0) {
        return [];
    }
    $st = $bd->prepare("SELECT idInsumoHijo, cantidad FROM insumo_receta_componente WHERE idInsumoPadre = ?");
    $st->execute([$idPadre]);
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_OBJ) as $r) {
        $hid = (int) $r->idInsumoHijo;
        $q = (float) $r->cantidad * $multCantidad;
        if ($hid > 0 && $q > 0) {
            $map[$hid] = ($map[$hid] ?? 0) + $q;
        }
    }
    if (count($map) === 0) {
        return [$idPadre => $multCantidad];
    }
    return $map;
}

/**
 * $detalle: array o JSON string. Formato: { "menus": [ { "slots": { "<idSlot>": <idInsumo>, ... } }, ... ] }
 * cantidadLinea = número de menús (debe coincidir con count(menus)).
 */
function expandirComboDesdeDetalle($detalleRaw, $cantidadLinea)
{
    $cantidadLinea = (int) $cantidadLinea;
    if ($cantidadLinea < 1) {
        $cantidadLinea = 1;
    }
    if ($detalleRaw === null || $detalleRaw === '') {
        return [];
    }
    $det = $detalleRaw;
    if (is_string($detalleRaw)) {
        $det = json_decode($detalleRaw, true);
    } elseif (is_object($detalleRaw)) {
        $det = json_decode(json_encode($detalleRaw), true);
    }
    if (!is_array($det)) {
        return [];
    }
    $menus = $det['menus'] ?? [];
    if (!is_array($menus) || count($menus) !== $cantidadLinea) {
        return [];
    }
    $map = [];
    foreach ($menus as $menu) {
        if (!is_array($menu)) {
            continue;
        }
        $slots = $menu['slots'] ?? $menu['s'] ?? [];
        if (!is_array($slots)) {
            continue;
        }
        foreach ($slots as $slotKey => $idInsumoElegido) {
            $hid = (int) $idInsumoElegido;
            if ($hid > 0) {
                $map[$hid] = ($map[$hid] ?? 0) + 1;
            }
        }
    }
    return $map;
}

/**
 * Texto multilínea para cocina / comanda: elecciones del menú con etiqueta de slot + nombre de insumo.
 */
function construirResumenComboParaCocina($bd, $detalleJsonRaw, $idPlantilla)
{
    $idPlantilla = (int) $idPlantilla;
    if ($idPlantilla <= 0 || $detalleJsonRaw === null || $detalleJsonRaw === '') {
        return '';
    }
    $det = is_array($detalleJsonRaw) ? $detalleJsonRaw : json_decode((string) $detalleJsonRaw, true);
    if (!is_array($det)) {
        return '';
    }
    $menus = $det['menus'] ?? [];
    if (!is_array($menus) || count($menus) === 0) {
        return '';
    }
    try {
        $st = $bd->prepare('SELECT id, etiqueta FROM combo_plantilla_slot WHERE id_plantilla = ? ORDER BY orden ASC, id ASC');
        $st->execute([$idPlantilla]);
        $slotsMeta = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $slotsMeta[(string) $r['id']] = (string) $r['etiqueta'];
        }
    } catch (\Exception $e) {
        return '';
    }
    $bloques = [];
    $mi = 1;
    $stNom = $bd->prepare('SELECT nombre FROM insumos WHERE id = ?');
    foreach ($menus as $menu) {
        if (!is_array($menu)) {
            $mi++;
            continue;
        }
        $slots = $menu['slots'] ?? $menu['s'] ?? [];
        if (!is_array($slots)) {
            $mi++;
            continue;
        }
        $partes = [];
        foreach ($slots as $slotId => $idInsumoElegido) {
            $et = $slotsMeta[(string) $slotId] ?? ('Opción ' . $slotId);
            $nid = (int) $idInsumoElegido;
            $nom = '?';
            if ($nid > 0) {
                $stNom->execute([$nid]);
                $row = $stNom->fetchColumn();
                $nom = $row ? (string) $row : ('#' . $nid);
            }
            $partes[] = $et . ': ' . $nom;
        }
        if (count($partes) > 0) {
            $bloques[] = 'Menú ' . $mi . ': ' . implode(' · ', $partes);
        }
        $mi++;
    }
    return implode("\n", $bloques);
}

function expandirNecesidadesLineaPedido($bd, $linea)
{
    _asegurarTipoVentaInsumos();
    $arr = is_array($linea) ? $linea : (array) $linea;
    $id = (int) ($arr['id'] ?? 0);
    if ($id <= 0) {
        return [];
    }
    $cant = isset($arr['cantidad']) ? (float) $arr['cantidad'] : 1;
    if ($cant < 0) {
        $cant = 0;
    }
    $tipoVenta = strtoupper(trim((string) ($arr['tipoVenta'] ?? $arr['tipo_venta'] ?? '')));
    if ($tipoVenta === '') {
        $st = $bd->prepare("SELECT tipoVenta FROM insumos WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_OBJ);
        $tipoVenta = strtoupper($row->tipoVenta ?? 'NORMAL');
    }
    if ($tipoVenta === '' || $tipoVenta === 'NORMAL') {
        return [$id => $cant];
    }
    if ($tipoVenta === 'RECETA') {
        return expandirRecetaInsumo($bd, $id, $cant);
    }
    if ($tipoVenta === 'COMBO') {
        $det = $arr['detalleJson'] ?? $arr['detalle_json'] ?? null;
        return expandirComboDesdeDetalle($det, (int) round($cant));
    }
    return [$id => $cant];
}

function expandirNecesidadesDesdeFilaItemOrden($bd, $row)
{
    $o = is_object($row) ? $row : (object) $row;
    $linea = [
        'id' => (int) ($o->idInsumo ?? 0),
        'cantidad' => isset($o->cantidad) ? (float) $o->cantidad : 1,
        'tipoVenta' => $o->tipoVenta ?? null,
        'detalle_json' => $o->detalle_json ?? null,
    ];
    return expandirNecesidadesLineaPedido($bd, $linea);
}

function aplicarDescuentoStockPorMapa($bd, $mapa, $idUsuario, $tipoHistorial = 'VENTA', $nota = null)
{
    foreach ($mapa as $idInsumo => $qty) {
        $iid = (int) $idInsumo;
        $q = (float) $qty;
        if ($iid <= 0 || $q <= 0) {
            continue;
        }
        $bd->prepare("UPDATE insumos SET stock = GREATEST(0, stock - ?) WHERE id = ?")->execute([$q, $iid]);
        $mov = $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, nota, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        $mov->execute([$iid, $idUsuario, -$q, $tipoHistorial, $nota, date('Y-m-d H:i:s')]);
    }
}

function obtenerRecetaComponentesPorPadre($idPadre)
{
    $bd = conectarBaseDatos();
    $st = $bd->prepare("SELECT idInsumoHijo, cantidad FROM insumo_receta_componente WHERE idInsumoPadre = ? ORDER BY idInsumoHijo ASC");
    $st->execute([(int) $idPadre]);
    return $st->fetchAll(PDO::FETCH_OBJ);
}

function guardarRecetaInsumo($idPadre, $receta)
{
    $bd = conectarBaseDatos();
    $idPadre = (int) $idPadre;
    $bd->prepare("DELETE FROM insumo_receta_componente WHERE idInsumoPadre = ?")->execute([$idPadre]);
    if (!is_array($receta) && !is_object($receta)) {
        return true;
    }
    $ins = $bd->prepare("INSERT INTO insumo_receta_componente (idInsumoPadre, idInsumoHijo, cantidad) VALUES (?,?,?)");
    foreach ($receta as $r) {
        $r = is_object($r) ? get_object_vars($r) : (array) $r;
        $hijo = (int) ($r['idInsumoHijo'] ?? $r['id'] ?? 0);
        $cant = isset($r['cantidad']) ? (float) $r['cantidad'] : 1;
        if ($hijo <= 0 || $cant <= 0) {
            continue;
        }
        $ins->execute([$idPadre, $hijo, $cant]);
    }
    return true;
}

function obtenerPlantillasCombo()
{
    $bd = conectarBaseDatos();
    $plantillas = $bd->query("SELECT * FROM combo_plantilla ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);
    foreach ($plantillas as $p) {
        _cargarSlotsPlantillaCombo($bd, $p);
    }
    return $plantillas;
}

function _cargarSlotsPlantillaCombo($bd, $p)
{
    $st = $bd->prepare("SELECT * FROM combo_plantilla_slot WHERE id_plantilla = ? ORDER BY orden ASC, id ASC");
    $st->execute([(int) $p->id]);
    $p->slots = $st->fetchAll(PDO::FETCH_OBJ);
    foreach ($p->slots as $s) {
        $so = $bd->prepare("SELECT o.id, o.id_insumo, i.nombre AS nombre_insumo, i.stock, i.tipoVenta FROM combo_plantilla_opcion o LEFT JOIN insumos i ON i.id = o.id_insumo WHERE o.id_slot = ? ORDER BY o.id ASC");
        $so->execute([(int) $s->id]);
        $opciones = $so->fetchAll(PDO::FETCH_OBJ);
        
        foreach ($opciones as $op) {
            $tmpArr = [(object)['id' => $op->id_insumo, 'stock' => $op->stock, 'tipoVenta' => $op->tipoVenta]];
            ajustarStockDisponibleVentaEnFilas($bd, $tmpArr);
            $op->stock = (float)$tmpArr[0]->stock;
            unset($op->tipoVenta);
        }
        $s->opciones = $opciones;
    }
}

function obtenerPlantillaComboPorId($id)
{
    $bd = conectarBaseDatos();
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    $st = $bd->prepare("SELECT * FROM combo_plantilla WHERE id = ?");
    $st->execute([$id]);
    $p = $st->fetch(PDO::FETCH_OBJ);
    if (!$p) {
        return null;
    }
    _cargarSlotsPlantillaCombo($bd, $p);
    return $p;
}

function guardarPlantillaCombo($payload)
{
    $bd = conectarBaseDatos();
    $p = is_object($payload) ? get_object_vars($payload) : (array) $payload;
    $nombre = trim((string) ($p['nombre'] ?? ''));
    if ($nombre === '') {
        return false;
    }
    $desc = (string) ($p['descripcion'] ?? '');
    $descPct = isset($p['descuento_pct']) ? (float) $p['descuento_pct'] : 0;
    $activo = isset($p['activo']) ? (int) (bool) $p['activo'] : 1;
    $idPlantilla = isset($p['id']) ? (int) $p['id'] : 0;

    $bd->beginTransaction();
    try {
        if ($idPlantilla > 0) {
            $bd->prepare("UPDATE combo_plantilla SET nombre=?, descripcion=?, descuento_pct=?, activo=? WHERE id=?")
                ->execute([$nombre, $desc, $descPct, $activo, $idPlantilla]);
            $stSlotIds = $bd->prepare("SELECT id FROM combo_plantilla_slot WHERE id_plantilla = ?");
            $stSlotIds->execute([$idPlantilla]);
            foreach ($stSlotIds->fetchAll(PDO::FETCH_COLUMN) as $sid) {
                $bd->prepare("DELETE FROM combo_plantilla_opcion WHERE id_slot = ?")->execute([(int) $sid]);
            }
            $bd->prepare("DELETE FROM combo_plantilla_slot WHERE id_plantilla = ?")->execute([$idPlantilla]);
        } else {
            $bd->prepare("INSERT INTO combo_plantilla (nombre, descripcion, descuento_pct, activo) VALUES (?,?,?,?)")
                ->execute([$nombre, $desc, $descPct, $activo]);
            $idPlantilla = (int) $bd->lastInsertId();
        }

        $slots = $p['slots'] ?? [];
        if (is_object($slots)) {
            $slots = json_decode(json_encode($slots), true);
        }
        if (!is_array($slots)) {
            $slots = [];
        }
        $insSlot = $bd->prepare("INSERT INTO combo_plantilla_slot (id_plantilla, etiqueta, orden) VALUES (?,?,?)");
        $insOp = $bd->prepare("INSERT INTO combo_plantilla_opcion (id_slot, id_insumo) VALUES (?,?)");
        foreach ($slots as $idx => $slot) {
            $slot = is_object($slot) ? get_object_vars($slot) : (array) $slot;
            $et = trim((string) ($slot['etiqueta'] ?? ''));
            if ($et === '') {
                continue;
            }
            $ord = isset($slot['orden']) ? (int) $slot['orden'] : (int) $idx;
            $insSlot->execute([$idPlantilla, $et, $ord]);
            $idSlot = (int) $bd->lastInsertId();
            $ops = $slot['opciones'] ?? [];
            if (is_object($ops)) {
                $ops = json_decode(json_encode($ops), true);
            }
            if (!is_array($ops)) {
                $ops = [];
            }
            foreach ($ops as $op) {
                $op = is_object($op) ? get_object_vars($op) : (array) $op;
                $idi = (int) ($op['id_insumo'] ?? $op['idInsumo'] ?? 0);
                if ($idi > 0) {
                    $insOp->execute([$idSlot, $idi]);
                }
            }
        }
        $bd->commit();
        return $idPlantilla;
    } catch (\Exception $e) {
        $bd->rollBack();
        throw $e;
    }
}

function eliminarPlantillaCombo($id)
{
    $bd = conectarBaseDatos();
    $id = (int) $id;
    if ($id <= 0) {
        return false;
    }
    $stSlotIds = $bd->prepare("SELECT id FROM combo_plantilla_slot WHERE id_plantilla = ?");
    $stSlotIds->execute([$id]);
    foreach ($stSlotIds->fetchAll(PDO::FETCH_COLUMN) as $sid) {
        $bd->prepare("DELETE FROM combo_plantilla_opcion WHERE id_slot = ?")->execute([(int) $sid]);
    }
    $bd->prepare("DELETE FROM combo_plantilla_slot WHERE id_plantilla = ?")->execute([$id]);
    $bd->prepare("DELETE FROM combo_plantilla WHERE id = ?")->execute([$id]);
    return true;
}

// ─── CLIENTES ─────────────────────────────────────────────────────────────────
$NOMBRES_GENERICOS = ['mostrador', 'sin nombre', 'sin nombr', 's/n', '99001', 'consumidor final', 'cf', ''];

function esNombreGenerico($nombre)
{
    global $NOMBRES_GENERICOS;
    return in_array(strtolower(trim($nombre)), $NOMBRES_GENERICOS);
}

function obtenerClientes($q = '')
{
    $bd = conectarBaseDatos();
    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $bd->prepare(
            "SELECT id, nombre, apellido, telefono, email, nit, direccion, notas
             FROM clientes
             WHERE nombre LIKE ? OR apellido LIKE ? OR nit LIKE ?
             ORDER BY nombre ASC
             LIMIT 20"
        );
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $bd->query(
            "SELECT id, nombre, apellido, telefono, email, nit, direccion, notas
             FROM clientes ORDER BY nombre ASC"
        );
    }
    return $stmt->fetchAll();
}

function registrarCliente($data)
{
    if (esNombreGenerico($data->nombre ?? '')) {
        return ['ok' => false, 'error' => 'Nombre genérico no permitido'];
    }
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare(
        "INSERT INTO clientes (nombre, apellido, telefono, email, nit, direccion, notas)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        trim($data->nombre),
        trim($data->apellido   ?? ''),
        trim($data->telefono   ?? ''),
        trim($data->email      ?? ''),
        trim($data->nit        ?? ''),
        trim($data->direccion  ?? ''),
        trim($data->notas      ?? ''),
    ]);
    return ['ok' => true, 'id' => $bd->lastInsertId()];
}

function editarCliente($data)
{
    if (esNombreGenerico($data->nombre ?? '')) {
        return ['ok' => false, 'error' => 'Nombre genérico no permitido'];
    }
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare(
        "UPDATE clientes SET nombre=?, apellido=?, telefono=?, email=?, nit=?, direccion=?, notas=?
         WHERE id=?"
    );
    $stmt->execute([
        trim($data->nombre),
        trim($data->apellido   ?? ''),
        trim($data->telefono   ?? ''),
        trim($data->email      ?? ''),
        trim($data->nit        ?? ''),
        trim($data->direccion  ?? ''),
        trim($data->notas      ?? ''),
        $data->id,
    ]);
    return ['ok' => true];
}

function eliminarCliente($id)
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    return ['ok' => true];
}
// ──────────────────────────────────────────────────────────────────────────────

function conectarBaseDatos()
{
    require_once __DIR__ . '/db_config.php';
    $host = DB_HOST;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    $port = DB_PORT;
    $charset = DB_CHARSET;

    $options = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_SSL) {
        // Buscar el bundle CA del sistema (Debian/Ubuntu en Docker)
        $caBundle = '/etc/ssl/certs/ca-certificates.crt';
        if (file_exists($caBundle)) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $caBundle;
        }
        $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    try {
        $pdo = new \PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    } catch (\PDOException $e) {
        die('Error de conexión: ' . $e->getMessage());
    }
}

function obtenerInsumosBajoStock()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM insumos WHERE stock <= stockMinimo AND stockMinimo > 0 ORDER BY stock ASC");
    return $sentencia->fetchAll();
}

function registrarCompra($payload)
{
    $resultados = [];
    $bd = conectarBaseDatos();
    $insumos = $payload->insumos;
    $idUsuario = $payload->idUsuario;

    foreach ($insumos as $insumo) {
        $sentencia = $bd->prepare("UPDATE insumos SET stock = stock + ? WHERE id = ?");
        $sentencia->execute([$insumo->cantidad, $insumo->id]);
        if ($sentencia) array_push($resultados, $sentencia);

        $movimiento = $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, fecha) VALUES (?, ?, ?, 'COMPRA', ?)");
        $movimiento->execute([$insumo->id, $idUsuario, $insumo->cantidad, date("Y-m-d H:i:s")]);
    }
    return $resultados;
}

function obtenerHistorialStock($fechaInicio = null, $fechaFin = null)
{
    $bd = conectarBaseDatos();
    $valores = [];
    $where = '';
    if ($fechaInicio && $fechaFin) {
        $where = 'WHERE DATE(h.fecha) BETWEEN ? AND ?';
        $valores = [$fechaInicio, $fechaFin];
    }
    $sentencia = $bd->prepare("
        SELECT h.*, i.nombre as insumoNombre, u.nombre as usuarioNombre 
        FROM historial_stock h 
        LEFT JOIN insumos i ON h.idInsumo = i.id 
        LEFT JOIN usuarios u ON h.idUsuario = u.id 
		$where
        ORDER BY h.fecha DESC
    ");
    $sentencia->execute($valores);
    return $sentencia->fetchAll();
}

function registrarMerma($payload)
{
    $bd = conectarBaseDatos();
    $idInsumo = $payload->idInsumo;
    $cantidad = $payload->cantidad;
    $idUsuario = $payload->idUsuario;

    $descuento = $bd->prepare("UPDATE insumos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
    $resultado = $descuento->execute([$cantidad, $idInsumo]);

    if ($resultado) {
        $movimiento = $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, fecha) VALUES (?, ?, ?, 'MERMA', ?)");
        $movimiento->execute([$idInsumo, $idUsuario, -$cantidad, date("Y-m-d H:i:s")]);
    }

    return $resultado;
}

function producirDesdeMateria($payload)
{
    $bd = conectarBaseDatos();
    $idInsumo   = $payload->idInsumo;
    $usoMateria = $payload->usoMateria;   // gramos/unidades de materia a usar
    $idUsuario  = $payload->idUsuario;

    // Verificar que haya suficiente materia (stockMateria en kg, tipoCorte en g/porción)
    $stmt = $bd->prepare("SELECT stock, stockMateria, tipoCorte, nombre FROM insumos WHERE id = ?");
    $stmt->execute([$idInsumo]);
    $insumo = $stmt->fetchObject();
    if (!$insumo || $insumo->tipoCorte <= 0) return ['ok' => false, 'error' => 'Sin tasa de conversión configurada'];

    $materiaEnGramos = $insumo->stockMateria * 1000;
    $usoEnGramos = $usoMateria * 1000;
    if ($usoEnGramos > $materiaEnGramos) return ['ok' => false, 'error' => 'No hay suficiente materia prima'];

    $porcionesNuevas = (int)floor($usoEnGramos / $insumo->tipoCorte);
    if ($porcionesNuevas <= 0) return ['ok' => false, 'error' => 'La cantidad indicada no alcanza para preparar ni una porción'];

    $nuevaMateria = round($insumo->stockMateria - $usoMateria, 4);
    $nuevoStock   = $insumo->stock + $porcionesNuevas;
    $fecha = date("Y-m-d H:i:s");

    $upd = $bd->prepare("UPDATE insumos SET stock = ?, stockMateria = ? WHERE id = ?");
    $ok = $upd->execute([$nuevoStock, $nuevaMateria, $idInsumo]);

    if ($ok) {
        // Registrar en historial: entrada de porciones producidas
        $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, nota, fecha) VALUES (?, ?, ?, 'PRODUCCION', ?, ?)")
            ->execute([
                $idInsumo,
                $idUsuario,
                $porcionesNuevas,
                "Producidas desde {$usoMateria} kg de materia prima",
                $fecha
            ]);
    }

    return [
        'ok'             => (bool)$ok,
        'porcionesNuevas' => $porcionesNuevas,
        'nuevoStock'     => $nuevoStock,
        'nuevaMateria'   => $nuevaMateria,
    ];
}

function obtenerEstadoCaja()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM caja_diaria WHERE estado = 'ABIERTA' ORDER BY id DESC LIMIT 1");
    $caja = $sentencia->fetchObject();

    if ($caja) {
        $sqlVentas = "SELECT 
            IFNULL(SUM(total), 0) as totalVentas, 
            IFNULL(SUM(CASE WHEN metodoPago = 'EFECTIVO' AND montoEfectivo = 0 THEN total ELSE montoEfectivo END), 0) as ventasEfectivo, 
            IFNULL(SUM(montoTarjeta), 0) as ventasTarjeta, 
            IFNULL(SUM(montoQR), 0) as ventasQR 
            FROM ventas WHERE fecha >= ?";
        $sentenciaVentas = $bd->prepare($sqlVentas);
        $sentenciaVentas->execute([$caja->fechaApertura]);
        $ventas = $sentenciaVentas->fetchObject();

        $caja->ventasAcumuladas = $ventas->totalVentas;
        $caja->ventasEfectivo = $ventas->ventasEfectivo;
        $caja->ventasTarjeta = $ventas->ventasTarjeta;
        $caja->ventasQR = $ventas->ventasQR;

        $sqlGastos = "SELECT IFNULL(SUM(monto), 0) as totalGastos FROM gastos_caja WHERE idCaja = ?";
        $sentenciaGastos = $bd->prepare($sqlGastos);
        $sentenciaGastos->execute([$caja->id]);
        $caja->gastosAcumulados = $sentenciaGastos->fetchObject()->totalGastos;

        return $caja;
    }
    return false;
}

function abrirCaja($payload)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("INSERT INTO caja_diaria (idUsuarioApertura, montoApertura, fechaApertura, estado) VALUES (?, ?, ?, 'ABIERTA')");
    return $sentencia->execute([$payload->idUsuario, $payload->montoApertura, date("Y-m-d H:i:s")]);
}

function cerrarCaja($payload)
{
    $bd = conectarBaseDatos();
    $caja = obtenerEstadoCaja();
    if (!$caja) return false;

    $fechaCierre = date("Y-m-d H:i:s");
    $sentencia = $bd->prepare("UPDATE caja_diaria SET idUsuarioCierre = ?, montoCierre = ?, ventasTotales = ?, gastosTotales = ?, ventasTarjeta = ?, ventasQR = ?, fechaCierre = ?, estado = 'CERRADA' WHERE id = ?");
    $ok = $sentencia->execute([
        $payload->idUsuario,
        $payload->montoCierre,
        $caja->ventasAcumuladas,
        $caja->gastosAcumulados,
        $caja->ventasTarjeta,
        $caja->ventasQR,
        $fechaCierre,
        $caja->id
    ]);
    if (!$ok) return false;

    // Traer detalle de gastos para el PDF
    $stmtGastos = $bd->prepare("SELECT concepto, monto, fecha FROM gastos_caja WHERE idCaja = ? ORDER BY fecha ASC");
    $stmtGastos->execute([$caja->id]);
    $gastos = $stmtGastos->fetchAll();

    // Nombre del usuario que cierra
    $stmtU = $bd->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmtU->execute([$payload->idUsuario]);
    $usuario = $stmtU->fetchObject();

    return [
        'ok'               => true,
        'fechaApertura'    => $caja->fechaApertura,
        'fechaCierre'      => $fechaCierre,
        'montoApertura'    => $caja->montoApertura,
        'montoCierre'      => $payload->montoCierre,
        'ventasTotales'    => $caja->ventasAcumuladas,
        'ventasEfectivo'   => $caja->ventasEfectivo,
        'ventasTarjeta'    => $caja->ventasTarjeta,
        'ventasQR'         => $caja->ventasQR,
        'gastosTotal'      => $caja->gastosAcumulados,
        'gastos'           => $gastos,
        'usuarioCierre'    => $usuario ? $usuario->nombre : '',
    ];
}

function registrarGastoCaja($payload)
{
    $bd = conectarBaseDatos();
    $idCaja = $payload->idCaja;
    $concepto = $payload->concepto;
    $monto = $payload->monto;
    $idUsuario = $payload->idUsuario;
    $fecha = date("Y-m-d H:i:s");

    $sentencia = $bd->prepare("INSERT INTO gastos_caja (idCaja, concepto, monto, fecha, idUsuario) VALUES (?, ?, ?, ?, ?)");
    return $sentencia->execute([$idCaja, $concepto, $monto, $fecha, $idUsuario]);
}

function obtenerGastosDeCaja($idCaja)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT concepto, monto, fecha FROM gastos_caja WHERE idCaja = ? ORDER BY fecha ASC");
    $sentencia->execute([$idCaja]);
    return $sentencia->fetchAll();
}

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
                 subtotal, descuentos, baseCredito, iva, total, nota, idVenta, idUsuario)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $datos->idUsuario
        ]);
        $idFactura = $bd->lastInsertId();

        // Insertar ítems
        $stmtItem = $bd->prepare("
            INSERT INTO factura_items (idFactura, cantidad, descripcion, precioUnitario, descuento, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($datos->items as $item) {
            $subtotalItem = max(0, ($item->cantidad * $item->precioUnitario) - $item->descuento);
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
        $condiciones[] = "f.nitComprador LIKE ?";
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

function obtenerHistorialCajas()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("
        SELECT c.*, ua.nombre as usuarioApertura, uc.nombre as usuarioCierre 
        FROM caja_diaria c
        LEFT JOIN usuarios ua ON c.idUsuarioApertura = ua.id
        LEFT JOIN usuarios uc ON c.idUsuarioCierre = uc.id
        ORDER BY c.fechaApertura DESC
    ");
    return $sentencia->fetchAll();
}

/**
 * Cantidades ya comprometidas en pedidos activos (mesas / delivery sin cerrar).
 * El stock en `insumos` no baja hasta cobrar; esto evita sobre-vender entre órdenes.
 */
function obtenerMapaStockReservadoEnOrdenesActivas($bd)
{
    _asegurarTipoVentaInsumos();
    _asegurarDetalleJsonItemsOrden();
    $sentencia = $bd->query("
        SELECT io.idInsumo, io.cantidad, io.detalle_json, IFNULL(i.tipoVenta, 'NORMAL') AS tipoVenta
        FROM items_orden io
        INNER JOIN ordenes_activas oa ON oa.id = io.idOrden
        LEFT JOIN insumos i ON i.id = io.idInsumo
        WHERE io.idInsumo IS NOT NULL AND io.idInsumo > 0
    ");
    $mapa = [];
    foreach ($sentencia->fetchAll(PDO::FETCH_OBJ) as $row) {
        $ex = expandirNecesidadesDesdeFilaItemOrden($bd, $row);
        _mergeCantidadesMapa($mapa, $ex);
    }
    return $mapa;
}

function obtenerMapaCantidadesItemsOrden($bd, $idOrden)
{
    _asegurarTipoVentaInsumos();
    _asegurarDetalleJsonItemsOrden();
    $st = $bd->prepare("
        SELECT io.idInsumo, io.cantidad, io.detalle_json, IFNULL(i.tipoVenta, 'NORMAL') AS tipoVenta
        FROM items_orden io
        LEFT JOIN insumos i ON i.id = io.idInsumo
        WHERE io.idOrden = ? AND io.idInsumo IS NOT NULL AND io.idInsumo > 0
    ");
    $st->execute([(int) $idOrden]);
    $m = [];
    foreach ($st->fetchAll(PDO::FETCH_OBJ) as $r) {
        $ex = expandirNecesidadesDesdeFilaItemOrden($bd, $r);
        _mergeCantidadesMapa($m, $ex);
    }
    return $m;
}

/**
 * Ajusta el campo `stock` de cada fila a (stock físico en BD − ya pedido en órdenes activas).
 */
function ajustarStockDisponibleVentaEnFilas($bd, $filas)
{
    if (!$filas || count($filas) === 0) {
        return $filas;
    }
    _asegurarTipoVentaInsumos();
    $mapa = obtenerMapaStockReservadoEnOrdenesActivas($bd);
    foreach ($filas as $row) {
        $id = (int) ($row->id ?? 0);
        if ($id <= 0) {
            continue;
        }
        $tv = strtoupper($row->tipoVenta ?? 'NORMAL');
        if ($tv === 'RECETA') {
            $nec = expandirRecetaInsumo($bd, $id, 1);
            $soloPadre = count($nec) === 1 && isset($nec[$id]);
            if (!$soloPadre && count($nec) > 0) {
                $minUnidades = null;
                foreach ($nec as $hid => $needPer) {
                    $needPer = (float) $needPer;
                    if ($needPer <= 0) {
                        continue;
                    }
                    $st = $bd->prepare('SELECT stock FROM insumos WHERE id = ?');
                    $st->execute([(int) $hid]);
                    $inv = $st->fetch(PDO::FETCH_OBJ);
                    $stockFisico = $inv ? (float) $inv->stock : 0;
                    $res = (float) ($mapa[(int) $hid] ?? 0);
                    $libre = max(0, $stockFisico - $res);
                    $unidades = (int) floor($libre / $needPer);
                    $minUnidades = $minUnidades === null ? $unidades : min($minUnidades, $unidades);
                }
                $row->stock = max(0, (int) ($minUnidades ?? 0));
                continue;
            }
        }
        $res = (float) ($mapa[$id] ?? 0);
        $stockFisico = (float) ($row->stock ?? 0);
        $row->stock = max(0, (int) floor($stockFisico - $res));
    }
    return $filas;
}

/**
 * Valida que haya stock libre para los ítems del payload. Si $idOrdenExcluir está definido,
 * no cuenta la reserva de esa orden (útil al editar la misma mesa/delivery).
 * @return true|object { error: 'stock', mensaje: string }
 */
function validarStockDisponibleParaItemsOrden($bd, $insumosPayload, $idOrdenExcluir = null)
{
    _asegurarTipoVentaInsumos();
    _asegurarDetalleJsonItemsOrden();
    if ($insumosPayload === null || !is_iterable($insumosPayload)) {
        return true;
    }
    $porId = [];
    foreach ($insumosPayload as $insumo) {
        $i = is_object($insumo) ? get_object_vars($insumo) : (array) $insumo;
        $id = (int) ($i['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $tipoVenta = strtoupper(trim((string) ($i['tipoVenta'] ?? $i['tipo_venta'] ?? '')));
        if ($tipoVenta === '') {
            $stTv = $bd->prepare('SELECT tipoVenta FROM insumos WHERE id = ?');
            $stTv->execute([$id]);
            $tipoVenta = strtoupper((string) ($stTv->fetchColumn() ?: 'NORMAL'));
        }
        $ex = expandirNecesidadesLineaPedido($bd, $i);
        if ($tipoVenta === 'COMBO' && count($ex) === 0) {
            return (object) ['error' => 'stock', 'mensaje' => 'Menú combo incompleto: elegí las opciones de cada unidad (la cantidad debe coincidir con el número de menús configurados).'];
        }
        _mergeCantidadesMapa($porId, $ex);
    }
    if (count($porId) === 0) {
        return true;
    }
    $mapaReservado = obtenerMapaStockReservadoEnOrdenesActivas($bd);
    if ($idOrdenExcluir) {
        $mapaActual = obtenerMapaCantidadesItemsOrden($bd, (int) $idOrdenExcluir);
        foreach ($mapaActual as $idInsumo => $c) {
            $mapaReservado[$idInsumo] = max(0, ($mapaReservado[$idInsumo] ?? 0) - $c);
        }
    }
    foreach ($porId as $idInsumo => $necesita) {
        $stmt = $bd->prepare('SELECT stock, nombre FROM insumos WHERE id = ?');
        $stmt->execute([$idInsumo]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$row) {
            return (object) ['error' => 'stock', 'mensaje' => 'Un producto del pedido no existe en inventario.'];
        }
        $stockFisico = (float) $row->stock;
        $yaReservadoOtros = (float) ($mapaReservado[$idInsumo] ?? 0);
        $libre = $stockFisico - $yaReservadoOtros;
        if ($necesita > $libre) {
            $nom = $row->nombre ?? 'El producto';
            $necStr = (strpos((string) $necesita, '.') !== false) ? rtrim(rtrim(number_format((float) $necesita, 3, '.', ''), '0'), '.') : (string) (int) $necesita;
            $libStr = (strpos((string) $libre, '.') !== false) ? rtrim(rtrim(number_format((float) $libre, 3, '.', ''), '0'), '.') : (string) (int) max(0, $libre);
            return (object) ['error' => 'stock', 'mensaje' => "Stock insuficiente para «{$nom}»: se necesitan {$necStr} y solo quedan {$libStr} disponible(s) para nuevas órdenes."];
        }
    }
    return true;
}

function obtenerMenuDia($dia, $ajustarStockVenta = false)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("
        SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
        FROM menu_dia
        INNER JOIN insumos ON insumos.id = menu_dia.idInsumo
        LEFT JOIN categorias ON categorias.id = insumos.categoria
        WHERE menu_dia.diaSemana = ?
    ");
    $sentencia->execute([$dia]);
    $filas = $sentencia->fetchAll();
    if ($ajustarStockVenta) {
        ajustarStockDisponibleVentaEnFilas($bd, $filas);
    }
    return $filas;
}

function guardarMenuDia($idInsumo, $dia)
{
    $bd = conectarBaseDatos();
    // Verificar si ya existe para ese día
    $check = $bd->prepare("SELECT id FROM menu_dia WHERE idInsumo = ? AND diaSemana = ?");
    $check->execute([$idInsumo, $dia]);
    if ($check->fetch()) return true; // Ya está en el menú

    $sentencia = $bd->prepare("INSERT INTO menu_dia (idInsumo, diaSemana) VALUES (?, ?)");
    return $sentencia->execute([$idInsumo, $dia]);
}

function registrarReserva($reserva)
{
    $bd = conectarBaseDatos();

    $fecha   = $reserva->fecha;
    $hora    = $reserva->hora;
    $idMesa  = isset($reserva->idMesa) ? $reserva->idMesa : null;
    $esEvento = ($idMesa === null || $idMesa === '');

    // Detectar solapamiento (reservas activas ±2h en el mismo día)
    if ($esEvento) {
        // Evento total: conflicto con CUALQUIER reserva ese día
        $check = $bd->prepare("
            SELECT r.*, u.nombre AS usuarioNombre
            FROM reservas r
            LEFT JOIN usuarios u ON r.idUsuario = u.id
            WHERE r.fecha = ? AND r.estado IN ('PENDIENTE','CONFIRMADA')
            LIMIT 1
        ");
        $check->execute([$fecha]);
    } else {
        // Mesa específica: conflicto con la misma mesa O con un evento total, en ventana de 2h
        $check = $bd->prepare("
            SELECT r.*, u.nombre AS usuarioNombre
            FROM reservas r
            LEFT JOIN usuarios u ON r.idUsuario = u.id
            WHERE r.fecha = ?
              AND r.estado IN ('PENDIENTE','CONFIRMADA')
              AND (r.idMesa = ? OR r.idMesa IS NULL)
              AND ABS(TIMESTAMPDIFF(MINUTE, r.hora, ?)) < 120
            LIMIT 1
        ");
        $check->execute([$fecha, $idMesa, $hora]);
    }

    $conflicto = $check->fetch();
    if ($conflicto) {
        return [
            'ok'        => false,
            'error'     => 'SOLAPAMIENTO',
            'cliente'   => $conflicto->nombre_cliente,
            'hora'      => $conflicto->hora,
            'idMesa'    => $conflicto->idMesa,
        ];
    }

    $sentencia = $bd->prepare("INSERT INTO reservas (nombre_cliente, telefono, fecha, hora, personas, idMesa, notas, adelanto, idUsuario, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE')");
    $ok = $sentencia->execute([
        $reserva->nombre_cliente,
        $reserva->telefono,
        $fecha,
        $hora,
        $reserva->personas,
        $esEvento ? null : $idMesa,
        $reserva->notas,
        isset($reserva->adelanto) ? (float)$reserva->adelanto : 0,
        $reserva->idUsuario
    ]);
    return ['ok' => $ok];
}

function obtenerReservas()
{
    $bd = conectarBaseDatos();
    // JOIN con usuarios para traer el nombre de quien registró
    $sentencia = $bd->query("
        SELECT r.*, u.nombre as usuarioNombre 
        FROM reservas r
        LEFT JOIN usuarios u ON r.idUsuario = u.id
        ORDER BY r.fecha ASC, r.hora ASC
    ");
    return $sentencia->fetchAll();
}

function eliminarReserva($id)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM reservas WHERE id = ?");
    return $sentencia->execute([$id]);
}

function cambiarEstadoReserva($id, $estado)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
    return $sentencia->execute([$estado, $id]);
}

function eliminarDelMenuDia($idInsumo, $dia)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM menu_dia WHERE idInsumo = ? AND diaSemana = ?");
    return $sentencia->execute([$idInsumo, $dia]);
}

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
    _asegurarTipoOrdenOrdenes();
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT * FROM ordenes_activas WHERE tipo='DELIVERY' AND referencia=?");
    $stmt->execute([$idDelivery]);
    $orden = $stmt->fetch();

    if (!$orden) return null;

    // Si es mesero, ocultar detalles de deliveries de otros usuarios
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

    _asegurarPagadoItemsOrden();
    _asegurarAcompanamientoItemsOrden();
    _asegurarDetalleJsonItemsOrden();
    _asegurarTipoVentaInsumos();
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
    _asegurarCreatedAtOrdenes();
    _asegurarTipoOrdenOrdenes();
    _asegurarDetalleJsonItemsOrden();
    $bd = conectarBaseDatos();

    if (!isset($delivery->idDelivery) || $delivery->idDelivery === "" || $delivery->idDelivery === null) {
        $maxStmt = $bd->query("SELECT MAX(CAST(SUBSTRING(referencia, 2) AS UNSIGNED)) FROM ordenes_activas WHERE tipo='DELIVERY'");
        $maxNum  = (int) $maxStmt->fetchColumn();
        $delivery->idDelivery = "D" . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    }

    $cliente    = ($delivery->cliente === "" || $delivery->cliente === null) ? "S/N" : $delivery->cliente;
    $direccion  = $delivery->direccion ?? "";
    $telefono   = $delivery->telefono  ?? "";
    // Respetar el tipo_orden recibido (puede ser 'DELIVERY' o 'LLEVAR')
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
        // Bebidas no pasan por cocina: nacen como 'listo' para que el mesero las entregue directamente
        $estadoInicial = ($tipoInsumo === 'BEBIDA') ? 'listo' : ($i['estado'] ?? 'pendiente');
        $pagado = isset($i['pagado']) ? (int)$i['pagado'] : 0;
        $detJson = _encodeDetalleJsonParaDb($i['detalleJson'] ?? $i['detalle_json'] ?? null);
        $bd->prepare("INSERT INTO items_orden (idOrden, idInsumo, codigo, nombre, precio, caracteristicas, cantidad, estado, tipo, pagado, detalle_json) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$idOrden, $i['id'] ?? 0, $i['codigo'] ?? '', $i['nombre'] ?? '', $i['precio'] ?? 0, $i['caracteristicas'] ?? '', $i['cantidad'] ?? 1, $estadoInicial, $tipoInsumo, $pagado, $detJson]);
    }

    return ["status" => true, "idDelivery" => $delivery->idDelivery];
}

function cancelarDelivery($id, $idUsuario = null, $motivo = null)
{
    $bd = conectarBaseDatos();

    // Obtener orden e items antes de eliminar
    $stmtOrden = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo='DELIVERY' AND referencia=?");
    $stmtOrden->execute([$id]);
    $orden = $stmtOrden->fetch();

    if ($orden) {
        $idOrden = $orden->id;

        // Registrar en tabla cancelaciones
        $bd->prepare("INSERT INTO cancelaciones (tipo, referencia, idOrden, idUsuario, motivo, fecha) VALUES ('DELIVERY', ?, ?, ?, ?, ?)")
            ->execute([$id, $idOrden, $idUsuario, $motivo, date('Y-m-d H:i:s')]);

        // Solo descontar stock para ítems que ya fueron preparados (cocina los usó pero no se cobró)
        // Los ítems 'pendiente' no se cocinaron, sus ingredientes siguen en stock
        _asegurarTipoVentaInsumos();
        _asegurarDetalleJsonItemsOrden();
        $stmtItems = $bd->prepare("
            SELECT io.*, IFNULL(i.tipoVenta, 'NORMAL') AS tipoVenta
            FROM items_orden io
            LEFT JOIN insumos i ON i.id = io.idInsumo
            WHERE io.idOrden=? AND io.estado IN ('listo','entregado')
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
    $resultado = $stmt->execute([$id]);
    // Limpiar items huérfanos (ya fueron contabilizados en cancelaciones/stock)
    if ($orden) {
        $bd->prepare("DELETE FROM items_orden WHERE idOrden=?")->execute([$orden->id]);
    }
    return $resultado;
}

function editarDelivery($delivery)
{
    return ocuparDelivery($delivery);
}

function actualizarEstadoDelivery($idVenta, $estado)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE ventas SET estado_delivery = ? WHERE id = ?");
    return $sentencia->execute([$estado, $idVenta]);
}

function marcarItemListoCocina($payload)
{
    $tipo = $payload->tipo;
    $id   = $payload->id;
    $indiceInsumo = (int)$payload->indiceInsumo;
    $soloAcompanamiento = isset($payload->soloAcompanamiento) ? $payload->soloAcompanamiento : false;

    // LLEVAR se almacena con tipo='DELIVERY' en ordenes_activas
    $tipoDb = ($tipo === 'LLEVAR') ? 'DELIVERY' : $tipo;

    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo=? AND referencia=?");
    $stmt->execute([$tipoDb, (string)$id]);
    $orden = $stmt->fetch();

    if (!$orden) return false;
    $idOrden = $orden->id;

    // Obtener el ítem en la posición indicada (orden de inserción)
    $stmt = $bd->prepare("SELECT id, estado FROM items_orden WHERE idOrden=? ORDER BY id ASC LIMIT 1 OFFSET ?");
    $stmt->bindValue(1, (int)$idOrden, \PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$indiceInsumo, \PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch();

    if (!$item) return false;

    if ($soloAcompanamiento) {
        return $bd->prepare("UPDATE items_orden SET acompanamiento_listo=1 WHERE id=?")->execute([$item->id]);
    } else {
        $bd->prepare("UPDATE items_orden SET estado='listo' WHERE id=?")->execute([$item->id]);
    }

    // Las órdenes pagadas se mantienen visibles hasta que alguien las marque como entregadas
    return true;
}

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

// Despiece parrilla: lotes con líneas (cuadre kg total y gramos por línea)
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

/** Unidades que ingresan a stock desde una línea de despiece.
 * Prioriza el nuevo campo genérico `porciones`;
 * si no existe o es 0, cae al cálculo antiguo (porciones_250 + porciones_350).
 */
function unidadesStockDesdeLineaDespiece($porciones250, $porciones350, $porcionesGenericas = 0) {
    $gen = (int) $porcionesGenericas;
    if ($gen > 0) return $gen;
    return (int) $porciones250 + (int) $porciones350;
}

/**
 * Asegura que despiece_parrilla_linea tenga las columnas del nuevo sistema flexible.
 * Usa IF NOT EXISTS para que sea idempotente (puede correr en cada request).
 */
function _asegurarColumnasDespieceLinea() {
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $bd->exec("
            ALTER TABLE despiece_parrilla_linea
              ADD COLUMN IF NOT EXISTS porciones       INT UNSIGNED NOT NULL DEFAULT 0,
              ADD COLUMN IF NOT EXISTS gramos_porcion  INT UNSIGNED NOT NULL DEFAULT 0
        ");
    } catch (\Exception $e) { /* silencioso — si ya existen no falla */ }
}

/** Normaliza fecha enviada desde input datetime-local para MySQL DATETIME */
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
