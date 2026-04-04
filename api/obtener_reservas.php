<?php
include_once "encabezado.php";
include_once "funciones.php";
$reservas = obtenerReservas();
echo json_encode($reservas);
