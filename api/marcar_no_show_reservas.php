<?php
include_once "encabezado.php";
include_once "funciones.php";

$bd = conectarBaseDatos();
$ahora = date('Y-m-d H:i:s');
$hoy = date('Y-m-d');

// Buscar reservas de hoy en estado PENDIENTE o CONFIRMADA que ya pasaron 30 minutos de la hora
$sql = "SELECT id, idMesa, nombre_cliente, adelanto, hora FROM reservas WHERE fecha = ? AND estado IN ('PENDIENTE','CONFIRMADA') AND TIMESTAMPADD(MINUTE, 30, CONCAT(fecha, ' ', hora)) < ?";
$stmt = $bd->prepare($sql);
$stmt->execute([$hoy, $ahora]);
$reservas = $stmt->fetchAll(PDO::FETCH_OBJ);

$resultados = [];
foreach ($reservas as $res) {
    // Usamos la función central que ahora maneja el cobro automático si hay adelanto
    cambiarEstadoReserva($res->id, 'NO-SHOW', 1); // 1 = Admin
    $resultados[] = "Reserva ID {$res->id} ({$res->nombre_cliente}) marcada NO-SHOW. Si tenía adelanto, se registró en caja.";
}
echo json_encode($resultados);