<?php
// Zona horaria de Bolivia (UTC-4)
date_default_timezone_set('America/La_Paz');

// Evitar que errores PHP contaminen la respuesta JSON
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(0);

// Responder OPTIONS (preflight CORS) inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');