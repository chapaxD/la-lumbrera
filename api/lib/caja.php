<?php
// lib/caja.php - Gestión de Caja Diaria y Gastos

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
