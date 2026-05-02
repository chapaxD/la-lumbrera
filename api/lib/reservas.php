<?php
// lib/reservas.php - Gestión de Reservas

function completarReservaPorMesa($idMesa)
{
    $bd = conectarBaseDatos();
    $hoy = date("Y-m-d");
    
    // Si idMesa es numérico pero viene como string, intentamos ambos.
    // También cerramos Eventos Totales (idMesa IS NULL) si se está cobrando una mesa importante (asumimos que el evento terminó)
    $sentencia = $bd->prepare("UPDATE reservas SET estado = 'COMPLETADA' WHERE (idMesa = ? OR (idMesa IS NULL AND ? != '0')) AND fecha = ? AND estado IN ('PENDIENTE', 'CONFIRMADA', 'SENTADA')");
    return $sentencia->execute([(string)$idMesa, (string)$idMesa, $hoy]);
}

function registrarReserva($reserva)
{
    $bd = conectarBaseDatos();

    $fecha   = $reserva->fecha;
    $hora    = $reserva->hora;
    $idMesa  = isset($reserva->idMesa) ? $reserva->idMesa : null;
    $esEvento = ($idMesa === null || $idMesa === '');

    // 1. Detectar solapamiento
    if ($esEvento) {
        // Si es evento total: bloquear si existe CUALQUIER reserva activa ese día
        $check = $bd->prepare("
            SELECT r.*, u.nombre AS usuarioNombre
            FROM reservas r
            LEFT JOIN usuarios u ON r.idUsuario = u.id
            WHERE r.fecha = ? AND r.estado IN ('PENDIENTE','CONFIRMADA')
            LIMIT 1
        ");
        $check->execute([$fecha]);
    } else {
        // Si es mesa específica: bloquear si hay un evento total ese día,
        // O si hay reserva para la misma mesa dentro de ±2h
        $check = $bd->prepare("
            SELECT r.*, u.nombre AS usuarioNombre
            FROM reservas r
            LEFT JOIN usuarios u ON r.idUsuario = u.id
            WHERE r.fecha = ?
              AND r.estado IN ('PENDIENTE','CONFIRMADA')
              AND (
                  r.idMesa IS NULL
                  OR (r.idMesa = ? AND ABS(TIMESTAMPDIFF(MINUTE, r.hora, ?)) < 120)
              )
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

    // 2. Verificar si la mesa está OCUPADA actualmente (solo si la reserva es para hoy)
    if (!$esEvento && $fecha === date('Y-m-d')) {
        $checkOcupada = $bd->prepare("SELECT id FROM ordenes_activas WHERE tipo='LOCAL' AND referencia = ? LIMIT 1");
        $checkOcupada->execute([(string)$idMesa]);
        if ($checkOcupada->fetch()) {
             return ['ok' => false, 'error' => 'MESA_OCUPADA_AHORA'];
        }
    }

    $sentencia = $bd->prepare("INSERT INTO reservas (nombre_cliente, telefono, fecha, hora, personas, idMesa, notas, menu_evento, costo_total_evento, adelanto, idUsuario, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE')");
    $ok = $sentencia->execute([
        $reserva->nombre_cliente,
        $reserva->telefono,
        $fecha,
        $hora,
        $reserva->personas,
        $esEvento ? null : $idMesa,
        $reserva->notas,
        isset($reserva->menu_evento) ? $reserva->menu_evento : null,
        isset($reserva->costo_total_evento) && $reserva->costo_total_evento > 0 ? (float)$reserva->costo_total_evento : 0,
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

function cambiarEstadoReserva($id, $estado, $idUsuario = 1)
{
    $bd = conectarBaseDatos();

    // Si el nuevo estado es NO-SHOW, cobramos el adelanto si existe
    if ($estado === 'NO-SHOW') {
        $stmtCheck = $bd->prepare("SELECT estado, adelanto, nombre_cliente, idMesa FROM reservas WHERE id = ?");
        $stmtCheck->execute([$id]);
        $res = $stmtCheck->fetch();

        // Solo cobramos si no estaba ya en NO-SHOW y tiene adelanto > 0
        if ($res && $res->estado !== 'NO-SHOW' && $res->adelanto > 0) {
            $ahora = date('Y-m-d H:i:s');
            // Registrar el adelanto como venta en caja
            $sqlVenta = "INSERT INTO ventas (idMesa, cliente, fecha, total, pagado, idUsuario, metodoPago, montoEfectivo, montoTarjeta, montoQR, tipo_orden) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $bd->prepare($sqlVenta)->execute([
                $res->idMesa ?? 0,
                $res->nombre_cliente . " (ADELANTO NO-SHOW)",
                $ahora,
                $res->adelanto,
                $res->adelanto,
                $idUsuario,
                'EFECTIVO',
                $res->adelanto,
                0,
                0,
                'LOCAL'
            ]);
        }
    }

    $sentencia = $bd->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
    return $sentencia->execute([$estado, $id]);
}

