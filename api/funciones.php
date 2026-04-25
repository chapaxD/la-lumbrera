<?php
date_default_timezone_set('America/La_Paz');
define('DIRECTORIO', './fotos/');

// Módulos de la API
include_once "lib/db.php";
include_once "lib/usuarios.php";
include_once "lib/mesas.php";
include_once "lib/stock.php";
include_once "lib/clientes.php";
include_once "lib/ventas.php";
include_once "lib/caja.php";
include_once "lib/facturas.php";
include_once "lib/reservas.php";
include_once "lib/deliveries.php";
include_once "lib/cocina.php";
include_once "lib/config.php";
include_once "lib/reportes.php";
include_once "lib/despiece.php";
include_once "lib/cancelaciones.php";









// ─── CLIENTES ─────────────────────────────────────────────────────────────────
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Singleton de conexión PDO.
 * Una sola conexión TCP+SSL por request → crítico para TiDB Cloud gratuito.
 */








