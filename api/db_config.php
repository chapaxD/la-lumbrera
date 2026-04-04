<?php
// ============================================================
//  CONFIGURACION DE BASE DE DATOS
//  Detecta automaticamente si es local o produccion (TiDB Cloud)
// ============================================================

$serverName = $_SERVER['SERVER_NAME'] ?? '';
$esLocal = in_array($serverName, ['localhost', '127.0.0.1'])
        || str_starts_with($serverName, '192.168.')
        || str_starts_with($serverName, '10.')
        || (getenv('APP_ENV') === 'local');

if ($esLocal) {
    // WAMP / Laragon local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'botanero_ventas');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
    define('DB_SSL',  false);
} else {
    // TiDB Cloud produccion
    define('DB_HOST', 'gateway01.us-east-1.prod.aws.tidbcloud.com');
    define('DB_NAME', 'botanero_ventas');
    define('DB_USER', '2QmBXDx15RoezTK.root');
    define('DB_PASS', '1uiSTs5CZmPUjA46');
    define('DB_PORT', '4000');
    define('DB_SSL',  true);
}

define('DB_CHARSET', 'utf8mb4');
