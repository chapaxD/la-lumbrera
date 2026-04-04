<?php
include_once "funciones.php";
echo "Configured Timezone: " . date_default_timezone_get() . "\n";
echo "Current Server Date (with TZ): " . date("Y-m-d") . "\n";
echo "Current Server Time (with TZ): " . date("H:i:s") . "\n";
$bd = conectarBaseDatos();
$sentencia = $bd->query("SELECT * FROM reservas ORDER BY id DESC LIMIT 5");
$reservas = $sentencia->fetchAll();
echo "Last 5 Reservations:\n";
echo json_encode($reservas, JSON_PRETTY_PRINT);
?>
