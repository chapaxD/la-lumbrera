<?php
// ============================================================
//  CONFIGURACION DE BASE DE DATOS
//  Detecta automaticamente si es local o produccion
// ============================================================

$esLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', ''])
        || str_starts_with($_SERVER['SERVER_NAME'] ?? '', '192.168.');

if ($esLocal) {
    // WAMP / Laragon local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'botanero_ventas');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // InfinityFree produccion
    define('DB_HOST', 'sql105.infinityfree.com');
    define('DB_NAME', 'if0_41574140_la_lumbrera');
    define('DB_USER', 'if0_41574140');
    define('DB_PASS', 'gONN7GH7aH');
}

define('DB_CHARSET', 'utf8mb4');
