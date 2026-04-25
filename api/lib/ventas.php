<?php
// lib/ventas.php - Gestión de Ventas y Estadísticas

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

    $sql = "SELECT 
                COUNT(*) AS total, 
                IFNULL(SUM(ventas.total), 0) AS totalDinero,
                COUNT(CASE WHEN tipo_orden = 'LOCAL' OR tipo_orden IS NULL OR tipo_orden = '' THEN 1 END) AS totalLocales,
                COUNT(CASE WHEN tipo_orden = 'DELIVERY' OR tipo_orden = 'LLEVAR' THEN 1 END) AS totalDelivery
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
    if ($tipo_orden === 'LOCAL' && isset($venta->idMesa) && $venta->idMesa) {
        $hoy = date('Y-m-d');
        $stmtReserva = $bd->prepare("SELECT adelanto FROM reservas WHERE idMesa = ? AND fecha = ? AND estado IN ('COMPLETADA','SENTADA') AND adelanto > 0 ORDER BY id DESC LIMIT 1");
        $stmtReserva->execute([$venta->idMesa, $hoy]);
        $reserva = $stmtReserva->fetch();
        if ($reserva && $reserva->adelanto > 0) {
            $adelanto = (float)$reserva->adelanto;
            if ($adelanto >= $venta->total) {
                $venta->total = 0;
            } else {
                $venta->total = $venta->total - $adelanto;
            }
        }
    }

    $idUsuario = (int)($venta->idUsuario ?? 0);
    if ($idUsuario === 0) $idUsuario = 1;

    $idMesa = ($tipo_orden === 'LLEVAR' || $tipo_orden === 'DELIVERY') ? 0 : $venta->idMesa;

    $sentencia = $bd->prepare("INSERT INTO ventas (idMesa, cliente, fecha, total, pagado, idUsuario, metodoPago, montoEfectivo, montoTarjeta, montoQR, tipo_orden, direccion, telefono, estado_delivery) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $exitoVenta = $sentencia->execute([$idMesa, $venta->cliente, date("Y-m-d H:i:s"), $venta->total, $venta->pagado,  $idUsuario, $metodoPago, $montoEfectivo, $montoTarjeta, $montoQR, $tipo_orden, $direccion, $telefono, $estado_delivery]);
    
    if (!$exitoVenta) return false;

    $idVenta = $bd->lastInsertId();
    registrarInsumosVenta($venta->insumos, $idVenta, $idUsuario);

    // Gestionar orden activa
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
            $bd->prepare("UPDATE ordenes_activas SET estado='pagada' WHERE id=?")->execute([$ordenActiva->id]);
            $bd->prepare("UPDATE items_orden SET pagado=1 WHERE idOrden=?")->execute([$ordenActiva->id]);
        } else {
            $bd->prepare("DELETE FROM ordenes_activas WHERE id=?")->execute([$ordenActiva->id]);
        }
    }

    if ($tipo_orden === 'LOCAL') completarReservaPorMesa($venta->idMesa);

    return true;
}

function registrarInsumosVenta($insumos, $idVenta, $idUsuario)
{
    $bd = conectarBaseDatos();
    _asegurarTipoVentaInsumos();
    foreach ($insumos as $insumo) {
        $arr = (array)$insumo;
        $sentencia = $bd->prepare("INSERT INTO insumos_venta(idInsumo, precio, cantidad, idVenta) VALUES(?,?,?,?)");
        $sentencia->execute([$arr['id'], $arr['precio'], $arr['cantidad'], $idVenta]);
        
        $nec = expandirNecesidadesLineaPedido($bd, $arr);
        aplicarDescuentoStockPorMapa($bd, $nec, $idUsuario, 'VENTA', null);
    }
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

function actualizarEstadoDelivery($idVenta, $estado)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE ventas SET estado_delivery = ? WHERE id = ?");
    return $sentencia->execute([$estado, $idVenta]);
}

// ─── ESTADÍSTICAS ─────────────────────────────────────────────────────────────

function obtenerVentasPorMesesDeUsuario($anio, $idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT MONTH(fecha) AS mes, SUM(total) AS totalVentas FROM ventas WHERE YEAR(fecha) = ? AND idUsuario = ? GROUP BY MONTH(fecha) ORDER BY mes ASC");
    $sentencia->execute([$anio, $idUsuario]);
    return $sentencia->fetchAll();
}

function obtenerVentasPorDiaMes($mes, $anio, $idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DAY(fecha) AS dia, SUM(total) AS totalVentas FROM ventas WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND idUsuario = ? GROUP BY DAY(fecha) ORDER BY dia ASC");
    $sentencia->execute([$mes, $anio, $idUsuario]);
    return $sentencia->fetchAll();
}

function obtenerVentasSemanaDeUsuario($idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DAYNAME(fecha) AS dia, DAYOFWEEK(fecha) AS numeroDia, SUM(total) AS totalVentas FROM ventas WHERE YEARWEEK(fecha)=YEARWEEK(CURDATE()) AND idUsuario = ? GROUP BY dia, numeroDia ORDER BY numeroDia ASC");
    $sentencia->execute([$idUsuario]);
    return $sentencia->fetchAll();
}

function obtenerInsumosMasVendidos($limite)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT SUM(insumos_venta.precio * insumos_venta.cantidad ) AS total, insumos.nombre, insumos.tipo, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria FROM insumos_venta INNER JOIN insumos ON insumos.id = insumos_venta.idInsumo LEFT JOIN categorias ON categorias.id = insumos.categoria GROUP BY insumos_venta.idInsumo, insumos.nombre, insumos.tipo, categoria ORDER BY total DESC LIMIT ?");
    $sentencia->bindValue(1, (int)$limite, \PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}

function obtenerTotalesPorMesa($limite = 5)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT SUM(total) AS total, idMesa FROM ventas GROUP BY idMesa ORDER BY total DESC LIMIT ?");
    $sentencia->bindValue(1, (int)$limite, \PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}

function obtenerVentasDelDia()
{
    $bd = conectarBaseDatos();
    $hoy = date('Y-m-d');
    $sentencia = $bd->prepare("SELECT IFNULL(SUM(total),0) AS totalVentasHoy FROM ventas WHERE fecha >= ? AND fecha < DATE_ADD(?, INTERVAL 1 DAY)");
    $sentencia->execute([$hoy, $hoy]);
    return $sentencia->fetchObject()->totalVentasHoy;
}

function obtenerTotalVentas()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT IFNULL(SUM(total),0) AS totalVentas FROM ventas");
    return $sentencia->fetchObject()->totalVentas;
}

function cantidadVentasDia()
{
    $bd = conectarBaseDatos();
    $hoy = date('Y-m-d');
    $sentencia = $bd->prepare("SELECT COUNT(*) AS cantidad FROM ventas WHERE fecha >= ? AND fecha < DATE_ADD(?, INTERVAL 1 DAY)");
    $sentencia->execute([$hoy, $hoy]);
    return (int)$sentencia->fetchObject()->cantidad;
}

function obtenerVentasUsuario($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT usuarios.nombre, SUM(ventas.total) AS totalVentas FROM ventas INNER JOIN usuarios ON usuarios.id = ventas.idUsuario WHERE (DATE(fecha) >= ? AND DATE(fecha) <= ?) GROUP BY ventas.idUsuario, usuarios.nombre");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}

function obtenerVentasPorHora($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DATE_FORMAT(fecha,'%H') AS hora, SUM(total) as totalVentas FROM ventas WHERE (DATE(fecha) >= ? AND DATE(fecha) <= ?) GROUP BY DATE_FORMAT(fecha,'%H') ORDER BY hora ASC");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}

function obtenerVentasPorMeses($anio)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT MONTH(fecha) AS mes, SUM(total) AS totalVentas FROM ventas WHERE YEAR(fecha) = ? GROUP BY MONTH(fecha) ORDER BY mes ASC");
    $sentencia->execute([$anio]);
    return $sentencia->fetchAll();
}

function obtenerVentasDiasSemana()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT DAYNAME(fecha) AS dia, DAYOFWEEK(fecha) AS numeroDia, SUM(total) AS totalVentas FROM ventas WHERE YEARWEEK(fecha, 1)=YEARWEEK(CURDATE(), 1) GROUP BY dia, numeroDia ORDER BY (numeroDia + 5) % 7 ASC");
    return $sentencia->fetchAll();
}

function obtenerResumenVentasPorDia($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT DATE(fecha) as fecha, COUNT(*) as numVentas, SUM(total) as totalVentas FROM ventas WHERE DATE(fecha) >= ? AND DATE(fecha) <= ? GROUP BY DATE(fecha) ORDER BY DATE(fecha) ASC");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}

function obtenerTopInsumosPorPeriodo($fechaInicio, $fechaFin, $limite = 5)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT insumos.nombre, SUM(insumos_venta.cantidad) as totalVendidos, SUM(insumos_venta.cantidad * insumos_venta.precio) as totalDinero, IFNULL(categorias.nombre, 'NO DEFINIDA') as categoria FROM insumos_venta INNER JOIN ventas ON ventas.id = insumos_venta.idVenta INNER JOIN insumos ON insumos.id = insumos_venta.idInsumo LEFT JOIN categorias ON categorias.id = insumos.categoria WHERE DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ? GROUP BY insumos_venta.idInsumo, insumos.nombre, categoria ORDER BY totalVendidos DESC LIMIT ?");
    $sentencia->bindValue(1, $fechaInicio);
    $sentencia->bindValue(2, $fechaFin);
    $sentencia->bindValue(3, (int)$limite, \PDO::PARAM_INT);
    $sentencia->execute();
    return $sentencia->fetchAll();
}

function obtenerVentasPorUsuario($fechaInicio, $fechaFin)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT IFNULL(SUM(ventas.total), 0) AS total, usuarios.nombre FROM ventas INNER JOIN usuarios ON usuarios.id = ventas.idUsuario WHERE (DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?) GROUP BY ventas.idUsuario, usuarios.nombre");
    $sentencia->execute([$fechaInicio, $fechaFin]);
    return $sentencia->fetchAll();
}
