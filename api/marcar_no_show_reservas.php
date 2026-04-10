<?php
include_once "encabezado.php";
include_once "funciones.php";

$bd = conectarBaseDatos();
$ahora = date('Y-m-d H:i:s');
$hoy = date('Y-m-d');

// Buscar reservas de hoy en estado PENDIENTE o CONFIRMADA que ya pasaron 15 minutos de la hora
$sql = "SELECT id, idMesa, nombre_cliente, adelanto, hora FROM reservas WHERE fecha = ? AND estado IN ('PENDIENTE','CONFIRMADA') AND adelanto > 0 AND TIMESTAMPADD(MINUTE, 15, CONCAT(fecha, ' ', hora)) < ?";
$stmt = $bd->prepare($sql);
$stmt->execute([$hoy, $ahora]);
$reservas = $stmt->fetchAll(PDO::FETCH_OBJ);

$resultados = [];
foreach ($reservas as $res) {
    // 1. Marcar la reserva como NO-SHOW
    $bd->prepare("UPDATE reservas SET estado='NO-SHOW' WHERE id=?")->execute([$res->id]);
    // 2. Registrar el adelanto como venta en caja (venta rápida sin detalle de insumos)
    $bd->prepare("INSERT INTO ventas (idMesa, cliente, fecha, total, pagado, idUsuario, metodoPago, montoEfectivo, montoTarjeta, montoQR, tipo_orden, direccion, telefono, estado_delivery) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $res->idMesa,
            $res->nombre_cliente,
            $ahora,
            $res->adelanto,
            $res->adelanto,
            1, // idUsuario admin
            'EFECTIVO',
            $res->adelanto,
            0,
            0,
            'LOCAL',
            null,
            null,
            null
        ]);
    $resultados[] = "Reserva ID {$res->id} marcada NO-SHOW y adelanto registrado en caja.";
}
echo json_encode($resultados);